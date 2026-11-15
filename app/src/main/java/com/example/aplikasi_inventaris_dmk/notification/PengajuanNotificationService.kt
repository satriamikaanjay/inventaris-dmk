package com.example.aplikasi_inventaris_dmk.notification

import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.os.Looper
import androidx.core.content.ContextCompat
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class PengajuanNotificationService : Service() {

    companion object {
        private const val POLL_INTERVAL_MS = 5000L

        fun startMonitoring(context: Context) {
            val session = PengajuanLocalStore.getActiveSession(context) ?: return
            if (!session.isLoggedIn) return

            NotificationHelper.ensureChannels(context)
            StatusPollScheduler.scheduleAll(context, 10_000L)
            val intent = Intent(context, PengajuanNotificationService::class.java)
            try {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    ContextCompat.startForegroundService(context, intent)
                } else {
                    context.startService(intent)
                }
            } catch (_: Exception) {
                // Abaikan jika OS membatasi start foreground service dari background
            }
        }

        fun stopMonitoring(context: Context) {
            StatusPollScheduler.cancelAll(context)
            try {
                context.stopService(Intent(context, PengajuanNotificationService::class.java))
            } catch (_: Exception) {
            }
        }
    }

    private val handler = Handler(Looper.getMainLooper())
    private var isRunning = false
    private var isFetching = false

    private val pollRunnable = object : Runnable {
        override fun run() {
            if (!isRunning) return
            pollServerNow()
            handler.postDelayed(this, POLL_INTERVAL_MS)
        }
    }

    override fun onCreate() {
        super.onCreate()
        NotificationHelper.ensureChannels(this)
        val notification = NotificationHelper.buildMonitorNotification(this)
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                startForeground(
                    NotificationHelper.MONITOR_NOTIF_ID,
                    notification,
                    ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC
                )
            } else {
                startForeground(NotificationHelper.MONITOR_NOTIF_ID, notification)
            }
        } catch (_: Exception) {
            stopSelf()
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val session = PengajuanLocalStore.getActiveSession(this)
        if (session == null || !session.isLoggedIn) {
            stopSelf()
            return START_NOT_STICKY
        }

        if (!isRunning) {
            isRunning = true
            handler.removeCallbacks(pollRunnable)
            handler.post(pollRunnable)
        }
        return START_STICKY
    }

    private fun pollServerNow() {
        val session = PengajuanLocalStore.getActiveSession(this)
        if (session == null || !session.isLoggedIn) {
            stopSelf()
            return
        }
        if (isFetching) return
        isFetching = true

        val uid = if (session.userId > 0) session.userId else null
        ApiClient.instance.getUserPengajuan(uid, session.userName)
            .enqueue(object : Callback<PengajuanUserResponse> {
                override fun onResponse(
                    call: Call<PengajuanUserResponse>,
                    response: Response<PengajuanUserResponse>
                ) {
                    isFetching = false
                    StatusPollScheduler.markPolledNow(this@PengajuanNotificationService)
                    val body = response.body()
                    if (response.isSuccessful && body != null && body.status == "success") {
                        val apiItems = body.riwayat ?: emptyList()
                        val synced = PengajuanLocalStore.mergeWithServer(
                            this@PengajuanNotificationService,
                            uid,
                            session.userName,
                            apiItems
                        )
                        NotificationHelper.checkAndNotifyStatusChanges(
                            this@PengajuanNotificationService,
                            synced
                        )
                    }
                }

                override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                    isFetching = false
                }
            })
    }

    override fun onTaskRemoved(rootIntent: Intent?) {
        val session = PengajuanLocalStore.getActiveSession(this)
        if (session != null && session.isLoggedIn) {
            StatusPollScheduler.scheduleNextAlarm(applicationContext, 3_000L)
            StatusPollScheduler.schedulePeriodicJob(applicationContext)
        }
        super.onTaskRemoved(rootIntent)
    }

    override fun onDestroy() {
        isRunning = false
        handler.removeCallbacks(pollRunnable)
        val session = PengajuanLocalStore.getActiveSession(this)
        if (session != null && session.isLoggedIn) {
            StatusPollScheduler.scheduleNextAlarm(applicationContext, 5_000L)
        }
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null
}
