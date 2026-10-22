# EZONI Farm for Android

The Android app opens the EZONI Farm PHP site inside Android WebView. It uses the same pages, styling, login flow, role navigation, database records and JavaScript interactions as the web app. The Vercel setup preserves those existing screens and workflows.

Build against a verified Vercel HTTPS deployment by passing its address as the `ezoniWebBaseUrl` Gradle property. The production address is configured after deployment; do not assume a placeholder is a working server.

```powershell
.\gradlew.bat assembleDebug -PezoniWebBaseUrl=https://your-verified-deployment.example/
```

The Vercel project uses Neon PostgreSQL through its `DATABASE_URL` environment variable. Initialize the fresh database with [`database/initialize.php`](../database/initialize.php), using `DATABASE_URL` and `INITIAL_ADMIN_*` from a trusted terminal with PHP's `pdo_pgsql` extension. A hosted MySQL-compatible database is also supported through `DB_*` settings. See [`database/README.md`](../database/README.md) for setup details.

No old farm records or accounts were recovered. Starter categories and species are editable through **Admin > Settings**. To keep Android records independent from the web installation, initialize a separate database, connect a separate Vercel project to it over TLS, and build with that project's HTTPS address.

The WebView enables JavaScript and DOM storage, preserves first-party PHP session cookies, keeps links on the configured HTTPS host inside the app, and opens external HTTPS, email and phone links with their normal Android handlers. PHP sessions are stored in the deployment's database. The WebView rejects cleartext traffic and cancels unverified TLS connections. The Compose farm preview remains available from the connection error screen for offline viewing.

## Build a debug APK

On Windows, run the Gradle command above from this folder with the verified deployment address. The generated APK is saved to `app/build/outputs/apk/debug/app-debug.apk`. A signed release build has not been configured yet.
