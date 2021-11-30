@echo off
title webdav
cls
echo webdav适配允许文件大小限制50m变更为4G..
echo.
net stop webclient

echo 调整认证同时支付http和https
reg add "HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters" /v "BasicAuthLevel" /t REG_DWORD /d "2" /f

echo 调整大小限制
reg add "HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters" /v "FileSizeLimitInBytes" /t REG_DWORD /d "4294967295" /f

net start webclient

echo webdav apply success.
pause