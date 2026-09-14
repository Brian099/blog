@echo off
chcp 65001 >nul
title Z-Blog Apache 本地测试服务器 (Port 8088)

echo ======================================================
echo   正在启动 Apache 2.4 本地测试环境...
echo   访问地址: http://localhost:8088
echo   安装向导: http://localhost:8088/install
echo   按 Ctrl + C 可终止服务器运行
echo ======================================================

set "HTTPD_EXE=C:\Users\Brian\AppData\Local\Microsoft\WinGet\Packages\ApacheLounge.httpd_Microsoft.Winget.Source_8wekyb3d8bbwe\Apache24\bin\httpd.exe"
set "CONF_FILE=%~dp0apache_dev.conf"

"%HTTPD_EXE%" -f "%CONF_FILE%" -DFOREGROUND
pause
