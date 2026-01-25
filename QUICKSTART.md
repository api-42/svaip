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
composer dev
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
composer setup
```

This will:
1. Install all dependencies
2. Create `.env` file
3. Generate application key
4. Create and migrate database
5. Build frontend assets

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

## 🌐 Access the Application

Once running, open your browser:

- **Home**: http://localhost:8000
- **Register**: http://localhost:8000/register  
- **Login**: http://localhost:8000/login

**Default test user** (after running seeders):
- Email: test@example.com
- Password: password

---

## 🧪 Run Tests

```bash
# All tests (70 tests, ~2 seconds)
php artisan test

# Specific test suites
php artisan test --filter=Auth
php artisan test --filter=Scoring

# With coverage
php artisan test --coverage
```

---

## 🛑 Stopping Services

Press **Ctrl+C** in the terminal where services are running.

All services stop automatically.

---

## 📚 Full Documentation

See **[START.md](./START.md)** for:
- Detailed setup instructions
- Troubleshooting guide
- Production deployment
- Database management
- Cache commands
- And more...

---

## 💡 Quick Tips

**Already set up?** Just run:
```bash
start-dev.bat  # Windows
./start-dev.sh # Linux/Mac
composer dev   # Any platform
```

**Need to reset database?**
```bash
php artisan migrate:fresh
```

**Frontend not updating?**
- Make sure Vite is running (`npm run dev` or use `composer dev`)
- Check http://localhost:5173 is accessible

**Port 8000 busy?**
```bash
php artisan serve --port=8001
```

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
├── start-dev.bat         # Windows startup script ⭐
├── start-dev.sh          # Linux/Mac startup script ⭐
├── start-simple.bat      # Basic server only (Windows)
├── START.md              # Detailed documentation 📚
├── app/                  # Application code
│   ├── Models/           # Database models
│   ├── Http/Controllers/ # Request handlers
│   └── Http/Resources/   # API responses
├── tests/                # 70 tests ✅
│   ├── Feature/Auth/     # Authentication tests
│   └── Feature/Scoring/  # Scoring system tests
├── database/
│   ├── migrations/       # Database schema
│   └── factories/        # Test data generators
└── resources/
    ├── views/            # Blade templates
    └── js/               # Frontend JavaScript
```

---

## 🎉 Features Implemented

✅ **Authentication** - Register, login, logout (27 tests)  
✅ **Smart Scoring** - Point-based assessments with results  
✅ **Result Templates** - Customizable outcome pages  
✅ **Public Sharing** - Unique URLs for completed flows  
✅ **Branching Logic** - Conditional flow navigation  
✅ **Card System** - Reusable question components  

**Test Coverage**: 70 tests passing (160 assertions) 🎯

---

## 🚀 Next Steps

1. Start the server: `start-dev.bat`
2. Open http://localhost:8000
3. Register a new account
4. Create your first flow
5. Add cards with scoring
6. Create result templates
7. Run and share results!

Happy coding! 🎨
