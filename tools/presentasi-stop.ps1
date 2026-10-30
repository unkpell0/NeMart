#requires -Version 5.1
<#
    Menghentikan server PHP NeMart dan Cloudflare Tunnel yang dijalankan
    oleh presentasi-start.ps1.
#>
$ErrorActionPreference = 'SilentlyContinue'
$port = 8123
$cloudflared = Join-Path $PSScriptRoot 'bin\cloudflared.exe'
$ids = @()

try {
    Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction Stop |
        ForEach-Object { $ids += $_.OwningProcess }
} catch { }

Get-Process -Name 'cloudflared' -ErrorAction SilentlyContinue |
    Where-Object { $_.Path -eq $cloudflared } |
    ForEach-Object { $ids += $_.Id }

$ids = $ids | Sort-Object -Unique
$stopped = 0

foreach ($processId in $ids) {
    try {
        Stop-Process -Id $processId -Force -ErrorAction Stop
        Write-Host ("Dihentikan: PID {0}" -f $processId)
        $stopped++
    } catch {
        Write-Host ("Gagal menghentikan PID {0}: {1}" -f $processId, $_.Exception.Message)
    }
}

if ($stopped -eq 0) {
    Write-Host 'Tidak ada server/tunnel NeMart yang berjalan.'
} else {
    Write-Host ("Selesai. {0} proses dihentikan." -f $stopped)
}
