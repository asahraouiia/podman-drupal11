# Script de démarrage complet des conteneurs Drupal (PowerShell)
# Initialise Podman puis démarre/reconstruit les conteneurs

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

Write-Host "=== Démarrage des conteneurs Drupal ===" -ForegroundColor Cyan

# 1. Initialiser Podman/WSL
Write-Host "Étape 1: Initialisation de Podman..."
& "$ScriptDir\init-podman.ps1"

# 2. Se placer dans le répertoire du projet
Set-Location $ProjectRoot

# 3. Nettoyer les anciens conteneurs si nécessaire
Write-Host ""
Write-Host "Étape 2: Nettoyage des conteneurs existants..."
podman compose -f podman-compose.yml down 2>$null

# Vérifier et supprimer les conteneurs orphelins
$orphanContainers = podman ps -a --filter "name=^(web|php|db)$" --format "{{.Names}}" 2>$null
if ($orphanContainers) {
    Write-Host "Suppression des conteneurs orphelins: $orphanContainers"
    podman rm -f $orphanContainers
}

# 4. Reconstruire les images si demandé
if ($args[0] -eq "--rebuild" -or $args[0] -eq "-r") {
    Write-Host ""
    Write-Host "Étape 3: Reconstruction des images..."
    podman compose -f podman-compose.yml build
} else {
    Write-Host ""
    Write-Host "Étape 3: Utilisation des images existantes (utilisez --rebuild pour reconstruire)"
}

# 5. Démarrer les conteneurs
Write-Host ""
Write-Host "Étape 4: Démarrage des conteneurs..."
podman compose -f podman-compose.yml up -d

# 6. Attendre que les services soient prêts
Write-Host ""
Write-Host "Étape 5: Vérification de l'état des services..."
Start-Sleep -Seconds 3

# 7. Afficher l'état final
Write-Host ""
Write-Host "=== État des conteneurs ===" -ForegroundColor Cyan
podman ps --filter "name=^(web|php|db)$"

Write-Host ""
Write-Host "✓ Conteneurs démarrés avec succès" -ForegroundColor Green
Write-Host ""
Write-Host "Accès au site: http://localhost:8080" -ForegroundColor Yellow
Write-Host ""
Write-Host "Commandes utiles:"
Write-Host "  - Logs en temps réel: podman compose -f podman-compose.yml logs -f"
Write-Host "  - Shell PHP: podman exec -it php bash"
Write-Host "  - Shell Apache: podman exec -it web bash"
Write-Host "  - Arrêter: podman compose -f podman-compose.yml down"
