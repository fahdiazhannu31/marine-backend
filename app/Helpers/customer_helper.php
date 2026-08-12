<?php

use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\Customer\CustomerRequest;
use Xendit\XenditSdkException;

if (!function_exists('createCustomerForPaymentGateway')) {
    function createCustomerForPaymentGateway($user)
    {
        require_once(__DIR__ . '/../../vendor/autoload.php');

        // Konfigurasi API Key Xendit
        Configuration::setXenditKey("xnd_development_aFudHYZlYFN6B7Cey9ByjWVMGG6Xa8NITmWn8ccTAhQ3Kn1mpDjdUNZcvcstk3F");

        $apiInstance = new CustomerApi();
        $idempotency_key = "idempotency-" . uniqid();

        // Memisahkan fullname menjadi given_names dan surname
        $fullname = $user['fullname'] ?? 'Anonymous';
        $nameParts = explode(' ', $fullname, 2);
        $given_names = $nameParts[0]; // Nama depan
        $surname = $nameParts[1] ?? ''; // Nama belakang, jika ada

        // Data customer request
        $customer_request = new CustomerRequest([
            'reference_id' => 'user_' . $user['id'],
            'type' => 'INDIVIDUAL',
            'individual_detail' => [
                'given_names' => $given_names,
                'surname' => $surname
            ],
            'email' => $user['email'],
        ]);

        try {
            $result = $apiInstance->createCustomer($idempotency_key, null, $customer_request);
            log_message('info', 'Customer created successfully: ' . json_encode($result));
        } catch (XenditSdkException $e) {
            log_message('error', 'Xendit SDK Exception: ' . $e->getMessage());
        } catch (\Exception $e) {
            log_message('error', 'General Exception: ' . $e->getMessage());
        }
    }
}
