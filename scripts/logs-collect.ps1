#!/usr/bin/env pwsh
# =========================================================
# Script de collecte des logs des conteneurs Podman
# =========================================================
# Description : Récupère les logs de tous les conteneurs et les sauvegarde
# Utilisation  : .\logs-collect.ps1
# Auteur      : Abdellah Sahraoui
# Date        : 2025-01-28
# =========================================================

Write-Host "`n╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   COLLECTE DES LOGS DES CONTENEURS PODMAN             ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Répertoire de base pour les logs
$LOGS_BASE_DIR = Join-Path $PSScriptRoot "..\logs"

# Créer les répertoires de logs s'ils n'existent pas
$logDirs = @("apache", "php", "postgres")
foreach ($dir in $logDirs) {
    $path = Join-Path $LOGS_BASE_DIR $dir
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
        Write-Host "✓ Répertoire créé: $path" -ForegroundColor Green
    }
}

# Fonction pour collecter les logs d'un conteneur
function Collect-ContainerLogs {
    param(
        [string]$containerName,
        [string]$logSubDir
    )
    
    Write-Host "Collecte des logs du conteneur '$containerName'..." -NoNewline
    
    # Vérifier si le conteneur existe
    $exists = podman ps -a --filter "name=^${containerName}$" --format "{{.Names}}" 2>$null
    
    if (-not $exists) {
        Write-Host " [ABSENT]" -ForegroundColor Yellow
        return
    }
    
    # Chemins de sauvegarde
    $logDir = Join-Path $LOGS_BASE_DIR $logSubDir
    $outputFile = Join-Path $logDir "container-logs.log"
    $timestampFile = Join-Path $logDir "last-collect.txt"
    
    # Récupérer les logs
    try {
        podman logs $containerName > $outputFile 2>&1
        
        # Enregistrer l'horodatage
        Get-Date -Format "yyyy-MM-dd HH:mm:ss" | Out-File -FilePath $timestampFile -Encoding utf8
        
        $fileSize = (Get-Item $outputFile).Length
        $sizeKB = [math]::Round($fileSize / 1KB, 2)
        
        Write-Host " [OK - $sizeKB KB]" -ForegroundColor Green
        Write-Host "  └─> Sauvegardé: $outputFile" -ForegroundColor Gray
    } catch {
        Write-Host " [ERREUR]" -ForegroundColor Red
        Write-Host "  └─> $_" -ForegroundColor Red
    }
}

# Collecter les logs de chaque conteneur
Collect-ContainerLogs -containerName "web" -logSubDir "apache"
Collect-ContainerLogs -containerName "php" -logSubDir "php"
Collect-ContainerLogs -containerName "db" -logSubDir "postgres"

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║   ✓ Collecte des logs terminée                        ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "Les logs sont disponibles dans:" -ForegroundColor Cyan
Write-Host "  $LOGS_BASE_DIR\" -ForegroundColor Yellow
Write-Host ""
