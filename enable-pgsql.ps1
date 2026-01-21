# Enable PostgreSQL Extension for PHP
# Run this script as Administrator

Write-Host "Finding php.ini location..." -ForegroundColor Cyan

# Get php.ini path
$phpIniPath = php -r "echo php_ini_loaded_file();"

if (-not $phpIniPath -or $phpIniPath -eq "") {
    Write-Host "ERROR: Could not find php.ini file!" -ForegroundColor Red
    Write-Host "Please make sure PHP is installed and in PATH" -ForegroundColor Yellow
    exit 1
}

Write-Host "Found php.ini at: $phpIniPath" -ForegroundColor Green

# Backup php.ini
$backupPath = "$phpIniPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Copy-Item $phpIniPath $backupPath
Write-Host "Backup created at: $backupPath" -ForegroundColor Green

# Read php.ini content
$content = Get-Content $phpIniPath -Raw

# Enable pdo_pgsql extension
if ($content -match ";extension=pdo_pgsql") {
    $content = $content -replace ";extension=pdo_pgsql", "extension=pdo_pgsql"
    Write-Host "Enabled: extension=pdo_pgsql" -ForegroundColor Green
} elseif ($content -match "extension=pdo_pgsql") {
    Write-Host "Already enabled: extension=pdo_pgsql" -ForegroundColor Yellow
} else {
    # Add if not exists
    $content += "`nextension=pdo_pgsql`n"
    Write-Host "Added: extension=pdo_pgsql" -ForegroundColor Green
}

# Enable pgsql extension
if ($content -match ";extension=pgsql") {
    $content = $content -replace ";extension=pgsql", "extension=pgsql"
    Write-Host "Enabled: extension=pgsql" -ForegroundColor Green
} elseif ($content -match "extension=pgsql") {
    Write-Host "Already enabled: extension=pgsql" -ForegroundColor Yellow
} else {
    # Add if not exists
    $content += "`nextension=pgsql`n"
    Write-Host "Added: extension=pgsql" -ForegroundColor Green
}

# Save php.ini
Set-Content $phpIniPath $content -NoNewline

Write-Host "`nChanges saved successfully!" -ForegroundColor Green
Write-Host "`nVerifying extensions..." -ForegroundColor Cyan

# Verify
$extensions = php -m
if ($extensions -match "pdo_pgsql" -and $extensions -match "pgsql") {
    Write-Host "SUCCESS! PostgreSQL extensions are now enabled!" -ForegroundColor Green
    Write-Host "`nEnabled extensions:" -ForegroundColor Cyan
    php -m | Select-String "pgsql"
} else {
    Write-Host "WARNING: Extensions might not be loaded yet." -ForegroundColor Yellow
    Write-Host "Please restart your terminal and try again." -ForegroundColor Yellow
}

Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Close and reopen your terminal" -ForegroundColor White
Write-Host "2. Run: php -m | findstr pgsql" -ForegroundColor White
Write-Host "3. Run: php artisan migrate" -ForegroundColor White
