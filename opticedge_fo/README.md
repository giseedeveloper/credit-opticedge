# Opticedge FO — Field Officer Mobile App

A modern Flutter KYC mobile app for Field Officers to register and manage customers.

**API Base:** `https://credit.opticedgeafrica.net/api/v1`

---

## Setup

### Prerequisites
- Flutter SDK ≥ 3.2.0
- Dart SDK ≥ 3.2.0
- Android Studio / VS Code with Flutter extension

### Install & Run

```bash
cd opticedge_fo
flutter pub get
flutter run
```

---

## Project Structure

```
lib/
├── main.dart                       # App entry point
├── config/
│   ├── constants.dart              # Colors, API URL, keys
│   ├── theme.dart                  # Material 3 theme (orange)
│   └── routes.dart                 # GoRouter navigation
├── core/
│   ├── api/api_client.dart         # Dio HTTP client + interceptors
│   ├── models/                     # Data models (User, Customer, Dashboard)
│   ├── providers/                  # Riverpod state (auth, customer, kyc)
│   └── storage/secure_storage.dart # Token & user storage
├── screens/
│   ├── splash/splash_screen.dart   # Animated splash
│   ├── auth/login_screen.dart      # Login with card UI
│   ├── dashboard/                  # Dashboard with stats + actions
│   ├── customers/                  # Customer list + detail
│   ├── kyc/                        # 7-step KYC wizard
│   │   └── steps/                  # step1_device → step7_submit
│   └── profile/profile_screen.dart # FO profile + logout
└── widgets/
    ├── common/                     # AppButton, StatusBadge, PhotoPickerTile
    └── kyc/step_indicator.dart     # Step progress indicator
```

---

## API Endpoints Used

| Method | Endpoint | Screen |
|---|---|---|
| POST | `/login` | Login |
| GET | `/me` | Profile |
| POST | `/logout` | Profile |
| GET | `/kyc/dashboard` | Dashboard |
| GET | `/kyc/customers` | Customer List |
| GET | `/kyc/customers/{id}` | Customer Detail |
| GET | `/kyc/branches` | Step 3 (branch dropdown) |
| POST | `/kyc/application/step1` | KYC Wizard Step 1 |
| POST | `/kyc/application/{id}/step2` | KYC Wizard Step 2 |
| POST | `/kyc/application/{id}/step3` | KYC Wizard Step 3 |
| POST | `/kyc/application/{id}/step4` | KYC Wizard Step 4 |
| POST | `/kyc/application/{id}/step5` | KYC Wizard Step 5 |
| POST | `/kyc/application/{id}/step6` | KYC Wizard Step 6 |
| POST | `/kyc/application/{id}/step7` | KYC Wizard Step 7 (Submit) |
| GET | `/kyc/application/{id}/status` | Customer Detail |

---

## Features

- **Splash screen** — animated logo, scan line, particles, loading dots
- **Login** — glassmorphism card, animated, error handling, remember me
- **Dashboard** — greeting, stats grid (animated), quick actions, recent customers
- **Customer list** — tabs (All/Drafts/Pending/Approved/Rejected), search, infinite scroll
- **Customer detail** — collapsible sections, all photos, verification timeline
- **KYC Wizard (7 steps)**:
  1. Device info + IMEI + photos
  2. Identity (name, gender, DOB, ID type, ID photos, headshot)
  3. Contact (phone, branch dropdown, region, address)
  4. Income (occupation chips, monthly income, payment cycle, business photo)
  5. Next of Kin (primary + optional secondary, relationship chips)
  6. Consent (animated toggles for all 3 consent items)
  7. Submit (summary card, source selection, FO notes, success animation)
- **Profile** — user info, permissions, sign out

---

## Android Permissions

Add to `android/app/src/main/AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.CAMERA"/>
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE"/>
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE"/>
```

## iOS Permissions

Add to `ios/Runner/Info.plist`:

```xml
<key>NSCameraUsageDescription</key>
<string>Required for capturing customer and document photos</string>
<key>NSPhotoLibraryUsageDescription</key>
<string>Required for selecting photos from gallery</string>
```

---

## Notes

- Token stored securely via `flutter_secure_storage`
- KYC registration requires `loans.create` permission — API returns 403 otherwise
- Image uploads use `multipart/form-data` via Dio `FormData`
- All photos compressed to 75% quality, max 1200px width before upload
