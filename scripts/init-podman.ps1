# Script d'initialisation et démarrage Podman/WSL (PowerShell)
# Vérifie et démarre WSL + Podman Machine + Containers

$ErrorActionPreference = "Stop"

Write-Host "=== Initialisation de l'environnement Podman ===" -ForegroundColor Cyan

# 1. Vérifier si WSL est actif
Write-Host "Vérification de WSL..."
try {
    $wslList = wsl -l -v 2>&1
} catch {
    Write-Host "❌ Erreur: WSL n'est pas accessible" -ForegroundColor Red
    exit 1
}

# 2. Vérifier l'état de la machine Podman
Write-Host "Vérification de la machine Podman..."
$machineRunning = $false

if ($wslList -match "podman-machine-default.*Running") {
    $machineRunning = $true
    Write-Host "✓ Machine Podman déjà active" -ForegroundColor Green
} else {
    Write-Host "Machine Podman arrêtée. Redémarrage de WSL et Podman..."
    wsl --shutdown
    Start-Sleep -Seconds 3
    podman machine start
    Write-Host "✓ Machine Podman démarrée" -ForegroundColor Green
}

# 3. Vérifier la connectivité Podman
Write-Host "Vérification de la connectivité Podman..."
try {
    $null = podman ps 2>&1
} catch {
    Write-Host "⚠ Problème de connexion Podman. Redémarrage..." -ForegroundColor Yellow
    wsl --shutdown
    Start-Sleep -Seconds 3
    podman machine start
}

# 4. Afficher l'état
Write-Host ""
Write-Host "=== État de l'environnement ===" -ForegroundColor Cyan
podman --version
Write-Host ""
wsl -l -v
Write-Host ""
podman ps -a
Write-Host ""
Write-Host "✓ Environnement Podman prêt" -ForegroundColor Green
