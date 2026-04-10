@echo off
set PATH=C:\php;%PATH%
echo Starte FFR Webserver auf http://localhost:8000 ...
echo Druecke Ctrl+C zum Beenden.
start http://localhost:8000
php -S localhost:8000
