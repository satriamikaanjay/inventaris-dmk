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

class ScanResultActivity : AppCompatActivity() {

    private lateinit var tvScannedKode: TextView
    private lateinit var tvScannedSubtitle: TextView
    private lateinit var tvMatchedCountBadge: TextView
    private lateinit var containerMatchedAssets: LinearLayout

    private var scannedCode: String = ""
    private var targetKodeAset: String = ""
    private var userId: Int = 0
    private var userName: String = "Pengguna"
    private var userEmail: String = ""
    private var userRole: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_scan_result)

        scannedCode    = intent.getStringExtra("SCANNED_RAW_CODE") ?: ""
        targetKodeAset = intent.getStringExtra("TARGET_KODE_ASET") ?: scannedCode
        userId         = intent.getIntExtra("USER_ID", 0)
        userName       = intent.getStringExtra("USER_NAME") ?: "Pengguna"
        userEmail      = intent.getStringExtra("USER_EMAIL") ?: ""
        userRole       = intent.getStringExtra("USER_ROLE") ?: ""

        tvScannedKode          = findViewById(R.id.tvScannedKode)
        tvScannedSubtitle      = findViewById(R.id.tvScannedSubtitle)
        tvMatchedCountBadge    = findViewById(R.id.tvMatchedCountBadge)
        containerMatchedAssets = findViewById(R.id.containerMatchedAssets)

        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { finish() }
        findViewById<TextView>(R.id.btnScanUlangHeader)?.setOnClickListener {
            startActivity(Intent(this, ScanActivity::class.java).apply {
                putExtra("USER_ID", userId)
                putExtra("USER_NAME", userName)
                putExtra("USER_EMAIL", userEmail)
                putExtra("USER_ROLE", userRole)
            })
            finish()
        }

        // Tampilkan daftar dari cache terlebih dahulu
        val initialMatched = filterAssetsByCode(ScanActivity.cachedInventory)
        renderMatchedAssets(initialMatched)

        // Sinkronisasi data terbaru dari server
        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(
                call: Call<InventoryListResponse>,
                response: Response<InventoryListResponse>
            ) {
                val list = response.body()?.data
                if (response.isSuccessful && !list.isNullOrEmpty()) {
                    ScanActivity.cachedInventory = list
                    if (!isDestroyed && !isFinishing) {
                        val freshMatched = filterAssetsByCode(list)
                        renderMatchedAssets(freshMatched)
                    }
                }
            }

            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {
                // Gunakan cache yang sudah ditampilkan
            }
        })
    }

    private fun filterAssetsByCode(list: List<AssetItem>): List<AssetItem> {
        if (list.isEmpty()) return emptyList()

        val cleanTarget = targetKodeAset.trim()
        val normTarget = cleanTarget.replace("\\s+".toRegex(), "")

        // 1. Ambil semua aset yang memiliki kode_aset sama (case-insensitive, abaikan spasi)
        if (cleanTarget.isNotEmpty() && cleanTarget != "-") {
            val sameKodeList = list.filter { item ->
                val k = item.kodeAset?.trim() ?: ""
                val normK = k.replace("\\s+".toRegex(), "")
                k.equals(cleanTarget, ignoreCase = true) ||
                        (normK.isNotEmpty() && normK.equals(normTarget, ignoreCase = true))
            }
            if (sameKodeList.isNotEmpty()) {
                return sameKodeList
            }
        }

        // 2. Fallback pencarian dari raw scan code
        val rawClean = scannedCode.trim()
        val firstMatch = list.firstOrNull {
            it.kodeAset?.trim().equals(rawClean, ignoreCase = true) ||
                    it.nomorSeri?.trim().equals(rawClean, ignoreCase = true) ||
                    (rawClean.toIntOrNull() != null && it.id == rawClean.toIntOrNull())
        }
        if (firstMatch != null) {
            val matchedKode = firstMatch.kodeAset?.trim() ?: ""
            if (matchedKode.isNotEmpty() && matchedKode != "-") {
                return list.filter { it.kodeAset?.trim().equals(matchedKode, ignoreCase = true) }
            }
            return listOf(firstMatch)
        }

        return emptyList()
    }

    private fun renderMatchedAssets(matchedList: List<AssetItem>) {
        containerMatchedAssets.removeAllViews()

        val displayKode = matchedList.firstOrNull()?.kodeAset?.trim()?.takeIf { it.isNotEmpty() }
            ?: targetKodeAset.ifBlank { scannedCode }

        tvScannedKode.text = "Kode Aset: $displayKode"
        tvMatchedCountBadge.text = "${matchedList.size} Data"
        tvScannedSubtitle.text = if (matchedList.size > 1) {
            "Ditemukan ${matchedList.size} aset dengan kode yang sama di Cabang / Ruangan berbeda. Pilih salah satu:"
        } else {
            "Ditemukan ${matchedList.size} aset. Ketuk kartu di bawah untuk melihat detail lengkap:"
        }

        if (matchedList.isEmpty()) {
            val tvEmpty = TextView(this).apply {
                text = "Tidak ada aset yang cocok dengan kode \"$displayKode\"."
                setTextColor(Color.parseColor("#64748B"))
                setTextSize(TypedValue.COMPLEX_UNIT_SP, 14f)
                gravity = Gravity.CENTER
                setPadding(dp(16), dp(40), dp(16), dp(40))
            }
            containerMatchedAssets.addView(tvEmpty)
            return
        }

        matchedList.forEachIndexed { index, item ->
            val card = buildAssetChoiceCard(index + 1, item)
            containerMatchedAssets.addView(card)
        }
    }

    private fun buildAssetChoiceCard(number: Int, item: AssetItem): CardView {
        val namaAset = cleanVal(item.namaAset)
        val kodeAset = cleanVal(item.kodeAset)
        val kategori = cleanVal(item.kategori)
        val cabang   = cleanVal(item.cabang)
        val ruangan  = cleanVal(item.ruangan)
        val lokasi   = cleanVal(item.lokasiSaatIni ?: item.lokasi)
        val merk     = cleanVal(item.merk)
        val unitVal  = item.unit ?: 0
        val unitStr  = if (unitVal > 0) "$unitVal Unit" else "-"
        val status   = cleanVal(item.status ?: item.statusRaw ?: "Baik")

        val card = CardView(this).apply {
            radius = dp(16).toFloat()
            cardElevation = dp(3).toFloat()
            setCardBackgroundColor(Color.WHITE)
            isClickable = true
            isFocusable = true
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                bottomMargin = dp(12)
            }
        }

        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dp(16), dp(14), dp(16), dp(14))
        }

        // Baris 1: Nomor + Nama Aset + Badge Status
        val topRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
        }

        val tvBadgeNo = TextView(this).apply {
            text = "$number"
            setTextColor(Color.WHITE)
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
            typeface = Typeface.DEFAULT_BOLD
            gravity = Gravity.CENTER
            setBackgroundResource(R.drawable.bg_circle_blue)
            layoutParams = LinearLayout.LayoutParams(dp(26), dp(26))
        }

        val titleCol = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f).apply {
                marginStart = dp(10)
                marginEnd = dp(8)
            }
        }

        val tvName = TextView(this).apply {
            text = namaAset
            setTextColor(Color.parseColor("#0F172A"))
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 15f)
            typeface = Typeface.DEFAULT_BOLD
        }

        val tvKatKode = TextView(this).apply {
            text = "Kode: $kodeAset  •  $kategori"
            setTextColor(Color.parseColor("#64748B"))
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 11.5f)
        }

        titleCol.addView(tvName)
        titleCol.addView(tvKatKode)

        val tvStatusBadge = TextView(this).apply {
            text = status.uppercase()
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 10f)
            typeface = Typeface.DEFAULT_BOLD
            setPadding(dp(10), dp(4), dp(10), dp(4))
            val up = status.uppercase()
            when {
                up.contains("RUSAK BERAT") || up.contains("HILANG") -> {
                    setBackgroundResource(R.drawable.bg_badge_red)
                    setTextColor(Color.parseColor("#C62828"))
                }
                up.contains("RUSAK") || up.contains("PERBAIKAN") -> {
                    setBackgroundResource(R.drawable.bg_badge_orange)
                    setTextColor(Color.parseColor("#E65100"))
                }
                else -> {
                    setBackgroundResource(R.drawable.bg_badge_green)
                    setTextColor(Color.parseColor("#2E7D32"))
                }
            }
        }

        topRow.addView(tvBadgeNo)
        topRow.addView(titleCol)
        topRow.addView(tvStatusBadge)
        root.addView(topRow)

        // Box Highlight Cabang & Ruangan (Pembeda Utama)
        val locationBox = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundResource(R.drawable.bg_card_top)
            setPadding(dp(12), dp(10), dp(12), dp(10))
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                topMargin = dp(12)
            }
        }

        val rowCabang = buildHighlightInfoRow("🏢 Cabang", cabang, "#1C4E8F")
        val rowRuangan = buildHighlightInfoRow("🚪 Ruangan", ruangan, "#0F172A")
        locationBox.addView(rowCabang)
        locationBox.addView(rowRuangan)

        if (lokasi != "-" && !lokasi.equals(ruangan, ignoreCase = true)) {
            val rowLokasi = buildHighlightInfoRow("📍 Lokasi Spesifik", lokasi, "#475569")
            locationBox.addView(rowLokasi)
        }

        root.addView(locationBox)

        // Baris Bawah: Merk + Jumlah Unit + Tombol Lihat Detail
        val bottomRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                topMargin = dp(10)
            }
        }

        val tvMeta = TextView(this).apply {
            text = "Merk: $merk   •   Jumlah: $unitStr"
            setTextColor(Color.parseColor("#475569"))
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
            typeface = Typeface.DEFAULT_BOLD
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
        }

        val btnOpenDetail = Button(this).apply {
            text = "Lihat Detail →"
            isAllCaps = false
            setTextColor(Color.WHITE)
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
            typeface = Typeface.DEFAULT_BOLD
            setBackgroundResource(R.drawable.bg_btn_blue_square)
            setPadding(dp(14), 0, dp(14), 0)
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                dp(36)
            )
            setOnClickListener { openAssetDetail(item) }
        }

        bottomRow.addView(tvMeta)
        bottomRow.addView(btnOpenDetail)
        root.addView(bottomRow)

        card.addView(root)
        card.setOnClickListener { openAssetDetail(item) }
        return card
    }

    private fun buildHighlightInfoRow(label: String, value: String, valueColorHex: String): LinearLayout {
        return LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = Gravity.CENTER_VERTICAL
            setPadding(0, dp(2), 0, dp(2))

            val tvLbl = TextView(this@ScanResultActivity).apply {
                text = label
                setTextColor(Color.parseColor("#475569"))
                setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
                layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 0.42f)
            }

            val tvVal = TextView(this@ScanResultActivity).apply {
                text = value
                setTextColor(Color.parseColor(valueColorHex))
                setTextSize(TypedValue.COMPLEX_UNIT_SP, 13f)
                typeface = Typeface.DEFAULT_BOLD
                gravity = Gravity.END
                textAlignment = View.TEXT_ALIGNMENT_VIEW_END
                layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 0.58f)
            }

            addView(tvLbl)
            addView(tvVal)
        }
    }

    private fun openAssetDetail(aset: AssetItem) {
        val statusDisplay = (aset.status ?: aset.statusRaw ?: "Baik").trim()
        val itemName = aset.namaAset ?: "Aset #${aset.kodeAset ?: "-"}"
        val itemCode = "Kode: ${aset.kodeAset ?: "-"}"

        val detailIntent = Intent(this, DetailActivity::class.java).apply {
            putExtra("ITEM_ASET_ID",           aset.id)
            putExtra("ITEM_NAME",              itemName)
            putExtra("ITEM_CODE",              itemCode)
            putExtra("ITEM_STATUS",            statusDisplay)
            putExtra("ITEM_STATUS_BERJALAN",   aset.statusBerjalan ?: "-")
            putExtra("ITEM_LOCATION",          aset.lokasi ?: "-")
            putExtra("ITEM_LOKASI_SAAT_INI",   aset.lokasiSaatIni ?: aset.lokasi ?: "-")
            putExtra("ITEM_RUANGAN",           aset.ruangan ?: "-")
            putExtra("ITEM_CATEGORY",          aset.kategori ?: "-")
            putExtra("ITEM_SPECS",             buildSpesifikasiString(aset))

            putExtra("ITEM_MERK",              aset.merk ?: "-")
            putExtra("ITEM_SERI",              aset.nomorSeri ?: "-")
            putExtra("ITEM_UNIT",              aset.unit ?: 0)
            putExtra("ITEM_TGL",               aset.tanggalPengadaan ?: "-")
            putExtra("ITEM_HARGA",             aset.hargaBeli ?: 0.0)
            putExtra("ITEM_VENDOR",            aset.vendorPengadaan ?: "-")
            putExtra("ITEM_NO_BA",             aset.noBeritaAcara ?: "-")
            putExtra("ITEM_CABANG",            aset.cabang ?: "-")
            putExtra("ITEM_PIC",               aset.penanggungJawab ?: "-")
            putExtra("ITEM_STATUS_MAINT",      aset.statusMaintenance ?: "-")
            putExtra("ITEM_TEKNISI",           aset.teknisi ?: "-")
            putExtra("ITEM_BIAYA_SERVIS",      aset.biayaServis ?: 0.0)
            putExtra("ITEM_SERVIS_TERAKHIR",   aset.servisTerakhir ?: "-")
            putExtra("ITEM_SERVIS_BERIKUTNYA", aset.servisBerikutnya ?: "-")
            putExtra("ITEM_RIWAYAT_MUTASI",    aset.riwayatMutasi ?: "-")
            putExtra("ITEM_TGL_MUTASI",        aset.tanggalMutasi ?: "-")
            putExtra("ITEM_STATUS_PELEBURAN",  aset.statusPeleburan ?: "-")
            putExtra("ITEM_TGL_PELEBURAN",     aset.tanggalPeleburan ?: "-")
            putExtra("ITEM_METODE_PELEBURAN",  aset.metodePeleburan ?: "-")
            putExtra("ITEM_NILAI_JUAL",        aset.nilaiJual ?: 0.0)

            putExtra("USER_ID",                userId)
            putExtra("USER_NAME",              userName)
            putExtra("USER_EMAIL",             userEmail)
            putExtra("USER_ROLE",              userRole)
        }
        startActivity(detailIntent)
    }

    private fun buildSpesifikasiString(item: AssetItem): String {
        val lines = mutableListOf<String>()
        val statusVal = (item.status ?: item.statusRaw ?: "Baik").trim()
        if (statusVal.isNotBlank() && statusVal != "-") {
            lines.add("• Kondisi Fisik: $statusVal")
        }
        val merkVal = item.merk?.trim()
        if (!merkVal.isNullOrBlank() && merkVal != "-") {
            lines.add("• Merk / Brand: $merkVal")
        }
        val seriVal = item.nomorSeri?.trim()
        if (!seriVal.isNullOrBlank() && seriVal != "-" && seriVal != "--") {
            lines.add("• No. Seri: $seriVal")
        }
        val totalUnit = item.unit ?: 1
        lines.add("• Jumlah Unit: $totalUnit Unit")
        val ruanganVal = item.ruangan?.trim()
        if (!ruanganVal.isNullOrBlank() && ruanganVal != "-") {
            lines.add("• Ruangan: $ruanganVal")
        }
        val cabangVal = item.cabang?.trim()
        if (!cabangVal.isNullOrBlank() && cabangVal != "-") {
            lines.add("• Cabang: $cabangVal")
        }
        val harga = item.hargaBeli ?: 0.0
        if (harga > 0) {
            val formatRupiah = NumberFormat.getCurrencyInstance(Locale.forLanguageTag("id-ID"))
            lines.add("• Nilai Perolehan: ${formatRupiah.format(harga).replace("Rp", "Rp ")}")
        }
        return if (lines.isNotEmpty()) lines.joinToString("\n")
        else "• Kondisi Fisik: $statusVal"
    }

    private fun cleanVal(raw: String?): String {
        if (raw == null) return "-"
        val trimmed = raw.trim()
        if (trimmed.isEmpty() || trimmed.equals("null", ignoreCase = true) || trimmed == "--") {
            return "-"
        }
        return trimmed
    }

    private fun dp(value: Int): Int {
        return TypedValue.applyDimension(
            TypedValue.COMPLEX_UNIT_DIP,
            value.toFloat(),
            resources.displayMetrics
        ).toInt()
    }
}
