package com.nyam0s1.ezoni

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.nyam0s1.ezoni.ui.farm.FarmApp
import com.nyam0s1.ezoni.ui.theme.EzoniTheme
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.runtime.Composable

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            EzoniTheme {
                EzoniRoot()
            }
        }
    }
}

@Composable
private fun EzoniRoot() {
    var showOfflinePreview by rememberSaveable { mutableStateOf(false) }

    if (showOfflinePreview) {
        FarmApp(onClosePreview = { showOfflinePreview = false })
    } else {
        EzoniWebApp(
            baseUrl = BuildConfig.EZONI_WEB_BASE_URL,
            onOpenOfflinePreview = { showOfflinePreview = true },
        )
    }
}
