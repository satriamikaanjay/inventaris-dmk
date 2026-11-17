package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.graphics.Color
import android.graphics.Typeface
import android.os.Bundle
import android.util.TypedValue
import android.view.Gravity
import android.view.View
import android.widget.Button
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.AssetItem
import com.example.aplikasi_inventaris_dmk.network.InventoryListResponse
import java.text.NumberFormat
import java.util.Locale
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class DetailActivity : AppCompatActivity() {

    private lateinit var containerFullDetails: LinearLayout

    private var currentCabang: String = "-"
    private var currentRuangan: String = "-"
    private var currentRuanganCabang: ArrayList<String> = arrayListOf()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_detail)

        containerFullDetails = findViewById(R.id.containerFullDetails)

        // Tombol Kembali
        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { finish() }
        findViewById<Button>(R.id.btnKembali)?.setOnClickListener { finish() }

        // Data user dari intent
        val userId    = intent.getIntExtra("USER_ID", 0)
        val userName  = intent.getStringExtra("USER_NAME") ?: "Pengguna"
        val userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        val userRole  = intent.getStringExtra("USER_ROLE") ?: ""
        val asetId    = intent.getIntExtra("ITEM_ASET_ID", 0)

        val rawCodeIntent = intent.getStringExtra("ITEM_CODE") ?: "-"
        val cleanKode = rawCodeIntent.removePrefix("Kode:").trim()

        // Cari di cache lokal berdasarkan ID aset spesifik terlebih dahulu
        val cachedItem = if (asetId > 0) {
            ScanActivity.cachedInventory.firstOrNull { it.id == asetId }
        } else {
            ScanActivity.cachedInventory.firstOrNull {
                cleanKode.isNotEmpty() && cleanKode != "-" && it.kodeAset?.trim().equals(cleanKode, ignoreCase = true)
            }
        }

        // Render awal menggunakan gabungan intent + cache
        renderFullDetail(cachedItem)

        // Sinkronisasi data terbaru dari server agar semua kolom website selalu paling mutakhir
        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(call: Call<InventoryListResponse>, response: Response<InventoryListResponse>) {
                val list = response.body()?.data
                if (response.isSuccessful && !list.isNullOrEmpty()) {
                    ScanActivity.cachedInventory = list
                    val freshItem = if (asetId > 0) {
                        list.firstOrNull { it.id == asetId }
                    } else {
                        list.firstOrNull {
                            cleanKode.isNotEmpty() && cleanKode != "-" && it.kodeAset?.trim().equals(cleanKode, ignoreCase = true)
                        }
                    }
                    if (freshItem != null && !isDestroyed && !isFinishing) {
                        renderFullDetail(freshItem)
                    }
                }
            }

            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {
                // Tetap gunakan data dari intent/cache
            }
        })

        // Saat user klik "Kirim Pengajuan", buka halaman baru untuk memilih jenis pengajuan
        findViewById<Button>(R.id.btnPinjamSekarang)?.setOnClickListener {
            val currentName = findViewById<TextView>(R.id.tvItemName)?.text?.toString() ?: "-"
            val currentCode = findViewById<TextView>(R.id.tvItemCode)?.text?.toString() ?: "Kode: -"
            val currentStatus = findViewById<TextView>(R.id.tvItemStatus)?.text?.toString() ?: "-"
            val currentLocation = findViewById<TextView>(R.id.tvItemLocation)?.text?.toString() ?: "-"
            val currentCategory = findViewById<TextView>(R.id.tvItemCategory)?.text?.toString() ?: "-"

            val nextIntent = Intent(this, PilihPengajuanActivity::class.java).apply {
                putExtra("ITEM_NAME", currentName)
                putExtra("ITEM_CODE", currentCode)
                putExtra("ITEM_STATUS", currentStatus)
                putExtra("ITEM_LOCATION", currentLocation)
                putExtra("ITEM_CATEGORY", currentCategory)
                putExtra("ITEM_CABANG", currentCabang)
                putExtra("ITEM_RUANGAN", currentRuangan)
                putStringArrayListExtra("ITEM_RUANGAN_CABANG", currentRuanganCabang)
                putExtra("ITEM_ASET_ID", asetId)
                putExtra("USER_ID", userId)
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
            }
            startActivity(nextIntent)
        }

        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            val histIntent = Intent(this, HistoryActivity::class.java).apply {
                putExtra("USER_ID", userId)
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
            }
            startActivity(histIntent)
        }
    }

    private fun renderFullDetail(item: AssetItem?) {
        val rawCodeIntent = intent.getStringExtra("ITEM_CODE")?.removePrefix("Kode:")?.trim()

        val kodeAset          = cleanVal(item?.kodeAset ?: rawCodeIntent)
        val namaAset          = cleanVal(item?.namaAset ?: intent.getStringExtra("ITEM_NAME"))
        val kategori          = cleanVal(item?.kategori ?: intent.getStringExtra("ITEM_CATEGORY"))
        val cabang            = cleanVal(item?.cabang ?: intent.getStringExtra("ITEM_CABANG"))
        val nomorSeri         = cleanVal(item?.nomorSeri ?: intent.getStringExtra("ITEM_SERI"))

        val merk              = cleanVal(item?.merk ?: intent.getStringExtra("ITEM_MERK"))
        val unitVal           = item?.unit ?: intent.getIntExtra("ITEM_UNIT", 0)
        val unitStr           = if (unitVal > 0) "$unitVal Unit" else "-"
        val tanggalPengadaan  = cleanVal(item?.tanggalPengadaan ?: intent.getStringExtra("ITEM_TGL"))
        val hargaBeli         = cleanRupiah(item?.hargaBeli ?: intent.getDoubleExtra("ITEM_HARGA", 0.0))
        val vendorPengadaan   = cleanVal(item?.vendorPengadaan ?: intent.getStringExtra("ITEM_VENDOR"))
        val noBeritaAcara     = cleanVal(item?.noBeritaAcara ?: intent.getStringExtra("ITEM_NO_BA"))

        val lokasiSaatIni     = cleanVal(
            item?.lokasiSaatIni
                ?: intent.getStringExtra("ITEM_LOKASI_SAAT_INI")
                ?: item?.lokasi
                ?: intent.getStringExtra("ITEM_LOCATION")
        )
        val ruangan           = cleanVal(item?.ruangan ?: intent.getStringExtra("ITEM_RUANGAN"))
        val penanggungJawab   = cleanVal(item?.penanggungJawab ?: intent.getStringExtra("ITEM_PIC"))
        val statusAset        = cleanVal(item?.status ?: item?.statusRaw ?: intent.getStringExtra("ITEM_STATUS"))
        val statusBerjalan    = cleanVal(item?.statusBerjalan ?: intent.getStringExtra("ITEM_STATUS_BERJALAN"))

        val statusMaintenance = cleanVal(item?.statusMaintenance ?: intent.getStringExtra("ITEM_STATUS_MAINT"))
        val teknisi           = cleanVal(item?.teknisi ?: intent.getStringExtra("ITEM_TEKNISI"))
        val biayaServis       = cleanRupiah(item?.biayaServis ?: intent.getDoubleExtra("ITEM_BIAYA_SERVIS", 0.0))
        val servisTerakhir    = cleanVal(item?.servisTerakhir ?: intent.getStringExtra("ITEM_SERVIS_TERAKHIR"))
        val servisBerikutnya  = cleanVal(item?.servisBerikutnya ?: intent.getStringExtra("ITEM_SERVIS_BERIKUTNYA"))

        val riwayatMutasi     = cleanVal(item?.riwayatMutasi ?: intent.getStringExtra("ITEM_RIWAYAT_MUTASI"))
        val tanggalMutasi     = cleanVal(item?.tanggalMutasi ?: intent.getStringExtra("ITEM_TGL_MUTASI"))
        val statusPeleburan   = cleanVal(item?.statusPeleburan ?: intent.getStringExtra("ITEM_STATUS_PELEBURAN"))
        val tanggalPeleburan  = cleanVal(item?.tanggalPeleburan ?: intent.getStringExtra("ITEM_TGL_PELEBURAN"))
        val metodePeleburan   = cleanVal(item?.metodePeleburan ?: intent.getStringExtra("ITEM_METODE_PELEBURAN"))
        val nilaiJual         = cleanRupiah(item?.nilaiJual ?: intent.getDoubleExtra("ITEM_NILAI_JUAL", 0.0))

        // Simpan state cabang & ruangan untuk diteruskan ke Pengajuan Perpindahan
        currentCabang = cabang
        currentRuangan = ruangan

        val roomsSet = linkedSetOf<String>()
        item?.ruanganCabang?.forEach { r ->
            val cl = cleanVal(r)
            if (cl != "-") roomsSet.add(cl)
        }
        if (cabang != "-") {
            ScanActivity.cachedInventory
                .filter { cleanVal(it.cabang).equals(cabang, ignoreCase = true) }
                .forEach { aset ->
                    val r = cleanVal(aset.ruangan)
                    if (r != "-") roomsSet.add(r)
                }
        }
        currentRuanganCabang = ArrayList(roomsSet.sorted())

        // Header Ringkasan Atas
        findViewById<TextView>(R.id.tvItemName)?.text = namaAset
        findViewById<TextView>(R.id.tvItemCode)?.text = "Kode: $kodeAset"
        findViewById<TextView>(R.id.tvItemStatus)?.text = statusAset
        findViewById<TextView>(R.id.tvTipeAsetBadge)?.text = statusBerjalan
        findViewById<TextView>(R.id.tvItemLocation)?.text = if (ruangan != "-") "$cabang ($ruangan)" else lokasiSaatIni
        findViewById<TextView>(R.id.tvItemCategory)?.text = kategori

        // Warna Badge Status
        val tvStatus = findViewById<TextView>(R.id.tvItemStatus)
        val statusUpper = statusAset.uppercase()
        when {
            statusUpper.contains("RUSAK BERAT") || statusUpper.contains("RUSAK PARAH") || statusUpper.contains("HILANG") -> {
                tvStatus?.setBackgroundResource(R.drawable.bg_badge_red)
                tvStatus?.setTextColor(Color.parseColor("#C62828"))
            }
            statusUpper.contains("RUSAK") || statusUpper.contains("PERBAIKAN") -> {
                tvStatus?.setBackgroundResource(R.drawable.bg_badge_orange)
                tvStatus?.setTextColor(Color.parseColor("#E65100"))
            }
            else -> {
                tvStatus?.setBackgroundResource(R.drawable.bg_badge_green)
                tvStatus?.setTextColor(Color.parseColor("#2E7D32"))
            }
        }

        // Warna Badge Tipe Aset (Selaras aturan web: Aset Berjalan vs Aset Tidak Berjalan / Aset Tidak Dipakai)
        val tvTipe = findViewById<TextView>(R.id.tvTipeAsetBadge)
        val tipeUpper = statusBerjalan.uppercase()
        when {
            tipeUpper.contains("TIDAK BERJALAN") || tipeUpper.contains("TIDAK DIPAKAI") -> {
                tvTipe?.setBackgroundResource(R.drawable.bg_badge_orange)
                tvTipe?.setTextColor(Color.parseColor("#E65100"))
            }
            tipeUpper.contains("ASET BERJALAN") -> {
                tvTipe?.setBackgroundResource(R.drawable.bg_nik_badge)
                tvTipe?.setTextColor(Color.parseColor("#1C4E8F"))
            }
            else -> {
                tvTipe?.setBackgroundResource(R.drawable.bg_nik_badge)
                tvTipe?.setTextColor(Color.parseColor("#475569"))
            }
        }

        // Ikon Barang
        val ivIcon = findViewById<ImageView>(R.id.ivItemDetailIcon)
        val checkStr = "$namaAset $kategori".lowercase()
        when {
            checkStr.contains("kendaraan") || checkStr.contains("mobil") || checkStr.contains("motor") ->
                ivIcon?.setImageResource(android.R.drawable.ic_menu_directions)
            checkStr.contains("proyektor") || checkStr.contains("kamera") ->
                ivIcon?.setImageResource(android.R.drawable.ic_menu_camera)
            checkStr.contains("laptop") || checkStr.contains("komputer") || checkStr.contains("printer") ->
                ivIcon?.setImageResource(android.R.drawable.ic_menu_slideshow)
            else -> ivIcon?.setImageResource(android.R.drawable.ic_menu_agenda)
        }

        // Bangun semua kartu seksi sesuai halaman Data Aset di Website
        containerFullDetails.removeAllViews()

        addSectionCard(
            stepNumber = "1",
            title = "Klasifikasi & Cabang",
            rows = listOf(
                "Kode Aset" to kodeAset,
                "Nama Aset" to namaAset,
                "Kategori" to kategori,
                "Cabang" to cabang,
                "Nomor Seri" to nomorSeri
            )
        )

        addSectionCard(
            stepNumber = "2",
            title = "Data Pengadaan & Spesifikasi",
            rows = listOf(
                "Merk / Brand" to merk,
                "Total Unit (Cabang)" to unitStr,
                "Tanggal Pengadaan" to tanggalPengadaan,
                "Harga Beli" to hargaBeli,
                "Vendor Pengadaan" to vendorPengadaan,
                "No. Berita Acara" to noBeritaAcara
            )
        )

        addSectionCard(
            stepNumber = "3",
            title = "Operasional & Status",
            rows = listOf(
                "Lokasi Spesifik Saat Ini" to lokasiSaatIni,
                "Keterangan Ruangan" to ruangan,
                "Penanggung Jawab" to penanggungJawab,
                "Status Aset Umum" to statusAset,
                "Tipe Aset" to statusBerjalan
            )
        )

        addSectionCard(
            stepNumber = "4",
            title = "Maintenance & Servis",
            rows = listOf(
                "Status Maintenance" to statusMaintenance,
                "Nama Teknisi" to teknisi,
                "Biaya Servis" to biayaServis,
                "Servis Terakhir" to servisTerakhir,
                "Servis Berikutnya" to servisBerikutnya
            )
        )

        addSectionCard(
            stepNumber = "5",
            title = "Mutasi & Peleburan",
            rows = listOf(
                "Riwayat Mutasi" to riwayatMutasi,
                "Tanggal Mutasi" to tanggalMutasi,
                "Status Peleburan" to statusPeleburan,
                "Tanggal Peleburan" to tanggalPeleburan,
                "Metode Peleburan" to metodePeleburan,
                "Nilai Jual" to nilaiJual
            )
        )
    }

    private fun addSectionCard(stepNumber: String, title: String, rows: List<Pair<String, String>>) {
        val card = CardView(this).apply {
            radius = dp(16).toFloat()
            cardElevation = dp(2).toFloat()
            setCardBackgroundColor(Color.WHITE)
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                setMargins(dp(16), dp(12), dp(16), 0)
            }
        }

        val inner = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dp(18), dp(16), dp(18), dp(16))
        }

        val headerRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
        }

        val badgeNum = TextView(this).apply {
            text = stepNumber
            setTextColor(Color.WHITE)
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
            typeface = Typeface.DEFAULT_BOLD
            gravity = Gravity.CENTER
            setBackgroundResource(R.drawable.bg_circle_blue)
            layoutParams = LinearLayout.LayoutParams(dp(24), dp(24))
        }

        val tvTitle = TextView(this).apply {
            text = title
            setTextColor(Color.parseColor("#1C4E8F"))
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 14f)
            typeface = Typeface.DEFAULT_BOLD
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                marginStart = dp(10)
            }
        }

        headerRow.addView(badgeNum)
        headerRow.addView(tvTitle)
        inner.addView(headerRow)

        val divider = View(this).apply {
            setBackgroundColor(Color.parseColor("#E2E8F0"))
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                dp(1)
            ).apply {
                topMargin = dp(10)
                bottomMargin = dp(6)
            }
        }
        inner.addView(divider)

        rows.forEachIndexed { index, (label, value) ->
            val rowLayout = LinearLayout(this).apply {
                orientation = LinearLayout.HORIZONTAL
                gravity = Gravity.TOP
                setPadding(0, dp(7), 0, dp(7))
            }

            val tvLabel = TextView(this).apply {
                text = label
                setTextColor(Color.parseColor("#64748B"))
                setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
                layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 0.45f).apply {
                    marginEnd = dp(8)
                }
            }

            val tvValue = TextView(this).apply {
                text = value.ifBlank { "-" }
                setTextColor(Color.parseColor("#0F172A"))
                setTextSize(TypedValue.COMPLEX_UNIT_SP, 12.5f)
                typeface = Typeface.DEFAULT_BOLD
                gravity = Gravity.END
                textAlignment = View.TEXT_ALIGNMENT_VIEW_END
                layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 0.55f)
            }

            rowLayout.addView(tvLabel)
            rowLayout.addView(tvValue)
            inner.addView(rowLayout)

            if (index < rows.lastIndex) {
                val itemDivider = View(this).apply {
                    setBackgroundColor(Color.parseColor("#F1F5F9"))
                    layoutParams = LinearLayout.LayoutParams(
                        LinearLayout.LayoutParams.MATCH_PARENT,
                        dp(1)
                    )
                }
                inner.addView(itemDivider)
            }
        }

        card.addView(inner)
        containerFullDetails.addView(card)
    }

    private fun cleanVal(raw: String?): String {
        if (raw == null) return "-"
        val trimmed = raw.trim()
        if (trimmed.isEmpty() || trimmed.equals("null", ignoreCase = true) || trimmed == "--") {
            return "-"
        }
        return trimmed
    }

    private fun cleanRupiah(amount: Double?): String {
        if (amount == null || amount <= 0.0) return "-"
        val fmt = NumberFormat.getCurrencyInstance(Locale.forLanguageTag("id-ID"))
        return fmt.format(amount).replace("Rp", "Rp ").substringBefore(",00")
    }

    private fun dp(value: Int): Int {
        return TypedValue.applyDimension(
            TypedValue.COMPLEX_UNIT_DIP,
            value.toFloat(),
            resources.displayMetrics
        ).toInt()
    }
}
