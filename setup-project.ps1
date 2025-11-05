# اسکریپت راه‌اندازی پروژه HaadiShop
# این اسکریپت Composer و PHP را در PATH جستجو می‌کند

Write-Host "=== HaadiShop Project Setup ===" -ForegroundColor Cyan
Write-Host ""

# بررسی PHP
Write-Host "Checking PHP..." -ForegroundColor Yellow
$phpPath = $null
$possiblePhpPaths = @(
    "C:\php\php.exe",
    "C:\xampp\php\php.exe",
    "C:\wamp64\bin\php\php8.2\php.exe",
    "C:\wamp\bin\php\php8.2\php.exe",
    "C:\laragon\bin\php\php-8.2\php.exe"
)

foreach ($path in $possiblePhpPaths) {
    if (Test-Path $path) {
        $phpPath = $path
        Write-Host "PHP found at: $phpPath" -ForegroundColor Green
        break
    }
}

if (-not $phpPath) {
    # سعی در پیدا کردن از PATH
    try {
        $phpVersion = php -v 2>&1
        if ($LASTEXITCODE -eq 0) {
            $phpPath = "php"
            Write-Host "PHP found in PATH" -ForegroundColor Green
        }
    } catch {
        Write-Host "PHP not found! Please install PHP 8.2+ or add it to PATH." -ForegroundColor Red
        Write-Host "Common locations: C:\php, C:\xampp\php, C:\wamp64\bin\php" -ForegroundColor Yellow
        exit 1
    }
}

# بررسی Composer
Write-Host "`nChecking Composer..." -ForegroundColor Yellow
$composerPath = $null

# سعی در پیدا کردن composer در PATH
try {
    $composerVersion = composer --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        $composerPath = "composer"
        Write-Host "Composer found in PATH" -ForegroundColor Green
    }
} catch {
    # جستجو در مسیرهای معمول
    $possibleComposerPaths = @(
        "$env:LOCALAPPDATA\Programs\Composer\composer.bat",
        "$env:APPDATA\Composer\composer.bat",
        "C:\ProgramData\ComposerSetup\bin\composer.bat"
    )
    
    foreach ($path in $possibleComposerPaths) {
        if (Test-Path $path) {
            $composerPath = $path
            Write-Host "Composer found at: $composerPath" -ForegroundColor Green
            break
        }
    }
    
    # جستجو composer.phar
    if (-not $composerPath) {
        $composerPhar = Join-Path $PSScriptRoot "composer.phar"
        if (Test-Path $composerPhar) {
            $composerPath = "$phpPath $composerPhar"
            Write-Host "Using local composer.phar" -ForegroundColor Green
        }
    }
}

if (-not $composerPath) {
    Write-Host "Composer not found!" -ForegroundColor Red
    Write-Host "Please restart PowerShell after installing Composer, or run:" -ForegroundColor Yellow
    Write-Host "  php -r `"copy('https://getcomposer.org/installer', 'composer-setup.php');`"" -ForegroundColor Cyan
    Write-Host "  php composer-setup.php" -ForegroundColor Cyan
    exit 1
}

# ساخت پروژه Laravel
Write-Host "`nCreating Laravel project..." -ForegroundColor Yellow
$projectPath = Join-Path $PSScriptRoot "haadishop"

if (Test-Path $projectPath) {
    Write-Host "Project folder already exists. Skipping..." -ForegroundColor Yellow
} else {
    if ($composerPath -eq "composer") {
        & composer create-project laravel/laravel haadishop
    } elseif ($composerPath -like "*composer.phar*") {
        Invoke-Expression "$composerPath create-project laravel/laravel haadishop"
    } else {
        & $composerPath create-project laravel/laravel haadishop
    }
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to create Laravel project!" -ForegroundColor Red
        exit 1
    }
    
    Write-Host "Laravel project created successfully!" -ForegroundColor Green
}

Write-Host "`n=== Next Steps ===" -ForegroundColor Cyan
Write-Host "1. Edit .env file in haadishop folder" -ForegroundColor Yellow
Write-Host "2. Run: php artisan key:generate" -ForegroundColor Yellow
Write-Host "3. Run: php artisan migrate" -ForegroundColor Yellow
Write-Host "4. Run: php artisan serve" -ForegroundColor Yellow

