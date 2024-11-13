<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Ramsey\Uuid\Uuid;
use App\Models\Payment;
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
    use ResponseTrait;

    public function __construct()
    {
        helper('form');
        // Set Xendit API Key
        Configuration::setXenditKey("xnd_development_aFudHYZlYFN6B7Cey9ByjWVMGG6Xa8NITmWn8ccTAhQ3Kn1mpDjdUNZcvcstk3F");
        $this->paymentModel = new Payment();
    }

    public function create()
    {
        $apiInstance = new InvoiceApi();

        $user_id = 323;  // Pastikan mengganti ini dengan ID pengguna yang sesuai
        $jml_pax = (int)$this->request->getPost('jml_pax');
        $rute = $this->request->getPost('nama_rute');
        $amount = (int)$this->request->getPost('amount');

        // Get the payer's email from the post data or use a default one
        $payer_email = 'fahdiazhannu31@gmail.com';

        try {
            // Prepare parameters for Xendit API
            $params = [
                'external_id' => Uuid::uuid4()->toString(),
                'payer_email' => $payer_email,
                'description' => 'Pembelian tiket Rute ' . $rute . '<br> Sejumlah ' . $jml_pax . ' PAX',
                'amount' => $amount, // Ensure amount is an integer
                'customer' => [
                    'given_names' => 'Fahdi',
                    'surname' => 'Azhannu',
                    'email' => 'fahdiazhannu31@gmail.com',
                    'mobile_number' => '+6281398744517',
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email', 'whatsapp'],
                    'invoice_reminder' => ['email', 'whatsapp'],
                    'invoice_paid' => ['email', 'whatsapp']
                ],
                'success_redirect_url' => base_url('/payment-success'),
                'failure_redirect_url' => base_url('/payment-failure')
            ];


            // Attempt to create an invoice using the Xendit API
            $invoice = $apiInstance->createInvoice($params);

            // Prepare data to store in the database (payment)
            $invoiceData = [
                'user_id' => $user_id,
                'jml_pax' => $jml_pax, // Ensure the value is cast to an integer
                'rute' => $rute,
                'status' => 'PENDING',
                'payer_email' => $params['payer_email'],
                'external_id' => $params['external_id'],
                'checkout_link' => $invoice['invoice_url'],
            ];

            // Insert the invoice data into the database
            $this->paymentModel->insert($invoiceData);  // Assume insert returns the ID of the newly inserted row

            // Redirect to the invoice URL for payment
            return redirect()->to($invoice['invoice_url']);
        } catch (\Exception $e) {
            // Handle general exception
            return redirect()->back()->with('error', 'Error saat membuat invoice: ' . $e->getMessage());
        }
    }



    public function webhook()
    {
        $apiInstance = new InvoiceApi();
        $post = $this->request->getJSON();

        // Retrieve invoice from Xendit using the ID from the request
        $invoice_id = $post->id;
        $external_id = $post->external_id;
        // Retrieve invoice using Xendit API
        $getInvoice = $apiInstance->getInvoiceById($invoice_id);

        $paymentModel = new Payment();

        // Find payment record based on external_id
        $payment = $paymentModel->where('external_id', $external_id)->get()->getResult();

        // Check if payment exists
        if (empty($payment)) {
            return $this->response->setStatusCode(404)->setJSON(['data' => 'Payment not found']);
        }

        // Check if payment status is already settled
        if ($payment[0]->status == 'SETTLED') {
            return $this->response->setJSON(['data' => 'Payment has already been processed']);
        }


        // Update payment status
        $updatedData = [
            'status' => strtolower($getInvoice['status']), // Access status from the Xendit invoice response object
        ];

        // Update the payment record in the database
        $paymentModel->update($payment[0]->id, $updatedData);

        // Return response indicating success
        return $this->response->setJSON([
            'data' => 'Success',
        ]);
    }

    public function success()
    {
        $paymentModel = new Payment();
        $user_id = 323; // Replace with dynamic user_id if available
        $payment = $paymentModel->where('user_id', $user_id)->orderBy('id', 'DESC')->first();

        // Ensure you're passing the payment data to the view correctly
        $data['qrcode'] = $payment; // Pass the full payment data, not just the string.

        return view('payment_success', $data);
    }



    public function generateQR()
    {
        $paymentModel = new Payment();
        $user_id = 323; // Replace with dynamic user_id if available
        $payment = $paymentModel->where('user_id', $user_id)->orderBy('id', 'DESC')->first();

        // Check if payment exists and has a status of 'PAID' or 'SETTLED'
        if (!$payment || ($payment['status'] !== 'PAID' && $payment['status'] !== 'SETTLED')) {
            return $this->response->setJSON(['error' => 'Payment not found or not settled/paid']);
        }

        // Prepare QR code data as JSON
        $qrData = json_encode([
            'user_id' => $payment['user_id'],
            'jml_pax' => $payment['jml_pax'],
            'rute' => $payment['rute']
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
        if (!isset($decodedData['user_id']) || !isset($decodedData['jml_pax']) || !isset($decodedData['rute'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Beberapa data QR Code tidak ditemukan.'
            ]);
        }

        // Mendapatkan data dari decoded QR Code
        $user_id = $decodedData['user_id'];
        $jml_pax = $decodedData['jml_pax'];
        $rute = $decodedData['rute'];

        // Memanggil model untuk memverifikasi data QR code dengan user_id
        $paymentModel = new Payment();
        $payment = $paymentModel->where('user_id', $user_id)
            ->where('jml_pax', $jml_pax)
            ->where('rute', $rute)
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
}
