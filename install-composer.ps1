# اسکریپت نصب Composer در Windows
# اجرا در PowerShell با دسترسی Administrator

Write-Host "Checking PHP installation..." -ForegroundColor Cyan
$phpVersion = php -v 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: PHP is not installed or not in PATH!" -ForegroundColor Red
    Write-Host "Please install PHP 8.2+ first from https://windows.php.net/download/" -ForegroundColor Yellow
    exit 1
}
Write-Host "PHP found: $($phpVersion -split "`n" | Select-Object -First 1)" -ForegroundColor Green

Write-Host "`nDownloading Composer installer..." -ForegroundColor Cyan
$installerUrl = "https://getcomposer.org/installer"
$installerFile = "composer-setup.php"

try {
    Invoke-WebRequest -Uri $installerUrl -OutFile $installerFile
    Write-Host "Installer downloaded successfully." -ForegroundColor Green
    
    Write-Host "`nInstalling Composer..." -ForegroundColor Cyan
    php composer-setup.php
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`nComposer installed successfully!" -ForegroundColor Green
        Write-Host "You can now use: php composer.phar" -ForegroundColor Yellow
        Write-Host "Or add composer.phar to your PATH and rename it to composer.exe" -ForegroundColor Yellow
        
        # سعی در افزودن به PATH موقت
        $currentDir = Get-Location
        $composerPath = Join-Path $currentDir "composer.phar"
        if (Test-Path $composerPath) {
            Write-Host "`nTo use globally, add this to your PATH:" -ForegroundColor Cyan
            Write-Host $currentDir -ForegroundColor Yellow
        }
    } else {
        Write-Host "Installation failed!" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "ERROR: Failed to download Composer installer!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
} finally {
    if (Test-Path $installerFile) {
        Remove-Item $installerFile
    }
}

Write-Host "`nDone! You can now create Laravel project with:" -ForegroundColor Green
Write-Host "php composer.phar create-project laravel/laravel haadishop" -ForegroundColor Cyan

