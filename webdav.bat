@echo off
title webdav
cls
echo webdav���������ļ���С����50m���Ϊ4G..
echo.
net stop webclient

echo ������֤ͬʱ֧��http��https
reg add "HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters" /v "BasicAuthLevel" /t REG_DWORD /d "2" /f

echo ������С����
reg add "HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters" /v "FileSizeLimitInBytes" /t REG_DWORD /d "4294967295" /f

net start webclient

echo webdav apply success.
pause