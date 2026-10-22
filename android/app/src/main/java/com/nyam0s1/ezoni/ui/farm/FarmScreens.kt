package com.nyam0s1.ezoni.ui.farm

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Spa
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.nyam0s1.ezoni.ui.theme.EzoniColors

@Composable
internal fun OverviewScreen(onNavigate: (FarmDestination) -> Unit) {
    Column(
        modifier = Modifier
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 10.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(
                text = "Farm overview",
                style = MaterialTheme.typography.headlineSmall,
                color = EzoniColors.Text,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = "Your day-to-day farm activity in one place.",
                style = MaterialTheme.typography.bodyMedium,
                color = EzoniColors.MutedText,
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            MetricCard("—", "Active livestock", Modifier.weight(1f))
            MetricCard("—", "Open alerts", Modifier.weight(1f))
        }
        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            MetricCard("—", "Inventory items", Modifier.weight(1f))
            MetricCard("—", "Sales today", Modifier.weight(1f))
        }

        ConnectionCard()

        Text(
            text = "Farm areas",
            style = MaterialTheme.typography.titleMedium,
            color = EzoniColors.Text,
            fontWeight = FontWeight.SemiBold,
        )
        FarmAreaRow("Livestock", "Animals, status, and lineage", onClick = { onNavigate(FarmDestination.LIVESTOCK) })
        FarmAreaRow("Inventory", "Farm inputs and stock levels", onClick = { onNavigate(FarmDestination.INVENTORY) })
        FarmAreaRow("Produce", "Harvests and sales", onClick = { onNavigate(FarmDestination.PRODUCE) })
    }
}

@Composable
internal fun ModuleScreen(title: String, description: String, details: List<String>) {
    Column(
        modifier = Modifier
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 10.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(
                text = title,
                style = MaterialTheme.typography.headlineSmall,
                color = EzoniColors.Text,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = description,
                style = MaterialTheme.typography.bodyMedium,
                color = EzoniColors.MutedText,
            )
        }

        ConnectionCard()

        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = EzoniColors.Surface),
            shape = RoundedCornerShape(18.dp),
        ) {
            Column(
                modifier = Modifier.padding(18.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Text(
                    text = "What this area will cover",
                    style = MaterialTheme.typography.titleSmall,
                    color = EzoniColors.Leaf,
                    fontWeight = FontWeight.Bold,
                )
                details.forEach { detail ->
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("•", color = EzoniColors.Leaf)
                        Spacer(Modifier.width(10.dp))
                        Text(detail, color = EzoniColors.Text, style = MaterialTheme.typography.bodyMedium)
                    }
                }
            }
        }
    }
}

@Composable
internal fun MoreScreen(onNavigate: (FarmDestination) -> Unit) {
    Column(
        modifier = Modifier
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 10.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Text(
            text = "More",
            style = MaterialTheme.typography.headlineSmall,
            color = EzoniColors.Text,
            fontWeight = FontWeight.SemiBold,
        )
        Text(
            text = "Additional farm tools and account areas.",
            style = MaterialTheme.typography.bodyMedium,
            color = EzoniColors.MutedText,
        )
        FarmAreaRow("Produce", "Harvest and sales records", Icons.Default.Spa) { onNavigate(FarmDestination.PRODUCE) }
        FarmAreaRow("Reports", "Income, costs, and production", Icons.Default.Assessment) { onNavigate(FarmDestination.REPORTS) }
        FarmAreaRow("Alerts", "Low-stock and farm notices", Icons.Default.Notifications) { onNavigate(FarmDestination.ALERTS) }
        FarmAreaRow("Profile", "Account details and preferences", Icons.Default.Person) { onNavigate(FarmDestination.PROFILE) }
    }
}

@Composable
private fun MetricCard(value: String, label: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier.height(104.dp),
        colors = CardDefaults.cardColors(containerColor = EzoniColors.Surface),
        shape = RoundedCornerShape(18.dp),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.SpaceBetween,
        ) {
            Text(value, style = MaterialTheme.typography.headlineSmall, color = EzoniColors.Leaf, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.bodySmall, color = EzoniColors.MutedText)
        }
    }
}

@Composable
private fun FarmAreaRow(
    title: String,
    subtitle: String,
    icon: ImageVector? = null,
    onClick: () -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        colors = CardDefaults.cardColors(containerColor = EzoniColors.Surface),
        shape = RoundedCornerShape(16.dp),
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 15.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            if (icon != null) {
                Icon(icon, contentDescription = null, tint = EzoniColors.Leaf)
                Spacer(Modifier.width(12.dp))
            }
            Column(modifier = Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall, color = EzoniColors.Text, fontWeight = FontWeight.SemiBold)
                Text(subtitle, style = MaterialTheme.typography.bodySmall, color = EzoniColors.MutedText)
            }
            Icon(Icons.Default.ChevronRight, contentDescription = "Open $title", tint = EzoniColors.MutedText)
        }
    }
}

@Composable
private fun ConnectionCard() {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EzoniColors.Surface),
        shape = RoundedCornerShape(18.dp),
    ) {
        Column(
            modifier = Modifier.padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(7.dp),
        ) {
            Text(
                text = "Waiting for server connection",
                style = MaterialTheme.typography.titleSmall,
                color = EzoniColors.Leaf,
                fontWeight = FontWeight.Bold,
            )
            Text(
                text = "Live counts and farm records will appear here when the mobile app is connected to EZONI's PHP system.",
                style = MaterialTheme.typography.bodySmall,
                color = EzoniColors.MutedText,
            )
        }
    }
}
