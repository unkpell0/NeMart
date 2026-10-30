@echo off
title NeMart Online - Presentasi
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0presentasi-start.ps1"
echo.
echo Tekan tombol apa saja untuk menutup jendela ini.
pause >nul
