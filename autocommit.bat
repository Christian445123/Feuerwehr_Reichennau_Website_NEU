@echo off
setlocal enabledelayedexpansion
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

:: Pruefen ob Aenderungen vorhanden
git diff --cached --quiet 2>nul
if %errorlevel%==0 goto wait

:: Geaenderte Dateien sammeln
set "FILES="
for /f "tokens=1,2" %%a in ('git diff --cached --name-status') do (
    if "%%a"=="A" set "FILES=!FILES! +%%b"
    if "%%a"=="M" set "FILES=!FILES! %%b"
    if "%%a"=="D" set "FILES=!FILES! -%%b"
)

:: Anzahl geaenderter Dateien zaehlen
set /a COUNT=0
for /f %%i in ('git diff --cached --numstat ^| find /c /v ""') do set /a COUNT=%%i

:: Commit-Message zusammenbauen
if %COUNT%==1 (
    for /f "tokens=1,2" %%a in ('git diff --cached --name-status') do (
        if "%%a"=="A" set "MSG=Neu: %%b"
        if "%%a"=="M" set "MSG=Update: %%b"
        if "%%a"=="D" set "MSG=Entfernt: %%b"
    )
) else (
    set "MSG=%COUNT% Dateien geaendert:%FILES%"
)

echo [%date% %time%] %MSG%
git commit -m "%MSG%" >nul 2>&1
echo [%date% %time%] Pushe zu Remote...
git push >nul 2>&1
if %errorlevel%==0 (
    echo [%date% %time%] Erfolgreich synchronisiert.
) else (
    echo [%date% %time%] Push fehlgeschlagen - pruefen!
)
echo.

:wait
ping -n 31 127.0.0.1 >nul
goto loop
