#!/usr/bin/env pwsh
# =========================================================
# Script d'arrêt des conteneurs Podman Drupal
# =========================================================
# Description : Arrête proprement tous les conteneurs Drupal
# Utilisation  : .\stop-containers.ps1
# Auteur      : Abdellah Sahraoui
# Date        : 2025-01-28
# =========================================================

Write-Host "`n╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   ARRÊT DES CONTENEURS PODMAN DRUPAL                  ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Naviguer vers le répertoire du projet
$PROJECT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $PROJECT_DIR

Write-Host "Arrêt des conteneurs..." -ForegroundColor Yellow

try {
    # Arrêter les conteneurs avec podman compose
    podman compose down
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
        Write-Host "║   ✓ Conteneurs arrêtés avec succès                    ║" -ForegroundColor Green
        Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Green
        Write-Host ""
        Write-Host "Pour redémarrer les conteneurs:" -ForegroundColor Cyan
        Write-Host "  .\scripts\start-containers.ps1" -ForegroundColor Yellow
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Red
        Write-Host "║   ✗ Erreur lors de l'arrêt des conteneurs             ║" -ForegroundColor Red
        Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Red
        Write-Host ""
        exit 1
    }
} catch {
    Write-Host ""
    Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Red
    Write-Host "║   ✗ Erreur critique                                   ║" -ForegroundColor Red
    Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Red
    Write-Host ""
    Write-Host "Erreur: $_" -ForegroundColor Red
    Write-Host ""
    exit 1
}
