# Niageo Ops — mobile app

Flutter app for NiageoOps. Phase 1 is invoicing: the pipeline at a glance, invoices
(create, edit drafts, send, record payments, share the PDF) and clients. Phase 2 is
operations: uptime monitoring (run a check from the phone), subscription bills (mark paid)
and recurring billing plans (generate the next invoice early). Reports, scopes and the
rest of the web app come in later phases.

It talks to the Laravel app through the token API under `/api/v1` (see `routes/api.php`
and `app/Http/Controllers/Api`). Sign in with the same email and password as the web app.

## Run it

Requirements: Flutter 3.47+ (stable), Xcode for iOS, Android Studio / SDK for Android.

```bash
cd mobile
flutter pub get
flutter run                 # a connected device or simulator
```

The sign-in screen has a **Server** field; it defaults to `https://niageo-io.on-forge.com`
(change `AppConfig.defaultBaseUrl` in `lib/core/config.dart` for another default). The
address is remembered on the device.

Checks:

```bash
flutter analyze
flutter test
dart format lib test
```

## Build for devices

```bash
flutter build apk --release          # Android, build/app/outputs/flutter-apk/app-release.apk
flutter build appbundle --release    # Play Store
flutter build ipa --release          # iOS (needs signing set up in Xcode)
```

## How it is put together

```
lib/
  app.dart, main.dart, router.dart, shell.dart   app entry, go_router routes, bottom tabs
  core/
    api/        Dio client with the bearer token, one ApiException type for the UI
    storage/    session in the platform keystore (flutter_secure_storage)
    theme.dart  the web app's palette, Material 3
    widgets/    status badge, stat tile, section card, sheets, empty/error states
  features/
    auth/        sign-in, session restore, expiry handling
    dashboard/   pipeline screen, "needs attention" card
    invoices/    list (filters, search, paging), detail (actions, payments, PDF share),
                 editor (InvoiceDraft keeps the form state and totals)
    clients/     list, detail, picker and quick-create sheets
    products/    catalogue for one-tap line items
    operations/  Ops hub, uptime list + site detail (check now), subscriptions (mark paid),
                 billing plans list + detail (generate now, pause/resume/end)
    settings/
```

State is Riverpod. Server data lives in `FutureProvider`s that watch `refreshTickProvider`;
anything that changes data bumps the tick, so every screen refetches on its own.
Models are hand-written (`fromJson`), no code generation to run.

## API used

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/api/v1/auth/login` | email, password, device_name → token |
| POST | `/api/v1/auth/logout` | revoke the device token |
| GET | `/api/v1/dashboard` | metrics, pipeline by status, recent and overdue invoices, operations summary |
| GET/POST | `/api/v1/clients`, `/api/v1/clients/{id}` | list, detail (presets, contacts, recent invoices), quick create |
| GET | `/api/v1/products` | active products |
| GET/POST | `/api/v1/invoices` | paged list (`status`, `client_id`, `search`), create |
| GET/PUT/DELETE | `/api/v1/invoices/{id}` | detail, edit draft, delete draft/cancelled |
| POST | `/api/v1/invoices/{id}/transition` | `status`: sent, paid, overdue, cancelled, draft |
| POST | `/api/v1/invoices/{id}/payments` | record a payment (auto-marks paid when settled) |
| DELETE | `/api/v1/payments/{id}` | remove a payment |
| POST | `/api/v1/invoices/{id}/duplicate` | copy as a new draft |
| GET | `/api/v1/invoices/{id}/pdf` | the branded PDF |
| GET | `/api/v1/uptime`, `/api/v1/uptime/{id}` | monitored sites with summary; detail with 24h stats and recent checks |
| POST | `/api/v1/uptime/{id}/check` | ping the site now |
| GET | `/api/v1/subscriptions` | bills with summary (statuses refreshed) |
| POST | `/api/v1/subscriptions/{id}/mark-paid` | mark paid; auto-renewing bills advance to the next due date |
| GET | `/api/v1/billing-plans`, `/api/v1/billing-plans/{id}` | plans with summary; detail with upcoming periods, items, invoices |
| POST | `/api/v1/billing-plans/{id}/generate` | create the next period's draft invoice |
| POST | `/api/v1/billing-plans/{id}/status` | `status`: active, paused, ended |
