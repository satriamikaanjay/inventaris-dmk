package com.example.aplikasi_inventaris_dmk.network

import com.google.gson.annotations.SerializedName

data class LoginResponse(
    val status: String,
    val message: String,
    val data: UserData?
)

data class UserData(
    val id: Int?,
    @SerializedName("user_id") val user_id: Int?,
    @SerializedName("user_name") val user_name: String?,
    val nama_lengkap: String?,
    val email: String?,
    val role: String?
)