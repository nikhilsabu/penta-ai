# Push penta-ai to GitHub (nikhilsabu/penta-ai)
# Run in PowerShell: .\scripts\push-to-github.ps1

$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

function Test-GhAuth {
    gh auth status *> $null
    return $LASTEXITCODE -eq 0
}

Write-Host "Checking GitHub CLI auth..." -ForegroundColor Cyan
if (-not (Test-GhAuth)) {
    Write-Host "Login required. Complete the browser/device flow when prompted." -ForegroundColor Yellow
    gh auth login --hostname github.com --git-protocol https --web
    if (-not (Test-GhAuth)) {
        throw "GitHub login was not completed."
    }
}

Write-Host "Creating repo if needed..." -ForegroundColor Cyan
gh repo view nikhilsabu/penta-ai *> $null
if ($LASTEXITCODE -ne 0) {
    gh repo create penta-ai --public --source=. --remote=origin --description "Pentame AI chatbot widget with OpenAI, RAG, and admin dashboard"
} else {
    git remote remove origin 2>$null
    git remote add origin https://github.com/nikhilsabu/penta-ai.git
}

$branch = git branch --show-current
Write-Host "Pushing branch $branch..." -ForegroundColor Cyan
git push -u origin $branch

Write-Host "Done: https://github.com/nikhilsabu/penta-ai" -ForegroundColor Green
