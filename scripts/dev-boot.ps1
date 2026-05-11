param(
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'

Set-Location -Path $PSScriptRoot\..

function Test-PortFree {
    param([int]$PortToTest)

    $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $PortToTest)

    try {
        $listener.Start()
        $listener.Stop()
        return $true
    }
    catch {
        return $false
    }
}

if (-not (Test-PortFree -PortToTest $Port)) {
    Write-Host "Port $Port is already in use. Stop the process or run: .\\scripts\\dev-boot.ps1 -Port 8001" -ForegroundColor Yellow
    exit 1
}

Write-Host 'Compiling AssetMapper assets...' -ForegroundColor Cyan
php bin/console asset-map:compile

Write-Host "Starting dev server on http://127.0.0.1:$Port" -ForegroundColor Green
Write-Host 'Press Ctrl+C to stop.' -ForegroundColor DarkGray

php -S 127.0.0.1:$Port -t public public/index.php