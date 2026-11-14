package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.os.Bundle
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class HomeActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_home)

        // Ambil data user dari Intent
        val userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        val rawName   = intent.getStringExtra("USER_NAME") ?: ""
        val userName  = if (rawName.isNotBlank()) rawName
                        else if (userEmail.isNotBlank()) userEmail.substringBefore("@").replaceFirstChar { it.uppercase() }
                        else "Pengguna"
        val userRole = intent.getStringExtra("USER_ROLE") ?: "Staff"
        val userId   = intent.getIntExtra("USER_ID", 0)

        // Tampilkan data user ke UI
        findViewById<TextView>(R.id.tvUserName)?.text = userName
        findViewById<TextView>(R.id.tvUserRole)?.text =
            if (userRole.contains("Divisi", ignoreCase = true)) userRole
            else "Divisi IT - ${userRole.replaceFirstChar { it.uppercase() }}"
        findViewById<TextView>(R.id.tvUserNik)?.text =
            if (userId > 0) "NIK: 2025-IT-${String.format("%03d", userId)}" else "NIK: 2025-IT-012"
        findViewById<TextView>(R.id.tvAvatar)?.text = getInitials(userName)

        // Navigasi ke Inventory
        val openInventory = {
            val intent = Intent(this, InventoryActivity::class.java).apply {
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
        }

        findViewById<LinearLayout>(R.id.btnNavHome)?.setOnClickListener {
            findViewById<android.widget.ScrollView>(R.id.scrollViewProfile)?.smoothScrollTo(0, 0)
        }
        findViewById<LinearLayout>(R.id.btnNavInventory)?.setOnClickListener { openInventory() }
        findViewById<CardView>(R.id.btnAjukanBaru)?.setOnClickListener { openInventory() }

        // Navigasi ke Scan QR
        val openScan = {
            val intent = Intent(this, ScanActivity::class.java).apply {
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
        }

        findViewById<CardView>(R.id.btnScanQR)?.setOnClickListener { openScan() }
        findViewById<android.view.View>(R.id.btnNavScan)?.setOnClickListener { openScan() }

        // Navigasi ke History
        val openHistory = {
            val intent = Intent(this, HistoryActivity::class.java).apply {
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
                putExtra("USER_ID", userId)
            }
            startActivity(intent)
        }
        findViewById<LinearLayout>(R.id.btnNavHistory)?.setOnClickListener { openHistory() }

        // Notifikasi & Logout
        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            openHistory()
        }

        findViewById<ImageView>(R.id.ivUserIcon)?.setOnClickListener {
            showLogoutDialog(userName)
        }

        // Fetch jumlah pengajuan pending dari API
        fetchPendingCount(userId, userName)
    }

    private fun fetchPendingCount(userId: Int, userName: String) {
        val uid = if (userId > 0) userId else null
        ApiClient.instance.getUserPengajuan(uid, if (uid == null) userName else null)
            .enqueue(object : Callback<PengajuanUserResponse> {
                override fun onResponse(
                    call: Call<PengajuanUserResponse>,
                    response: Response<PengajuanUserResponse>
                ) {
                    val body = response.body()
                    if (response.isSuccessful && body != null && body.status == "success") {
                        val count = body.totalPending
                        runOnUiThread {
                            findViewById<TextView>(R.id.tvPendingCount)?.text = count.toString()
                        }
                    }
                }

                override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                    // Gagal ambil data — biarkan angka 0 tetap tampil
                }
            })
    }

    private fun getInitials(name: String): String {
        val parts = name.trim().split(" ")
        return if (parts.size >= 2) {
            "${parts[0].firstOrNull()?.uppercase() ?: ""}${parts[1].firstOrNull()?.uppercase() ?: ""}"
        } else {
            name.take(2).uppercase()
        }
    }

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
