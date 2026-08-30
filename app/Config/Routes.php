<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */


$routes->get('/aboutus', 'Users::aboutus');
$routes->get('/listpackage', 'Users::listpackage');
$routes->get('print-tickets-departure/(:num)/(:num)',  'Admin::printTicketsDeparture/$1/$2');
$routes->get('print-tickets-return/(:num)/(:num)',  'Admin::printTicketsReturn/$1/$2');
    $routes->get('/detailpackage/(:any)', 'Users::detailpackage/$1');
    $routes->get('/', 'Users::index');

// Grup routes untuk `Users`
$routes->group('', ['filter' => 'role:users,admin'], function ($routes) {

    $routes->get('/listtranscation', 'Users::listTransaction');
    $routes->post('/generate-qrcode', 'PaymentController::generateQR');
    $routes->post('payments-manual', 'PaymentController::manualCheckout');
    $routes->post('process-manual-payment', 'PaymentController::processManualPayment');
    $routes->get('booking-success/(:any)', 'PaymentController::bookingSuccess/$1');
    $routes->get('manual-payment/detail/(:any)', 'PaymentController::viewManualPaymentDetail/$1');
        $routes->post('admin/updateStatus', 'PaymentController::updateStatus');
        $routes->post('/payments', 'PaymentController::create');
});

// Grup routes untuk `Admin`
$routes->group('', ['filter' => 'role:admin'], function ($routes) {
    $routes->get('/admin', 'Admin::index');
    $routes->get('/admin/(:num)', 'Admin::detailuser/$1');

    $routes->get('/crudlistpackage', 'Admin::crudlistpackage');
    $routes->post('/crudlistpackageadd', 'Admin::crudlistpackageadd');
    $routes->get('/crudlistpackageedit/(:num)', 'Admin::crudlistpackageedit/$1');
    $routes->post('/crudlistpackageupdate/(:num)', 'Admin::crudlistpackageupdate/$1');
    $routes->get('/crudlistpackagedelete/(:num)', 'Admin::crudlistpackagedelete/$1');

    $routes->get('/crudhome', 'Admin::crudhome');
    $routes->get('/crudhomeedit/(:num)', 'Admin::crudhomeedit/$1');
    $routes->post('/crudhomeupdate/(:num)', 'Admin::crudhomeupdate/$1');

    $routes->get('/crudaboutus', 'Admin::crudaboutus');
    $routes->get('/crudaboutusedit/(:num)', 'Admin::crudaboutusedit/$1');
    $routes->post('/crudaboutusupdate/(:num)', 'Admin::crudaboutusupdate/$1');

    $routes->get('/crudfooter', 'Admin::crudfooter');
    $routes->get('/crudfooteredit/(:num)', 'Admin::crudfooteredit/$1');
    $routes->post('/crudfooterupdate/(:num)', 'Admin::crudfooterupdate/$1');


    $routes->get('/crudboat', 'Admin::crudboat');

    $routes->get('/crudschedule', 'Admin::crudschedule');
    $routes->get('/crudscheduleedit/(:num)', 'Admin::crudscheduleedit/$1');
    $routes->post('/crudscheduleupdate/(:num)', 'Admin::crudscheduleupdate/$1');
    $routes->get('/crudscheduledelete/(:num)', 'Admin::crudscheduledelete/$1');

    $routes->get('/reservation-data', 'Admin::reservasidata');
    $routes->get('/choose-seat', 'Admin::chooseSeat');
    $routes->get('/getBookedSeats/(:num)', 'Admin::getBookedSeats/$1');
    $routes->get('/getAvailableSeats/(:num)', 'Admin::getAvailableSeats/$1');
    $routes->get('/getSeatsByBoat/(:any)', 'Admin::getSeatsByBoat/$1');
    $routes->get('/detail-departure/(:num)', 'Admin::detailDeparture/$1');
    $routes->get('/detail-return/(:num)', 'Admin::detailReturn/$1');
    $routes->post('/createBoat', 'Admin::createBoat');
    $routes->get('get-boat-capacity/(:num)', 'Admin::getBoatCapacity/$1');
    $routes->post('/createSchedule', 'Admin::createSchedule');
    $routes->post('/delete-schedule', 'Admin::deleteSchedule');
    $routes->post('update-schedule', 'Admin::updateSchedule');
    $routes->get('/get-schedule/(:num)', 'Admin::getSchedule/$1');

    $routes->post('updateBoat', 'Admin::updateBoat');
    $routes->post('deleteBoat', 'Admin::deleteBoat');
    $routes->get('getBoat/(:num)', 'Admin::getBoat/$1');


    // $routes->get('/Admin/index', 'Admin::index');
});


$routes->get('/admin/ticket/(:num)', 'Admin::getTicketData/$1');
$routes->options('/admin/ticket/(:num)', 'Admin::getTicketData/$1');
$routes->get('/admin/boarding-pass-pdf/(:num)', 'Admin::printBoardingPassPdf/$1');
$routes->get('/api/admin/boat-image', 'Admin::getBoatImageApi');
$routes->options('/api/admin/boat-image', 'Admin::getBoatImageApi');
$routes->post('/admin/checkin', 'Admin::checkin');
$routes->options('/admin/checkin', 'Admin::checkin');

$routes->get('/', 'Users::indexnonlogin');

// Webhook (must be BEFORE filtered routes)
$routes->post('/payments/webhook/xendit', 'PaymentController::webhook');

// ─── Admin API Routes untuk Seat Booking (NO AUTH for MVP) ─────────────
$routes->options('/insert-bookedseats', 'Admin::insertBookedSeats');
$routes->post('/insert-bookedseats', 'Admin::insertBookedSeats');

// Routes untuk Payment dan API lainnya
$routes->get('/api/routes', 'Route::getRoutes');

// ─── API Routes untuk React Frontend ─────────────────────────

// Auth (token-based, untuk React) - MUST be before generic /api routes
$routes->options('/api/auth/(:any)', 'AuthApiController::options');
$routes->post('/api/auth/login',    'AuthApiController::login');
$routes->post('/api/auth/register', 'AuthApiController::register');
$routes->post('/api/auth/logout',   'AuthApiController::logout');
$routes->get('/api/auth/me',        'AuthApiController::me');

// Generic API OPTIONS handler (fallback)
$routes->options('/api/(:any)', 'ApiController::options');

// Public API endpoints
$routes->get('/api/home', 'ApiController::home');
$routes->get('/api/gallery', 'ApiController::gallery');
$routes->get('/api/packages', 'ApiController::packages');
$routes->get('/api/packages/(:num)', 'ApiController::packageDetail/$1');
$routes->get('/api/schedules/departures', 'ApiController::departures');
$routes->get('/api/schedules/returns', 'ApiController::returns');
$routes->get('/api/footer', 'ApiController::footer');
$routes->get('/api/aboutus', 'ApiController::aboutus');

// Authenticated API endpoints
$routes->get('/api/transactions', 'ApiController::transactions');
$routes->post('/api/payments', 'ApiController::createPayment');
$routes->post('/api/payments/manual', 'ApiController::createManualPayment');
$routes->post('/api/payments/manual/(:num)/proof', 'ApiController::uploadTransferProof/$1');

// Admin API endpoints (protected, requires token + admin role)
$routes->get('/api/admin/debug-user', 'ApiController::adminDebugUser');
$routes->get('/api/admin/bookings/settled', 'ApiController::adminSettledBookings');
$routes->get('/api/admin/boat-seats', 'ApiController::adminBoatSeats');
$routes->get('/api/admin/booked-seats', 'ApiController::adminBookedSeats');
$routes->get('/api/admin/bookings/(:num)', 'ApiController::adminBookingDetail/$1');
$routes->get('/api/admin/manifest', 'ApiController::adminManifest');
$routes->get('/api/admin/manual-verifications', 'ApiController::adminManualVerifications');
$routes->post('/api/admin/manual-verifications/(:num)/approve', 'ApiController::adminApproveManualPayment/$1');
$routes->post('/api/admin/manual-verifications/(:num)/reject', 'ApiController::adminRejectManualPayment/$1');
$routes->get('/api/admin/ops-overview', 'ApiController::adminOpsOverview');

// ─── Manifest Upload API ─────────────────────────────────────────────────────
$routes->options('/api/admin/manifest/(:any)', 'ManifestUploadController::preflight');
$routes->post('/api/admin/manifest/upload',                          'ManifestUploadController::upload');
$routes->get('/api/admin/manifest/uploads',                          'ManifestUploadController::listUploads');
$routes->get('/api/admin/manifest/uploads/(:num)',                   'ManifestUploadController::getUpload/$1');
$routes->post('/api/admin/manifest/uploads/(:num)/confirm',          'ManifestUploadController::confirmUpload/$1');
$routes->post('/api/admin/manifest/uploads/(:num)/force-assign',     'ManifestUploadController::forceAssignSeats/$1');
$routes->delete('/api/admin/manifest/uploads/(:num)',                'ManifestUploadController::deleteUpload/$1');
$routes->get('/api/admin/manifest/delete-upload/(:num)',             'ManifestUploadController::deleteUpload/$1');
$routes->get('/api/admin/manifest/tickets/(:num)',                   'ManifestUploadController::getTickets/$1');
$routes->get('/api/admin/manifest/baggage/(:num)',                   'ManifestUploadController::listBaggage/$1');
$routes->post('/api/admin/manifest/baggage',                         'ManifestUploadController::addBaggage');
$routes->put('/api/admin/manifest/baggage/(:num)',                   'ManifestUploadController::updateBaggage/$1');
$routes->delete('/api/admin/manifest/baggage/(:num)',                'ManifestUploadController::deleteBaggage/$1');
$routes->post('/api/admin/manifest/baggage/(:num)/mark-printed',     'ManifestUploadController::markBaggagePrinted/$1');
$routes->get('/api/admin/manifest/baggage-tag-pdf/(:num)',           'ManifestUploadController::baggageTagPdf/$1');
$routes->get('/api/admin/manifest/boarding-pass/(:num)',             'ManifestUploadController::boardingPass/$1');
$routes->post('/api/admin/manifest/boarding-pass-self-service',      'ManifestUploadController::boardingPassSelfService');
$routes->get('/api/admin/manifest/group-qr-codes/(:num)',            'ManifestUploadController::getGroupQrCodes/$1');
$routes->post('/api/admin/manifest/send-group-qr-emails',            'ManifestUploadController::sendGroupQrEmails');
$routes->get('/api/admin/manifest/boats',                            'ManifestUploadController::listBoats');
$routes->put('/api/admin/manifest/boats/(:num)/crew',                'ManifestUploadController::updateBoatCrew/$1');
$routes->put('/api/admin/manifest/tickets/(:num)',                   'ManifestUploadController::updateTicket/$1');
$routes->post('/api/admin/manifest/tickets/(:num)/toggle-cancel',    'ManifestUploadController::toggleCancel/$1');
$routes->get('/api/admin/manifest/available-seats/(:num)',           'ManifestUploadController::getAvailableSeats/$1');
$routes->get('/api/admin/manifest/group-by-code/(:any)',             'ManifestUploadController::getGroupByCode/$1');
$routes->post('/api/admin/manifest/checkin-bulk',                    'ManifestUploadController::checkinBulk');
$routes->get('/api/admin/manifest/final/(:num)',                     'ManifestUploadController::getManifestFinal/$1');
$routes->get('/api/admin/manifest/export-excel/(:num)',              'ManifestUploadController::exportExcel/$1');
$routes->get('/api/admin/manifest/crew-checkins/(:num)',             'ManifestUploadController::getCrewCheckins/$1');
$routes->post('/api/admin/manifest/tickets/switch-seat',             'ManifestUploadController::switchSeat');
// ─────────────────────────────────────────────────────────────────────────────

// ─── Crew Management ─────────────────────────────────────────────────────────
$routes->options('/api/admin/crew/(:any)', 'CrewController::preflight');
$routes->get('/api/admin/crew',                                   'CrewController::index');
$routes->post('/api/admin/crew',                                  'CrewController::create');
$routes->get('/api/admin/crew/assignments',                       'CrewController::listAssignments');
$routes->post('/api/admin/crew/assignments',                      'CrewController::createAssignment');
$routes->delete('/api/admin/crew/assignments/(:num)',             'CrewController::deleteAssignment/$1');
$routes->get('/api/admin/crew/checkin-by-qr/(:any)',              'CrewController::checkinByQr/$1');
$routes->post('/api/admin/crew/checkin',                          'CrewController::checkin');
$routes->get('/api/admin/crew/(:num)',                            'CrewController::show/$1');
$routes->put('/api/admin/crew/(:num)',                            'CrewController::update/$1');
$routes->delete('/api/admin/crew/(:num)',                         'CrewController::delete/$1');
$routes->get('/api/admin/crew/(:num)/qr-pdf',                    'CrewController::qrPdf/$1');
// ─────────────────────────────────────────────────────────────────────────────

// Master data CRUD (boats, packages, schedules)
$routes->get('/api/admin/boats', 'AdminCrudController::boats');
$routes->post('/api/admin/boats', 'AdminCrudController::createBoat');
$routes->post('/api/admin/boats/(:num)', 'AdminCrudController::updateBoat/$1');
$routes->delete('/api/admin/boats/(:num)', 'AdminCrudController::deleteBoat/$1');

$routes->get('/api/admin/packages', 'AdminCrudController::packagesAdmin');
$routes->post('/api/admin/packages', 'AdminCrudController::createPackage');
$routes->post('/api/admin/packages/(:num)', 'AdminCrudController::updatePackage/$1');
$routes->delete('/api/admin/packages/(:num)', 'AdminCrudController::deletePackage/$1');

$routes->get('/api/admin/schedules', 'AdminCrudController::schedules');
$routes->post('/api/admin/schedules', 'AdminCrudController::createSchedule');
$routes->put('/api/admin/schedules/(:num)', 'AdminCrudController::updateSchedule/$1');
$routes->delete('/api/admin/schedules/(:num)', 'AdminCrudController::deleteSchedule/$1');
$routes->get('/api/admin/dashboard-stats', 'ApiController::adminDashboardStats');
$routes->get('/api/home', 'ApiController::home');
$routes->get('/api/packages', 'ApiController::packages');
$routes->get('/api/packages/(:num)', 'ApiController::packageDetail/$1');
$routes->get('/api/schedules/departures', 'ApiController::departures');
$routes->get('/api/schedules/returns', 'ApiController::returns');
$routes->get('/api/gallery', 'ApiController::gallery');
$routes->get('/api/footer', 'ApiController::footer');
$routes->get('/api/aboutus', 'ApiController::aboutus');
$routes->get('/api/transactions', 'ApiController::transactions');
$routes->post('/api/payments', 'ApiController::createPayment');
$routes->post('/api/payments/manual', 'ApiController::createManualPayment');
// ─────────────────────────────────────────────────────────────
$routes->get('/payment-success', 'PaymentController::success');
$routes->get('/payment-failure', 'PaymentController::failure');
$routes->post('/validate-qrcode', 'PaymentController::validateQRCode');
$routes->get('/scan-qrcode', 'PaymentController::scanQRCode');
$routes->post('qrcode_scanner/attendance', 'PaymentController::validateQRCode');
$routes->get('/indextwo', 'Users::indextwo');
$routes->post('customer/registerCustomer', 'CustomerController::registerCustomer');

$routes->post('register', 'CustomController::attemptRegister');
$routes->get('explore', 'Users::explore');
