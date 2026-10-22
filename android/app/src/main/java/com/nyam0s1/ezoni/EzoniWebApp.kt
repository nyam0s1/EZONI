package com.nyam0s1.ezoni

import android.app.AlertDialog
import android.content.ActivityNotFoundException
import android.content.Intent
import android.net.Uri
import android.net.http.SslError
import android.webkit.CookieManager
import android.webkit.JsPromptResult
import android.webkit.JsResult
import android.webkit.SslErrorHandler
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.BackHandler
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import com.nyam0s1.ezoni.ui.theme.EzoniColors

@Composable
internal fun EzoniWebApp(
    baseUrl: String,
    onOpenOfflinePreview: () -> Unit,
) {
    val context = LocalContext.current
    var loadError by remember(baseUrl) { mutableStateOf<String?>(null) }
    var canGoBack by remember(baseUrl) { mutableStateOf(false) }
    val currentLoadError by rememberUpdatedState<(String) -> Unit>({ loadError = it })
    val currentHistoryChanged by rememberUpdatedState<(Boolean) -> Unit>({ canGoBack = it })
    val startUrl = remember(baseUrl) { baseUrl.trim().let { if (it.endsWith('/')) it else "$it/" } }
    val webView = remember(context, startUrl) {
        WebView(context).apply {
            setBackgroundColor(android.graphics.Color.rgb(14, 17, 23))
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.cacheMode = WebSettings.LOAD_DEFAULT
            settings.mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
            settings.allowFileAccess = false
            settings.allowContentAccess = false
            settings.javaScriptCanOpenWindowsAutomatically = false
            settings.setSupportMultipleWindows(false)
            CookieManager.getInstance().setAcceptCookie(true)
            CookieManager.getInstance().setAcceptThirdPartyCookies(this, false)

            webChromeClient = EzoniWebChromeClient()
            webViewClient = EzoniWebViewClient(
                expectedHost = Uri.parse(startUrl).host.orEmpty(),
                onLoadError = { currentLoadError(it) },
                onHistoryChanged = { currentHistoryChanged(it) },
            )
            loadUrl(startUrl)
        }
    }

    DisposableEffect(webView) {
        onDispose {
            webView.stopLoading()
            webView.destroy()
        }
    }

    BackHandler(enabled = canGoBack) {
        if (webView.canGoBack()) {
            webView.goBack()
        }
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .safeDrawingPadding()
            .background(EzoniColors.Background),
    ) {
        AndroidView(
            factory = { webView },
            modifier = Modifier.fillMaxSize(),
        )

        loadError?.let { message ->
            WebAppError(
                message = message,
                onRetry = {
                    loadError = null
                    webView.loadUrl(startUrl)
                },
                onOpenOfflinePreview = onOpenOfflinePreview,
            )
        }
    }
}

@Composable
private fun WebAppError(
    message: String,
    onRetry: () -> Unit,
    onOpenOfflinePreview: () -> Unit,
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(EzoniColors.Background)
            .padding(horizontal = 28.dp, vertical = 32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Text(
            text = "EZONI FARM",
            style = MaterialTheme.typography.headlineSmall,
            color = EzoniColors.Leaf,
            fontWeight = FontWeight.Bold,
        )
        Spacer(Modifier.height(18.dp))
        Text(
            text = "Couldn’t connect to EZONI Farm",
            style = MaterialTheme.typography.titleLarge,
            color = EzoniColors.Text,
            fontWeight = FontWeight.SemiBold,
            textAlign = TextAlign.Center,
        )
        Spacer(Modifier.height(10.dp))
        Text(
            text = message,
            style = MaterialTheme.typography.bodyMedium,
            color = EzoniColors.MutedText,
            textAlign = TextAlign.Center,
        )
        Spacer(Modifier.height(24.dp))
        Button(
            onClick = onRetry,
            shape = RoundedCornerShape(10.dp),
            colors = ButtonDefaults.buttonColors(
                containerColor = EzoniColors.Leaf,
                contentColor = EzoniColors.Background,
            ),
        ) {
            Text("Try again", fontWeight = FontWeight.Bold)
        }
        TextButton(onClick = onOpenOfflinePreview) {
            Text("Open offline preview", color = EzoniColors.Leaf)
        }
    }
}

private class EzoniWebViewClient(
    private val expectedHost: String,
    private val onLoadError: (String) -> Unit,
    private val onHistoryChanged: (Boolean) -> Unit,
) : WebViewClient() {
    override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
        val uri = request.url
        val isSameHost = uri.host.equals(expectedHost, ignoreCase = true)

        if (isSameHost && uri.scheme.equals("https", ignoreCase = true)) {
            return false
        }

        if (isSameHost && uri.scheme.equals("http", ignoreCase = true)) {
            onLoadError("An insecure link was blocked. EZONI Farm must be opened over HTTPS.")
            return true
        }

        if (uri.scheme.equals("https", ignoreCase = true) ||
            uri.scheme.equals("mailto", ignoreCase = true) ||
            uri.scheme.equals("tel", ignoreCase = true)
        ) {
            try {
                view.context.startActivity(Intent(Intent.ACTION_VIEW, uri))
            } catch (_: ActivityNotFoundException) {
                onLoadError("No app is available to open this link.")
            }
        }
        return true
    }

    override fun onPageStarted(view: WebView, url: String?, favicon: android.graphics.Bitmap?) {
        onHistoryChanged(view.canGoBack())
    }

    override fun onPageFinished(view: WebView, url: String?) {
        onHistoryChanged(view.canGoBack())
    }

    override fun onReceivedError(
        view: WebView,
        request: WebResourceRequest,
        error: WebResourceError,
    ) {
        if (request.isForMainFrame) {
            onLoadError("Check your connection and try again. ${error.description}")
        }
        onHistoryChanged(view.canGoBack())
    }

    override fun onReceivedSslError(view: WebView, handler: SslErrorHandler, error: SslError) {
        handler.cancel()
        if (Uri.parse(error.url).host.equals(expectedHost, ignoreCase = true)) {
            onLoadError("The site’s HTTPS certificate could not be verified. Renew the certificate, then try again.")
        }
    }
}

private class EzoniWebChromeClient : WebChromeClient() {
    override fun onJsAlert(view: WebView?, url: String?, message: String?, result: JsResult?): Boolean {
        if (view == null || result == null) return false
        AlertDialog.Builder(view.context)
            .setTitle("EZONI Farm")
            .setMessage(message.orEmpty())
            .setPositiveButton(android.R.string.ok) { _, _ -> result.confirm() }
            .setOnCancelListener { result.cancel() }
            .show()
        return true
    }

    override fun onJsConfirm(
        view: WebView?,
        url: String?,
        message: String?,
        result: JsResult?,
    ): Boolean {
        if (view == null || result == null) return false
        AlertDialog.Builder(view.context)
            .setTitle("EZONI Farm")
            .setMessage(message.orEmpty())
            .setPositiveButton("Continue") { _, _ -> result.confirm() }
            .setNegativeButton("Cancel") { _, _ -> result.cancel() }
            .setOnCancelListener { result.cancel() }
            .show()
        return true
    }

    override fun onJsPrompt(
        view: WebView?,
        url: String?,
        message: String?,
        defaultValue: String?,
        result: JsPromptResult?,
    ): Boolean = false
}
