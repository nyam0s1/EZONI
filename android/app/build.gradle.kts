plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.compose.compiler)
}

val ezoniWebBaseUrl = providers.gradleProperty("ezoniWebBaseUrl")
    .orElse("https://ezoni-farm.vercel.app/")
    .get()

android {
    namespace = "com.nyam0s1.ezoni"
    compileSdk = 37

    defaultConfig {
        applicationId = "com.nyam0s1.ezoni"
        minSdk = 26
        targetSdk = 37
        versionCode = 1
        versionName = "1.0"
        buildConfigField("String", "EZONI_WEB_BASE_URL", "\"$ezoniWebBaseUrl\"")
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlin {
        compilerOptions {
            jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
        }
    }
}

dependencies {
    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.activity.compose)
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.material.icons.extended)
    debugImplementation(libs.androidx.compose.ui.tooling)
}
