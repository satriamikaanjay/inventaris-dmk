package com.example.aplikasi_inventaris_dmk.network

import com.google.gson.annotations.SerializedName

data class InventoryListResponse(
    @SerializedName("status") val status: String,
    @SerializedName("message") val message: String?,
    @SerializedName("total") val total: Int?,
    @SerializedName("data") val data: List<AssetItem>?
)

data class InventorySingleResponse(
    @SerializedName("status") val status: String,
    @SerializedName("message") val message: String?,
    @SerializedName("data") val data: AssetItem?
)

data class AssetItem(
    @SerializedName("id") val id: Int,
    @SerializedName("kode_aset") val kodeAset: String?,
    @SerializedName("nama_aset") val namaAset: String?,
    @SerializedName("kategori") val kategori: String?,
    @SerializedName("kategori_id") val kategoriId: Int?,
    @SerializedName("status") val status: String?,
    @SerializedName("status_raw") val statusRaw: String?,
    @SerializedName("status_berjalan") val statusBerjalan: String?,
    @SerializedName("lokasi") val lokasi: String?,
    @SerializedName("lokasi_saat_ini") val lokasiSaatIni: String?,
    @SerializedName("ruangan", alternate = ["Ruangan"]) val ruangan: String?,
    @SerializedName("ruangan_cabang") val ruanganCabang: List<String>?,
    @SerializedName("unit") val unit: Int?,
    @SerializedName("spesifikasi") val spesifikasi: String?,
    @SerializedName("merk") val merk: String?,
    @SerializedName("nomor_seri") val nomorSeri: String?,
    @SerializedName("cabang") val cabang: String?,
    @SerializedName("cabang_id") val cabangId: Int?,
    @SerializedName("penanggung_jawab") val penanggungJawab: String?,
    @SerializedName("tanggal_pengadaan") val tanggalPengadaan: String?,
    @SerializedName("harga_beli") val hargaBeli: Double?,
    @SerializedName("vendor_pengadaan") val vendorPengadaan: String?,
    @SerializedName("no_berita_acara") val noBeritaAcara: String?,
    @SerializedName("status_maintenance") val statusMaintenance: String?,
    @SerializedName("teknisi") val teknisi: String?,
    @SerializedName("biaya_servis") val biayaServis: Double?,
    @SerializedName("servis_terakhir") val servisTerakhir: String?,
    @SerializedName("servis_berikutnya") val servisBerikutnya: String?,
    @SerializedName("riwayat_mutasi") val riwayatMutasi: String?,
    @SerializedName("tanggal_mutasi") val tanggalMutasi: String?,
    @SerializedName("status_peleburan") val statusPeleburan: String?,
    @SerializedName("tanggal_peleburan") val tanggalPeleburan: String?,
    @SerializedName("metode_peleburan") val metodePeleburan: String?,
    @SerializedName("nilai_jual") val nilaiJual: Double?
)

data class CategoryListResponse(
    @SerializedName("status") val status: String,
    @SerializedName("data") val data: List<CategoryItem>?
)

data class CategoryItem(
    @SerializedName("id") val id: Int,
    @SerializedName("nama_kategori") val namaKategori: String?,
    @SerializedName("kode_kategori") val kodeKategori: String?
)

data class BranchListResponse(
    @SerializedName("status") val status: String,
    @SerializedName("data") val data: List<BranchItem>?
)

data class BranchItem(
    @SerializedName("id") val id: Int,
    @SerializedName("nama_cabang") val namaCabang: String?,
    @SerializedName("ruangan") val ruangan: List<String>? = null
)

data class PengajuanRequest(
    @SerializedName("user_id") val userId: Int?,
    @SerializedName("nama_user") val namaUser: String,
    @SerializedName("pemohon") val pemohon: String = namaUser,
    @SerializedName("aset_id") val asetId: Int?,
    @SerializedName("data_stok_id") val dataStokId: Int? = null,
    @SerializedName("kode_aset") val kodeAset: String,
    @SerializedName("nama_aset") val namaAset: String,
    @SerializedName("kategori") val kategori: String?,
    @SerializedName("kategori_nama") val kategoriNama: String? = kategori,
    @SerializedName("unit_jumlah") val unitJumlah: Int? = null,
    @SerializedName("satuan") val satuan: String? = null,
    @SerializedName("nilai_estimasi") val nilaiEstimasi: Double? = null,
    @SerializedName("lokasi") val lokasi: String?,
    @SerializedName("cabang_asal") val cabangAsal: String? = null,
    @SerializedName("ruangan_asal") val ruanganAsal: String? = null,
    @SerializedName("cabang_tujuan") val cabangTujuan: String? = null,
    @SerializedName("ruangan_tujuan") val ruanganTujuan: String? = null,
    @SerializedName("jenis") val jenis: String,
    @SerializedName("jenis_pengajuan") val jenisPengajuan: String = jenis,
    @SerializedName("keterangan") val keterangan: String?,
    @SerializedName("file_name") val fileName: String? = null,
    @SerializedName("file_base64") val fileBase64: String? = null
)

data class PengajuanItem(
    @SerializedName("id") val id: Int,
    @SerializedName("nomor_pengajuan") val nomorPengajuan: String? = null,
    @SerializedName("user_id") val userId: Int?,
    @SerializedName("data_stok_id") val dataStokId: Int? = null,
    @SerializedName("nama_user", alternate = ["pemohon"]) val namaUser: String?,
    @SerializedName("kode_aset") val kodeAset: String?,
    @SerializedName("nama_aset") val namaAset: String?,
    @SerializedName("kategori", alternate = ["kategori_nama"]) val kategori: String?,
    @SerializedName("unit_jumlah") val unitJumlah: Int? = null,
    @SerializedName("satuan") val satuan: String? = null,
    @SerializedName("nilai_estimasi") val nilaiEstimasi: Double? = null,
    @SerializedName("lokasi") val lokasi: String?,
    @SerializedName("cabang_asal") val cabangAsal: String? = null,
    @SerializedName("ruangan_asal") val ruanganAsal: String? = null,
    @SerializedName("cabang_tujuan") val cabangTujuan: String? = null,
    @SerializedName("ruangan_tujuan") val ruanganTujuan: String? = null,
    @SerializedName("jenis_pengajuan", alternate = ["jenis"]) val jenisPengajuan: String?,
    @SerializedName("keterangan") val keterangan: String?,
    @SerializedName("lampiran") val lampiran: String? = null,
    @SerializedName("lampiran_nama") val lampiranNama: String? = null,
    @SerializedName("lampiran_url") val lampiranUrl: String? = null,
    @SerializedName("status") val status: String?,
    @SerializedName("alasan_penolakan", alternate = ["catatan_admin"]) val alasanPenolakan: String?,
    @SerializedName("tanggal_pengajuan") val tanggalPengajuan: String?,
    @SerializedName("tanggal_respon") val tanggalRespon: String?,
    @SerializedName("respon_oleh") val responOleh: String?
)

data class StokListResponse(
    @SerializedName("status") val status: String,
    @SerializedName("total") val total: Int?,
    @SerializedName("data") val data: List<StokItem>?
)

data class StokItem(
    @SerializedName("id") val id: Int,
    @SerializedName("urutan") val urutan: Int?,
    @SerializedName("nama_barang") val namaBarang: String?,
    @SerializedName("jumlah") val jumlah: Int?,
    @SerializedName("satuan") val satuan: String?,
    @SerializedName("keterangan") val keterangan: String?,
    @SerializedName("tersedia") val tersedia: Boolean?
)


data class PengajuanSubmitResponse(
    @SerializedName("status") val status: String?,
    @SerializedName("success") val success: Boolean? = null,
    @SerializedName("message") val message: String?,
    @SerializedName("data") val data: PengajuanItem?
)

data class PengajuanUserResponse(
    @SerializedName("status") val status: String,
    @SerializedName("total_pending") val totalPending: Int,
    @SerializedName("total_riwayat") val totalRiwayat: Int,
    @SerializedName("pending") val pending: List<PengajuanItem>?,
    @SerializedName("riwayat") val riwayat: List<PengajuanItem>?
)
