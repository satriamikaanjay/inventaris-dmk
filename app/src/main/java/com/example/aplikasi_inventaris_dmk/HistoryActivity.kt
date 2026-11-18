package com.example.aplikasi_inventaris_dmk

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.graphics.Color
import android.graphics.Typeface
import android.net.Uri
import android.os.Bundle
import android.view.Gravity
import android.view.View
import android.widget.Button
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import androidx.core.content.ContextCompat
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanItem
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import com.example.aplikasi_inventaris_dmk.notification.NotificationHelper
import com.example.aplikasi_inventaris_dmk.notification.PengajuanNotificationService
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class HistoryActivity : AppCompatActivity() {

    private lateinit var listContainer: LinearLayout
    private var userId: Int = 0
    private var userName: String = "Pengguna"

    private val statusUpdateReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            if (::listContainer.isInitialized) {
                loadHistory(userId, userName)
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_history)

        userName = intent.getStringExtra("USER_NAME") ?: "Pengguna"
        val userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        val userRole = intent.getStringExtra("USER_ROLE") ?: ""
        userId = intent.getIntExtra("USER_ID", 0)

        // Navigasi ke Home (1x klik langsung kembali ke Home)
        findViewById<LinearLayout>(R.id.btnNavHome)?.setOnClickListener {
            val intent = Intent(this, HomeActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
            finish()
        }

        // Navigasi ke Inventory (1x klik langsung berpindah)
        findViewById<LinearLayout>(R.id.btnNavInventory)?.setOnClickListener {
            val intent = Intent(this, InventoryActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
            finish()
        }

        // Navigasi ke Scan
        findViewById<View>(R.id.btnNavScan)?.setOnClickListener {
            val intent = Intent(this, ScanActivity::class.java).apply {
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
        }

        // Navigasi ke Data Stok
        findViewById<LinearLayout>(R.id.btnNavStok)?.setOnClickListener {
            val intent = Intent(this, DataStokActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
            finish()
        }

        // Refresh & Logout
        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            Toast.makeText(this, "Memperbarui status pengajuan...", Toast.LENGTH_SHORT).show()
            loadHistory(userId, userName)
        }

        findViewById<ImageView>(R.id.ivUserIcon)?.setOnClickListener {
            showLogoutDialog(userName)
        }

        setupListContainer()
        loadHistory(userId, userName)
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
        if (::listContainer.isInitialized) {
            loadHistory(userId, userName)
        }
    }

    private fun setupListContainer() {
        listContainer = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).also { it.setMargins(dpToPx(16), dpToPx(4), dpToPx(16), dpToPx(16)) }
        }

        val sv = findViewById<ScrollView>(R.id.scrollViewHistory)
        val outerLinear = sv?.getChildAt(0) as? LinearLayout
        outerLinear?.addView(listContainer)
    }

    private fun loadHistory(userId: Int, userName: String) {
        listContainer.removeAllViews()

        val loading = ProgressBar(this).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).also {
                it.gravity = Gravity.CENTER_HORIZONTAL
                it.setMargins(0, dpToPx(32), 0, 0)
            }
        }
        listContainer.addView(loading)

        val uid = if (userId > 0) userId else null
        val localItems = PengajuanLocalStore.getSubmissionsForUser(this, uid, userName)

        ApiClient.instance.getUserPengajuan(uid, userName)
            .enqueue(object : Callback<PengajuanUserResponse> {
                override fun onResponse(
                    call: Call<PengajuanUserResponse>,
                    response: Response<PengajuanUserResponse>
                ) {
                    val body = response.body()
                    runOnUiThread {
                        listContainer.removeAllViews()
                        if (response.isSuccessful && body != null && body.status == "success") {
                            val apiItems = body.riwayat ?: emptyList()
                            val synced = PengajuanLocalStore.mergeWithServer(
                                this@HistoryActivity,
                                uid,
                                userName,
                                apiItems
                            )
                            NotificationHelper.checkAndNotifyStatusChanges(this@HistoryActivity, synced)
                            if (synced.isEmpty()) {
                                showEmptyState("Belum ada riwayat pengajuan.")
                            } else {
                                synced.forEach { item -> addRiwayatCard(item) }
                            }
                        } else {
                            val errMsg = ApiClient.extractErrorMessage(
                                response,
                                "Gagal memuat riwayat pengajuan (HTTP ${response.code()})."
                            )
                            if (localItems.isNotEmpty()) {
                                Toast.makeText(this@HistoryActivity, errMsg, Toast.LENGTH_SHORT).show()
                                localItems.forEach { item -> addRiwayatCard(item) }
                            } else {
                                showEmptyState(errMsg)
                            }
                        }
                    }
                }

                override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                    runOnUiThread {
                        listContainer.removeAllViews()
                        if (localItems.isNotEmpty()) {
                            localItems.forEach { item -> addRiwayatCard(item) }
                        } else {
                            showEmptyState("Koneksi gagal: ${t.localizedMessage}")
                        }
                    }
                }
            })
    }

    private fun addRiwayatCard(item: PengajuanItem) {
        val ctx = this

        val card = CardView(ctx).apply {
            radius = dpToPx(14).toFloat()
            cardElevation = dpToPx(2).toFloat()
            setCardBackgroundColor(Color.WHITE)
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).also { it.setMargins(0, 0, 0, dpToPx(12)) }
        }

        val inner = LinearLayout(ctx).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dpToPx(16), dpToPx(14), dpToPx(16), dpToPx(14))
        }

        // Baris atas: nama aset + badge status (MENUNGGU / DITERIMA / DITOLAK)
        val rowTop = LinearLayout(ctx).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
        }

        val tvNamaAset = TextView(ctx).apply {
            text = item.namaAset ?: "Aset Inventaris"
            textSize = 14f
            setTextColor(Color.parseColor("#0F172A"))
            setTypeface(null, Typeface.BOLD)
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f).also {
                it.marginEnd = dpToPx(8)
            }
        }

        val rawStatus = (item.status ?: "Menunggu").trim()
        val (statusLabel, statusDrawable, statusTxtColor) = when {
            rawStatus.equals("Disetujui", ignoreCase = true) ||
            rawStatus.equals("Selesai", ignoreCase = true) ||
            rawStatus.equals("Diterima", ignoreCase = true) ->
                Triple("DITERIMA", R.drawable.bg_badge_green, "#2E7D32")
            rawStatus.equals("Ditolak", ignoreCase = true) ->
                Triple("DITOLAK", R.drawable.bg_badge_red, "#C62828")
            else ->
                Triple("MENUNGGU", R.drawable.bg_badge_orange, "#E65100")
        }

        val tvStatus = TextView(ctx).apply {
            text = statusLabel
            textSize = 10f
            setTypeface(null, Typeface.BOLD)
            setTextColor(Color.parseColor(statusTxtColor))
            setBackgroundResource(statusDrawable)
            setPadding(dpToPx(10), dpToPx(4), dpToPx(10), dpToPx(4))
        }

        rowTop.addView(tvNamaAset)
        rowTop.addView(tvStatus)
        inner.addView(rowTop)

        // Nomor Ajuan & Kode aset
        val noAjuanStr = if (!item.nomorPengajuan.isNullOrBlank()) "${item.nomorPengajuan} • " else ""
        val tvKode = TextView(ctx).apply {
            text = "${noAjuanStr}Kode Aset: ${item.kodeAset ?: "-"}"
            textSize = 11f
            setTextColor(Color.parseColor("#64748B"))
            setPadding(0, dpToPx(4), 0, 0)
        }
        inner.addView(tvKode)

        // Garis tipis pemisah
        val divider = View(ctx).apply {
            setBackgroundColor(Color.parseColor("#E2E8F0"))
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                dpToPx(1)
            ).also { it.setMargins(0, dpToPx(10), 0, dpToPx(10)) }
        }
        inner.addView(divider)

        // Jenis pengajuan
        val jenisLabel = item.jenisPengajuan?.replaceFirstChar { it.uppercase() } ?: "-"
        val tvJenis = TextView(ctx).apply {
            text = "Jenis Pengajuan: Pengajuan $jenisLabel"
            textSize = 12f
            setTypeface(null, Typeface.BOLD)
            setTextColor(Color.parseColor("#1C4E8F"))
        }
        inner.addView(tvJenis)

        // Jika Perpindahan: tampilkan Ruangan Lama & Ruangan Baru
        if (item.jenisPengajuan.equals("perpindahan", ignoreCase = true)) {
            val rLama = item.ruanganAsal?.takeIf { it.isNotBlank() } ?: "-"
            val rBaru = item.ruanganTujuan?.takeIf { it.isNotBlank() } ?: "-"
            val cab = item.cabangAsal?.takeIf { it.isNotBlank() } ?: "-"
            val tvPerpindahan = TextView(ctx).apply {
                text = "Cabang: $cab\nRuangan Lama: $rLama  ➔  Ruangan Baru: $rBaru"
                textSize = 11.5f
                setTypeface(null, Typeface.BOLD)
                setTextColor(Color.parseColor("#0F172A"))
                setPadding(0, dpToPx(4), 0, 0)
            }
            inner.addView(tvPerpindahan)
        }

        // Keterangan / Deskripsi
        if (!item.keterangan.isNullOrBlank() && item.keterangan != "-") {
            val tvKet = TextView(ctx).apply {
                text = "Deskripsi: ${item.keterangan}"
                textSize = 11f
                setTextColor(Color.parseColor("#475569"))
                setPadding(0, dpToPx(4), 0, 0)
            }
            inner.addView(tvKet)
        }

        // Tanggal pengajuan
        val tgl = item.tanggalPengajuan ?: "-"
        val tvTgl = TextView(ctx).apply {
            text = "Tanggal Pengajuan: $tgl"
            textSize = 11f
            setTextColor(Color.parseColor("#64748B"))
            setPadding(0, dpToPx(3), 0, 0)
        }
        inner.addView(tvTgl)

        // Keterangan Status Notifikasi
        val notifStatusMsg = when (statusLabel) {
            "DITERIMA" -> "✅ Pengajuan Anda telah DITERIMA (di-ACC) oleh Admin."
            "DITOLAK"  -> "❌ Pengajuan Anda DITOLAK oleh Admin."
            else       -> "⏳ Menunggu konfirmasi / verifikasi dari Admin."
        }
        val tvNotifStatus = TextView(ctx).apply {
            text = notifStatusMsg
            textSize = 11f
            setTypeface(null, Typeface.BOLD)
            setTextColor(Color.parseColor(statusTxtColor))
            setPadding(0, dpToPx(6), 0, 0)
        }
        inner.addView(tvNotifStatus)

        // Catatan Admin jika ada
        if (!item.alasanPenolakan.isNullOrBlank()) {
            val tvAlasan = TextView(ctx).apply {
                text = "Catatan Admin: ${item.alasanPenolakan}"
                textSize = 11f
                setTextColor(Color.parseColor("#C62828"))
                setPadding(0, dpToPx(4), 0, 0)
                setTypeface(null, Typeface.ITALIC)
            }
            inner.addView(tvAlasan)
        }

        // Tombol Lihat File Lampiran (jika ada file yang diupload)
        val fileUrl = item.lampiranUrl
        val fileName = item.lampiranNama ?: item.lampiran
        if (!fileUrl.isNullOrBlank() || !fileName.isNullOrBlank()) {
            val btnViewAttachment = Button(ctx).apply {
                text = "👁️ Lihat File: ${fileName ?: "Lampiran"}"
                isAllCaps = false
                textSize = 11.5f
                setTextColor(Color.parseColor("#1C4E8F"))
                setBackgroundColor(Color.parseColor("#E8F0FB"))
                layoutParams = LinearLayout.LayoutParams(
                    LinearLayout.LayoutParams.MATCH_PARENT,
                    dpToPx(38)
                ).also { it.setMargins(0, dpToPx(8), 0, 0) }
                setOnClickListener {
                    openAttachmentUrl(fileUrl, fileName)
                }
            }
            inner.addView(btnViewAttachment)
        }

        card.addView(inner)
        listContainer.addView(card)
    }

    private fun openAttachmentUrl(fileUrl: String?, fileName: String?) {
        if (fileUrl.isNullOrBlank()) {
            Toast.makeText(this, "File lampiran: ${fileName ?: "-"}", Toast.LENGTH_SHORT).show()
            return
        }
        try {
            val uri = Uri.parse(fileUrl)
            val viewIntent = Intent(Intent.ACTION_VIEW, uri).apply {
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            startActivity(viewIntent)
        } catch (_: Exception) {
            Toast.makeText(this, "Tidak dapat membuka lampiran: $fileUrl", Toast.LENGTH_SHORT).show()
        }
    }

    private fun showEmptyState(msg: String) {
        val tv = TextView(this).apply {
            text = msg
            textSize = 13f
            setTextColor(Color.parseColor("#64748B"))
            gravity = Gravity.CENTER
            setPadding(dpToPx(16), dpToPx(40), dpToPx(16), dpToPx(16))
        }
        listContainer.addView(tv)
    }

    private fun dpToPx(dp: Int): Int =
        (dp * resources.displayMetrics.density).toInt()

    private fun showLogoutDialog(userName: String) {
        AlertDialog.Builder(this)
            .setTitle("Konfirmasi Logout")
            .setMessage("Halo $userName, apakah Anda yakin ingin keluar (logout) dari akun ini?")
            .setPositiveButton("Iya") { _, _ ->
                PengajuanNotificationService.stopMonitoring(this)
                PengajuanLocalStore.clearActiveSession(this)
                Toast.makeText(this, "Anda telah keluar dari akun", Toast.LENGTH_SHORT).show()
                val intent = Intent(this, LoginActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                }
                startActivity(intent)
                finish()
            }
            .setNegativeButton("Tidak", null)
            .show()
    }
}
