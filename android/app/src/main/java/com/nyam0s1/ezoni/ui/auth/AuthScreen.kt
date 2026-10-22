package com.nyam0s1.ezoni.ui.auth

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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import com.nyam0s1.ezoni.R
import com.nyam0s1.ezoni.ui.theme.EzoniColors

@Composable
fun AuthScreen(onOpenPreview: () -> Unit) {
    var isRegistering by rememberSaveable { mutableStateOf(false) }
    var usernameOrEmail by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
    var feedback by remember { mutableStateOf<String?>(null) }

    Scaffold(
        containerColor = EzoniColors.Background,
        contentWindowInsets = WindowInsets.safeDrawing,
    ) { contentPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(contentPadding)
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 24.dp, vertical = 28.dp),
            verticalArrangement = Arrangement.spacedBy(18.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Spacer(Modifier.height(8.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.Center,
            ) {
                Image(
                    painter = painterResource(R.drawable.ezoni_logo),
                    contentDescription = "EZONI Farm logo",
                    modifier = Modifier
                        .size(68.dp)
                        .clip(CircleShape),
                )
                Spacer(Modifier.width(14.dp))
                Column {
                    Text(
                        text = "EZONI FARM",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = EzoniColors.Leaf,
                    )
                    Text(
                        text = "Farm management",
                        style = MaterialTheme.typography.bodyMedium,
                        color = EzoniColors.MutedText,
                    )
                }
            }

            Spacer(Modifier.height(12.dp))

            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(6.dp),
            ) {
                Text(
                    text = if (isRegistering) "Request an account" else "Welcome back",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = EzoniColors.Text,
                )
                Text(
                    text = if (isRegistering) {
                        "Create your details to request access to EZONI Farm."
                    } else {
                        "Sign in to manage your farm records."
                    },
                    style = MaterialTheme.typography.bodyLarge,
                    color = EzoniColors.MutedText,
                )
            }

            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EzoniColors.Surface),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(14.dp),
                ) {
                    if (isRegistering) {
                        EzoniTextField(
                            value = usernameOrEmail,
                            onValueChange = { usernameOrEmail = it; feedback = null },
                            label = "Username",
                        )
                        EzoniTextField(
                            value = email,
                            onValueChange = { email = it; feedback = null },
                            label = "Email address",
                            keyboardType = KeyboardType.Email,
                        )
                    } else {
                        EzoniTextField(
                            value = usernameOrEmail,
                            onValueChange = { usernameOrEmail = it; feedback = null },
                            label = "Username or email",
                        )
                    }

                    EzoniTextField(
                        value = password,
                        onValueChange = { password = it; feedback = null },
                        label = "Password",
                        passwordVisible = passwordVisible,
                        onTogglePassword = { passwordVisible = !passwordVisible },
                    )

                    if (isRegistering) {
                        EzoniTextField(
                            value = confirmPassword,
                            onValueChange = { confirmPassword = it; feedback = null },
                            label = "Confirm password",
                            passwordVisible = passwordVisible,
                            onTogglePassword = { passwordVisible = !passwordVisible },
                        )
                    }

                    feedback?.let { message ->
                        Text(
                            text = message,
                            style = MaterialTheme.typography.bodySmall,
                            color = if (message.startsWith("Please") || message.startsWith("Passwords")) {
                                EzoniColors.Error
                            } else {
                                EzoniColors.Leaf
                            },
                        )
                    }

                    Button(
                        onClick = {
                            feedback = if (isRegistering) {
                                when {
                                    usernameOrEmail.isBlank() || email.isBlank() ||
                                        password.isBlank() || confirmPassword.isBlank() ->
                                        "Please complete all fields."

                                    password != confirmPassword ->
                                        "Passwords do not match."

                                    else ->
                                        "The registration screen is ready. Server connection will be added next."
                                }
                            } else if (usernameOrEmail.isBlank() || password.isBlank()) {
                                "Please enter your username and password."
                            } else {
                                "The sign-in screen is ready. Server connection will be added next."
                            }
                        },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(52.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = EzoniColors.Leaf,
                            contentColor = EzoniColors.Background,
                        ),
                    ) {
                        Text(
                            text = if (isRegistering) "Request access" else "Sign in",
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
            }

            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    text = if (isRegistering) "Already have an account?" else "New to EZONI?",
                    style = MaterialTheme.typography.bodyMedium,
                    color = EzoniColors.MutedText,
                )
                TextButton(
                    onClick = {
                        isRegistering = !isRegistering
                        feedback = null
                    },
                ) {
                    Text(
                        text = if (isRegistering) "Sign in" else "Request an account",
                        color = EzoniColors.Leaf,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
            }

            Text(
                text = "Mobile authentication is not connected to the PHP system yet.",
                style = MaterialTheme.typography.bodySmall,
                color = EzoniColors.MutedText,
            )

            TextButton(onClick = onOpenPreview) {
                Text(
                    text = "Preview farm screens (offline)",
                    color = EzoniColors.Leaf,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        }
    }
}

@Composable
private fun EzoniTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    keyboardType: KeyboardType = KeyboardType.Text,
    passwordVisible: Boolean = true,
    onTogglePassword: (() -> Unit)? = null,
) {
    val isPassword = label.contains("password", ignoreCase = true)

    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        singleLine = true,
        keyboardOptions = KeyboardOptions(
            keyboardType = if (isPassword) KeyboardType.Password else keyboardType,
        ),
        visualTransformation = if (isPassword && !passwordVisible) {
            PasswordVisualTransformation()
        } else {
            VisualTransformation.None
        },
        trailingIcon = if (isPassword && onTogglePassword != null) {
            {
                TextButton(onClick = onTogglePassword) {
                    Text(if (passwordVisible) "Hide" else "Show")
                }
            }
        } else {
            null
        },
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = EzoniColors.Leaf,
            unfocusedBorderColor = EzoniColors.MutedText.copy(alpha = 0.45f),
            focusedLabelColor = EzoniColors.Leaf,
            cursorColor = EzoniColors.Leaf,
        ),
    )
}
