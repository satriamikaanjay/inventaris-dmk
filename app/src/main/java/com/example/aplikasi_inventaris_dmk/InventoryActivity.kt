package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.Color
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.RadioButton
import android.widget.RadioGroup
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import androidx.recyclerview.widget.GridLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.AssetItem
import com.example.aplikasi_inventaris_dmk.network.BranchItem
import com.example.aplikasi_inventaris_dmk.network.BranchListResponse
import com.example.aplikasi_inventaris_dmk.network.CategoryItem
import com.example.aplikasi_inventaris_dmk.network.CategoryListResponse
import com.example.aplikasi_inventaris_dmk.network.InventoryListResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class InventoryActivity : AppCompatActivity() {

    private lateinit var rvInventory: RecyclerView
    private lateinit var progressBarLoading: ProgressBar
    private lateinit var layoutEmptyState: LinearLayout
    private lateinit var tvEmptyMessage: TextView
    private lateinit var btnRetry: Button
    private lateinit var tvTotalItems: TextView
    private lateinit var tvActiveFilter: TextView
    private lateinit var tvFilterLabel: TextView
    private lateinit var adapter: InventoryAdapter

    private var allItems: List<AssetItem> = emptyList()
    private var currentSearch = ""

    // Filter aktif
    private var selectedKategoriId: Int? = null
    private var selectedKategoriName: String? = null
    private var selectedCabangId: Int? = null
    private var selectedCabangName: String? = null

    // Master data dari database sebagai default
    private val defaultKategoris = listOf(
        CategoryItem(0, "Alat Kantor", "H"),
        CategoryItem(0, "Alat Logistik", "F"),
        CategoryItem(0, "Alat Rumah Tangga", "E"),
        CategoryItem(0, "Aset & Properti", "A"),
        CategoryItem(0, "Dekorasi", "G"),
        CategoryItem(0, "Elektronik", "C"),
        CategoryItem(0, "Furniture", "B"),
        CategoryItem(0, "Kendaraan Operasional", "D"),
        CategoryItem(0, "Tanah dan Bangunan", "T")
    )

    private val defaultCabangs = listOf(
        BranchItem(0, "Batu"),
        BranchItem(0, "Juanda"),
        BranchItem(0, "Kediri"),
        BranchItem(0, "Labuan Bajo"),
        BranchItem(0, "Perak"),
        BranchItem(0, "Semarang"),
        BranchItem(0, "Solo"),
        BranchItem(0, "Tower"),
        BranchItem(0, "YIA"),
        BranchItem(0, "Yogyakarta")
    )

    // Data untuk dialog
    private var kategoris: List<CategoryItem> = defaultKategoris
    private var cabangs: List<BranchItem> = defaultCabangs

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_inventory)

        val userName  = intent.getStringExtra("USER_NAME")  ?: "User"
        val userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        val userRole  = intent.getStringExtra("USER_ROLE")  ?: "Staff"
        val userId    = intent.getIntExtra("USER_ID", 0)

        rvInventory        = findViewById(R.id.rvInventory)
        progressBarLoading = findViewById(R.id.progressBarLoading)
        layoutEmptyState   = findViewById(R.id.layoutEmptyState)
        tvEmptyMessage     = findViewById(R.id.tvEmptyMessage)
        btnRetry           = findViewById(R.id.btnRetry)
        tvTotalItems       = findViewById(R.id.tvTotalItems)
        tvActiveFilter     = findViewById(R.id.tvActiveFilter)
        tvFilterLabel      = findViewById(R.id.tvFilterLabel)

        rvInventory.layoutManager = GridLayoutManager(this, 2)
        adapter = InventoryAdapter(emptyList()) { item -> openDetailFromAsset(item) }
        rvInventory.adapter = adapter

        // Navigasi
        findViewById<LinearLayout>(R.id.btnNavHome)?.setOnClickListener { finish() }
        findViewById<LinearLayout>(R.id.btnNavInventory)?.setOnClickListener { }
        findViewById<android.view.View>(R.id.btnNavScan)?.setOnClickListener {
            startActivity(Intent(this, ScanActivity::class.java).apply {
                putExtra("USER_NAME", userName); putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole); putExtra("USER_ID", userId)
            })
        }
        findViewById<LinearLayout>(R.id.btnNavHistory)?.setOnClickListener {
            startActivity(Intent(this, HistoryActivity::class.java).apply {
                putExtra("USER_NAME", userName); putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole); putExtra("USER_ID", userId)
            })
        }

        // Tombol filter icon
        findViewById<LinearLayout>(R.id.btnOpenFilter)?.setOnClickListener { showFilterDialog() }

        // Label filter aktif → klik untuk reset
        tvActiveFilter.setOnClickListener { resetFilter() }

        // Refresh
        findViewById<ImageView>(R.id.ivRefresh)?.setOnClickListener { loadInventoryFromApi() }

        // Notifikasi & Logout
        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            Toast.makeText(this, "Tidak ada notifikasi baru", Toast.LENGTH_SHORT).show()
        }
        findViewById<ImageView>(R.id.ivUserIcon)?.setOnClickListener { showLogoutDialog(userName) }

        // Retry
        btnRetry.setOnClickListener { loadInventoryFromApi() }

        // Live search
        findViewById<EditText>(R.id.etSearch)?.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                currentSearch = s?.toString()?.trim() ?: ""
                applyFilterAndSearch()
            }
            override fun afterTextChanged(s: Editable?) {}
        })

        // Load semua data sekaligus
        loadInventoryFromApi()
        loadCategories()
        loadBranches()
    }

    // ─── Load semua data aset ────────────────────────────────────────────────
    private fun loadInventoryFromApi() {
        showLoading(true)
        layoutEmptyState.visibility = View.GONE

        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(call: Call<InventoryListResponse>, response: Response<InventoryListResponse>) {
                showLoading(false)
                val body = response.body()
                if (response.isSuccessful && body?.status == "success") {
                    allItems = body.data ?: emptyList()
                    ScanActivity.cachedInventory = allItems
                    syncCategoriesAndBranchesFromItems()
                    applyFilterAndSearch()
                } else {
                    showEmpty("Gagal memuat data (HTTP ${response.code()}).")
                }
            }
            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {
                showLoading(false)
                showEmpty("Gagal terhubung ke server: ${t.message}")
            }
        })
    }

    private fun getCleanCategoryName(raw: String?): String {
        val s = raw?.trim() ?: ""
        if (s.equals("Kendraan Operasional", ignoreCase = true)) {
            return "Kendaraan Operasional"
        }
        return s
    }

    private fun syncCategoriesAndBranchesFromItems() {
        if (allItems.isNotEmpty()) {
            val extractedCats = allItems
                .filter { !it.kategori.isNullOrBlank() && it.kategori != "-" }
                .map {
                    val cleanName = getCleanCategoryName(it.kategori)
                    CategoryItem(it.kategoriId ?: 0, cleanName, null)
                }
                .distinctBy { it.namaKategori?.trim()?.lowercase() }

            if (extractedCats.isNotEmpty()) {
                kategoris = (kategoris + extractedCats)
                    .map { CategoryItem(it.id, getCleanCategoryName(it.namaKategori), it.kodeKategori) }
                    .filter { !it.namaKategori.isNullOrBlank() && it.namaKategori != "-" }
                    .distinctBy { it.namaKategori?.trim()?.lowercase() }
                    .sortedBy { it.namaKategori }
            }

            val extractedBranches = allItems
                .filter { !it.cabang.isNullOrBlank() && it.cabang != "-" }
                .map { BranchItem(it.cabangId ?: 0, it.cabang!!.trim()) }
                .distinctBy { it.namaCabang?.trim()?.lowercase() }

            if (extractedBranches.isNotEmpty()) {
                cabangs = (cabangs + extractedBranches)
                    .filter { !it.namaCabang.isNullOrBlank() && it.namaCabang != "-" }
                    .distinctBy { it.namaCabang?.trim()?.lowercase() }
                    .sortedBy { it.namaCabang }
            }
        }
    }

    private fun loadCategories() {
        ApiClient.instance.getCategories().enqueue(object : Callback<CategoryListResponse> {
            override fun onResponse(call: Call<CategoryListResponse>, response: Response<CategoryListResponse>) {
                val data = response.body()?.data
                if (!data.isNullOrEmpty()) {
                    kategoris = (kategoris + data)
                        .map { CategoryItem(it.id, getCleanCategoryName(it.namaKategori), it.kodeKategori) }
                        .filter { !it.namaKategori.isNullOrBlank() && it.namaKategori != "-" }
                        .distinctBy { it.namaKategori?.trim()?.lowercase() }
                        .sortedBy { it.namaKategori }
                }
            }
            override fun onFailure(call: Call<CategoryListResponse>, t: Throwable) {}
        })
    }

    private fun loadBranches() {
        ApiClient.instance.getBranches().enqueue(object : Callback<BranchListResponse> {
            override fun onResponse(call: Call<BranchListResponse>, response: Response<BranchListResponse>) {
                val data = response.body()?.data
                if (!data.isNullOrEmpty()) {
                    cabangs = (cabangs + data)
                        .filter { !it.namaCabang.isNullOrBlank() && it.namaCabang != "-" }
                        .distinctBy { it.namaCabang?.trim()?.lowercase() }
                        .sortedBy { it.namaCabang }
                }
            }
            override fun onFailure(call: Call<BranchListResponse>, t: Throwable) {}
        })
    }

    // ─── Popup Filter ────────────────────────────────────────────────────────
    private fun showFilterDialog() {
        val dialogView = layoutInflater.inflate(R.layout.dialog_filter_inventory, null)
        val rgKategori = dialogView.findViewById<RadioGroup>(R.id.rgKategori)
        val rgCabang   = dialogView.findViewById<RadioGroup>(R.id.rgCabang)
        val rbKategoriSemua = dialogView.findViewById<RadioButton>(R.id.rbKategoriSemua)
        val rbCabangSemua   = dialogView.findViewById<RadioButton>(R.id.rbCabangSemua)

        // Pastikan status centang "Semua" akurat
        val isKatSemua = (selectedKategoriId == null && selectedKategoriName == null)
        rbKategoriSemua.isChecked = isKatSemua

        val isCabSemua = (selectedCabangId == null && selectedCabangName == null)
        rbCabangSemua.isChecked = isCabSemua

        // Pastikan daftar kategori unik tanpa duplikasi
        val uniqueKategoris = kategoris
            .map { CategoryItem(it.id, getCleanCategoryName(it.namaKategori), it.kodeKategori) }
            .filter { !it.namaKategori.isNullOrBlank() && it.namaKategori != "-" }
            .distinctBy { it.namaKategori?.trim()?.lowercase() }
            .sortedBy { it.namaKategori }

        // Tambah radio button semua kategori secara dinamis (tanpa duplikat)
        uniqueKategoris.forEach { kat ->
            val isCheckedThis = (selectedKategoriName != null && kat.namaKategori.equals(selectedKategoriName, ignoreCase = true)) ||
                    (selectedKategoriId != null && selectedKategoriId != 0 && kat.id == selectedKategoriId)

            val rb = RadioButton(this).apply {
                id = View.generateViewId()
                text = kat.namaKategori ?: "-"
                textSize = 13f
                setTextColor(Color.parseColor("#333333"))
                tag = kat.id
                isChecked = isCheckedThis
            }
            rgKategori.addView(rb)
        }

        // Pastikan daftar cabang unik tanpa duplikasi
        val uniqueCabangs = cabangs
            .filter { !it.namaCabang.isNullOrBlank() && it.namaCabang != "-" }
            .distinctBy { it.namaCabang?.trim()?.lowercase() }
            .sortedBy { it.namaCabang }

        // Tambah radio button semua cabang secara dinamis (tanpa duplikat)
        uniqueCabangs.forEach { cab ->
            val isCheckedThis = (selectedCabangName != null && cab.namaCabang.equals(selectedCabangName, ignoreCase = true)) ||
                    (selectedCabangId != null && selectedCabangId != 0 && cab.id == selectedCabangId)

            val rb = RadioButton(this).apply {
                id = View.generateViewId()
                text = cab.namaCabang ?: "-"
                textSize = 13f
                setTextColor(Color.parseColor("#333333"))
                tag = cab.id
                isChecked = isCheckedThis
            }
            rgCabang.addView(rb)
        }

        AlertDialog.Builder(this)
            .setTitle(null)
            .setView(dialogView)
            .setPositiveButton("Terapkan") { _, _ ->
                // Ambil pilihan kategori
                val catCheckedId = rgKategori.checkedRadioButtonId
                if (catCheckedId == R.id.rbKategoriSemua || catCheckedId == -1) {
                    selectedKategoriId   = null
                    selectedKategoriName = null
                } else {
                    val rb = dialogView.findViewById<RadioButton>(catCheckedId)
                    selectedKategoriId   = rb?.tag as? Int
                    selectedKategoriName = rb?.text?.toString()
                }

                // Ambil pilihan cabang
                val cabCheckedId = rgCabang.checkedRadioButtonId
                if (cabCheckedId == R.id.rbCabangSemua || cabCheckedId == -1) {
                    selectedCabangId   = null
                    selectedCabangName = null
                } else {
                    val rb = dialogView.findViewById<RadioButton>(cabCheckedId)
                    selectedCabangId   = rb?.tag as? Int
                    selectedCabangName = rb?.text?.toString()
                }

                applyFilterAndSearch()
                updateFilterLabel()
            }
            .setNegativeButton("Reset") { _, _ ->
                resetFilter()
            }
            .show()
    }

    private fun resetFilter() {
        selectedKategoriId   = null
        selectedKategoriName = null
        selectedCabangId     = null
        selectedCabangName   = null
        applyFilterAndSearch()
        updateFilterLabel()
    }

    private fun updateFilterLabel() {
        val parts = mutableListOf<String>()
        selectedKategoriName?.let { parts.add("Kategori: $it") }
        selectedCabangName?.let  { parts.add("Cabang: $it") }

        if (parts.isEmpty()) {
            tvActiveFilter.visibility = View.GONE
            tvFilterLabel.text = "Filter"
            tvFilterLabel.setTextColor(Color.WHITE)
        } else {
            tvActiveFilter.visibility = View.VISIBLE
            tvActiveFilter.text = parts.joinToString("  |  ") + "   ✕ Reset"
            tvFilterLabel.text = "Aktif"
            tvFilterLabel.setTextColor(Color.parseColor("#FFD700"))
        }
    }

    // ─── Terapkan filter + pencarian ────────────────────────────────────────
    private fun applyFilterAndSearch() {
        var filtered = allItems

        selectedKategoriName?.let { katName ->
            val cleanSelected = getCleanCategoryName(katName).trim().lowercase()
            filtered = filtered.filter { item ->
                val itemKat = getCleanCategoryName(item.kategori).trim().lowercase()
                itemKat == cleanSelected || (selectedKategoriId != null && selectedKategoriId != 0 && item.kategoriId == selectedKategoriId)
            }
        } ?: run {
            selectedKategoriId?.let { katId ->
                filtered = filtered.filter { it.kategoriId == katId }
            }
        }

        selectedCabangName?.let { cabName ->
            val cleanSelected = cabName.trim().lowercase()
            filtered = filtered.filter { item ->
                val itemCab = item.cabang?.trim()?.lowercase() ?: ""
                itemCab == cleanSelected || (selectedCabangId != null && selectedCabangId != 0 && item.cabangId == selectedCabangId)
            }
        } ?: run {
            selectedCabangId?.let { cabId ->
                filtered = filtered.filter { it.cabangId == cabId }
            }
        }

        if (currentSearch.isNotEmpty()) {
            val q = currentSearch.lowercase()
            filtered = filtered.filter { item ->
                (item.namaAset ?: "").lowercase().contains(q) ||
                        (item.kodeAset ?: "").lowercase().contains(q) ||
                        (item.merk ?: "").lowercase().contains(q) ||
                        (item.kategori ?: "").lowercase().contains(q) ||
                        (item.lokasi ?: "").lowercase().contains(q) ||
                        (item.cabang ?: "").lowercase().contains(q)
            }
        }

        // Memperbarui tampilan total aset dengan jumlah data yang sedang tampil/tersedia
        tvTotalItems.text = "${filtered.size}"

        if (filtered.isEmpty()) {
            showEmpty("Tidak ada barang yang sesuai.")
        } else {
            layoutEmptyState.visibility = View.GONE
            rvInventory.visibility = View.VISIBLE
        }
        adapter.updateData(filtered)
    }

    private fun openDetailFromAsset(item: AssetItem) {
        startActivity(Intent(this, DetailActivity::class.java).apply {
            val statusDisplay = item.status ?: item.statusRaw ?: "Baik"
            putExtra("ITEM_NAME",     item.namaAset ?: "Aset #${item.kodeAset ?: ""}")
            putExtra("ITEM_CODE",     "Kode: ${item.kodeAset ?: "-"}")
            putExtra("ITEM_STATUS",   statusDisplay)
            putExtra("ITEM_LOCATION", item.lokasi ?: "-")
            putExtra("ITEM_CATEGORY", item.kategori ?: "Umum")

            // Format spesifikasi lengkap dari database
            val infoSpesifikasi = buildSpesifikasiString(item)
            putExtra("ITEM_SPECS",    infoSpesifikasi)

            // Ekstra detail untuk keamanan parsing
            putExtra("ITEM_MERK",     item.merk ?: "-")
            putExtra("ITEM_SERI",     item.nomorSeri ?: "-")
            putExtra("ITEM_UNIT",     item.unit ?: 1)
            putExtra("ITEM_TGL",      item.tanggalPengadaan ?: "-")
            putExtra("ITEM_HARGA",    item.hargaBeli ?: 0.0)
            putExtra("ITEM_CABANG",   item.cabang ?: "-")
            putExtra("ITEM_PIC",      item.penanggungJawab ?: "-")
        })
    }

    private fun buildSpesifikasiString(item: AssetItem): String {
        val lines = mutableListOf<String>()

        // 1. Kondisi Fisik (Baik, Rusak Ringan, Rusak Sedang, Rusak Berat)
        val statusVal = (item.status ?: item.statusRaw ?: "Baik").trim()
        if (statusVal.isNotBlank() && statusVal != "-") {
            lines.add("• Kondisi Fisik: $statusVal")
        }

        // 2. Merk / Brand (Prioritas dari field merk atau pola 'Merk | Seri:')
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

        // 3. Nomor Seri (Prioritas dari field nomorSeri atau pola 'Merk | Seri:')
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

        // 4. Jumlah Unit
        val totalUnit = item.unit ?: 1
        lines.add("• Jumlah Unit: $totalUnit Unit")

        // 5. Lokasi Penyimpanan & Cabang
        val lokasiVal = item.lokasi?.trim()
        if (!lokasiVal.isNullOrBlank() && lokasiVal != "-") {
            lines.add("• Lokasi Penyimpanan: $lokasiVal")
        }
        val cabangVal = item.cabang?.trim()
        if (!cabangVal.isNullOrBlank() && cabangVal != "-") {
            lines.add("• Cabang: $cabangVal")
        }

        // 6. Tanggal Pengadaan
        val tglVal = item.tanggalPengadaan?.trim()
        if (!tglVal.isNullOrBlank() && tglVal != "-") {
            lines.add("• Tanggal Pengadaan: $tglVal")
        }

        // 7. Nilai Perolehan / Harga Beli
        val harga = item.hargaBeli ?: 0.0
        if (harga > 0) {
            val formatRupiah = java.text.NumberFormat.getCurrencyInstance(java.util.Locale.forLanguageTag("id-ID"))
            lines.add("• Nilai Perolehan: ${formatRupiah.format(harga).replace("Rp", "Rp ")}")
        }

        // 8. Penanggung Jawab
        val picVal = item.penanggungJawab?.trim()
        if (!picVal.isNullOrBlank() && picVal != "-") {
            lines.add("• Penanggung Jawab: $picVal")
        }

        // 9. Kategori
        val katVal = item.kategori?.trim()
        if (!katVal.isNullOrBlank() && katVal != "-") {
            lines.add("• Kategori: $katVal")
        }

        // 10. Keterangan Tambahan jika ada info spesifik selain pola otomatis
        if (rawSpecs.isNotBlank() && rawSpecs != "-") {
            val isAutoPattern = rawSpecs.contains("| Seri:") || rawSpecs.startsWith("- |")
            if (!isAutoPattern) {
                lines.add("• Keterangan Tambahan: $rawSpecs")
            }
        }

        return if (lines.isNotEmpty()) lines.joinToString("\n") else "• Kondisi Fisik: $statusVal\n• Spesifikasi standar inventaris DMK."
    }

    private fun showLoading(show: Boolean) {
        progressBarLoading.visibility = if (show) View.VISIBLE else View.GONE
        rvInventory.visibility        = if (show) View.GONE    else View.VISIBLE
    }

    private fun showEmpty(msg: String) {
        rvInventory.visibility      = View.GONE
        layoutEmptyState.visibility = View.VISIBLE
        tvEmptyMessage.text         = msg
    }

    private fun showLogoutDialog(userName: String) {
        AlertDialog.Builder(this)
            .setTitle("Keluar Akun")
            .setMessage("Halo $userName, apakah Anda yakin ingin keluar?")
            .setPositiveButton("Keluar") { _, _ ->
                startActivity(Intent(this, LoginActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                })
                finish()
            }
            .setNegativeButton("Batal", null)
            .show()
    }
}