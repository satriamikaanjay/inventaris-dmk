package com.example.aplikasi_inventaris_dmk.notification

import android.app.job.JobParameters
import android.app.job.JobService
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore
import com.example.aplikasi_inventaris_dmk.network.PengajuanUserResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class StatusCheckJobService : JobService() {

    private var currentCall: Call<PengajuanUserResponse>? = null

    override fun onStartJob(params: JobParameters?): Boolean {
        val session = PengajuanLocalStore.getActiveSession(this)
        if (session == null || !session.isLoggedIn) {
            return false
        }

        val uid = if (session.userId > 0) session.userId else null
        currentCall = ApiClient.instance.getUserPengajuan(uid, session.userName)
        currentCall?.enqueue(object : Callback<PengajuanUserResponse> {
            override fun onResponse(
                call: Call<PengajuanUserResponse>,
                response: Response<PengajuanUserResponse>
            ) {
                try {
                    StatusPollScheduler.markPolledNow(this@StatusCheckJobService)
                    val body = response.body()
                    if (response.isSuccessful && body != null && body.status == "success") {
                        val apiItems = body.riwayat ?: emptyList()
                        val synced = PengajuanLocalStore.mergeWithServer(
                            this@StatusCheckJobService,
                            uid,
                            session.userName,
                            apiItems
                        )
                        NotificationHelper.checkAndNotifyStatusChanges(
                            this@StatusCheckJobService,
                            synced
                        )
                    }
                    StatusPollScheduler.scheduleNextAlarm(this@StatusCheckJobService, 10_000L)
                } finally {
                    jobFinished(params, false)
                }
            }

            override fun onFailure(call: Call<PengajuanUserResponse>, t: Throwable) {
                jobFinished(params, true)
            }
        })

        return true
    }

    override fun onStopJob(params: JobParameters?): Boolean {
        currentCall?.cancel()
        return true
    }
}
