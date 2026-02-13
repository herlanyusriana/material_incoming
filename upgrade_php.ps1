$ErrorActionPreference = "Stop"

$zipPath = "C:\Users\herla\Downloads\php-8.5.2-nts-Win32-vs17-x64.zip"
$phpDir = "C:\php"
$phpBak = "C:\php_backup_$(Get-Date -Format 'yyyyMMddHHmmss')"

Write-Host "Installing PHP 8.5..."

if (-not (Test-Path $zipPath)) {
    Write-Error "PHP 8.5 Zip not found at $zipPath"
    exit 1
}

# 1. Backup existing PHP folder
if (Test-Path $phpDir) {
    Write-Host "Backing up existing PHP 8.2 folder to $phpBak..."
    Rename-Item -Path $phpDir -NewName $phpBak
}

# 2. Extract Zip
Write-Host "Extracting PHP 8.5 to $phpDir..."
Expand-Archive -Path $zipPath -DestinationPath $phpDir -Force

# 3. Configure php.ini
$phpIniDev = Join-Path $phpDir "php.ini-development"
$phpIni = Join-Path $phpDir "php.ini"

if (Test-Path $phpIniDev) {
    Write-Host "Creating php.ini from development template..."
    Copy-Item $phpIniDev $phpIni
    
    $content = Get-Content $phpIni
    
    # Extensions
    $content = $content -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    
    $extensions = @("curl", "fileinfo", "gd", "mbstring", "openssl", "pdo_mysql", "pdo_sqlite", "sqlite3")
    foreach ($ext in $extensions) {
        $content = $content -replace ";extension=$ext", "extension=$ext"
    }

    # Timezone
    $content = $content -replace ';date.timezone =', 'date.timezone = Asia/Jakarta'
    
    Set-Content -Path $phpIni -Value $content
    Write-Host "php.ini configured."
} else {
    Write-Warning "php.ini-development not found!"
}

# 4. Global PATH environment variable should surely be set from previous step, verify
$userPath = [System.Environment]::GetEnvironmentVariable("Path", "User")
if ($userPath -notlike "*$phpDir*") {
    Write-Host "Adding $phpDir to User Path..."
    [System.Environment]::SetEnvironmentVariable("Path", "$userPath;$phpDir", "User")
}

# 5. Verify
& "$phpDir\php.exe" -v
Write-Host "PHP 8.5 setup complete!"
