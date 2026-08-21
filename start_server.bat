@echo off
chcp 65001 >nul
title Modern PHP Blog Server

echo ========================================================
echo   极简双栏技术博客系统 (PHP 现代化重构)
echo   本地开发服务器启动中...
echo ========================================================
echo.
echo   前台地址: http://127.0.0.1:8080/
echo   后台地址: http://127.0.0.1:8080/admin (默认: admin / admin123)
echo.
echo   按 Ctrl+C 可停止服务器
echo ========================================================
echo.

php -c "%~dp0php.ini" -S 127.0.0.1:8080 -t "%~dp0public" "%~dp0public\index.php"
pause
