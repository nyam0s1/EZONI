# EZONI Farm for Android

The Android app opens the EZONI Farm site inside Android WebView. It uses the same pages, styling, login flow, role navigation, database records, and JavaScript interactions as the web app. The web source is maintained on the repository's `main` and `web` branches.

## Open in Android Studio

Open this `android/` folder as the project and allow Gradle to synchronize. The app requires Android 8.0 or newer.

## Build a debug APK

On Windows, run from this folder:

```powershell
.\gradlew.bat assembleDebug
```

The generated APK is saved to `app/build/outputs/apk/debug/app-debug.apk`. A signed release build has not been configured yet.

The app uses [EZONI Farm](https://ezoni-farm.vercel.app/) by default. To use another HTTPS instance, pass the `ezoniWebBaseUrl` Gradle property:

```powershell
.\gradlew.bat assembleDebug -PezoniWebBaseUrl=https://your-farm.example/
```

## Connection and offline preview

The WebView enables JavaScript and DOM storage, preserves first-party session cookies, keeps links on the configured HTTPS host inside the app, and opens external HTTPS, email, and phone links with their normal Android handlers. It rejects cleartext traffic and unverified TLS connections.

Login, live farm records, reports, and saving changes require internet. The connection error screen offers a static Compose preview with placeholder screens. The preview does not store farm records or synchronize changes.
