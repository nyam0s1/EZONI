package com.nyam0s1.ezoni.ui.farm

import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.MoreHoriz
import androidx.compose.material.icons.filled.Pets
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.nyam0s1.ezoni.R
import com.nyam0s1.ezoni.ui.theme.EzoniColors

internal enum class FarmDestination(val label: String) {
    OVERVIEW("Overview"),
    LIVESTOCK("Livestock"),
    INVENTORY("Inventory"),
    MORE("More"),
    PRODUCE("Produce"),
    REPORTS("Reports"),
    ALERTS("Alerts"),
    PROFILE("Profile"),
}

private val mainDestinations = listOf(
    FarmDestination.OVERVIEW,
    FarmDestination.LIVESTOCK,
    FarmDestination.INVENTORY,
    FarmDestination.MORE,
)

@Composable
fun FarmApp(onClosePreview: () -> Unit) {
    var destinationName by rememberSaveable { mutableStateOf(FarmDestination.OVERVIEW.name) }
    val destination = remember(destinationName) { FarmDestination.valueOf(destinationName) }

    Scaffold(
        containerColor = EzoniColors.Background,
        contentWindowInsets = WindowInsets.safeDrawing,
        bottomBar = {
            NavigationBar(containerColor = EzoniColors.Surface) {
                mainDestinations.forEach { item ->
                    NavigationBarItem(
                        selected = isSelected(item, destination),
                        onClick = { destinationName = item.name },
                        icon = {
                            Icon(
                                imageVector = when (item) {
                                    FarmDestination.OVERVIEW -> Icons.Default.Home
                                    FarmDestination.LIVESTOCK -> Icons.Default.Pets
                                    FarmDestination.INVENTORY -> Icons.Default.Inventory2
                                    else -> Icons.Default.MoreHoriz
                                },
                                contentDescription = item.label,
                            )
                        },
                        label = { Text(item.label) },
                    )
                }
            }
        },
    ) { contentPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(contentPadding),
        ) {
            FarmHeader(
                title = if (destination == FarmDestination.OVERVIEW) "EZONI FARM" else destination.label,
                onClosePreview = onClosePreview,
            )

            when (destination) {
                FarmDestination.OVERVIEW -> OverviewScreen(onNavigate = { destinationName = it.name })
                FarmDestination.LIVESTOCK -> ModuleScreen(
                    title = "Livestock",
                    description = "Keep animal records, lineage, quantities, and status together.",
                    details = listOf("Animal register", "Status and history", "Lineage records"),
                )
                FarmDestination.INVENTORY -> ModuleScreen(
                    title = "Inventory",
                    description = "Track farm inputs, available quantities, minimum levels, and usage.",
                    details = listOf("Stock register", "Restock and usage history", "Low-stock thresholds"),
                )
                FarmDestination.PRODUCE -> ModuleScreen(
                    title = "Produce",
                    description = "Manage harvested produce, available stock, and sales activity.",
                    details = listOf("Produce register", "Harvest records", "Sales history"),
                )
                FarmDestination.REPORTS -> ModuleScreen(
                    title = "Reports",
                    description = "Review sales, costs, production, and farm performance over time.",
                    details = listOf("Income and expenses", "Production summaries", "Date-filtered reports"),
                )
                FarmDestination.ALERTS -> ModuleScreen(
                    title = "Alerts",
                    description = "See livestock, inventory, and produce records that need attention.",
                    details = listOf("Low inventory", "Low produce", "Livestock thresholds"),
                )
                FarmDestination.PROFILE -> ModuleScreen(
                    title = "Profile",
                    description = "Account details and personal settings will appear here after sign-in is connected.",
                    details = listOf("Account details", "Password and preferences"),
                )
                FarmDestination.MORE -> MoreScreen(onNavigate = { destinationName = it.name })
            }
        }
    }
}

@Composable
private fun FarmHeader(title: String, onClosePreview: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 20.dp, vertical = 14.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Image(
            painter = painterResource(R.drawable.ezoni_logo),
            contentDescription = "EZONI Farm logo",
            modifier = Modifier
                .size(42.dp)
                .clip(CircleShape),
        )
        Spacer(Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = EzoniColors.Text,
            )
            Text(
                text = "Offline layout preview",
                style = MaterialTheme.typography.bodySmall,
                color = EzoniColors.MutedText,
            )
        }
        androidx.compose.material3.TextButton(onClick = onClosePreview) {
            Text("Exit", color = EzoniColors.Leaf)
        }
    }
}

private fun isSelected(item: FarmDestination, current: FarmDestination): Boolean = when (item) {
    FarmDestination.MORE -> current in setOf(
        FarmDestination.MORE,
        FarmDestination.PRODUCE,
        FarmDestination.REPORTS,
        FarmDestination.ALERTS,
        FarmDestination.PROFILE,
    )
    else -> item == current
}
