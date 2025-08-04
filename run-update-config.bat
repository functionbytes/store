@echo off
echo Running checkout configuration update...
echo.
php artisan ecommerce:update-checkout-config
echo.
echo Done! Check https://mercosan.test/orden/ to verify the changes.
pause