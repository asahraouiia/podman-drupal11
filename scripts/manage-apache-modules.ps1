# Script de gestion des modules Apache (PowerShell)
# Permet d'activer ou désactiver des modules Apache et de reconstruire le conteneur

param(
    [Parameter(Position=0)]
    [string]$Action,
    
    [Parameter(Position=1, ValueFromRemainingArguments=$true)]
    [string[]]$Modules,
    
    [switch]$NoRebuild,
    [switch]$Restart,
    [switch]$Help
)

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$Dockerfile = Join-Path $ProjectRoot "docker\apache\Dockerfile"

# Modules Apache couramment utilisés
$AvailableModules = @(
    "rewrite", "headers", "expires", "deflate", "ssl",
    "proxy", "proxy_http", "proxy_fcgi", "proxy_balancer", "proxy_wstunnel",
    "remoteip", "socache_shmcb", "auth_basic", "authn_file",
    "authz_user", "authz_groupfile", "mime", "dir", "alias", "filter"
)

function Show-Help {
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Script de gestion des modules Apache" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    Write-Host "Usage: .\manage-apache-modules.ps1 [OPTIONS] <action> <module1> [module2] [...]"
    Write-Host ""
    Write-Host "Actions:"
    Write-Host "  enable <module>      Active un ou plusieurs modules"
    Write-Host "  disable <module>     Désactive un ou plusieurs modules"
    Write-Host "  list                 Liste tous les modules disponibles"
    Write-Host "  status               Affiche les modules actuellement activés"
    Write-Host "  rebuild              Reconstruit le conteneur Apache"
    Write-Host ""
    Write-Host "Options:"
    Write-Host "  -NoRebuild          Ne reconstruit pas le conteneur (juste modifie le Dockerfile)"
    Write-Host "  -Restart            Redémarre le conteneur après reconstruction"
    Write-Host "  -Help               Affiche cette aide"
    Write-Host ""
    Write-Host "Exemples:"
    Write-Host "  .\manage-apache-modules.ps1 enable headers expires"
    Write-Host "  .\manage-apache-modules.ps1 disable ssl"
    Write-Host "  .\manage-apache-modules.ps1 list"
    Write-Host "  .\manage-apache-modules.ps1 status"
    Write-Host "  .\manage-apache-modules.ps1 enable deflate -Restart"
    Write-Host ""
    Write-Host "Modules couramment utilisés pour Drupal:"
    Write-Host "  - rewrite    : Clean URLs (déjà activé)"
    Write-Host "  - headers    : Gestion des en-têtes HTTP"
    Write-Host "  - expires    : Cache et expiration des contenus"
    Write-Host "  - deflate    : Compression gzip"
    Write-Host "  - ssl        : Support HTTPS"
    Write-Host ""
}

function Show-AvailableModules {
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Modules Apache disponibles" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    foreach ($module in $AvailableModules) {
        Write-Host "  ✓ mod_$module" -ForegroundColor Green
    }
    Write-Host ""
    Write-Host "Note: D'autres modules peuvent être disponibles. Consultez la documentation Apache."
}

function Get-CurrentModules {
    if (-not (Test-Path $Dockerfile)) {
        Write-Host "❌ Erreur: Dockerfile introuvable: $Dockerfile" -ForegroundColor Red
        exit 1
    }
    
    $content = Get-Content $Dockerfile -Raw
    $matches = [regex]::Matches($content, "'LoadModule (\w+)_module")
    $modules = @()
    foreach ($match in $matches) {
        $modules += $match.Groups[1].Value
    }
    return $modules
}

function Show-Status {
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Modules Apache actuellement activés" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    
    $modules = Get-CurrentModules
    
    if ($modules.Count -eq 0) {
        Write-Host "  Aucun module explicitement activé dans le Dockerfile" -ForegroundColor Yellow
    } else {
        foreach ($module in $modules) {
            Write-Host "  ✓ mod_$module" -ForegroundColor Green
        }
    }
    Write-Host ""
    
    Write-Host "Bloc de configuration actuel:" -ForegroundColor Blue
    Write-Host "────────────────────────────────────────────────────────────────" -ForegroundColor Yellow
    $content = Get-Content $Dockerfile
    $inBlock = $false
    $count = 0
    foreach ($line in $content) {
        if ($line -match "# Ensure proxy modules") {
            $inBlock = $true
        }
        if ($inBlock) {
            Write-Host $line
            $count++
            if ($count -ge 5) { break }
        }
    }
    Write-Host "────────────────────────────────────────────────────────────────" -ForegroundColor Yellow
    Write-Host ""
}

function Enable-Modules {
    param([string[]]$ModulesToEnable)
    
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Activation de modules Apache" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    
    $currentModules = Get-CurrentModules
    $newModules = [System.Collections.ArrayList]@($currentModules)
    
    foreach ($module in $ModulesToEnable) {
        if ($currentModules -contains $module) {
            Write-Host "  ⚠  mod_$module est déjà activé" -ForegroundColor Yellow
        } else {
            Write-Host "  ✓  Activation de mod_$module" -ForegroundColor Green
            $null = $newModules.Add($module)
        }
    }
    
    Update-Dockerfile $newModules
}

function Disable-Modules {
    param([string[]]$ModulesToDisable)
    
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Désactivation de modules Apache" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    
    $currentModules = Get-CurrentModules
    $newModules = [System.Collections.ArrayList]@()
    
    foreach ($module in $currentModules) {
        if ($ModulesToDisable -contains $module) {
            Write-Host "  ✓  Désactivation de mod_$module" -ForegroundColor Green
        } else {
            $null = $newModules.Add($module)
        }
    }
    
    Update-Dockerfile $newModules
}

function Update-Dockerfile {
    param([System.Collections.ArrayList]$Modules)
    
    # Créer une sauvegarde
    Copy-Item $Dockerfile "$Dockerfile.bak"
    Write-Host "  ℹ  Sauvegarde créée: $Dockerfile.bak" -ForegroundColor Blue
    
    # Construire le nouveau bloc de modules
    $moduleLines = @()
    for ($i = 0; $i -lt $Modules.Count; $i++) {
        $module = $Modules[$i]
        if ($i -eq $Modules.Count - 1) {
            $moduleLines += "    'LoadModule ${module}_module modules/mod_${module}.so'"
        } else {
            $moduleLines += "    'LoadModule ${module}_module modules/mod_${module}.so' \"
        }
    }
    
    $newBlock = @"
RUN printf '%s\n' ``
$($moduleLines -join "`n")`` >> /usr/local/apache2/conf/httpd.conf || true
"@
    
    # Lire le contenu du Dockerfile
    $content = Get-Content $Dockerfile -Raw
    
    # Remplacer le bloc RUN printf
    $pattern = "(?s)(# Ensure proxy modules.*?\n)RUN printf.*?true(\s*\n# Ensure our sites-enabled)"
    $replacement = "`$1$newBlock`$2"
    $newContent = $content -replace $pattern, $replacement
    
    # Écrire le nouveau contenu
    Set-Content -Path $Dockerfile -Value $newContent -NoNewline
    
    Write-Host "  ✓  Dockerfile mis à jour" -ForegroundColor Green
    Write-Host ""
}

function Rebuild-Container {
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Reconstruction du conteneur Apache" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    
    Set-Location $ProjectRoot
    
    Write-Host "  →  Construction de l'image..." -ForegroundColor Yellow
    podman compose -f podman-compose.yml build web
    
    Write-Host ""
    Write-Host "  ✓  Image reconstruite avec succès" -ForegroundColor Green
}

function Restart-Container {
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  Redémarrage du conteneur Apache" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
    
    Set-Location $ProjectRoot
    
    Write-Host "  →  Arrêt du conteneur web..." -ForegroundColor Yellow
    podman compose -f podman-compose.yml stop web
    
    Write-Host "  →  Démarrage du conteneur web..." -ForegroundColor Yellow
    podman compose -f podman-compose.yml up -d web
    
    Write-Host ""
    Write-Host "  ✓  Conteneur redémarré" -ForegroundColor Green
}

# Main logic
if ($Help -or -not $Action) {
    Show-Help
    exit 0
}

switch ($Action.ToLower()) {
    "list" {
        Show-AvailableModules
    }
    "status" {
        Show-Status
    }
    "rebuild" {
        Rebuild-Container
        if ($Restart) {
            Restart-Container
        }
    }
    "enable" {
        if ($Modules.Count -eq 0) {
            Write-Host "❌ Erreur: Aucun module spécifié" -ForegroundColor Red
            Write-Host "Usage: .\manage-apache-modules.ps1 enable <module1> [module2] ..."
            exit 1
        }
        
        Enable-Modules $Modules
        
        if (-not $NoRebuild) {
            Rebuild-Container
        }
        
        if ($Restart) {
            Restart-Container
        }
        
        Write-Host ""
        Write-Host "✓ Modules activés avec succès" -ForegroundColor Green
    }
    "disable" {
        if ($Modules.Count -eq 0) {
            Write-Host "❌ Erreur: Aucun module spécifié" -ForegroundColor Red
            Write-Host "Usage: .\manage-apache-modules.ps1 disable <module1> [module2] ..."
            exit 1
        }
        
        Disable-Modules $Modules
        
        if (-not $NoRebuild) {
            Rebuild-Container
        }
        
        if ($Restart) {
            Restart-Container
        }
        
        Write-Host ""
        Write-Host "✓ Modules désactivés avec succès" -ForegroundColor Green
    }
    default {
        Write-Host "❌ Erreur: Action inconnue: $Action" -ForegroundColor Red
        Write-Host ""
        Show-Help
        exit 1
    }
}
