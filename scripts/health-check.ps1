#!/usr/bin/env pwsh
# =========================================================
# Script de vérification de la santé des conteneurs Podman
# =========================================================
# Description : Vérifie l'état de santé des 3 conteneurs Drupal
# Utilisation  : .\health-check.ps1
# Auteur      : Abdellah Sahraoui
# Date        : 2025-01-28
# =========================================================

Write-Host "`n╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   VÉRIFICATION DE LA SANTÉ DES CONTENEURS PODMAN      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Liste des conteneurs à vérifier
$containers = @("web", "php", "db")

$allHealthy = $true

foreach ($container in $containers) {
    Write-Host "Vérification du conteneur: $container" -NoNewline
    
    # Vérifier si le conteneur existe
    $exists = podman ps -a --filter "name=^${container}$" --format "{{.Names}}" 2>$null
    
    if (-not $exists) {
        Write-Host " ... " -NoNewline
        Write-Host "[ABSENT]" -ForegroundColor Red
        $allHealthy = $false
        continue
    }
    
    # Vérifier si le conteneur est en cours d'exécution
    $running = podman ps --filter "name=^${container}$" --format "{{.Names}}" 2>$null
    
    if (-not $running) {
        Write-Host " ... " -NoNewline
        Write-Host "[ARRÊTÉ]" -ForegroundColor Yellow
        $allHealthy = $false
        continue
    }
    
    # Vérifier le statut de santé
    $healthStatus = podman inspect --format "{{.State.Health.Status}}" $container 2>$null
    
    if (-not $healthStatus -or $healthStatus -eq "<no value>") {
        # Pas de healthcheck configuré, vérifier juste l'état
        $state = podman inspect --format "{{.State.Status}}" $container 2>$null
        if ($state -eq "running") {
            Write-Host " ... " -NoNewline
            Write-Host "[EN COURS (pas de healthcheck)]" -ForegroundColor Cyan
        } else {
            Write-Host " ... " -NoNewline
            Write-Host "[$state]" -ForegroundColor Yellow
            $allHealthy = $false
        }
    } else {
        switch ($healthStatus) {
            "healthy" {
                Write-Host " ... " -NoNewline
                Write-Host "[SAIN ✓]" -ForegroundColor Green
            }
            "unhealthy" {
                Write-Host " ... " -NoNewline
                Write-Host "[PROBLÈME ✗]" -ForegroundColor Red
                $allHealthy = $false
                
                # Afficher les derniers logs en cas de problème
                Write-Host "   Derniers logs:" -ForegroundColor Yellow
                podman logs --tail 5 $container 2>&1 | ForEach-Object {
                    Write-Host "   $_" -ForegroundColor Gray
                }
            }
            "starting" {
                Write-Host " ... " -NoNewline
                Write-Host "[DÉMARRAGE ⟳]" -ForegroundColor Yellow
            }
            default {
                Write-Host " ... " -NoNewline
                Write-Host "[$healthStatus]" -ForegroundColor Yellow
            }
        }
    }
}

Write-Host ""

if ($allHealthy) {
    Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║   ✓ Tous les conteneurs sont opérationnels            ║" -ForegroundColor Green
    Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Green
    Write-Host ""
    exit 0
} else {
    Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Red
    Write-Host "║   ✗ Certains conteneurs ont des problèmes             ║" -ForegroundColor Red
    Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Red
    Write-Host ""
    Write-Host "Pour plus de détails, exécutez:" -ForegroundColor Yellow
    Write-Host "  podman ps -a" -ForegroundColor Cyan
    Write-Host "  podman logs <nom_conteneur>" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}
