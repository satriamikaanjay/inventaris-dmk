package com.example.aplikasi_inventaris_dmk

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.CheckBox
import android.widget.EditText
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

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)

        // Deklarasi Komponen UI
        val edtEmail = findViewById<EditText>(R.id.etEmail)
        val edtPassword = findViewById<EditText>(R.id.etPassword)
        val cbRememberMe = findViewById<CheckBox>(R.id.cbRememberMe)
        val tvForgotPassword = findViewById<TextView>(R.id.tvForgotPassword)
        val btnLogin = findViewById<Button>(R.id.btnLogin)
        val btnCallCenter = findViewById<Button>(R.id.btnCallCenter)

        // Fitur Ingat Saya: Memuat email yang tersimpan jika ada
        val prefs = getSharedPreferences("LoginPrefs", Context.MODE_PRIVATE)
        val isRemembered = prefs.getBoolean("REMEMBER_EMAIL", false)
        if (isRemembered) {
            val savedEmail = prefs.getString("SAVED_EMAIL", "") ?: ""
            edtEmail.setText(savedEmail)
            cbRememberMe.isChecked = true
            edtPassword.requestFocus()
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

                    if (response.isSuccessful) {
                        val loginResponse = response.body()
                        if (loginResponse != null && loginResponse.status == "success") {
                            // Simpan status Ingat Saya
                            if (cbRememberMe.isChecked) {
                                prefs.edit()
                                    .putBoolean("REMEMBER_EMAIL", true)
                                    .putString("SAVED_EMAIL", email)
                                    .apply()
                            } else {
                                prefs.edit().clear().apply()
                            }

                            Toast.makeText(this@LoginActivity, "Login Berhasil: ${loginResponse.message}", Toast.LENGTH_SHORT).show()

                            // Berpindah ke Halaman Profile / Dashboard dengan membawa data pengguna
                            val intent = Intent(this@LoginActivity, HomeActivity::class.java).apply {
                                loginResponse.data?.let { user ->
                                    // Gunakan user_name dari API, fallback ke prefix email jika kosong
                                    val displayName = when {
                                        !user.user_name.isNullOrBlank() -> user.user_name
                                        !user.nama_lengkap.isNullOrBlank() -> user.nama_lengkap
                                        else -> email.substringBefore("@")
                                            .replaceFirstChar { it.uppercase() }
                                    }
                                    putExtra("USER_ID", user.user_id ?: user.id ?: 0)
                                    putExtra("USER_NAME", displayName)
                                    putExtra("USER_EMAIL", user.email ?: email)
                                    putExtra("USER_ROLE", user.role ?: "Staff")
                                } ?: run {
                                    // Fallback jika data null
                                    putExtra("USER_NAME", email.substringBefore("@")
                                        .replaceFirstChar { it.uppercase() })
                                    putExtra("USER_EMAIL", email)
                                }
                            }
                            startActivity(intent)
                            finish()
                        } else {
                            val msg = loginResponse?.message ?: "Login gagal. Silakan coba lagi."
                            Toast.makeText(this@LoginActivity, msg, Toast.LENGTH_LONG).show()
                        }
                    } else {
                        // Membaca pesan error detail dari respon server jika ada
                        val errorMsg = try {
                            val errorBody = response.errorBody()?.string()
                            if (!errorBody.isNullOrEmpty()) {
                                JSONObject(errorBody).optString("message", "Email atau kata sandi salah.")
                            } else {
                                "Email atau kata sandi salah."
                            }
                        } catch (e: Exception) {
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

    private fun openCallCenter() {
        val phoneNumber = "081234567890"
        try {
            val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phoneNumber"))
            startActivity(intent)
        } catch (e: Exception) {
            Toast.makeText(this, "Call Center: $phoneNumber", Toast.LENGTH_LONG).show()
        }
    }
}