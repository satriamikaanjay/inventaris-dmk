package com.example.aplikasi_inventaris_dmk

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.ImageView
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.InventoryListResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class PilihPengajuanActivity : AppCompatActivity() {

    private var itemCabang: String = "-"
    private var itemRuangan: String = "-"
    private var availableRoomsInCabang: ArrayList<String> = arrayListOf()

    private val formLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (result.resultCode == RESULT_OK) {
            finish()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_pilih_pengajuan)

        val itemName     = intent.getStringExtra("ITEM_NAME")     ?: "Aset Inventaris"
        val itemCode     = intent.getStringExtra("ITEM_CODE")     ?: "Kode: -"
        val itemStatus   = intent.getStringExtra("ITEM_STATUS")   ?: "Baik"
        val itemLocation = intent.getStringExtra("ITEM_LOCATION") ?: "-"
        val itemCategory = intent.getStringExtra("ITEM_CATEGORY") ?: "Umum"
        val asetId       = intent.getIntExtra("ITEM_ASET_ID", 0)

        itemCabang  = intent.getStringExtra("ITEM_CABANG")?.trim()?.ifBlank { "-" } ?: "-"
        itemRuangan = intent.getStringExtra("ITEM_RUANGAN")?.trim()?.ifBlank { "-" } ?: "-"
        val passedRooms = intent.getStringArrayListExtra("ITEM_RUANGAN_CABANG") ?: arrayListOf()

        val userId    = intent.getIntExtra("USER_ID", 0)
        val userName  = intent.getStringExtra("USER_NAME") ?: "Pengguna"
        val userEmail = intent.getStringExtra("USER_EMAIL") ?: ""
        val userRole  = intent.getStringExtra("USER_ROLE") ?: ""

        // Hitung daftar ruangan yang ada di cabang aset ini
        refreshRoomsFromCache(asetId, itemCode.removePrefix("Kode:").trim(), passedRooms)

        // Sinkronisasi di background bila cache belum terisi
        ApiClient.instance.getInventory().enqueue(object : Callback<InventoryListResponse> {
            override fun onResponse(call: Call<InventoryListResponse>, response: Response<InventoryListResponse>) {
                val list = response.body()?.data
                if (response.isSuccessful && !list.isNullOrEmpty()) {
                    ScanActivity.cachedInventory = list
                    refreshRoomsFromCache(asetId, itemCode.removePrefix("Kode:").trim(), passedRooms)
                }
            }
            override fun onFailure(call: Call<InventoryListResponse>, t: Throwable) {}
        })

        // Tampilkan ringkasan aset
        findViewById<TextView>(R.id.tvSelectedAssetName)?.text = itemName
        findViewById<TextView>(R.id.tvSelectedAssetCode)?.text = itemCode

        // Tombol Kembali
        findViewById<ImageView>(R.id.ivBack)?.setOnClickListener { finish() }
        findViewById<Button>(R.id.btnKembaliPilih)?.setOnClickListener { finish() }

        // Fungsi untuk menampilkan popup konfirmasi sebelum masuk ke halaman Form Pengajuan
        val confirmAndOpenForm: (String) -> Unit = { namaPengajuan ->
            val confirmMessage = if (namaPengajuan.contains("Perpindahan", ignoreCase = true) && itemCabang != "-") {
                "Apakah Anda yakin ingin memilih $namaPengajuan untuk aset \"$itemName\" (Cabang: $itemCabang)?"
            } else {
                "Apakah Anda yakin ingin memilih $namaPengajuan untuk aset \"$itemName\"?"
            }

            AlertDialog.Builder(this)
                .setTitle("Konfirmasi Pilihan Pengajuan")
                .setMessage(confirmMessage)
                .setPositiveButton("Iya") { _, _ ->
                    val formIntent = Intent(this, FormPengajuanActivity::class.java).apply {
                        putExtra("JENIS_PENGAJUAN", namaPengajuan)
                        putExtra("ITEM_NAME", itemName)
                        putExtra("ITEM_CODE", itemCode)
                        putExtra("ITEM_STATUS", itemStatus)
                        putExtra("ITEM_LOCATION", itemLocation)
                        putExtra("ITEM_CATEGORY", itemCategory)
                        putExtra("ITEM_CABANG", itemCabang)
                        putExtra("ITEM_RUANGAN", itemRuangan)
                        putStringArrayListExtra("ITEM_RUANGAN_CABANG", availableRoomsInCabang)
                        putExtra("ITEM_ASET_ID", asetId)
                        putExtra("USER_ID", userId)
                        putExtra("USER_NAME", userName)
                        putExtra("USER_EMAIL", userEmail)
                        putExtra("USER_ROLE", userRole)
                    }
                    formLauncher.launch(formIntent)
                }
                .setNegativeButton("Tidak", null)
                .show()
        }

        // 5 Opsi Pengajuan: Peminjaman, Permintaan, Peleburan, Perpindahan, Servis
        findViewById<CardView>(R.id.cardOpsiPeminjaman)?.setOnClickListener {
            confirmAndOpenForm("Pengajuan Peminjaman")
        }
        findViewById<CardView>(R.id.cardOpsiPermintaan)?.setOnClickListener {
            confirmAndOpenForm("Pengajuan Permintaan")
        }
        findViewById<CardView>(R.id.cardOpsiPeleburan)?.setOnClickListener {
            confirmAndOpenForm("Pengajuan Peleburan")
        }
        findViewById<CardView>(R.id.cardOpsiPerpindahan)?.setOnClickListener {
            confirmAndOpenForm("Pengajuan Perpindahan")
        }
        findViewById<CardView>(R.id.cardOpsiServis)?.setOnClickListener {
            confirmAndOpenForm("Pengajuan Servis")
        }
    }

    private fun refreshRoomsFromCache(asetId: Int, cleanCode: String, initialRooms: List<String>) {
        val matchedAsset = ScanActivity.cachedInventory.firstOrNull {
            (asetId > 0 && it.id == asetId) ||
            (cleanCode.isNotEmpty() && cleanCode != "-" && it.kodeAset?.trim().equals(cleanCode, ignoreCase = true))
        }

        if (matchedAsset != null) {
            val cab = matchedAsset.cabang?.trim()
            if (!cab.isNullOrBlank() && cab != "-") itemCabang = cab
            val rng = matchedAsset.ruangan?.trim()
            if (!rng.isNullOrBlank() && rng != "-") itemRuangan = rng
        }

        val roomSet = linkedSetOf<String>()
        initialRooms.forEach { r ->
            val cl = r.trim()
            if (cl.isNotEmpty() && cl != "-" && !cl.equals("null", ignoreCase = true)) {
                roomSet.add(cl)
            }
        }
        matchedAsset?.ruanganCabang?.forEach { r ->
            val cl = r.trim()
            if (cl.isNotEmpty() && cl != "-" && !cl.equals("null", ignoreCase = true)) {
                roomSet.add(cl)
            }
        }
        if (itemCabang.isNotBlank() && itemCabang != "-") {
            ScanActivity.cachedInventory
                .filter { it.cabang?.trim().equals(itemCabang, ignoreCase = true) }
                .forEach { aset ->
                    val r = aset.ruangan?.trim() ?: ""
                    if (r.isNotEmpty() && r != "-" && !r.equals("null", ignoreCase = true)) {
                        roomSet.add(r)
                    }
                }
        }
        availableRoomsInCabang = ArrayList(roomSet.sorted())
    }
}
