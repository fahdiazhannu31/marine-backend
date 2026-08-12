<?php

namespace App\Controllers;

use App\Models\HomeModel;
use App\Models\HomeGalleryModel;
use App\Models\FooterModel;
use App\Models\AboutusModel;
use App\Models\PackageModel;

class ApiController extends BaseController
{
    private const CORS_ORIGIN = 'http://localhost:3000';

    private const ALLOWED_ORIGINS = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:5174',
    ];

    private function getAllowedOrigin(): string
    {
        $origin = $this->request->getHeaderLine('Origin');
        return in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : self::ALLOWED_ORIGINS[0];
    }

    /**
     * Clean up a passenger_names payload: only keep non-empty, trimmed strings.
     *
     * @param mixed $raw
     * @return string[]
     */
    private function sanitizePassengerNames($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $names = [];
        foreach ($raw as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function jsonResponse(array $data, int $status = 200)
    {
        $origin = $this->getAllowedOrigin();
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setJSON($data);
    }

    public function options()
    {
        $origin = $this->getAllowedOrigin();
        return $this->response
            ->setStatusCode(200)
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setBody('');
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/home
    // Response: { hero, footer, about, gallery }
    // ─────────────────────────────────────────────────────────────
    public function home()
    {
        return $this->jsonResponse([
            'hero'    => (new HomeModel())->findAll(),
            'footer'  => (new FooterModel())->findAll(),
            'about'   => (new AboutusModel())->first(),
            'gallery' => (new HomeGalleryModel())->getGrouped(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/gallery
    // Response: { left: [], center: [], right: [] }
    // ─────────────────────────────────────────────────────────────
    public function gallery()
    {
        return $this->jsonResponse((new HomeGalleryModel())->getGrouped());
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/packages
    // Response: [ { id, title, description, photo1, photo2, photo3,
    //               price_per_pax, price_per_pax_weekend, pax_count, status } ]
    // ─────────────────────────────────────────────────────────────
    public function packages()
    {
        $packages = (new PackageModel())
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->jsonResponse($packages);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/packages/:id
    // Response: { id, title, description, photo1, photo2, photo3,
    //             price_per_pax, price_per_pax_weekend, pax_count, status }
    // ─────────────────────────────────────────────────────────────
    public function packageDetail($id)
    {
        $package = (new PackageModel())->find($id);

        if (!$package) {
            return $this->jsonResponse(['error' => 'Package not found'], 404);
        }

        return $this->jsonResponse($package);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/schedules/departures
    // Response: [ { id, boat_name, date, total_pax, type } ]
    // ─────────────────────────────────────────────────────────────
    public function departures()
    {
        $db = \Config\Database::connect();

        $rows = $db->table('schedule')
            ->select('schedule.id, schedule.date, schedule.total_pax, schedule.type, boat.boat_name')
            ->join('boat', 'boat.id = schedule.boat_id')
            ->where('schedule.type', 'DEPARTURE')
            ->orderBy('schedule.date', 'ASC')
            ->get()
            ->getResultArray();

        return $this->jsonResponse($rows);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/schedules/returns
    // ─────────────────────────────────────────────────────────────
    public function returns()
    {
        $db = \Config\Database::connect();

        $rows = $db->table('schedule')
            ->select('schedule.id, schedule.date, schedule.total_pax, schedule.type, boat.boat_name')
            ->join('boat', 'boat.id = schedule.boat_id')
            ->where('schedule.type', 'RETURN')
            ->orderBy('schedule.date', 'ASC')
            ->get()
            ->getResultArray();

        return $this->jsonResponse($rows);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/footer
    // Response: [ { id, day_op, phone, copyright } ]
    // ─────────────────────────────────────────────────────────────
    public function footer()
    {
        return $this->jsonResponse((new FooterModel())->findAll());
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/aboutus
    // Response: [ { id, jb_photo, jb_title, jb_description,
    //               as_title, as_description, as_photo, as_name, as_position } ]
    // ─────────────────────────────────────────────────────────────
    public function aboutus()
    {
        return $this->jsonResponse((new AboutusModel())->findAll());
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/transactions
    // Requires: Authorization: Bearer <token>
    // ─────────────────────────────────────────────────────────────
    public function transactions()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->jsonResponse(['error' => 'Unauthenticated.'], 401);
        }
        $rawToken   = substr($authHeader, 7);
        $tokenModel = new \App\Models\ApiTokenModel();
        $tokenRow   = $tokenModel->findValid($rawToken);
        if (!$tokenRow) {
            return $this->jsonResponse(['error' => 'Invalid or expired token.'], 401);
        }

        $paymentModel = new \App\Models\Payment();
        $payments = $paymentModel
            ->where('user_id', (int) $tokenRow['user_id'])
            ->orderBy('id', 'DESC')
            ->findAll();

        return $this->jsonResponse($payments ?? []);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/payments (Xendit invoice)
    //   package_id, package_name, schedule_departure_id,
    //   schedule_return_id, jml_pax, amount, trip_type
    // }
    // Requires: Authorization: Bearer <token>
    // Response: { checkout_link, external_id }
    // ─────────────────────────────────────────────────────────────
    public function createPayment()
    {
        $frontendUrl = env('frontend.baseURL', 'http://localhost:5173');
        // ── 1. Authenticate via Bearer token ──
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->jsonResponse(['error' => 'Unauthenticated.'], 401);
        }
        $rawToken   = substr($authHeader, 7);
        $tokenModel = new \App\Models\ApiTokenModel();
        $tokenRow   = $tokenModel->findValid($rawToken);
        if (!$tokenRow) {
            return $this->jsonResponse(['error' => 'Invalid or expired token.'], 401);
        }

        $userModel = new \Myth\Auth\Models\UserModel();
        $user      = $userModel->find((int) $tokenRow['user_id']);
        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found.'], 401);
        }

        // ── 2. Parse body ──
        $body = $this->request->getJSON(true) ?? [];

        $packageId           = (int) ($body['package_id']            ?? 0);
        $packageName         = trim($body['package_name']             ?? '');
        $scheduleDepartureId = (int) ($body['schedule_departure_id'] ?? 0);
        $scheduleReturnId    = (int) ($body['schedule_return_id']    ?? 0);
        $jmlPax              = (int) ($body['jml_pax']               ?? 0);
        $amount              = (int) ($body['amount']                ?? 0);
        $tripType            = trim($body['trip_type']               ?? 'departure_only');

        // Group booking: name of the "group" (defaults to the booker's own
        // name) plus one name per pax/seat.
        $groupName = trim($body['group_name'] ?? '');
        if ($groupName === '') {
            $groupName = $user->fullname ?? $user->username;
        }
        $passengerNames = $this->sanitizePassengerNames($body['passenger_names'] ?? []);

        if (!$packageId || !$packageName || !$scheduleDepartureId || !$jmlPax || !$amount) {
            return $this->jsonResponse(['error' => 'Missing required fields.'], 422);
        }

        if (!empty($passengerNames) && count($passengerNames) !== $jmlPax) {
            return $this->jsonResponse(['error' => 'Jumlah nama penumpang harus sama dengan jumlah pax.'], 422);
        }

        // ── 3. Create Xendit invoice ──
        try {
            \Xendit\Configuration::setXenditKey("xnd_development_UKZzhNTfAY0HVZTk4994o43gFcRi0axlEEGVGzuHYeWkAsdLwXNJRn91wa4g");
            $apiInstance = new \Xendit\Invoice\InvoiceApi();

            $externalId = \Ramsey\Uuid\Uuid::uuid4()->toString();

            $nameParts  = explode(' ', $user->fullname ?? $user->username, 2);
            $givenNames = $nameParts[0];
            $surname    = $nameParts[1] ?? '';

            $params = [
                'external_id'   => $externalId,
                'payer_email'   => $user->email,
                'description'   => 'Paket ' . $packageName . ' — ' . $jmlPax . ' pax',
                'amount'        => $amount,
                'invoice_duration' => 2880,
                'currency'      => 'IDR',
                'customer' => [
                    'given_names'   => $givenNames,
                    'surname'       => $surname,
                    'email'         => $user->email,
                    'mobile_number' => $user->phone ?? '',
                ],
                'customer_notification_preference' => [
                    'invoice_created'  => ['email', 'whatsapp'],
                    'invoice_reminder' => ['email', 'whatsapp'],
                    'invoice_paid'     => ['email', 'whatsapp'],
                ],
                'success_redirect_url' => $frontendUrl . '/payment-success',
                'failure_redirect_url' => $frontendUrl . '/payment-failure',
            ];

            $invoice = $apiInstance->createInvoice($params);

            // ── 4. Save to DB ──
            $paymentModel = new \App\Models\Payment();
            $paymentModel->insert([
                'user_id'                => (int) $user->id,
                'group_name'            => $groupName,
                'jml_pax'               => $jmlPax,
                'package_id'            => $packageId,
                'package_name'          => $packageName,
                'schedule_departure_id' => $scheduleDepartureId,
                'schedule_return_id'    => $scheduleReturnId ?: null,
                'status'                => 'PENDING',
                'payer_email'           => $user->email,
                'external_id'           => $externalId,
                'checkout_link'         => $invoice['invoice_url'],
            ]);
            $paymentId = $paymentModel->getInsertID();

            if (!empty($passengerNames)) {
                (new \App\Models\BookingPassengerModel())->createForBooking($paymentId, $passengerNames);
            }

            // ── 5. Deduct slots ──
            $scheduleModel = new \App\Models\ScheduleModel();

            $dep = $scheduleModel->find($scheduleDepartureId);
            if ($dep) {
                $newSlots = max(0, (int)$dep['total_pax'] - $jmlPax);
                $scheduleModel->update($scheduleDepartureId, ['total_pax' => $newSlots]);
            }

            if ($scheduleReturnId) {
                $ret = $scheduleModel->find($scheduleReturnId);
                if ($ret) {
                    $newSlots = max(0, (int)$ret['total_pax'] - $jmlPax);
                    $scheduleModel->update($scheduleReturnId, ['total_pax' => $newSlots]);
                }
            }

            return $this->jsonResponse([
                'checkout_link' => $invoice['invoice_url'],
                'external_id'   => $externalId,
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Failed to create invoice: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/payments/manual
    // Body (JSON): same as createPayment
    // Requires: Authorization: Bearer <token>
    // Response: { external_id, message }
    // ─────────────────────────────────────────────────────────────
    public function createManualPayment()
    {
        // Auth
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->jsonResponse(['error' => 'Unauthenticated.'], 401);
        }
        $rawToken   = substr($authHeader, 7);
        $tokenModel = new \App\Models\ApiTokenModel();
        $tokenRow   = $tokenModel->findValid($rawToken);
        if (!$tokenRow) {
            return $this->jsonResponse(['error' => 'Invalid or expired token.'], 401);
        }

        $userModel = new \Myth\Auth\Models\UserModel();
        $user      = $userModel->find((int) $tokenRow['user_id']);
        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found.'], 401);
        }

        $body                = $this->request->getJSON(true) ?? [];
        $packageId           = (int) ($body['package_id']            ?? 0);
        $packageName         = trim($body['package_name']             ?? '');
        $scheduleDepartureId = (int) ($body['schedule_departure_id'] ?? 0);
        $scheduleReturnId    = (int) ($body['schedule_return_id']    ?? 0);
        $jmlPax              = (int) ($body['jml_pax']               ?? 0);
        $amount              = (int) ($body['amount']                ?? 0);
        $tripType            = trim($body['trip_type']               ?? 'departure_only');

        $groupName = trim($body['group_name'] ?? '');
        if ($groupName === '') {
            $groupName = $user->fullname ?? $user->username;
        }
        $passengerNames = $this->sanitizePassengerNames($body['passenger_names'] ?? []);

        if (!$packageId || !$packageName || !$scheduleDepartureId || !$jmlPax || !$amount) {
            return $this->jsonResponse(['error' => 'Missing required fields.'], 422);
        }

        if (!empty($passengerNames) && count($passengerNames) !== $jmlPax) {
            return $this->jsonResponse(['error' => 'Jumlah nama penumpang harus sama dengan jumlah pax.'], 422);
        }

        $externalId   = 'MANUAL-' . time() . '-' . rand(1000, 9999);
        $paymentModel = new \App\Models\Payment();

        $paymentModel->insert([
            'user_id'                => (int) $user->id,
            'group_name'            => $groupName,
            'jml_pax'               => $jmlPax,
            'package_id'            => $packageId,
            'package_name'          => $packageName,
            'schedule_departure_id' => $scheduleDepartureId,
            'schedule_return_id'    => $scheduleReturnId ?: null,
            'status'                => 'ON VERIFICATION',
            'payer_email'           => $user->email,
            'external_id'           => $externalId,
            'amount'                => $amount,
            'trip_type'             => $tripType,
            'payment_method'        => 'manual',
        ]);
        $paymentId = $paymentModel->getInsertID();

        if (!empty($passengerNames)) {
            (new \App\Models\BookingPassengerModel())->createForBooking($paymentId, $passengerNames);
        }

        // Deduct slots
        $scheduleModel = new \App\Models\ScheduleModel();
        $dep = $scheduleModel->find($scheduleDepartureId);
        if ($dep) {
            $scheduleModel->update($scheduleDepartureId, ['total_pax' => max(0, (int)$dep['total_pax'] - $jmlPax)]);
        }
        if ($scheduleReturnId) {
            $ret = $scheduleModel->find($scheduleReturnId);
            if ($ret) {
                $scheduleModel->update($scheduleReturnId, ['total_pax' => max(0, (int)$ret['total_pax'] - $jmlPax)]);
            }
        }

        return $this->jsonResponse([
            'external_id' => $externalId,
            'message'     => 'Manual payment created. Please upload your transfer proof.',
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════
    // ADMIN ENDPOINTS - Yacht Seat Booking
    // ═══════════════════════════════════════════════════════════════

    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/bookings/settled
    // Fetch all settled bookings with user details
    // Requires: Authorization: Bearer <token> (admin only)
    // Response: [ { id, user_id, user_name, jml_pax, amount, status, ... } ]
    // ─────────────────────────────────────────────────────────────
    public function adminSettledBookings()
    {
        // Debug: Test auth first
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->jsonResponse(['error' => 'No Bearer token provided'], 401);
        }

        $rawToken = substr($authHeader, 7);
        
        // Direct DB query - handle both raw (legacy) and hashed tokens
        $db = \Config\Database::connect();
        
        // Try 1: Check if token is raw (legacy)
        $tokenRow = $db->table('api_tokens')
            ->where('token', $rawToken)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getFirstRow('array');
        
        // Try 2: Check if token needs hashing (new method)
        if (!$tokenRow) {
            $tokenHash = hash('sha256', $rawToken);
            $tokenRow = $db->table('api_tokens')
                ->where('token', $tokenHash)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()
                ->getFirstRow('array');
        }
        
        if (!$tokenRow) {
            return $this->jsonResponse(['error' => 'Invalid or expired token'], 401);
        }

        // Check admin status
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden - not an admin'], 403);
        }

        $bookings = $db->table('payments')
            ->select('payments.*, users.fullname as user_name, users.email as user_email')
            ->join('users', 'users.id = payments.user_id', 'left')
            ->where('payments.status', 'SETTLED')
            ->orderBy('payments.id', 'DESC')
            ->get()
            ->getResultArray();

        return $this->jsonResponse($bookings ?? []);
    }

    // NOTE: adminYachtSeats() / adminAssignSeats() / adminGenerateTicket()
    // used to live here. They operated on the `yacht_seats` table, which
    // turned out to be completely dead: the real seat-assignment flow used
    // by the React admin panel is adminBoatSeats() + adminBookedSeats() +
    // Admin::insertBookedSeats() against the `seat` / `booked_seats` tables
    // instead. Removed together with the unused `yacht_seats` table.

    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/bookings/{id}
    // Get booking detail with assigned seats
    // Requires: Authorization: Bearer <token> (admin only)
    // ─────────────────────────────────────────────────────────────
    public function adminBookingDetail($bookingId = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        if (!$bookingId) {
            return $this->jsonResponse(['error' => 'booking_id is required.'], 422);
        }

        $db = \Config\Database::connect();
        
        $booking = $db->table('payments')
            ->select('payments.*, users.fullname as user_name, users.email as user_email')
            ->join('users', 'users.id = payments.user_id', 'left')
            ->where('payments.id', $bookingId)
            ->get()
            ->getFirstRow('array');

        if (!$booking) {
            return $this->jsonResponse(['error' => 'Booking not found.'], 404);
        }

        if (empty($booking['group_name'])) {
            $booking['group_name'] = $booking['user_name'];
        }

        // Get seats actually assigned so far (real tables: seat + booked_seats)
        $seats = $db->table('booked_seats bs')
            ->select('s.id, s.seat_number, bs.seat_id')
            ->join('seat s', 's.id = bs.seat_id', 'left')
            ->where('bs.payment_id', $bookingId)
            ->get()
            ->getResultArray();
        $booking['assigned_seats'] = $seats;

        // Passenger names entered at booking time (group booking feature),
        // in the order they'll be paired with seats as they get assigned.
        $booking['passenger_names'] = array_column(
            (new \App\Models\BookingPassengerModel())->getNamesForBooking((int) $bookingId),
            'name'
        );

        return $this->jsonResponse($booking);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/dashboard-stats?range=7|30|90 (days, default 14)
    // Aggregated stats for the admin dashboard:
    //   summary, status_breakdown, revenue_trend,
    //   top_packages, recent_transactions
    // Requires: Authorization: Bearer <token> (admin only)
    // ─────────────────────────────────────────────────────────────
    public function adminDashboardStats()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden - not an admin'], 403);
        }

        $db = \Config\Database::connect();

        $rangeDays = (int) ($this->request->getVar('range') ?? 14);
        if (!in_array($rangeDays, [7, 14, 30, 90], true)) {
            $rangeDays = 14;
        }
        $startDate = date('Y-m-d', strtotime("-{$rangeDays} days"));

        // Statuses that count as actual revenue (paid/settled)
        $paidStatuses = ['PAID', 'SETTLED'];

        // ── Summary ──
        $totalTransactions = (int) $db->table('payments')->countAllResults(false);

        $revenueRow = $db->table('payments')
            ->selectSum('amount', 'total_revenue')
            ->whereIn('status', $paidStatuses)
            ->get()
            ->getRowArray();
        $totalRevenue = (float) ($revenueRow['total_revenue'] ?? 0);

        $paxRow = $db->table('payments')
            ->selectSum('jml_pax', 'total_pax')
            ->whereIn('status', $paidStatuses)
            ->get()
            ->getRowArray();
        $totalPassengers = (int) ($paxRow['total_pax'] ?? 0);

        $checkedInCount = (int) $db->table('payments')
            ->where('attendance IS NOT NULL', null, false)
            ->countAllResults(false);

        // ── Status breakdown ──
        $statusBreakdown = $db->table('payments')
            ->select('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $statusCounts = [];
        foreach ($statusBreakdown as $row) {
            $statusCounts[$row['status'] ?? 'UNKNOWN'] = (int) $row['count'];
        }

        // ── Revenue trend (per day, last N days) ──
        $trendRaw = $db->table('payments')
            ->select("DATE(created_at) as date, COALESCE(SUM(amount), 0) as revenue, COUNT(*) as transactions")
            ->whereIn('status', $paidStatuses)
            ->where('created_at >=', $startDate)
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Fill in missing days with zero so the chart has a continuous axis
        $trendByDate = [];
        foreach ($trendRaw as $row) {
            $trendByDate[$row['date']] = [
                'date'         => $row['date'],
                'revenue'      => (float) $row['revenue'],
                'transactions' => (int) $row['transactions'],
            ];
        }
        $revenueTrend = [];
        for ($i = $rangeDays - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $revenueTrend[] = $trendByDate[$d] ?? ['date' => $d, 'revenue' => 0, 'transactions' => 0];
        }

        // ── Top packages ──
        $topPackages = $db->table('payments')
            ->select('package_name, COUNT(*) as bookings, COALESCE(SUM(amount), 0) as revenue')
            ->whereIn('status', $paidStatuses)
            ->where('package_name IS NOT NULL', null, false)
            ->groupBy('package_name')
            ->orderBy('revenue', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // ── Recent transactions ──
        $recentTransactions = $db->table('payments p')
            ->select('p.id, u.fullname as user_name, p.package_name, p.amount, p.status, p.jml_pax, p.attendance, p.created_at')
            ->join('users u', 'u.id = p.user_id', 'left')
            ->orderBy('p.id', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        return $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_revenue'      => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                    'total_passengers'   => $totalPassengers,
                    'total_checked_in'   => $checkedInCount,
                    'pending_count'      => $statusCounts['PENDING'] ?? 0,
                    'settled_count'      => $statusCounts['SETTLED'] ?? 0,
                    'paid_count'         => $statusCounts['PAID'] ?? 0,
                    'verification_count' => $statusCounts['ON VERIFICATION'] ?? 0,
                ],
                'status_breakdown'    => $statusBreakdown,
                'revenue_trend'       => $revenueTrend,
                'top_packages'        => $topPackages,
                'recent_transactions' => $recentTransactions,
                'range_days'          => $rangeDays,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/booked-seats?schedule_id=X&payment_id=Y
    // Fetch already booked seats for a specific schedule and payment
    // Optional auth: works with or without token (for admin use)
    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/boat-seats?boat_id=X (or schedule_id=X)
    // Fetch seat layout for a boat (MVC system - uses seat table)
    // Optional auth: works without token (for admin use)
    // Response: [ { id, boat_id, seat_number, status } ]
    // ─────────────────────────────────────────────────────────────
    public function adminBoatSeats()
    {
        try {
            $boatId = (int)$this->request->getVar('boat_id');
            $scheduleId = (int)$this->request->getVar('schedule_id');

            if (!$boatId && !$scheduleId) {
                return $this->jsonResponse(['error' => 'boat_id or schedule_id is required'], 422);
            }

            $db = \Config\Database::connect();

            // If schedule_id provided, resolve boat_id
            // Try table: schedule → boat_id first, then departure_schedule → package → boat
            if ($scheduleId && !$boatId) {
                // Try `schedule` table (new system)
                $schedule = $db->table('schedule')
                    ->select('boat_id')
                    ->where('id', $scheduleId)
                    ->get()
                    ->getFirstRow('array');

                if ($schedule && !empty($schedule['boat_id'])) {
                    $boatId = $schedule['boat_id'];
                } else {
                    // Try `departure_schedule` table → packages → boat
                    $depSched = $db->table('departure_schedule')
                        ->select('departure_schedule.package_id, packages.boat_id')
                        ->join('packages', 'packages.id = departure_schedule.package_id', 'left')
                        ->where('departure_schedule.id', $scheduleId)
                        ->get()
                        ->getFirstRow('array');

                    if ($depSched && !empty($depSched['boat_id'])) {
                        $boatId = $depSched['boat_id'];
                    } else {
                        // Try `return_schedule` table → packages → boat
                        $retSched = $db->table('return_schedule')
                            ->select('return_schedule.package_id, packages.boat_id')
                            ->join('packages', 'packages.id = return_schedule.package_id', 'left')
                            ->where('return_schedule.id', $scheduleId)
                            ->get()
                            ->getFirstRow('array');

                        if ($retSched && !empty($retSched['boat_id'])) {
                            $boatId = $retSched['boat_id'];
                        }
                    }
                }

                if (!$boatId) {
                    return $this->jsonResponse(['error' => 'Could not resolve boat from schedule_id ' . $scheduleId], 404);
                }
            }

            // Fetch boat seat layout from the seat table (MVC system)
            $sql = "SELECT id, boat_id, seat_number, status 
                    FROM seat 
                    WHERE boat_id = ?
                    ORDER BY seat_number ASC";
            
            $seats = $db->query($sql, [$boatId])->getResultArray();

            return $this->jsonResponse($seats ?? []);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/booked-seats?schedule_id=X&payment_id=Y
    // Fetch already booked seats for a payment (joins with seat table)
    // Optional auth: works without token (for admin use)
    // Response: [ { seat_id, payment_id, seat_number, status } ]
    // ─────────────────────────────────────────────────────────────
    public function adminBookedSeats()
    {
        try {
            $scheduleId = (int)$this->request->getVar('schedule_id');
            $paymentId = (int)$this->request->getVar('payment_id');

            if (!$paymentId) {
                return $this->jsonResponse(['error' => 'payment_id is required'], 422);
            }

            $db = \Config\Database::connect();

            // Query booked seats - joins with seat table to get seat info
            $sql = "SELECT DISTINCT bs.id, bs.seat_id, bs.payment_id, s.seat_number, s.status, s.boat_id
                    FROM booked_seats bs 
                    LEFT JOIN seat s ON s.id = bs.seat_id 
                    WHERE bs.payment_id = ?";
            
            // Optional: filter by schedule if provided
            if ($scheduleId) {
                $sql .= " AND (bs.schedule_departure_id = ? OR bs.schedule_return_id = ?)";
                $booked = $db->query($sql, [$paymentId, $scheduleId, $scheduleId])->getResultArray();
            } else {
                $booked = $db->query($sql, [$paymentId])->getResultArray();
            }

            return $this->jsonResponse($booked ?? []);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/admin/yacht-seats?schedule_id=X
    // Fetch seat layout for a specific schedule
    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    private function isAdminUser(): bool
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        $rawToken = substr($authHeader, 7);
        
        // Direct query - handle both raw (legacy) and hashed tokens
        $db = \Config\Database::connect();
        
        // Try 1: Check if token is raw (legacy)
        $tokenRow = $db->table('api_tokens')
            ->where('token', $rawToken)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getFirstRow('array');
        
        // Try 2: Check if token needs hashing (new method)
        if (!$tokenRow) {
            $tokenHash = hash('sha256', $rawToken);
            $tokenRow = $db->table('api_tokens')
                ->where('token', $tokenHash)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()
                ->getFirstRow('array');
        }

        if (!$tokenRow) {
            return false;
        }

        $userModel = new \Myth\Auth\Models\UserModel();
        $user = $userModel->find((int) $tokenRow['user_id']);

        if (!$user) {
            return false;
        }

        // Check if user is in admin group using direct query to auth_groups_users table (Myth\Auth)
        $adminGroupId = 1; // 'admin' group from auth_groups table
        
        $result = $db->table('auth_groups_users')
            ->where('user_id', $user->id)
            ->where('group_id', $adminGroupId)
            ->countAllResults();

        return $result > 0;
    }

}
