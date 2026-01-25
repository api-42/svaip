<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# SVAIP - Smart Flow Assessment Platform

A Laravel-based platform for creating interactive flow-based questionnaires with scoring, branching logic, and customizable result pages.

---

## 🚀 Quick Start

### First Time Setup
```bash
composer setup
```

### Start Development Server

**Windows:**
```bash
start-dev.bat
```

**Linux/Mac:**
```bash
chmod +x start-dev.sh
./start-dev.sh
```

**Or use Composer:**
```bash
composer dev
```

**Access the app:** http://localhost:8000

📖 **See [QUICKSTART.md](./QUICKSTART.md) for detailed instructions**

---

## ✨ Features

✅ **Smart Scoring System** - Point-based assessments with automatic calculation  
✅ **Result Templates** - Customizable outcome pages based on score ranges  
✅ **Public Sharing** - Unique shareable URLs for completed assessments  
✅ **Branching Logic** - Conditional navigation based on answers  
✅ **User Authentication** - Secure registration and login  
✅ **Card System** - Reusable question components  

**Test Coverage:** 70 tests passing (160 assertions) ✅

---

## 📚 Documentation

- **[QUICKSTART.md](./QUICKSTART.md)** - Quick reference and common commands
- **[START.md](./START.md)** - Comprehensive setup and deployment guide
- **[Feature Implementation Summary](./session-files/)** - Detailed feature documentation

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific suites
php artisan test --filter=Auth
php artisan test --filter=Scoring

# With coverage
php artisan test --coverage
```

**Current Status:** 70 tests passing
- Authentication: 27 tests ✅
- Scoring System: 40 tests ✅
- Integration: 3 tests ✅

---

## 🛠️ Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vite, TailwindCSS 4.0, Alpine.js
- **Database:** SQLite (easily switchable)
- **Testing:** PHPUnit with comprehensive test coverage

---

## 📦 What's Running

When you use `start-dev.bat` or `composer dev`:

| Service | URL | Purpose |
|---------|-----|---------|
| Laravel Server | http://localhost:8000 | Main application |
| Vite Dev Server | http://localhost:5173 | Frontend hot reload |
| Queue Worker | - | Background jobs |
| Pail (Logs) | - | Real-time logs |

---

## 🎯 Use Cases

- **HR/Recruiting:** Skills assessments, culture fit scoring, candidate evaluation
- **Marketing/Sales:** Lead qualification, product finders, engagement quizzes  
- **Education:** Automated grading, learning path recommendations, knowledge checks
- **Product Teams:** Onboarding flows, feature discovery, user segmentation

---

## 🔧 Development Commands

```bash
# Start dev environment (all services)
start-dev.bat            # Windows
./start-dev.sh           # Linux/Mac
composer dev             # Any platform

# Simple server only
start-simple.bat         # Windows

# Database
php artisan migrate               # Run migrations
php artisan migrate:fresh         # Fresh database

# Testing
php artisan test                  # All tests
php artisan test --filter=Name    # Specific tests

# Cache management
php artisan config:clear          # Clear config cache
php artisan cache:clear           # Clear application cache
```

---

## 📖 About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
