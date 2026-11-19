package com.example.aplikasi_inventaris_dmk

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Color
import android.graphics.drawable.ColorDrawable
import android.net.Uri
import android.os.Handler
import android.os.Looper
import android.util.LruCache
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.ImageView
import android.widget.ProgressBar
import android.widget.TextView
import androidx.appcompat.app.AlertDialog
import com.example.aplikasi_inventaris_dmk.network.ApiClient
import okhttp3.OkHttpClient
import okhttp3.Request
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit

object PhotoLoader {

    private val mainHandler = Handler(Looper.getMainLooper())
    private val executor = Executors.newFixedThreadPool(4)

    private val imageHttpClient: OkHttpClient by lazy {
        OkHttpClient.Builder()
            .connectTimeout(8, TimeUnit.SECONDS)
            .readTimeout(15, TimeUnit.SECONDS)
            .build()
    }

    private val memoryCache: LruCache<String, Bitmap> by lazy {
        val maxMemoryKb = (Runtime.getRuntime().maxMemory() / 1024L).toInt()
        val cacheSizeKb = (maxMemoryKb / 8).coerceIn(4 * 1024, 24 * 1024)
        object : LruCache<String, Bitmap>(cacheSizeKb) {
            override fun sizeOf(key: String, bitmap: Bitmap): Int {
                return bitmap.byteCount / 1024
            }
        }
    }

    fun hasPhoto(fotoBarang: String?, fotoUrl: String? = null): Boolean {
        val rawPath = fotoBarang?.trim().orEmpty()
        val rawUrl = fotoUrl?.trim().orEmpty()
        return (rawPath.isNotEmpty() && rawPath != "-") || (rawUrl.isNotEmpty() && rawUrl != "-")
    }

    /**
     * Membangun URL foto berdasarkan server aktif saat ini (ApiClient.getBaseUrl),
     * sehingga foto tetap muncul walaupun perangkat berpindah jaringan Wi-Fi / IP server.
     */
    fun resolveFotoUrl(context: Context, fotoBarang: String?, fotoUrl: String? = null): String? {
        val rawPath = fotoBarang?.trim().orEmpty()
        if (rawPath.isNotEmpty() && rawPath != "-") {
            if (rawPath.startsWith("http://", ignoreCase = true) || rawPath.startsWith("https://", ignoreCase = true)) {
                val fileNameFromUrl = rawPath.substringBefore('?').substringAfterLast('/').substringAfterLast('\\')
                if (fileNameFromUrl.isNotBlank()) {
                    val base = ApiClient.getBaseUrl(context).trimEnd('/')
                    return "$base/foto-barang/${Uri.encode(fileNameFromUrl)}"
                }
                return rawPath
            }
            val cleanName = rawPath.substringBefore('?').substringAfterLast('/').substringAfterLast('\\').trim()
            if (cleanName.isNotEmpty()) {
                val base = ApiClient.getBaseUrl(context).trimEnd('/')
                return "$base/foto-barang/${Uri.encode(cleanName)}"
            }
        }

        val rawUrl = fotoUrl?.trim().orEmpty()
        if (rawUrl.isNotEmpty() && rawUrl != "-") {
            val cleanName = rawUrl.substringBefore('?').substringAfterLast('/').substringAfterLast('\\').trim()
            if (cleanName.isNotEmpty() && rawUrl.contains("/foto-barang/", ignoreCase = true)) {
                val base = ApiClient.getBaseUrl(context).trimEnd('/')
                return "$base/foto-barang/${Uri.encode(cleanName)}"
            }
            return rawUrl
        }

        return null
    }

    fun loadInto(
        context: Context,
        imageView: ImageView,
        fotoBarang: String?,
        fotoUrl: String? = null,
        maxDimPx: Int = 512,
        onLoaded: ((Bitmap) -> Unit)? = null,
        onFailed: (() -> Unit)? = null
    ) {
        val resolvedUrl = resolveFotoUrl(context, fotoBarang, fotoUrl)
        if (resolvedUrl.isNullOrBlank()) {
            imageView.tag = null
            onFailed?.invoke()
            return
        }

        val cacheKey = "$resolvedUrl#$maxDimPx"
        imageView.tag = cacheKey

        val cached = memoryCache.get(cacheKey)
        if (cached != null && !cached.isRecycled) {
            imageView.setImageBitmap(cached)
            onLoaded?.invoke(cached)
            return
        }

        executor.execute {
            val bitmap = fetchAndDecodeBitmap(resolvedUrl, maxDimPx)
            mainHandler.post {
                if (imageView.tag != cacheKey) {
                    return@post
                }
                if (bitmap != null) {
                    memoryCache.put(cacheKey, bitmap)
                    imageView.setImageBitmap(bitmap)
                    onLoaded?.invoke(bitmap)
                } else {
                    onFailed?.invoke()
                }
            }
        }
    }

    fun showImagePreviewPopup(
        context: Context,
        title: String,
        subtitle: String?,
        fotoBarang: String?,
        fotoUrl: String? = null
    ) {
        if (!hasPhoto(fotoBarang, fotoUrl)) return

        val view = LayoutInflater.from(context).inflate(R.layout.dialog_image_preview, null)
        val dialog = AlertDialog.Builder(context)
            .setView(view)
            .setCancelable(true)
            .create()

        dialog.window?.setBackgroundDrawable(ColorDrawable(Color.TRANSPARENT))

        val tvTitle = view.findViewById<TextView>(R.id.tvPreviewTitle)
        val tvSubtitle = view.findViewById<TextView>(R.id.tvPreviewSubtitle)
        val btnClose = view.findViewById<TextView>(R.id.btnClosePreview)
        val ivPhoto = view.findViewById<ImageView>(R.id.ivPreviewPhoto)
        val pbLoading = view.findViewById<ProgressBar>(R.id.pbPreviewLoading)
        val tvError = view.findViewById<TextView>(R.id.tvPreviewError)
        val tvHint = view.findViewById<TextView>(R.id.tvPreviewHint)
        val btnTutup = view.findViewById<Button>(R.id.btnTutupPreview)

        tvTitle.text = title.ifBlank { "Foto Barang" }
        if (!subtitle.isNullOrBlank()) {
            tvSubtitle.text = subtitle
            tvSubtitle.visibility = View.VISIBLE
        } else {
            tvSubtitle.visibility = View.GONE
        }

        btnClose.setOnClickListener { dialog.dismiss() }
        btnTutup.setOnClickListener { dialog.dismiss() }

        var isZoomed = false
        ivPhoto.setOnClickListener {
            if (ivPhoto.drawable == null) return@setOnClickListener
            isZoomed = !isZoomed
            if (isZoomed) {
                ivPhoto.scaleType = ImageView.ScaleType.CENTER_CROP
                tvHint.text = "Mode diperbesar • Ketuk lagi untuk ukuran normal"
            } else {
                ivPhoto.scaleType = ImageView.ScaleType.FIT_CENTER
                tvHint.text = "Ketuk foto untuk memperbesar / perkecil"
            }
        }

        pbLoading.visibility = View.VISIBLE
        tvError.visibility = View.GONE

        loadInto(
            context = context,
            imageView = ivPhoto,
            fotoBarang = fotoBarang,
            fotoUrl = fotoUrl,
            maxDimPx = 1280,
            onLoaded = {
                pbLoading.visibility = View.GONE
                tvError.visibility = View.GONE
            },
            onFailed = {
                pbLoading.visibility = View.GONE
                tvError.visibility = View.VISIBLE
            }
        )

        dialog.show()
        dialog.window?.setLayout(
            (context.resources.displayMetrics.widthPixels * 0.94).toInt(),
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
    }

    private fun fetchAndDecodeBitmap(url: String, maxDimPx: Int): Bitmap? {
        return try {
            val request = Request.Builder()
                .url(url)
                .header("ngrok-skip-browser-warning", "true")
                .build()
            imageHttpClient.newCall(request).execute().use { response ->
                if (!response.isSuccessful) return null
                val bytes = response.body?.bytes() ?: return null
                if (bytes.isEmpty()) return null

                val bounds = BitmapFactory.Options().apply {
                    inJustDecodeBounds = true
                }
                BitmapFactory.decodeByteArray(bytes, 0, bytes.size, bounds)

                var sampleSize = 1
                val maxSide = maxOf(bounds.outWidth, bounds.outHeight)
                if (maxSide > maxDimPx && maxDimPx > 0) {
                    while ((maxSide / sampleSize) > maxDimPx * 2) {
                        sampleSize *= 2
                    }
                }

                val decodeOpts = BitmapFactory.Options().apply {
                    inSampleSize = sampleSize
                }
                BitmapFactory.decodeByteArray(bytes, 0, bytes.size, decodeOpts)
            }
        } catch (_: Exception) {
            null
        }
    }
}
