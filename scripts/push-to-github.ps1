# Push penta-ai to GitHub (nikhilsabu/penta-ai)
# Run once in PowerShell from project root: .\scripts\push-to-github.ps1

$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

Write-Host "Checking GitHub CLI auth..." -ForegroundColor Cyan
$auth = gh auth status 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Login required. Complete the browser/device flow when prompted." -ForegroundColor Yellow
    gh auth login --hostname github.com --git-protocol https --web
}

Write-Host "Creating repo if needed..." -ForegroundColor Cyan
gh repo view nikhilsabu/penta-ai 2>$null
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
