# 🚀 SVAIP - Quick Start Scripts

Three ways to run the application, from simplest to most powerful:

## ⚡ Quick Start (Recommended)

### Windows
```bash
start-dev.bat
```

### Linux/Mac
```bash
chmod +x start-dev.sh
./start-dev.sh
```

### Using Composer
```bash
php C:\php\composer.phar dev
```

This starts **all development services** in one terminal:
- 🌐 Web Server (http://localhost:8000)
- ⚙️ Queue Worker
- 📋 Log Viewer
- ⚡ Vite Dev Server with hot reload

---

## 🎯 First Time Setup

Before starting for the first time, run:

```bash
php C:\php\composer.phar setup
```

This will:
1. Install all dependencies
2. Create `.env` file
3. Generate application key
4. Create and migrate database
5. Build frontend assets

---

## 🌐 Access the Application

Once running, open your browser:

- **Home**: http://localhost:8000
- **Register**: http://localhost:8000/register  
- **Login**: http://localhost:8000/login

**Authentication is API-based:**
- Forms use Alpine.js to call `/api/auth/*` endpoints
- Session cookies stored automatically
- No page reloads on login/register

---

## 🔐 Authentication Flow

The auth system uses a modern API approach:

1. **Registration:**
   - Fill form → Alpine.js POST to `/api/auth/register`
   - Server validates, creates user, logs in, regenerates session
   - JSON response → Client redirects to homepage

2. **Login:**
   - Fill form → Alpine.js POST to `/api/auth/login`
   - Server validates credentials, creates session
   - JSON response → Client redirects to homepage

3. **Logout:**
   - Click logout → Alpine.js POST to `/api/auth/logout`
   - Server invalidates session
   - Client redirects to login page

No page reloads, just smooth JSON API calls! 🚀

---

## 📡 API Endpoints

All API routes are under `/api/*` (no versioning):

### Authentication (Public)
- `POST /api/auth/register` - Create account
- `POST /api/auth/login` - Login with credentials
- `POST /api/auth/logout` - Logout (requires auth)

### Flows (Authenticated)
- `GET /api/flows` - List user's flows
- `POST /api/flows` - Create new flow
- `GET /api/flows/{id}` - Get flow details
- `PUT /api/flows/{id}` - Update flow
- `DELETE /api/flows/{id}` - Delete flow

**📖 See [docs/API.md](./docs/API.md) for complete API documentation**

---

## 🔧 Alternative Start Options

### Option 1: Simple Server Only
If you don't need queue workers or log viewers:

**Windows:**
```bash
start-simple.bat
```

This starts only the web server at http://localhost:8000

### Option 2: Manual Control
Start each service separately:

```bash
# Terminal 1 - Web Server
php artisan serve

# Terminal 2 - Queue Worker (optional)
php artisan queue:listen

# Terminal 3 - Frontend Dev Server (optional)
npm run dev
```

---

## 🧪 Run Tests

```bash
# All tests (82 tests, ~60 seconds)
php artisan test

# Specific test suites
php artisan test --filter=Analytics
php artisan test --filter=Scoring

# With coverage
php artisan test --coverage
```

**Note:** Auth tests currently fail - they test legacy form POST flow. Need updating to test API endpoints.

---

## 🛑 Stopping Services

Press **Ctrl+C** in the terminal where services are running.

All services stop automatically.

---

## 📚 Full Documentation

- **[README.md](./README.md)** - Project overview and features
- **[START.md](./START.md)** - Detailed setup and deployment
- **[docs/API.md](./docs/API.md)** - Complete API reference
- **[docs/ENGINEERING_STANDARDS.md](./docs/ENGINEERING_STANDARDS.md)** - Development standards

---

## 💡 Quick Tips

**Already set up?** Just run:
```bash
start-dev.bat  # Windows
./start-dev.sh # Linux/Mac
php C:\php\composer.phar dev   # Any platform (Windows: use full path)
```

**Need to reset database?**
```bash
php artisan migrate:fresh
```

**Frontend not updating?**
- Make sure Vite is running (`npm run dev` or use `composer dev`)
- Check http://localhost:5173 is accessible
- Clear browser cache

**Port 8000 busy?**
```bash
php artisan serve --port=8001
```

**CSRF token errors?**
- Check `<meta name="csrf-token">` exists in layout
- Verify Alpine.js includes token in fetch headers

**Session errors on login/register?**
- Ensure API auth routes have 'web' middleware in `routes/api.php`
- Check session driver in `.env` (default: `file`)

---

## 🎯 What's Running?

When you use `composer dev` or `start-dev.bat`:

| Service | URL | Purpose |
|---------|-----|---------|
| Laravel Server | http://localhost:8000 | Main application |
| Vite Dev Server | http://localhost:5173 | Frontend hot reload |
| Queue Worker | - | Background jobs |
| Pail (Logs) | - | Real-time logs in terminal |

---

## ⚙️ Minimum Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and NPM
- SQLite (included with PHP)

---

## 🆘 Having Issues?

### Common Problems

**"Session store not set on request"**
- **Fix:** Auth routes in `routes/api.php` need `web` middleware
- **Check:** `Route::middleware(['web'])->group(...)` for auth endpoints

**404 on API calls**
- **Fix:** API base URL should be `/api` not `/api/v1`
- **Check:** `public/js/api-service.js` line 16: `constructor(baseUrl = '/api')`

**CSRF token missing**
- **Fix:** Ensure meta tag in layout: `<meta name="csrf-token" content="{{ csrf_token() }}">`
- **Check:** Blade templates include CSRF token in fetch headers

**Login/register form not working**
- **Fix:** Check browser console for JavaScript errors
- **Check:** Network tab shows POST to `/api/auth/login` with 200 response

**Tests failing**
- **Expected:** Auth tests fail (test old form flow, need updating)
- **Action:** Run non-auth tests: `php artisan test --exclude-group=auth`

### General Troubleshooting

1. **Check `.env` file exists**: Run `composer setup`
2. **Dependencies missing**: Run `composer install && npm install`
3. **Database errors**: Delete `database/database.sqlite` and run `php artisan migrate`
4. **Port conflicts**: Use `--port=8001` or stop other services
5. **Permission errors**: Run `chmod -R 775 storage bootstrap/cache` (Linux/Mac)

See **[START.md](./START.md)** for detailed troubleshooting.

---

## 📦 Project Structure

```
svaip/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php       # API auth (login/register/logout)
│   │   │   └── Api/
│   │   │       ├── FlowController.php   # Flow CRUD API
│   │   │       └── TokenController.php  # Token generation
│   │   └── Requests/Auth/              # FormRequest validation
│   ├── Services/FlowService.php        # Business logic
│   └── Policies/FlowPolicy.php         # Authorization
├── public/js/api-service.js            # Frontend API client
├── resources/views/
│   └── auth/                           # Login/register with Alpine.js
├── routes/
│   ├── api.php                         # API routes (/api/*)
│   └── web.php                         # View routes
├── tests/                              # 82 tests
└── docs/
    ├── API.md                          # API documentation
    └── ENGINEERING_STANDARDS.md        # Dev standards
```

---

## 🎉 Features Implemented

✅ **API-First Auth** - JSON endpoints with session management  
✅ **No Redirects** - Client-side navigation after auth  
✅ **Smart Scoring** - Point-based assessments with results  
✅ **Result Templates** - Customizable outcome pages  
✅ **Public Sharing** - Unique URLs for completed flows  
✅ **Branching Logic** - Conditional flow navigation  
✅ **Cycle Detection** - Prevents infinite loops  
✅ **Analytics** - Comprehensive statistics dashboard  

**Test Coverage**: 82 tests (206 assertions) 🎯

---

## 🚀 Next Steps

1. Start the server: `start-dev.bat` or `composer dev`
2. Open http://localhost:8000
3. Register a new account (uses API!)
4. Create your first flow
5. Add cards with scoring
6. Create result templates
7. Run and share results!

**API Testing:**
- Open DevTools Network tab
- Watch API calls to `/api/auth/login` and `/api/flows`
- See JSON responses in real-time

Happy coding! 🎨
