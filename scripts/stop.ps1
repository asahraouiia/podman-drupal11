param()

# PowerShell stop script: stop and remove containers by name
Write-Host "Stopping containers: web, php, db"
podman stop web php db 2>$null | Out-Null || $true
podman rm -f web php db 2>$null | Out-Null || $true
Write-Host "Stopped and removed containers (if they existed)."
