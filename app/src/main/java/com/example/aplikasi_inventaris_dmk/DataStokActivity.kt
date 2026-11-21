package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.BitmapFactory
import android.graphics.Color
import android.graphics.drawable.ColorDrawable
import android.net.Uri
import android.os.Bundle
import android.provider.OpenableColumns
import android.text.Editable
import android.text.TextWatcher
import android.util.Base64
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
import android.webkit.MimeTypeMap
import android.widget.AdapterView
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.EditText
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.Spinner
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanRequest
import com.example.aplikasi_inventaris_dmk.network.PengajuanSubmitResponse
import com.example.aplikasi_inventaris_dmk.network.StokItem
import com.example.aplikasi_inventaris_dmk.network.StokListResponse
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class DataStokActivity : AppCompatActivity() {

    private enum class FilterStatus {
        SEMUA,
        TERSEDIA,
        HABIS
    }

    data class SatuanConversionOption(
        val displayLabel: String,
        val shortLabel: String,
        val multiplier: Int,
        val baseSatuan: String
    ) {
        override fun toString(): String = displayLabel
    }

    data class SelectedAttachment(
        val uri: Uri,
        val fileName: String,
        val fileSizeStr: String,
        val mimeType: String,
        val base64Data: String
    )

    private var onFilePickedForDialog: ((SelectedAttachment) -> Unit)? = null

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
            val base64Data = readFileAsBase64(uri) ?: return@registerForActivityResult

            val attachment = SelectedAttachment(
                uri = uri,
                fileName = resolvedName,
                fileSizeStr = sizeStr,
                mimeType = mime,
                base64Data = base64Data
            )
            onFilePickedForDialog?.invoke(attachment)
        }
    }

    private lateinit var rvDataStok: RecyclerView
    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var layoutEmptyState: LinearLayout
    private lateinit var tvEmptyTitle: TextView
    private lateinit var tvEmptySubtitle: TextView
    private lateinit var btnRetry: Button

    private lateinit var cardServerStatus: CardView
    private lateinit var viewServerDot: View
    private lateinit var tvServerStatus: TextView
    private lateinit var tvLastUpdated: TextView

    private lateinit var tvStatTotalJenis: TextView
    private lateinit var tvStatTersedia: TextView
    private lateinit var tvStatKosong: TextView

    private lateinit var etSearch: EditText
    private lateinit var btnClearSearch: ImageView

    private lateinit var btnFilterSemua: TextView
    private lateinit var btnFilterTersedia: TextView
    private lateinit var btnFilterHabis: TextView
    private lateinit var btnPengajuanLainnyaLuar: Button

    private lateinit var adapter: DataStokAdapter

    private var masterStokList: List<StokItem> = emptyList()
    private var currentFilter: FilterStatus = FilterStatus.SEMUA
    private var currentQuery: String = ""

    private var currentUserId: Int = 0
    private var currentUserName: String = "Pengguna"
    private var currentUserEmail: String = ""
    private var currentUserRole: String = ""

    private val numberFormat = NumberFormat.getNumberInstance(Locale.Builder().setLanguage("id").setRegion("ID").build())
    private val timeFormat = SimpleDateFormat("HH:mm:ss", Locale.getDefault())

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_data_stok)

        resolveActiveUserAccount()
        initViews()
        setupRecyclerView()
        setupListeners()
        loadDataStok(showLoadingIndicator = true)
    }

    private fun resolveActiveUserAccount() {
        val activeSession = PengajuanLocalStore.getActiveSession(this)
        val intentUserId = intent.getIntExtra("USER_ID", 0)
        val intentUserName = intent.getStringExtra("USER_NAME")?.trim()
        val intentUserEmail = intent.getStringExtra("USER_EMAIL")?.trim()
        val intentUserRole = intent.getStringExtra("USER_ROLE")?.trim()

        currentUserId = if (intentUserId > 0) intentUserId else (activeSession?.userId ?: 0)
        currentUserName = if (!intentUserName.isNullOrBlank() && !intentUserName.equals("Pengguna", ignoreCase = true)) {
            intentUserName
        } else {
            activeSession?.userName?.takeIf { it.isNotBlank() } ?: (intentUserName ?: "Pengguna")
        }
        currentUserEmail = if (!intentUserEmail.isNullOrBlank()) intentUserEmail else (activeSession?.userEmail ?: "")
        currentUserRole = if (!intentUserRole.isNullOrBlank()) intentUserRole else (activeSession?.userRole ?: "")
    }

    private fun initViews() {
        rvDataStok = findViewById(R.id.rvDataStok)
        swipeRefresh = findViewById(R.id.swipeRefreshStok)
        progressBar = findViewById(R.id.progressBarStok)
        layoutEmptyState = findViewById(R.id.layoutEmptyStateStok)
        tvEmptyTitle = findViewById(R.id.tvEmptyTitleStok)
        tvEmptySubtitle = findViewById(R.id.tvEmptySubtitleStok)
        btnRetry = findViewById(R.id.btnRetryStok)

        cardServerStatus = findViewById(R.id.cardStokServerStatus)
        viewServerDot = findViewById(R.id.viewStokServerDot)
        tvServerStatus = findViewById(R.id.tvStokServerStatus)
        tvLastUpdated = findViewById(R.id.tvStokLastUpdated)

        tvStatTotalJenis = findViewById(R.id.tvStatTotalJenis)
        tvStatTersedia = findViewById(R.id.tvStatTersedia)
        tvStatKosong = findViewById(R.id.tvStatKosong)

        etSearch = findViewById(R.id.etSearchStok)
        btnClearSearch = findViewById(R.id.btnClearSearch)

        btnFilterSemua = findViewById(R.id.btnFilterSemua)
        btnFilterTersedia = findViewById(R.id.btnFilterTersedia)
        btnFilterHabis = findViewById(R.id.btnFilterHabis)
        btnPengajuanLainnyaLuar = findViewById(R.id.btnPengajuanLainnyaLuar)

        swipeRefresh.setColorSchemeColors(Color.parseColor("#1C4E8F"), Color.parseColor("#2E7D32"))
    }

    private fun setupRecyclerView() {
        adapter = DataStokAdapter(emptyList()) { selectedItem ->
            showStokDetailDialog(selectedItem)
        }
        rvDataStok.layoutManager = LinearLayoutManager(this)
        rvDataStok.adapter = adapter
    }

    private fun setupListeners() {
        findViewById<ImageView>(R.id.ivBack).setOnClickListener {
            finish()
        }

        findViewById<ImageView>(R.id.btnRefresh).setOnClickListener {
            loadDataStok(showLoadingIndicator = true)
        }

        swipeRefresh.setOnRefreshListener {
            loadDataStok(showLoadingIndicator = false)
        }

        btnRetry.setOnClickListener {
            loadDataStok(showLoadingIndicator = true)
        }

        btnClearSearch.setOnClickListener {
            etSearch.setText("")
        }

        etSearch.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                currentQuery = s?.toString()?.trim() ?: ""
                btnClearSearch.visibility = if (currentQuery.isNotEmpty()) View.VISIBLE else View.GONE
                applyFilters()
            }
            override fun afterTextChanged(s: Editable?) {}
        })

        btnFilterSemua.setOnClickListener {
            currentFilter = FilterStatus.SEMUA
            updateFilterTabsUi()
            applyFilters()
        }

        btnFilterTersedia.setOnClickListener {
            currentFilter = FilterStatus.TERSEDIA
            updateFilterTabsUi()
            applyFilters()
        }

        btnFilterHabis.setOnClickListener {
            currentFilter = FilterStatus.HABIS
            updateFilterTabsUi()
            applyFilters()
        }

        // Tombol Pengajuan Lainnya di luar daftar (untuk mengajukan barang yang belum ada di Data Stok)
        btnPengajuanLainnyaLuar.setOnClickListener {
            showFormPengajuanLainnyaStokDialog()
        }

        findViewById<LinearLayout>(R.id.btnNavHome)?.setOnClickListener {
            startActivity(Intent(this, HomeActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
                putExtra("USER_NAME", currentUserName)
                putExtra("USER_EMAIL", currentUserEmail)
                putExtra("USER_ROLE", currentUserRole)
                putExtra("USER_ID", currentUserId)
            })
            finish()
        }

        findViewById<LinearLayout>(R.id.btnNavInventory)?.setOnClickListener {
            startActivity(Intent(this, InventoryActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", currentUserName)
                putExtra("USER_EMAIL", currentUserEmail)
                putExtra("USER_ROLE", currentUserRole)
                putExtra("USER_ID", currentUserId)
            })
            finish()
        }

        findViewById<View>(R.id.btnNavScan)?.setOnClickListener {
            startActivity(Intent(this, ScanActivity::class.java).apply {
                putExtra("USER_NAME", currentUserName)
                putExtra("USER_EMAIL", currentUserEmail)
                putExtra("USER_ROLE", currentUserRole)
                putExtra("USER_ID", currentUserId)
            })
        }

        findViewById<LinearLayout>(R.id.btnNavStok)?.setOnClickListener {
            rvDataStok.smoothScrollToPosition(0)
        }

        findViewById<LinearLayout>(R.id.btnNavHistory)?.setOnClickListener {
            startActivity(Intent(this, HistoryActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                putExtra("USER_NAME", currentUserName)
                putExtra("USER_EMAIL", currentUserEmail)
                putExtra("USER_ROLE", currentUserRole)
                putExtra("USER_ID", currentUserId)
            })
            finish()
        }
    }

    private fun updateFilterTabsUi() {
        val selectedBg = R.drawable.bg_btn_blue_square
        val unselectedBg = R.drawable.bg_nik_badge

        when (currentFilter) {
            FilterStatus.SEMUA -> {
                btnFilterSemua.setBackgroundResource(selectedBg)
                btnFilterSemua.setTextColor(Color.WHITE)

                btnFilterTersedia.setBackgroundResource(unselectedBg)
                btnFilterTersedia.setTextColor(Color.parseColor("#1C4E8F"))

                btnFilterHabis.setBackgroundResource(unselectedBg)
                btnFilterHabis.setTextColor(Color.parseColor("#C62828"))
            }
            FilterStatus.TERSEDIA -> {
                btnFilterSemua.setBackgroundResource(unselectedBg)
                btnFilterSemua.setTextColor(Color.parseColor("#64748B"))

                btnFilterTersedia.setBackgroundResource(selectedBg)
                btnFilterTersedia.setTextColor(Color.WHITE)

                btnFilterHabis.setBackgroundResource(unselectedBg)
                btnFilterHabis.setTextColor(Color.parseColor("#C62828"))
            }
            FilterStatus.HABIS -> {
                btnFilterSemua.setBackgroundResource(unselectedBg)
                btnFilterSemua.setTextColor(Color.parseColor("#64748B"))

                btnFilterTersedia.setBackgroundResource(unselectedBg)
                btnFilterTersedia.setTextColor(Color.parseColor("#1C4E8F"))

                btnFilterHabis.setBackgroundResource(R.drawable.bg_circle_red)
                btnFilterHabis.setTextColor(Color.WHITE)
            }
        }
    }

    private fun loadDataStok(showLoadingIndicator: Boolean) {
        if (showLoadingIndicator) {
            progressBar.visibility = View.VISIBLE
        }

        val currentUrl = ApiClient.getBaseUrl(this)
        val hostDisplay = ApiClient.getDisplayHost(currentUrl)

        tvServerStatus.text = "Menyinkronkan data stok dari $hostDisplay..."
        tvServerStatus.setTextColor(Color.parseColor("#1E293B"))
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#F1F5F9"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_orange)

        ApiClient.instance.getDataStok().enqueue(object : Callback<StokListResponse> {
            override fun onResponse(
                call: Call<StokListResponse>,
                response: Response<StokListResponse>
            ) {
                progressBar.visibility = View.GONE
                swipeRefresh.isRefreshing = false

                if (response.isSuccessful && response.body()?.status == "success") {
                    val list = response.body()?.data ?: emptyList()
                    masterStokList = list

                    updateSummaryCards(list)
                    updateServerStatusSuccess(hostDisplay, list.size)
                    applyFilters()
                } else {
                    updateServerStatusError("Gagal mengambil data stok (HTTP ${response.code()})")
                    if (masterStokList.isEmpty()) {
                        showEmptyState(
                            "Gagal Sinkronisasi",
                            "Server web merespons kode HTTP ${response.code()} saat memuat data stok."
                        )
                    }
                }
            }

            override fun onFailure(call: Call<StokListResponse>, t: Throwable) {
                progressBar.visibility = View.GONE
                swipeRefresh.isRefreshing = false

                val errMsg = t.localizedMessage ?: "Timeout"
                updateServerStatusError("Offline • Tidak dapat terhubung ke $hostDisplay")
                if (masterStokList.isEmpty()) {
                    showEmptyState(
                        "Koneksi Gagal",
                        "Pastikan laptop dan HP terhubung ke Wi-Fi yang sama dan web aktif di $hostDisplay.\n\nDetail: $errMsg"
                    )
                } else {
                    Toast.makeText(
                        this@DataStokActivity,
                        "Gagal menyinkronkan data stok terbaru dari server.",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            }
        })
    }

    private fun updateSummaryCards(items: List<StokItem>) {
        val totalJenis = items.size
        val totalTersedia = items.count { (it.tersedia == true) || ((it.jumlah ?: 0) > 0) }
        val totalKosong = items.count { (it.tersedia == false) || ((it.jumlah ?: 0) <= 0) }

        tvStatTotalJenis.text = numberFormat.format(totalJenis)
        tvStatTersedia.text = numberFormat.format(totalTersedia)
        tvStatKosong.text = numberFormat.format(totalKosong)
    }

    private fun updateServerStatusSuccess(host: String, itemCount: Int) {
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#E8F5E9"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_blue)
        tvServerStatus.text = "Sinkron dengan web ($host) • $itemCount jenis barang"
        tvServerStatus.setTextColor(Color.parseColor("#2E7D32"))
        tvLastUpdated.text = "Diperbarui ${timeFormat.format(Date())}"
        tvLastUpdated.setTextColor(Color.parseColor("#1B5E20"))
    }

    private fun updateServerStatusError(message: String) {
        cardServerStatus.setCardBackgroundColor(Color.parseColor("#FFEBEE"))
        viewServerDot.setBackgroundResource(R.drawable.bg_circle_red)
        tvServerStatus.text = message
        tvServerStatus.setTextColor(Color.parseColor("#C62828"))
        tvLastUpdated.text = "Offline"
        tvLastUpdated.setTextColor(Color.parseColor("#7F1D1D"))
    }

    private fun applyFilters() {
        var filtered = masterStokList

        filtered = when (currentFilter) {
            FilterStatus.SEMUA -> filtered
            FilterStatus.TERSEDIA -> filtered.filter { (it.tersedia == true) || ((it.jumlah ?: 0) > 0) }
            FilterStatus.HABIS -> filtered.filter { (it.tersedia == false) || ((it.jumlah ?: 0) <= 0) }
        }

        if (currentQuery.isNotEmpty()) {
            val q = currentQuery.lowercase(Locale.ROOT)
            filtered = filtered.filter { item ->
                val nama = item.namaBarang?.lowercase(Locale.ROOT) ?: ""
                val satuan = item.satuan?.lowercase(Locale.ROOT) ?: ""
                val ket = item.keterangan?.lowercase(Locale.ROOT) ?: ""
                nama.contains(q) || satuan.contains(q) || ket.contains(q)
            }
        }

        adapter.updateData(filtered)

        if (filtered.isEmpty()) {
            if (masterStokList.isEmpty()) {
                showEmptyState(
                    "Belum Ada Data Stok",
                    "Tidak ada data stok barang yang tercatat pada sistem web."
                )
            } else {
                showEmptyState(
                    "Barang Tidak Ditemukan",
                    "Tidak ada stok yang cocok dengan kata kunci \"$currentQuery\" atau filter aktif saat ini."
                )
            }
        } else {
            layoutEmptyState.visibility = View.GONE
            rvDataStok.visibility = View.VISIBLE
        }
    }

    private fun showEmptyState(title: String, subtitle: String) {
        layoutEmptyState.visibility = View.VISIBLE
        rvDataStok.visibility = View.GONE
        tvEmptyTitle.text = title
        tvEmptySubtitle.text = subtitle
    }

    /**
     * Menghasilkan opsi satuan (satuan dasar + satuan besar/konversi) berdasarkan nama barang & satuan dasar:
     * 1. Lakban DMK: 1 dus = 54 roll atau 9 roll, 1 slop = 6 roll
     * 2. Lakban Bening atau Warna: 1 dus = 12 Slop atau 72 roll, 1 slop = 6 roll
     * 3. 1 slop = 6 roll (untuk barang satuan Roll)
     * 4. HVS / Kertas A4: 1 dus = 5 rim
     * 5. Wrapping: 1 dus = 6 roll
     */
    private fun getConversionOptionsForItem(item: StokItem): Pair<List<SatuanConversionOption>, String?> {
        val rawName = (item.namaBarang ?: "").trim()
        val lowerName = rawName.lowercase(Locale.ROOT)
        val baseSatuan = item.satuan?.trim()?.takeIf { it.isNotEmpty() && it != "-" } ?: "Unit"
        val lowerSatuan = baseSatuan.lowercase(Locale.ROOT)

        return when {
            // 1. Lakban DMK: 1 dus = 54 roll atau 9 roll, 1 slop = 6 roll
            lowerName.contains("lakban dmk") -> {
                val options = listOf(
                    SatuanConversionOption(
                        displayLabel = "Roll (Satuan — 1 Roll)",
                        shortLabel = "Roll",
                        multiplier = 1,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Slop (1 Slop = 6 Roll)",
                        shortLabel = "Slop (Isi 6 Roll)",
                        multiplier = 6,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Dus Isi 9 (1 Dus = 9 Roll)",
                        shortLabel = "Dus (Isi 9 Roll)",
                        multiplier = 9,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Dus Isi 54 (1 Dus = 54 Roll)",
                        shortLabel = "Dus (Isi 54 Roll)",
                        multiplier = 54,
                        baseSatuan = baseSatuan
                    )
                )
                val info = "• 1 Slop = 6 Roll\n• 1 Dus = 9 Roll atau 54 Roll"
                Pair(options, info)
            }

            // 2. Lakban Bening atau Warna: 1 dus = 12 Slop atau 72 roll, 1 slop = 6 roll
            lowerName.contains("lakban") -> {
                val options = listOf(
                    SatuanConversionOption(
                        displayLabel = "Roll (Satuan — 1 Roll)",
                        shortLabel = "Roll",
                        multiplier = 1,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Slop (1 Slop = 6 Roll)",
                        shortLabel = "Slop (Isi 6 Roll)",
                        multiplier = 6,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Dus (1 Dus = 12 Slop / 72 Roll)",
                        shortLabel = "Dus (12 Slop / 72 Roll)",
                        multiplier = 72,
                        baseSatuan = baseSatuan
                    )
                )
                val info = "• 1 Slop = 6 Roll\n• 1 Dus = 12 Slop atau 72 Roll"
                Pair(options, info)
            }

            // 5. Wrapping: 1 dus = 6 roll
            lowerName.contains("wrapping") -> {
                val options = listOf(
                    SatuanConversionOption(
                        displayLabel = "Roll (Satuan — 1 Roll)",
                        shortLabel = "Roll",
                        multiplier = 1,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Dus (1 Dus = 6 Roll)",
                        shortLabel = "Dus (Isi 6 Roll)",
                        multiplier = 6,
                        baseSatuan = baseSatuan
                    )
                )
                val info = "• 1 Dus = 6 Roll"
                Pair(options, info)
            }

            // 4. HVS / Kertas A4: 1 dus = 5 rim
            lowerName.contains("hvs") || lowerName.contains("kertas a4") || lowerSatuan == "rim" -> {
                val rimLabel = if (lowerSatuan == "rim") baseSatuan else "Rim"
                val options = listOf(
                    SatuanConversionOption(
                        displayLabel = "$rimLabel (Satuan — 1 $rimLabel)",
                        shortLabel = rimLabel,
                        multiplier = 1,
                        baseSatuan = rimLabel
                    ),
                    SatuanConversionOption(
                        displayLabel = "Dus (1 Dus = 5 $rimLabel)",
                        shortLabel = "Dus (Isi 5 $rimLabel)",
                        multiplier = 5,
                        baseSatuan = rimLabel
                    )
                )
                val info = "• 1 Dus = 5 $rimLabel"
                Pair(options, info)
            }

            // 3. Barang lainnya dengan satuan Roll: 1 slop = 6 roll
            lowerSatuan == "roll" -> {
                val options = listOf(
                    SatuanConversionOption(
                        displayLabel = "Roll (Satuan — 1 Roll)",
                        shortLabel = "Roll",
                        multiplier = 1,
                        baseSatuan = baseSatuan
                    ),
                    SatuanConversionOption(
                        displayLabel = "Slop (1 Slop = 6 Roll)",
                        shortLabel = "Slop (Isi 6 Roll)",
                        multiplier = 6,
                        baseSatuan = baseSatuan
                    )
                )
                val info = "• 1 Slop = 6 Roll"
                Pair(options, info)
            }

            // Barang dengan satuan dasar lainnya (Ball, Kg, Botol, Kilo, Buah, Ikat, Biji, dll.)
            else -> {
                val options = listOf(
                    SatuanConversionOption(
                        displayLabel = "$baseSatuan (Satuan — 1 $baseSatuan)",
                        shortLabel = baseSatuan,
                        multiplier = 1,
                        baseSatuan = baseSatuan
                    )
                )
                Pair(options, null)
            }
        }
    }

    /**
     * Menampilkan popup Detail Data Stok beserta tombol Pengajuan Permintaan.
     */
    private fun showStokDetailDialog(item: StokItem) {
        val view = LayoutInflater.from(this).inflate(R.layout.dialog_detail_data_stok, null)
        val dialog = AlertDialog.Builder(this)
            .setView(view)
            .create()

        dialog.window?.setBackgroundDrawable(ColorDrawable(Color.TRANSPARENT))

        val nomorUrut = item.urutan ?: item.id
        val kodeStok = String.format(Locale.US, "STK-%03d", nomorUrut)
        val jumlah = item.jumlah ?: 0
        val satuan = item.satuan?.trim()?.takeIf { it.isNotEmpty() && it != "-" } ?: "Unit"
        val ket = item.keterangan?.trim()?.takeIf { it.isNotEmpty() && it != "-" } ?: "Tidak ada catatan tambahan"
        val isTersedia = (item.tersedia == true) || (jumlah > 0)

        val tvKode = view.findViewById<TextView>(R.id.tvDetailKodeStok)
        val tvStatusBadge = view.findViewById<TextView>(R.id.tvDetailStatusBadge)
        val btnClose = view.findViewById<TextView>(R.id.btnCloseDetailStok)
        val tvNamaBarang = view.findViewById<TextView>(R.id.tvDetailNamaBarang)
        val tvSubTitle = view.findViewById<TextView>(R.id.tvDetailSubTitle)
        val cardDetailFoto = view.findViewById<CardView>(R.id.cardDetailFotoStok)
        val ivDetailFoto = view.findViewById<ImageView>(R.id.ivDetailFotoStok)
        val tvJumlahStok = view.findViewById<TextView>(R.id.tvDetailJumlahStok)
        val tvSatuanDasar = view.findViewById<TextView>(R.id.tvDetailSatuanDasar)
        val layoutInfoKonversi = view.findViewById<LinearLayout>(R.id.layoutDetailInfoKonversi)
        val tvInfoKonversi = view.findViewById<TextView>(R.id.tvDetailInfoKonversi)
        val tvKeterangan = view.findViewById<TextView>(R.id.tvDetailKeteranganStok)
        val btnPermintaan = view.findViewById<Button>(R.id.btnPengajuanPermintaanStok)
        val btnTutup = view.findViewById<Button>(R.id.btnTutupDetailStok)

        tvKode.text = kodeStok
        val namaBarangDisplay = item.namaBarang ?: "Barang Stok"
        tvNamaBarang.text = namaBarangDisplay
        tvSubTitle.text = "Kategori: Data Stok Persediaan • Nomor Urut #$nomorUrut"
        tvJumlahStok.text = "${numberFormat.format(jumlah)} $satuan"
        tvSatuanDasar.text = satuan
        tvKeterangan.text = ket

        // Foto Barang Stok (Klik untuk membuka popup preview ukuran besar)
        cardDetailFoto.visibility = View.GONE
        if (PhotoLoader.hasPhoto(item.fotoBarang, item.fotoUrl)) {
            val openPreview = View.OnClickListener {
                PhotoLoader.showImagePreviewPopup(
                    context = this,
                    title = namaBarangDisplay,
                    subtitle = "$kodeStok • Stok: ${numberFormat.format(jumlah)} $satuan",
                    fotoBarang = item.fotoBarang,
                    fotoUrl = item.fotoUrl
                )
            }
            PhotoLoader.loadInto(
                context = this,
                imageView = ivDetailFoto,
                fotoBarang = item.fotoBarang,
                fotoUrl = item.fotoUrl,
                maxDimPx = 800,
                onLoaded = {
                    cardDetailFoto.visibility = View.VISIBLE
                    cardDetailFoto.setOnClickListener(openPreview)
                    ivDetailFoto.setOnClickListener(openPreview)
                },
                onFailed = {
                    cardDetailFoto.visibility = View.GONE
                }
            )
        }

        if (isTersedia && jumlah > 0) {
            tvStatusBadge.text = "TERSEDIA"
            tvStatusBadge.setBackgroundResource(R.drawable.bg_badge_green)
            tvStatusBadge.setTextColor(Color.parseColor("#2E7D32"))
            tvJumlahStok.setTextColor(Color.parseColor("#1C4E8F"))
        } else {
            tvStatusBadge.text = "STOK HABIS"
            tvStatusBadge.setBackgroundResource(R.drawable.bg_badge_red)
            tvStatusBadge.setTextColor(Color.parseColor("#C62828"))
            tvJumlahStok.setTextColor(Color.parseColor("#C62828"))
        }

        val (_, infoKonversiText) = getConversionOptionsForItem(item)
        if (!infoKonversiText.isNullOrBlank()) {
            layoutInfoKonversi.visibility = View.VISIBLE
            tvInfoKonversi.text = infoKonversiText
        } else {
            layoutInfoKonversi.visibility = View.GONE
        }

        btnClose.setOnClickListener { dialog.dismiss() }
        btnTutup.setOnClickListener { dialog.dismiss() }

        btnPermintaan.setOnClickListener {
            if (jumlah <= 0) {
                Toast.makeText(
                    this,
                    "Stok \"${item.namaBarang}\" sedang kosong (0 $satuan), tidak dapat mengajukan peminjaman/permintaan.",
                    Toast.LENGTH_LONG
                ).show()
                return@setOnClickListener
            }
            dialog.dismiss()
            showFormPengajuanPermintaanStokDialog(item)
        }

        dialog.show()
        dialog.window?.setLayout(
            (resources.displayMetrics.widthPixels * 0.92).toInt(),
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
    }

    /**
     * Menampilkan Form Pengajuan Permintaan untuk item yang sudah ada di Data Stok:
     * - Nama pemohon otomatis terisi dari akun yang login
     * - Pilihan satuan dasar atau satuan konversi lebih besar (Slop / Dus)
     * - Validasi tidak bisa meminjam melebihi stok yang tersedia
     * - Textbox deskripsi / keterangan (opsional)
     */
    private fun showFormPengajuanPermintaanStokDialog(item: StokItem) {
        resolveActiveUserAccount()

        val view = LayoutInflater.from(this).inflate(R.layout.dialog_form_pengajuan_stok, null)
        val dialog = AlertDialog.Builder(this)
            .setView(view)
            .setCancelable(false)
            .create()

        dialog.window?.setBackgroundDrawable(ColorDrawable(Color.TRANSPARENT))
        dialog.window?.setSoftInputMode(WindowManager.LayoutParams.SOFT_INPUT_ADJUST_RESIZE)

        val nomorUrut = item.urutan ?: item.id
        val kodeStok = String.format(Locale.US, "STK-%03d", nomorUrut)
        val stokTersedia = maxOf(0, item.jumlah ?: 0)
        val baseSatuan = item.satuan?.trim()?.takeIf { it.isNotEmpty() && it != "-" } ?: "Unit"

        val tvBadgeJenis = view.findViewById<TextView>(R.id.tvBadgeJenisFormStok)
        val tvTitleForm = view.findViewById<TextView>(R.id.tvTitleFormStok)
        val btnClose = view.findViewById<TextView>(R.id.btnCloseFormStok)
        val tvNamaBarang = view.findViewById<TextView>(R.id.tvFormStokNamaBarang)
        val tvKode = view.findViewById<TextView>(R.id.tvFormStokKode)
        val tvTersediaBadge = view.findViewById<TextView>(R.id.tvFormStokTersediaBadge)
        val tvKonversiGuide = view.findViewById<TextView>(R.id.tvFormStokKonversiGuide)

        val etNamaPemohon = view.findViewById<EditText>(R.id.etFormStokNamaPemohon)
        val spinnerSatuan = view.findViewById<Spinner>(R.id.spinnerSatuanStok)
        val tvLabelJumlah = view.findViewById<TextView>(R.id.tvLabelJumlahStok)
        val btnMinus = view.findViewById<Button>(R.id.btnMinusJumlahStok)
        val etJumlah = view.findViewById<EditText>(R.id.etJumlahPinjamStok)
        val btnPlus = view.findViewById<Button>(R.id.btnPlusJumlahStok)
        val boxValidasi = view.findViewById<LinearLayout>(R.id.boxValidasiStok)
        val tvValidasiInfo = view.findViewById<TextView>(R.id.tvValidasiStokInfo)
        val layoutUploadFile = view.findViewById<LinearLayout>(R.id.layoutUploadFileStok)
        val tvSelectedFileName = view.findViewById<TextView>(R.id.tvSelectedFileNameStok)
        val tvSelectedFileInfo = view.findViewById<TextView>(R.id.tvSelectedFileInfoStok)
        val btnPilihFile = view.findViewById<Button>(R.id.btnPilihFileStok)
        val layoutFileActions = view.findViewById<LinearLayout>(R.id.layoutFileActionsStok)
        val btnLihatFile = view.findViewById<Button>(R.id.btnLihatFileStok)
        val btnHapusFile = view.findViewById<Button>(R.id.btnHapusFileStok)
        val etDeskripsi = view.findViewById<EditText>(R.id.etDeskripsiStok)
        val btnBatal = view.findViewById<Button>(R.id.btnBatalFormStok)
        val btnSubmit = view.findViewById<Button>(R.id.btnSubmitFormStok)

        var selectedAttachment: SelectedAttachment? = null
        var isSubmitting = false

        val applySelectedFileUi: (SelectedAttachment?) -> Unit = { att ->
            selectedAttachment = att
            if (att != null) {
                tvSelectedFileName.text = att.fileName
                tvSelectedFileInfo.text = "Ukuran: ${att.fileSizeStr} • Siap dilampirkan"
                tvSelectedFileInfo.setTextColor(Color.parseColor("#2E7D32"))
                btnPilihFile.text = "Ganti File"
                layoutFileActions.visibility = View.VISIBLE
            } else {
                tvSelectedFileName.text = "Belum ada file dipilih (Opsional)"
                tvSelectedFileInfo.text = "Mendukung semua jenis file (Foto, PDF, Dokumen, Excel, dll)"
                tvSelectedFileInfo.setTextColor(Color.parseColor("#64748B"))
                btnPilihFile.text = "Pilih File"
                layoutFileActions.visibility = View.GONE
            }
        }

        val triggerPickFile = {
            if (!isSubmitting) {
                onFilePickedForDialog = { att ->
                    applySelectedFileUi(att)
                }
                pickAnyFileLauncher.launch(arrayOf("*/*"))
            }
        }
        btnPilihFile.setOnClickListener { triggerPickFile() }
        layoutUploadFile.setOnClickListener { triggerPickFile() }
        btnLihatFile.setOnClickListener { openSelectedFilePreview(selectedAttachment) }
        btnHapusFile.setOnClickListener {
            if (!isSubmitting) {
                applySelectedFileUi(null)
            }
        }

        tvBadgeJenis.text = "PENGAJUAN PERMINTAAN (DATA STOK)"
        tvTitleForm.text = "Form Pengajuan Permintaan"
        tvLabelJumlah.text = "Jumlah Stok yang Ingin Dipinjam / Diminta"
        btnSubmit.text = "Ajukan Permintaan"
        btnSubmit.backgroundTintList = android.content.res.ColorStateList.valueOf(Color.parseColor("#1C4E8F"))

        tvNamaBarang.text = item.namaBarang ?: "Barang Stok"
        tvKode.text = "Kode Stok: $kodeStok"
        tvTersediaBadge.text = "Tersedia: ${numberFormat.format(stokTersedia)} $baseSatuan"
        if (stokTersedia > 0) {
            tvTersediaBadge.setBackgroundResource(R.drawable.bg_badge_green)
            tvTersediaBadge.setTextColor(Color.parseColor("#2E7D32"))
        } else {
            tvTersediaBadge.setBackgroundResource(R.drawable.bg_badge_red)
            tvTersediaBadge.setTextColor(Color.parseColor("#C62828"))
        }

        // 1. Nama otomatis terisi dari akun user yang login
        etNamaPemohon.setText(currentUserName)

        // 2. Opsi satuan (Satuan dasar + Satuan konversi lebih besar sesuai aturan foto)
        val (conversionOptions, infoKonversiText) = getConversionOptionsForItem(item)
        if (!infoKonversiText.isNullOrBlank()) {
            tvKonversiGuide.visibility = View.VISIBLE
            tvKonversiGuide.text = "ℹ️ Konversi Satuan:\n$infoKonversiText"
        } else {
            tvKonversiGuide.visibility = View.GONE
        }

        val spinnerAdapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            conversionOptions
        ).also {
            it.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        }
        spinnerSatuan.adapter = spinnerAdapter

        val validateAndRefreshPreview = {
            val selectedOpt = (spinnerSatuan.selectedItem as? SatuanConversionOption)
                ?: conversionOptions.first()
            val rawInput = etJumlah.text?.toString()?.trim() ?: ""
            val inputQty = rawInput.toIntOrNull() ?: 0
            val totalBaseQty = inputQty.toLong() * selectedOpt.multiplier.toLong()
            val maxInSelectedUnit = if (selectedOpt.multiplier > 0) stokTersedia / selectedOpt.multiplier else 0

            if (inputQty <= 0) {
                boxValidasi.setBackgroundResource(R.drawable.bg_badge_orange)
                tvValidasiInfo.setTextColor(Color.parseColor("#E65100"))
                tvValidasiInfo.text = "⚠ Masukkan jumlah minimal 1 ${selectedOpt.shortLabel}."
                btnSubmit.isEnabled = false
                btnSubmit.alpha = 0.5f
            } else if (stokTersedia > 0 && totalBaseQty > stokTersedia) {
                boxValidasi.setBackgroundResource(R.drawable.bg_badge_red)
                tvValidasiInfo.setTextColor(Color.parseColor("#C62828"))
                val detailKonversi = if (selectedOpt.multiplier > 1) {
                    "$inputQty ${selectedOpt.shortLabel} = ${numberFormat.format(totalBaseQty)} ${selectedOpt.baseSatuan}"
                } else {
                    "${numberFormat.format(totalBaseQty)} ${selectedOpt.baseSatuan}"
                }
                val saranMax = if (maxInSelectedUnit > 0) {
                    "Maksimal yang bisa dipinjam untuk satuan ini: ${numberFormat.format(maxInSelectedUnit)} ${selectedOpt.shortLabel} (${numberFormat.format(maxInSelectedUnit * selectedOpt.multiplier)} ${selectedOpt.baseSatuan})."
                } else {
                    "Stok saat ini (${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan}) tidak cukup untuk 1 ${selectedOpt.shortLabel} (${selectedOpt.multiplier} ${selectedOpt.baseSatuan}). Silakan pilih satuan ${selectedOpt.baseSatuan}."
                }
                tvValidasiInfo.text = "❌ Tidak bisa meminjam lebih dari stok tersedia!\nDiminta: $detailKonversi, sedangkan stok tersedia hanya ${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan}.\n$saranMax"
                etJumlah.error = "Melebihi stok tersedia (${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan})"
                btnSubmit.isEnabled = false
                btnSubmit.alpha = 0.5f
            } else if (stokTersedia <= 0) {
                boxValidasi.setBackgroundResource(R.drawable.bg_badge_red)
                tvValidasiInfo.setTextColor(Color.parseColor("#C62828"))
                tvValidasiInfo.text = "❌ Stok barang ini sedang habis (0 ${selectedOpt.baseSatuan})."
                btnSubmit.isEnabled = false
                btnSubmit.alpha = 0.5f
            } else {
                etJumlah.error = null
                boxValidasi.setBackgroundResource(R.drawable.bg_badge_green)
                tvValidasiInfo.setTextColor(Color.parseColor("#2E7D32"))
                val sisaStok = maxOf(0L, stokTersedia.toLong() - totalBaseQty)
                if (selectedOpt.multiplier > 1) {
                    tvValidasiInfo.text = "✓ Konversi: ${numberFormat.format(inputQty)} ${selectedOpt.shortLabel} = ${numberFormat.format(totalBaseQty)} ${selectedOpt.baseSatuan}\nSisa stok jika disetujui: ${numberFormat.format(sisaStok)} ${selectedOpt.baseSatuan} (dari ${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan})"
                } else {
                    tvValidasiInfo.text = "✓ Total dipinjam: ${numberFormat.format(totalBaseQty)} ${selectedOpt.baseSatuan}\nSisa stok jika disetujui: ${numberFormat.format(sisaStok)} ${selectedOpt.baseSatuan} (dari ${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan})"
                }
                btnSubmit.isEnabled = !isSubmitting
                btnSubmit.alpha = if (isSubmitting) 0.6f else 1.0f
            }
        }

        spinnerSatuan.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, v: View?, position: Int, id: Long) {
                validateAndRefreshPreview()
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {
                validateAndRefreshPreview()
            }
        }

        etJumlah.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                validateAndRefreshPreview()
            }
            override fun afterTextChanged(s: Editable?) {}
        })

        btnMinus.setOnClickListener {
            if (isSubmitting) return@setOnClickListener
            val current = etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 1
            val next = maxOf(1, current - 1)
            etJumlah.setText(next.toString())
            etJumlah.setSelection(etJumlah.text.length)
        }

        btnPlus.setOnClickListener {
            if (isSubmitting) return@setOnClickListener
            val selectedOpt = (spinnerSatuan.selectedItem as? SatuanConversionOption)
                ?: conversionOptions.first()
            val current = etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 0
            val next = current + 1
            val nextBaseQty = next.toLong() * selectedOpt.multiplier.toLong()
            if (stokTersedia > 0 && nextBaseQty > stokTersedia) {
                Toast.makeText(
                    this,
                    "Tidak dapat melebihi stok tersedia (${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan})!",
                    Toast.LENGTH_SHORT
                ).show()
                return@setOnClickListener
            }
            etJumlah.setText(next.toString())
            etJumlah.setSelection(etJumlah.text.length)
        }

        btnClose.setOnClickListener {
            if (!isSubmitting) dialog.dismiss()
        }
        btnBatal.setOnClickListener {
            if (!isSubmitting) dialog.dismiss()
        }

        btnSubmit.setOnClickListener {
            if (isSubmitting) return@setOnClickListener

            val selectedOpt = (spinnerSatuan.selectedItem as? SatuanConversionOption)
                ?: conversionOptions.first()
            val inputQty = etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 0
            val totalBaseQtyLong = inputQty.toLong() * selectedOpt.multiplier.toLong()

            if (inputQty <= 0) {
                etJumlah.error = "Masukkan jumlah minimal 1"
                etJumlah.requestFocus()
                return@setOnClickListener
            }

            if (stokTersedia > 0 && totalBaseQtyLong > stokTersedia) {
                etJumlah.error = "Melebihi stok tersedia (${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan})"
                Toast.makeText(
                    this,
                    "Jumlah yang dipinjam (${numberFormat.format(totalBaseQtyLong)} ${selectedOpt.baseSatuan}) melebihi stok tersedia (${numberFormat.format(stokTersedia)} ${selectedOpt.baseSatuan})!",
                    Toast.LENGTH_LONG
                ).show()
                return@setOnClickListener
            }

            val totalBaseQty = totalBaseQtyLong.toInt()
            val rawDeskripsi = etDeskripsi.text?.toString()?.trim() ?: ""
            val jenisApi = "permintaan"

            val keteranganFinal = buildString {
                if (selectedOpt.multiplier > 1) {
                    append("[Pilihan Satuan: $inputQty ${selectedOpt.shortLabel} = $totalBaseQty ${selectedOpt.baseSatuan}]")
                }
                if (rawDeskripsi.isNotEmpty()) {
                    if (isNotEmpty()) append(" — ")
                    append(rawDeskripsi)
                }
                if (isEmpty()) {
                    append("Permintaan peminjaman stok sebanyak $totalBaseQty ${selectedOpt.baseSatuan} oleh $currentUserName")
                }
            }

            isSubmitting = true
            btnSubmit.isEnabled = false
            btnSubmit.text = "Mengirim..."
            btnBatal.isEnabled = false
            spinnerSatuan.isEnabled = false
            etJumlah.isEnabled = false
            btnPilihFile.isEnabled = false
            btnHapusFile.isEnabled = false
            etDeskripsi.isEnabled = false

            val requestBody = PengajuanRequest(
                userId = if (currentUserId > 0) currentUserId else null,
                namaUser = currentUserName,
                pemohon = currentUserName,
                asetId = null,
                dataStokId = item.id,
                kodeAset = kodeStok,
                namaAset = item.namaBarang ?: "Barang Stok",
                kategori = "Data Stok",
                kategoriNama = "Data Stok",
                unitJumlah = totalBaseQty,
                satuan = selectedOpt.baseSatuan,
                nilaiEstimasi = null,
                lokasi = "Gudang Data Stok",
                cabangAsal = "Pusat",
                ruanganAsal = "Gudang Stok",
                cabangTujuan = "Pusat",
                ruanganTujuan = "Gudang Stok",
                jenis = jenisApi,
                jenisPengajuan = jenisApi,
                keterangan = keteranganFinal,
                fileName = selectedAttachment?.fileName,
                fileBase64 = selectedAttachment?.base64Data
            )

            ApiClient.instance.submitPengajuan(requestBody).enqueue(object : Callback<PengajuanSubmitResponse> {
                override fun onResponse(
                    call: Call<PengajuanSubmitResponse>,
                    response: Response<PengajuanSubmitResponse>
                ) {
                    isSubmitting = false
                    val body = response.body()
                    val isOk = response.isSuccessful && body != null && (body.status == "success" || body.success == true)

                    if (isOk) {
                        val savedItem = PengajuanLocalStore.saveSubmission(
                            context = this@DataStokActivity,
                            serverItem = body.data,
                            userId = if (currentUserId > 0) currentUserId else null,
                            userName = currentUserName,
                            dataStokId = item.id,
                            kodeAset = kodeStok,
                            namaAset = item.namaBarang ?: "Barang Stok",
                            kategori = "Data Stok",
                            unitJumlah = totalBaseQty,
                            satuan = selectedOpt.baseSatuan,
                            nilaiEstimasi = null,
                            lokasi = "Gudang Data Stok",
                            cabangAsal = "Pusat",
                            ruanganAsal = "Gudang Stok",
                            cabangTujuan = "Pusat",
                            ruanganTujuan = "Gudang Stok",
                            jenisPengajuan = jenisApi,
                            keterangan = keteranganFinal,
                            fileName = selectedAttachment?.fileName,
                            localFileUri = selectedAttachment?.uri?.toString()
                        )

                        dialog.dismiss()
                        loadDataStok(showLoadingIndicator = false)

                        val infoSatuanPinjam = if (selectedOpt.multiplier > 1) {
                            "$inputQty ${selectedOpt.shortLabel} (setara ${numberFormat.format(totalBaseQty)} ${selectedOpt.baseSatuan})"
                        } else {
                            "${numberFormat.format(totalBaseQty)} ${selectedOpt.baseSatuan}"
                        }

                        AlertDialog.Builder(this@DataStokActivity)
                            .setTitle("✅ Pengajuan Permintaan Terkirim")
                            .setMessage(
                                "Nomor Pengajuan: ${savedItem.nomorPengajuan ?: "-"}\n" +
                                "Jenis: Pengajuan Permintaan (Data Stok)\n" +
                                "Pemohon: $currentUserName\n" +
                                "Barang: ${item.namaBarang ?: "-"} ($kodeStok)\n" +
                                "Jumlah: $infoSatuanPinjam\n" +
                                "Lampiran File: ${selectedAttachment?.fileName ?: "Tidak ada (Opsional)"}\n" +
                                "Keterangan: ${rawDeskripsi.ifEmpty { "-" }}\n\n" +
                                "Pengajuan ini telah masuk ke halaman Pengajuan Admin di Web dan menunggu persetujuan (ACC)."
                            )
                            .setPositiveButton("Lihat Riwayat") { _, _ ->
                                startActivity(Intent(this@DataStokActivity, HistoryActivity::class.java).apply {
                                    putExtra("USER_NAME", currentUserName)
                                    putExtra("USER_EMAIL", currentUserEmail)
                                    putExtra("USER_ROLE", currentUserRole)
                                    putExtra("USER_ID", currentUserId)
                                })
                            }
                            .setNegativeButton("Selesai", null)
                            .show()
                    } else {
                        btnSubmit.isEnabled = true
                        btnSubmit.text = "Ajukan Permintaan"
                        btnBatal.isEnabled = true
                        spinnerSatuan.isEnabled = true
                        etJumlah.isEnabled = true
                        btnPilihFile.isEnabled = true
                        btnHapusFile.isEnabled = true
                        etDeskripsi.isEnabled = true

                        val fallback = "Gagal mengirim pengajuan stok (HTTP ${response.code()})."
                        val errMsg = body?.message?.takeIf { it.isNotBlank() }
                            ?: ApiClient.extractErrorMessage(response, fallback)
                        Toast.makeText(this@DataStokActivity, "❌ $errMsg", Toast.LENGTH_LONG).show()
                    }
                }

                override fun onFailure(call: Call<PengajuanSubmitResponse>, t: Throwable) {
                    isSubmitting = false
                    btnSubmit.isEnabled = true
                    btnSubmit.text = "Ajukan Permintaan"
                    btnBatal.isEnabled = true
                    spinnerSatuan.isEnabled = true
                    etJumlah.isEnabled = true
                    btnPilihFile.isEnabled = true
                    btnHapusFile.isEnabled = true
                    etDeskripsi.isEnabled = true

                    Toast.makeText(
                        this@DataStokActivity,
                        "❌ Koneksi ke server gagal: ${t.localizedMessage}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            })
        }

        validateAndRefreshPreview()
        dialog.show()
        dialog.window?.setLayout(
            (resources.displayMetrics.widthPixels * 0.94).toInt(),
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
    }

    /**
     * Menampilkan Form Pengajuan Lainnya (diletakkan di luar Detail Stok):
     * Digunakan untuk mengajukan barang baru / barang yang belum ada di Data Stok,
     * lengkap dengan input Nama Barang, Jumlah, Satuan, Harga (Rp, opsional), dan Deskripsi (opsional).
     */
    private fun showFormPengajuanLainnyaStokDialog() {
        resolveActiveUserAccount()

        val view = LayoutInflater.from(this).inflate(R.layout.dialog_form_pengajuan_lainnya_stok, null)
        val dialog = AlertDialog.Builder(this)
            .setView(view)
            .setCancelable(false)
            .create()

        dialog.window?.setBackgroundDrawable(ColorDrawable(Color.TRANSPARENT))
        dialog.window?.setSoftInputMode(WindowManager.LayoutParams.SOFT_INPUT_ADJUST_RESIZE)

        val btnClose = view.findViewById<TextView>(R.id.btnCloseFormLainnyaStok)
        val etNamaPemohon = view.findViewById<EditText>(R.id.etLainnyaNamaPemohon)
        val etNamaBarang = view.findViewById<EditText>(R.id.etLainnyaNamaBarang)
        val btnMinus = view.findViewById<Button>(R.id.btnMinusLainnyaJumlah)
        val etJumlah = view.findViewById<EditText>(R.id.etLainnyaJumlah)
        val btnPlus = view.findViewById<Button>(R.id.btnPlusLainnyaJumlah)
        val spinnerSatuan = view.findViewById<Spinner>(R.id.spinnerLainnyaSatuan)
        val etHarga = view.findViewById<EditText>(R.id.etLainnyaHarga)
        val tvFormatHarga = view.findViewById<TextView>(R.id.tvLainnyaFormatHarga)
        val layoutUploadFile = view.findViewById<LinearLayout>(R.id.layoutUploadFileLainnya)
        val tvSelectedFileName = view.findViewById<TextView>(R.id.tvSelectedFileNameLainnya)
        val tvSelectedFileInfo = view.findViewById<TextView>(R.id.tvSelectedFileInfoLainnya)
        val btnPilihFile = view.findViewById<Button>(R.id.btnPilihFileLainnya)
        val layoutFileActions = view.findViewById<LinearLayout>(R.id.layoutFileActionsLainnya)
        val btnLihatFile = view.findViewById<Button>(R.id.btnLihatFileLainnya)
        val btnHapusFile = view.findViewById<Button>(R.id.btnHapusFileLainnya)
        val etDeskripsi = view.findViewById<EditText>(R.id.etLainnyaDeskripsi)
        val btnBatal = view.findViewById<Button>(R.id.btnBatalLainnyaStok)
        val btnSubmit = view.findViewById<Button>(R.id.btnSubmitLainnyaStok)

        var selectedAttachment: SelectedAttachment? = null
        var isSubmitting = false

        val applySelectedFileUi: (SelectedAttachment?) -> Unit = { att ->
            selectedAttachment = att
            if (att != null) {
                tvSelectedFileName.text = att.fileName
                tvSelectedFileInfo.text = "Ukuran: ${att.fileSizeStr} • Siap dilampirkan"
                tvSelectedFileInfo.setTextColor(Color.parseColor("#047857"))
                btnPilihFile.text = "Ganti File"
                layoutFileActions.visibility = View.VISIBLE
            } else {
                tvSelectedFileName.text = "Belum ada file dipilih (Opsional)"
                tvSelectedFileInfo.text = "Mendukung semua jenis file (Foto, PDF, Dokumen, Excel, dll)"
                tvSelectedFileInfo.setTextColor(Color.parseColor("#64748B"))
                btnPilihFile.text = "Pilih File"
                layoutFileActions.visibility = View.GONE
            }
        }

        val triggerPickFile = {
            if (!isSubmitting) {
                onFilePickedForDialog = { att ->
                    applySelectedFileUi(att)
                }
                pickAnyFileLauncher.launch(arrayOf("*/*"))
            }
        }
        btnPilihFile.setOnClickListener { triggerPickFile() }
        layoutUploadFile.setOnClickListener { triggerPickFile() }
        btnLihatFile.setOnClickListener { openSelectedFilePreview(selectedAttachment) }
        btnHapusFile.setOnClickListener {
            if (!isSubmitting) {
                applySelectedFileUi(null)
            }
        }

        // 1. Nama otomatis terisi dari akun yang login
        etNamaPemohon.setText(currentUserName)

        // 2. Pilihan satuan barang
        val daftarSatuan = listOf(
            "Pcs",
            "Buah",
            "Unit",
            "Roll",
            "Slop",
            "Dus",
            "Rim",
            "Pack",
            "Ball",
            "Botol",
            "Kg",
            "Kilo",
            "Ikat",
            "Biji",
            "Set"
        )
        val satuanAdapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            daftarSatuan
        ).also {
            it.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        }
        spinnerSatuan.adapter = satuanAdapter

        val updatePreviewHarga = {
            val rawHarga = etHarga.text?.toString()?.replace("[^0-9]".toRegex(), "") ?: ""
            val hargaVal = rawHarga.toLongOrNull() ?: 0L
            val qtyVal = (etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 1).coerceAtLeast(1)
            if (hargaVal > 0L) {
                tvFormatHarga.text = "Nominal Harga: Rp ${numberFormat.format(hargaVal)} (Untuk $qtyVal ${spinnerSatuan.selectedItem ?: "Unit"})"
                tvFormatHarga.setTextColor(Color.parseColor("#047857"))
            } else {
                tvFormatHarga.text = "Opsional • Kosongkan jika harga belum diketahui"
                tvFormatHarga.setTextColor(Color.parseColor("#64748B"))
            }
        }

        etHarga.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                updatePreviewHarga()
            }
            override fun afterTextChanged(s: Editable?) {}
        })

        etJumlah.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                updatePreviewHarga()
            }
            override fun afterTextChanged(s: Editable?) {}
        })

        spinnerSatuan.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, v: View?, position: Int, id: Long) {
                updatePreviewHarga()
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {
                updatePreviewHarga()
            }
        }

        btnMinus.setOnClickListener {
            if (isSubmitting) return@setOnClickListener
            val current = etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 1
            val next = maxOf(1, current - 1)
            etJumlah.setText(next.toString())
            etJumlah.setSelection(etJumlah.text.length)
        }

        btnPlus.setOnClickListener {
            if (isSubmitting) return@setOnClickListener
            val current = etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 0
            val next = current + 1
            etJumlah.setText(next.toString())
            etJumlah.setSelection(etJumlah.text.length)
        }

        btnClose.setOnClickListener {
            if (!isSubmitting) dialog.dismiss()
        }
        btnBatal.setOnClickListener {
            if (!isSubmitting) dialog.dismiss()
        }

        btnSubmit.setOnClickListener {
            if (isSubmitting) return@setOnClickListener

            val inputNamaBarang = etNamaBarang.text?.toString()?.trim() ?: ""
            if (inputNamaBarang.isEmpty()) {
                etNamaBarang.error = "Nama barang wajib diisi"
                etNamaBarang.requestFocus()
                Toast.makeText(this, "Silakan isi nama barang yang ingin diajukan!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val inputQty = etJumlah.text?.toString()?.trim()?.toIntOrNull() ?: 0
            if (inputQty <= 0) {
                etJumlah.error = "Minimal 1"
                etJumlah.requestFocus()
                Toast.makeText(this, "Jumlah barang minimal 1!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val rawHarga = etHarga.text?.toString()?.replace("[^0-9]".toRegex(), "") ?: ""
            val inputHargaLong = rawHarga.toLongOrNull() ?: 0L
            val nilaiEstimasiValue: Double? = if (inputHargaLong > 0L) inputHargaLong.toDouble() else null

            val selectedSatuan = spinnerSatuan.selectedItem?.toString()?.trim()?.takeIf { it.isNotEmpty() } ?: "Unit"
            val rawDeskripsi = etDeskripsi.text?.toString()?.trim() ?: ""
            val hargaFormatted = if (inputHargaLong > 0L) "Rp ${numberFormat.format(inputHargaLong)}" else null

            val keteranganFinal = buildString {
                if (hargaFormatted != null) {
                    append("[Barang Baru di Luar Stok • Harga: $hargaFormatted]")
                } else {
                    append("[Barang Baru di Luar Stok]")
                }
                if (rawDeskripsi.isNotEmpty()) {
                    append(" — ")
                    append(rawDeskripsi)
                }
            }

            val kodeStokBaru = "STK-BARU"
            val jenisApi = "lainnya_stok"

            isSubmitting = true
            btnSubmit.isEnabled = false
            btnSubmit.text = "Mengirim..."
            btnBatal.isEnabled = false
            etNamaBarang.isEnabled = false
            etJumlah.isEnabled = false
            spinnerSatuan.isEnabled = false
            etHarga.isEnabled = false
            btnPilihFile.isEnabled = false
            btnHapusFile.isEnabled = false
            etDeskripsi.isEnabled = false

            val requestBody = PengajuanRequest(
                userId = if (currentUserId > 0) currentUserId else null,
                namaUser = currentUserName,
                pemohon = currentUserName,
                asetId = null,
                dataStokId = null,
                kodeAset = kodeStokBaru,
                namaAset = inputNamaBarang,
                kategori = "Data Stok (Barang Baru)",
                kategoriNama = "Data Stok (Barang Baru)",
                unitJumlah = inputQty,
                satuan = selectedSatuan,
                nilaiEstimasi = nilaiEstimasiValue,
                lokasi = "Pengajuan Barang Baru",
                cabangAsal = "Pusat",
                ruanganAsal = "Gudang Stok",
                cabangTujuan = "Pusat",
                ruanganTujuan = "Gudang Stok",
                jenis = jenisApi,
                jenisPengajuan = jenisApi,
                keterangan = keteranganFinal,
                fileName = selectedAttachment?.fileName,
                fileBase64 = selectedAttachment?.base64Data
            )

            ApiClient.instance.submitPengajuan(requestBody).enqueue(object : Callback<PengajuanSubmitResponse> {
                override fun onResponse(
                    call: Call<PengajuanSubmitResponse>,
                    response: Response<PengajuanSubmitResponse>
                ) {
                    isSubmitting = false
                    val body = response.body()
                    val isOk = response.isSuccessful && body != null && (body.status == "success" || body.success == true)

                    if (isOk) {
                        val savedItem = PengajuanLocalStore.saveSubmission(
                            context = this@DataStokActivity,
                            serverItem = body.data,
                            userId = if (currentUserId > 0) currentUserId else null,
                            userName = currentUserName,
                            dataStokId = null,
                            kodeAset = kodeStokBaru,
                            namaAset = inputNamaBarang,
                            kategori = "Data Stok (Barang Baru)",
                            unitJumlah = inputQty,
                            satuan = selectedSatuan,
                            nilaiEstimasi = nilaiEstimasiValue,
                            lokasi = "Pengajuan Barang Baru",
                            cabangAsal = "Pusat",
                            ruanganAsal = "Gudang Stok",
                            cabangTujuan = "Pusat",
                            ruanganTujuan = "Gudang Stok",
                            jenisPengajuan = jenisApi,
                            keterangan = keteranganFinal,
                            fileName = selectedAttachment?.fileName,
                            localFileUri = selectedAttachment?.uri?.toString()
                        )

                        dialog.dismiss()
                        loadDataStok(showLoadingIndicator = false)

                        AlertDialog.Builder(this@DataStokActivity)
                            .setTitle("✅ Pengajuan Lainnya Terkirim")
                            .setMessage(
                                "Nomor Pengajuan: ${savedItem.nomorPengajuan ?: "-"}\n" +
                                "Jenis: Pengajuan Lainnya (Data Stok)\n" +
                                "Pemohon: $currentUserName\n" +
                                "Nama Barang: $inputNamaBarang\n" +
                                "Jumlah: ${numberFormat.format(inputQty)} $selectedSatuan\n" +
                                "Harga: ${hargaFormatted ?: "- (Opsional)"}\n" +
                                "Lampiran File: ${selectedAttachment?.fileName ?: "Tidak ada (Opsional)"}\n" +
                                "Keterangan: ${rawDeskripsi.ifEmpty { "-" }}\n\n" +
                                "Pengajuan ini telah masuk ke halaman Pengajuan Admin di Web (tab Pengajuan Lainnya Data Stok)."
                            )
                            .setPositiveButton("Lihat Riwayat") { _, _ ->
                                startActivity(Intent(this@DataStokActivity, HistoryActivity::class.java).apply {
                                    putExtra("USER_NAME", currentUserName)
                                    putExtra("USER_EMAIL", currentUserEmail)
                                    putExtra("USER_ROLE", currentUserRole)
                                    putExtra("USER_ID", currentUserId)
                                })
                            }
                            .setNegativeButton("Selesai", null)
                            .show()
                    } else {
                        btnSubmit.isEnabled = true
                        btnSubmit.text = "Ajukan Lainnya"
                        btnBatal.isEnabled = true
                        etNamaBarang.isEnabled = true
                        etJumlah.isEnabled = true
                        spinnerSatuan.isEnabled = true
                        etHarga.isEnabled = true
                        btnPilihFile.isEnabled = true
                        btnHapusFile.isEnabled = true
                        etDeskripsi.isEnabled = true

                        val fallback = "Gagal mengirim pengajuan lainnya (HTTP ${response.code()})."
                        val errMsg = body?.message?.takeIf { it.isNotBlank() }
                            ?: ApiClient.extractErrorMessage(response, fallback)
                        Toast.makeText(this@DataStokActivity, "❌ $errMsg", Toast.LENGTH_LONG).show()
                    }
                }

                override fun onFailure(call: Call<PengajuanSubmitResponse>, t: Throwable) {
                    isSubmitting = false
                    btnSubmit.isEnabled = true
                    btnSubmit.text = "Ajukan Lainnya"
                    btnBatal.isEnabled = true
                    etNamaBarang.isEnabled = true
                    etJumlah.isEnabled = true
                    spinnerSatuan.isEnabled = true
                    etHarga.isEnabled = true
                    btnPilihFile.isEnabled = true
                    btnHapusFile.isEnabled = true
                    etDeskripsi.isEnabled = true

                    Toast.makeText(
                        this@DataStokActivity,
                        "❌ Koneksi ke server gagal: ${t.localizedMessage}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            })
        }

        updatePreviewHarga()
        dialog.show()
        dialog.window?.setLayout(
            (resources.displayMetrics.widthPixels * 0.94).toInt(),
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
    }

    private fun openSelectedFilePreview(attachment: SelectedAttachment?) {
        if (attachment == null) {
            Toast.makeText(this, "Belum ada file yang dipilih", Toast.LENGTH_SHORT).show()
            return
        }

        val uri = attachment.uri
        val mime = attachment.mimeType.ifBlank { contentResolver.getType(uri) ?: "*/*" }

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
                            .setTitle(attachment.fileName)
                            .setView(iv)
                            .setPositiveButton("Tutup", null)
                            .setNeutralButton("Buka di Aplikasi Lain") { _, _ ->
                                launchExternalViewer(uri, mime, attachment.fileName)
                            }
                            .show()
                        return
                    }
                }
            } catch (_: Exception) {}
        }

        launchExternalViewer(uri, mime, attachment.fileName)
    }

    private fun launchExternalViewer(uri: Uri, mime: String, fileName: String) {
        try {
            val viewIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, mime)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            startActivity(Intent.createChooser(viewIntent, "Lihat File: $fileName"))
        } catch (_: Exception) {
            Toast.makeText(
                this,
                "File \"$fileName\" siap dilampirkan (Tidak ada aplikasi pembuka format ini di perangkat).",
                Toast.LENGTH_LONG
            ).show()
        }
    }

    private fun ensureExtension(name: String, mime: String): String {
        val currentExt = name.substringAfterLast('.', "")
        if (name.contains('.') && currentExt.isNotBlank() && currentExt.length <= 6) {
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

    private fun readFileAsBase64(uri: Uri): String? {
        return try {
            contentResolver.openInputStream(uri)?.use { input ->
                val bytes = input.readBytes()
                if (bytes.size > 10 * 1024 * 1024) {
                    Toast.makeText(this, "Ukuran file terlalu besar (Maks 10 MB)", Toast.LENGTH_SHORT).show()
                    null
                } else {
                    Base64.encodeToString(bytes, Base64.NO_WRAP)
                }
            }
        } catch (_: Exception) {
            Toast.makeText(this, "Gagal membaca file yang dipilih", Toast.LENGTH_SHORT).show()
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
