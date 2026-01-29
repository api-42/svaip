# Composer Path Configuration

This project uses a custom composer path for Windows: `php C:\php\composer.phar`

## Why?

On this system, composer is installed at `C:\php\composer.phar` and needs to be run with `php` explicitly.

## Updated Scripts

The following scripts have been updated to use the custom composer path:

### Windows Batch Files
- **start-dev.bat** - Main development server start script
- **start-simple.bat** - Simple server-only start script

Both now use:
```batch
php C:\php\composer.phar <command>
```

### Usage

**First time setup:**
```bash
php C:\php\composer.phar setup
```

**Start development:**
```bash
start-dev.bat
# or
php C:\php\composer.phar dev:windows
```

**Start simple server:**
```bash
start-simple.bat
```

## Other Systems

If you're on Linux/Mac or have composer in your PATH, you can:
1. Use `start-dev.sh` (will use `composer` command)
2. Or use `composer dev` directly

## Troubleshooting

**If composer path is different on your system:**
1. Find your composer location: `where composer` (Windows) or `which composer` (Linux/Mac)
2. Update the path in:
   - `start-dev.bat` (line 17 and line 73)
   - `start-simple.bat` (lines 13 and 22)

**If you want to use global composer:**
Simply replace `php C:\php\composer.phar` with `composer` in the batch files.
