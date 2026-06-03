$root = $PSScriptRoot
Write-Host "Project folder: $root"
$router = Join-Path $root "router.php"
if (-not (Test-Path $router)) {
    Write-Error "router.php not found in $root"
    exit 1
}
php -S localhost:8000 -t $root $router
