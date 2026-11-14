package com.example.aplikasi_inventaris_dmk

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.os.Build
import android.os.Bundle
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.widget.Button
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
import com.example.aplikasi_inventaris_dmk.network.InventorySingleResponse
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.common.InputImage
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors

class ScanActivity : AppCompatActivity() {

    companion object {
        var cachedInventory: List<AssetItem> = emptyList()
    }

    private lateinit var previewView: PreviewView
    private lateinit var cameraExecutor: ExecutorService
    private var camera: Camera? = null
    private var isTorchOn = false

    private lateinit var tvHasilScanTitle: TextView
    private lateinit var tvScannedItemName: TextView
    private lateinit var tvScannedItemCode: TextView
    private lateinit var tvScannedItemStatus: TextView
    private lateinit var tvSpesifikasi: TextView
    private lateinit var ivItemIcon: ImageView
    private lateinit var btnAjukanPinjaman: Button

    private var lastScannedCode: String? = null
    private var lastScanTimestamp: Long = 0
    private var isFetchingFromApi = false

    // Data item yang sedang terpilih setelah scan
    private var scannedAssetItem: AssetItem? = null
    private var currentScannedName = "-"
    private var currentScannedCode = "-"
    private var currentScannedStatus = "TERSEDIA"
    private var currentScannedLocation = "-"
    private var currentScannedCategory = "Inventaris"
    private var currentScannedSpecs = "-"

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

    // User info diteruskan dari HomeActivity
    private var userId   = 0
    private var userName = "Pengguna"
    private var userEmail = ""
    private var userRole  = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_scan)

        // Ambil data user dari intent
        userId    = intent.getIntExtra("USER_ID", 0)
        userName  = intent.getStringExtra("USER_NAME") ?: "Pengguna"
        userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        userRole  = intent.getStringExtra("USER_ROLE") ?: ""

        previewView = findViewById(R.id.previewView)
        tvHasilScanTitle = findViewById(R.id.tvHasilScanTitle)
        tvScannedItemName = findViewById(R.id.tvScannedItemName)
        tvScannedItemCode = findViewById(R.id.tvScannedItemCode)
        tvScannedItemStatus = findViewById(R.id.tvScannedItemStatus)
        tvSpesifikasi = findViewById(R.id.tvSpesifikasi)
        ivItemIcon = findViewById(R.id.ivItemIcon)
        btnAjukanPinjaman = findViewById(R.id.btnAjukanPinjaman)

        cameraExecutor = Executors.newSingleThreadExecutor()

        // Tombol Kembali & Tutup
        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { finish() }
        findViewById<TextView>(R.id.tvTutup)?.setOnClickListener { finish() }
        findViewById<Button>(R.id.btnBatal)?.setOnClickListener { finish() }

        // Tombol Senter
        val ivFlashToggle = findViewById<ImageView>(R.id.ivFlashToggle)
        ivFlashToggle?.setOnClickListener { toggleTorch() }

        // Tombol Ajukan Pinjaman / Lihat Detail
        btnAjukanPinjaman.setOnClickListener {
            val intent = Intent(this, DetailActivity::class.java).apply {
                val codeWithPrefix = if (currentScannedCode.startsWith("Kode:")) currentScannedCode else "Kode: $currentScannedCode"
                putExtra("ITEM_NAME", currentScannedName)
                putExtra("ITEM_CODE", codeWithPrefix)
                putExtra("ITEM_STATUS", currentScannedStatus)
                putExtra("ITEM_LOCATION", currentScannedLocation)
                putExtra("ITEM_CATEGORY", currentScannedCategory)
                putExtra("ITEM_SPECS", currentScannedSpecs)
                // User info
                putExtra("USER_ID", userId)
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)

                scannedAssetItem?.let { aset ->
                    putExtra("ITEM_ASET_ID", aset.id)
                    putExtra("ITEM_MERK", aset.merk ?: "-")
                    putExtra("ITEM_SERI", aset.nomorSeri ?: "-")
                    putExtra("ITEM_UNIT", aset.unit ?: 1)
                    putExtra("ITEM_TGL", aset.tanggalPengadaan ?: "-")
                    putExtra("ITEM_HARGA", aset.hargaBeli ?: 0.0)
                    putExtra("ITEM_CABANG", aset.cabang ?: "-")
                    putExtra("ITEM_PIC", aset.penanggungJawab ?: "-")
                }
            }
            startActivity(intent)
            finish()
        }

        // Preload inventaris di background jika cache belum terisi
        if (cachedInventory.isEmpty()) {
            preloadInventory()
        }

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
                // Preload berjalan senyap di background
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
        val mediaImage = imageProxy.image
        if (mediaImage != null) {
            val image = InputImage.fromMediaImage(mediaImage, imageProxy.imageInfo.rotationDegrees)
            val scanner = BarcodeScanning.getClient()

            scanner.process(image)
                .addOnSuccessListener { barcodes ->
                    if (barcodes.isNotEmpty()) {
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
        // Cegah deteksi berulang dalam 3 detik untuk kode yang sama
        if (code == lastScannedCode && (currentTime - lastScanTimestamp) < 3000) return
        if (isFetchingFromApi) return

        lastScannedCode = code
        lastScanTimestamp = currentTime

        vibratePhone()

        val cleanCode = code.trim()

        runOnUiThread {
            tvHasilScanTitle.text = "Mencari data aset..."
            tvScannedItemName.text = cleanCode
            tvScannedItemCode.text = "Memuat dari database..."
            Toast.makeText(this, "Kode terbaca: $cleanCode", Toast.LENGTH_SHORT).show()
        }

        // Cari data aset di memori / API
        lookupAssetFromApi(cleanCode)
    }

    private fun findMatchingAsset(code: String, list: List<AssetItem>): AssetItem? {
        val clean = code.trim()
        if (clean.isEmpty() || list.isEmpty()) return null

        // 1. Pencarian persis (Case-insensitive)
        val exactMatch = list.firstOrNull { it.kodeAset?.trim().equals(clean, ignoreCase = true) }
        if (exactMatch != null) return exactMatch

        // 2. Jika kode QR berisi URL atau parameter query (misal: https://domain/item?code=B.64/...)
        val extractedCode = when {
            clean.contains("code=") -> clean.substringAfter("code=").substringBefore("&").trim()
            clean.contains("/") && clean.startsWith("http") -> clean.substringAfterLast("/").trim()
            else -> clean
        }
        if (extractedCode.isNotEmpty() && extractedCode != clean) {
            val urlMatch = list.firstOrNull { it.kodeAset?.trim().equals(extractedCode, ignoreCase = true) }
            if (urlMatch != null) return urlMatch
        }

        // 3. Substring match (jika kode aset terkandung dalam hasil scan barcode atau sebaliknya)
        val subMatch = list.firstOrNull { item ->
            val k = item.kodeAset?.trim() ?: ""
            k.isNotEmpty() && (clean.contains(k, ignoreCase = true) || (clean.length >= 6 && k.contains(clean, ignoreCase = true)))
        }
        if (subMatch != null) return subMatch

        // 4. Pencarian berdasarkan Nomor Seri (jika barcode adalah barcode nomor seri fisik)
        val seriMatch = list.firstOrNull {
            val s = it.nomorSeri?.trim()
            !s.isNullOrBlank() && s != "-" && s != "--" && s.equals(clean, ignoreCase = true)
        }
        if (seriMatch != null) return seriMatch

        // 5. Pencarian berdasarkan ID numerik
        val idNum = clean.toIntOrNull()
        if (idNum != null) {
            val idMatch = list.firstOrNull { it.id == idNum }
            if (idMatch != null) return idMatch
        }

        return null
    }

    private fun displayFoundAsset(aset: AssetItem) {
        scannedAssetItem       = aset
        currentScannedName     = aset.namaAset ?: "Aset #${aset.kodeAset}"
        currentScannedCode     = aset.kodeAset ?: "-"
        currentScannedStatus   = (aset.status ?: aset.statusRaw ?: "Baik").trim()
        currentScannedLocation = aset.lokasi ?: "-"
        currentScannedCategory = aset.kategori ?: "Inventaris"
        currentScannedSpecs    = formatSpecsForScan(aset)

        runOnUiThread {
            tvHasilScanTitle.text = "Barang Berhasil Ditemukan!"
            tvScannedItemName.text = currentScannedName
            tvScannedItemCode.text = "Kode: $currentScannedCode"
            tvScannedItemStatus.text = "STATUS: ${currentScannedStatus.uppercase()}"
            tvSpesifikasi.text = "Spesifikasi:\n$currentScannedSpecs"
            btnAjukanPinjaman.text = "Lihat Detail & Ajukan"

            val statusUpper = currentScannedStatus.uppercase()
            when {
                statusUpper.contains("RUSAK BERAT") || statusUpper.contains("HILANG") || statusUpper.contains("RUSAK PARAH") -> {
                    tvScannedItemStatus.setBackgroundResource(R.drawable.bg_badge_red)
                    tvScannedItemStatus.setTextColor(Color.parseColor("#C62828"))
                }
                statusUpper.contains("RUSAK") || statusUpper.contains("PERBAIKAN") || statusUpper.contains("DIPINJAM") -> {
                    tvScannedItemStatus.setBackgroundResource(R.drawable.bg_badge_orange)
                    tvScannedItemStatus.setTextColor(Color.parseColor("#E65100"))
                }
                else -> {
                    tvScannedItemStatus.setBackgroundResource(R.drawable.bg_badge_green)
                    tvScannedItemStatus.setTextColor(Color.parseColor("#2E7D32"))
                }
            }

            // Penyesuaian ikon berdasarkan kategori dan nama
            val checkStr = (currentScannedName + " " + currentScannedCategory).lowercase()
            when {
                checkStr.contains("kendaraan") || checkStr.contains("mobil") || checkStr.contains("motor") ->
                    ivItemIcon.setImageResource(android.R.drawable.ic_menu_directions)
                checkStr.contains("proyektor") || checkStr.contains("kamera") || checkStr.contains("cctv") ->
                    ivItemIcon.setImageResource(android.R.drawable.ic_menu_camera)
                checkStr.contains("laptop") || checkStr.contains("komputer") || checkStr.contains("printer") ->
                    ivItemIcon.setImageResource(android.R.drawable.ic_menu_slideshow)
                checkStr.contains("meja") || checkStr.contains("kursi") || checkStr.contains("furniture") ->
                    ivItemIcon.setImageResource(android.R.drawable.ic_menu_edit)
                else ->
                    ivItemIcon.setImageResource(android.R.drawable.ic_menu_agenda)
            }
        }
    }

    private fun displayNotFound(code: String) {
        scannedAssetItem       = null
        currentScannedName     = "Barang Tidak Dikenal"
        currentScannedCode     = code
        currentScannedStatus   = "TIDAK DITEMUKAN DI DATABASE"
        currentScannedLocation = "-"
        currentScannedCategory = "Inventaris DMK"
        currentScannedSpecs    = "Kode QR/Barcode: $code. Data tidak ditemukan di sistem."

        runOnUiThread {
            tvHasilScanTitle.text = "Kode Tidak Ditemukan"
            tvScannedItemName.text = "Kode: $code"
            tvScannedItemCode.text = "Tidak ada di database"
            tvScannedItemStatus.text = "TIDAK TERDAFTAR"
            tvScannedItemStatus.setBackgroundResource(R.drawable.bg_badge_orange)
            tvScannedItemStatus.setTextColor(Color.parseColor("#E65100"))
            tvSpesifikasi.text = "Kode ini tidak terdaftar di sistem inventaris DMK."
            btnAjukanPinjaman.text = "Lihat Detail"
        }
    }

    private fun displayOfflineError(code: String, errorMsg: String) {
        scannedAssetItem       = null
        currentScannedName     = "Barang Inventaris"
        currentScannedCode     = code
        currentScannedStatus   = "OFFLINE"
        currentScannedLocation = "-"
        currentScannedCategory = "Inventaris"
        currentScannedSpecs    = "Kode: $code. Gagal terhubung ke server ($errorMsg)."

        runOnUiThread {
            tvHasilScanTitle.text = "Offline - Data Tidak Tersedia"
            tvScannedItemName.text = "Kode: $code"
            tvScannedItemCode.text = "Server tidak terjangkau"
            tvScannedItemStatus.text = "OFFLINE"
            tvScannedItemStatus.setBackgroundResource(R.drawable.bg_badge_red)
            tvScannedItemStatus.setTextColor(Color.parseColor("#C62828"))
            tvSpesifikasi.text = "Periksa koneksi internet dan pastikan server aktif."
            btnAjukanPinjaman.text = "Lihat Detail"
        }
    }

    // ─── Lookup ke API / Cache ────────────────────────────────────────────────
    private fun lookupAssetFromApi(code: String) {
        val cleanCode = code.trim()

        // 1. Cek langsung dari memory cache jika sudah tersedia
        if (cachedInventory.isNotEmpty()) {
            val matched = findMatchingAsset(cleanCode, cachedInventory)
            if (matched != null) {
                displayFoundAsset(matched)
                return
            }
        }

        isFetchingFromApi = true

        // 2. Coba endpoint findAssetByCode (jika backend telah menyediakan endpoint ini)
        ApiClient.instance.findAssetByCode(cleanCode).enqueue(object : Callback<InventorySingleResponse> {
            override fun onResponse(call: Call<InventorySingleResponse>, response: Response<InventorySingleResponse>) {
                val body = response.body()
                if (response.isSuccessful && body != null && body.status == "success" && body.data != null) {
                    isFetchingFromApi = false
                    displayFoundAsset(body.data)
                } else {
                    // Fallback: Jika endpoint findAssetByCode belum ada (404) di remote server,
                    // ambil daftar inventaris lengkap dan lakukan pencarian otomatis
                    fetchInventoryAndMatch(cleanCode)
                }
            }

            override fun onFailure(call: Call<InventorySingleResponse>, t: Throwable) {
                // Fallback jika pemanggilan pertama gagal
                fetchInventoryAndMatch(cleanCode)
            }
        })
    }

    private fun fetchInventoryAndMatch(code: String) {
        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(call: Call<InventoryListResponse>, response: Response<InventoryListResponse>) {
                isFetchingFromApi = false
                val list = response.body()?.data
                if (response.isSuccessful && !list.isNullOrEmpty()) {
                    cachedInventory = list
                    val matched = findMatchingAsset(code, list)
                    if (matched != null) {
                        displayFoundAsset(matched)
                    } else {
                        displayNotFound(code)
                    }
                } else {
                    displayNotFound(code)
                }
            }

            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {
                isFetchingFromApi = false
                displayOfflineError(code, t.localizedMessage ?: "Jaringan bermasalah")
            }
        })
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
                val vibratorManager = getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as? VibratorManager
                vibratorManager?.defaultVibrator?.vibrate(VibrationEffect.createOneShot(150, VibrationEffect.DEFAULT_AMPLITUDE))
            } else {
                @Suppress("DEPRECATION")
                val vibrator = getSystemService(Context.VIBRATOR_SERVICE) as? Vibrator
                @Suppress("DEPRECATION")
                vibrator?.vibrate(150)
            }
        } catch (e: Exception) {
            // Abaikan jika vibrator tidak tersedia
        }
    }

    private fun formatSpecsForScan(aset: AssetItem): String {
        val lines = mutableListOf<String>()

        val statusVal = (aset.status ?: aset.statusRaw ?: "Baik").trim()
        if (statusVal.isNotBlank() && statusVal != "-") {
            lines.add("• Kondisi: $statusVal")
        }

        var merkVal = aset.merk?.trim()
        val rawSpecs = aset.spesifikasi?.trim() ?: ""
        if ((merkVal.isNullOrBlank() || merkVal == "-") && rawSpecs.contains("| Seri:")) {
            val extracted = rawSpecs.substringBefore("|").trim()
            if (extracted.isNotBlank() && extracted != "-") {
                merkVal = extracted
            }
        }
        if (!merkVal.isNullOrBlank() && merkVal != "-") {
            lines.add("• Merk: $merkVal")
        }

        var seriVal = aset.nomorSeri?.trim()
        if ((seriVal.isNullOrBlank() || seriVal == "-" || seriVal == "--") && rawSpecs.contains("Seri:")) {
            val extracted = rawSpecs.substringAfter("Seri:").trim()
            if (extracted.isNotBlank() && extracted != "-" && extracted != "--") {
                seriVal = extracted
            }
        }
        if (!seriVal.isNullOrBlank() && seriVal != "-" && seriVal != "--") {
            lines.add("• No. Seri: $seriVal")
        }

        lines.add("• Jumlah: ${aset.unit ?: 1} Unit")

        if (!aset.lokasi.isNullOrBlank() && aset.lokasi != "-") {
            lines.add("• Lokasi: ${aset.lokasi}")
        }
        if (!aset.cabang.isNullOrBlank() && aset.cabang != "-") {
            lines.add("• Cabang: ${aset.cabang}")
        }
        if (!aset.tanggalPengadaan.isNullOrBlank() && aset.tanggalPengadaan != "-") {
            lines.add("• Tgl Pengadaan: ${aset.tanggalPengadaan}")
        }

        if (rawSpecs.isNotBlank() && rawSpecs != "-") {
            val isAutoPattern = rawSpecs.contains("| Seri:") || rawSpecs.startsWith("- |")
            if (!isAutoPattern) {
                lines.add("• Keterangan: $rawSpecs")
            }
        }

        return if (lines.isNotEmpty()) lines.joinToString("\n") else "• Kondisi: $statusVal\n• Spesifikasi standar inventaris DMK."
    }

    override fun onDestroy() {
        super.onDestroy()
        cameraExecutor.shutdown()
    }
}
