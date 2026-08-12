<?php

namespace App\Controllers;

use App\Models\BoatModel;
use App\Models\PackageModel;
use App\Models\FooterModel;
use App\Models\AboutusModel;
use App\Models\HomeModel;
use App\Models\PackageScheduleModel;
use App\Models\Payment;
use App\Models\BookedSeat;
use App\Models\Seat;


class Users extends BaseController
{
    protected $paymentModel;
    protected $db, $builder;
    protected $bookedSeatModel;
    protected $FooterModel;
    protected $AboutusModel;
    protected $HomeModel;
    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->bookedSeatModel = new BookedSeat();
        $this->db = \Config\Database::connect();
        $this->builder = $this->db->table('users');

        $this->FooterModel = new FooterModel();
        $this->AboutusModel = new AboutusModel();
        $this->HomeModel = new HomeModel();
    }
    public function indexnonlogin(): string
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | Home';
        return view('users/index', $data);
    }

    public function transacationUser()
    {
        $paymentModel = new Payment();
        $user_id = session('user_id');
        $data['payments'] = $paymentModel->where('user_id', $user_id);
    }

    public function aboutusnonlogin(): string
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | About Us';
        return view('users/aboutusnonlogin', $data);
    }

    public function index(): string
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | Home';
        return view('users/index', $data);
    }

    public function listPackage()
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        // Load the model
        $packageModel = new PackageModel();
        $data['title'] = 'Nama Sepa | Our Packages';
        // Fetch data from the database
        $data['packages'] = $packageModel->findAll();
        return view('users/listpackage', $data);
    }

     public function detailPackage($id)
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | Detail Package';
        $paymentModel = new Payment();
        $packageModel = new PackageModel();
        $scheduleModel = new PackageScheduleModel(); // Assume this is the model for package_schedule
        $boatModel = new BoatModel();

        // Get the database connection
        $db = \Config\Database::connect();

        // Ambil data package berdasarkan ID
        $data['package'] = $packageModel->find($id);
        $idpackage = $id;

        if (!$data['package']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Package not found");
        }

        // Get the schedule dates for this package
        $data['departure'] = $db->table('schedule')
            ->select('schedule.*, boat.boat_name as boat_name') // pilih kolom yang ingin diambil
            ->join('boat', 'boat.id = schedule.boat_id')   // relasi antar tabel
            ->where('schedule.type', 'DEPARTURE')
            ->get()
            ->getResultArray();


        $data['return'] = $db->table('schedule')
            ->select('schedule.*, boat.boat_name as boat_name') // pilih kolom yang ingin diambil
            ->join('boat', 'boat.id = schedule.boat_id')   // relasi antar tabel
            ->where('type', 'RETURN')
            ->get()
            ->getResultArray();

$data['pendingTransaction'] = null;
    if (logged_in()) {
        $data['pendingTransaction'] = $paymentModel
            ->where('user_id', user()->id)
            ->where('status', 'pending')
            ->first();
    }

        return view('users/detailpackage', $data);
    }
    
    public function aboutUs()
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | About Us';
        return view('users/aboutus', $data);
    }

    public function indextwo(): string
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | Home';
        return view('welcome_message', $data);
    }

    public function listTransaction()
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | Your Transactions';
        $user_id = user()->id;
        $paymentModel = new Payment();
        $seatModel = new Seat();
        $bookedSeatModel = new BookedSeat();
        $data['payments'] = $paymentModel->getPaymentsWithDetails($user_id);
        $data['seats'] = $seatModel->findAll();
        $data['bookedSeats'] = $bookedSeatModel->findAll();
        $data['user_id'] = $user_id;
        // Kirim ke view
        //return response()->setJSON($data);
        return view('users/listtransaction', $data);
    }

    public function crudfooter()
    {
        // Cara ngambil semua data dari table category
        $footer = $this->FooterModel->findAll();

        $data = [
            'footer' => $footer,
        ];

        return view('admin/crudfooter', $data);
    }

    public function crudhome()
    {
        // Cara ngambil semua data dari table category
        $home = $this->HomeModel->findAll();

        $data = [
            'home' => $home,
        ];

        return view('admin/crudhome', $data);
    }

    public function crudaboutus()
    {
        // Cara ngambil semua data dari table category
        $aboutus = $this->AboutusModel->findAll();

        $data = [
            'aboutus' => $aboutus,
        ];

        return view('admin/crudaboutus', $data);
    
    }
        public function explore()
    {
        $footer = $this->FooterModel->findAll();
        $data['footer'] = $footer;
        $data['title'] = 'Nama Sepa | Explore';
        return view('users/explore', $data);
    }
}
