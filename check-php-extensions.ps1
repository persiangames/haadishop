# اسکریپت بررسی PHP Extensions
# اجرا: .\check-php-extensions.ps1

$phpPath = "C:\tools\php84\php.exe"

if (-not (Test-Path $phpPath)) {
    Write-Host "PHP not found at: $phpPath" -ForegroundColor Red
    Write-Host "Please update the path in this script." -ForegroundColor Yellow
    exit 1
}

Write-Host "Checking PHP Extensions..." -ForegroundColor Cyan
Write-Host ""

$requiredExtensions = @(
    "fileinfo",
    "mbstring",
    "openssl",
    "pdo_mysql",
    "tokenizer",
    "xml",
    "ctype",
    "json",
    "bcmath",
    "curl",
    "gd",
    "zip"
)

$allExtensions = & $phpPath -m
$missing = @()

foreach ($ext in $requiredExtensions) {
    if ($allExtensions -match $ext) {
        Write-Host "✓ $ext" -ForegroundColor Green
    } else {
        Write-Host "✗ $ext (MISSING)" -ForegroundColor Red
        $missing += $ext
    }
}

Write-Host ""

if ($missing.Count -eq 0) {
    Write-Host "All required extensions are enabled!" -ForegroundColor Green
} else {
    Write-Host "Missing extensions: $($missing -join ', ')" -ForegroundColor Red
    Write-Host ""
    Write-Host "To enable extensions:" -ForegroundColor Yellow
    Write-Host "1. Open: C:\tools\php84\php.ini" -ForegroundColor Cyan
    Write-Host "2. Find: ;extension=EXTENSION_NAME" -ForegroundColor Cyan
    Write-Host "3. Remove semicolon: extension=EXTENSION_NAME" -ForegroundColor Cyan
    Write-Host "4. Save and restart PHP" -ForegroundColor Cyan
}

