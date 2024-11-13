<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index'); // $routes->get('/', 'Home::index', ['filter' => 'login']);
// $routes->get('/home', 'Home::index', ['filter' => 'login']);
$routes->get('/indextwo', 'Home::indextwo');
$routes->post('/payments', 'PaymentController::create');
$routes->post('/payments/webhook/xendit', 'PaymentController::webhook');
$routes->get('/api/routes', 'Route::getRoutes');
$routes->get('/payment-success', 'PaymentController::success');
$routes->get('/payment-failure', 'PaymentController::failure');
$routes->post('/generate-qrcode', 'PaymentController::generateQR');
$routes->post('/validate-qrcode', 'PaymentController::validateQRCode');
$routes->get('/scan-qrcode', 'PaymentController::scanQRCode');
$routes->post('qrcode_scanner/attendance', 'PaymentController::validateQRCode');
