package com.example.aplikasi_inventaris_dmk

import android.app.ProgressDialog
import android.content.res.ColorStateList
import android.graphics.Color
import android.os.Bundle
import android.widget.Button
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanRequest
import com.example.aplikasi_inventaris_dmk.network.PengajuanSubmitResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class DetailActivity : AppCompatActivity() {

    private var selectedJenisPengajuan = "Pengajuan Peminjaman"

    private lateinit var btnPeminjaman: Button
    private lateinit var btnPengembalian: Button
    private lateinit var btnPeleburan: Button
    private lateinit var btnServis: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_detail)

        // Tombol Kembali
        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { finish() }
        findViewById<Button>(R.id.btnKembali)?.setOnClickListener { finish() }

        // Data dari intent
        val itemName     = intent.getStringExtra("ITEM_NAME")     ?: "Aset Inventaris"
        val itemCode     = intent.getStringExtra("ITEM_CODE")     ?: "Kode: -"
        val itemStatus   = intent.getStringExtra("ITEM_STATUS")   ?: "Baik"
        val itemLocation = intent.getStringExtra("ITEM_LOCATION") ?: "-"
        val itemCategory = intent.getStringExtra("ITEM_CATEGORY") ?: "Umum"
        var itemSpecs    = intent.getStringExtra("ITEM_SPECS")

        val itemMerk   = intent.getStringExtra("ITEM_MERK")
        val itemSeri   = intent.getStringExtra("ITEM_SERI")
        val itemUnit   = intent.getIntExtra("ITEM_UNIT", 0)
        val itemTgl    = intent.getStringExtra("ITEM_TGL")
        val itemHarga  = intent.getDoubleExtra("ITEM_HARGA", 0.0)
        val itemCabang = intent.getStringExtra("ITEM_CABANG")
        val itemPic    = intent.getStringExtra("ITEM_PIC")

        // Data user dari intent (dikirim dari Login → Home → Inventory/Scan → Detail)
        val userId   = intent.getIntExtra("USER_ID", 0)
        val userName = intent.getStringExtra("USER_NAME") ?: "Pengguna"

        // Ambil kode bersih (tanpa prefix "Kode: ")
        val rawCode = itemCode.removePrefix("Kode: ").trim()

        // Ambil aset_id jika ada (dikirim dari ScanActivity)
        val asetId = intent.getIntExtra("ITEM_ASET_ID", 0).takeIf { it > 0 }

        // Build spesifikasi jika belum lengkap
        if (itemSpecs.isNullOrBlank() || itemSpecs.contains("| Seri:") || itemSpecs.trim() == "-" || !itemSpecs.contains("•")) {
            val lines = mutableListOf<String>()
            val statusClean = itemStatus.replace("STATUS:", "").trim()
            if (statusClean.isNotBlank() && statusClean != "-") lines.add("• Kondisi Fisik: $statusClean")

            var cleanMerk = itemMerk?.trim()
            if ((cleanMerk.isNullOrBlank() || cleanMerk == "-") && itemSpecs?.contains("| Seri:") == true) {
                val ext = itemSpecs.substringBefore("|").trim()
                if (ext.isNotBlank() && ext != "-") cleanMerk = ext
            }
            if (!cleanMerk.isNullOrBlank() && cleanMerk != "-") lines.add("• Merk / Brand: $cleanMerk")

            var cleanSeri = itemSeri?.trim()
            if ((cleanSeri.isNullOrBlank() || cleanSeri == "-" || cleanSeri == "--") && itemSpecs?.contains("Seri:") == true) {
                val ext = itemSpecs.substringAfter("Seri:").trim()
                if (ext.isNotBlank() && ext != "-" && ext != "--") cleanSeri = ext
            }
            if (!cleanSeri.isNullOrBlank() && cleanSeri != "-" && cleanSeri != "--") lines.add("• No. Seri: $cleanSeri")

            if (itemUnit > 0) lines.add("• Jumlah Unit: $itemUnit Unit")
            if (itemLocation.isNotBlank() && itemLocation != "-") lines.add("• Lokasi Penyimpanan: $itemLocation")
            if (!itemCabang.isNullOrBlank() && itemCabang != "-") lines.add("• Cabang: $itemCabang")
            if (!itemCategory.isNullOrBlank() && itemCategory != "-") lines.add("• Kategori: $itemCategory")
            if (!itemTgl.isNullOrBlank() && itemTgl != "-") lines.add("• Tanggal Pengadaan: $itemTgl")
            if (itemHarga > 0) {
                val fmt = java.text.NumberFormat.getCurrencyInstance(java.util.Locale.forLanguageTag("id-ID"))
                lines.add("• Nilai Perolehan: ${fmt.format(itemHarga).replace("Rp", "Rp ")}")
            }
            if (!itemPic.isNullOrBlank() && itemPic != "-") lines.add("• Penanggung Jawab: $itemPic")

            itemSpecs = if (lines.isNotEmpty()) lines.joinToString("\n")
                        else "• Kondisi Fisik: $itemStatus\n• Spesifikasi standar inventaris DMK."
        }

        // Tampilkan ke UI
        findViewById<TextView>(R.id.tvItemName)?.text     = itemName
        findViewById<TextView>(R.id.tvItemCode)?.text     = itemCode
        findViewById<TextView>(R.id.tvItemStatus)?.text   = itemStatus
        findViewById<TextView>(R.id.tvItemLocation)?.text = itemLocation
        findViewById<TextView>(R.id.tvItemCategory)?.text = itemCategory
        findViewById<TextView>(R.id.tvItemSpecs)?.text    = itemSpecs

        // Badge warna status
        val tvStatus   = findViewById<TextView>(R.id.tvItemStatus)
        val statusUpper = itemStatus.uppercase()
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

        // Ikon barang
        val ivIcon = findViewById<ImageView>(R.id.ivItemDetailIcon)
        val checkStr = (itemName + " " + itemCategory).lowercase()
        when {
            checkStr.contains("kendaraan") || checkStr.contains("mobil") || checkStr.contains("motor") ->
                ivIcon?.setImageResource(android.R.drawable.ic_menu_directions)
            checkStr.contains("proyektor") || checkStr.contains("kamera") ->
                ivIcon?.setImageResource(android.R.drawable.ic_menu_camera)
            checkStr.contains("laptop") || checkStr.contains("komputer") || checkStr.contains("printer") ->
                ivIcon?.setImageResource(android.R.drawable.ic_menu_slideshow)
            else -> ivIcon?.setImageResource(android.R.drawable.ic_menu_agenda)
        }

        // Tombol Jenis Pengajuan
        btnPeminjaman   = findViewById(R.id.btnPengajuanPeminjaman)
        btnPengembalian = findViewById(R.id.btnPengajuanPengembalian)
        btnPeleburan    = findViewById(R.id.btnPengajuanPeleburan)
        btnServis       = findViewById(R.id.btnPengajuanServis)

        updateButtonSelection(btnPeminjaman)

        btnPeminjaman.setOnClickListener   { selectedJenisPengajuan = "Pengajuan Peminjaman";   updateButtonSelection(btnPeminjaman) }
        btnPengembalian.setOnClickListener { selectedJenisPengajuan = "Pengajuan Pengembalian"; updateButtonSelection(btnPengembalian) }
        btnPeleburan.setOnClickListener    { selectedJenisPengajuan = "Pengajuan Peleburan";    updateButtonSelection(btnPeleburan) }
        btnServis.setOnClickListener       { selectedJenisPengajuan = "Pengajuan Servis";       updateButtonSelection(btnServis) }

        // Tombol AJUKAN — kirim ke API
        findViewById<Button>(R.id.btnPinjamSekarang)?.setOnClickListener {
            AlertDialog.Builder(this)
                .setTitle("Konfirmasi Pengajuan")
                .setMessage("Apakah Anda ingin melakukan $selectedJenisPengajuan untuk \"$itemName\"?")
                .setPositiveButton("Ajukan") { _, _ ->
                    submitPengajuan(
                        userId      = if (userId > 0) userId else null,
                        userName    = userName,
                        asetId      = asetId,
                        kodeAset    = rawCode,
                        namaAset    = itemName,
                        kategori    = itemCategory,
                        lokasi      = itemLocation,
                        jenis       = selectedJenisPengajuan,
                        keterangan  = null
                    )
                }
                .setNegativeButton("Batal", null)
                .show()
        }

        findViewById<ImageView>(R.id.ivNotification)?.setOnClickListener {
            Toast.makeText(this, "Tidak ada notifikasi baru", Toast.LENGTH_SHORT).show()
        }
    }

    private fun submitPengajuan(
        userId: Int?,
        userName: String,
        asetId: Int?,
        kodeAset: String,
        namaAset: String,
        kategori: String?,
        lokasi: String?,
        jenis: String,
        keterangan: String?
    ) {
        // Map jenis pengajuan ke enum backend
        val jenisApi = when {
            jenis.contains("Pengembalian", true) -> "pengembalian"
            jenis.contains("Peleburan", true)    -> "peleburan"
            jenis.contains("Servis", true)        -> "servis"
            else                                  -> "peminjaman"
        }

        @Suppress("DEPRECATION")
        val progress = ProgressDialog(this).apply {
            setMessage("Mengirim pengajuan $jenisApi...")
            setCancelable(false)
            show()
        }

        val body = PengajuanRequest(
            userId        = userId,
            namaUser      = userName,
            asetId        = asetId,
            kodeAset      = kodeAset,
            namaAset      = namaAset,
            kategori      = kategori,
            lokasi        = lokasi,
            jenisPengajuan = jenisApi,
            keterangan    = keterangan ?: "Pengajuan $jenisApi via Mobile App"
        )

        ApiClient.instance.submitPengajuan(body).enqueue(object : Callback<PengajuanSubmitResponse> {
            override fun onResponse(call: Call<PengajuanSubmitResponse>, response: Response<PengajuanSubmitResponse>) {
                progress.dismiss()
                val body = response.body()
                if (response.isSuccessful && body != null && body.status == "success") {
                    Toast.makeText(
                        this@DetailActivity,
                        "✅ $jenis \"$namaAset\" berhasil dikirim!\nMenunggu persetujuan Admin.",
                        Toast.LENGTH_LONG
                    ).show()
                    finish()
                } else {
                    val errMsg = body?.message ?: "Gagal mengirim pengajuan. Coba lagi."
                    Toast.makeText(this@DetailActivity, "❌ $errMsg", Toast.LENGTH_LONG).show()
                }
            }

            override fun onFailure(call: Call<PengajuanSubmitResponse>, t: Throwable) {
                progress.dismiss()
                Toast.makeText(
                    this@DetailActivity,
                    "❌ Koneksi gagal: ${t.localizedMessage}",
                    Toast.LENGTH_LONG
                ).show()
            }
        })
    }

    private fun updateButtonSelection(activeBtn: Button) {
        val allButtons = arrayOf(btnPeminjaman, btnPengembalian, btnPeleburan, btnServis)
        for (btn in allButtons) {
            if (btn == activeBtn) {
                btn.backgroundTintList = ColorStateList.valueOf(Color.parseColor("#1C4E8F"))
                btn.setTextColor(Color.WHITE)
                btn.setCompoundDrawablesWithIntrinsicBounds(android.R.drawable.presence_online, 0, 0, 0)
            } else {
                btn.backgroundTintList = ColorStateList.valueOf(Color.parseColor("#F8F9FA"))
                btn.setTextColor(Color.parseColor("#333333"))
                btn.setCompoundDrawablesWithIntrinsicBounds(android.R.drawable.presence_invisible, 0, 0, 0)
            }
        }
    }
}
