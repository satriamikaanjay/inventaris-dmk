package com.example.aplikasi_inventaris_dmk.notification

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class StatusPollReceiver : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent?) {
        if (intent?.action == Intent.ACTION_BOOT_COMPLETED) {
            val session = PengajuanLocalStore.getActiveSession(context)
            if (session != null && session.isLoggedIn) {
                StatusPollScheduler.scheduleAll(context, 5_000L)
            }
            return
        }

        val session = PengajuanLocalStore.getActiveSession(context)
        if (session == null || !session.isLoggedIn) {
            StatusPollScheduler.cancelAll(context)
            return
        }

        // Jika service foreground baru saja mengecek < 4.5 detik lalu, cukup jadwalkan alarm berikutnya
        if (StatusPollScheduler.wasRecentlyPolled(context, 4500L)) {
            StatusPollScheduler.scheduleNextAlarm(context, 10_000L)
            return
        }

        val pendingResult = goAsync()
        val appContext = context.applicationContext
        val uid = if (session.userId > 0) session.userId else null

        ApiClient.instance.getUserPengajuan(uid, session.userName)
            .enqueue(object : Callback<PengajuanUserResponse> {
                override fun onResponse(
                    call: Call<PengajuanUserResponse>,
                    response: Response<PengajuanUserResponse>
                ) {
                    try {
                        StatusPollScheduler.markPolledNow(appContext)
                        val body = response.body()
                        if (response.isSuccessful && body != null && body.status == "success") {
                            val apiItems = body.riwayat ?: emptyList()
                            val synced = PengajuanLocalStore.mergeWithServer(
                                appContext,
                                uid,
                                session.userName,
                                apiItems
                            )
                            NotificationHelper.checkAndNotifyStatusChanges(appContext, synced)
                        }
                    } finally {
                        StatusPollScheduler.scheduleNextAlarm(appContext, 10_000L)
                        pendingResult.finish()
                    }
                }

                override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                    try {
                        StatusPollScheduler.scheduleNextAlarm(appContext, 15_000L)
                    } finally {
                        pendingResult.finish()
                    }
                }
            })
    }
}
