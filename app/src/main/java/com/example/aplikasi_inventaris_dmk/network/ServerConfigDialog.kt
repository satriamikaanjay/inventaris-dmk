package com.example.aplikasi_inventaris_dmk.network

import android.content.Context
import android.graphics.Color
import android.text.InputType
import android.util.TypedValue
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.RadioButton
import android.widget.RadioGroup
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog

object ServerConfigDialog {

    fun show(context: Context, onSaved: () -> Unit) {
        ApiClient.init(context)
        val currentUrl = ApiClient.getBaseUrl(context)
        val currentHost = ApiClient.extractHostIp(currentUrl)

        val dp: (Int) -> Int = { value ->
            TypedValue.applyDimension(
                TypedValue.COMPLEX_UNIT_DIP,
                value.toFloat(),
                context.resources.displayMetrics
            ).toInt()
        }

        val container = LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dp(20), dp(14), dp(20), dp(8))
        }

        val tvHintInfo = TextView(context).apply {
            text = "Pastikan HP dan Laptop terhubung ke Wi-Fi / jaringan yang sama. Masukkan IP Laptop (cek lewat cmd: ipconfig) atau URL server:"
            textSize = 12f
            setTextColor(Color.parseColor("#475569"))
        }
        container.addView(tvHintInfo)

        val tvLabelIp = TextView(context).apply {
            text = "IP Laptop atau URL Lengkap"
            textSize = 12f
            setTextColor(Color.parseColor("#0F172A"))
            setPadding(0, dp(12), 0, dp(4))
        }
        container.addView(tvLabelIp)

        val etInput = EditText(context).apply {
            setText(
                if (currentUrl.contains("127.0.0.1")) ApiClient.DEFAULT_HOST_IP else currentHost
            )
            hint = "Contoh: ${ApiClient.DEFAULT_HOST_IP} atau 192.168.1.10"
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI
            textSize = 14f
            setSingleLine(true)
        }
        container.addView(etInput)

        val tvLabelMode = TextView(context).apply {
            text = "Mode Server di Laptop"
            textSize = 12f
            setTextColor(Color.parseColor("#0F172A"))
            setPadding(0, dp(12), 0, dp(4))
        }
        container.addView(tvLabelMode)

        val rgMode = RadioGroup(context)

        val rbApache = RadioButton(context).apply {
            id = android.view.View.generateViewId()
            text = "Laragon Apache Wi-Fi (Otomatis, Port 80)"
            textSize = 12.5f
        }
        val rbArtisan = RadioButton(context).apply {
            id = android.view.View.generateViewId()
            text = "Artisan Serve Wi-Fi (--host=0.0.0.0:8000)"
            textSize = 12.5f
        }
        val rbUsb = RadioButton(context).apply {
            id = android.view.View.generateViewId()
            text = "Kabel USB / adb reverse (127.0.0.1:8000)"
            textSize = 12.5f
        }

        rgMode.addView(rbApache)
        rgMode.addView(rbArtisan)
        rgMode.addView(rbUsb)

        when {
            currentUrl.contains("127.0.0.1") || currentUrl.contains("localhost") -> rbUsb.isChecked = true
            currentUrl.contains(":8000") -> rbArtisan.isChecked = true
            else -> rbApache.isChecked = true
        }

        container.addView(rgMode)

        val tvCurrent = TextView(context).apply {
            text = "URL aktif saat ini:\n$currentUrl"
            textSize = 11f
            setTextColor(Color.parseColor("#64748B"))
            setPadding(0, dp(10), 0, 0)
        }
        container.addView(tvCurrent)

        AlertDialog.Builder(context)
            .setTitle("Pengaturan Server Wi-Fi")
            .setView(container)
            .setPositiveButton("Simpan & Hubungkan") { _, _ ->
                val raw = etInput.text.toString().trim()
                val newUrl = when (rgMode.checkedRadioButtonId) {
                    rbUsb.id -> ApiClient.LOCALHOST_USB_URL
                    rbArtisan.id -> {
                        if (raw.contains("/")) {
                            ApiClient.normalizeBaseUrl(raw, preferArtisanPortIfNoPath = true)
                        } else {
                            val hostOnly = raw.removePrefix("http://").removePrefix("https://").substringBefore(":").ifBlank { ApiClient.DEFAULT_HOST_IP }
                            "http://$hostOnly:8000/api/"
                        }
                    }
                    else -> {
                        if (raw.contains("/")) {
                            ApiClient.normalizeBaseUrl(raw, preferArtisanPortIfNoPath = false)
                        } else {
                            val hostOnly = raw.removePrefix("http://").removePrefix("https://").substringBefore(":").ifBlank { ApiClient.DEFAULT_HOST_IP }
                            "http://$hostOnly/INVENTARIS-DMK/public/api/"
                        }
                    }
                }
                val saved = ApiClient.setBaseUrl(context, newUrl, markAsCustom = true)
                Toast.makeText(context, "Server diatur ke: $saved", Toast.LENGTH_SHORT).show()
                onSaved()
            }
            .setNeutralButton("Reset Default") { _, _ ->
                val resetUrl = ApiClient.setBaseUrl(context, ApiClient.DEFAULT_BASE_URL, markAsCustom = false)
                Toast.makeText(context, "Reset ke default: $resetUrl", Toast.LENGTH_SHORT).show()
                onSaved()
            }
            .setNegativeButton("Batal", null)
            .show()
    }
}
