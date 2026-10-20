@echo off
set "SOURCE=%~dp0."
set "PAQUET=%~dp0toun_en_ligne"
set "ARCHIVE=%~dp0toun_en_ligne.zip"

if exist "%PAQUET%" rmdir /s /q "%PAQUET%"
if exist "%ARCHIVE%" del /q "%ARCHIVE%"

robocopy "%SOURCE%" "%PAQUET%" /E /NFL /NDL /NJH /NJS ^
    /XD toun_mobile "vid?o" Cours schemas_memoire "Claude outputs" "Nouveau dossier*" "sauvegarde*" toun_en_ligne ^
    /XF *.psd *.aep *.docx *.sql *.bat *.zip README.txt _toun_diag.php diag_*.php "~$*" "*-1.php" "? propos.php"

tar -a -c -f "%ARCHIVE%" -C "%PAQUET%" .
rmdir /s /q "%PAQUET%"

echo.
echo Le paquet toun_en_ligne.zip est pret.
pause
