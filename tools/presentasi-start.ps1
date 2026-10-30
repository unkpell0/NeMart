#requires -Version 5.1
<#
    Menyalakan server PHP NeMart + Cloudflare Quick Tunnel.
    Jalankan lewat Demo-Presentasi.bat atau:
      powershell -ExecutionPolicy Bypass -File presentasi-start.ps1
#>
param(
    [switch]$NoBrowser
)

$ErrorActionPreference = 'Stop'
$tools = $PSScriptRoot
$app = Split-Path -Parent $tools
$port = 8123
$cloudflared = Join-Path $tools 'bin\cloudflared.exe'
$urlFile = Join-Path $app 'data\url-presentasi.txt'
$logErr = Join-Path $app 'data\tunnel-presentasi.log'
$logOut = Join-Path $app 'data\tunnel-presentasi.out.log'

function Write-Step([string]$text) {
    Write-Host "[NeMart] $text"
}

Write-Step 'Menyiapkan demo presentasi...'

# --- PHP -------------------------------------------------------------------
$php = $null
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if ($phpCommand) {
    $php = $phpCommand.Source
} else {
    foreach ($candidate in @('C:\xampp\php\php.exe', 'C:\laragon\bin\php\php.exe')) {
        if (Test-Path $candidate) { $php = $candidate; break }
    }
}
if (-not $php) {
    throw 'PHP tidak ditemukan. Install XAMPP atau tambahkan php ke PATH.'
}

$listening = $false
$tcp = New-Object System.Net.Sockets.TcpClient
try {
    $tcp.Connect('127.0.0.1', $port)
    $listening = $true
} catch {
    $listening = $false
} finally {
    $tcp.Close()
}

if (-not $listening) {
    Write-Step "Menyalakan server PHP di http://127.0.0.1:$port ..."
    Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$port", '-t', '.') -WorkingDirectory $app -WindowStyle Hidden
} else {
    Write-Step "Server PHP sudah berjalan di port $port."
}

$healthy = $false
for ($i = 0; $i -lt 25; $i++) {
    try {
        $health = Invoke-WebRequest -Uri "http://127.0.0.1:$port/healthz.php" -UseBasicParsing -TimeoutSec 3
        if ($health.StatusCode -eq 200) { $healthy = $true; break }
    } catch { }
    Start-Sleep -Milliseconds 800
}
if (-not $healthy) {
    throw "Server PHP tidak merespons di port $port. Cek apakah port dipakai aplikasi lain."
}
Write-Step 'Server PHP sehat.'

# --- cloudflared -----------------------------------------------------------
if (-not (Test-Path $cloudflared)) {
    Write-Step 'cloudflared belum ada, mengunduh (~55 MB)...'
    New-Item -ItemType Directory -Force -Path (Split-Path $cloudflared) | Out-Null
    Invoke-WebRequest -UseBasicParsing -Uri 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe' -OutFile $cloudflared
    Write-Step 'cloudflared selesai diunduh.'
}

$existing = Get-Process -Name 'cloudflared' -ErrorAction SilentlyContinue |
    Where-Object { $_.Path -eq $cloudflared }

if ($existing) {
    if (Test-Path $urlFile) {
        $knownUrl = (Get-Content $urlFile -First 1).Trim()
        if ($knownUrl) {
            Write-Step "Tunnel sudah berjalan: $knownUrl"
            if (-not $NoBrowser) { Start-Process $knownUrl }
            exit 0
        }
    }
    Write-Step 'Tunnel cloudflared sudah berjalan, tetapi URL tidak tercatat. Hentikan dulu dengan presentasi-stop.ps1 lalu jalankan ulang.'
    exit 1
}

New-Item -ItemType Directory -Force -Path (Split-Path $logErr) | Out-Null
Remove-Item $logErr, $logOut -Force -ErrorAction SilentlyContinue

Write-Step 'Membuat Cloudflare Quick Tunnel...'
Start-Process -FilePath $cloudflared `
    -ArgumentList @('tunnel', '--url', "http://127.0.0.1:$port", '--no-autoupdate', '--protocol', 'http2') `
    -WorkingDirectory $app -WindowStyle Hidden `
    -RedirectStandardError $logErr -RedirectStandardOutput $logOut

$publicUrl = $null
for ($i = 0; $i -lt 90; $i++) {
    Start-Sleep -Milliseconds 500
    if (Test-Path $logErr) {
        try {
            $match = Select-String -Path $logErr -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' -ErrorAction SilentlyContinue | Select-Object -First 1
            if ($match) {
                $publicUrl = $match.Matches[0].Value
                break
            }
        } catch { }
    }
}

if (-not $publicUrl) {
    throw "URL tunnel tidak ditemukan. Periksa log: $logErr"
}

$publicUrl | Set-Content -Path $urlFile -Encoding ASCII

$desktop = [Environment]::GetFolderPath('Desktop')
if ($desktop) {
    $desktopFile = Join-Path $desktop 'NeMart-URL-Presentasi.txt'
    @(
        'NeMart - URL presentasi',
        'URL : ' + $publicUrl,
        'Dibuat: ' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'),
        '',
        'Jalankan Demo-Presentasi.bat lagi jika URL mati (URL akan berubah).'
    ) | Set-Content -Path $desktopFile -Encoding ASCII
}

Write-Host ''
Write-Host '==============================================================' -ForegroundColor Green
Write-Host '  DEMO NEMART ONLINE' -ForegroundColor Green
Write-Host ('  ' + $publicUrl) -ForegroundColor Cyan
Write-Host '==============================================================' -ForegroundColor Green
Write-Host 'URL disimpan di Desktop\NeMart-URL-Presentasi.txt'
Write-Host 'Biarkan laptop menyala dan terhubung internet selama presentasi.'
Write-Host ''

if (-not $NoBrowser) {
    Start-Process $publicUrl
}
