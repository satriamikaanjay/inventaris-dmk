package com.example.aplikasi_inventaris_dmk

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.camera.core.Camera
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.content.ContextCompat
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.AssetItem
import com.example.aplikasi_inventaris_dmk.network.InventoryListResponse
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.common.InputImage
import java.text.NumberFormat
import java.util.Locale
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class ScanActivity : AppCompatActivity() {

    companion object {
        var cachedInventory: List<AssetItem> = emptyList()
    }

    private lateinit var previewView: PreviewView
    private lateinit var tvGuideText: TextView
    private lateinit var cameraExecutor: ExecutorService
    private var camera: Camera? = null
    private var isTorchOn = false

    private var lastScannedCode: String? = null
    private var lastScanTimestamp: Long = 0
    private var isProcessingScan = false

    // User info diteruskan dari Home / Inventory / History
    private var userId = 0
    private var userName = "Pengguna"
    private var userEmail = ""
    private var userRole = ""

    private val requestPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { isGranted: Boolean ->
        if (isGranted) {
            startCamera()
        } else {
            Toast.makeText(
                this,
                "Izin kamera dibutuhkan untuk memindai QR Code barang inventaris.",
                Toast.LENGTH_LONG
            ).show()
            finish()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_scan)

        userId    = intent.getIntExtra("USER_ID", 0)
        userName  = intent.getStringExtra("USER_NAME") ?: "Pengguna"
        userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        userRole  = intent.getStringExtra("USER_ROLE") ?: ""

        previewView = findViewById(R.id.previewView)
        tvGuideText = findViewById(R.id.tvGuideText)

        cameraExecutor = Executors.newSingleThreadExecutor()

        // Tombol Kembali & Senter
        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { finish() }
        findViewById<ImageView>(R.id.ivFlashToggle)?.setOnClickListener { toggleTorch() }

        // Preload data inventaris terbaru agar saat scan langsung cocok & cepat
        preloadInventory()

        // Periksa izin kamera
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            startCamera()
        } else {
            requestPermissionLauncher.launch(Manifest.permission.CAMERA)
        }
    }

    private fun preloadInventory() {
        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(call: Call<InventoryListResponse>, response: Response<InventoryListResponse>) {
                val data = response.body()?.data
                if (response.isSuccessful && !data.isNullOrEmpty()) {
                    cachedInventory = data
                }
            }
            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {
                // Berjalan senyap di background
            }
        })
    }

    private fun startCamera() {
        val cameraProviderFuture = ProcessCameraProvider.getInstance(this)
        cameraProviderFuture.addListener({
            val cameraProvider: ProcessCameraProvider = cameraProviderFuture.get()

            val preview = Preview.Builder().build().also {
                it.setSurfaceProvider(previewView.surfaceProvider)
            }

            val imageAnalyzer = ImageAnalysis.Builder()
                .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                .build()
                .also {
                    it.setAnalyzer(cameraExecutor) { imageProxy ->
                        processImageProxy(imageProxy)
                    }
                }

            val cameraSelector = CameraSelector.DEFAULT_BACK_CAMERA

            try {
                cameraProvider.unbindAll()
                camera = cameraProvider.bindToLifecycle(this, cameraSelector, preview, imageAnalyzer)
            } catch (exc: Exception) {
                Toast.makeText(this, "Gagal mengaktifkan kamera: ${exc.localizedMessage}", Toast.LENGTH_SHORT).show()
            }
        }, ContextCompat.getMainExecutor(this))
    }

    @SuppressLint("UnsafeOptInUsageError")
    private fun processImageProxy(imageProxy: ImageProxy) {
        if (isProcessingScan) {
            imageProxy.close()
            return
        }

        val mediaImage = imageProxy.image
        if (mediaImage != null) {
            val image = InputImage.fromMediaImage(mediaImage, imageProxy.imageInfo.rotationDegrees)
            val scanner = BarcodeScanning.getClient()

            scanner.process(image)
                .addOnSuccessListener { barcodes ->
                    if (!isProcessingScan && barcodes.isNotEmpty()) {
                        for (barcode in barcodes) {
                            val rawValue = barcode.rawValue ?: barcode.displayValue
                            if (!rawValue.isNullOrBlank()) {
                                handleBarcodeDetected(rawValue)
                                break
                            }
                        }
                    }
                }
                .addOnCompleteListener {
                    imageProxy.close()
                }
        } else {
            imageProxy.close()
        }
    }

    private fun handleBarcodeDetected(code: String) {
        val currentTime = System.currentTimeMillis()
        if (isProcessingScan) return
        if (code == lastScannedCode && (currentTime - lastScanTimestamp) < 3000) return

        isProcessingScan = true
        lastScannedCode = code
        lastScanTimestamp = currentTime

        vibratePhone()

        val cleanCode = code.trim()
        runOnUiThread {
            tvGuideText.text = "Memuat daftar aset kode: $cleanCode..."
        }

        // 1. Cek di cache lokal jika sudah ada
        if (cachedInventory.isNotEmpty()) {
            val matched = findMatchingAsset(cleanCode, cachedInventory)
            if (matched != null) {
                openScanResultList(cleanCode, matched.kodeAset ?: cleanCode)
                return
            }
        }

        // 2. Ambil data terbaru dari server untuk memastikan aset ditemukan dengan akurat
        fetchInventoryAndOpenResultList(cleanCode)
    }

    private fun fetchInventoryAndOpenResultList(code: String) {
        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(call: Call<InventoryListResponse>, response: Response<InventoryListResponse>) {
                val list = response.body()?.data
                if (response.isSuccessful && !list.isNullOrEmpty()) {
                    cachedInventory = list
                    val matched = findMatchingAsset(code, list)
                    if (matched != null) {
                        openScanResultList(code, matched.kodeAset ?: code)
                    } else {
                        runOnUiThread {
                            isProcessingScan = false
                            tvGuideText.text = "Arahkan kamera ke QR Code / Barcode aset"
                            Toast.makeText(
                                this@ScanActivity,
                                "Aset dengan kode \"$code\" tidak ditemukan di database.",
                                Toast.LENGTH_LONG
                            ).show()
                        }
                    }
                } else {
                    runOnUiThread {
                        isProcessingScan = false
                        tvGuideText.text = "Arahkan kamera ke QR Code / Barcode aset"
                        Toast.makeText(
                            this@ScanActivity,
                            "Gagal memuat data aset dari server.",
                            Toast.LENGTH_SHORT
                        ).show()
                    }
                }
            }

            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {
                runOnUiThread {
                    isProcessingScan = false
                    tvGuideText.text = "Arahkan kamera ke QR Code / Barcode aset"
                    Toast.makeText(
                        this@ScanActivity,
                        "Koneksi gagal: ${t.localizedMessage}",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            }
        })
    }

    private fun findMatchingAsset(code: String, list: List<AssetItem>): AssetItem? {
        val clean = code.trim()
        if (clean.isEmpty() || list.isEmpty()) return null

        // 1. Pencarian persis pada kode_aset (Case-insensitive)
        list.firstOrNull { it.kodeAset?.trim().equals(clean, ignoreCase = true) }?.let { return it }

        // 2. Pencarian kode_aset dengan mengabaikan spasi berlebih
        val normalizedScan = clean.replace("\\s+".toRegex(), "")
        list.firstOrNull {
            val k = it.kodeAset?.replace("\\s+".toRegex(), "") ?: ""
            k.isNotEmpty() && k.equals(normalizedScan, ignoreCase = true)
        }?.let { return it }

        // 3. Jika QR berisi URL atau parameter query (misal: ?code=...)
        val extractedCode = when {
            clean.contains("code=", ignoreCase = true) ->
                clean.substringAfter("code=").substringBefore("&").trim()
            clean.startsWith("http", ignoreCase = true) && clean.contains("/") ->
                clean.substringAfterLast("/").trim()
            else -> clean
        }
        if (extractedCode.isNotEmpty() && extractedCode != clean) {
            list.firstOrNull { it.kodeAset?.trim().equals(extractedCode, ignoreCase = true) }?.let { return it }
        }

        // 4. Pencarian berdasarkan Nomor Seri fisik
        list.firstOrNull {
            val s = it.nomorSeri?.trim()
            !s.isNullOrBlank() && s != "-" && s != "--" && s.equals(clean, ignoreCase = true)
        }?.let { return it }

        // 5. Pencarian berdasarkan ID numerik
        clean.toIntOrNull()?.let { idNum ->
            list.firstOrNull { it.id == idNum }?.let { return it }
        }

        // 6. Jika teks QR memuat kode_aset secara utuh di dalamnya
        list.firstOrNull { item ->
            val k = item.kodeAset?.trim() ?: ""
            k.length >= 4 && clean.contains(k, ignoreCase = true)
        }?.let { return it }

        return null
    }

    private fun openScanResultList(scannedRawCode: String, targetKodeAset: String) {
        runOnUiThread {
            Toast.makeText(
                this,
                "✅ Kode Aset ditemukan: $targetKodeAset",
                Toast.LENGTH_SHORT
            ).show()

            val resultIntent = Intent(this, ScanResultActivity::class.java).apply {
                putExtra("SCANNED_RAW_CODE", scannedRawCode)
                putExtra("TARGET_KODE_ASET", targetKodeAset)
                putExtra("USER_ID",          userId)
                putExtra("USER_NAME",        userName)
                putExtra("USER_EMAIL",       userEmail)
                putExtra("USER_ROLE",        userRole)
            }
            startActivity(resultIntent)
            finish()
        }
    }

    private fun buildSpesifikasiString(item: AssetItem): String {
        val lines = mutableListOf<String>()

        val statusVal = (item.status ?: item.statusRaw ?: "Baik").trim()
        if (statusVal.isNotBlank() && statusVal != "-") {
            lines.add("• Kondisi Fisik: $statusVal")
        }

        var merkVal = item.merk?.trim()
        val rawSpecs = item.spesifikasi?.trim() ?: ""
        if ((merkVal.isNullOrBlank() || merkVal == "-") && rawSpecs.contains("| Seri:")) {
            val extracted = rawSpecs.substringBefore("|").trim()
            if (extracted.isNotBlank() && extracted != "-") {
                merkVal = extracted
            }
        }
        if (!merkVal.isNullOrBlank() && merkVal != "-") {
            lines.add("• Merk / Brand: $merkVal")
        }

        var seriVal = item.nomorSeri?.trim()
        if ((seriVal.isNullOrBlank() || seriVal == "-" || seriVal == "--") && rawSpecs.contains("Seri:")) {
            val extracted = rawSpecs.substringAfter("Seri:").trim()
            if (extracted.isNotBlank() && extracted != "-" && extracted != "--") {
                seriVal = extracted
            }
        }
        if (!seriVal.isNullOrBlank() && seriVal != "-" && seriVal != "--") {
            lines.add("• No. Seri: $seriVal")
        }

        val totalUnit = item.unit ?: 1
        lines.add("• Jumlah Unit: $totalUnit Unit")

        val lokasiVal = item.lokasi?.trim()
        if (!lokasiVal.isNullOrBlank() && lokasiVal != "-") {
            lines.add("• Lokasi Penyimpanan: $lokasiVal")
        }
        val cabangVal = item.cabang?.trim()
        if (!cabangVal.isNullOrBlank() && cabangVal != "-") {
            lines.add("• Cabang: $cabangVal")
        }

        val tglVal = item.tanggalPengadaan?.trim()
        if (!tglVal.isNullOrBlank() && tglVal != "-") {
            lines.add("• Tanggal Pengadaan: $tglVal")
        }

        val harga = item.hargaBeli ?: 0.0
        if (harga > 0) {
            val formatRupiah = NumberFormat.getCurrencyInstance(Locale.forLanguageTag("id-ID"))
            lines.add("• Nilai Perolehan: ${formatRupiah.format(harga).replace("Rp", "Rp ")}")
        }

        val picVal = item.penanggungJawab?.trim()
        if (!picVal.isNullOrBlank() && picVal != "-") {
            lines.add("• Penanggung Jawab: $picVal")
        }

        val katVal = item.kategori?.trim()
        if (!katVal.isNullOrBlank() && katVal != "-") {
            lines.add("• Kategori: $katVal")
        }

        if (rawSpecs.isNotBlank() && rawSpecs != "-") {
            val isAutoPattern = rawSpecs.contains("| Seri:") || rawSpecs.startsWith("- |")
            if (!isAutoPattern) {
                lines.add("• Keterangan Tambahan: $rawSpecs")
            }
        }

        return if (lines.isNotEmpty()) lines.joinToString("\n")
               else "• Kondisi Fisik: $statusVal\n• Spesifikasi standar inventaris DMK."
    }

    private fun toggleTorch() {
        val cam = camera ?: return
        isTorchOn = !isTorchOn
        cam.cameraControl.enableTorch(isTorchOn)
        val ivFlash = findViewById<ImageView>(R.id.ivFlashToggle)
        if (isTorchOn) {
            ivFlash?.setColorFilter(ContextCompat.getColor(this, android.R.color.holo_orange_light))
            Toast.makeText(this, "Lampu senter dinyalakan", Toast.LENGTH_SHORT).show()
        } else {
            ivFlash?.setColorFilter(ContextCompat.getColor(this, android.R.color.white))
            Toast.makeText(this, "Lampu senter dimatikan", Toast.LENGTH_SHORT).show()
        }
    }

    private fun vibratePhone() {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                val vibratorManager = getSystemService(VIBRATOR_MANAGER_SERVICE) as? VibratorManager
                vibratorManager?.defaultVibrator?.vibrate(
                    VibrationEffect.createOneShot(150, VibrationEffect.DEFAULT_AMPLITUDE)
                )
            } else {
                @Suppress("DEPRECATION")
                val vibrator = getSystemService(Context.VIBRATOR_SERVICE) as? Vibrator
                @Suppress("DEPRECATION")
                vibrator?.vibrate(150)
            }
        } catch (_: Exception) {
            // Abaikan jika vibrator tidak tersedia
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        cameraExecutor.shutdown()
    }
}
