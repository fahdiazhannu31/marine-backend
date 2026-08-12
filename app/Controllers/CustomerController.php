<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\Customer\CustomerRequest;
use Xendit\XenditSdkException;

class CustomerController extends Controller
{
    public function __construct()
    {
        // Set API Key untuk Xendit
       Configuration::setXenditKey(env('xendit.APIKey'));
    }

    public function registerCustomer()
    {
        // Ambil data pelanggan yang diinput dari form registrasi
        $data = $this->request->getPost();

        // Pastikan data valid sebelum mengirim ke Xendit
        if (empty($data['fullname']) || empty($data['email']) || empty($data['mobile_number'])) {
            return redirect()->back()->with('error', 'Data pelanggan tidak lengkap');
        }

        // Pecah fullname menjadi given_names dan surname
        $nameParts = explode(' ', $data['fullname']);
        $given_names = $nameParts[0]; // Nama depan
        $surname = isset($nameParts[1]) ? $nameParts[1] : ''; // Nama belakang (jika ada)

        // Buat instance CustomerApi
        $apiInstance = new CustomerApi();
        $idempotency_key = "idempotency-" . uniqid(); // Unique key untuk mencegah duplikasi permintaan

        // Buat objek CustomerRequest untuk dikirim ke Xendit
        $customer_request = new CustomerRequest([
            'reference_id' => 'demo_' . uniqid(),  // Gunakan reference_id unik
            'type' => 'INDIVIDUAL',
            'individual_detail' => [
                'given_names' => $given_names,
                'surname' => $surname
            ],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number']
        ]);

        try {
            // Kirim data pelanggan ke Xendit
            $result = $apiInstance->createCustomer($idempotency_key, null, $customer_request);
            // Tampilkan hasil sukses
            return view('customer/success', ['result' => $result]);
        } catch (XenditSdkException $e) {
            // Tangani error dari Xendit
            return redirect()->back()->with('error', 'Gagal membuat pelanggan di Xendit: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Tangani error umum
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
