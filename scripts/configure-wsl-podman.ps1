@echo off
REM ################################################################################
REM Script de configuration WSL2 et Podman Machine pour Drupal 11
REM Optimise les ressources pour accelerer l'installation Composer
REM ################################################################################

echo.
echo ==========================================
echo   Configuration WSL2 et Podman Machine
echo ==========================================
echo.

REM Copier le fichier .wslconfig
echo [INFO] Copie de .wslconfig vers %USERPROFILE%\.wslconfig...
copy /Y "%~dp0..\config\.wslconfig" "%USERPROFILE%\.wslconfig"

if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Fichier .wslconfig copie avec succes
) else (
    echo [ERROR] Erreur lors de la copie de .wslconfig
    exit /b 1
)

echo.
echo Configuration WSL2 appliquee:
echo   - Memory: 6GB
echo   - Processors: 4
echo   - Swap: 2GB
echo.

REM Redemarrer WSL2
echo [WARNING] Redemarrage de WSL2 pour appliquer les changements...
wsl --shutdown

if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] WSL2 arrete avec succes
) else (
    echo [WARNING] WSL2 n'etait pas en cours d'execution
)

echo [INFO] Attente de 5 secondes...
timeout /t 5 /nobreak >nul

REM Arreter Podman Machine
echo.
echo [INFO] Arret de Podman Machine...
podman machine stop 2>nul

if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Podman Machine arretee
) else (
    echo [WARNING] Podman Machine n'etait pas en cours d'execution
)

REM Note: Podman Machine sous WSL utilise les ressources definies dans .wslconfig
REM La commande 'podman machine set' n'est pas supportee pour les machines WSL
echo.
echo [INFO] Podman Machine utilisera les ressources WSL2 definies dans .wslconfig

REM Demarrer Podman Machine
echo.
echo [INFO] Demarrage de Podman Machine...
podman machine start

if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Podman Machine demarree
) else (
    echo [ERROR] Erreur lors du demarrage de Podman Machine
    exit /b 1
)

echo.
echo [INFO] Attente du demarrage complet (15 secondes)...
timeout /t 15 /nobreak >nul

echo.
echo ==========================================
echo   Configuration terminee avec succes!
echo ==========================================
echo.
echo WSL2:
echo   - 6GB RAM
echo   - 4 CPUs
echo   - 2GB Swap
echo.
echo Podman Machine:
echo   - Utilise les ressources WSL2
echo   - 6GB RAM (partage avec WSL2)
echo   - 4 CPUs (partage avec WSL2)
echo.
echo Vous pouvez maintenant lancer ./scripts/SETUP_FULL.sh
echo.

pause
