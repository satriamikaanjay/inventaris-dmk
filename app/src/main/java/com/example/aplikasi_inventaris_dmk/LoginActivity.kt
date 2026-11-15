package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.text.method.HideReturnsTransformationMethod
import android.text.method.PasswordTransformationMethod
import android.widget.Button
import android.widget.EditText
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.LoginResponse
import org.json.JSONObject
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class LoginActivity : AppCompatActivity() {

    private var isPasswordVisible = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)

        // Jika diarahkan keluar karena akses user diblokir oleh Master Admin saat sedang aktif
        if (intent.getBooleanExtra("FORCE_BLOCKED_LOGOUT", false)) {
            showBlockedByMasterAdminDialog()
        }

        // Deklarasi Komponen UI
        val edtEmail = findViewById<EditText>(R.id.etEmail)
        val edtPassword = findViewById<EditText>(R.id.etPassword)
        val ivTogglePassword = findViewById<ImageView>(R.id.ivTogglePassword)
        val tvForgotPassword = findViewById<TextView>(R.id.tvForgotPassword)
        val btnLogin = findViewById<Button>(R.id.btnLogin)
        val btnCallCenter = findViewById<Button>(R.id.btnCallCenter)

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

            // Ubah tombol ke status loading
            btnLogin.isEnabled = false
            btnLogin.text = "Memproses..."

            // Panggil API Login menggunakan Retrofit
            ApiClient.instance.loginUser(email, password).enqueue(object : Callback<LoginResponse> {
                override fun onResponse(call: Call<LoginResponse>, response: Response<LoginResponse>) {
                    btnLogin.isEnabled = true
                    btnLogin.text = "Masuk Sekarang"

                    val loginResponse = response.body()

                    // Cek jika status diblokir meskipun HTTP 200
                    if (loginResponse != null && (loginResponse.status.equals("blocked", ignoreCase = true) || loginResponse.blocked == true)) {
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

                            val nextIntent = Intent(this@LoginActivity, HomeActivity::class.java).apply {
                                loginResponse.data?.let { user ->
                                    val displayName = when {
                                        !user.user_name.isNullOrBlank() -> user.user_name
                                        !user.nama_lengkap.isNullOrBlank() -> user.nama_lengkap
                                        else -> email.substringBefore("@")
                                            .replaceFirstChar { it.uppercase() }
                                    }
                                    putExtra("USER_ID", user.user_id ?: user.id ?: 0)
                                    putExtra("USER_NAME", displayName)
                                    putExtra("USER_EMAIL", user.email ?: email)
                                    putExtra("USER_ROLE", user.role ?: "")
                                } ?: run {
                                    putExtra(
                                        "USER_NAME",
                                        email.substringBefore("@").replaceFirstChar { it.uppercase() }
                                    )
                                    putExtra("USER_EMAIL", email)
                                }
                            }
                            startActivity(nextIntent)
                            finish()
                        } else {
                            val msg = loginResponse?.message ?: "Login gagal. Silakan coba lagi."
                            if (msg.contains("blokir", ignoreCase = true)) {
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

                        val isBlockedError = response.code() == 403 ||
                                rawErrorBody.contains("blokir", ignoreCase = true) ||
                                rawErrorBody.contains("blocked", ignoreCase = true)

                        if (isBlockedError) {
                            showBlockedByMasterAdminDialog()
                            return
                        }

                        val errorMsg = try {
                            if (rawErrorBody.isNotEmpty()) {
                                JSONObject(rawErrorBody).optString("message", "Email atau kata sandi salah.")
                            } else {
                                "Email atau kata sandi salah."
                            }
                        } catch (_: Exception) {
                            "Email atau kata sandi salah."
                        }
                        Toast.makeText(this@LoginActivity, errorMsg, Toast.LENGTH_LONG).show()
                    }
                }

                override fun onFailure(call: Call<LoginResponse>, t: Throwable) {
                    btnLogin.isEnabled = true
                    btnLogin.text = "Masuk Sekarang"
                    Toast.makeText(
                        this@LoginActivity,
                        "Koneksi Gagal: Periksa jaringan Anda dan pastikan server aktif (${t.localizedMessage})",
                        Toast.LENGTH_LONG
                    ).show()
                }
            })
        }
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
