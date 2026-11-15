package com.example.aplikasi_inventaris_dmk.network

import android.content.Context
import org.json.JSONArray
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

data class ActiveUserSession(
    val userId: Int,
    val userName: String,
    val userEmail: String,
    val userRole: String,
    val isLoggedIn: Boolean
)

object PengajuanLocalStore {
    private const val PREF_NAME = "PengajuanLocalPrefs"
    private const val KEY_ITEMS = "local_pengajuan_items"
    private const val KEY_NOTIFIED_STATUS = "notified_status_map"
    private const val KEY_SESSION_USER_ID = "session_user_id"
    private const val KEY_SESSION_USER_NAME = "session_user_name"
    private const val KEY_SESSION_USER_EMAIL = "session_user_email"
    private const val KEY_SESSION_USER_ROLE = "session_user_role"
    private const val KEY_SESSION_LOGGED_IN = "session_logged_in"

    fun saveActiveSession(
        context: Context,
        userId: Int,
        userName: String,
        userEmail: String,
        userRole: String
    ) {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        prefs.edit()
            .putInt(KEY_SESSION_USER_ID, userId)
            .putString(KEY_SESSION_USER_NAME, userName)
            .putString(KEY_SESSION_USER_EMAIL, userEmail)
            .putString(KEY_SESSION_USER_ROLE, userRole)
            .putBoolean(KEY_SESSION_LOGGED_IN, true)
            .apply()
    }

    fun getActiveSession(context: Context): ActiveUserSession? {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        if (!prefs.getBoolean(KEY_SESSION_LOGGED_IN, false)) return null
        return ActiveUserSession(
            userId = prefs.getInt(KEY_SESSION_USER_ID, 0),
            userName = prefs.getString(KEY_SESSION_USER_NAME, "Pengguna") ?: "Pengguna",
            userEmail = prefs.getString(KEY_SESSION_USER_EMAIL, "") ?: "",
            userRole = prefs.getString(KEY_SESSION_USER_ROLE, "") ?: "",
            isLoggedIn = true
        )
    }

    fun clearActiveSession(context: Context) {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        prefs.edit()
            .putBoolean(KEY_SESSION_LOGGED_IN, false)
            .remove(KEY_SESSION_USER_ID)
            .remove(KEY_SESSION_USER_NAME)
            .remove(KEY_SESSION_USER_EMAIL)
            .remove(KEY_SESSION_USER_ROLE)
            .apply()
    }

    fun saveSubmission(
        context: Context,
        serverItem: PengajuanItem? = null,
        userId: Int?,
        userName: String,
        kodeAset: String,
        namaAset: String,
        kategori: String?,
        lokasi: String?,
        cabangAsal: String? = null,
        ruanganAsal: String? = null,
        cabangTujuan: String? = null,
        ruanganTujuan: String? = null,
        jenisPengajuan: String,
        keterangan: String?,
        fileName: String?,
        localFileUri: String? = null
    ): PengajuanItem {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        val rawJson = prefs.getString(KEY_ITEMS, "[]") ?: "[]"
        val arr = try {
            JSONArray(rawJson)
        } catch (_: Exception) {
            JSONArray()
        }

        val today = serverItem?.tanggalPengajuan
            ?: SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date())
        val newId = serverItem?.id ?: (System.currentTimeMillis() % 1000000).toInt()
        val nomorAjuan = serverItem?.nomorPengajuan ?: "AJU-$newId"
        val statusVal = serverItem?.status ?: "Menunggu"

        val cleanKet = keterangan?.trim()?.ifBlank { "-" } ?: "-"

        val obj = JSONObject().apply {
            put("id", newId)
            put("nomor_pengajuan", nomorAjuan)
            put("user_id", userId ?: 0)
            put("nama_user", userName)
            put("kode_aset", kodeAset)
            put("nama_aset", namaAset)
            put("kategori", kategori ?: "-")
            put("lokasi", lokasi ?: "-")
            put("cabang_asal", cabangAsal ?: "-")
            put("ruangan_asal", ruanganAsal ?: "-")
            put("cabang_tujuan", cabangTujuan ?: cabangAsal ?: "-")
            put("ruangan_tujuan", ruanganTujuan ?: "-")
            put("jenis_pengajuan", jenisPengajuan)
            put("keterangan", cleanKet)
            put("lampiran_nama", fileName ?: serverItem?.lampiranNama ?: "")
            put("lampiran_url", serverItem?.lampiranUrl ?: localFileUri ?: "")
            put("status", statusVal)
            put("tanggal_pengajuan", today)
        }

        val newArr = JSONArray()
        newArr.put(obj)
        for (i in 0 until arr.length()) {
            val existing = arr.optJSONObject(i) ?: continue
            if (existing.optInt("id") != newId) {
                newArr.put(existing)
            }
        }

        prefs.edit().putString(KEY_ITEMS, newArr.toString()).apply()

        // Catat status awal ("Menunggu") agar saat Admin ACC / Tolak langsung terdeteksi perubahan statusnya
        val rawMap = prefs.getString(KEY_NOTIFIED_STATUS, "{}") ?: "{}"
        val mapObj = try {
            JSONObject(rawMap)
        } catch (_: Exception) {
            JSONObject()
        }
        mapObj.put(nomorAjuan, statusVal)
        prefs.edit().putString(KEY_NOTIFIED_STATUS, mapObj.toString()).apply()

        return PengajuanItem(
            id = newId,
            nomorPengajuan = nomorAjuan,
            userId = userId,
            namaUser = userName,
            kodeAset = kodeAset,
            namaAset = namaAset,
            kategori = kategori,
            lokasi = lokasi,
            cabangAsal = cabangAsal,
            ruanganAsal = ruanganAsal,
            cabangTujuan = cabangTujuan ?: cabangAsal,
            ruanganTujuan = ruanganTujuan,
            jenisPengajuan = jenisPengajuan,
            keterangan = cleanKet,
            lampiran = serverItem?.lampiran,
            lampiranNama = fileName ?: serverItem?.lampiranNama,
            lampiranUrl = serverItem?.lampiranUrl ?: localFileUri,
            status = statusVal,
            alasanPenolakan = null,
            tanggalPengajuan = today,
            tanggalRespon = null,
            responOleh = null
        )
    }

    fun getSubmissionsForUser(context: Context, userId: Int?, userName: String): List<PengajuanItem> {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        val rawJson = prefs.getString(KEY_ITEMS, "[]") ?: "[]"
        val arr = try {
            JSONArray(rawJson)
        } catch (_: Exception) {
            return emptyList()
        }

        val result = mutableListOf<PengajuanItem>()
        for (i in 0 until arr.length()) {
            val o = arr.optJSONObject(i) ?: continue
            val itemUserId = o.optInt("user_id", 0)
            val itemUserName = o.optString("nama_user", "")

            val matchUser = itemUserId == 0 ||
                    (userId != null && userId > 0 && itemUserId == userId) ||
                    itemUserName.equals(userName, ignoreCase = true) ||
                    itemUserName.contains(userName, ignoreCase = true) ||
                    userName.contains(itemUserName, ignoreCase = true) ||
                    userName.isBlank()

            if (matchUser) {
                result.add(
                    PengajuanItem(
                        id = o.optInt("id", i + 1),
                        nomorPengajuan = o.optString("nomor_pengajuan", "").takeIf { it.isNotBlank() },
                        userId = itemUserId,
                        namaUser = itemUserName,
                        kodeAset = o.optString("kode_aset", "-"),
                        namaAset = o.optString("nama_aset", "-"),
                        kategori = o.optString("kategori", "-"),
                        lokasi = o.optString("lokasi", "-"),
                        cabangAsal = o.optString("cabang_asal", "-"),
                        ruanganAsal = o.optString("ruangan_asal", "-"),
                        cabangTujuan = o.optString("cabang_tujuan", "-"),
                        ruanganTujuan = o.optString("ruangan_tujuan", "-"),
                        jenisPengajuan = o.optString("jenis_pengajuan", "-"),
                        keterangan = o.optString("keterangan", "-"),
                        lampiran = o.optString("lampiran", "").takeIf { it.isNotBlank() },
                        lampiranNama = o.optString("lampiran_nama", "").takeIf { it.isNotBlank() },
                        lampiranUrl = o.optString("lampiran_url", "").takeIf { it.isNotBlank() },
                        status = o.optString("status", "Menunggu"),
                        alasanPenolakan = o.optString("alasan_penolakan", "").takeIf { it.isNotBlank() },
                        tanggalPengajuan = o.optString("tanggal_pengajuan", "-"),
                        tanggalRespon = o.optString("tanggal_respon", "").takeIf { it.isNotBlank() },
                        responOleh = o.optString("respon_oleh", "").takeIf { it.isNotBlank() }
                    )
                )
            }
        }
        return result
    }

    /**
     * Menyinkronkan cache lokal dengan data resmi dari server database.
     * Data dari server adalah sumber kebenaran utama (jika dihapus dari database/phpMyAdmin,
     * maka cache lokal user ini juga otomatis ikut terhapus agar selalu sinkron).
     */
    fun mergeWithServer(
        context: Context,
        userId: Int?,
        userName: String,
        serverItems: List<PengajuanItem>
    ): List<PengajuanItem> {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        val updatedArr = JSONArray()

        // Simpan seluruh data resmi terbaru dari server
        for (item in serverItems) {
            val obj = JSONObject().apply {
                put("id", item.id)
                put("nomor_pengajuan", item.nomorPengajuan ?: "AJU-${item.id}")
                put("user_id", item.userId ?: userId ?: 0)
                put("nama_user", item.namaUser ?: userName)
                put("kode_aset", item.kodeAset ?: "-")
                put("nama_aset", item.namaAset ?: "-")
                put("kategori", item.kategori ?: "-")
                put("lokasi", item.lokasi ?: "-")
                put("cabang_asal", item.cabangAsal ?: "-")
                put("ruangan_asal", item.ruanganAsal ?: "-")
                put("cabang_tujuan", item.cabangTujuan ?: "-")
                put("ruangan_tujuan", item.ruanganTujuan ?: "-")
                put("jenis_pengajuan", item.jenisPengajuan ?: "-")
                put("keterangan", item.keterangan ?: "-")
                put("lampiran", item.lampiran ?: "")
                put("lampiran_nama", item.lampiranNama ?: "")
                put("lampiran_url", item.lampiranUrl ?: "")
                put("status", item.status ?: "Menunggu")
                put("alasan_penolakan", item.alasanPenolakan ?: "")
                put("tanggal_pengajuan", item.tanggalPengajuan ?: "-")
                put("tanggal_respon", item.tanggalRespon ?: "")
                put("respon_oleh", item.responOleh ?: "")
            }
            updatedArr.put(obj)
        }

        prefs.edit().putString(KEY_ITEMS, updatedArr.toString()).apply()
        return serverItems
    }

    /**
     * Mengecek apakah ada pengajuan yang statusnya baru berubah (misal dari Menunggu -> Disetujui / Ditolak)
     * untuk ditampilkan sebagai notifikasi di panel notifikasi Android & pop-up ke user.
     */
    @Synchronized
    fun detectStatusUpdates(context: Context, items: List<PengajuanItem>): List<PengajuanItem> {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        val rawMap = prefs.getString(KEY_NOTIFIED_STATUS, "{}") ?: "{}"
        val mapObj = try {
            JSONObject(rawMap)
        } catch (_: Exception) {
            JSONObject()
        }

        val newlyUpdated = mutableListOf<PengajuanItem>()
        for (item in items) {
            val key = item.nomorPengajuan ?: "id_${item.id}"
            val currentStatus = (item.status ?: "Menunggu").trim()
            val prevStatus = mapObj.optString(key, "")

            if (prevStatus.isNotEmpty() && !prevStatus.equals(currentStatus, ignoreCase = true)) {
                newlyUpdated.add(item)
            }
            mapObj.put(key, currentStatus)
        }

        prefs.edit().putString(KEY_NOTIFIED_STATUS, mapObj.toString()).apply()
        return newlyUpdated
    }
}
