package com.example.aplikasi_inventaris_dmk

import android.app.Activity
import android.app.Application
import android.content.Intent
import android.os.Bundle
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.WifiAccessGuard

class InventarisApp : Application() {

    private var currentResumedActivity: Activity? = null

    override fun onCreate() {
        super.onCreate()
        ApiClient.init(applicationContext)
        WifiAccessGuard.startMonitoring(applicationContext)

        // Pantau perubahan Wi-Fi secara real-time saat aplikasi sedang dibuka
        WifiAccessGuard.addListener { result ->
            val active = currentResumedActivity ?: return@addListener
            if (active !is WifiBlockedActivity &&
                !active.isFinishing &&
                !active.isDestroyed &&
                result !is WifiAccessGuard.WifiCheckResult.Allowed
            ) {
                launchWifiBlockedScreen(active)
            }
        }

        // Pastikan setiap halaman aplikasi terkunci jika tidak terhubung ke Wi-Fi TP-Link_456A
        registerActivityLifecycleCallbacks(object : ActivityLifecycleCallbacks {
            override fun onActivityCreated(activity: Activity, savedInstanceState: Bundle?) {}
            override fun onActivityStarted(activity: Activity) {}

            override fun onActivityResumed(activity: Activity) {
                currentResumedActivity = activity
                if (activity !is WifiBlockedActivity && !WifiAccessGuard.isAllowed(activity)) {
                    launchWifiBlockedScreen(activity)
                }
            }

            override fun onActivityPaused(activity: Activity) {
                if (currentResumedActivity === activity) {
                    currentResumedActivity = null
                }
            }

            override fun onActivityStopped(activity: Activity) {}
            override fun onActivitySaveInstanceState(activity: Activity, outState: Bundle) {}
            override fun onActivityDestroyed(activity: Activity) {}
        })
    }

    private fun launchWifiBlockedScreen(fromActivity: Activity) {
        val intent = Intent(fromActivity, WifiBlockedActivity::class.java).apply {
            addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP)
        }
        fromActivity.startActivity(intent)
    }
}
