param(
    [string]$ServerHost = "188.137.179.54",
    [string]$User = "root",
    [int]$Port = 22,
    [string]$RemotePath = "/var/www/html",
    [string]$DbUser = "luxury_app",
    [string]$DbPass = "PonUnaClaveFuerte_2026",
    [switch]$SkipPush
)

$ErrorActionPreference = "Stop"

$plinkPath = "C:\Program Files\PuTTY\plink.exe"
if (-not (Test-Path $plinkPath)) {
    throw "No se encontro plink en: $plinkPath"
}

Write-Host "[1/4] Verificando repositorio local..."
$repoRoot = (& git rev-parse --show-toplevel).Trim()
$currentBranch = (& git rev-parse --abbrev-ref HEAD).Trim()

if (-not $repoRoot) {
    throw "No se pudo determinar la raiz del repositorio git."
}

if ($currentBranch -ne "master") {
    throw "Debes estar en master para desplegar. Branch actual: $currentBranch"
}

if (-not $SkipPush) {
    Write-Host "[2/4] Haciendo push a origin/master..."
    & git push origin master
    if ($LASTEXITCODE -ne 0) {
        throw "Fallo el push a origin/master."
    }
} else {
    Write-Host "[2/4] SkipPush activo: omitiendo push local."
}

Write-Host "[3/4] Desplegando en servidor..."
$remoteScript = @"
set -e
cd $RemotePath
git fetch origin
git checkout master
git reset --hard origin/master
sed -i \"/^define('DB_USER'/c\\define('DB_USER', '$DbUser');\" php/config.php
sed -i \"/^define('DB_PASS'/c\\define('DB_PASS', '$DbPass');\" php/config.php
php -r \"if(function_exists('opcache_reset')){opcache_reset(); echo 'OPCACHE_RESET\\n';}\"
php -r \"require '$RemotePath/php/config.php'; getDB(); echo 'DB_OK\\n';\"
git log --oneline -n 1
"@

$remoteScript = $remoteScript -replace "`r`n", "`n"

$remoteScriptB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($remoteScript))
$remoteCommand = "echo $remoteScriptB64 | base64 -d | bash"

$prevErrorActionPreference = $ErrorActionPreference
$ErrorActionPreference = "Continue"
$deployOutput = & $plinkPath -ssh "$User@$ServerHost" -P $Port $remoteCommand 2>&1
$nativeExitCode = $LASTEXITCODE
$ErrorActionPreference = $prevErrorActionPreference

$deployOutput | ForEach-Object { Write-Host $_ }

if ($nativeExitCode -ne 0) {
    throw "Fallo el despliegue remoto."
}

if ((($deployOutput | Out-String) -notmatch "DB_OK")) {
    throw "Deploy remoto sin confirmacion DB_OK. Revisa salida del servidor."
}

Write-Host "[4/4] Despliegue completado correctamente."
Write-Host "Servidor: $ServerHost"
Write-Host "Ruta remota: $RemotePath"