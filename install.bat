@echo off
title CAPIntake Installer
echo.
echo  ========================================
echo   CAPIntake Installer
echo  ========================================
echo.
echo  This will set up CAPIntake on your computer.
echo  It may take a few minutes.
echo.
pause

powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0scripts\Install-CAPIntake.ps1" -ProjectRoot "%~dp0."

echo.
pause
