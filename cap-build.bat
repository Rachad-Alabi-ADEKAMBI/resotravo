@echo off
REM ═══════════════════════════════════════════════════════════
REM  cap-build.bat — Builder et ouvrir ResoTravo dans Android Studio
REM
REM  Usage :
REM    cap-build.bat         → sync + ouvre Android Studio
REM    cap-build.bat run     → sync + lance sur émulateur/device
REM    cap-build.bat ip      → sync avec IP LAN (à éditer)
REM ═══════════════════════════════════════════════════════════

setlocal

set ACTION=%1

echo.
echo  ██████╗ ███████╗███████╗ ██████╗ ████████╗██████╗  █████╗ ██╗   ██╗ ██████╗
echo  ██╔══██╗██╔════╝██╔════╝██╔═══██╗╚══██╔══╝██╔══██╗██╔══██╗██║   ██║██╔═══██╗
echo  ██████╔╝█████╗  ███████╗██║   ██║   ██║   ██████╔╝███████║██║   ██║██║   ██║
echo  ██╔══██╗██╔══╝  ╚════██║██║   ██║   ██║   ██╔══██╗██╔══██║╚██╗ ██╔╝██║   ██║
echo  ██║  ██║███████╗███████║╚██████╔╝   ██║   ██║  ██║██║  ██║ ╚████╔╝ ╚██████╔╝
echo  ╚═╝  ╚═╝╚══════╝╚══════╝ ╚═════╝    ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝  ╚═══╝   ╚═════╝
echo.
echo  Mobile Build Tool
echo  ─────────────────────────────────────────────────────────

echo.
echo [1/2] Synchronisation Capacitor...
call npx cap sync android
if errorlevel 1 (
    echo [ERREUR] cap sync a echoue.
    pause
    exit /b 1
)

echo.
if "%ACTION%"=="run" (
    echo [2/2] Lancement sur emulateur/device...
    call npx cap run android
) else (
    echo [2/2] Ouverture Android Studio...
    call npx cap open android
)

echo.
echo [OK] Termine !
echo.
echo  RAPPEL : Pour tester sur un appareil physique,
echo  editez capacitor.config.ts et remplace 10.0.2.2
echo  par l'IP LAN de ta machine (ex: 192.168.1.42)
echo  puis relance ce script.
echo.
pause
