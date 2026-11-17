package com.example.aplikasi_inventaris_dmk

import android.app.Application
import com.example.aplikasi_inventaris_dmk.network.ApiClient

class InventarisApp : Application() {
    override fun onCreate() {
        super.onCreate()
        ApiClient.init(applicationContext)
    }
}
