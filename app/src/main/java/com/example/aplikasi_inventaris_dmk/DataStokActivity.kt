package com.example.aplikasi_inventaris_dmk

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
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.example.aplikasi_inventaris_dmk.network.ApiClient
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

    private lateinit var adapter: DataStokAdapter

    private var masterStokList: List<StokItem> = emptyList()
    private var currentFilter: FilterStatus = FilterStatus.SEMUA
    private var currentQuery: String = ""

    private val numberFormat = NumberFormat.getNumberInstance(Locale.Builder().setLanguage("id").setRegion("ID").build())
    private val timeFormat = SimpleDateFormat("HH:mm:ss", Locale.getDefault())

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_data_stok)

        initViews()
        setupRecyclerView()
        setupListeners()
        loadDataStok(showLoadingIndicator = true)
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

        // 1. Filter status
        filtered = when (currentFilter) {
            FilterStatus.SEMUA -> filtered
            FilterStatus.TERSEDIA -> filtered.filter { (it.tersedia == true) || ((it.jumlah ?: 0) > 0) }
            FilterStatus.HABIS -> filtered.filter { (it.tersedia == false) || ((it.jumlah ?: 0) <= 0) }
        }

        // 2. Filter search query
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

    private fun showStokDetailDialog(item: StokItem) {
        val jumlah = item.jumlah ?: 0
        val satuan = item.satuan?.takeIf { it.isNotBlank() && it != "-" } ?: "-"
        val ket = item.keterangan?.takeIf { it.isNotBlank() && it != "-" } ?: "-"
        val isTersedia = (item.tersedia == true) || (jumlah > 0)

        val statusText = if (isTersedia) "Tersedia (${numberFormat.format(jumlah)} $satuan)" else "Stok Kosong / Habis"

        val message = StringBuilder().apply {
            append("• Nomor Urut: #${item.urutan ?: item.id}\n")
            append("• Nama Barang: ${item.namaBarang ?: "-"}\n")
            append("• Jumlah Stok: ${numberFormat.format(jumlah)} $satuan\n")
            append("• Status Ketersediaan: $statusText\n")
            append("• Keterangan / Catatan: $ket\n\n")
            append("Data ini disinkronkan langsung dari tabel Data Stok di sistem web Inventaris DMK.")
        }.toString()

        AlertDialog.Builder(this)
            .setTitle(item.namaBarang ?: "Detail Stok Barang")
            .setMessage(message)
            .setPositiveButton("Tutup", null)
            .show()
    }
}
