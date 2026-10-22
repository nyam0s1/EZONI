# EZONI Farm for Android

The Android app opens the existing EZONI Farm PHP site inside Android WebView. This keeps its current pages, styling, login flow, role-based navigation, database-backed records, and JavaScript interactions in sync with the web app instead of maintaining a second copy of those features in native screens.

The default site address is `https://ezoni.rf.gd/`. To build against a Vercel deployment or the separate Android deployment, pass the address assigned to that Vercel project as a Gradle property:

```powershell
.\gradlew.bat assembleDebug -PezoniWebBaseUrl=https://your-farm-site.example/
```

The WebView enables JavaScript and DOM storage for the existing app, preserves first-party PHP session cookies, keeps links on the configured HTTPS host inside the app, and opens external HTTPS, email, and phone links with their normal Android handlers. It rejects cleartext traffic and cancels unverified TLS connections; renew the server certificate if the app displays a certificate error. The previous Compose farm preview remains available from the connection error screen for offline viewing.

## Build a debug APK

On Windows, run `gradlew.bat assembleDebug` from this folder. The generated APK is saved to `app/build/outputs/apk/debug/app-debug.apk`. A signed release build has not been configured yet.
