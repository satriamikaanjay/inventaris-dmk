package com.example.aplikasi_inventaris_dmk.network

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.LocationManager
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import android.net.wifi.WifiInfo
import android.net.wifi.WifiManager
import android.os.Build
import android.os.Handler
import android.os.Looper
import androidx.core.content.ContextCompat
import java.util.concurrent.CopyOnWriteArraySet

object WifiAccessGuard {

    const val REQUIRED_SSID = "TP-Link_456A"
    val ALLOWED_SSIDS = listOf(REQUIRED_SSID)

    sealed class WifiCheckResult {
        data class Allowed(val ssid: String) : WifiCheckResult()
        object PermissionRequired : WifiCheckResult()
        object LocationDisabled : WifiCheckResult()
        object NotConnectedToWifi : WifiCheckResult()
        data class WrongWifi(val currentSsid: String) : WifiCheckResult()
        object UnknownSsid : WifiCheckResult()
    }

    private val mainHandler = Handler(Looper.getMainLooper())
    private val listeners = CopyOnWriteArraySet<(WifiCheckResult) -> Unit>()

    @Volatile
    private var appContext: Context? = null

    @Volatile
    private var activeWifiNetwork: Network? = null

    @Volatile
    private var observedSsid: String? = null

    @Volatile
    private var networkCallback: ConnectivityManager.NetworkCallback? = null

    fun startMonitoring(context: Context) {
        val ctx = context.applicationContext
        appContext = ctx
        if (networkCallback == null) {
            registerCallbackInternal(ctx)
        }
    }

    fun refreshMonitoring(context: Context) {
        val ctx = context.applicationContext
        appContext = ctx
        unregisterCallbackInternal(ctx)
        registerCallbackInternal(ctx)
    }

    fun addListener(listener: (WifiCheckResult) -> Unit) {
        listeners.add(listener)
    }

    fun removeListener(listener: (WifiCheckResult) -> Unit) {
        listeners.remove(listener)
    }

    fun isAllowed(context: Context): Boolean {
        return checkWifiAccess(context) is WifiCheckResult.Allowed
    }

    fun hasLocationPermission(context: Context): Boolean {
        return ContextCompat.checkSelfPermission(
            context,
            Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED
    }

    fun isLocationEnabled(context: Context): Boolean {
        val lm = context.applicationContext.getSystemService(Context.LOCATION_SERVICE) as? LocationManager
            ?: return false
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            lm.isLocationEnabled
        } else {
            @Suppress("DEPRECATION")
            lm.isProviderEnabled(LocationManager.GPS_PROVIDER) ||
                lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
        }
    }

    @SuppressLint("MissingPermission")
    fun checkWifiAccess(context: Context): WifiCheckResult {
        val ctx = context.applicationContext
        if (appContext == null) {
            startMonitoring(ctx)
        }

        // 1. Cek apakah perangkat terhubung ke jaringan Wi-Fi
        if (!isConnectedToWifi(ctx)) {
            return WifiCheckResult.NotConnectedToWifi
        }

        // 2. Di Android, pembacaan nama Wi-Fi (SSID) wajib izin lokasi presisi
        if (!hasLocationPermission(ctx)) {
            return WifiCheckResult.PermissionRequired
        }

        // 3. Layanan Lokasi (GPS) harus aktif agar Android tidak menyamarkan SSID
        if (!isLocationEnabled(ctx)) {
            return WifiCheckResult.LocationDisabled
        }

        // 4. Baca SSID dari NetworkCallback (API 31+) dan fallback ke WifiManager
        val ssid = resolveCurrentSsid(ctx)
        if (ssid.isNullOrBlank()) {
            return WifiCheckResult.UnknownSsid
        }

        val matched = ALLOWED_SSIDS.any { it.equals(ssid, ignoreCase = true) }
        return if (matched) {
            WifiCheckResult.Allowed(ssid)
        } else {
            WifiCheckResult.WrongWifi(ssid)
        }
    }

    private fun isConnectedToWifi(context: Context): Boolean {
        val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager
            ?: return false

        val activeNet = cm.activeNetwork
        val activeCaps = if (activeNet != null) cm.getNetworkCapabilities(activeNet) else null
        if (activeCaps?.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) == true) {
            return true
        }

        val observedNet = activeWifiNetwork
        if (observedNet != null) {
            val caps = cm.getNetworkCapabilities(observedNet)
            if (caps?.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) == true) {
                return true
            }
        }

        @Suppress("DEPRECATION")
        for (net in cm.allNetworks) {
            val caps = cm.getNetworkCapabilities(net)
            if (caps?.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) == true) {
                return true
            }
        }
        return false
    }

    @SuppressLint("MissingPermission")
    @Suppress("DEPRECATION")
    private fun resolveCurrentSsid(context: Context): String? {
        // Prioritas 1: SSID terbaru dari NetworkCallback (FLAG_INCLUDE_LOCATION_INFO pada API 31+)
        val fromCallback = sanitizeSsid(observedSsid)
        if (!fromCallback.isNullOrBlank()) {
            return fromCallback
        }

        // Prioritas 2: TransportInfo dari ConnectivityManager (API 29+)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager
            val wifiNet = activeWifiNetwork ?: cm?.activeNetwork
            val caps = if (wifiNet != null) cm?.getNetworkCapabilities(wifiNet) else null
            val info = caps?.transportInfo as? WifiInfo
            val fromCaps = sanitizeSsid(info?.ssid)
            if (!fromCaps.isNullOrBlank()) {
                observedSsid = fromCaps
                return fromCaps
            }
        }

        // Prioritas 3: WifiManager.connectionInfo (kompatibel lintas versi Android)
        return try {
            val wm = context.applicationContext.getSystemService(Context.WIFI_SERVICE) as? WifiManager
            val rawSsid = wm?.connectionInfo?.ssid
            sanitizeSsid(rawSsid)?.also { observedSsid = it }
        } catch (_: Exception) {
            null
        }
    }

    private fun sanitizeSsid(raw: String?): String? {
        if (raw.isNullOrBlank()) return null
        val cleaned = raw.trim().removeSurrounding("\"").trim()
        if (cleaned.isBlank() ||
            cleaned.equals("<unknown ssid>", ignoreCase = true) ||
            cleaned.equals("0x", ignoreCase = true)
        ) {
            return null
        }
        return cleaned
    }

    private fun registerCallbackInternal(context: Context) {
        val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager ?: return
        val request = NetworkRequest.Builder()
            .addTransportType(NetworkCapabilities.TRANSPORT_WIFI)
            .build()

        val callback = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            object : ConnectivityManager.NetworkCallback(FLAG_INCLUDE_LOCATION_INFO) {
                override fun onAvailable(network: Network) {
                    activeWifiNetwork = network
                    notifyListeners(context)
                }

                override fun onCapabilitiesChanged(network: Network, networkCapabilities: NetworkCapabilities) {
                    activeWifiNetwork = network
                    val info = networkCapabilities.transportInfo as? WifiInfo
                    val ssid = sanitizeSsid(info?.ssid)
                    if (!ssid.isNullOrBlank()) {
                        observedSsid = ssid
                    }
                    notifyListeners(context)
                }

                override fun onLost(network: Network) {
                    if (activeWifiNetwork == network) {
                        activeWifiNetwork = null
                        observedSsid = null
                    }
                    notifyListeners(context)
                }
            }
        } else {
            object : ConnectivityManager.NetworkCallback() {
                override fun onAvailable(network: Network) {
                    activeWifiNetwork = network
                    notifyListeners(context)
                }

                override fun onCapabilitiesChanged(network: Network, networkCapabilities: NetworkCapabilities) {
                    activeWifiNetwork = network
                    notifyListeners(context)
                }

                override fun onLost(network: Network) {
                    if (activeWifiNetwork == network) {
                        activeWifiNetwork = null
                        observedSsid = null
                    }
                    notifyListeners(context)
                }
            }
        }

        try {
            cm.registerNetworkCallback(request, callback)
            networkCallback = callback
        } catch (_: Exception) {
        }
    }

    private fun unregisterCallbackInternal(context: Context) {
        val cb = networkCallback ?: return
        val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager ?: return
        try {
            cm.unregisterNetworkCallback(cb)
        } catch (_: Exception) {
        }
        networkCallback = null
        observedSsid = null
    }

    private fun notifyListeners(context: Context) {
        mainHandler.post {
            val result = checkWifiAccess(context)
            for (listener in listeners) {
                listener(result)
            }
        }
    }
}
