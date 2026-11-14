package com.example.aplikasi_inventaris_dmk.network

import retrofit2.Call
import retrofit2.http.Body
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @FormUrlEncoded
    @POST("login")
    fun loginUser(
        @Field("email") email: String,
        @Field("password") pass: String
    ): Call<LoginResponse>

    @GET("inventory")
    fun getInventory(
        @Query("search") search: String? = null,
        @Query("kategori_id") kategoriId: Int? = null,
        @Query("cabang_id") cabangId: Int? = null
    ): Call<InventoryListResponse>

    @GET("inventory/categories")
    fun getCategories(): Call<CategoryListResponse>

    @GET("inventory/branches")
    fun getBranches(): Call<BranchListResponse>

    @GET("inventory/find")
    fun findAssetByCode(
        @Query("code") code: String
    ): Call<InventorySingleResponse>

    @POST("pengajuan")
    fun submitPengajuan(@Body body: PengajuanRequest): Call<PengajuanSubmitResponse>

    @GET("pengajuan/user")
    fun getUserPengajuan(
        @Query("user_id") userId: Int?,
        @Query("nama_user") namaUser: String? = null
    ): Call<PengajuanUserResponse>
}