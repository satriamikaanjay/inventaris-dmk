package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Bundle
import android.provider.OpenableColumns
import android.text.Editable
import android.text.TextWatcher
import android.util.Base64
import android.view.View
import android.webkit.MimeTypeMap
import android.widget.AdapterView
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
    private var overlayLockRuanganManual: View? = null
    private var tvInfoRuanganCabang: TextView? = null
    private var spinnerRuanganBaruRef: Spinner? = null
    private var etRuanganBaruManualRef: EditText? = null
    private var etDeskripsiRef: EditText? = null
    private var btnSubmitRef: Button? = null
    private var defaultSubmitBtnText: String = "Ajukan"
    private var isDropdownRoomSelected = false

    private val allowedExtensions = setOf("jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx")

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

            val mime = contentResolver.getType(uri) ?: "*/*"
            val (rawName, sizeStr) = resolveFileMeta(uri)
            val resolvedName = ensureExtension(rawName, mime)

            val base64Data = readFileAsBase64(uri)
            if (base64Data == null) {
                resetSelectedFile()
                return@registerForActivityResult
            }

            selectedFileUri = uri
            selectedFileMime = mime
            selectedFileName = resolvedName
            selectedFileBase64 = base64Data

            tvSelectedFileName.text = resolvedName
            tvSelectedFileInfo.text = "Ukuran file: $sizeStr • Siap dilampirkan"
            btnPilihFile.text = "Ganti File"
            btnLihatFile.visibility = View.VISIBLE
        }
    }

    private fun resetSelectedFile() {
        selectedFileUri = null
        selectedFileName = null
        selectedFileBase64 = null
        selectedFileMime = null
        tvSelectedFileName.text = "Belum ada file dipilih (Opsional)"
        tvSelectedFileInfo.text = "Mendukung semua jenis file (Foto, PDF, Dokumen, Excel, dll)"
        btnPilihFile.text = "Pilih File"
        btnLihatFile.visibility = View.GONE
    }

    private fun ensureExtension(name: String, mime: String): String {
        val currentExt = name.substringAfterLast('.', "")
        if (name.contains('.') && currentExt.isNotBlank() && currentExt.length <= 5) {
            return name
        }
        val inferred = when (mime.lowercase(Locale.ROOT)) {
            "image/jpeg", "image/jpg" -> "jpg"
            "image/png" -> "png"
            "application/pdf" -> "pdf"
            "application/msword" -> "doc"
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" -> "docx"
            "application/vnd.ms-excel" -> "xls"
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" -> "xlsx"
            else -> MimeTypeMap.getSingleton().getExtensionFromMimeType(mime)?.lowercase(Locale.ROOT)
        }
        return if (!inferred.isNullOrBlank()) "$name.$inferred" else name
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_form_pengajuan)

        val jenisPengajuan = intent.getStringExtra("JENIS_PENGAJUAN") ?: "Pengajuan Lainnya (Aset)"
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
        val etRuanganBaruManual = findViewById<EditText>(R.id.etRuanganBaruManual)
        spinnerRuanganBaruRef = spinnerRuanganBaru
        etRuanganBaruManualRef = etRuanganBaruManual
        overlayLockRuanganBaru = findViewById(R.id.overlayLockRuanganBaru)
        overlayLockRuanganManual = findViewById(R.id.overlayLockRuanganManual)
        tvInfoRuanganCabang = findViewById(R.id.tvInfoRuanganCabang)

        overlayLockRuanganBaru?.setOnClickListener {
            Toast.makeText(
                this,
                "Tidak dapat mengubah saat mengirim",
                Toast.LENGTH_SHORT
            ).show()
        }

        overlayLockRuanganManual?.setOnClickListener {
            val msg = if (isSubmitting) {
                "Tidak dapat mengubah saat mengirim"
            } else {
                "Sudah memilih ruangan dari dropdown. Kembalikan dropdown ke '-- Pilih Ruangan Tujuan Baru --' untuk mengetik manual."
            }
            Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()
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

            // Tampilkan ruangan di cabang yang berbeda dari ruangan lama (jika ada)
            val diffRooms = allRooms.filter { !it.equals(itemRuangan, ignoreCase = true) }
            candidateNewRooms.addAll(diffRooms)

            val spinnerItems = mutableListOf(
                if (candidateNewRooms.isNotEmpty()) "-- Pilih Ruangan Tujuan Baru --"
                else "-- Tidak ada ruangan lain (Ketik Manual) --"
            )
            spinnerItems.addAll(candidateNewRooms)

            val adapter = ArrayAdapter(
                this,
                android.R.layout.simple_spinner_item,
                spinnerItems
            ).also {
                it.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
            }
            spinnerRuanganBaru?.adapter = adapter

            spinnerRuanganBaru?.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
                override fun onItemSelected(parent: AdapterView<*>?, view: View?, position: Int, id: Long) {
                    isDropdownRoomSelected = position > 0
                    updateRoomInputLockState()
                }

                override fun onNothingSelected(parent: AdapterView<*>?) {
                    isDropdownRoomSelected = false
                    updateRoomInputLockState()
                }
            }

            etRuanganBaruManual?.addTextChangedListener(object : TextWatcher {
                override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
                override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                    if (!isSubmitting && !isDropdownRoomSelected) {
                        val txt = s?.toString()?.trim() ?: ""
                        if (txt.isNotEmpty()) {
                            etRuanganBaruManual.error = null
                            tvInfoRuanganCabang?.text = "✓ Menggunakan ruangan baru (input manual): \"$txt\""
                            tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#2E7D32"))
                        } else {
                            tvInfoRuanganCabang?.text = "*Pilih ruangan dari dropdown atau ketik manual nama ruangan baru jika belum tersedia"
                            tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#1C4E8F"))
                        }
                    }
                }
                override fun afterTextChanged(s: Editable?) {}
            })

            updateRoomInputLockState()
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
                val manualInput = etRuanganBaruManual?.text?.toString()?.trim() ?: ""

                if (pos > 0) {
                    ruanganTujuanSelected = spinnerRuanganBaru?.selectedItem?.toString()?.trim()
                } else if (manualInput.isNotEmpty()) {
                    ruanganTujuanSelected = manualInput
                } else {
                    etRuanganBaruManual?.error = "Ketik nama ruangan baru atau pilih dari dropdown"
                    etRuanganBaruManual?.requestFocus()
                    Toast.makeText(
                        this,
                        "Silakan pilih Ruangan Baru dari dropdown atau ketik manual nama ruangan baru!",
                        Toast.LENGTH_LONG
                    ).show()
                    return@setOnClickListener
                }

                if (itemRuangan != "-" && ruanganTujuanSelected.equals(itemRuangan, ignoreCase = true)) {
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
        // FormPengajuanActivity khusus untuk pengajuan Inventory (Data Aset)
        val jenisApi = when {
            jenisLabel.contains("Peleburan", ignoreCase = true)   -> "peleburan"
            jenisLabel.contains("Perpindahan", ignoreCase = true) -> "perpindahan"
            jenisLabel.contains("Servis", ignoreCase = true)      -> "servis"
            else                                                  -> "lainnya_aset"
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
                    val fallback = "Gagal mengirim pengajuan (HTTP ${response.code()})."
                    val errMsg = body?.message?.takeIf { it.isNotBlank() }
                        ?: ApiClient.extractErrorMessage(response, fallback)
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

    private fun updateRoomInputLockState() {
        val etManual = etRuanganBaruManualRef ?: return
        if (isSubmitting) {
            etManual.isEnabled = false
            etManual.isFocusable = false
            etManual.isFocusableInTouchMode = false
            etManual.alpha = 0.55f
            overlayLockRuanganManual?.visibility = View.VISIBLE
            return
        }

        if (isDropdownRoomSelected) {
            // Sudah memilih dari dropdown -> kosongkan & kunci textbox manual agar tidak bisa mengetik manual
            if (!etManual.text.isNullOrEmpty()) {
                etManual.setText("")
            }
            etManual.error = null
            etManual.isEnabled = false
            etManual.isFocusable = false
            etManual.isFocusableInTouchMode = false
            etManual.alpha = 0.55f
            etManual.hint = "Dikunci (sudah memilih dari dropdown)"
            overlayLockRuanganManual?.visibility = View.VISIBLE
            val selected = spinnerRuanganBaruRef?.selectedItem?.toString()?.trim() ?: ""
            tvInfoRuanganCabang?.text = "✓ Ruangan dipilih dari daftar: \"$selected\" (Input manual dikunci)"
            tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#1C4E8F"))
        } else {
            // Belum memilih dari dropdown -> textbox manual aktif & bisa diketik
            etManual.isEnabled = true
            etManual.isFocusable = true
            etManual.isFocusableInTouchMode = true
            etManual.alpha = 1.0f
            etManual.hint = "Ketik ruangan baru jika belum ada di daftar"
            overlayLockRuanganManual?.visibility = View.GONE
            val manualTxt = etManual.text?.toString()?.trim() ?: ""
            if (manualTxt.isNotEmpty()) {
                tvInfoRuanganCabang?.text = "✓ Menggunakan ruangan baru (input manual): \"$manualTxt\""
                tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#2E7D32"))
            } else {
                tvInfoRuanganCabang?.text = "*Pilih ruangan dari dropdown atau ketik manual nama ruangan baru jika belum tersedia"
                tvInfoRuanganCabang?.setTextColor(android.graphics.Color.parseColor("#1C4E8F"))
            }
        }
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
        }
        updateRoomInputLockState()
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
