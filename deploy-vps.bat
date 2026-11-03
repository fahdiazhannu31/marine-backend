@echo off
echo ============================================
echo Deployment Script - SEPA Fullstack to VPS
echo ============================================
echo.

REM Check if git is available
git --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Git not found. Please install Git first.
    pause
    exit /b 1
)

echo [1/5] Checking for uncommitted changes...
git status --short
echo.

echo [2/5] Do you want to commit and push? (Y/N)
set /p commit_choice=Enter your choice: 

if /i "%commit_choice%"=="Y" (
    echo.
    set /p commit_msg=Enter commit message: 
    
    echo [3/5] Committing changes...
    git add .
    git commit -m "%commit_msg%"
    
    echo [4/5] Pushing to remote...
    git push origin main
    
    if errorlevel 1 (
        echo ERROR: Git push failed. Check your connection and credentials.
        pause
        exit /b 1
    )
    
    echo.
    echo ============================================
    echo SUCCESS: Code pushed to remote repository
    echo ============================================
    echo.
    echo NEXT STEPS - On VPS:
    echo 1. SSH to your VPS
    echo 2. cd /path/to/sepafullstack
    echo 3. git pull origin main
    echo 4. cd frontend ^&^& npm run build
    echo 5. Test the application
    echo.
    echo OR use the deploy script on VPS if available
    echo.
) else (
    echo.
    echo Deployment cancelled by user.
    echo.
)

pause
