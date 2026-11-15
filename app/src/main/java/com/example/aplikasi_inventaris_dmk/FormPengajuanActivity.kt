package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Bundle
import android.provider.OpenableColumns
import android.util.Base64
import android.view.View
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.EditText
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.Spinner
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanRequest
import com.example.aplikasi_inventaris_dmk.network.PengajuanSubmitResponse
import java.util.Locale
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class FormPengajuanActivity : AppCompatActivity() {

    private var selectedFileUri: Uri? = null
    private var selectedFileName: String? = null
    private var selectedFileBase64: String? = null
    private var selectedFileMime: String? = null

    private lateinit var tvSelectedFileName: TextView
    private lateinit var tvSelectedFileInfo: TextView
    private lateinit var btnPilihFile: Button
    private lateinit var btnLihatFile: Button

    private var isSubmitting = false
    private var currentSubmitCall: Call<PengajuanSubmitResponse>? = null
    private var overlayLockRuanganBaru: View? = null
    private var tvInfoRuanganCabang: TextView? = null
    private var spinnerRuanganBaruRef: Spinner? = null
    private var etDeskripsiRef: EditText? = null
    private var btnSubmitRef: Button? = null
    private var defaultSubmitBtnText: String = "Ajukan"

    // File picker yang mengizinkan SEMUA jenis file ("*/*")
    private val pickAnyFileLauncher = registerForActivityResult(
        ActivityResultContracts.OpenDocument()
    ) { uri: Uri? ->
        if (uri != null) {
            try {
                contentResolver.takePersistableUriPermission(
                    uri,
                    Intent.FLAG_GRANT_READ_URI_PERMISSION
                )
            } catch (_: Exception) {}

            selectedFileUri = uri
            selectedFileMime = contentResolver.getType(uri) ?: "*/*"
            val (name, sizeStr) = resolveFileMeta(uri)
            selectedFileName = name
            selectedFileBase64 = readFileAsBase64(uri)

            tvSelectedFileName.text = name
            tvSelectedFileInfo.text = "Ukuran file: $sizeStr • Siap dilampirkan"
            btnPilihFile.text = "Ganti File"
            btnLihatFile.visibility = View.VISIBLE
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_form_pengajuan)

        val jenisPengajuan = intent.getStringExtra("JENIS_PENGAJUAN") ?: "Pengajuan Peminjaman"
        val itemName       = intent.getStringExtra("ITEM_NAME")       ?: "Aset Inventaris"
        val itemCode       = intent.getStringExtra("ITEM_CODE")       ?: "Kode: -"
        val itemLocation   = intent.getStringExtra("ITEM_LOCATION")   ?: "-"
        val itemCategory   = intent.getStringExtra("ITEM_CATEGORY")   ?: "Umum"
        val itemCabang     = intent.getStringExtra("ITEM_CABANG")?.trim()?.ifBlank { "-" } ?: "-"
        val itemRuangan    = intent.getStringExtra("ITEM_RUANGAN")?.trim()?.ifBlank { "-" } ?: "-"
        val roomsInCabang  = intent.getStringArrayListExtra("ITEM_RUANGAN_CABANG") ?: arrayListOf()
        val asetId         = intent.getIntExtra("ITEM_ASET_ID", 0).takeIf { it > 0 }

        val userId   = intent.getIntExtra("USER_ID", 0)
        val userName = intent.getStringExtra("USER_NAME") ?: "Pengguna"

        val isPerpindahan = jenisPengajuan.contains("Perpindahan", ignoreCase = true)
        val rawCode = itemCode.removePrefix("Kode:").trim()

        // Tampilkan informasi di Header & Card Ringkasan
        findViewById<TextView>(R.id.tvHeaderTitle)?.text = "Form $jenisPengajuan"
        findViewById<TextView>(R.id.tvBadgeJenisPengajuan)?.text = jenisPengajuan.uppercase(Locale.getDefault())
        findViewById<TextView>(R.id.tvFormAssetName)?.text = itemName
        findViewById<TextView>(R.id.tvFormAssetCode)?.text = itemCode

        // 1. Nama otomatis terisi sesuai akun yang login
        val etNamaPengaju = findViewById<EditText>(R.id.etNamaPengaju)
        etNamaPengaju?.setText(userName)

        // 2. Pengaturan Seksi Perpindahan Ruangan (Ruangan Lama -> Ruangan Baru)
        val layoutPerpindahan = findViewById<LinearLayout>(R.id.layoutPerpindahanRuangan)
        val tvCabangPerpindahan = findViewById<TextView>(R.id.tvCabangPerpindahan)
        val etRuanganLama = findViewById<EditText>(R.id.etRuanganLama)
        val spinnerRuanganBaru = findViewById<Spinner>(R.id.spinnerRuanganBaru)
        spinnerRuanganBaruRef = spinnerRuanganBaru
        overlayLockRuanganBaru = findViewById(R.id.overlayLockRuanganBaru)
        tvInfoRuanganCabang = findViewById(R.id.tvInfoRuanganCabang)

        overlayLockRuanganBaru?.setOnClickListener {
            Toast.makeText(
                this,
                "Tidak dapat mengubah saat mengirim",
                Toast.LENGTH_SHORT
            ).show()
        }

        val candidateNewRooms = mutableListOf<String>()
        if (isPerpindahan) {
            layoutPerpindahan?.visibility = View.VISIBLE
            tvCabangPerpindahan?.text = "Cabang: $itemCabang"
            etRuanganLama?.setText(itemRuangan)

            val allRooms = roomsInCabang
                .map { it.trim() }
                .filter { it.isNotEmpty() && it != "-" }
                .distinct()

            if (allRooms.isEmpty()) {
                AlertDialog.Builder(this)
                    .setTitle("Tidak Dapat Melakukan Perpindahan")
                    .setMessage("Cabang \"$itemCabang\" tidak memiliki daftar ruangan sehingga perpindahan tidak dapat dilakukan.")
                    .setCancelable(false)
                    .setPositiveButton("Kembali") { _, _ -> finish() }
                    .show()
                return
            }

            // Prioritaskan ruangan yang berbeda dari ruangan lama
            val diffRooms = allRooms.filter { !it.equals(itemRuangan, ignoreCase = true) }
            if (diffRooms.isNotEmpty()) {
                candidateNewRooms.addAll(diffRooms)
            } else {
                candidateNewRooms.addAll(allRooms)
            }

            val spinnerItems = mutableListOf("-- Pilih Ruangan Tujuan Baru --")
            spinnerItems.addAll(candidateNewRooms)

            val adapter = ArrayAdapter(
                this,
                android.R.layout.simple_spinner_item,
                spinnerItems
            ).also {
                it.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
            }
            spinnerRuanganBaru?.adapter = adapter
        } else {
            layoutPerpindahan?.visibility = View.GONE
        }

        // 3. Upload File (Bebas semua file) + Tombol Lihat File
        tvSelectedFileName = findViewById(R.id.tvSelectedFileName)
        tvSelectedFileInfo = findViewById(R.id.tvSelectedFileInfo)
        btnPilihFile = findViewById(R.id.btnPilihFile)
        btnLihatFile = findViewById(R.id.btnLihatFile)

        val openFilePicker = {
            if (isSubmitting) {
                Toast.makeText(this, "Tidak dapat mengubah saat mengirim", Toast.LENGTH_SHORT).show()
            } else {
                pickAnyFileLauncher.launch(arrayOf("*/*"))
            }
        }
        btnPilihFile.setOnClickListener { openFilePicker() }
        findViewById<LinearLayout>(R.id.layoutUploadBox)?.setOnClickListener { openFilePicker() }

        btnLihatFile.setOnClickListener {
            openSelectedFilePreview()
        }

        // 4. Deskripsi (Opsional)
        val etDeskripsi = findViewById<EditText>(R.id.etDeskripsi)
        etDeskripsiRef = etDeskripsi

        // 5. Tombol Kembali (Batal Mengajukan / Peringatan Saat Sedang Mengirim)
        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { handleAttemptBack() }
        findViewById<Button>(R.id.btnBatalForm)?.setOnClickListener { handleAttemptBack() }
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                handleAttemptBack()
            }
        })

        // 6. Tombol Ajukan (Nama Pengajuan)
        val btnSubmit = findViewById<Button>(R.id.btnSubmitPengajuan)
        btnSubmitRef = btnSubmit
        val shortJenis = jenisPengajuan.removePrefix("Pengajuan ").trim()
        defaultSubmitBtnText = "Ajukan $shortJenis"
        btnSubmit?.text = defaultSubmitBtnText

        btnSubmit?.setOnClickListener {
            var ruanganTujuanSelected: String? = null
            if (isPerpindahan) {
                val pos = spinnerRuanganBaru?.selectedItemPosition ?: 0
                if (pos <= 0) {
                    Toast.makeText(
                        this,
                        "Silakan pilih Ruangan Baru (tujuan perpindahan) terlebih dahulu!",
                        Toast.LENGTH_LONG
                    ).show()
                    return@setOnClickListener
                }
                ruanganTujuanSelected = spinnerRuanganBaru?.selectedItem?.toString()?.trim()
                if (ruanganTujuanSelected.equals(itemRuangan, ignoreCase = true) && candidateNewRooms.size > 1) {
                    Toast.makeText(
                        this,
                        "Ruangan baru tidak boleh sama dengan ruangan lama ($itemRuangan)!",
                        Toast.LENGTH_LONG
                    ).show()
                    return@setOnClickListener
                }
            }

            val deskripsiText = etDeskripsi?.text?.toString()?.trim()
            submitFormPengajuan(
                btnSubmit = btnSubmit,
                defaultButtonText = "Ajukan $shortJenis",
                userId = if (userId > 0) userId else null,
                userName = userName,
                asetId = asetId,
                kodeAset = rawCode,
                namaAset = itemName,
                kategori = itemCategory,
                lokasi = itemLocation,
                cabangAsal = itemCabang,
                ruanganAsal = itemRuangan,
                cabangTujuan = itemCabang,
                ruanganTujuan = ruanganTujuanSelected ?: itemRuangan,
                jenisLabel = jenisPengajuan,
                deskripsi = deskripsiText,
                fileName = selectedFileName,
                fileBase64 = selectedFileBase64
            )
        }
    }

    private fun openSelectedFilePreview() {
        val uri = selectedFileUri
        if (uri == null) {
            Toast.makeText(this, "Belum ada file yang dipilih", Toast.LENGTH_SHORT).show()
            return
        }

        val mime = selectedFileMime ?: contentResolver.getType(uri) ?: "*/*"

        // Jika file adalah gambar, tampilkan dialog preview gambar langsung + opsi buka eksternal
        if (mime.startsWith("image/")) {
            try {
                contentResolver.openInputStream(uri)?.use { stream ->
                    val bitmap = BitmapFactory.decodeStream(stream)
                    if (bitmap != null) {
                        val iv = ImageView(this).apply {
                            setImageBitmap(bitmap)
                            adjustViewBounds = true
                            setPadding(24, 24, 24, 8)
                        }
                        AlertDialog.Builder(this)
                            .setTitle(selectedFileName ?: "Preview Lampiran")
                            .setView(iv)
                            .setPositiveButton("Tutup", null)
                            .setNeutralButton("Buka di Aplikasi Lain") { _, _ ->
                                launchExternalViewer(uri, mime)
                            }
                            .show()
                        return
                    }
                }
            } catch (_: Exception) {}
        }

        launchExternalViewer(uri, mime)
    }

    private fun launchExternalViewer(uri: Uri, mime: String) {
        try {
            val viewIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, mime)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            startActivity(Intent.createChooser(viewIntent, "Lihat File: ${selectedFileName ?: ""}"))
        } catch (_: Exception) {
            Toast.makeText(
                this,
                "File \"${selectedFileName ?: "lampiran"}\" siap dilampirkan (Tidak ada aplikasi pembuka format ini di HP).",
                Toast.LENGTH_LONG
            ).show()
        }
    }

    private fun submitFormPengajuan(
        btnSubmit: Button,
        defaultButtonText: String,
        userId: Int?,
        userName: String,
        asetId: Int?,
        kodeAset: String,
        namaAset: String,
        kategori: String?,
        lokasi: String?,
        cabangAsal: String,
        ruanganAsal: String,
        cabangTujuan: String,
        ruanganTujuan: String,
        jenisLabel: String,
        deskripsi: String?,
        fileName: String?,
        fileBase64: String?
    ) {
        val jenisApi = when {
            jenisLabel.contains("Permintaan", ignoreCase = true)  -> "permintaan"
            jenisLabel.contains("Peleburan", ignoreCase = true)   -> "peleburan"
            jenisLabel.contains("Perpindahan", ignoreCase = true) -> "perpindahan"
            jenisLabel.contains("Servis", ignoreCase = true)      -> "servis"
            else                                                  -> "peminjaman"
        }

        val combinedKeterangan = buildString {
            if (jenisApi == "perpindahan") {
                append("Pindah dari Ruang $ruanganAsal ke Ruang $ruanganTujuan (Cabang $cabangAsal)")
            }
            if (!deskripsi.isNullOrBlank()) {
                if (isNotEmpty()) append(" — ")
                append(deskripsi)
            }
            if (isEmpty()) {
                append("$jenisLabel oleh $userName")
            }
        }

        setSubmittingState(true)

        val requestBody = PengajuanRequest(
            userId = userId,
            namaUser = userName,
            pemohon = userName,
            asetId = asetId,
            kodeAset = kodeAset,
            namaAset = namaAset,
            kategori = kategori,
            kategoriNama = kategori,
            lokasi = lokasi,
            cabangAsal = cabangAsal,
            ruanganAsal = ruanganAsal,
            cabangTujuan = cabangTujuan,
            ruanganTujuan = ruanganTujuan,
            jenis = jenisApi,
            jenisPengajuan = jenisApi,
            keterangan = combinedKeterangan,
            fileName = fileName,
            fileBase64 = fileBase64
        )

        val call = ApiClient.instance.submitPengajuan(requestBody)
        currentSubmitCall = call
        call.enqueue(object : Callback<PengajuanSubmitResponse> {
            override fun onResponse(
                call: Call<PengajuanSubmitResponse>,
                response: Response<PengajuanSubmitResponse>
            ) {
                if (!isSubmitting || call.isCanceled) return
                setSubmittingState(false)

                val body = response.body()
                val isOk = response.isSuccessful && body != null && (body.status == "success" || body.success == true)
                if (isOk) {
                    PengajuanLocalStore.saveSubmission(
                        context = this@FormPengajuanActivity,
                        serverItem = body?.data,
                        userId = userId,
                        userName = userName,
                        kodeAset = kodeAset,
                        namaAset = namaAset,
                        kategori = kategori,
                        lokasi = if (jenisApi == "perpindahan") "Ruang Lama: $ruanganAsal → Ruang Baru: $ruanganTujuan ($cabangAsal)" else lokasi,
                        cabangAsal = cabangAsal,
                        ruanganAsal = ruanganAsal,
                        cabangTujuan = cabangTujuan,
                        ruanganTujuan = ruanganTujuan,
                        jenisPengajuan = jenisApi,
                        keterangan = combinedKeterangan,
                        fileName = fileName,
                        localFileUri = selectedFileUri?.toString()
                    )
                    Toast.makeText(
                        this@FormPengajuanActivity,
                        "✅ $jenisLabel berhasil dikirim ke Admin!\nStatus saat ini: MENUNGGU persetujuan Admin.",
                        Toast.LENGTH_LONG
                    ).show()
                    setResult(RESULT_OK)
                    finish()
                } else {
                    val errMsg = body?.message ?: "Gagal mengirim pengajuan (HTTP ${response.code()})."
                    Toast.makeText(this@FormPengajuanActivity, "❌ $errMsg", Toast.LENGTH_LONG).show()
                }
            }

            override fun onFailure(call: Call<PengajuanSubmitResponse>, t: Throwable) {
                if (call.isCanceled) return
                setSubmittingState(false)
                Toast.makeText(
                    this@FormPengajuanActivity,
                    "❌ Koneksi ke server gagal: ${t.localizedMessage}",
                    Toast.LENGTH_LONG
                ).show()
            }
        })
    }

    private fun setSubmittingState(submitting: Boolean) {
        isSubmitting = submitting
        btnSubmitRef?.isEnabled = !submitting
        btnSubmitRef?.text = if (submitting) "Mengirim..." else defaultSubmitBtnText
        overlayLockRuanganBaru?.visibility = if (submitting) View.VISIBLE else View.GONE
        spinnerRuanganBaruRef?.isEnabled = !submitting
        etDeskripsiRef?.isEnabled = !submitting
        btnPilihFile.isEnabled = !submitting

        if (submitting) {
            tvInfoRuanganCabang?.text = "⚠ Tidak dapat mengubah saat mengirim"
            tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#C62828"))
        } else {
            tvInfoRuanganCabang?.text = "*Pilihan ruangan menyesuaikan daftar ruangan pada cabang aset ini"
            tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#1C4E8F"))
        }
    }

    private fun handleAttemptBack() {
        if (!isSubmitting) {
            finish()
            return
        }
        AlertDialog.Builder(this)
            .setTitle("Pengiriman Sedang Berlangsung")
            .setMessage("Jika kembali pengajuan gagal. Apakah Anda yakin ingin membatalkan pengiriman?")
            .setPositiveButton("Ya, Kembali") { _, _ ->
                currentSubmitCall?.cancel()
                isSubmitting = false
                finish()
            }
            .setNegativeButton("Lanjutkan Mengirim", null)
            .show()
    }

    private fun readFileAsBase64(uri: Uri): String? {
        return try {
            contentResolver.openInputStream(uri)?.use { input ->
                val bytes = input.readBytes()
                if (bytes.size > 8 * 1024 * 1024) {
                    Toast.makeText(this, "Ukuran file terlalu besar (Maks 8 MB)", Toast.LENGTH_SHORT).show()
                    null
                } else {
                    Base64.encodeToString(bytes, Base64.NO_WRAP)
                }
            }
        } catch (_: Exception) {
            null
        }
    }

    private fun resolveFileMeta(uri: Uri): Pair<String, String> {
        var displayName = "file_lampiran"
        var sizeBytes: Long = -1L

        try {
            contentResolver.query(uri, null, null, null, null)?.use { cursor ->
                if (cursor.moveToFirst()) {
                    val nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                    if (nameIndex >= 0) {
                        val name = cursor.getString(nameIndex)
                        if (!name.isNullOrBlank()) displayName = name
                    }
                    val sizeIndex = cursor.getColumnIndex(OpenableColumns.SIZE)
                    if (sizeIndex >= 0 && !cursor.isNull(sizeIndex)) {
                        sizeBytes = cursor.getLong(sizeIndex)
                    }
                }
            }
        } catch (_: Exception) {
            uri.lastPathSegment?.let { displayName = it }
        }

        val sizeFormatted = when {
            sizeBytes < 0 -> "Ukuran tidak diketahui"
            sizeBytes < 1024 -> "$sizeBytes B"
            sizeBytes < 1024 * 1024 -> String.format(Locale.US, "%.1f KB", sizeBytes / 1024.0)
            else -> String.format(Locale.US, "%.2f MB", sizeBytes / (1024.0 * 1024.0))
        }
        return Pair(displayName, sizeFormatted)
    }
}
