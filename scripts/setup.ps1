$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (Test-Path '.env') {
    Write-Host '.env already exists; keeping existing credentials.'
    exit 0
}
function New-LocalPassword {
    $bytes = New-Object byte[] 24
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return 'Pt!9' + [BitConverter]::ToString($bytes).Replace('-', '')
}
$saPassword = New-LocalPassword
$appPassword = New-LocalPassword
$content = @"
MSSQL_SA_PASSWORD=$saPassword
DB_PASSWORD=$appPassword
DEMO_PASSWORD=Password123!
WEB_PORT=8080
"@
[IO.File]::WriteAllText((Join-Path (Get-Location) '.env'), $content + "`n", (New-Object Text.UTF8Encoding($false)))
Write-Host 'Created .env with random database passwords. Do not commit this file.'
Write-Host 'Next: docker compose up -d --build'
