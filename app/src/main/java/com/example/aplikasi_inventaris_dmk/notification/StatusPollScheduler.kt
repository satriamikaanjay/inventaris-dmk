package com.example.aplikasi_inventaris_dmk.notification

import android.app.AlarmManager
import android.app.PendingIntent
import android.app.job.JobInfo
import android.app.job.JobScheduler
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.os.Build
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore

object StatusPollScheduler {

    private const val ALARM_REQUEST_CODE = 8801
    private const val JOB_ID = 8802
    private const val PREF_POLL = "StatusPollMeta"
    private const val KEY_LAST_POLL_MS = "last_poll_ms"

    fun markPolledNow(context: Context) {
        context.getSharedPreferences(PREF_POLL, Context.MODE_PRIVATE)
            .edit()
            .putLong(KEY_LAST_POLL_MS, System.currentTimeMillis())
            .apply()
    }

    fun wasRecentlyPolled(context: Context, thresholdMs: Long = 4500L): Boolean {
        val last = context.getSharedPreferences(PREF_POLL, Context.MODE_PRIVATE)
            .getLong(KEY_LAST_POLL_MS, 0L)
        return (System.currentTimeMillis() - last) < thresholdMs
    }

    fun scheduleAll(context: Context, delayMs: Long = 10_000L) {
        val session = PengajuanLocalStore.getActiveSession(context)
        if (session == null || !session.isLoggedIn) return

        scheduleNextAlarm(context, delayMs)
        schedulePeriodicJob(context)
    }

    fun scheduleNextAlarm(context: Context, delayMs: Long = 10_000L) {
        val session = PengajuanLocalStore.getActiveSession(context)
        if (session == null || !session.isLoggedIn) return

        val alarmManager = context.getSystemService(Context.ALARM_SERVICE) as? AlarmManager ?: return
        val intent = Intent(context, StatusPollReceiver::class.java)
        val pi = PendingIntent.getBroadcast(
            context,
            ALARM_REQUEST_CODE,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val triggerAt = System.currentTimeMillis() + delayMs
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                alarmManager.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerAt, pi)
            } else {
                alarmManager.set(AlarmManager.RTC_WAKEUP, triggerAt, pi)
            }
        } catch (_: Exception) {
        }
    }

    fun schedulePeriodicJob(context: Context) {
        val session = PengajuanLocalStore.getActiveSession(context)
        if (session == null || !session.isLoggedIn) return

        val scheduler = context.getSystemService(Context.JOB_SCHEDULER_SERVICE) as? JobScheduler ?: return
        val component = ComponentName(context, StatusCheckJobService::class.java)

        val builder = JobInfo.Builder(JOB_ID, component)
            .setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY)
            .setPersisted(true)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            builder.setPeriodic(15 * 60 * 1000L, 5 * 60 * 1000L)
        } else {
            builder.setPeriodic(15 * 60 * 1000L)
        }

        try {
            scheduler.schedule(builder.build())
        } catch (_: Exception) {
        }
    }

    fun cancelAll(context: Context) {
        val alarmManager = context.getSystemService(Context.ALARM_SERVICE) as? AlarmManager
        val intent = Intent(context, StatusPollReceiver::class.java)
        val pi = PendingIntent.getBroadcast(
            context,
            ALARM_REQUEST_CODE,
            intent,
            PendingIntent.FLAG_NO_CREATE or PendingIntent.FLAG_IMMUTABLE
        )
        if (pi != null && alarmManager != null) {
            alarmManager.cancel(pi)
            pi.cancel()
        }

        val scheduler = context.getSystemService(Context.JOB_SCHEDULER_SERVICE) as? JobScheduler
        scheduler?.cancel(JOB_ID)
    }
}
