# Trusted database certificates

`cacert.pem` is the public Mozilla CA bundle distributed by curl at
https://curl.se/ca/cacert.pem, downloaded on 2026-10-22. Its certificate data was
generated on 2026-10-20. The bundle includes its source and license notices.

The connection factory uses this as a fallback when a system CA bundle is not
available, so both the Vercel runtime and local tools can verify hosted database
certificates. `DB_SSL_CA` can select a different trusted provider CA file.

Refresh it from the same HTTPS source when maintaining the runtime. Never replace
certificate verification with a disabled TLS check.
