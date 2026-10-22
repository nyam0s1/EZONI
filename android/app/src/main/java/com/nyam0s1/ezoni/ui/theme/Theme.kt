package com.nyam0s1.ezoni.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable

private val EzoniDarkColorScheme = darkColorScheme(
    primary = EzoniColors.Leaf,
    onPrimary = EzoniColors.Background,
    background = EzoniColors.Background,
    onBackground = EzoniColors.Text,
    surface = EzoniColors.Surface,
    onSurface = EzoniColors.Text,
    secondary = EzoniColors.Leaf,
    onSecondary = EzoniColors.Background,
)

@Composable
fun EzoniTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = EzoniDarkColorScheme,
        content = content,
    )
}
