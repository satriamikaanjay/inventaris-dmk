package com.example.aplikasi_inventaris_dmk.notification

import android.Manifest
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.BitmapFactory
import android.graphics.Color
import android.media.RingtoneManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.app.TaskStackBuilder
import androidx.core.content.ContextCompat
import com.example.aplikasi_inventaris_dmk.HistoryActivity
import com.example.aplikasi_inventaris_dmk.HomeActivity
import com.example.aplikasi_inventaris_dmk.R
import com.example.aplikasi_inventaris_dmk.network.PengajuanItem
import com.example.aplikasi_inventaris_dmk.network.PengajuanLocalStore

object NotificationHelper {

    const val CHANNEL_STATUS_ID = "pengajuan_status_channel"
    const val CHANNEL_MONITOR_ID = "pengajuan_monitor_channel"
    const val MONITOR_NOTIF_ID = 9001
    const val ACTION_STATUS_UPDATED = "com.example.aplikasi_inventaris_dmk.STATUS_UPDATED"

    fun ensureChannels(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val nm = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

            // 1. Channel Notifikasi Utama (Pop-up Heads-up, Suara, Getar di Panel Notifikasi)
            val statusChannel = NotificationChannel(
                CHANNEL_STATUS_ID,
                "Status Pengajuan Aset",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Notifikasi ketika pengajuan aset di-ACC atau ditolak oleh Admin."
                enableLights(true)
                lightColor = Color.parseColor("#1C4E8F")
                enableVibration(true)
                vibrationPattern = longArrayOf(0, 250, 150, 250)
                lockscreenVisibility = Notification.VISIBILITY_PUBLIC
            }
            nm.createNotificationChannel(statusChannel)

            // 2. Channel Pemantauan Latar Belakang (Senyap & Minimalis)
            val monitorChannel = NotificationChannel(
                CHANNEL_MONITOR_ID,
                "Sinkronisasi Latar Belakang",
                NotificationManager.IMPORTANCE_MIN
            ).apply {
                description = "Menjaga pemantauan status persetujuan Admin tetap aktif."
                setShowBadge(false)
                enableLights(false)
                enableVibration(false)
                setSound(null, null)
            }
            nm.createNotificationChannel(monitorChannel)
        }
    }

    fun buildMonitorNotification(context: Context): Notification {
        ensureChannels(context)
        val session = PengajuanLocalStore.getActiveSession(context)
        val homeIntent = Intent(context, HomeActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_SINGLE_TOP or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra("USER_ID", session?.userId ?: 0)
            putExtra("USER_NAME", session?.userName ?: "Pengguna")
            putExtra("USER_EMAIL", session?.userEmail ?: "")
            putExtra("USER_ROLE", session?.userRole ?: "")
        }
        val pi = PendingIntent.getActivity(
            context,
            MONITOR_NOTIF_ID,
            homeIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        return NotificationCompat.Builder(context, CHANNEL_MONITOR_ID)
            .setSmallIcon(R.drawable.ic_notif_status)
            .setContentTitle("Aplikasi Pengajuan DMK")
            .setContentText("Memantau status persetujuan pengajuan dari Admin")
            .setPriority(NotificationCompat.PRIORITY_MIN)
            .setOngoing(true)
            .setSilent(true)
            .setContentIntent(pi)
            .build()
    }

    /**
     * Memeriksa apakah ada status pengajuan yang berubah (misal Menunggu -> Disetujui / Ditolak).
     * Jika ada, langsung menampilkan notifikasi sistem di panel notifikasi Android
     * dan mengirim broadcast ke UI yang sedang aktif.
     */
    fun checkAndNotifyStatusChanges(context: Context, items: List<PengajuanItem>): List<PengajuanItem> {
        val changedItems = PengajuanLocalStore.detectStatusUpdates(context, items)
        if (changedItems.isNotEmpty()) {
            changedItems.forEach { item ->
                showStatusNotification(context, item)
            }
            val broadcast = Intent(ACTION_STATUS_UPDATED).apply {
                setPackage(context.packageName)
            }
            context.sendBroadcast(broadcast)
        }
        return changedItems
    }

    fun showStatusNotification(context: Context, item: PengajuanItem) {
        ensureChannels(context)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            val granted = ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.POST_NOTIFICATIONS
            ) == PackageManager.PERMISSION_GRANTED
            if (!granted) return
        }

        val rawStatus = (item.status ?: "Menunggu").trim()
        val isApproved = rawStatus.equals("Disetujui", ignoreCase = true) ||
                rawStatus.equals("Selesai", ignoreCase = true) ||
                rawStatus.equals("Diterima", ignoreCase = true)
        val isRejected = rawStatus.equals("Ditolak", ignoreCase = true)

        val jenisLabel = item.jenisPengajuan?.replaceFirstChar { it.uppercase() } ?: "Aset"
        val namaAset = item.namaAset ?: "Aset Inventaris"
        val nomor = item.nomorPengajuan ?: item.kodeAset ?: "-"

        val title = when {
            isApproved -> "✅ Pengajuan $jenisLabel Diterima (ACC)"
            isRejected -> "❌ Pengajuan $jenisLabel Ditolak"
            else       -> "🔔 Pembaruan Status Pengajuan $jenisLabel"
        }

        val perpindahanInfo = if (item.jenisPengajuan.equals("perpindahan", ignoreCase = true) &&
            !item.ruanganTujuan.isNullOrBlank() && item.ruanganTujuan != "-"
        ) {
            "\nRuangan: ${item.ruanganAsal ?: "-"} ➔ ${item.ruanganTujuan}"
        } else {
            ""
        }

        val catatanAdmin = if (!item.alasanPenolakan.isNullOrBlank()) {
            "\nCatatan Admin: ${item.alasanPenolakan}"
        } else {
            ""
        }

        val shortBody = when {
            isApproved -> "Pengajuan \"$namaAset\" ($nomor) telah di-ACC oleh Admin."
            isRejected -> "Pengajuan \"$namaAset\" ($nomor) ditolak oleh Admin."
            else       -> "Status pengajuan \"$namaAset\" ($nomor): $rawStatus."
        }
        val bigBody = "$shortBody$perpindahanInfo$catatanAdmin"

        val session = PengajuanLocalStore.getActiveSession(context)
        val homeIntent = Intent(context, HomeActivity::class.java).apply {
            putExtra("USER_ID", session?.userId ?: item.userId ?: 0)
            putExtra("USER_NAME", session?.userName ?: item.namaUser ?: "Pengguna")
            putExtra("USER_EMAIL", session?.userEmail ?: "")
            putExtra("USER_ROLE", session?.userRole ?: "")
        }
        val historyIntent = Intent(context, HistoryActivity::class.java).apply {
            putExtra("USER_ID", session?.userId ?: item.userId ?: 0)
            putExtra("USER_NAME", session?.userName ?: item.namaUser ?: "Pengguna")
            putExtra("USER_EMAIL", session?.userEmail ?: "")
            putExtra("USER_ROLE", session?.userRole ?: "")
        }

        val notifId = (item.id * 31) + rawStatus.hashCode()
        val pendingIntent = TaskStackBuilder.create(context).run {
            addNextIntent(homeIntent)
            addNextIntent(historyIntent)
            getPendingIntent(
                notifId,
                PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
            )
        }

        val accentColor = when {
            isApproved -> Color.parseColor("#2E7D32")
            isRejected -> Color.parseColor("#C62828")
            else       -> Color.parseColor("#1C4E8F")
        }

        val largeIcon = try {
            BitmapFactory.decodeResource(context.resources, R.drawable.logo)
        } catch (_: Exception) {
            null
        }

        val soundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)

        val builder = NotificationCompat.Builder(context, CHANNEL_STATUS_ID)
            .setSmallIcon(R.drawable.ic_notif_status)
            .setColor(accentColor)
            .setContentTitle(title)
            .setContentText(shortBody)
            .setStyle(NotificationCompat.BigTextStyle().bigText(bigBody))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_STATUS)
            .setAutoCancel(true)
            .setSound(soundUri)
            .setVibrate(longArrayOf(0, 250, 150, 250))
            .setDefaults(NotificationCompat.DEFAULT_ALL)
            .setVisibility(NotificationCompat.VISIBILITY_PUBLIC)
            .setContentIntent(pendingIntent)

        if (largeIcon != null) {
            builder.setLargeIcon(largeIcon)
        }

        try {
            NotificationManagerCompat.from(context).notify(notifId, builder.build())
        } catch (_: SecurityException) {
        }
    }
}
