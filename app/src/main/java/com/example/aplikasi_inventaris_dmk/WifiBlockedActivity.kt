package com.example.aplikasi_inventaris_dmk

import android.Manifest
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.provider.Settings
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import com.example.aplikasi_inventaris_dmk.network.WifiAccessGuard

class WifiBlockedActivity : AppCompatActivity() {

    private lateinit var tvTitle: TextView
    private lateinit var tvMessage: TextView
    private lateinit var tvRequiredSsid: TextView
    private lateinit var tvCurrentStatus: TextView
    private lateinit var btnPrimaryAction: Button
    private lateinit var btnRecheck: Button

    private var currentResult: WifiAccessGuard.WifiCheckResult =
        WifiAccessGuard.WifiCheckResult.NotConnectedToWifi

    private var hasRequestedPermissionOnce = false

    private val requestLocationPermissionsLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { grants ->
        val fineGranted = grants[Manifest.permission.ACCESS_FINE_LOCATION] == true
        if (fineGranted) {
            WifiAccessGuard.refreshMonitoring(this)
            evaluateWifiAndUpdateUi(showToastOnFail = false)
        } else {
            evaluateWifiAndUpdateUi(showToastOnFail = false)
        }
    }

    private val wifiStateListener: (WifiAccessGuard.WifiCheckResult) -> Unit = { result ->
        if (!isFinishing && !isDestroyed) {
            handleResult(result, showToastOnFail = false)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_wifi_blocked)

        tvTitle = findViewById(R.id.tvWifiBlockedTitle)
        tvMessage = findViewById(R.id.tvWifiBlockedMessage)
        tvRequiredSsid = findViewById(R.id.tvRequiredSsid)
        tvCurrentStatus = findViewById(R.id.tvCurrentWifiStatus)
        btnPrimaryAction = findViewById(R.id.btnWifiPrimaryAction)
        btnRecheck = findViewById(R.id.btnWifiRecheck)

        tvRequiredSsid.text = WifiAccessGuard.REQUIRED_SSID

        btnPrimaryAction.setOnClickListener {
            onPrimaryActionClicked()
        }

        btnRecheck.setOnClickListener {
            WifiAccessGuard.refreshMonitoring(this)
            evaluateWifiAndUpdateUi(showToastOnFail = true)
        }

        findViewById<TextView>(R.id.btnWifiExitApp).setOnClickListener {
            finishAffinity()
        }

        // Mencegah tombol Back melewati halaman blokir Wi-Fi
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                finishAffinity()
            }
        })
    }

    override fun onStart() {
        super.onStart()
        WifiAccessGuard.addListener(wifiStateListener)
    }

    override fun onStop() {
        WifiAccessGuard.removeListener(wifiStateListener)
        super.onStop()
    }

    override fun onResume() {
        super.onResume()
        val result = WifiAccessGuard.checkWifiAccess(this)
        handleResult(result, showToastOnFail = false)

        // Minta izin lokasi otomatis sekali saat pertama kali masuk agar user lebih praktis
        if (result is WifiAccessGuard.WifiCheckResult.PermissionRequired && !hasRequestedPermissionOnce) {
            hasRequestedPermissionOnce = true
            requestLocationPermission()
        }
    }

    private fun evaluateWifiAndUpdateUi(showToastOnFail: Boolean) {
        val result = WifiAccessGuard.checkWifiAccess(this)
        handleResult(result, showToastOnFail)
    }

    private fun handleResult(result: WifiAccessGuard.WifiCheckResult, showToastOnFail: Boolean) {
        currentResult = result
        when (result) {
            is WifiAccessGuard.WifiCheckResult.Allowed -> {
                Toast.makeText(
                    this,
                    "Terhubung ke Wi-Fi ${result.ssid}",
                    Toast.LENGTH_SHORT
                ).show()
                finish()
            }

            is WifiAccessGuard.WifiCheckResult.PermissionRequired -> {
                tvTitle.text = "Izin Lokasi Diperlukan"
                tvMessage.text =
                    "Android memerlukan izin Lokasi Presisi untuk memverifikasi bahwa HP Anda terhubung ke Wi-Fi ${WifiAccessGuard.REQUIRED_SSID}."
                tvCurrentStatus.text = "Izin lokasi belum diberikan"
                btnPrimaryAction.text = "Izinkan Akses Lokasi"
                if (showToastOnFail) {
                    Toast.makeText(this, "Silakan berikan izin lokasi terlebih dahulu", Toast.LENGTH_SHORT).show()
                }
            }

            is WifiAccessGuard.WifiCheckResult.LocationDisabled -> {
                tvTitle.text = "Aktifkan Lokasi (GPS)"
                tvMessage.text =
                    "Nyalakan layanan Lokasi (GPS) di HP Anda agar sistem dapat membaca nama Wi-Fi ${WifiAccessGuard.REQUIRED_SSID}."
                tvCurrentStatus.text = "Lokasi (GPS) nonaktif"
                btnPrimaryAction.text = "Buka Pengaturan Lokasi"
                if (showToastOnFail) {
                    Toast.makeText(this, "Silakan aktifkan Lokasi (GPS) terlebih dahulu", Toast.LENGTH_SHORT).show()
                }
            }

            is WifiAccessGuard.WifiCheckResult.NotConnectedToWifi -> {
                tvTitle.text = "Harus Menggunakan Wi-Fi"
                tvMessage.text =
                    "Aplikasi Inventaris DMK hanya dapat diakses saat HP tersambung ke Wi-Fi ${WifiAccessGuard.REQUIRED_SSID} (bukan paket data seluler)."
                tvCurrentStatus.text = "Wi-Fi tidak terhubung"
                btnPrimaryAction.text = "Buka Pengaturan Wi-Fi"
                if (showToastOnFail) {
                    Toast.makeText(this, "Sambungkan ke Wi-Fi ${WifiAccessGuard.REQUIRED_SSID}", Toast.LENGTH_SHORT).show()
                }
            }

            is WifiAccessGuard.WifiCheckResult.WrongWifi -> {
                tvTitle.text = "Wi-Fi Tidak Diizinkan"
                tvMessage.text =
                    "Anda sedang terhubung ke Wi-Fi lain. Pindahkan koneksi Wi-Fi ke ${WifiAccessGuard.REQUIRED_SSID} untuk membuka aplikasi."
                tvCurrentStatus.text = "Terhubung ke: ${result.currentSsid}"
                btnPrimaryAction.text = "Ganti ke Wi-Fi ${WifiAccessGuard.REQUIRED_SSID}"
                if (showToastOnFail) {
                    Toast.makeText(
                        this,
                        "Wi-Fi saat ini ${result.currentSsid}, wajib ${WifiAccessGuard.REQUIRED_SSID}",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            }

            is WifiAccessGuard.WifiCheckResult.UnknownSsid -> {
                tvTitle.text = "Nama Wi-Fi Tidak Terbaca"
                tvMessage.text =
                    "Pastikan HP terhubung ke Wi-Fi ${WifiAccessGuard.REQUIRED_SSID}, Lokasi (GPS) aktif, dan izin Lokasi Presisi diizinkan."
                tvCurrentStatus.text = "Nama Wi-Fi tidak terdeteksi"
                btnPrimaryAction.text = "Buka Pengaturan Wi-Fi"
                if (showToastOnFail) {
                    Toast.makeText(this, "Nama Wi-Fi belum terbaca, coba Cek Ulang", Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun onPrimaryActionClicked() {
        when (currentResult) {
            is WifiAccessGuard.WifiCheckResult.PermissionRequired -> {
                val canAskAgain = !hasRequestedPermissionOnce ||
                    shouldShowRequestPermissionRationale(Manifest.permission.ACCESS_FINE_LOCATION)
                if (canAskAgain) {
                    hasRequestedPermissionOnce = true
                    requestLocationPermission()
                } else {
                    openAppDetailsSettings()
                }
            }

            is WifiAccessGuard.WifiCheckResult.LocationDisabled -> {
                try {
                    startActivity(Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS))
                } catch (_: Exception) {
                    openAppDetailsSettings()
                }
            }

            else -> {
                try {
                    startActivity(Intent(Settings.ACTION_WIFI_SETTINGS))
                } catch (_: Exception) {
                    startActivity(Intent(Settings.ACTION_SETTINGS))
                }
            }
        }
    }

    private fun requestLocationPermission() {
        requestLocationPermissionsLauncher.launch(
            arrayOf(
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            )
        )
    }

    private fun openAppDetailsSettings() {
        val intent = Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply {
            data = Uri.fromParts("package", packageName, null)
        }
        startActivity(intent)
    }
}
