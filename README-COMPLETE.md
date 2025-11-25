# Delivery Dispatch MVP - Complete Production-Ready System

## Overview

This is a minimal but production-ready MVP for a restaurant delivery dispatch system with:
- **Backend**: Laravel 12 API (PHP 8.2+)
- **Frontend**: React SPA (Vite + React Router + Tailwind)
- **Mobile**: Flutter (Android-first)
- **Real-time**: Soketi (Pusher protocol)
- **Database**: MySQL 8.0 with spatial types
- **Auth**: Laravel Sanctum (token-based)
- **Tests**: Pest

## Backend Architecture

### Database Schema

**riders**
- Tracks rider status (OFFLINE, IDLE, BUSY)
- Stores latest position (lat/lng + spatial POINT)
- Battery level and last seen timestamp

**orders**
- Status workflow: UNASSIGNED → ASSIGNED → PICKED_UP → OUT_FOR_DELIVERY → DELIVERED/FAILED
- Customer details and delivery address with spatial support
- Assigned rider relationship

**order_events**
- Immutable audit log of all order status changes
- JSON metadata for each event

**rider_locations**
- Historical location trail for riders
- Linked to active orders
- Includes speed, heading, accuracy, battery

### Business Rules Implemented

1. **One Active Order Per Rider**: A rider can only have one order in ASSIGNED/PICKED_UP/OUT_FOR_DELIVERY status
2. **Automatic Rider Status**: Rider status automatically transitions between IDLE/BUSY based on order assignments
3. **Location Throttling**: Broadcast events throttled to once per second per rider to prevent spam
4. **Batch Location Upload**: Riders can upload up to 50 location points per request
5. **Order Events**: All status changes automatically create audit trail events

### API Endpoints

#### Authentication
```
POST /api/auth/login
Body: { "email": "...", "password": "..." }
Response: { "token": "...", "user": {...} }

POST /api/auth/logout (requires auth)
```

#### Supervisor Endpoints (require auth)
```
GET /api/orders?status=UNASSIGNED
POST /api/orders
POST /api/orders/{id}/assign
POST /api/orders/{id}/reassign
POST /api/orders/{id}/status

GET /api/riders?status=IDLE
GET /api/map/riders
```

#### Rider Endpoints (require auth)
```
GET /api/rider/me/order
POST /api/rider/locations (throttled: 60/min)
Body: { "points": [{ "lat": ..., "lng": ..., "battery": ..., "ts": ... }] }
```

### Real-time Broadcasting

**Channel: riders**
- Event: `rider.location.updated`
- Payload: `{ rider_id, lat, lng, battery, ts }`

**Channel: orders**
- Event: `order.status.changed`
- Payload: `{ order_id, rider_id, status, ts }`

## Setup Instructions

### 1. Backend Setup

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example.mysql .env

# Configure database in .env
DB_DATABASE=delivery_dispatch
DB_USERNAME=root
DB_PASSWORD=your_password

# Configure broadcasting (Soketi)
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=delivery-dispatch
PUSHER_APP_KEY=local-key
PUSHER_APP_SECRET=local-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Run tests
./vendor/bin/pest
```

### 2. Start Soketi (Broadcasting Server)

```bash
# Using NPM
npx soketi start --config=soketi.json

# Or using Docker
docker run -p 6001:6001 quay.io/soketi/soketi:latest
```

**soketi.json** (create in project root):
```json
{
  "debug": true,
  "port": 6001,
  "appManager.array.apps": [
    {
      "id": "delivery-dispatch",
      "key": "local-key",
      "secret": "local-secret"
    }
  ]
}
```

### 3. Start Laravel Server

```bash
php artisan serve
# API available at http://localhost:8000
```

### Default Credentials

**Supervisor:**
- Email: `supervisor@example.com`
- Password: `password`

**Riders:**
- Email: `+1234567801` through `+1234567810`
- Password: `password`

## React Frontend Implementation Guide

### Directory Structure
```
frontend/
├── src/
│   ├── main.jsx              # App entry point
│   ├── App.jsx               # Router and layout
│   ├── lib/
│   │   ├── api.js            # Axios client with token
│   │   └── pusher.js         # Pusher client
│   ├── pages/
│   │   ├── Login.jsx
│   │   ├── Orders.jsx        # Orders list + create form
│   │   ├── Riders.jsx        # Riders list
│   │   └── MapLive.jsx       # Live map
│   └── components/
│       ├── Header.jsx
│       ├── OrderRow.jsx
│       ├── RiderRow.jsx
│       └── AssignDrawer.jsx
└── package.json
```

### Setup React Frontend

```bash
# Create Vite project
npm create vite@latest frontend -- --template react
cd frontend

# Install dependencies
npm install react-router-dom axios dayjs pusher-js
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

**src/lib/api.js:**
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
```

**src/lib/pusher.js:**
```javascript
import Pusher from 'pusher-js';

const pusher = new Pusher('local-key', {
  wsHost: '127.0.0.1',
  wsPort: 6001,
  forceTLS: false,
  disableStats: true,
  enabledTransports: ['ws', 'wss'],
});

export default pusher;
```

**Key React Components:**

1. **Login Page**: POST to `/api/auth/login`, store token
2. **Orders Page**:
   - Fetch orders with filters
   - Create new orders
   - Assign/reassign riders (show modal with IDLE riders)
3. **Riders Page**: List with status badges
4. **Map Page**:
   - Fetch `/api/map/riders`
   - Subscribe to `riders` channel
   - Update markers on `rider.location.updated`

**Environment Variables (.env):**
```
VITE_API_URL=http://localhost:8000/api
VITE_PUSHER_KEY=local-key
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
```

## Flutter Mobile App Implementation Guide

### Project Structure
```
lib/
├── main.dart
├── services/
│   ├── api_service.dart      # HTTP client + token storage
│   ├── location_service.dart # Geolocator integration
│   └── auth_service.dart
├── screens/
│   ├── login_screen.dart
│   └── home_screen.dart      # My Order + status buttons
├── models/
│   ├── order.dart
│   └── location_point.dart
└── widgets/
    └── status_button.dart
```

### Setup Flutter

```bash
flutter create rider_app
cd rider_app

# Add dependencies to pubspec.yaml
flutter pub add http dio geolocator permission_handler flutter_secure_storage url_launcher
flutter pub get
```

**pubspec.yaml:**
```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.0.0
  geolocator: ^11.0.0
  permission_handler: ^11.0.0
  flutter_secure_storage: ^9.0.0
  url_launcher: ^6.2.0
```

### Key Flutter Components

**1. API Service (lib/services/api_service.dart):**
```dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  final Dio _dio = Dio(BaseOptions(
    baseUrl: 'http://YOUR_IP:8000/api',
  ));
  final _storage = FlutterSecureStorage();

  Future<void> login(String email, String password) async {
    final response = await _dio.post('/auth/login', data: {
      'email': email,
      'password': password,
    });
    await _storage.write(key: 'token', value: response.data['token']);
  }

  Future<Map<String, dynamic>?> getMyOrder() async {
    final token = await _storage.read(key: 'token');
    try {
      final response = await _dio.get('/rider/me/order',
        options: Options(headers: {'Authorization': 'Bearer $token'}));
      return response.data;
    } catch (e) {
      if (e is DioException && e.response?.statusCode == 204) {
        return null; // No active order
      }
      rethrow;
    }
  }

  Future<void> uploadLocations(List<Map<String, dynamic>> points) async {
    final token = await _storage.read(key: 'token');
    await _dio.post('/rider/locations',
      data: {'points': points},
      options: Options(headers: {'Authorization': 'Bearer $token'}));
  }
}
```

**2. Location Service (lib/services/location_service.dart):**
```dart
import 'package:geolocator/geolocator.dart';
import 'dart:async';

class LocationService {
  List<Map<String, dynamic>> _locationBuffer = [];
  Timer? _uploadTimer;
  final ApiService _apiService;

  LocationService(this._apiService);

  Future<void> startTracking() async {
    final permission = await Geolocator.requestPermission();
    if (permission == LocationPermission.denied) return;

    // Start listening to location updates
    Geolocator.getPositionStream(
      locationSettings: LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 25, // meters
      ),
    ).listen((Position position) {
      _locationBuffer.add({
        'lat': position.latitude,
        'lng': position.longitude,
        'accuracy': position.accuracy,
        'speed': position.speed,
        'battery': null, // TODO: Get battery level
      });

      // Upload when buffer reaches 20 points or every 30 seconds
      if (_locationBuffer.length >= 20) {
        _uploadLocations();
      }
    });

    // Periodic upload timer
    _uploadTimer = Timer.periodic(Duration(seconds: 30), (_) {
      if (_locationBuffer.isNotEmpty) {
        _uploadLocations();
      }
    });
  }

  Future<void> _uploadLocations() async {
    if (_locationBuffer.isEmpty) return;

    final points = List.from(_locationBuffer);
    _locationBuffer.clear();

    try {
      await _apiService.uploadLocations(points);
    } catch (e) {
      // Re-add to buffer if upload fails
      _locationBuffer.addAll(points);
    }
  }

  void stopTracking() {
    _uploadTimer?.cancel();
  }
}
```

**3. Home Screen (lib/screens/home_screen.dart):**
```dart
// Display current order
// Show status buttons (PICKED_UP, OUT_FOR_DELIVERY, DELIVERED, FAILED)
// Start location tracking when order is active
// Button to open Google Maps for navigation
```

**4. Open Google Maps:**
```dart
import 'package:url_launcher/url_launcher.dart';

Future<void> openGoogleMaps(double lat, double lng) async {
  final url = 'https://www.google.com/maps/dir/?api=1&destination=$lat,$lng';
  if (await canLaunchUrl(Uri.parse(url))) {
    await launchUrl(Uri.parse(url));
  }
}
```

### Android Permissions (android/app/src/main/AndroidManifest.xml):
```xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION"/>
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION"/>
<uses-permission android:name="android.permission.FOREGROUND_SERVICE"/>
```

**TODO for Production:**
- Implement Android Foreground Service for background location
- Add battery level detection
- Handle app termination and restart
- Implement retry logic for failed uploads
- Add proper error handling and user feedback

## Testing

### Run Backend Tests
```bash
./vendor/bin/pest
```

### Test Coverage
- ✅ Order assignment (422 on double-assign)
- ✅ Order lifecycle (full workflow with rider status changes)
- ✅ Location ingestion (batch processing, updates, broadcasting)

## Production Deployment Checklist

### Backend
- [ ] Set up production database with spatial indexing
- [ ] Configure Redis for caching and broadcasting
- [ ] Set up Soketi in production (or use Pusher)
- [ ] Configure proper CORS settings
- [ ] Set up supervisor for queue workers
- [ ] Enable rate limiting on sensitive endpoints
- [ ] Set up monitoring (Laravel Telescope, Sentry)
- [ ] Configure backup strategy

### Frontend
- [ ] Build production assets (`npm run build`)
- [ ] Configure CDN for static assets
- [ ] Set up environment variables
- [ ] Implement proper error boundaries
- [ ] Add analytics

### Mobile
- [ ] Configure production API endpoints
- [ ] Implement Android Foreground Service
- [ ] Set up crash reporting (Firebase Crashlytics)
- [ ] Configure ProGuard rules
- [ ] Test on multiple Android devices
- [ ] Submit to Google Play Store

## API Response Examples

### Login
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Supervisor Admin",
    "email": "supervisor@example.com",
    "role": "supervisor"
  }
}
```

### Get Orders
```json
{
  "data": [
    {
      "id": 1,
      "code": "ORD-001",
      "customer_name": "John Doe",
      "customer_phone": "+1234567890",
      "address": "123 Main St",
      "status": "ASSIGNED",
      "assigned_rider_id": 5,
      "rider": {
        "id": 5,
        "name": "Mike Johnson",
        "status": "BUSY"
      }
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Get Map Riders
```json
[
  {
    "id": 1,
    "name": "John Doe",
    "status": "IDLE",
    "lat": 40.7128,
    "lng": -74.0060,
    "battery": 85,
    "last_seen_at": "2025-01-01T12:34:56.000000Z"
  }
]
```

## Architecture Decisions

1. **Spatial Types**: Using both lat/lng doubles AND spatial POINT columns for flexibility and performance
2. **Token Auth**: Sanctum tokens instead of session-based auth for API-first architecture
3. **Event Sourcing**: Order events table provides complete audit trail
4. **Throttled Broadcasting**: Prevents network spam while maintaining real-time feel
5. **Batch Locations**: Reduces API calls and improves mobile battery life

## Known Limitations

1. Flutter app requires foreground service implementation for production
2. No rider-to-order distance calculation (can be added using spatial functions)
3. Single-tenant system (multi-restaurant support would require tenant_id columns)
4. No SMS notifications (can be added with Laravel Notifications)
5. Basic role-based access control (can be extended with permissions package)

## License

MIT License - Feel free to use and modify for your needs.
