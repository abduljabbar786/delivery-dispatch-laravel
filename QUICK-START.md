# Quick Start Guide - Delivery Dispatch MVP

## 🚀 Get the Backend Running in 5 Minutes

### 1. Configure Environment
```bash
cp .env.example.mysql .env
```

Edit `.env` and set your database credentials:
```
DB_DATABASE=delivery_dispatch
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. Install & Setup
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 3. Start Services

**Terminal 1 - Laravel:**
```bash
php artisan serve
```

**Terminal 2 - Soketi (Broadcasting):**
```bash
npx soketi start --config=soketi.json
```

Create `soketi.json` in project root:
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

### 4. Test the API

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"supervisor@example.com","password":"password"}'
```

Save the token from response!

**Get Riders:**
```bash
curl http://localhost:8000/api/riders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Create Order:**
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "ORD-001",
    "customer_name": "John Doe",
    "customer_phone": "+1234567890",
    "address": "123 Main St",
    "lat": 40.7128,
    "lng": -74.0060
  }'
```

### 5. Run Tests
```bash
./vendor/bin/pest
```

All tests should pass! ✅

## 📱 Next Steps

### Build React Frontend
See `README-COMPLETE.md` section "React Frontend Implementation Guide"

Key steps:
1. `npm create vite@latest frontend -- --template react`
2. Install dependencies (react-router-dom, axios, pusher-js, tailwindcss)
3. Copy example code from README
4. Configure environment variables
5. `npm run dev`

### Build Flutter Mobile App
See `README-COMPLETE.md` section "Flutter Mobile App Implementation Guide"

Key steps:
1. `flutter create rider_app`
2. Add dependencies (dio, geolocator, permission_handler, flutter_secure_storage)
3. Copy example services and screens from README
4. Update API base URL to your machine's IP
5. `flutter run`

## 🧪 What's Included

### Backend Features ✅
- [x] Laravel 12 API with Sanctum token auth
- [x] MySQL 8 with spatial types and indexes
- [x] 4 migrations (riders, orders, order_events, rider_locations)
- [x] Complete Eloquent models with relationships
- [x] Business logic controllers (Order assignment, lifecycle, location ingestion)
- [x] Broadcasting events (Soketi/Pusher protocol)
- [x] Comprehensive Pest tests (all passing)
- [x] Database seeders (10 riders + users)
- [x] Rate limiting and security best practices

### API Endpoints ✅
- Auth: login, logout
- Orders: list, create, assign, reassign, update status
- Riders: list, my order, upload locations (batch max 50)
- Map: get all rider positions
- Real-time: 2 channels (riders, orders)

### Business Rules ✅
- One active order per rider (enforced with 422)
- Automatic rider status management (IDLE ↔ BUSY)
- Location broadcast throttling (1 per second)
- Complete audit trail via order_events
- Spatial indexing for efficient geo queries

## 🔑 Default Credentials

**Supervisor:**
- Email: `supervisor@example.com`
- Password: `password`

**Riders (use any):**
- Email: `+1234567801` through `+1234567810`
- Password: `password`

## 📚 Documentation

- **README-COMPLETE.md**: Full implementation guide with code examples
- **.env.example.mysql**: Complete environment template
- **API Documentation**: See README for all endpoints and responses
- **Tests**: Check `tests/Feature/` for implementation examples

## 🐛 Troubleshooting

**Database connection error:**
- Check MySQL is running
- Verify credentials in `.env`
- Create database: `CREATE DATABASE delivery_dispatch;`

**Soketi won't start:**
- Install: `npm install -g soketi`
- Or use Docker: `docker run -p 6001:6001 quay.io/soketi/soketi`

**Tests failing:**
- Run `php artisan migrate:fresh --seed` to reset database
- Ensure CACHE_DRIVER=array in `.env` for tests

**Spatial query errors:**
- Ensure MySQL 8.0+ (spatial support required)
- Check migrations ran successfully

## 📦 Project Structure

```
delivery-dispatch-mvp/
├── app/
│   ├── Events/
│   │   ├── OrderStatusChanged.php       ✅ Broadcasting
│   │   └── RiderLocationUpdated.php     ✅ Broadcasting
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php           ✅ Token login
│   │   ├── OrderController.php          ✅ Full business logic
│   │   ├── RiderController.php          ✅ Location ingestion
│   │   └── MapController.php            ✅ Rider positions
│   └── Models/
│       ├── Order.php                     ✅ Relationships
│       ├── Rider.php                     ✅ currentOrder()
│       ├── OrderEvent.php                ✅ Audit trail
│       └── RiderLocation.php             ✅ History
├── database/
│   ├── migrations/                       ✅ 4 tables + spatial
│   └── seeders/                          ✅ 10 riders + users
├── tests/Feature/
│   ├── OrderAssignmentTest.php           ✅ Passing
│   ├── OrderLifecycleTest.php            ✅ Passing
│   └── RiderLocationIngestTest.php       ✅ Passing
├── routes/
│   ├── api.php                           ✅ All endpoints
│   └── channels.php                      ✅ Broadcasting
├── config/
│   └── broadcasting.php                  ✅ Pusher/Soketi
├── .env.example.mysql                    ✅ Complete template
├── README-COMPLETE.md                    ✅ Full guide
└── QUICK-START.md                        ✅ This file
```

## 🎯 Implementation Status

### Completed ✅
- ✅ Laravel 12 backend (100% functional)
- ✅ Database schema with spatial support
- ✅ All API endpoints
- ✅ Business logic with transactions
- ✅ Real-time broadcasting setup
- ✅ Comprehensive tests (all passing)
- ✅ Seeders and sample data
- ✅ Complete documentation

### To Implement (Documented in README)
- 📝 React SPA frontend (detailed guide provided)
- 📝 Flutter mobile app (detailed guide provided)
- 📝 Production deployment setup

## 💡 Tips

1. **Use Postman/Insomnia**: Import the API endpoints for easy testing
2. **Check Logs**: `tail -f storage/logs/laravel.log`
3. **Broadcasting Debug**: Set `BROADCAST_CONNECTION=log` to see events in logs
4. **Database GUI**: Use TablePlus, Sequel Pro, or phpMyAdmin to inspect data
5. **API Testing**: All test files show how to use the API correctly

## 🚀 Ready to Build!

You now have a production-ready backend. Follow the React and Flutter guides in README-COMPLETE.md to build the frontends!

Need help? Check the full README for:
- Detailed API examples
- Complete code samples
- Architecture decisions
- Production checklist
