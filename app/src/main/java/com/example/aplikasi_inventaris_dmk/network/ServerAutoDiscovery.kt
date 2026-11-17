package com.example.aplikasi_inventaris_dmk.network

import android.content.Context
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.os.Handler
import android.os.Looper
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.Inet4Address
import java.net.InetSocketAddress
import java.net.NetworkInterface
import java.net.Socket
import java.net.URL
import java.util.concurrent.CopyOnWriteArrayList
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicBoolean
import java.util.concurrent.atomic.AtomicReference

object ServerAutoDiscovery {

    data class DiscoveryResult(
        val foundUrl: String?,
        val scannedLabel: String,
        val isBlockedByAdmin: Boolean = false
    )

    private data class LocalNetworkInfo(
        val ip: String,
        val prefixLength: Int,
        val gateway: String?
    )

    private val mainHandler = Handler(Looper.getMainLooper())
    private val isScanning = AtomicBoolean(false)
    private val pendingListeners = CopyOnWriteArrayList<(DiscoveryResult) -> Unit>()

    fun getActiveWifiSubnetLabel(context: Context): String {
        val nets = collectLocalNetworks(context.applicationContext)
        if (nets.isEmpty()) return "jaringan lokal"
        val first = nets.first().ip
        val parts = first.split(".")
        return if (parts.size == 4) "${parts[0]}.${parts[1]}.${parts[2]}.x" else first
    }

    fun discoverAsync(
        context: Context,
        onStatus: ((String) -> Unit)? = null,
        onFinished: (DiscoveryResult) -> Unit
    ) {
        val appContext = context.applicationContext
        pendingListeners.add(onFinished)
        val label = getActiveWifiSubnetLabel(appContext)
        mainHandler.post {
            onStatus?.invoke("Mencari server otomatis di $label...")
        }

        if (!isScanning.compareAndSet(false, true)) {
            return
        }

        Thread {
            val result = try {
                discoverSync(appContext, label)
            } catch (_: Exception) {
                DiscoveryResult(null, label, false)
            } finally {
                isScanning.set(false)
            }

            val listeners = ArrayList(pendingListeners)
            pendingListeners.clear()
            mainHandler.post {
                for (listener in listeners) {
                    listener(result)
                }
            }
        }.start()
    }

    private fun discoverSync(context: Context, label: String): DiscoveryResult {
        val localNets = collectLocalNetworks(context)

        val priorityIps = LinkedHashSet<String>()
        val savedHost = ApiClient.extractHostIp(ApiClient.getBaseUrl(context))
        if (isValidIpv4(savedHost)) priorityIps.add(savedHost)
        if (isValidIpv4(ApiClient.DEFAULT_HOST_IP)) priorityIps.add(ApiClient.DEFAULT_HOST_IP)

        for (net in localNets) {
            if (!net.gateway.isNullOrBlank() && isValidIpv4(net.gateway)) {
                priorityIps.add(net.gateway)
            }
        }
        // Gateway hotspot Windows & USB adb reverse
        priorityIps.add("192.168.137.1")
        priorityIps.add("127.0.0.1")

        // 1. Cek cepat IP prioritas (IP terakhir, gateway, hotspot Windows, USB localhost)
        for (ip in priorityIps) {
            val probe = probeHostBothPorts(ip, socketTimeoutMs = 350)
            if (probe != null) {
                return DiscoveryResult(probe.first, label, probe.second)
            }
        }

        // 2. Bangun daftar IP pada subnet Wi-Fi / Hotspot lokal saat ini
        val subnetCandidates = LinkedHashSet<String>()
        val ownIps = localNets.map { it.ip }.toSet()

        for (net in localNets) {
            val octets = net.ip.split(".").mapNotNull { it.toIntOrNull() }
            if (octets.size != 4) continue
            val basePrefix3 = "${octets[0]}.${octets[1]}.${octets[2]}"

            // Prioritaskan IP umum (1..30, 100..130, 200..254, lalu sisanya)
            val orderedLastOctets = LinkedHashSet<Int>()
            for (i in 1..30) orderedLastOctets.add(i)
            for (i in 100..135) orderedLastOctets.add(i)
            for (i in 31..99) orderedLastOctets.add(i)
            for (i in 136..254) orderedLastOctets.add(i)

            for (last in orderedLastOctets) {
                val candidate = "$basePrefix3.$last"
                if (candidate !in ownIps && candidate !in priorityIps) {
                    subnetCandidates.add(candidate)
                }
            }

            // Jika subnet /22 atau /23 (mis. Wi-Fi kampus), cek blok /24 tetangganya juga
            if (net.prefixLength in 22..23) {
                val blockCount = if (net.prefixLength == 22) 4 else 2
                val alignedThird = (octets[2] / blockCount) * blockCount
                for (offset in 0 until blockCount) {
                    val third = alignedThird + offset
                    if (third == octets[2] || third !in 0..255) continue
                    val neighborPrefix = "${octets[0]}.${octets[1]}.$third"
                    for (last in 1..254) {
                        val candidate = "$neighborPrefix.$last"
                        if (candidate !in ownIps && candidate !in priorityIps) {
                            subnetCandidates.add(candidate)
                        }
                    }
                }
            }
        }

        if (subnetCandidates.isEmpty()) {
            return DiscoveryResult(null, label, false)
        }

        val foundUrlRef = AtomicReference<String?>(null)
        val blockedRef = AtomicBoolean(false)
        val stopFlag = AtomicBoolean(false)

        val executor = Executors.newFixedThreadPool(48)
        try {
            for (ip in subnetCandidates) {
                if (stopFlag.get()) break
                executor.execute {
                    if (stopFlag.get()) return@execute
                    val res = probeHostBothPorts(ip, socketTimeoutMs = 320, stopFlag = stopFlag)
                    if (res != null && foundUrlRef.compareAndSet(null, res.first)) {
                        blockedRef.set(res.second)
                        stopFlag.set(true)
                    }
                }
            }
            executor.shutdown()
            executor.awaitTermination(8, TimeUnit.SECONDS)
        } finally {
            stopFlag.set(true)
            executor.shutdownNow()
        }

        return DiscoveryResult(foundUrlRef.get(), label, blockedRef.get())
    }

    private fun probeHostBothPorts(
        ip: String,
        socketTimeoutMs: Int,
        stopFlag: AtomicBoolean? = null
    ): Pair<String, Boolean>? {
        if (stopFlag?.get() == true) return null

        // Cek Port 80 (Laragon Apache) lebih dulu kecuali localhost USB
        if (ip != "127.0.0.1") {
            if (isTcpPortOpen(ip, 80, socketTimeoutMs)) {
                val apacheUrl = "http://$ip/INVENTARIS-DMK/public/api/"
                val check = verifyInventarisApi(apacheUrl)
                if (check != null) {
                    return apacheUrl to check
                }
            }
        }

        if (stopFlag?.get() == true) return null

        // Cek Port 8000 (php artisan serve / adb reverse)
        if (isTcpPortOpen(ip, 8000, socketTimeoutMs)) {
            val artisanUrl = "http://$ip:8000/api/"
            val check = verifyInventarisApi(artisanUrl)
            if (check != null) {
                return artisanUrl to check
            }
        }

        return null
    }

    private fun isTcpPortOpen(ip: String, port: Int, timeoutMs: Int): Boolean {
        return try {
            Socket().use { socket ->
                socket.connect(InetSocketAddress(ip, port), timeoutMs)
                true
            }
        } catch (_: Exception) {
            false
        }
    }

    /**
     * Mengembalikan null jika bukan server Inventaris DMK,
     * atau Boolean (true = diblokir master admin, false = aktif/allowed).
     */
    private fun verifyInventarisApi(baseApiUrl: String): Boolean? {
        var conn: HttpURLConnection? = null
        return try {
            val url = URL("${baseApiUrl}user-access-status")
            conn = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "GET"
                connectTimeout = 1500
                readTimeout = 2000
                setRequestProperty("Accept", "application/json")
                setRequestProperty("ngrok-skip-browser-warning", "true")
                instanceFollowRedirects = false
            }
            val code = conn.responseCode
            if (code != 200 && code != 403) return null

            val stream = if (code == 200) conn.inputStream else conn.errorStream
            val body = stream?.bufferedReader()?.use { it.readText() }?.trim() ?: return null
            if (!body.startsWith("{")) return null

            val json = JSONObject(body)
            val status = json.optString("status", "")
            val isInventarisEndpoint =
                json.has("allowed") ||
                    json.has("blocked") ||
                    status.equals("allowed", ignoreCase = true) ||
                    status.equals("blocked", ignoreCase = true)

            if (!isInventarisEndpoint) return null

            val isBlocked = json.optBoolean("blocked", false) ||
                (json.has("allowed") && !json.optBoolean("allowed", true)) ||
                status.equals("blocked", ignoreCase = true)

            isBlocked
        } catch (_: Exception) {
            null
        } finally {
            conn?.disconnect()
        }
    }

    private fun collectLocalNetworks(context: Context): List<LocalNetworkInfo> {
        val result = ArrayList<LocalNetworkInfo>()
        val seenIps = HashSet<String>()

        try {
            val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager
            val activeNet = cm?.activeNetwork
            val caps = if (activeNet != null) cm.getNetworkCapabilities(activeNet) else null
            val isWifiOrLan = caps?.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) == true ||
                caps?.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET) == true

            if (activeNet != null && isWifiOrLan) {
                val linkProps = cm.getLinkProperties(activeNet)
                val gateway = linkProps?.routes
                    ?.firstOrNull { it.isDefaultRoute && it.gateway is Inet4Address }
                    ?.gateway
                    ?.hostAddress

                linkProps?.linkAddresses?.forEach { linkAddr ->
                    val addr = linkAddr.address
                    if (addr is Inet4Address && !addr.isLoopbackAddress && addr.isSiteLocalAddress) {
                        val ip = addr.hostAddress ?: return@forEach
                        if (seenIps.add(ip)) {
                            result.add(LocalNetworkInfo(ip, linkAddr.prefixLength, gateway))
                        }
                    }
                }
            }
        } catch (_: Exception) {
        }

        // Fallback & dukungan Hotspot HP (ap0 / swlan0 / wlan0) via NetworkInterface
        try {
            val interfaces = NetworkInterface.getNetworkInterfaces()
            while (interfaces != null && interfaces.hasMoreElements()) {
                val nif = interfaces.nextElement()
                if (!nif.isUp || nif.isLoopback) continue
                val name = nif.name.lowercase()
                // Abaikan interface seluler agar tidak scan jaringan operator
                if (name.startsWith("rmnet") || name.startsWith("ccmni") || name.startsWith("pdp") || name.startsWith("tun")) {
                    continue
                }
                val isPreferredLan = name.startsWith("wlan") ||
                    name.startsWith("ap") ||
                    name.startsWith("swlan") ||
                    name.startsWith("wifi") ||
                    name.startsWith("eth") ||
                    name.startsWith("rndis")
                if (!isPreferredLan) continue

                for (ifaceAddr in nif.interfaceAddresses) {
                    val addr = ifaceAddr.address
                    if (addr is Inet4Address && !addr.isLoopbackAddress && addr.isSiteLocalAddress) {
                        val ip = addr.hostAddress ?: continue
                        if (seenIps.add(ip)) {
                            val prefix = ifaceAddr.networkPrefixLength.toInt().let { if (it in 8..30) it else 24 }
                            result.add(LocalNetworkInfo(ip, prefix, null))
                        }
                    }
                }
            }
        } catch (_: Exception) {
        }

        return result
    }

    private fun isValidIpv4(ip: String?): Boolean {
        if (ip.isNullOrBlank()) return false
        val parts = ip.split(".")
        if (parts.size != 4) return false
        return parts.all { part ->
            val n = part.toIntOrNull() ?: return@all false
            n in 0..255
        }
    }
}
