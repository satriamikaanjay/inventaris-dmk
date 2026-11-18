package com.example.aplikasi_inventaris_dmk

import android.Manifest
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.graphics.Color
import android.os.Build
import android.os.Bundle
import android.view.View
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import androidx.core.content.ContextCompat
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanItem
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import com.example.aplikasi_inventaris_dmk.network.ServerAutoDiscovery
import com.example.aplikasi_inventaris_dmk.network.ServerConfigDialog
import com.example.aplikasi_inventaris_dmk.network.UserAccessStatusResponse
import com.example.aplikasi_inventaris_dmk.notification.NotificationHelper
import com.example.aplikasi_inventaris_dmk.notification.PengajuanNotificationService
import java.util.Locale
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class HomeActivity : AppCompatActivity() {

    private var userId: Int = 0
    private var userName: String = "Pengguna"
    private var userEmail: String = ""
    private var userRole: String = ""
    private var latestPengajuanList: List<PengajuanItem> = emptyList()

    private val requestNotificationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { isGranted ->
        if (isGranted) {
            PengajuanNotificationService.startMonitoring(this)
        }
    }

    private val statusUpdateReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            val uid = if (userId > 0) userId else null
            val cached = PengajuanLocalStore.getSubmissionsForUser(this@HomeActivity, uid, userName)
            latestPengajuanList = cached
            updateStatsUi(cached)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_home)

        readUserIntent(intent)
        bindUserHeader()
        PengajuanLocalStore.saveActiveSession(this, userId, userName, userEmail, userRole)
        requestNotificationPermissionAndStartMonitor()

        // Navigasi ke Inventory (1x klik langsung berpindah)
        val openInventory = {
            val nextIntent = Intent(this, InventoryActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(nextIntent)
        }

        findViewById<LinearLayout>(R.id.btnNavHome)?.setOnClickListener {
            findViewById<ScrollView>(R.id.scrollViewProfile)?.smoothScrollTo(0, 0)
        }
        findViewById<LinearLayout>(R.id.btnNavInventory)?.setOnClickListener { openInventory() }
        findViewById<CardView>(R.id.btnAjukanBaru)?.setOnClickListener { openInventory() }

        // Navigasi ke Data Stok Barang (Sinkron Web)
        val openDataStok = {
            val nextIntent = Intent(this, DataStokActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(nextIntent)
        }
        findViewById<CardView>(R.id.btnDataStok)?.setOnClickListener { openDataStok() }
        findViewById<LinearLayout>(R.id.btnNavStok)?.setOnClickListener { openDataStok() }

        // Navigasi ke Scan QR
        val openScan = {
            val nextIntent = Intent(this, ScanActivity::class.java).apply {
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(nextIntent)
        }

        findViewById<CardView>(R.id.btnScanQR)?.setOnClickListener { openScan() }
        findViewById<android.view.View>(R.id.btnNavScan)?.setOnClickListener { openScan() }

        // Navigasi ke History (1x klik langsung berpindah)
        val openHistory = {
            val nextIntent = Intent(this, HistoryActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(nextIntent)
        }
        findViewById<LinearLayout>(R.id.btnNavHistory)?.setOnClickListener { openHistory() }
        findViewById<CardView>(R.id.cardLatestNotif)?.setOnClickListener { openHistory() }

        // Tombol Lonceng Notifikasi -> Tampilkan Popup Daftar Notifikasi Status Pengajuan (Menunggu, Diterima, Ditolak)
        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            showNotificationDialog(openHistory)
        }

        findViewById<ImageView>(R.id.ivUserIcon)?.setOnClickListener {
            showLogoutDialog()
        }

        findViewById<CardView>(R.id.cardHomeServerStatus)?.setOnClickListener {
            syncWithWebServer(allowAutoDiscover = true)
        }
        findViewById<TextView>(R.id.btnHomeConfigServer)?.setOnClickListener {
            ServerConfigDialog.show(this) {
                syncWithWebServer(allowAutoDiscover = false)
            }
        }

        // Ketika user menekan tombol Back di HP pada halaman Home -> muncul peringatan keluar & logout
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                showBackExitLogoutDialog()
            }
        })
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        readUserIntent(intent)
        bindUserHeader()
        PengajuanLocalStore.saveActiveSession(this, userId, userName, userEmail, userRole)
        PengajuanNotificationService.startMonitoring(this)
    }

    override fun onStart() {
        super.onStart()
        ContextCompat.registerReceiver(
            this,
            statusUpdateReceiver,
            IntentFilter(NotificationHelper.ACTION_STATUS_UPDATED),
            ContextCompat.RECEIVER_NOT_EXPORTED
        )
    }

    override fun onStop() {
        try {
            unregisterReceiver(statusUpdateReceiver)
        } catch (_: Exception) {
        }
        super.onStop()
    }

    override fun onResume() {
        super.onResume()
        syncWithWebServer(allowAutoDiscover = true)
    }

    private fun syncWithWebServer(allowAutoDiscover: Boolean = true) {
        val host = ApiClient.getDisplayHost(ApiClient.getBaseUrl(this))
        updateHomeServerStatus(
            connected = null,
            title = "Menyinkronkan dengan server...",
            subtitle = "Memeriksa koneksi web ($host)"
        )
        verifyUserAccessStillAllowed()
        fetchUserPengajuanStats(userId, userName, allowAutoDiscover)
    }

    private fun updateHomeServerStatus(connected: Boolean?, title: String, subtitle: String) {
        runOnUiThread {
            val card = findViewById<CardView>(R.id.cardHomeServerStatus) ?: return@runOnUiThread
            val dot = findViewById<View>(R.id.viewHomeServerDot) ?: return@runOnUiThread
            val tvTitle = findViewById<TextView>(R.id.tvHomeServerTitle) ?: return@runOnUiThread
            val tvSub = findViewById<TextView>(R.id.tvHomeServerSubtitle) ?: return@runOnUiThread

            tvTitle.text = title
            tvSub.text = subtitle
            when (connected) {
                true -> {
                    card.setCardBackgroundColor(Color.parseColor("#E8F5E9"))
                    dot.setBackgroundResource(R.drawable.bg_circle_blue)
                    tvTitle.setTextColor(Color.parseColor("#2E7D32"))
                    tvSub.setTextColor(Color.parseColor("#1B5E20"))
                }
                false -> {
                    card.setCardBackgroundColor(Color.parseColor("#FFEBEE"))
                    dot.setBackgroundResource(R.drawable.bg_circle_red)
                    tvTitle.setTextColor(Color.parseColor("#C62828"))
                    tvSub.setTextColor(Color.parseColor("#7F1D1D"))
                }
                null -> {
                    card.setCardBackgroundColor(Color.parseColor("#F1F5F9"))
                    dot.setBackgroundResource(R.drawable.bg_circle_orange)
                    tvTitle.setTextColor(Color.parseColor("#1E293B"))
                    tvSub.setTextColor(Color.parseColor("#64748B"))
                }
            }
        }
    }

    private fun verifyUserAccessStillAllowed() {
        ApiClient.instance.getUserAccessStatus().enqueue(object : Callback<UserAccessStatusResponse> {
            override fun onResponse(
                call: Call<UserAccessStatusResponse>,
                response: Response<UserAccessStatusResponse>
            ) {
                val body = response.body()
                val rawErr = if (!response.isSuccessful) {
                    try { response.errorBody()?.string() ?: "" } catch (_: Exception) { "" }
                } else ""
                val errJson = ApiClient.parseErrorJson(rawErr)

                val isMasterAdminBlocked =
                    body?.blocked == true ||
                    body?.allowed == false ||
                    body?.status.equals("blocked", ignoreCase = true) ||
                    (errJson != null && (
                        errJson.optBoolean("blocked", false) ||
                        (errJson.has("allowed") && !errJson.optBoolean("allowed", true)) ||
                        errJson.optString("status", "").equals("blocked", ignoreCase = true) ||
                        errJson.optString("message", "").contains("master admin", ignoreCase = true)
                    ))

                if (isMasterAdminBlocked && !isFinishing && !isDestroyed) {
                    PengajuanNotificationService.stopMonitoring(this@HomeActivity)
                    PengajuanLocalStore.clearActiveSession(this@HomeActivity)
                    Toast.makeText(
                        this@HomeActivity,
                        "Akses user di blokir oleh master admin",
                        Toast.LENGTH_LONG
                    ).show()
                    val loginIntent = Intent(this@HomeActivity, LoginActivity::class.java).apply {
                        flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                        putExtra("FORCE_BLOCKED_LOGOUT", true)
                    }
                    startActivity(loginIntent)
                    finish()
                }
            }

            override fun onFailure(call: Call<UserAccessStatusResponse>, t: Throwable) {
            }
        })
    }

    private fun requestNotificationPermissionAndStartMonitor() {
        NotificationHelper.ensureChannels(this)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) ==
                PackageManager.PERMISSION_GRANTED
            ) {
                PengajuanNotificationService.startMonitoring(this)
            } else {
                requestNotificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
            }
        } else {
            PengajuanNotificationService.startMonitoring(this)
        }
    }

    private fun readUserIntent(src: Intent?) {
        if (src == null) return
        val emailExtra = src.getStringExtra("USER_EMAIL")
        if (!emailExtra.isNullOrBlank()) userEmail = emailExtra

        val rawName = src.getStringExtra("USER_NAME") ?: ""
        if (rawName.isNotBlank()) {
            userName = rawName
        } else if (userEmail.isNotBlank() && userName == "Pengguna") {
            userName = userEmail.substringBefore("@").replaceFirstChar { it.uppercase() }
        }

        val roleExtra = src.getStringExtra("USER_ROLE")
        if (!roleExtra.isNullOrBlank()) userRole = roleExtra

        val idExtra = src.getIntExtra("USER_ID", 0)
        if (idExtra > 0) userId = idExtra
    }

    private fun bindUserHeader() {
        findViewById<TextView>(R.id.tvUserName)?.text = userName
        findViewById<TextView>(R.id.tvUserNik)?.text = when {
            userEmail.isNotBlank() -> userEmail
            userId > 0 -> String.format(Locale.US, "ID Pengguna: #%03d", userId)
            else -> "Pengguna Aktif"
        }
        findViewById<TextView>(R.id.tvAvatar)?.text = getInitials(userName)
    }

    private fun fetchUserPengajuanStats(userId: Int, userName: String, allowAutoDiscover: Boolean = true) {
        val uid = if (userId > 0) userId else null
        val localItems = PengajuanLocalStore.getSubmissionsForUser(this, uid, userName)

        ApiClient.instance.getUserPengajuan(uid, userName)
            .enqueue(object : Callback<PengajuanUserResponse> {
                override fun onResponse(
                    call: Call<PengajuanUserResponse>,
                    response: Response<PengajuanUserResponse>
                ) {
                    val body = response.body()
                    if (response.isSuccessful && body != null && body.status == "success") {
                        val apiItems = body.riwayat ?: emptyList()
                        val synced = PengajuanLocalStore.mergeWithServer(
                            this@HomeActivity,
                            uid,
                            userName,
                            apiItems
                        )
                        latestPengajuanList = synced
                        updateStatsUi(synced)

                        val host = ApiClient.getDisplayHost()
                        updateHomeServerStatus(
                            connected = true,
                            title = "Terhubung dengan server",
                            subtitle = "Sinkron dengan web ($host) • ${synced.size} riwayat pengajuan"
                        )

                        // Deteksi perubahan status dari Admin -> tampilkan di Panel Notifikasi HP & Pop-up
                        val changed = NotificationHelper.checkAndNotifyStatusChanges(this@HomeActivity, synced)
                        if (changed.isNotEmpty() && !isFinishing && !isDestroyed) {
                        val first = changed.first()
                        val stLabel = formatStatusUser(first.status)
                        val jenisLabel = first.jenisPengajuan?.replaceFirstChar { it.uppercase() } ?: "Aset"
                        AlertDialog.Builder(this@HomeActivity)
                            .setTitle("🔔 Pembaruan Status Pengajuan")
                            .setMessage(
                                "Pengajuan $jenisLabel Anda untuk aset \"${first.namaAset ?: "-"}\" (${first.nomorPengajuan ?: first.kodeAset ?: "-"}) kini berstatus:\n\n" +
                                "👉 $stLabel"
                            )
                            .setPositiveButton("Lihat Riwayat") { _, _ ->
                                val nextIntent = Intent(this@HomeActivity, HistoryActivity::class.java).apply {
                                    flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                                    putExtra("USER_NAME", userName)
                                    putExtra("USER_EMAIL", userEmail)
                                    putExtra("USER_ROLE", userRole)
                                    putExtra("USER_ID", userId)
                                }
                                startActivity(nextIntent)
                            }
                            .setNegativeButton("Tutup", null)
                            .show()
                        }
                    } else {
                        val host = ApiClient.getDisplayHost()
                        updateHomeServerStatus(
                            connected = false,
                            title = "Sinkronisasi server gagal (HTTP ${response.code()})",
                            subtitle = "Ketuk untuk cek ulang atau atur IP ($host)"
                        )
                        latestPengajuanList = localItems
                        updateStatsUi(localItems)
                    }
                }

                override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                    latestPengajuanList = localItems
                    updateStatsUi(localItems)

                    if (allowAutoDiscover && !isFinishing && !isDestroyed) {
                        ServerAutoDiscovery.discoverAsync(
                            context = this@HomeActivity,
                            onStatus = { statusText ->
                                if (!isFinishing && !isDestroyed) {
                                    updateHomeServerStatus(
                                        connected = null,
                                        title = "Mencari server di Wi-Fi...",
                                        subtitle = statusText
                                    )
                                }
                            },
                            onFinished = { result ->
                                if (isFinishing || isDestroyed) return@discoverAsync
                                val foundUrl = result.foundUrl
                                if (foundUrl != null) {
                                    ApiClient.setBaseUrl(this@HomeActivity, foundUrl, markAsCustom = false)
                                    syncWithWebServer(allowAutoDiscover = false)
                                } else {
                                    val host = ApiClient.getDisplayHost()
                                    updateHomeServerStatus(
                                        connected = false,
                                        title = "Tidak terhubung dengan server",
                                        subtitle = "Mode offline • Server belum ditemukan di ${result.scannedLabel} ($host)"
                                    )
                                }
                            }
                        )
                    } else {
                        val host = ApiClient.getDisplayHost()
                        updateHomeServerStatus(
                            connected = false,
                            title = "Tidak terhubung dengan server",
                            subtitle = "Mode offline • Pastikan Wi-Fi sama dengan laptop ($host)"
                        )
                    }
                }
            })
    }

    private fun formatStatusUser(rawStatus: String?): String {
        val st = (rawStatus ?: "Menunggu").trim()
        return when {
            st.equals("Disetujui", ignoreCase = true) || st.equals("Selesai", ignoreCase = true) -> "DITERIMA (DI-ACC)"
            st.equals("Ditolak", ignoreCase = true) -> "DITOLAK"
            else -> "MENUNGGU"
        }
    }

    private fun updateStatsUi(riwayat: List<PengajuanItem>) {
        val pendingCount = riwayat.count {
            val st = (it.status ?: "Menunggu").trim()
            st.equals("Menunggu", ignoreCase = true) || st.equals("Pending", ignoreCase = true)
        }
        val approvedCount = riwayat.count {
            val st = (it.status ?: "").trim()
            st.equals("Disetujui", ignoreCase = true) || st.equals("Selesai", ignoreCase = true) || st.equals("Diterima", ignoreCase = true)
        }
        val rejectedCount = riwayat.count {
            (it.status ?: "").trim().equals("Ditolak", ignoreCase = true)
        }

        runOnUiThread {
            findViewById<TextView>(R.id.tvPendingCount)?.text = pendingCount.toString()
            findViewById<TextView>(R.id.tvApprovedCount)?.text = approvedCount.toString()
            findViewById<TextView>(R.id.tvRejectedCount)?.text = rejectedCount.toString()

            val latest = riwayat.firstOrNull()
            if (latest != null) {
                val jenis = latest.jenisPengajuan?.replaceFirstChar { it.uppercase() } ?: "Inventaris"
                val aset = latest.namaAset ?: "Aset"
                val stRaw = (latest.status ?: "Menunggu").trim()
                val stDisplay = when {
                    stRaw.equals("Disetujui", ignoreCase = true) || stRaw.equals("Selesai", ignoreCase = true) -> "Diterima"
                    stRaw.equals("Ditolak", ignoreCase = true) -> "Ditolak"
                    else -> "Menunggu"
                }

                findViewById<TextView>(R.id.tvNotifTitle)?.text =
                    "Pengajuan $jenis — Status: $stDisplay"

                val perpindahanInfo = if (latest.jenisPengajuan.equals("perpindahan", ignoreCase = true) &&
                    !latest.ruanganTujuan.isNullOrBlank() && latest.ruanganTujuan != "-") {
                    " (${latest.ruanganAsal ?: "-"} → ${latest.ruanganTujuan})"
                } else ""

                findViewById<TextView>(R.id.tvNotifMessage)?.text = when (stDisplay) {
                    "Diterima" -> "✅ Pengajuan \"$aset\"$perpindahanInfo telah DITERIMA (di-ACC) oleh Admin."
                    "Ditolak"  -> "❌ Pengajuan \"$aset\"$perpindahanInfo DITOLAK oleh Admin. Ketuk untuk detail."
                    else       -> "⏳ Pengajuan \"$aset\"$perpindahanInfo sedang MENUNGGU verifikasi Admin."
                }
            } else {
                findViewById<TextView>(R.id.tvNotifTitle)?.text = "Informasi Pengajuan"
                findViewById<TextView>(R.id.tvNotifMessage)?.text =
                    "Pilih menu Ajukan Barang atau Scan QR untuk memulai pengajuan inventaris."
            }
        }
    }

    private fun showNotificationDialog(openHistory: () -> Unit) {
        if (latestPengajuanList.isEmpty()) {
            AlertDialog.Builder(this)
                .setTitle("Notifikasi Pengajuan")
                .setMessage("Belum ada notifikasi pengajuan terbaru.")
                .setPositiveButton("Tutup", null)
                .show()
            return
        }

        val lines = latestPengajuanList.take(8).map { item ->
            val jenis = item.jenisPengajuan?.replaceFirstChar { it.uppercase() } ?: "Aset"
            val st = formatStatusUser(item.status)
            val icon = when {
                st.startsWith("DITERIMA") -> "✅"
                st.startsWith("DITOLAK")  -> "❌"
                else                      -> "⏳"
            }
            val ruangStr = if (item.jenisPengajuan.equals("perpindahan", ignoreCase = true) && !item.ruanganTujuan.isNullOrBlank()) {
                "\n   Ruang: ${item.ruanganAsal ?: "-"} → ${item.ruanganTujuan}"
            } else ""
            "$icon [$st] Pengajuan $jenis\n   Aset: ${item.namaAset ?: "-"} (${item.kodeAset ?: "-"})$ruangStr"
        }

        AlertDialog.Builder(this)
            .setTitle("Notifikasi Status Pengajuan")
            .setMessage(lines.joinToString("\n\n"))
            .setPositiveButton("Lihat Riwayat") { _, _ -> openHistory() }
            .setNegativeButton("Tutup", null)
            .show()
    }

    private fun getInitials(name: String): String {
        val parts = name.trim().split(" ").filter { it.isNotBlank() }
        return if (parts.size >= 2) {
            "${parts[0].firstOrNull()?.uppercase() ?: ""}${parts[1].firstOrNull()?.uppercase() ?: ""}"
        } else {
            name.take(2).uppercase()
        }
    }

    private fun showLogoutDialog() {
        AlertDialog.Builder(this)
            .setTitle("Konfirmasi Logout")
            .setMessage("Halo $userName, apakah Anda yakin ingin keluar (logout) dari akun ini?")
            .setPositiveButton("Iya") { _, _ ->
                performLogout()
            }
            .setNegativeButton("Tidak", null)
            .show()
    }

    private fun showBackExitLogoutDialog() {
        AlertDialog.Builder(this)
            .setTitle("Keluar Aplikasi")
            .setMessage("Jika Anda keluar maka akan ter-logout. Apakah Anda yakin ingin keluar?")
            .setPositiveButton("Iya") { _, _ ->
                performLogout()
            }
            .setNegativeButton("Tidak", null)
            .show()
    }

    private fun performLogout() {
        PengajuanNotificationService.stopMonitoring(this)
        PengajuanLocalStore.clearActiveSession(this)
        Toast.makeText(this, "Anda telah keluar dari akun", Toast.LENGTH_SHORT).show()
        val intent = Intent(this, LoginActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        }
        startActivity(intent)
        finish()
    }
}
