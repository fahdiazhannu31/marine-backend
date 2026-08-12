<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Myth\Auth\Controllers\AuthController;
use CodeIgniter\Controller;
use CodeIgniter\Session\Session;
use Myth\Auth\Config\Auth as AuthConfig;
use Myth\Auth\Entities\User;
use Myth\Auth\Models\UserModel;
use App\Models\ProjectModel;
use Xendit\Configuration;
use Xendit\Customer\CustomerApi;
use Xendit\Customer\CustomerRequest;
use Xendit\XenditSdkException;

class CustomController extends BaseController
{

    protected $auth;

    /**
     * @var AuthConfig
     */
    protected $config;

    /**
     * @var Session
     */
    protected $session;

    public function __construct()
    {
        // Most services in this controller require
        // the session to be started - so fire it up!
        $this->session = service('session');

        $this->config = config('Auth');
        $this->auth   = service('authentication');
    }

    public function attemptRegister()
    {
        // Check if registration is allowed
        if (! $this->config->allowRegistration) {
            return redirect()->back()->withInput()->with('error', lang('Auth.registerDisabled'));
        }

        $users = model(UserModel::class);

        // Validate basics first since some password rules rely on these fields
        $rules = config('Validation')->registrationRules ?? [
            'username' => 'required|alpha_numeric_space|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'fullname' => 'required|string|max_length[255]', // Validasi fullname
            'phone' => 'required|string|max_length[50]', // Validasi phone
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validate passwords since they can only be validated properly here
        $rules = [
            'password'     => 'required|strong_password',
            'pass_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Handle phone format to ensure it starts with +62
        $phone = $this->request->getPost('phone');
        if (substr($phone, 0, 1) == '0') {
            $phone = '+62' . substr($phone, 1); // Ganti 0 dengan +62
        } elseif (!preg_match('/^\+62/', $phone)) {
            $phone = '+62' . $phone; // Tambahkan +62 jika belum ada
        }

        // Split fullname into given_names and surname
        $fullname = $this->request->getPost('fullname');
        $names = explode(' ', $fullname, 2); // Membagi berdasarkan spasi pertama
        $given_names = $names[0]; // Nama depan
        $surname = isset($names[1]) ? $names[1] : ''; // Nama belakang, jika ada

        // Save the user
        $allowedPostFields = array_merge(['password', 'fullname', 'phone', 'email'], $this->config->validFields, $this->config->personalFields);
        $user              = new User($this->request->getPost($allowedPostFields));

        // Update phone number and split fullname
        $user->phone = $phone; // Set phone to the formatted value
        $user->fullname = $fullname; // Set fullname to original value
        $name_parts = explode(' ', $fullname);

       // Jika hanya ada satu nama (tidak ada spasi), tetapkan nilai default untuk surname
if (count($name_parts) == 1) {
    $given_names = $name_parts[0];
    $surname = '-'; // Atau nilai default lain seperti 'NoSurname', dll.
} else {
    $given_names = $name_parts[0];
    $surname = implode(' ', array_slice($name_parts, 1));
}

// Pastikan untuk menyimpan nilai given_names dan surname ke user
$user->given_names = $given_names;
$user->surname = $surname;

        $this->config->requireActivation === null ? $user->activate() : $user->generateActivateHash();

        // Ensure default group gets assigned if set
        if (! empty($this->config->defaultUserGroup)) {
            $users = $users->withGroup($this->config->defaultUserGroup);
        }

        if (! $users->save($user)) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        // Logic Tambah Customer API
        try {
            Configuration::setXenditKey(env('xendit.APIKey'));
            $customerApi = new CustomerApi();
            $idempotency_key = "idempotency-" . uniqid();
            $customer_request = new CustomerRequest([
                'reference_id' => 'MRN-00' . uniqid(),
                'type' => 'INDIVIDUAL',
                'individual_detail' => [
                    'given_names' => $given_names, // Nama depan
                    'surname' => $surname, // Nama belakang
                ],
                'email' => $user->email,
                'mobile_number' => $user->phone // Gunakan nomor telepon yang sudah diformat
            ]);
            // Run fungsi createCustomer
            $customerApi->createCustomer($idempotency_key, null, $customer_request);
        } catch (\Xendit\XenditSdkException $e) {
            $fullError = $e->getFullError() ? json_encode($e->getFullError()) : '';
            return redirect()->back()->with(
                'error',
                'Registration successful, but failed to create Xendit customer due to API error: ' . $e->getMessage() . ' Details: ' . $fullError
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(
                'error',
                'Registration successful, but failed to create Xendit customer due to an unexpected error: ' . $e->getMessage()
            );
        }

        if ($this->config->requireActivation !== null) {
            $activator = service('activator');
            $sent      = $activator->send($user);

            if (! $sent) {
                return redirect()->back()->withInput()->with('error', $activator->error() ?? lang('Auth.unknownError'));
            }

            // Success!
            return redirect()->route('login')->with('message', lang('Auth.activationSuccess'));
        }

        // Success!
        return redirect()->route('login')->with('message', lang('Auth.registerSuccess'));
    }
}
