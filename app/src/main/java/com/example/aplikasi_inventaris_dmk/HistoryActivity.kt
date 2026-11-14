package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.Color
import android.os.Bundle
import android.view.Gravity
import android.view.View
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanItem
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class HistoryActivity : AppCompatActivity() {

    private lateinit var listContainer: LinearLayout

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_history)

        val userName  = intent.getStringExtra("USER_NAME")  ?: "Pengguna"
        val userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        val userRole  = intent.getStringExtra("USER_ROLE")  ?: "Staff"
        val userId    = intent.getIntExtra("USER_ID", 0)

        // Navigasi ke Home
        findViewById<LinearLayout>(R.id.btnNavHome)?.setOnClickListener {
            val intent = Intent(this, HomeActivity::class.java).apply {
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
            finish()
        }

        // Navigasi ke Inventory
        findViewById<LinearLayout>(R.id.btnNavInventory)?.setOnClickListener {
            val intent = Intent(this, InventoryActivity::class.java).apply {
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

        // Notifikasi & Logout
        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            Toast.makeText(this, "Memuat notifikasi...", Toast.LENGTH_SHORT).show()
        }

        findViewById<ImageView>(R.id.ivUserIcon)?.setOnClickListener {
            showLogoutDialog(userName)
        }

        // Container untuk daftar riwayat (LinearLayout di dalam ScrollView)
        // Kita akan menambahkan item ke ScrollView → LinearLayout → LinearLayout (sub-container)
        setupListContainer()
        loadHistory(userId, userName)
    }

    private fun setupListContainer() {
        // Cari sub-LinearLayout di dalam scrollViewHistory
        val scrollContent = findViewById<LinearLayout>(
            resources.getIdentifier("scrollViewHistory", "id", packageName)
        )

        // Buat container list baru yang akan diisi item riwayat
        listContainer = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).also { it.setMargins(dpToPx(16), 0, dpToPx(16), dpToPx(16)) }
        }

        // Dapatkan LinearLayout dalam ScrollView dan tambahkan listContainer
        val sv = findViewById<android.widget.ScrollView>(R.id.scrollViewHistory)
        val outerLinear = sv?.getChildAt(0) as? LinearLayout
        outerLinear?.addView(listContainer)
    }

    private fun loadHistory(userId: Int, userName: String) {
        // Tampilkan loading
        val loading = ProgressBar(this).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).also { it.gravity = Gravity.CENTER_HORIZONTAL; it.setMargins(0, dpToPx(24), 0, 0) }
        }
        listContainer.addView(loading)

        val uid = if (userId > 0) userId else null
        ApiClient.instance.getUserPengajuan(uid, if (uid == null) userName else null)
            .enqueue(object : Callback<PengajuanUserResponse> {
                override fun onResponse(
                    call: Call<PengajuanUserResponse>,
                    response: Response<PengajuanUserResponse>
                ) {
                    val body = response.body()
                    runOnUiThread {
                        listContainer.removeView(loading)
                        if (response.isSuccessful && body != null && body.status == "success") {
                            val riwayat = body.riwayat
                            if (riwayat.isNullOrEmpty()) {
                                showEmptyState("Belum ada riwayat pengajuan.")
                            } else {
                                riwayat.forEach { item -> addRiwayatCard(item) }
                            }
                        } else {
                            showEmptyState("Gagal memuat riwayat. Coba lagi.")
                        }
                    }
                }

                override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                    runOnUiThread {
                        listContainer.removeView(loading)
                        showEmptyState("Koneksi gagal: ${t.localizedMessage}")
                    }
                }
            })
    }

    private fun addRiwayatCard(item: PengajuanItem) {
        val ctx = this

        val card = CardView(ctx).apply {
            radius = dpToPx(12).toFloat()
            cardElevation = dpToPx(2).toFloat()
            setCardBackgroundColor(Color.WHITE)
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).also { it.setMargins(0, 0, 0, dpToPx(10)) }
        }

        val inner = LinearLayout(ctx).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dpToPx(14), dpToPx(12), dpToPx(14), dpToPx(12))
        }

        // Baris atas: nama aset + badge status
        val rowTop = LinearLayout(ctx).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
        }

        val tvNamaAset = TextView(ctx).apply {
            text = item.namaAset ?: "Aset Inventaris"
            textSize = 13f
            setTextColor(Color.parseColor("#1A1A1A"))
            setTypeface(null, android.graphics.Typeface.BOLD)
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
        }

        val statusText = item.status ?: "Pending"
        val (statusBg, statusTxt) = when (statusText) {
            "Disetujui" -> Pair("#E8F5E9", "#2E7D32")
            "Ditolak"   -> Pair("#FFEBEE", "#C62828")
            else        -> Pair("#FFF3E0", "#E65100")  // Pending = orange
        }

        val tvStatus = TextView(ctx).apply {
            text = statusText.uppercase()
            textSize = 10f
            setTextColor(Color.parseColor(statusTxt))
            setBackgroundColor(Color.parseColor(statusBg))
            setPadding(dpToPx(8), dpToPx(3), dpToPx(8), dpToPx(3))
        }

        rowTop.addView(tvNamaAset)
        rowTop.addView(tvStatus)
        inner.addView(rowTop)

        // Kode aset
        val tvKode = TextView(ctx).apply {
            text = "Kode: ${item.kodeAset ?: "-"}"
            textSize = 11f
            setTextColor(Color.parseColor("#757575"))
            setPadding(0, dpToPx(2), 0, 0)
        }
        inner.addView(tvKode)

        // Jenis pengajuan
        val jenisLabel = item.jenisPengajuan?.replaceFirstChar { it.uppercase() } ?: "-"
        val tvJenis = TextView(ctx).apply {
            text = "Jenis: Pengajuan $jenisLabel"
            textSize = 11f
            setTextColor(Color.parseColor("#555555"))
            setPadding(0, dpToPx(2), 0, 0)
        }
        inner.addView(tvJenis)

        // Tanggal pengajuan
        val tgl = item.tanggalPengajuan?.take(10) ?: "-"
        val tvTgl = TextView(ctx).apply {
            text = "Tanggal: $tgl"
            textSize = 11f
            setTextColor(Color.parseColor("#757575"))
            setPadding(0, dpToPx(2), 0, 0)
        }
        inner.addView(tvTgl)

        // Alasan penolakan (jika ada)
        if (!item.alasanPenolakan.isNullOrBlank()) {
            val tvAlasan = TextView(ctx).apply {
                text = "Alasan Tolak: ${item.alasanPenolakan}"
                textSize = 11f
                setTextColor(Color.parseColor("#C62828"))
                setPadding(0, dpToPx(4), 0, 0)
                setTypeface(null, android.graphics.Typeface.ITALIC)
            }
            inner.addView(tvAlasan)
        }

        // Disetujui oleh
        if (statusText == "Disetujui" && !item.responOleh.isNullOrBlank()) {
            val tvAcc = TextView(ctx).apply {
                text = "Disetujui oleh: ${item.responOleh}"
                textSize = 11f
                setTextColor(Color.parseColor("#2E7D32"))
                setPadding(0, dpToPx(4), 0, 0)
            }
            inner.addView(tvAcc)
        }

        card.addView(inner)
        listContainer.addView(card)
    }

    private fun showEmptyState(msg: String) {
        val tv = TextView(this).apply {
            text = msg
            textSize = 13f
            setTextColor(Color.parseColor("#9E9E9E"))
            gravity = Gravity.CENTER
            setPadding(dpToPx(16), dpToPx(32), dpToPx(16), dpToPx(16))
        }
        listContainer.addView(tv)
    }

    private fun dpToPx(dp: Int): Int =
        (dp * resources.displayMetrics.density).toInt()

    private fun showLogoutDialog(userName: String) {
        AlertDialog.Builder(this)
            .setTitle("Keluar Akun")
            .setMessage("Halo $userName, apakah Anda yakin ingin keluar dari aplikasi?")
            .setPositiveButton("Keluar") { _, _ ->
                val intent = Intent(this, LoginActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                }
                startActivity(intent)
                finish()
            }
            .setNegativeButton("Batal", null)
            .show()
    }
}
