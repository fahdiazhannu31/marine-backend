<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\Customer\CustomerRequest;
use Xendit\XenditSdkException;

class XenditController extends Controller
{
    public function createCustomer()
    {
        // Set Xendit API key
       Configuration::setXenditKey(env('xendit.APIKey'));
        // Create an instance of the Customer API
        $apiInstance = new CustomerApi();

        // Generate a unique idempotency key to prevent duplicate requests
        $idempotency_key = "idempotency-" . uniqid();

        // Create a customer request object
        $customer_request = new CustomerRequest([
            'reference_id' => 'demo_1475801962608',
            'type' => 'INDIVIDUAL',
            'individual_detail' => [
                'given_names' => 'John',
                'surname' => 'Doe'
            ],
            'email' => 'customers@website.com',
            'mobile_number' => '+628121234567890'
        ]);

        // Try making the API request
        try {
            $result = $apiInstance->createCustomer($idempotency_key, null, $customer_request);
            // Output the result as JSON
            return $this->response->setJSON($result);
        } catch (XenditSdkException $e) {
            // Handle SDK-specific exceptions and output error as JSON
            $error_response = [
                'error' => 'Exception when calling CustomerApi->createCustomer',
                'message' => $e->getMessage(),
                'full_error' => $e->getFullError()
            ];
            return $this->response->setJSON($error_response);
        } catch (\Exception $e) {
            // Handle general exceptions and output error as JSON
            $error_response = [
                'error' => 'General Exception',
                'message' => $e->getMessage()
            ];
            return $this->response->setJSON($error_response);
        }
    }
}
