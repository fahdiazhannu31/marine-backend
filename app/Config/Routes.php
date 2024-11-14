<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Routes untuk halaman yang hanya bisa diakses oleh pengguna yang belum login
$routes->group('', ['filter' => 'noauth'], function($routes) {
    $routes->get('/', 'Users::indexnonlogin');
    $routes->get('/aboutus.', 'Users::aboutusnonlogin');
});

// Route untuk halaman utama
$routes->group('', ['filter' => 'login'], function($routes) {
    $routes->get('/home', 'Users::index'); 
    $routes->get('/aboutus', 'Users::aboutus');
});

// Grup routes untuk `Users`
$routes->group('', ['filter' => 'role:users'], function($routes) {
    $routes->get('/listpackage', 'Users::listpackage');
    $routes->get('/detailpackage', 'Users::detailpackage');
});

// Grup routes untuk `Admin`
$routes->group('', ['filter' => 'role:admin'], function($routes) {
    $routes->get('/admin', 'Admin::index');
    $routes->get('/admin/(:num)', 'Admin::detailuser/$1');
    $routes->get('/crudlistpackage', 'Admin::crudlistpackage');
    $routes->get('/crudhome', 'Admin::crudhome');
    $routes->get('/crudaboutus', 'Admin::crudaboutus');
    $routes->get('/crudfooter', 'Admin::crudfooter');
    // $routes->get('/Admin/index', 'Admin::index');
});

// Routes untuk Payment dan API lainnya
$routes->post('/payments', 'PaymentController::create');
$routes->post('/payments/webhook/xendit', 'PaymentController::webhook');
$routes->get('/api/routes', 'Route::getRoutes');
$routes->get('/payment-success', 'PaymentController::success');
$routes->get('/payment-failure', 'PaymentController::failure');
$routes->post('/generate-qrcode', 'PaymentController::generateQR');
$routes->post('/validate-qrcode', 'PaymentController::validateQRCode');
$routes->get('/scan-qrcode', 'PaymentController::scanQRCode');
$routes->post('qrcode_scanner/attendance', 'PaymentController::validateQRCode');
