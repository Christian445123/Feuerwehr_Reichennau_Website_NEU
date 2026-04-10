@echo off
chcp 65001 >nul
set PATH=C:\php;%PATH%
cd /d "%~dp0"

echo ============================================
echo   FFR Autocommit + Sync
echo   Ueberwacht Aenderungen, committet und pusht
echo   Druecke Ctrl+C zum Beenden
echo ============================================
echo.

:loop
git add -A >nul 2>&1
for /f %%i in ('git status --porcelain') do (
    echo [%date% %time%] Aenderungen gefunden, committe...
    git commit -m "Auto-Update %date% %time%" >nul 2>&1
    echo [%date% %time%] Pushe zu Remote...
    git push >nul 2>&1
    if %errorlevel%==0 (
        echo [%date% %time%] Erfolgreich synchronisiert.
    ) else (
        echo [%date% %time%] Push fehlgeschlagen - pruefen!
    )
    echo.
    goto wait
)
:wait
ping -n 31 127.0.0.1 >nul
goto loop
