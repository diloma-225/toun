@echo off
cd /d "%~dp0"
call "C:\flutter\flutter\bin\flutter.bat" run -d chrome --web-port 5050
pause
