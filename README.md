# EZONI Farm for Android

The Android app opens the EZONI Farm web application in Android WebView, preserving its pages, styling, login, and farm workflows. Android 8.0 or newer is required.

## Repository branches

* `main`: Stable web application.
* `web`: Web application source, kept aligned with `main`.
* `android`: Android app source in the `android/` folder.

Open the `android/` folder in Android Studio. See [the Android README](android/README.md) for build details.

## Build a debug APK

From the `android/` folder on Windows:

```powershell
.\gradlew.bat assembleDebug
```

The APK is written to `android/app/build/outputs/apk/debug/app-debug.apk`. A release signing configuration has not been added.

## Connection and offline preview

The app connects to [EZONI Farm](https://ezoni-farm.vercel.app/). Login, farm records, reports, and saving changes require an internet connection. The connection error screen offers an optional static offline preview with placeholder screens. It does not store farm records or synchronize offline changes.
