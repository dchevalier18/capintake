#Requires -Version 5.1
<#
.SYNOPSIS
    CAPIntake one-click installer for Windows.
.DESCRIPTION
    Detects or installs PHP, Composer, and Node.js, then sets up the
    CAPIntake application with SQLite, seeds reference data, builds
    frontend assets, and creates a desktop shortcut to launch the app.
#>
param(
    [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot),
    [int]$Port = 8000
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
function Write-Step  { param([string]$Msg) Write-Host "`n==> " -ForegroundColor Cyan -NoNewline; Write-Host $Msg }
function Write-Ok    { param([string]$Msg) Write-Host "    [OK] " -ForegroundColor Green -NoNewline; Write-Host $Msg }
function Write-Warn  { param([string]$Msg) Write-Host "    [!]  " -ForegroundColor Yellow -NoNewline; Write-Host $Msg }
function Write-Err   { param([string]$Msg) Write-Host "    [X]  " -ForegroundColor Red -NoNewline; Write-Host $Msg }

function Test-Command {
    param([string]$Name)
    $null = Get-Command $Name -ErrorAction SilentlyContinue
    return $?
}

function Find-Executable {
    <#
    .SYNOPSIS
        Searches PATH and common Windows locations for an executable.
        Returns the first match or $null.
    #>
    param(
        [string]$Name,
        [string[]]$ExtraPaths = @()
    )

    # Check PATH first
    $cmd = Get-Command $Name -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    # Check extra paths
    foreach ($p in $ExtraPaths) {
        $expanded = [Environment]::ExpandEnvironmentVariables($p)
        if (Test-Path $expanded) { return $expanded }
    }

    return $null
}

# ---------------------------------------------------------------------------
# Banner
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "  ================================================================" -ForegroundColor Cyan
Write-Host "   CAPIntake Installer" -ForegroundColor Cyan
Write-Host "   Open-source intake & case management for Community Action Agencies" -ForegroundColor DarkCyan
Write-Host "  ================================================================" -ForegroundColor Cyan
Write-Host ""

$ProjectRoot = (Resolve-Path $ProjectRoot).Path
Write-Host "  Project directory: $ProjectRoot"
Write-Host ""

# ---------------------------------------------------------------------------
# 1. Detect / Install PHP
# ---------------------------------------------------------------------------
Write-Step "Checking for PHP 8.3+..."

$phpPaths = @(
    "$env:USERPROFILE\.config\herd\bin\php.bat",
    "$env:USERPROFILE\.config\herd\bin\php84\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php83\php.exe",
    "C:\laragon\bin\php\php-8.4\php.exe",
    "C:\laragon\bin\php\php-8.3\php.exe",
    "C:\xampp\php\php.exe",
    "$env:USERPROFILE\scoop\apps\php\current\php.exe",
    "C:\tools\php83\php.exe",
    "C:\tools\php84\php.exe",
    "C:\php\php.exe"
)

$PHP = Find-Executable -Name "php" -ExtraPaths $phpPaths

if ($PHP) {
    $phpVersion = & $PHP -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;" 2>$null
    if ([version]$phpVersion -ge [version]"8.3") {
        Write-Ok "Found PHP $phpVersion at $PHP"
    } else {
        Write-Warn "Found PHP $phpVersion but 8.3+ is required"
        $PHP = $null
    }
}

if (-not $PHP) {
    Write-Warn "PHP 8.3+ not found. Installing Laravel Herd (includes PHP + Composer)..."
    Write-Host ""
    Write-Host "    Laravel Herd is a free, lightweight PHP development environment." -ForegroundColor DarkGray
    Write-Host "    It will install PHP 8.4, Composer, and a local web server." -ForegroundColor DarkGray
    Write-Host ""

    $herdInstaller = Join-Path $env:TEMP "herd-installer.exe"
    $herdUrl = "https://herd.laravel.com/download/windows"

    try {
        Write-Host "    Downloading Herd..." -NoNewline
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        Invoke-WebRequest -Uri $herdUrl -OutFile $herdInstaller -UseBasicParsing
        Write-Host " done." -ForegroundColor Green

        Write-Host "    Running Herd installer (follow the prompts in the installer window)..."
        Start-Process -FilePath $herdInstaller -Wait
        Remove-Item $herdInstaller -ErrorAction SilentlyContinue

        # Re-detect PHP after Herd install
        $PHP = Find-Executable -Name "php" -ExtraPaths $phpPaths
        if ($PHP) {
            Write-Ok "PHP is now available at $PHP"
        } else {
            Write-Err "PHP still not found after Herd install."
            Write-Err "Please restart this installer after Herd finishes setting up."
            Read-Host "Press Enter to exit"
            exit 1
        }
    } catch {
        Write-Err "Could not download Herd: $_"
        Write-Host ""
        Write-Host "    Please install PHP 8.3+ manually:" -ForegroundColor Yellow
        Write-Host "    - Laravel Herd: https://herd.laravel.com/windows" -ForegroundColor Yellow
        Write-Host "    - Or: winget install PHP.PHP.8.3" -ForegroundColor Yellow
        Write-Host ""
        Read-Host "Press Enter to exit"
        exit 1
    }
}

# ---------------------------------------------------------------------------
# 2. Detect / Install Composer
# ---------------------------------------------------------------------------
Write-Step "Checking for Composer..."

$composerPaths = @(
    "$env:USERPROFILE\.config\herd\bin\composer.bat",
    "C:\ProgramData\ComposerSetup\bin\composer.bat",
    "$env:APPDATA\Composer\vendor\bin\composer.bat",
    "C:\laragon\bin\composer\composer.bat"
)

$COMPOSER = Find-Executable -Name "composer" -ExtraPaths $composerPaths

if ($COMPOSER) {
    Write-Ok "Found Composer at $COMPOSER"
} else {
    Write-Warn "Composer not found. Attempting to install..."

    if (Test-Command "winget") {
        Write-Host "    Installing via winget..."
        & winget install Composer.Composer --accept-source-agreements --accept-package-agreements 2>$null
        $COMPOSER = Find-Executable -Name "composer" -ExtraPaths $composerPaths
    }

    if (-not $COMPOSER) {
        Write-Err "Could not install Composer automatically."
        Write-Host "    Please install from https://getcomposer.org/download/" -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "Composer installed at $COMPOSER"
}

# ---------------------------------------------------------------------------
# 3. Detect / Install Node.js
# ---------------------------------------------------------------------------
Write-Step "Checking for Node.js 18+..."

$NODE = Find-Executable -Name "node"
$NPM  = Find-Executable -Name "npm"

if ($NODE) {
    $nodeVersion = (& $NODE --version 2>$null).TrimStart("v")
    $nodeMajor = [int]($nodeVersion.Split(".")[0])
    if ($nodeMajor -ge 18) {
        Write-Ok "Found Node.js v$nodeVersion"
    } else {
        Write-Warn "Found Node.js v$nodeVersion but 18+ is required"
        $NODE = $null
    }
}

if (-not $NODE) {
    Write-Warn "Node.js 18+ not found. Attempting to install..."

    if (Test-Command "winget") {
        Write-Host "    Installing via winget..."
        & winget install OpenJS.NodeJS.LTS --accept-source-agreements --accept-package-agreements 2>$null

        # Refresh PATH
        $env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
        $NODE = Find-Executable -Name "node"
        $NPM  = Find-Executable -Name "npm"
    }

    if (-not $NODE) {
        Write-Err "Could not install Node.js automatically."
        Write-Host "    Please install from https://nodejs.org/" -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "Node.js installed"
}

if (-not $NPM) {
    $NPM = Find-Executable -Name "npm"
    if (-not $NPM) {
        Write-Err "npm not found (should come with Node.js)."
        Read-Host "Press Enter to exit"
        exit 1
    }
}

# ---------------------------------------------------------------------------
# 4. Check required PHP extensions
# ---------------------------------------------------------------------------
Write-Step "Checking PHP extensions..."

$missingExts = @()
foreach ($ext in @("pdo_sqlite", "mbstring", "xml", "curl", "zip", "gd", "bcmath", "openssl")) {
    $loaded = & $PHP -r "echo extension_loaded('$ext') ? 'yes' : 'no';" 2>$null
    if ($loaded -ne "yes") { $missingExts += $ext }
}

if ($missingExts.Count -gt 0) {
    Write-Err "Missing PHP extensions: $($missingExts -join ', ')"
    Write-Host "    Enable them in your php.ini file." -ForegroundColor Yellow

    $phpIni = & $PHP -r "echo php_ini_loaded_file();" 2>$null
    if ($phpIni) {
        Write-Host "    Your php.ini is at: $phpIni" -ForegroundColor Yellow
    }
    Read-Host "Press Enter to exit"
    exit 1
} else {
    Write-Ok "All required PHP extensions are loaded"
}

# ---------------------------------------------------------------------------
# 5. Set up environment file
# ---------------------------------------------------------------------------
Write-Step "Setting up environment..."

Push-Location $ProjectRoot

$envFile = Join-Path $ProjectRoot ".env"
$envExample = Join-Path $ProjectRoot ".env.example"

if (-not (Test-Path $envFile)) {
    if (Test-Path $envExample) {
        Copy-Item $envExample $envFile
        Write-Ok "Created .env from .env.example"
    } else {
        Write-Err ".env.example not found - is this the right directory?"
        Pop-Location
        exit 1
    }
} else {
    Write-Ok ".env already exists"
}

# Ensure SQLite is the database (simplest for local installs)
$envContent = Get-Content $envFile -Raw
if ($envContent -match "DB_CONNECTION=(?!sqlite)") {
    $envContent = $envContent -replace "DB_CONNECTION=\w+", "DB_CONNECTION=sqlite"
    Set-Content $envFile $envContent -NoNewline
    Write-Ok "Set database to SQLite"
}

# Create SQLite database file if it doesn't exist
$dbPath = Join-Path $ProjectRoot "database\database.sqlite"
if (-not (Test-Path $dbPath)) {
    New-Item -Path $dbPath -ItemType File -Force | Out-Null
    Write-Ok "Created SQLite database file"
} else {
    Write-Ok "SQLite database already exists"
}

# Set APP_URL to localhost
$envContent = Get-Content $envFile -Raw
$envContent = $envContent -replace "APP_URL=http://[^\r\n]+", "APP_URL=http://localhost:$Port"
Set-Content $envFile $envContent -NoNewline

# ---------------------------------------------------------------------------
# 6. Install PHP dependencies
# ---------------------------------------------------------------------------
Write-Step "Installing PHP dependencies (this may take a few minutes)..."

$spinner = @('|', '/', '-', '\')
$composerLog = Join-Path $env:TEMP "capintake-composer.log"
"" | Set-Content $composerLog

$composerProc = Start-Process -FilePath $COMPOSER `
    -ArgumentList "install", "--no-interaction", "--working-dir=$ProjectRoot" `
    -NoNewWindow -PassThru `
    -RedirectStandardOutput $composerLog `
    -RedirectStandardError (Join-Path $env:TEMP "capintake-composer-err.log")

$spinIdx = 0
$packageCount = 0
$lastSize = 0

while (-not $composerProc.HasExited) {
    $elapsed = [math]::Round(((Get-Date) - $composerProc.StartTime).TotalSeconds)

    # Check log file for new content
    try {
        $currentSize = (Get-Item $composerLog -ErrorAction SilentlyContinue).Length
        if ($currentSize -gt $lastSize) {
            $newLines = Get-Content $composerLog -Tail 5 -ErrorAction SilentlyContinue
            foreach ($line in $newLines) {
                if ($line -match "Installing\s+(\S+)") {
                    $packageCount++
                    $pkgName = $Matches[1]
                }
            }
            $lastSize = $currentSize
        }
    } catch { }

    if ($packageCount -gt 0) {
        Write-Host "`r    $($spinner[$spinIdx % 4]) Installed $packageCount packages so far... (${elapsed}s)                    " -NoNewline -ForegroundColor DarkGray
    } else {
        Write-Host "`r    $($spinner[$spinIdx % 4]) Resolving dependencies... (${elapsed}s)                                    " -NoNewline -ForegroundColor DarkGray
    }
    $spinIdx++
    Start-Sleep -Milliseconds 300
}

$composerProc.WaitForExit()
$composerExit = $composerProc.ExitCode
Write-Host ""

Remove-Item $composerLog -ErrorAction SilentlyContinue
Remove-Item (Join-Path $env:TEMP "capintake-composer-err.log") -ErrorAction SilentlyContinue

if ($composerExit -ne 0) {
    Write-Err "Composer install failed (exit code $composerExit). Run manually to see details:"
    Write-Err "  $COMPOSER install --no-interaction --working-dir=$ProjectRoot"
    Pop-Location
    exit 1
}
Write-Ok "PHP dependencies installed ($packageCount packages)"

# ---------------------------------------------------------------------------
# 7. Generate APP_KEY
# ---------------------------------------------------------------------------
Write-Step "Generating application key..."

$keyCheck = & $PHP artisan key:check 2>$null
$envContent = Get-Content $envFile -Raw
if ($envContent -match "APP_KEY=\s*$" -or $envContent -match "APP_KEY=$") {
    & $PHP artisan key:generate --force 2>$null | Out-Null
    Write-Ok "Application key generated"
} else {
    Write-Ok "Application key already set"
}

# ---------------------------------------------------------------------------
# 8. Install Node dependencies and build assets
# ---------------------------------------------------------------------------
Write-Step "Installing Node.js dependencies..."

$npmLog = Join-Path $env:TEMP "capintake-npm.log"
$npmProc = Start-Process -FilePath $NPM -ArgumentList "ci", "--prefix", $ProjectRoot `
    -NoNewWindow -PassThru `
    -RedirectStandardOutput $npmLog `
    -RedirectStandardError (Join-Path $env:TEMP "capintake-npm-err.log")

$spinIdx = 0
while (-not $npmProc.HasExited) {
    $elapsed = [math]::Round(((Get-Date) - $npmProc.StartTime).TotalSeconds)
    Write-Host "`r    $($spinner[$spinIdx % 4]) Installing npm packages... (${elapsed}s elapsed)                        " -NoNewline -ForegroundColor DarkGray
    $spinIdx++
    Start-Sleep -Milliseconds 300
}
$npmProc.WaitForExit()
Write-Host ""
Remove-Item $npmLog, (Join-Path $env:TEMP "capintake-npm-err.log") -ErrorAction SilentlyContinue

if ($npmProc.ExitCode -ne 0) {
    # Fallback to npm install if ci fails (no lock file)
    Write-Warn "npm ci failed, trying npm install..."
    & $NPM install --prefix $ProjectRoot 2>&1 | Out-Null
}
Write-Ok "Node.js dependencies installed"

Write-Step "Building frontend assets..."

$buildLog = Join-Path $env:TEMP "capintake-build.log"
$buildProc = Start-Process -FilePath $NPM -ArgumentList "run", "build", "--prefix", $ProjectRoot `
    -NoNewWindow -PassThru `
    -RedirectStandardOutput $buildLog `
    -RedirectStandardError (Join-Path $env:TEMP "capintake-build-err.log")

$spinIdx = 0
while (-not $buildProc.HasExited) {
    $elapsed = [math]::Round(((Get-Date) - $buildProc.StartTime).TotalSeconds)
    Write-Host "`r    $($spinner[$spinIdx % 4]) Building CSS and JavaScript... (${elapsed}s elapsed)                    " -NoNewline -ForegroundColor DarkGray
    $spinIdx++
    Start-Sleep -Milliseconds 300
}
$buildProc.WaitForExit()
Write-Host ""
Remove-Item $buildLog, (Join-Path $env:TEMP "capintake-build-err.log") -ErrorAction SilentlyContinue

if ($buildProc.ExitCode -ne 0) {
    Write-Err "Asset build failed. Run manually: $NPM run build --prefix $ProjectRoot"
    Pop-Location
    exit 1
}
Write-Ok "Frontend assets built"

# ---------------------------------------------------------------------------
# 9. Run database migrations and seed
# ---------------------------------------------------------------------------
Write-Step "Setting up database..."

& $PHP artisan migrate --force 2>&1 | ForEach-Object {
    if ($_ -match "Migrating|created|INFO") { Write-Host "    $_" -ForegroundColor DarkGray }
}
Write-Ok "Database tables created"

# Seed reference data (seeders use updateOrCreate, safe to re-run)
Write-Host "    Seeding reference data (FPL guidelines, NPI indicators, programs)..." -ForegroundColor DarkGray
& $PHP artisan db:seed --force 2>&1 | Out-Null
Write-Ok "Reference data seeded"

# Create storage symlink
& $PHP artisan storage:link 2>$null | Out-Null

# ---------------------------------------------------------------------------
# 10. Create Start and Stop scripts
# ---------------------------------------------------------------------------
Write-Step "Creating launcher scripts..."

$startBat = Join-Path $ProjectRoot "Start-CAPIntake.bat"
$startContent = @'
@echo off
title CAPIntake
echo.
echo  ========================================
echo   CAPIntake is starting...
echo  ========================================
echo.

cd /d "{{PROJECT_ROOT}}"

:: Start the server in the background
start "CAPIntake Server" /min "{{PHP}}" artisan serve --port={{PORT}} --host=127.0.0.1

:: Wait for the server to be ready
timeout /t 3 /nobreak >nul

:: Open the browser
start http://localhost:{{PORT}}

echo  CAPIntake is running at http://localhost:{{PORT}}
echo.
echo  To stop: close this window and the minimized "CAPIntake Server" window,
echo  or run Stop-CAPIntake.bat
echo.
echo  Press any key to stop the server and exit...
pause >nul

:: Kill the server process
taskkill /fi "WINDOWTITLE eq CAPIntake Server*" /f >nul 2>&1
'@
$startContent = $startContent.Replace('{{PROJECT_ROOT}}', $ProjectRoot).Replace('{{PHP}}', $PHP).Replace('{{PORT}}', "$Port")
Set-Content $startBat $startContent
Write-Ok "Created Start-CAPIntake.bat"

$stopBat = Join-Path $ProjectRoot "Stop-CAPIntake.bat"
$stopContent = @'
@echo off
echo Stopping CAPIntake...
taskkill /fi "WINDOWTITLE eq CAPIntake Server*" /f >nul 2>&1
echo CAPIntake stopped.
timeout /t 2 >nul
'@
Set-Content $stopBat $stopContent
Write-Ok "Created Stop-CAPIntake.bat"

# ---------------------------------------------------------------------------
# 11. Create desktop shortcut
# ---------------------------------------------------------------------------
Write-Step "Creating desktop shortcut..."

try {
    $desktopPath = [Environment]::GetFolderPath("Desktop")
    $shortcutPath = Join-Path $desktopPath "CAPIntake.lnk"

    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = $startBat
    $shortcut.WorkingDirectory = $ProjectRoot
    $shortcut.Description = "Start CAPIntake case management system"
    $shortcut.WindowStyle = 1  # Normal window

    # Use a web-app style icon if available, otherwise default
    $icoPath = Join-Path $ProjectRoot "public\favicon.ico"
    if (Test-Path $icoPath) {
        $shortcut.IconLocation = $icoPath
    }

    $shortcut.Save()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($shell) | Out-Null
    Write-Ok "Desktop shortcut created: CAPIntake.lnk"
} catch {
    Write-Warn "Could not create desktop shortcut: $_"
    Write-Warn "You can start CAPIntake by double-clicking Start-CAPIntake.bat"
}

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
Pop-Location

Write-Host ""
Write-Host "  ================================================================" -ForegroundColor Green
Write-Host "   Installation complete!" -ForegroundColor Green
Write-Host "  ================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "  To start CAPIntake:" -ForegroundColor White
Write-Host "    - Double-click the 'CAPIntake' shortcut on your desktop" -ForegroundColor White
Write-Host "    - Or double-click Start-CAPIntake.bat in the project folder" -ForegroundColor White
Write-Host ""
Write-Host "  The app will open at: " -NoNewline; Write-Host "http://localhost:$Port" -ForegroundColor Cyan
Write-Host ""
Write-Host "  First time? The setup wizard will guide you through configuring" -ForegroundColor DarkGray
Write-Host "  your agency name, branding, admin account, and programs." -ForegroundColor DarkGray
Write-Host ""

# Ask if they want to start now
$startNow = Read-Host "  Start CAPIntake now? (Y/n)"
if ($startNow -ne "n" -and $startNow -ne "N") {
    Write-Host ""
    Write-Step "Starting CAPIntake..."
    Start-Process $startBat
}
