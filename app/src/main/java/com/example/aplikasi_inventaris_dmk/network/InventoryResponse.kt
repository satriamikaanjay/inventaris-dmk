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
    @SerializedName("lokasi") val lokasi: String?,
    @SerializedName("unit") val unit: Int?,
    @SerializedName("spesifikasi") val spesifikasi: String?,
    @SerializedName("merk") val merk: String?,
    @SerializedName("nomor_seri") val nomorSeri: String?,
    @SerializedName("cabang") val cabang: String?,
    @SerializedName("cabang_id") val cabangId: Int?,
    @SerializedName("penanggung_jawab") val penanggungJawab: String?,
    @SerializedName("tanggal_pengadaan") val tanggalPengadaan: String?,
    @SerializedName("harga_beli") val hargaBeli: Double?

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
    @SerializedName("nama_cabang") val namaCabang: String?
)

data class PengajuanRequest(
    @SerializedName("user_id") val userId: Int?,
    @SerializedName("nama_user") val namaUser: String,
    @SerializedName("aset_id") val asetId: Int?,
    @SerializedName("kode_aset") val kodeAset: String,
    @SerializedName("nama_aset") val namaAset: String,
    @SerializedName("kategori") val kategori: String?,
    @SerializedName("lokasi") val lokasi: String?,
    @SerializedName("jenis_pengajuan") val jenisPengajuan: String,
    @SerializedName("keterangan") val keterangan: String?
)

data class PengajuanItem(
    @SerializedName("id") val id: Int,
    @SerializedName("user_id") val userId: Int?,
    @SerializedName("nama_user") val namaUser: String?,
    @SerializedName("kode_aset") val kodeAset: String?,
    @SerializedName("nama_aset") val namaAset: String?,
    @SerializedName("kategori") val kategori: String?,
    @SerializedName("lokasi") val lokasi: String?,
    @SerializedName("jenis_pengajuan") val jenisPengajuan: String?,
    @SerializedName("keterangan") val keterangan: String?,
    @SerializedName("status") val status: String?,
    @SerializedName("alasan_penolakan") val alasanPenolakan: String?,
    @SerializedName("tanggal_pengajuan") val tanggalPengajuan: String?,
    @SerializedName("tanggal_respon") val tanggalRespon: String?,
    @SerializedName("respon_oleh") val responOleh: String?
)

data class PengajuanSubmitResponse(
    @SerializedName("status") val status: String,
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