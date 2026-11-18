package com.example.aplikasi_inventaris_dmk.network

import android.content.Context
import android.net.Uri
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import org.json.JSONObject
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
    const val DEFAULT_HOST_IP = "192.168.68.117"
    const val DEFAULT_APACHE_URL = "http://$DEFAULT_HOST_IP/INVENTARIS-DMK/public/api/"
    const val DEFAULT_ARTISAN_URL = "http://$DEFAULT_HOST_IP:8000/api/"
    const val LOCALHOST_USB_URL = "http://127.0.0.1:8000/api/"
    const val DEFAULT_BASE_URL = DEFAULT_APACHE_URL

    private const val PREF_NAME = "ApiClientPrefs"
    private const val KEY_BASE_URL = "base_url"
    private const val KEY_CUSTOM_LOCKED = "custom_locked"

    @Volatile
    private var currentBaseUrl: String = DEFAULT_BASE_URL

    @Volatile
    private var isCustomSaved: Boolean = false

    @Volatile
    private var cachedUrl: String? = null

    @Volatile
    private var cachedService: ApiService? = null

    private val okHttpClient: OkHttpClient by lazy {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
        }

        OkHttpClient.Builder()
            .addInterceptor { chain ->
                val req = chain.request().newBuilder()
                    .header("Accept", "application/json")
                    .header("ngrok-skip-browser-warning", "true")
                    .build()
                chain.proceed(req)
            }
            .addInterceptor(logging)
            .connectTimeout(6, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .build()
    }

    fun init(context: Context) {
        val prefs = context.applicationContext.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        val saved = prefs.getString(KEY_BASE_URL, null)
        isCustomSaved = prefs.getBoolean(KEY_CUSTOM_LOCKED, false)
        currentBaseUrl = if (!saved.isNullOrBlank()) normalizeBaseUrl(saved) else DEFAULT_BASE_URL
    }

    val instance: ApiService
        get() {
            val url = currentBaseUrl
            val existing = cachedService
            if (existing != null && cachedUrl == url) {
                return existing
            }
            synchronized(this) {
                val recheck = cachedService
                if (recheck != null && cachedUrl == currentBaseUrl) {
                    return recheck
                }
                val created = buildServiceForUrl(currentBaseUrl)
                cachedUrl = currentBaseUrl
                cachedService = created
                return created
            }
        }

    fun buildServiceForUrl(baseUrl: String): ApiService {
        val normalized = normalizeBaseUrl(baseUrl)
        val retrofit = Retrofit.Builder()
            .baseUrl(normalized)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
        return retrofit.create(ApiService::class.java)
    }

    fun getBaseUrl(context: Context? = null): String {
        if (context != null) {
            init(context)
        }
        return currentBaseUrl
    }

    fun isCustomUrlLocked(context: Context? = null): Boolean {
        if (context != null) {
            init(context)
        }
        return isCustomSaved
    }

    fun setBaseUrl(context: Context, rawUrl: String, markAsCustom: Boolean = true): String {
        val normalized = normalizeBaseUrl(rawUrl)
        currentBaseUrl = normalized
        isCustomSaved = markAsCustom
        cachedUrl = null
        cachedService = null
        context.applicationContext.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .edit()
            .putString(KEY_BASE_URL, normalized)
            .putBoolean(KEY_CUSTOM_LOCKED, markAsCustom)
            .apply()
        return normalized
    }

    fun getAlternateDefaultUrl(url: String = currentBaseUrl): String? {
        return try {
            val parsed = Uri.parse(url)
            val host = parsed.host ?: return null
            val scheme = parsed.scheme ?: "http"
            if (url.contains("/INVENTARIS-DMK/public", ignoreCase = true)) {
                "$scheme://$host:8000/api/"
            } else if (parsed.port == 8000 && host != "127.0.0.1" && host != "localhost") {
                "$scheme://$host/INVENTARIS-DMK/public/api/"
            } else {
                null
            }
        } catch (_: Exception) {
            null
        }
    }

    fun getDisplayHost(url: String = currentBaseUrl): String {
        return try {
            val parsed = Uri.parse(url)
            val host = parsed.host ?: return url
            val port = parsed.port
            val isApache = url.contains("/INVENTARIS-DMK/public", ignoreCase = true)
            when {
                port > 0 && port != 80 && port != 443 -> "$host:$port"
                isApache -> "$host (Apache)"
                else -> host
            }
        } catch (_: Exception) {
            url
        }
    }

    fun extractHostIp(url: String = currentBaseUrl): String {
        return try {
            Uri.parse(url).host?.takeIf { it.isNotBlank() } ?: DEFAULT_HOST_IP
        } catch (_: Exception) {
            DEFAULT_HOST_IP
        }
    }

    fun normalizeBaseUrl(rawInput: String, preferArtisanPortIfNoPath: Boolean = false): String {
        var cleaned = rawInput.trim()
        if (cleaned.isEmpty()) return DEFAULT_BASE_URL

        if (!cleaned.startsWith("http://", ignoreCase = true) &&
            !cleaned.startsWith("https://", ignoreCase = true)
        ) {
            cleaned = "http://$cleaned"
        }

        val uri = try {
            Uri.parse(cleaned)
        } catch (_: Exception) {
            return DEFAULT_BASE_URL
        }

        val scheme = uri.scheme ?: "http"
        val host = uri.host ?: return DEFAULT_BASE_URL
        val port = uri.port
        val rawPath = (uri.path ?: "").trim()

        val authority = if (port > 0) "$host:$port" else host

        val finalPath = when {
            rawPath.isEmpty() || rawPath == "/" -> {
                if (port == 8000) {
                    "/api/"
                } else if (preferArtisanPortIfNoPath && port <= 0) {
                    return "$scheme://$host:8000/api/"
                } else if (host == "127.0.0.1" || host == "localhost") {
                    "/api/"
                } else {
                    "/INVENTARIS-DMK/public/api/"
                }
            }
            rawPath.endsWith("/api", ignoreCase = true) -> "$rawPath/"
            rawPath.endsWith("/api/", ignoreCase = true) -> rawPath
            else -> "${rawPath.trimEnd('/')}/api/"
        }

        val normalizedPath = if (finalPath.startsWith("/")) finalPath else "/$finalPath"
        return "$scheme://$authority$normalizedPath"
    }

    fun parseErrorJson(rawErrorBody: String): JSONObject? {
        if (rawErrorBody.isBlank()) return null
        return try {
            JSONObject(rawErrorBody)
        } catch (_: Exception) {
            null
        }
    }

    fun extractErrorMessage(response: Response<*>, fallback: String): String {
        val raw = try {
            response.errorBody()?.string() ?: ""
        } catch (_: Exception) {
            ""
        }
        val msg = parseErrorJson(raw)?.optString("message", "")?.trim()
        return if (!msg.isNullOrBlank()) msg else fallback
    }
}
