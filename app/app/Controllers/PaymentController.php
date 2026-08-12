<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Ramsey\Uuid\Uuid;
use App\Models\Payment;
use App\Models\PackageScheduleModel;
use App\Models\ScheduleModel;
use App\Models\DepartureModel;
use App\Models\ReturnModel;
use App\Models\FooterModel;
use CodeIgniter\HTTP\RequestTrait;
use Xendit\Invoice\InvoiceStatus;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;
use CodeIgniter\API\ResponseTrait;
use Xendit\Invoice\NotificationChannel;
use Xendit\Invoice\NotificationPreference;


class PaymentController extends BaseController
{
    protected $paymentModel;
    protected $packageScheduleModel;
    protected $departureModel;
    protected $returnModel;
    protected $scheduleModel;
    protected $FooterModel;
    use ResponseTrait;

    public function __construct()
    {
        helper('form');
        // Set Xendit API Key
        Configuration::setXenditKey(env('xendit.APIKey'));
        $this->paymentModel = new Payment();
        $this->packageScheduleModel = new PackageScheduleModel();
        $this->departureModel = new DepartureModel();
        $this->returnModel = new ReturnModel();
        $this->scheduleModel = new scheduleModel();
        $this->FooterModel = new FooterModel();
    }

    public function create()
    {
        $apiInstance = new InvoiceApi();

        $user_id = (int)$this->request->getPost('user_id');
        $jml_pax = (int)$this->request->getPost('jml_pax');
        $package_id = (int)$this->request->getPost('package_id');
        $package_name = $this->request->getPost('package_name');
        $schedule_departure_id = (int)$this->request->getPost('schedule_departure_id');
        $schedule_return_id = (int)$this->request->getPost('schedule_return_id');
        $amount = (int)$this->request->getPost('amount');
        $phone = $this->request->getPost('phone');
        $email = $this->request->getPost('email');

        // Split fullname into given_names and surname
        $fullname = $this->request->getPost('fullname');
        $names = explode(' ', $fullname, 2); // Membagi berdasarkan spasi pertama
        $given_names = $names[0];
        $surname = isset($names[1]) ? $names[1] : '';

        $payer_email = $this->request->getPost('email');

        try {
            // Prepare parameters for Xendit API
            $params = [
                'external_id' => Uuid::uuid4()->toString(),
                'payer_email' => $payer_email,
                'description' => 'Pembelian tiket Rute ' . $package_name . '<br> Sejumlah ' . $jml_pax . ' PAX',
                'amount' => $amount,
                'invoice_duration' => 2880,
                'currency' => 'IDR',
                'customer' => [
                    'given_names' => $given_names,
                    'surname' => $surname,
                    'email' => $email,
                    'mobile_number' => $phone,
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email', 'whatsapp'],
                    'invoice_reminder' => ['email', 'whatsapp'],
                    'invoice_paid' => ['email', 'whatsapp']
                ],
                'success_redirect_url' => base_url('/payment-success'),
                'failure_redirect_url' => base_url('/payment-failure')
            ];

            $invoice = $apiInstance->createInvoice($params);

            // Prepare data to store in the database (payment)
            $invoiceData = [
                'user_id' => $user_id,
                'jml_pax' => $jml_pax,
                'package_id' => $package_id,
                'package_name' => $package_name,
                'schedule_departure_id' => $schedule_departure_id,
                'schedule_return_id' => $schedule_return_id,
                'amount' => $amount,
                'status' => 'PENDING',
                'payer_email' => $params['payer_email'],
                'external_id' => $params['external_id'],
                'checkout_link' => $invoice['invoice_url'],
            ];

            $this->paymentModel->insert($invoiceData);

            // Update departure slots
            $departureSchedule = $this->scheduleModel->find($schedule_departure_id);
            if (!$departureSchedule) {
                throw new \Exception("Departure schedule not found.");
            }

            $currentDepartureSlots = $departureSchedule['total_pax'];
            $newDepartureSlots = $currentDepartureSlots - $jml_pax;

            if ($newDepartureSlots < 0) {
                throw new \Exception("Not enough available slots for the departure schedule.");
            }

            $this->scheduleModel->update($schedule_departure_id, ['total_pax' => $newDepartureSlots]);

            // Update return slots if schedule_return_id exists
            if ($schedule_return_id) {
                $returnSchedule = $this->scheduleModel->find($schedule_return_id);
                if (!$returnSchedule) {
                    throw new \Exception("Return schedule not found.");
                }

                $currentReturnSlots = $returnSchedule['total_pax'];
                $newReturnSlots = $currentReturnSlots - $jml_pax;

                if ($newReturnSlots < 0) {
                    throw new \Exception("Not enough available slots for the return schedule.");
                }

                $this->scheduleModel->update($schedule_return_id, ['total_pax' => $newReturnSlots]);
            }

            // Redirect to the invoice URL for payment
            return redirect()->to($invoice['invoice_url']);
        } catch (\Throwable $e) {
    echo "<pre>";
    echo "Message : " . $e->getMessage() . PHP_EOL;
    echo "File    : " . $e->getFile() . PHP_EOL;
    echo "Line    : " . $e->getLine() . PHP_EOL;
    echo PHP_EOL;
    echo $e->getTraceAsString();
    die;
}
    }


    public function webhook()
    {
        try {
            // Set Xendit API Key
            Configuration::setXenditKey(env('xendit.APIKey'));
            
            $apiInstance = new InvoiceApi();
            $post = $this->request->getJSON(true);

            log_message('info', '[Webhook] Received: ' . json_encode($post));

            // Retrieve invoice from Xendit using the ID from the request
            $invoice_id = $post['id'] ?? null;
            $external_id = $post['external_id'] ?? null;

            if (!$invoice_id || !$external_id) {
                log_message('error', '[Webhook] Missing invoice_id or external_id');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing required fields']);
            }

            // Retrieve invoice using Xendit API
            $getInvoice = $apiInstance->getInvoiceById($invoice_id);
            log_message('info', '[Webhook] Xendit Invoice Status: ' . $getInvoice['status']);

            $paymentModel = new Payment();

            // Find payment record based on external_id
            $payment = $paymentModel->where('external_id', $external_id)->first();

            // Check if payment exists
            if (!$payment) {
                log_message('error', '[Webhook] Payment not found for external_id: ' . $external_id);
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Payment not found']);
            }

            // Check if payment status is already settled
            if ($payment['status'] === 'SETTLED') {
                log_message('info', '[Webhook] Payment already settled');
                return $this->response->setJSON(['data' => 'Payment has already been processed']);
            }

            // Update payment status
            $updatedData = [
                'status' => strtoupper($getInvoice['status']),
            ];

            // Update the payment record in the database
            $paymentModel->update($payment['id'], $updatedData);
            log_message('info', '[Webhook] Payment updated: ' . $external_id . ' -> ' . $getInvoice['status']);

            // Return response indicating success
            return $this->response->setJSON([
                'data' => 'Success',
                'status' => $getInvoice['status']
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Webhook] Error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }


    public function manualCheckout()
    {
        try {
            $user_id = (int)$this->request->getPost('user_id');
            $jml_pax = (int)$this->request->getPost('jml_pax');
            $package_id = (int)$this->request->getPost('package_id');
            $package_name = $this->request->getPost('package_name');
            $schedule_departure_id = (int)$this->request->getPost('schedule_departure_id');
            $schedule_return_id = (int)$this->request->getPost('schedule_return_id');
            $amount = (int)$this->request->getPost('amount');
            $phone = $this->request->getPost('phone');
            $email = $this->request->getPost('email');
            $fullname = $this->request->getPost('fullname');
            $trip_type = $this->request->getPost('trip_type');

            // Generate unique transaction ID
            $external_id = 'MANUAL-' . time() . '-' . rand(1000, 9999);

            // Get schedule details
            $departureSchedule = $this->scheduleModel->find($schedule_departure_id);
            if (!$departureSchedule) {
                throw new \Exception("Jadwal keberangkatan tidak ditemukan.");
            }

            $departure_date = $departureSchedule['date'];
            $return_date = null;

            if ($schedule_return_id) {
                $returnSchedule = $this->scheduleModel->find($schedule_return_id);
                if (!$returnSchedule) {
                    throw new \Exception("Jadwal kepulangan tidak ditemukan.");
                }
                $return_date = $returnSchedule['date'];
            }

            // Simpan pembayaran manual ke database
            $paymentData = [
                'user_id' => $user_id,
                'jml_pax' => $jml_pax,
                'package_id' => $package_id,
                'package_name' => $package_name,
                'schedule_departure_id' => $schedule_departure_id,
                'schedule_return_id' => $schedule_return_id,
                'status' => 'ON VERIFICATION',
                'payer_email' => $email,
                'external_id' => $external_id,
                'amount' => $amount,
                'trip_type' => $trip_type,
                'payment_method' => 'manual',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->paymentModel->insert($paymentData);

            // Update jumlah slot keberangkatan
            $newDepartureSlots = $departureSchedule['total_pax'] - $jml_pax;
            if ($newDepartureSlots < 0) {
                throw new \Exception("Kuota keberangkatan tidak mencukupi.");
            }
            $this->scheduleModel->update($schedule_departure_id, ['total_pax' => $newDepartureSlots]);

            // Update jumlah slot kepulangan
            if ($schedule_return_id) {
                $newReturnSlots = $returnSchedule['total_pax'] - $jml_pax;
                if ($newReturnSlots < 0) {
                    throw new \Exception("Kuota kepulangan tidak mencukupi.");
                }
                $this->scheduleModel->update($schedule_return_id, ['total_pax' => $newReturnSlots]);
            }

            // Set flashdata untuk Swal sukses
            session()->setFlashdata('success', 'Checkout manual berhasil! Silakan unggah bukti pembayaran.');

        $footer = $this->FooterModel->findAll();
            return view('users/manual_payment_checkout', [
                'title' => $package_name .  $jml_pax,
                'footer' => $footer,
                'external_id' => $external_id,
                'user_id' => $user_id,
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'jml_pax' => $jml_pax,
                'package_id' => $package_id,
                'package_name' => $package_name,
                'schedule_departure_id' => $schedule_departure_id,
                'schedule_return_id' => $schedule_return_id,
                'trip_type' => $trip_type,
                'amount' => $amount,
                'departure_date' => $departure_date,
                'return_date' => $return_date
            ]);
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function viewManualPaymentDetail($external_id)
    {
        // Cari data pembayaran berdasarkan external_id
        $payment = $this->paymentModel->where('external_id', $external_id)->first();
        if (!$payment) {
            throw new \Exception("Pembayaran tidak ditemukan. Silakan cek kembali external ID Anda.");
        }

        // Ambil detail jadwal keberangkatan
        $departureSchedule = $this->scheduleModel->find($payment['schedule_departure_id']);
        if (!$departureSchedule) {
            throw new \Exception("Jadwal keberangkatan tidak ditemukan. Silakan cek kembali schedule departure ID Anda.");
        }

        $departure_date = $departureSchedule['date'];
        $return_date = null;

        // Ambil detail jadwal kepulangan jika ada
        if (!empty($payment['schedule_return_id'])) {
            $returnSchedule = $this->scheduleModel->find($payment['schedule_return_id']);
            if ($returnSchedule) {
                $return_date = $returnSchedule['date'];
            }
        }
        $footer = $this->FooterModel->findAll();
        // Siapkan data untuk dikirim ke view
        $data = [
            'title' => $payment['package_name'] .  $payment['jml_pax'],
            'footer' => $footer,
            'external_id' => $payment['external_id'],
            'user_id' => $payment['user_id'],
            'jml_pax' => $payment['jml_pax'],
            'package_id' => $payment['package_id'],
            'package_name' => $payment['package_name'],
            'schedule_departure_id' => $payment['schedule_departure_id'],
            'schedule_return_id' => $payment['schedule_return_id'],
            'status' => $payment['status'],
            'amount' => $payment['amount'],
            'trip_type' => $payment['trip_type'],
            'created_at' => $payment['created_at'],
            'departure_date' => $departure_date,
            'return_date' => $return_date
        ];
        // Tampilkan detail pembayaran ke view
        return view('users/manual_payment_detail', $data);
    }



    public function processManualPayment()
    {
        // Validate file upload
        $validationRules = [
            'payment_proof' => [
                'label' => 'Payment Proof',
                'rules' => 'uploaded[payment_proof]|max_size[payment_proof,5120]|mime_in[payment_proof,image/jpg,image/jpeg,image/png,application/pdf]',
            ],
            'payment_date' => 'required',
            'bank_name' => 'required',
            'external_id' => 'required'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        // Handle file upload
        $file = $this->request->getFile('payment_proof');
        if ($file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/payment_proofs', $newName);

            // Update payment record with proof information
            $external_id = $this->request->getPost('external_id');

            $paymentUpdateData = [
                'payment_proof' => $newName,
                'payment_date' => $this->request->getPost('payment_date'),
                'bank_name' => $this->request->getPost('bank_name'),
                'notes' => $this->request->getPost('notes'),
                'status' => 'ON VERIFICATION',
                'transfer_slip' => $newName,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->paymentModel->where('external_id', $external_id)
                ->set($paymentUpdateData)
                ->update();

            return redirect()->to('payment-success')
                ->with('success', 'Your payment proof has been submitted and is pending verification');
        }

        return redirect()->back()->with('error', 'Failed to upload payment proof');
    }


    public function bookingSuccess($external_id)
    {
        $payment = $this->paymentModel->where('external_id', $external_id)->first();

        if (!$payment) {
            return redirect()->to('packages')->with('error', 'Booking not found');
        }

        $data = [
            'transaction_id' => $external_id,
            'booking' => $payment
        ];

        return view('payment_success', $data);
    }

    public function success()
    {
        $paymentModel = new Payment();
        $user_id = user()->id; // Replace with dynamic user_id if available
        $payment = $paymentModel->where('user_id', $user_id)->orderBy('id', 'DESC')->first();

        // Ensure you're passing the payment data to the view correctly
        $data['qrcode'] = $payment; // Pass the full payment data, not just the string.

        return view('payment_success', $data);
    }



    public function generateQR()
    {
        $paymentModel = new Payment();
        $user_id = user()->id; // Replace with dynamic user_id if available
        $payment = $paymentModel->where('user_id', $user_id)->orderBy('id', 'DESC')->first();

        // Check if payment exists and has a status of 'PAID' or 'SETTLED'
        if (!$payment || ($payment['status'] !== 'PAID' && $payment['status'] !== 'SETTLED')) {
            return $this->response->setJSON(['error' => 'Payment not found or not settled/paid']);
        }

        // Prepare QR code data as JSON
        $qrData = json_encode([
            'user_id' => $payment['user_id'],
            'jml_pax' => $payment['jml_pax'],
            'package_name' => $payment['package_name']
        ]);

        // Define file path for QR code
        $directory = 'assets/uploads/qr_codes/';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // Create directory if it does not exist
        }
        $filePath = $directory . $payment['external_id'] . '.png';

        // Generate QR code
        $writer = new PngWriter();
        $qrCode = QrCode::create($qrData)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(300)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $result = $writer->write($qrCode);
        $result->saveToFile($filePath);

        // Save QR code file path in database
        $updatedData = [
            'qr_code' => $filePath,
        ];
        $paymentModel->update($payment['id'], $updatedData);

        return $this->response->setJSON(['success' => 'QR Code generated successfully!', 'qr_code_path' => $filePath]);
    }

    public function failure()
    {
        return view('payment_failed');
    }

    public function scanQRCode()
    {
        return view('scan_qrcode');
    }

    public function validateQRCode()
    {
        // Mendapatkan data JSON yang dikirimkan melalui POST request
        $data = $this->request->getJSON();

        // Mengecek apakah QR code ada di dalam data request
        if (!isset($data->barcode)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'QR Code tidak ditemukan dalam data.'
            ]);
        }

        $barcode = $data->barcode;

        // Dekode JSON dari QR code
        $decodedData = json_decode($barcode, true); // Menambahkan true untuk hasil array asosiatif

        // Cek apakah data JSON berhasil didecode
        if (!$decodedData) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data QR Code tidak valid atau tidak dapat didekode.'
            ]);
        }

        // Mengecek apakah semua field yang diperlukan ada
        if (!isset($decodedData['user_id']) || !isset($decodedData['jml_pax']) || !isset($decodedData['package_name'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Beberapa data QR Code tidak ditemukan.'
            ]);
        }

        // Mendapatkan data dari decoded QR Code
        $user_id = $decodedData['user_id'];
        $jml_pax = $decodedData['jml_pax'];
        $rute = $decodedData['package_name'];

        // Memanggil model untuk memverifikasi data QR code dengan user_id
        $paymentModel = new Payment();
        $payment = $paymentModel->where('user_id', $user_id)
            ->where('jml_pax', $jml_pax)
            ->where('package_name', $rute)
            ->first();

        // Jika data tidak ditemukan di database
        if (!$payment) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data pembayaran tidak ditemukan untuk QR Code ini.'
            ]);
        }

        // Mengecek apakah sudah ada data attendance
        if (!empty($payment['attendance'])) {
            return $this->response->setJSON([
                'status' => 'info',
                'message' => 'Anda sudah scan sebelumnya.',
                'data' => $payment
            ]);
        }

        // Update field attendance dengan waktu saat ini
        $currentTime = date('Y-m-d H:i:s');  // Waktu saat ini dalam format YYYY-MM-DD HH:MM:SS

        // Memperbarui field attendance
        $updateSuccess = $paymentModel->update($payment['id'], ['attendance' => $currentTime]);

        // Jika update gagal
        if (!$updateSuccess) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal memperbarui data attendance.'
            ]);
        }

        // Jika data ditemukan dan diupdate
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'QR Code valid. Data ditemukan dan attendance berhasil diperbarui.',
            'data' => $payment
        ]);
    }
    
    public function updateStatus()
    {
        $paymentModel = new Payment(); // Instansiasi model
        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');

        $paymentModel->update($id, ['status' => $status]); // Update status

        return $this->response->setJSON(['message' => 'Status updated successfully']);
    }

    // Handle GET requests to webhook (for testing or health checks)
    public function webhookGet()
    {
        return $this->response->setStatusCode(200)->setJSON([
            'message' => 'Webhook endpoint is running. Use POST to send events.',
            'endpoint' => '/payments/webhook/xendit',
            'method' => 'POST'
        ]);
    }

    // Handle OPTIONS requests for CORS
    public function webhookOptions()
    {
        return $this->response
            ->setStatusCode(200)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->setBody('');
    }
}