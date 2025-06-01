# File: gitpush.ps1
param(
    [string]$message = "Update"
)

git add .
git commit -m "$message"
git push
