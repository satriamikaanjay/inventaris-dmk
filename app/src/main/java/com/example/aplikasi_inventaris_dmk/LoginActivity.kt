package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.text.method.HideReturnsTransformationMethod
import android.text.method.PasswordTransformationMethod
import android.view.View
import android.widget.Button
import android.widget.CheckBox
import android.widget.EditText
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.CategoryListResponse
import com.example.aplikasi_inventaris_dmk.network.LoginResponse
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.ServerAutoDiscovery
import com.example.aplikasi_inventaris_dmk.network.ServerConfigDialog
import com.example.aplikasi_inventaris_dmk.network.UserAccessStatusResponse
import com.example.aplikasi_inventaris_dmk.network.WifiAccessGuard
import com.example.aplikasi_inventaris_dmk.notification.PengajuanNotificationService
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class LoginActivity : AppCompatActivity() {

    private var isPasswordVisible = false

    private lateinit var cardServerStatus: CardView
    private lateinit var viewServerDot: View
    private lateinit var tvServerStatusTitle: TextView
    private lateinit var tvServerStatusSubtitle: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        ApiClient.init(applicationContext)

        val isBlockedLogout = intent.getBooleanExtra("FORCE_BLOCKED_LOGOUT", false)
        if (!isBlockedLogout) {
            val savedSession = PengajuanLocalStore.getActiveSession(this)
            if (savedSession != null && savedSession.isLoggedIn && savedSession.rememberMe) {
                if (WifiAccessGuard.isAllowed(this)) {
                    navigateToHomeFromSession(savedSession)
                    return
                }
            } else if (savedSession != null && !savedSession.rememberMe) {
                // Jika sebelumnya login tanpa "Ingat saya", bersihkan sesi saat kembali membuka halaman login
                PengajuanNotificationService.stopMonitoring(this)
                PengajuanLocalStore.clearActiveSession(this)
            }
        }

        setContentView(R.layout.activity_login)

        // Jika diarahkan keluar karena akses user diblokir oleh Master Admin saat sedang aktif
        if (isBlockedLogout) {
            showBlockedByMasterAdminDialog()
        }

        // Deklarasi Komponen UI
        val edtEmail = findViewById<EditText>(R.id.etEmail)
        val edtPassword = findViewById<EditText>(R.id.etPassword)
        val ivTogglePassword = findViewById<ImageView>(R.id.ivTogglePassword)
        val cbRememberMe = findViewById<CheckBox>(R.id.cbRememberMe)
        val tvForgotPassword = findViewById<TextView>(R.id.tvForgotPassword)
        val btnLogin = findViewById<Button>(R.id.btnLogin)
        val btnCallCenter = findViewById<Button>(R.id.btnCallCenter)

        cardServerStatus = findViewById(R.id.cardServerStatus)
        viewServerDot = findViewById(R.id.viewServerDot)
        tvServerStatusTitle = findViewById(R.id.tvServerStatusTitle)
        tvServerStatusSubtitle = findViewById(R.id.tvServerStatusSubtitle)

        findViewById<TextView>(R.id.btnCheckServer).setOnClickListener {
            checkServerConnectionAndSync(allowAutoProbe = true)
        }
        findViewById<TextView>(R.id.btnConfigServer).setOnClickListener {
            ServerConfigDialog.show(this) {
                checkServerConnectionAndSync(allowAutoProbe = false)
            }
        }

        // Cek koneksi & sinkronisasi dengan web begitu APK dibuka
        checkServerConnectionAndSync(allowAutoProbe = true)

        // Fitur Show / Hide Password
        ivTogglePassword.setOnClickListener {
            isPasswordVisible = !isPasswordVisible
            if (isPasswordVisible) {
                edtPassword.transformationMethod = HideReturnsTransformationMethod.getInstance()
                ivTogglePassword.setImageResource(R.drawable.ic_visibility)
            } else {
                edtPassword.transformationMethod = PasswordTransformationMethod.getInstance()
                ivTogglePassword.setImageResource(R.drawable.ic_visibility_off)
            }
            edtPassword.setSelection(edtPassword.text?.length ?: 0)
        }

        // Fitur Lupa Sandi
        tvForgotPassword.setOnClickListener {
            AlertDialog.Builder(this)
                .setTitle("Lupa Kata Sandi?")
                .setMessage("Silakan hubungi Administrator atau Call Center untuk melakukan pengaturan ulang kata sandi akun Anda.")
                .setPositiveButton("Hubungi Call Center") { _, _ ->
                    openCallCenter()
                }
                .setNegativeButton("Tutup", null)
                .show()
        }

        // Fitur Hubungi Call Center
        btnCallCenter.setOnClickListener {
            openCallCenter()
        }

        // Fitur Tombol Masuk / Login
        btnLogin.setOnClickListener {
            val email = edtEmail.text.toString().trim()
            val password = edtPassword.text.toString().trim()

            if (email.isEmpty()) {
                edtEmail.error = "Email wajib diisi"
                edtEmail.requestFocus()
                return@setOnClickListener
            }

            if (password.isEmpty()) {
                edtPassword.error = "Kata sandi wajib diisi"
                edtPassword.requestFocus()
                return@setOnClickListener
            }

            // Ubah tombol ke status loading dan lakukan login (otomatis cari server Wi-Fi jika IP lama gagal)
            performLoginRequest(
                email = email,
                password = password,
                rememberMe = cbRememberMe.isChecked,
                btnLogin = btnLogin,
                allowAutoDiscoverRetry = true
            )
        }
    }

    private fun performLoginRequest(
        email: String,
        password: String,
        rememberMe: Boolean,
        btnLogin: Button,
        allowAutoDiscoverRetry: Boolean
    ) {
        btnLogin.isEnabled = false
        btnLogin.text = "Memproses..."

        // Jika IP tersimpan berbeda subnet dengan Wi-Fi saat ini (mis. bekas IP USB), cari server Wi-Fi dulu
        val savedHost = ApiClient.extractHostIp(ApiClient.getBaseUrl(this))
        if (allowAutoDiscoverRetry &&
            !ApiClient.isCustomUrlLocked(this) &&
            !ServerAutoDiscovery.isHostOnActiveWifiSubnet(this, savedHost)
        ) {
            btnLogin.text = "Mencari server Wi-Fi..."
            ServerAutoDiscovery.discoverAsync(
                context = this,
                onStatus = { statusText ->
                    if (!isFinishing && !isDestroyed) {
                        renderServerChecking(statusText)
                    }
                },
                onFinished = { result ->
                    if (isFinishing || isDestroyed) return@discoverAsync
                    val foundUrl = result.foundUrl
                    if (foundUrl != null) {
                        ApiClient.setBaseUrl(this@LoginActivity, foundUrl, markAsCustom = false)
                        val host = ApiClient.getDisplayHost(foundUrl)
                        renderServerConnected("Sinkron dengan web ($host)")
                        performLoginRequest(email, password, rememberMe, btnLogin, allowAutoDiscoverRetry = false)
                    } else {
                        performLoginRequest(email, password, rememberMe, btnLogin, allowAutoDiscoverRetry = false)
                    }
                }
            )
            return
        }

        // Panggil API Login menggunakan Retrofit
        ApiClient.instance.loginUser(email, password).enqueue(object : Callback<LoginResponse> {
            override fun onResponse(call: Call<LoginResponse>, response: Response<LoginResponse>) {
                btnLogin.isEnabled = true
                btnLogin.text = "Masuk Sekarang"

                val loginResponse = response.body()

                // Cek jika status diblokir oleh Master Admin meskipun HTTP 200
                if (loginResponse != null &&
                    (loginResponse.status.equals("blocked", ignoreCase = true) ||
                            (loginResponse.message ?: "").contains("master admin", ignoreCase = true))
                ) {
                    showBlockedByMasterAdminDialog()
                    return
                }

                if (response.isSuccessful) {
                    if (loginResponse != null && loginResponse.status == "success") {
                        Toast.makeText(
                            this@LoginActivity,
                            "Login Berhasil: ${loginResponse.message ?: "Selamat datang"}",
                            Toast.LENGTH_SHORT
                        ).show()

                        val user = loginResponse.data
                        val resolvedUserId = user?.user_id ?: user?.id ?: 0
                        val displayName = user?.user_name?.takeIf { it.isNotBlank() }
                            ?: user?.nama_lengkap?.takeIf { it.isNotBlank() }
                            ?: email.substringBefore("@")
                                .replaceFirstChar { it.uppercase() }
                        val resolvedEmail = user?.email ?: email
                        val resolvedRole = user?.role ?: ""

                        // Simpan sesi aktif beserta pilihan "Ingat saya"
                        PengajuanLocalStore.saveActiveSession(
                            context = this@LoginActivity,
                            userId = resolvedUserId,
                            userName = displayName,
                            userEmail = resolvedEmail,
                            userRole = resolvedRole,
                            rememberMe = rememberMe
                        )

                        val nextIntent = Intent(this@LoginActivity, HomeActivity::class.java).apply {
                            putExtra("USER_ID", resolvedUserId)
                            putExtra("USER_NAME", displayName)
                            putExtra("USER_EMAIL", resolvedEmail)
                            putExtra("USER_ROLE", resolvedRole)
                        }
                        startActivity(nextIntent)
                        finish()
                    } else {
                        val msg = loginResponse?.message ?: "Login gagal. Silakan coba lagi."
                        if (msg.contains("master admin", ignoreCase = true)) {
                            showBlockedByMasterAdminDialog()
                        } else {
                            Toast.makeText(this@LoginActivity, msg, Toast.LENGTH_LONG).show()
                        }
                    }
                } else {
                    val rawErrorBody = try {
                        response.errorBody()?.string() ?: ""
                    } catch (_: Exception) {
                        ""
                    }

                    val errJson = ApiClient.parseErrorJson(rawErrorBody)
                    val errStatus = errJson?.optString("status", "") ?: ""
                    val errorMsg = errJson?.optString("message", "")?.takeIf { it.isNotBlank() }
                        ?: "Email atau kata sandi salah (HTTP ${response.code()})."

                    val isMasterAdminBlocked = errStatus.equals("blocked", ignoreCase = true) ||
                            errorMsg.contains("master admin", ignoreCase = true)

                    if (isMasterAdminBlocked) {
                        showBlockedByMasterAdminDialog()
                        return
                    }

                    if (response.code() in listOf(403, 429, 503) && !isFinishing && !isDestroyed) {
                        AlertDialog.Builder(this@LoginActivity)
                            .setTitle(if (response.code() == 503) "Sistem Maintenance" else "Login Ditolak")
                            .setMessage(errorMsg)
                            .setPositiveButton("Mengerti", null)
                            .show()
                    } else {
                        Toast.makeText(this@LoginActivity, errorMsg, Toast.LENGTH_LONG).show()
                    }
                }
            }

            override fun onFailure(call: Call<LoginResponse>, t: Throwable) {
                if (allowAutoDiscoverRetry && !isFinishing && !isDestroyed) {
                    btnLogin.text = "Mencari server Wi-Fi..."
                    ServerAutoDiscovery.discoverAsync(
                        context = this@LoginActivity,
                        onStatus = { statusText ->
                            if (!isFinishing && !isDestroyed) {
                                renderServerChecking(statusText)
                            }
                        },
                        onFinished = { result ->
                            if (isFinishing || isDestroyed) return@discoverAsync
                            val foundUrl = result.foundUrl
                            if (foundUrl != null) {
                                ApiClient.setBaseUrl(this@LoginActivity, foundUrl, markAsCustom = false)
                                val host = ApiClient.getDisplayHost(foundUrl)
                                renderServerConnected("Sinkron dengan web ($host)")
                                performLoginRequest(email, password, rememberMe, btnLogin, allowAutoDiscoverRetry = false)
                            } else {
                                btnLogin.isEnabled = true
                                btnLogin.text = "Masuk Sekarang"
                                renderServerOffline("Gagal menghubungi ${ApiClient.getDisplayHost()}: ${t.localizedMessage ?: "Timeout"}")
                                Toast.makeText(
                                    this@LoginActivity,
                                    "Koneksi Gagal: Pastikan HP & laptop di Wi-Fi ${WifiAccessGuard.REQUIRED_SSID} dan Laragon aktif",
                                    Toast.LENGTH_LONG
                                ).show()
                            }
                        }
                    )
                    return
                }

                btnLogin.isEnabled = true
                btnLogin.text = "Masuk Sekarang"
                renderServerOffline("Gagal menghubungi ${ApiClient.getDisplayHost()}: ${t.localizedMessage ?: "Timeout"}")
                Toast.makeText(
                    this@LoginActivity,
                    "Koneksi Gagal: Pastikan HP & laptop di Wi-Fi ${WifiAccessGuard.REQUIRED_SSID} dan cek Atur IP (${ApiClient.getDisplayHost()})",
                    Toast.LENGTH_LONG
                ).show()
            }
        })
    }

    override fun onResume() {
        super.onResume()
        if (!WifiAccessGuard.isAllowed(this)) {
            return
        }
        val isBlockedLogout = intent.getBooleanExtra("FORCE_BLOCKED_LOGOUT", false)
        if (!isBlockedLogout) {
            val savedSession = PengajuanLocalStore.getActiveSession(this)
            if (savedSession != null && savedSession.isLoggedIn && savedSession.rememberMe) {
                navigateToHomeFromSession(savedSession)
                return
            }
        }
        checkServerConnectionAndSync(allowAutoProbe = true)
    }

    private fun navigateToHomeFromSession(session: com.example.aplikasi_inventaris_dmk.network.ActiveUserSession) {
        val homeIntent = Intent(this, HomeActivity::class.java).apply {
            putExtra("USER_ID", session.userId)
            putExtra("USER_NAME", session.userName)
            putExtra("USER_EMAIL", session.userEmail)
            putExtra("USER_ROLE", session.userRole)
        }
        startActivity(homeIntent)
        finish()
    }

    private fun checkServerConnectionAndSync(allowAutoProbe: Boolean) {
        val currentUrl = ApiClient.getBaseUrl(this)
        val currentHost = ApiClient.extractHostIp(currentUrl)
        val displayHost = ApiClient.getDisplayHost(currentUrl)

        // Jika IP lama berbeda subnet dengan Wi-Fi saat ini (mis. bekas IP USB), langsung cari server Wi-Fi tanpa menunggu timeout
        if (allowAutoProbe &&
            !ApiClient.isCustomUrlLocked(this) &&
            !ServerAutoDiscovery.isHostOnActiveWifiSubnet(this, currentHost)
        ) {
            runAutoWifiDiscovery("Mencari server baru di Wi-Fi ${WifiAccessGuard.REQUIRED_SSID}")
            return
        }

        renderServerChecking("Memeriksa $displayHost...")

        val service = ApiClient.buildServiceForUrl(currentUrl)
        service.getUserAccessStatus().enqueue(object : Callback<UserAccessStatusResponse> {
            override fun onResponse(
                call: Call<UserAccessStatusResponse>,
                response: Response<UserAccessStatusResponse>
            ) {
                if (isFinishing || isDestroyed) return
                val body = response.body()
                val rawErr = if (!response.isSuccessful) {
                    try { response.errorBody()?.string() ?: "" } catch (_: Exception) { "" }
                } else ""
                val errJson = ApiClient.parseErrorJson(rawErr)

                val isBlocked = body?.blocked == true ||
                        body?.allowed == false ||
                        body?.status.equals("blocked", ignoreCase = true) ||
                        (errJson != null && (
                            errJson.optBoolean("blocked", false) ||
                            errJson.optString("status", "").equals("blocked", ignoreCase = true)
                        ))

                if (isBlocked) {
                    renderServerBlocked("Terhubung ($displayHost) • Akses user diblokir Master Admin")
                    return
                }

                if (response.isSuccessful && (body?.allowed == true || body?.status.equals("allowed", ignoreCase = true))) {
                    verifyDatabaseSync(service, displayHost)
                } else if (response.code() == 503) {
                    renderServerOffline("Server maintenance (HTTP 503) di $displayHost")
                } else if (allowAutoProbe) {
                    tryAlternateServerEndpoint(currentUrl, "Server $displayHost merespons HTTP ${response.code()}")
                } else {
                    renderServerOffline("Server merespons HTTP ${response.code()} di $displayHost")
                }
            }

            override fun onFailure(call: Call<UserAccessStatusResponse>, t: Throwable) {
                if (isFinishing || isDestroyed) return
                if (allowAutoProbe) {
                    tryAlternateServerEndpoint(currentUrl, "Pastikan HP & laptop di Wi-Fi yang sama ($displayHost)")
                } else {
                    renderServerOffline("Pastikan HP & laptop di Wi-Fi yang sama ($displayHost)")
                }
            }
        })
    }

    private fun tryAlternateServerEndpoint(failedUrl: String, fallbackError: String) {
        val altUrl = ApiClient.getAlternateDefaultUrl(failedUrl)
        if (altUrl == null) {
            runAutoWifiDiscovery(fallbackError)
            return
        }
        val altHost = ApiClient.getDisplayHost(altUrl)
        val altService = ApiClient.buildServiceForUrl(altUrl)
        altService.getUserAccessStatus().enqueue(object : Callback<UserAccessStatusResponse> {
            override fun onResponse(
                call: Call<UserAccessStatusResponse>,
                response: Response<UserAccessStatusResponse>
            ) {
                if (isFinishing || isDestroyed) return
                val body = response.body()
                if (response.isSuccessful && (body?.allowed == true || body?.status.equals("allowed", ignoreCase = true))) {
                    ApiClient.setBaseUrl(this@LoginActivity, altUrl, markAsCustom = false)
                    verifyDatabaseSync(altService, altHost)
                } else {
                    runAutoWifiDiscovery(fallbackError)
                }
            }

            override fun onFailure(call: Call<UserAccessStatusResponse>, t: Throwable) {
                if (isFinishing || isDestroyed) return
                runAutoWifiDiscovery(fallbackError)
            }
        })
    }

    private fun runAutoWifiDiscovery(fallbackError: String) {
        ServerAutoDiscovery.discoverAsync(
            context = this,
            onStatus = { statusText ->
                if (!isFinishing && !isDestroyed) {
                    renderServerChecking(statusText)
                }
            },
            onFinished = { result ->
                if (isFinishing || isDestroyed) return@discoverAsync
                val foundUrl = result.foundUrl
                if (foundUrl != null) {
                    ApiClient.setBaseUrl(this@LoginActivity, foundUrl, markAsCustom = false)
                    val host = ApiClient.getDisplayHost(foundUrl)
                    if (result.isBlockedByAdmin) {
                        renderServerBlocked("Terhubung ($host) • Akses user diblokir Master Admin")
                    } else {
                        verifyDatabaseSync(ApiClient.buildServiceForUrl(foundUrl), host)
                    }
                } else if (result.scannedLabel == "jaringan lokal") {
                    renderServerOffline(fallbackError)
                } else {
                    renderServerOffline("Server tidak ditemukan di ${result.scannedLabel} ($fallbackError)")
                }
            }
        )
    }

    private fun verifyDatabaseSync(service: com.example.aplikasi_inventaris_dmk.network.ApiService, displayHost: String) {
        service.getCategories().enqueue(object : Callback<CategoryListResponse> {
            override fun onResponse(
                call: Call<CategoryListResponse>,
                response: Response<CategoryListResponse>
            ) {
                if (isFinishing || isDestroyed) return
                if (response.isSuccessful && response.body()?.status == "success") {
                    val jumlahKategori = response.body()?.data?.size ?: 0
                    renderServerConnected("Sinkron dengan web ($displayHost) • $jumlahKategori kategori aktif")
                } else {
                    renderServerConnected("Terhubung ke $displayHost (sinkron sebagian)")
                }
            }

            override fun onFailure(call: Call<CategoryListResponse>, t: Throwable) {
                if (isFinishing || isDestroyed) return
                renderServerOffline("Server terdeteksi namun gagal sinkron data web ($displayHost)")
            }
        })
    }

    private fun renderServerChecking(subtitle: String) {
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#F1F5F9"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_orange)
        tvServerStatusTitle.text = "Menghubungkan ke server..."
        tvServerStatusTitle.setTextColor(Color.parseColor("#1E293B"))
        tvServerStatusSubtitle.text = subtitle
        tvServerStatusSubtitle.setTextColor(Color.parseColor("#64748B"))
    }

    private fun renderServerConnected(subtitle: String) {
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#E8F5E9"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_blue)
        tvServerStatusTitle.text = "Terhubung dengan server"
        tvServerStatusTitle.setTextColor(Color.parseColor("#2E7D32"))
        tvServerStatusSubtitle.text = subtitle
        tvServerStatusSubtitle.setTextColor(Color.parseColor("#1B5E20"))
    }

    private fun renderServerBlocked(subtitle: String) {
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#FFF3E0"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_red)
        tvServerStatusTitle.text = "Terhubung dengan server (Diblokir)"
        tvServerStatusTitle.setTextColor(Color.parseColor("#E65100"))
        tvServerStatusSubtitle.text = subtitle
        tvServerStatusSubtitle.setTextColor(Color.parseColor("#BF360C"))
    }

    private fun renderServerOffline(subtitle: String) {
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#FFEBEE"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_red)
        tvServerStatusTitle.text = "Tidak terhubung dengan server"
        tvServerStatusTitle.setTextColor(Color.parseColor("#C62828"))
        tvServerStatusSubtitle.text = subtitle
        tvServerStatusSubtitle.setTextColor(Color.parseColor("#7F1D1D"))
    }

    private fun showBlockedByMasterAdminDialog() {
        val blockedMessage = "Akses user di blokir oleh master admin"
        Toast.makeText(this, blockedMessage, Toast.LENGTH_LONG).show()
        if (!isFinishing && !isDestroyed) {
            AlertDialog.Builder(this)
                .setTitle("Akses Diblokir")
                .setMessage(blockedMessage)
                .setPositiveButton("Mengerti", null)
                .show()
        }
    }

    private fun openCallCenter() {
        val phoneNumber = "081234567890"
        try {
            val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phoneNumber"))
            startActivity(intent)
        } catch (_: Exception) {
            Toast.makeText(this, "Call Center: $phoneNumber", Toast.LENGTH_LONG).show()
        }
    }
}
