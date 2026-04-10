@echo off
chcp 65001 >nul
set PATH=C:\php;%PATH%
cd /d "%~dp0"

echo ============================================
echo   FFR Autocommit - Ueberwacht Aenderungen
echo   Druecke Ctrl+C zum Beenden
echo ============================================
echo.

:loop
git add -A >nul 2>&1
for /f %%i in ('git status --porcelain') do (
    echo [%date% %time%] Aenderungen gefunden, committe...
    git commit -m "Auto-Update %date% %time%" >nul 2>&1
    echo [%date% %time%] Commit erstellt.
    echo.
)
timeout /t 30 /nobreak >nul
goto loop
