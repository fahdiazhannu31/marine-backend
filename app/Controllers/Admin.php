<?php

namespace App\Controllers;

use App\Models\BoatModel;
use App\Models\BookedSeat;
use App\Models\Payment;
use App\Models\ScheduleModel;
use App\Models\Seat;
use FPDF;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;
use App\Models\PackageModel;
use App\Models\FooterModel;
use App\Models\AboutusModel;
use App\Models\HomeModel;
use Config\Validation;
use App\Libraries\BoardingPassPDF;

class Admin extends BaseController
{
    protected $paymentModel;
    protected $db, $builder;
    protected $bookedSeatModel;
    protected $PackageModel;
    protected $FooterModel;
    protected $AboutusModel;
    protected $HomeModel;
    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->bookedSeatModel = new BookedSeat();
        $this->db = \Config\Database::connect();
        $this->builder = $this->db->table('users');

        $this->PackageModel = new PackageModel();
        $this->FooterModel = new FooterModel();
        $this->AboutusModel = new AboutusModel();
        $this->HomeModel = new HomeModel();
    }

    public function index(): string
    {
        // $users = new \Myth\Auth\Models\UserModel();
        // $data['users'] = $users->findAll();
        $this->builder->select('users.id as userid, username, email, name');
        $this->builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $this->builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $query = $this->builder->get();

        $data['users'] = $query->getResult();

        return view('admin/index', $data);
    }

    public function detailuser($id = 0)
    {
        $this->builder->select('users.id as userid, username, email, fullname, user_image, name');
        $this->builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $this->builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $this->builder->where('users.id', $id);
        $query = $this->builder->get();

        $data['user'] = $query->getRow();

        if (empty($data['user'])) {
            return redirect()->to('/admin');
        }

        return view('admin/detailuser', $data);
    }

   public function crudlistpackage(): string
    {
        // $product = $this->PackageModel-> findAll();
        // untuk test apakah validasi masuk ke session
        // dd(session('validation'))
        // Nah ini session yang nyimpen fungsi validation yang di function addProduct
        // Ini session bisa di taro di BaseController biar nanti kalo function yang laen mau pake si session ga usah naro satu satu kaya gini
        session();

        // Cara ngambil semua data dari table category
        $packages = $this->PackageModel->findAll();

        // Fetch data from the database
        $data = [
            'packages' => $packages,

            // Nah ini manggil library si validationnya
            'validation' => \Config\Services::validation()
        ];
        return view('admin/crudlistpackage', $data);
    }

    /**
     * Generate Ticket PDF using FPDF
     * GET /admin/ticket-pdf/(:num)
     * 
     * Generate professional ticket PDF dengan 3-column layout:
     * Left: Boat photo | Middle: Ticket info | Right: QR code
     */
    public function generateTicketPDF($bookingId = 0)
    {
        try {
            // Get ticket data
            $ticketData = $this->getTicketDetail($bookingId);
            
            if (!$ticketData) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['error' => 'Ticket not found']);
            }
            
            // Create FPDF instance
            $pdf = new FPDF('L', 'mm', 'A5');  // Landscape, A5 size
            $pdf->AddPage();
            
            // Set fonts
            $pdf->SetFont('Arial', '', 10);
            
            // Page dimensions
            $pageWidth = $pdf->GetPageWidth();
            $pageHeight = $pdf->GetPageHeight();
            $margin = 5;
            $colWidth = ($pageWidth - 3 * $margin) / 3;
            $rowHeight = $pageHeight - 2 * $margin;
            
            // ===== LEFT COLUMN: Boat Photo (1/3) =====
            $x1 = $margin;
            $y1 = $margin;
            
            if (!empty($ticketData['boat_image'])) {
                // Add boat image
                $pdf->Image($ticketData['boat_image'], $x1, $y1, $colWidth, $rowHeight, 'JPG');
            } else {
                // Placeholder if no image
                $pdf->SetFillColor(200, 200, 200);
                $pdf->Rect($x1, $y1, $colWidth, $rowHeight, 'F');
                $pdf->SetFont('Arial', '', 12);
                $pdf->SetXY($x1, $y1 + $rowHeight / 2 - 5);
                $pdf->MultiCell($colWidth, 10, '[Boat Photo]', 0, 'C');
            }
            
            // ===== MIDDLE COLUMN: Ticket Info (1/3) =====
            $x2 = $x1 + $colWidth + $margin;
            $y2 = $y1;
            
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect($x2, $y2, $colWidth, $rowHeight, '');  // Border
            
            // Background color (cream)
            $pdf->SetFillColor(247, 242, 234);
            $pdf->Rect($x2, $y2, $colWidth, $rowHeight, 'F');
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($x2 + 2, $y2 + 3);
            $pdf->Cell($colWidth - 4, 5, 'NAMA MARINE', 0, 1, 'C');
            
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetXY($x2 + 2, $y2 + 9);
            $pdf->Cell($colWidth - 4, 3, 'TICKET', 0, 1, 'C');
            
            // Info rows
            $infoY = $y2 + 16;
            $lineHeight = 5;
            $labelWidth = $colWidth * 0.35;
            $valueWidth = $colWidth - $labelWidth - 4;
            
            $pdf->SetFont('Arial', 'B', 8);
            
            // Row 1: Passenger
            $pdf->SetXY($x2 + 2, $infoY);
            $pdf->Cell($labelWidth, $lineHeight, 'Passenger:', 0, 0);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell($valueWidth, $lineHeight, substr($ticketData['user_name'], 0, 18), 0, 1);
            
            // Row 2: Ref
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x2 + 2, $infoY + $lineHeight);
            $pdf->Cell($labelWidth, $lineHeight, 'Ref:', 0, 0);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell($valueWidth, $lineHeight, '#' . $ticketData['id'], 0, 1);
            
            // Row 3: Package
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x2 + 2, $infoY + $lineHeight * 2);
            $pdf->Cell($labelWidth, $lineHeight, 'Package:', 0, 0);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell($valueWidth, $lineHeight, substr($ticketData['package_name'], 0, 18), 0, 1);
            
            // Row 4: Date
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x2 + 2, $infoY + $lineHeight * 3);
            $pdf->Cell($labelWidth, $lineHeight, 'Date:', 0, 0);
            $pdf->SetFont('Arial', '', 8);
            $departDate = date('M d', strtotime($ticketData['date_departure']));
            $pdf->Cell($valueWidth, $lineHeight, $departDate, 0, 1);
            
            // Row 5: Boat
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x2 + 2, $infoY + $lineHeight * 4);
            $pdf->Cell($labelWidth, $lineHeight, 'Boat:', 0, 0);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell($valueWidth, $lineHeight, substr($ticketData['boat_departure_name'], 0, 18), 0, 1);
            
            // Row 6: Amount
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x2 + 2, $infoY + $lineHeight * 5);
            $pdf->Cell($labelWidth, $lineHeight, 'Amount:', 0, 0);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(201, 91, 102);  // Red color
            $pdf->Cell($valueWidth, $lineHeight, 'IDR ' . number_format($ticketData['amount'], 0, '.', ','), 0, 1);
            $pdf->SetTextColor(0, 0, 0);  // Reset color
            
            // ===== RIGHT COLUMN: QR Code (1/3) =====
            $x3 = $x2 + $colWidth + $margin;
            $y3 = $y1;
            
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect($x3, $y3, $colWidth, $rowHeight, '');  // Border
            
            // Background
            $pdf->SetFillColor(247, 242, 234);
            $pdf->Rect($x3, $y3, $colWidth, $rowHeight, 'F');
            
            // QR Code
            if (!empty($ticketData['qr_code'])) {
                $qrSize = 25;  // QR code size in mm
                $qrX = $x3 + ($colWidth - $qrSize) / 2;
                $qrY = $y3 + 10;
                
                // Download and display QR code image
                $pdf->Image($ticketData['qr_code'], $qrX, $qrY, $qrSize, $qrSize, 'PNG');
            }
            
            // QR Label
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetXY($x3 + 2, $y3 + $qrSize + 15);
            $pdf->Cell($colWidth - 4, 3, 'CHECK-IN', 0, 1, 'C');
            
            // Seats
            if (!empty($ticketData['seats'])) {
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->SetXY($x3 + 2, $y3 + $qrSize + 20);
                $pdf->Cell($colWidth - 4, 3, 'Seats:', 0, 1, 'C');
                
                $seatText = implode(', ', array_map(fn($s) => $s['seat_number'], $ticketData['seats']));
                $pdf->SetFont('Arial', '', 6);
                $pdf->SetXY($x3 + 2, $y3 + $qrSize + 24);
                $pdf->MultiCell($colWidth - 4, 2.5, $seatText, 0, 'C');
            }
            
            // Output PDF
            $filename = 'ticket_' . $ticketData['id'] . '.pdf';
            $pdf->Output('D', $filename);
            
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get ticket detail data (helper)
     */
    private function getTicketDetail($bookingId)
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('payments p')
            ->select('
                p.id, p.user_id, p.booking_id, p.amount,
                u.name as user_name, u.email,
                pk.package_name,
                sd.date_departure, sd.boat_id,
                b.boat_name as boat_departure_name,
                p.qr_code
            ')
            ->join('users u', 'u.id = p.user_id', 'left')
            ->join('packages pk', 'pk.id = p.package_id', 'left')
            ->join('schedule sd', 'sd.id = p.schedule_departure_id', 'left')
            ->join('boat b', 'b.id = sd.boat_id', 'left')
            ->where('p.id', $bookingId)
            ->get();
        
        $payment = $query->getRow('array');
        
        if (!$payment) {
            return null;
        }
        
        // Get boat image
        $boatModel = new BoatModel();
        $boat = $boatModel->find($payment['boat_id']);
        $payment['boat_image'] = $boat['photo1'] ?? null;
        
        // Get seats
        $seatsQuery = $db->table('booked_seat bs')
            ->select('s.seat_number')
            ->join('seat s', 's.id = bs.seat_id', 'left')
            ->where('bs.payment_id', $bookingId)
            ->get();
        
        $payment['seats'] = $seatsQuery->getResultArray();
        
        return $payment;
    }

    public function crudlistpackageadd()
    {
        $validation = $this->validate([
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'required|min_length[3]|max_length[255]',
            'price_per_pax' => 'required|numeric',
            'price_per_pax_weekend' => 'required|numeric',
            'pax_count' => 'required|integer',
            'status' => 'required',
            'photo1' => 'permit_empty|max_size[photo1,2048]|is_image[photo1]|mime_in[photo1,image/jpg,image/jpeg,image/png]',
            'photo2' => 'permit_empty|max_size[photo2,2048]|is_image[photo2]|mime_in[photo2,image/jpg,image/jpeg,image/png]',
            'photo3' => 'permit_empty|max_size[photo3,2048]|is_image[photo3]|mime_in[photo3,image/jpg,image/jpeg,image/png]',
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $packageModel = new PackageModel();

        // Upload dan simpan foto jika ada
        $photoName1 = $this->request->getFile('photo1')->isValid() ? $this->request->getFile('photo1')->getRandomName() : null;
        $photoName2 = $this->request->getFile('photo2')->isValid() ? $this->request->getFile('photo2')->getRandomName() : null;
        $photoName3 = $this->request->getFile('photo3')->isValid() ? $this->request->getFile('photo3')->getRandomName() : null;

        if ($photoName1) $this->request->getFile('photo1')->move('assets_users/images/', $photoName1);
        if ($photoName2) $this->request->getFile('photo2')->move('assets_users/images/', $photoName2);
        if ($photoName3) $this->request->getFile('photo3')->move('assets_users/images/', $photoName3);

        $packageModel->save([
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'photo1' => $photoName1 ? $photoName1 : null,
            'photo2' => $photoName2 ? $photoName2 : null,
            'photo3' => $photoName3 ? $photoName3 : null,
            'price_per_pax' => $this->request->getPost('price_per_pax'),
            'price_per_pax_weekend' => $this->request->getPost('price_per_pax_weekend'),
            'pax_count' => $this->request->getPost('pax_count'),
            'status' => $this->request->getPost('status')
        ]);

        return redirect()->to('/crudlistpackage')->with('success', 'Package successfully added!');
    }


    public function crudlistpackageedit($id)
    {
        $package = $this->PackageModel->find($id);

        if (!$package) {
            return redirect()->to('/crudlistpackage')->with('error', 'Package not found.');
        }

        $data = [
            'package' => $package,
        ];

        return view('admin/editlistpackage', $data);
    }

    public function crudlistpackageupdate($id)
    {
        $package = $this->PackageModel->find($id);

        if (!$package) {
            return redirect()->to('/crudlistpackage')->with('error', 'Package not found.');
        }

        $validation = $this->validate([
            'title' => 'required|min_length[3]|max_length[255]',
            'price_per_pax' => 'required|numeric',
            'price_per_pax_weekend' => 'required|numeric',
            'pax_count' => 'required|integer',
            'status' => 'required',
            'photo1' => 'max_size[photo1,2048]|is_image[photo1]|mime_in[photo1,image/jpg,image/jpeg,image/png]',
            'photo2' => 'max_size[photo2,2048]|is_image[photo2]|mime_in[photo2,image/jpg,image/jpeg,image/png]',
            'photo3' => 'max_size[photo3,2048]|is_image[photo3]|mime_in[photo3,image/jpg,image/jpeg,image/png]',
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // Data utama untuk diupdate
        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'price_per_pax' => $this->request->getPost('price_per_pax'),
            'price_per_pax_weekend' => $this->request->getPost('price_per_pax_weekend'),
            'pax_count' => $this->request->getPost('pax_count'),
            'status' => $this->request->getPost('status'),
        ];

        // Upload foto baru jika ada
        foreach (['photo1', 'photo2', 'photo3'] as $photo) {
            $file = $this->request->getFile($photo);
            if ($file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move('assets_users/images/', $newName);
                $data[$photo] = $newName;

                // Hapus foto lama jika ada
                if (!empty($package[$photo]) && file_exists($package[$photo])) {
                    unlink($package[$photo]);
                }
            }
        }

        $this->PackageModel->update($id, $data);

        return redirect()->to('/crudlistpackage')->with('success', 'Package successfully updated!');
    }


    public function crudlistpackagedelete($id)
    {
        $package = $this->PackageModel->find($id);

        if ($package) {
            foreach (['photo1', 'photo2', 'photo3'] as $photo) {
                if (file_exists($package[$photo])) {
                    unlink($package[$photo]);
                }
            }
            $this->PackageModel->delete($id);
        }

        return redirect()->to('/crudlistpackage')->with('success', 'Package successfully deleted!');
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

    public function crudhomeedit($id)
    {
        $home = $this->HomeModel->find($id);

        if (!$home) {
            return redirect()->to('/crudhome')->with('error', 'home not found.');
        }

        $data = [
            'home' => $home,
        ];

        return view('admin/edithome', $data);
    }

    public function crudhomeupdate($id)
    {
        $home = $this->HomeModel->find($id);

        if (!$home) {
            return redirect()->to('/crudhome')->with('error', 'home not found.');
        }

        // Data utama untuk diupdate
        $data = [
            'jb_title' => $this->request->getPost('jb_title'),
            'jb_desc' => $this->request->getPost('jb_desc'),
        ];

        // Upload foto baru jika ada
        foreach (['jb_photo'] as $photo) {
            $file = $this->request->getFile($photo);
            if ($file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move('assets_users/images/', $newName);
                $data[$photo] = $newName;

                // Hapus foto lama jika ada
                if (!empty($home[$photo]) && file_exists($home[$photo])) {
                    unlink($home[$photo]);
                }
            }
        }

        $this->HomeModel->update($id, $data);

        return redirect()->to('/crudhome')->with('success', 'home successfully updated!');
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

    public function crudaboutusedit($id)
    {
        $aboutus = $this->AboutusModel->find($id);

        if (!$aboutus) {
            return redirect()->to('/crudaboutus')->with('error', 'aboutus not found.');
        }

        $data = [
            'aboutus' => $aboutus,
        ];

        return view('admin/editaboutus', $data);
    }

    public function crudaboutusupdate($id)
    {
        $aboutus = $this->AboutusModel->find($id);

        if (!$aboutus) {
            return redirect()->to('/crudaboutus')->with('error', 'aboutus not found.');
        }

        // Data utama untuk diupdate
        $data = [
            'jb_title' => $this->request->getPost('jb_title'),
            'jb_desc' => $this->request->getPost('jb_desc'),
            'as_title' => $this->request->getPost('as_title'),
            'as_desc' => $this->request->getPost('as_desc'),
            'as_name' => $this->request->getPost('as_name'),
            'as_position' => $this->request->getPost('as_position'),
        ];

        // Upload foto baru jika ada
        foreach (['jb_photo', 'as_photo'] as $photo) {
            $file = $this->request->getFile($photo);
            if ($file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move('assets_users/images/', $newName);
                $data[$photo] = $newName;

                // Hapus foto lama jika ada
                if (!empty($aboutus[$photo]) && file_exists($aboutus[$photo])) {
                    unlink($aboutus[$photo]);
                }
            }
        }

        $this->AboutusModel->update($id, $data);

        return redirect()->to('/crudaboutus')->with('success', 'aboutus successfully updated!');
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

    public function crudfooteredit($id)
    {
        $data['footer'] = $this->FooterModel->find($id);

        if (!$data['footer']) {
            return redirect()->to('/crudfooteredit')->with('error', 'Footer data not found.');
        }

        return view('admin/editfooter', $data);
    }

    // Proses update footer
    public function crudfooterupdate($id)
    {
        $footer = $this->FooterModel->find($id);

        if (!$footer) {
            return redirect()->to('/crudfooteredit')->with('error', 'Footer data not found.');
        }

        // Ambil data dari form
        $data = [
            'day_op' => $this->request->getPost('day_op'),
            'phone' => $this->request->getPost('phone'),
            'copyright' => $this->request->getPost('copyright'),
        ];

        // Update data di database
        $this->FooterModel->update($id, $data);

        return redirect()->to('/crudfooter')->with('success', 'Footer updated successfully.');
    }

    public function reservasidatax(): string
    {
        $paymentModel = new Payment();
        $seatModel = new Seat();
        $bookedSeatModel = new BookedSeat(); // Asumsi ada model BookedSeat untuk kursi yang sudah dipesan

        $data['payments'] = $this->db->table('payments a')
            ->select('a.*, b.date')
            ->join('boat b', 'b.id = a.boat_id', 'left')
            ->get()
            ->getResult();

        $data['seats'] = $seatModel->findAll();

        // Ambil kursi yang sudah dipesan beserta informasi payment_id
        $data['bookedSeats'] = $bookedSeatModel->findAll();

        // Ambil data kursi yang belum dibooking berdasarkan departure_schedule_id
        foreach ($data['payments'] as $payment) {
            $packageScheduleId = $payment->boat_id;

            // Query untuk mendapatkan tanggal keberangkatan dari departure_schedule
            $departureDate = $this->db->table('departure_schedule')
                ->select('departure_date')
                ->where('id', $packageScheduleId)
                ->get()
                ->getRow();

            if ($departureDate) {
                // Ambil tanggal keberangkatan
                $departureDate = $departureDate->departure_date;

                // Query untuk mendapatkan kursi yang belum dipesan berdasarkan departure_schedule_id dan tanggal keberangkatan
                $availableSeats = $this->db->table('seats')
                    // Mengasumsikan ada mekanisme untuk mengetahui jadwal keberangkatan terkait kursi
                    // Misalnya dengan menggunakan relasi lain atau logika penentuan jadwal kursi yang tersedia
                    ->join('booked_seats', 'seats.id = booked_seats.seat_id', 'left') // Bergabung dengan booked_seats
                    ->where('booked_seats.payment_id IS NULL') // Kursi yang belum dibooking
                    ->get()->getResult(); // Ambil hasilnya

                // Menyimpan kursi yang tersedia untuk setiap pembayaran
                $payment->availableSeats = $availableSeats;
            }
        }

        return view('admin/reservasidata', $data);
    }

    public function reservasidata(): string
    {
        $data['payments'] = $this->db->table('payments a')
            ->select('a.*, b.date, c.username')
            ->join('schedule b', 'b.id = a.schedule_departure_id', 'left')
            ->join('users c', 'c.id = a.user_id', 'left')
            ->get()
            ->getResult();

        return view('admin/reservasidata', $data);
    }

    public function detailDeparture($id)
    {
        $seatModel = new Seat();
        $bookedSeatModel = new BookedSeat();
        $data['payments'] = $this->db->table('payments a')
            ->select('a.*, 
                 d.fullname, 
                 c.boat_name AS boat_departure_name, 
                 f.boat_name AS boat_return_name,
                 b.date AS date_departure, 
                 e.date AS date_return')
            ->join('schedule b', 'b.id = a.schedule_departure_id AND b.type = "DEPARTURE"', 'left')
            ->join('schedule e', 'e.id = a.schedule_return_id AND e.type = "RETURN"', 'left')
            ->join('users d', 'd.id = a.user_id', 'left')
            ->join('boat c', 'c.id = b.boat_id', 'left') // Boat for departure
            ->join('boat f', 'f.id = e.boat_id', 'left') // Boat for return
            ->where('a.id', $id)
            ->get()
            ->getResult();

        $schedule_id = $data['payments'][0]->schedule_departure_id;

        $data['booked_seats'] = $bookedSeatModel->where('schedule_departure_id', $schedule_id)->findAll();

        // Ambil boat_id dari data payments pertama (misal, karena hanya satu hasil)

        $data['getSchedule'] = $this->db->table('schedule')
            ->select('*')
            ->where('id', $schedule_id)
            ->get()
            ->getResult();

        $boat_id = $data['getSchedule'][0]->boat_id;
        if ($boat_id) {
            // Ambil data kursi berdasarkan boat_id
            $data['seats'] = $this->db->table('seat')
                ->select('seat.*, boat.boat_name AS boat_name') // Pilih semua kolom dari seat dan nama boat
                ->join('boat', 'boat.id = seat.boat_id') // Join dengan tabel boat
                ->where('seat.boat_id', $boat_id) // Gunakan boat_id yang didapat dari payments
                ->get()
                ->getResult();
            $data['boat_name'] = $data['seats'][0]->boat_name;
        } else {
            // Jika boat_id tidak ditemukan pada payments
            $data['seats'] = [];
        }

        return view('admin/detail-departure', $data);
    }


    public function detailReturn($id)
    {
        $seatModel = new Seat();
        $bookedSeatModel = new BookedSeat();
        $data['payments'] = $this->db->table('payments a')
            ->select('a.*, 
                 d.fullname, 
                 c.boat_name AS boat_departure_name, 
                 f.boat_name AS boat_return_name,
                 b.date AS date_departure, 
                 e.date AS date_return')
            ->join('schedule b', 'b.id = a.schedule_departure_id AND b.type = "DEPARTURE"', 'left')
            ->join('schedule e', 'e.id = a.schedule_return_id AND e.type = "RETURN"', 'left')
            ->join('users d', 'd.id = a.user_id', 'left')
            ->join('boat c', 'c.id = b.boat_id', 'left') // Boat for departure
            ->join('boat f', 'f.id = e.boat_id', 'left') // Boat for return
            ->where('a.id', $id)
            ->get()
            ->getResult();

        $schedule_id = $data['payments'][0]->schedule_return_id;

        $data['booked_seats'] = $bookedSeatModel->where('schedule_return_id', $schedule_id)->findAll();

        $data['getSchedule'] = $this->db->table('schedule')
            ->select('*')
            ->where('id', $schedule_id)
            ->get()
            ->getResult();

        $boat_id = $data['getSchedule'][0]->boat_id;
        if ($boat_id) {
            // Ambil data kursi berdasarkan boat_id
            $data['seats'] = $this->db->table('seat')
                ->select('seat.*, boat.boat_name AS boat_name') // Pilih semua kolom dari seat dan nama boat
                ->join('boat', 'boat.id = seat.boat_id') // Join dengan tabel boat
                ->where('seat.boat_id', $boat_id) // Gunakan boat_id yang didapat dari payments
                ->get()
                ->getResult();
            $data['boat_name'] = $data['seats'][0]->boat_name;
        } else {
            // Jika boat_id tidak ditemukan pada payments
            $data['seats'] = [];
        }

        return view('admin/detail-return', $data);
    }

    // In your controller (e.g., AdminController)
    public function getAvailableSeats($boatId)
    {
        // Assuming you have a 'SeatsModel' and a method to get available seats
        $seatsModel = new Seat();

        // Fetch seats for the selected boat ID
        $seats = $seatsModel->getSeatsByBoat($boatId);

        // Check availability for each seat
        $availableSeats = [];
        foreach ($seats as $seat) {
            $isAvailable = $seat['is_available'] == 0;  // assuming 'is_booked' is 0 for available and 1 for booked
            $availableSeats[] = [
                'seat_number' => $seat['seat_number'],
                'is_available' => $isAvailable,
                'id' => $seat['id'],
            ];
        }

        // Return the data as JSON
        return $this->response->setJSON($availableSeats);
    }



    public function insertBookedSeatsx()
    {
        $request = service('request');
        $data = $request->getJSON();

        // Validasi input
        if (!isset($data->payment_id) || !isset($data->seat_ids)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid input data.',
                'data' => null
            ]);
        }

        $paymentId = $data->payment_id;
        $seatIds = $data->seat_ids;
        $bookedAt = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d'); // Format tanggal untuk validasi

        $db = \Config\Database::connect();
        $bookedSeatsTable = $db->table('booked_seats');

        try {
            $db->transStart(); // Mulai transaksi

            foreach ($seatIds as $seatId) {
                // Periksa apakah kursi sudah dipesan pada tanggal yang sama
                $existing = $bookedSeatsTable->where('seat_id', $seatId)
                    ->where('DATE(booked_at)', $currentDate)
                    ->countAllResults();

                if ($existing > 0) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Seat ID $seatId is already booked on the same date.",
                        'data' => null
                    ]);
                }

                // Lakukan insert kursi jika belum dipesan
                $result = $bookedSeatsTable->insert([
                    'payment_id' => $paymentId,

                    'seat_id' => $seatId,
                    'booked_at' => $bookedAt
                ]);

                // Validasi hasil insert
                if (!$result) {
                    $db->transRollback(); // Batalkan transaksi jika gagal
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Failed to book seat ID $seatId.",
                        'data' => null
                    ]);
                }
            }

            $db->transComplete(); // Selesaikan transaksi

            if ($db->transStatus() === false) {
                $db->transRollback(); // Batalkan jika transaksi gagal
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Transaction failed.',
                    'data' => null
                ]);
            }

            // Jika sukses
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Seats booked successfully.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback(); // Pastikan rollback jika terjadi exception
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to book seats. Error: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }


    public function insertBookedSeats()
    {
        // Add CORS headers for React frontend
        $origin = $this->request->getHeaderLine('Origin');
        $allowedOrigins = ['http://localhost:5173', 'http://localhost:3000', 'http://localhost:5174'];
        
        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
        }
        
        // Handle preflight OPTIONS request
        if ($this->request->getMethod() === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        $request = service('request');
        $paymentId = $request->getPost('payment_id');  // Get payment_id from the form
        $seatIds = $request->getPost('seats');         // Get selected seat_ids from the form
        $departureId = $request->getPost('schedule_departure_id');
        $returnId = $request->getPost('schedule_return_id');
        
        log_message('info', "[insertBookedSeats] Received: paymentId=$paymentId, seatIds=" . json_encode($seatIds) . ", departureId=$departureId, returnId=$returnId");
        
        // Validate input
        if (empty($paymentId) || empty($seatIds)) {
            log_message('error', "[insertBookedSeats] Invalid input - missing paymentId or seatIds");
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid input data.',
                'data' => null
            ]);
        }

        $bookedAt = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d'); // Current date for validation

        $db = \Config\Database::connect();
        $bookedSeatsTable = $db->table('booked_seats');
        $seatsTable = $db->table('seat'); // Reference to the seats table

        try {
            $db->transStart(); // Start transaction

            foreach ($seatIds as $seatId) {
                log_message('info', "[insertBookedSeats] Processing seat $seatId for payment $paymentId on schedule $departureId");
                
                // Check if the seat is already booked on the same date
                // Note: Using raw SQL to handle DATE() function properly
                $checkSql = "SELECT COUNT(*) as cnt FROM booked_seats 
                            WHERE seat_id = ? AND DATE(booked_at) = ?";
                $result = $db->query($checkSql, [$seatId, $currentDate]);
                $existing = $result->getFirstRow('array')['cnt'] ?? 0;

                if ($existing > 0) {
                    log_message('error', "[insertBookedSeats] Seat $seatId already booked on $currentDate");
                    $db->transRollback();
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Seat ID $seatId is already booked on the same date.",
                        'data' => null
                    ]);
                }

                // Insert booked seat
                $insertData = [
                    'payment_id' => (int)$paymentId,
                    'schedule_departure_id' => (int)$departureId,
                    'seat_id' => (int)$seatId,
                    'booked_at' => $bookedAt
                ];
                
                // Only add schedule_return_id if it has a value
                if (!empty($returnId)) {
                    $insertData['schedule_return_id'] = (int)$returnId;
                } else {
                    $insertData['schedule_return_id'] = 0; // Default to 0 if not provided
                }
                
                log_message('info', "[insertBookedSeats] Insert data: " . json_encode($insertData));
                
                $result = $bookedSeatsTable->insert($insertData);
                log_message('info', "[insertBookedSeats] Insert result for seat $seatId: " . ($result ? 'success' : 'failed'));

                // Check insert result
                if (!$result) {
                    log_message('error', "[insertBookedSeats] Failed to insert seat $seatId");
                    $db->transRollback(); // Rollback transaction if insert fails
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Failed to book seat ID $seatId.",
                        'data' => null
                    ]);
                }

                // Update the seat status to 'booked' in the seats table
                $updateResult = $seatsTable->where('id', $seatId)
                    ->update(['status' => 'booked']);

                log_message('info', "[insertBookedSeats] Update seat status result for $seatId: " . ($updateResult ? 'success' : 'failed'));

                // Check if the seat status update is successful
                if (!$updateResult) {
                    log_message('error', "[insertBookedSeats] Failed to update status for seat $seatId");
                    $db->transRollback(); // Rollback transaction if update fails
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Failed to update status for seat ID $seatId.",
                        'data' => null
                    ]);
                }
            }

            $db->transComplete(); // Complete the transaction

            if ($db->transStatus() === false) {
                log_message('error', "[insertBookedSeats] Transaction failed");
                $db->transRollback(); // Rollback if the transaction fails
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Transaction failed.',
                    'data' => null
                ]);
            }

            // Pair the passenger names entered at booking time (group booking
            // feature) with the seats in the order they were just assigned,
            // so each seat's ticket shows the correct occupant's name.
            (new \App\Models\BookingPassengerModel())->assignSeatsInOrder((int) $paymentId, array_map('intval', $seatIds));

            // If success
            log_message('info', "[insertBookedSeats] SUCCESS - Booked seats for payment $paymentId: " . json_encode($seatIds));
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Seats booked and status updated successfully.',
                'data' => [
                    'payment_id' => $paymentId,
                    'booked_seats' => $seatIds
                ]
            ]);
        } catch (\Exception $e) {
            $db->transRollback(); // Rollback if an exception occurs
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to book seats. Error: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }




    public function getBookedSeats($paymentId)
    {
        // Query dengan join
        $bookedSeats = $this->bookedSeatModel
            ->select('booked_seats.id, booked_seats.payment_id, seats.seat_number')
            ->join('seats', 'seats.id = booked_seats.seat_id') // Join dengan tabel seats
            ->where('booked_seats.payment_id', $paymentId)
            ->findAll();

        return $this->response->setJSON($bookedSeats);
    }


    public function getSeatsByBoat($boat_id, $departure_schedule_id)
    {
        $seats = $this->db->table('seats')
            ->where('boat_id', $boat_id)
            ->where('departure_schedule_id', $departure_schedule_id)
            ->get()
            ->getResult();

        return $this->response->setJSON($seats);
    }

    public function chooseSeat()
    {
        $paymentModel = new Payment();
        $seatModel = new Seat();
        $bookedSeatModel = new BookedSeat(); // Assuming you have this model for booked seats

        // Fetch all payments with departure and return schedules
        $data['payments'] = $this->db->table('payments a')
            ->select('a.*, b.departure_date, c.return_date')
            ->join('departure_schedule b', 'b.id = a.departure_schedule_id', 'left')
            ->join('return_schedule c', 'c.id = a.return_schedule_id', 'left')
            ->get()
            ->getResult();

        // Fetch all seats
        $data['seats'] = $seatModel->findAll();

        // Fetch all booked seats (seats already reserved)
        $data['bookedSeats'] = $bookedSeatModel->findAll();

        // Fetch all boats (you'll use this to display boat options in the view)
        $data['boats'] = $this->db->table('boat')->select('*')->get()->getResult();

        // Process each payment to find available seats based on departure schedule
        foreach ($data['payments'] as $payment) {
            $packageScheduleId = $payment->departure_schedule_id;

            // Get the departure date for the specific schedule
            $departureDate = $this->db->table('departure_schedule')
                ->select('departure_date')
                ->where('id', $packageScheduleId)
                ->get()
                ->getRow();

            if ($departureDate) {
                // Save the departure date for each payment
                $payment->departure_date = $departureDate->departure_date;

                // Get the available seats for the payment (seats that are not booked)
                $availableSeats = $this->db->table('seats')
                    ->join('booked_seats', 'seats.id = booked_seats.seat_id', 'left') // Left join to check if seat is booked
                    ->where('booked_seats.payment_id IS NULL') // Only select available seats
                    ->get()->getResult();

                // Attach available seats to each payment
                $payment->availableSeats = $availableSeats;
            }
        }

        // Return the view with the necessary data
        return view('admin/chooseseat', $data);
    }

    public function crudboat()
    {
        $boatModel = new BoatModel();
        $data['boats'] = $boatModel->findAll();
        return view('admin/crudboat', $data);
    }

    public function createBoat()
    {
        $boatModel = new BoatModel();
        $seatModel = new Seat();

        $post = $this->request->getPost();
        $files = $this->request->getFiles();

        // Simpan data boat
        $dataBoat = [
            'boat_name' => $post['boat_name'],
            'capacity' => $post['capacity'],
        ];

        // Validasi dan upload foto
        for ($i = 1; $i <= 5; $i++) {
            if ($file = $files["photo$i"]) {
                // Cek apakah file valid dan belum dipindahkan
                if ($file->isValid() && !$file->hasMoved()) {
                    // Validasi tipe dan ukuran file
                    $validationRule = [
                        'photo' => [
                            'uploaded[photo' . $i . ']',
                            'mime_in[photo' . $i . ',image/jpg,image/jpeg,image/png,image/gif]',
                            'max_size[photo' . $i . ',2048]' // Maksimal 2MB
                        ]
                    ];

                    // Lakukan validasi
                    if ($this->validate($validationRule)) {
                        // Generate nama file unik
                        $newName = $file->getRandomName();

                        // Pindahkan file ke direktori uploads
                        $file->move(ROOTPATH . 'public/uploads/boats/', $newName);

                        // Simpan nama file ke dalam data boat
                        $dataBoat["photo$i"] = $newName;
                    } else {
                        // Jika validasi gagal, kembalikan pesan error
                        return $this->response->setJSON([
                            'status' => 'error',
                            'message' => "Invalid file for photo$i. Please check file type and size.",
                        ]);
                    }
                }
            }
        }

        try {
            // Insert data boat
            $boatModel->insert($dataBoat);
            $boatId = $boatModel->insertID(); // Dapatkan ID boat yang baru ditambahkan

            // Buat seat berdasarkan kapasitas
            $capacity = $post['capacity'];
            $prefix = '';

            if ($capacity < 20) {
                $prefix = 'A';
            } elseif ($capacity <= 50) {
                $prefix = 'B';
            } else {
                $prefix = 'C';
            }

            // Buat seat berdasarkan kapasitas
            $seatData = [];
            for ($i = 1; $i <= $capacity; $i++) {
                $seatData[] = [
                    'boat_id' => $boatId,
                    'seat_number' => $prefix . $i,
                ];
            }

            // Simpan seat dalam batch
            $seatModel->insertBatch($seatData);

            // Kirimkan respons sukses
            return $this->response->setJSON([
                'status' => 'success',
                'no' => $boatId, // Nomor urut boat baru
                'boat_name' => $dataBoat['boat_name'],
                'capacity' => $dataBoat['capacity'],
            ]);
        } catch (\Exception $e) {
            // Hapus foto yang sudah diupload jika terjadi error
            for ($i = 1; $i <= 5; $i++) {
                if (isset($dataBoat["photo$i"])) {
                    $filePath = ROOTPATH . 'public/uploads/boats/' . $dataBoat["photo$i"];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Kirimkan respons error dengan pesan detail
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to add boat and seats: ' . $e->getMessage(),
            ]);
        }
    }


    public function updateBoat()
    {
        $boatModel = new BoatModel();
        $post = $this->request->getPost();
        $files = $this->request->getFiles();

        $dataBoat = [
            'boat_name' => $post['boat_name'],
            'capacity' => $post['capacity'],
        ];

        // Get the existing boat data to delete old files if new ones are uploaded
        $existingBoat = $boatModel->find($post['boat_id']);

        // Handle photo uploads
        for ($i = 1; $i <= 5; $i++) {
            if ($file = $files["photo$i"]) {
                if ($file->isValid() && !$file->hasMoved()) {
                    // Validate file type and size
                    $validationRules = [
                        "photo$i" => [
                            'uploaded[photo' . $i . ']',
                            'mime_in[photo' . $i . ',image/jpg,image/jpeg,image/png,image/gif]',
                            'max_size[photo' . $i . ',2048]' // 2MB max
                        ]
                    ];

                    if ($this->validate($validationRules)) {
                        // Delete old file if it exists
                        if (!empty($existingBoat["photo$i"])) {
                            $oldFilePath = ROOTPATH . 'public/uploads/boats/' . $existingBoat["photo$i"];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }

                        // Upload new file
                        $newName = $file->getRandomName();
                        $file->move(ROOTPATH . 'public/uploads/boats/', $newName);
                        $dataBoat["photo$i"] = $newName;
                    }
                }
            }
        }

        try {
            $boatModel->update($post['boat_id'], $dataBoat);

            return $this->response->setJSON([
                'status' => 'success',
                'id' => $post['boat_id'],
                'boat_name' => $post['boat_name'],
                'capacity' => $post['capacity']
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to update boat: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteBoat()
    {
        $boatModel = new BoatModel();
        $id = $this->request->getPost('id');

        if ($boatModel->delete($id)) { // Assuming you have a model named newsModel
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false]);
        }
    }



    public function crudschedule()
    {
        $boatModel = new BoatModel();
        $scheduleModel = new ScheduleModel();
        $data['boats'] = $boatModel->findAll();
        $data['schedules'] = $scheduleModel->getScheduleDate();
        return view('admin/crudschedule', $data);
    }

    public function updateSchedule()
    {
        $scheduleModel = new ScheduleModel();
        $scheduleId = $this->request->getPost('id');
        $data = [
            'boat_id'   => $this->request->getPost('boat_id'),
            'type'      => $this->request->getPost('type'),
            'date'      => $this->request->getPost('date'),
            'total_pax' => $this->request->getPost('total_pax'),
        ];

        if ($scheduleModel->update($scheduleId, $data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Schedule updated successfully.'], 200);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update schedule.'], 500);
        }
    }


    public function deleteSchedule()
    {
        $scheduleModel = new ScheduleModel();
        $id = $this->request->getPost('id');

        if ($scheduleModel->delete($id)) { // Assuming you have a model named newsModel
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false]);
        }
    }


    

    public function getBoatCapacity($boat_id)
    {
        $boatModel = new BoatModel();
        $boat = $boatModel->find($boat_id); // Find the boat by ID
        return $this->response->setJSON(['capacity' => $boat['capacity']]); // Return the capacity as a JSON response
    }

    public function createSchedule()
    {
        $scheduleModel = new ScheduleModel();
        $post = $this->request->getPost();
        $dataSchedule = [
            'boat_id' => $post['boat_id'],
            'type' => $post['type'],
            'date' => $post['date'],
            'total_pax' => $post['total_pax']
        ];
        try {
            $scheduleModel->insert($dataSchedule);
            // Kirimkan respons sukses
            return $this->response->setJSON([
                'status' => 'success',
                'boat_id' => $dataSchedule['boat_id'], // Nomor urut boat baru
                'type' => $dataSchedule['type'],
                'date' => $dataSchedule['date'],
                'total_pax' => $dataSchedule['total_pax']
            ]);
        } catch (\Exception $e) {
            // Kirimkan respons error
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to add schedule.',
            ]);
        }
    }

    public function getSchedule($id)
    {
        $scheduleModel = new ScheduleModel();
        $schedule = $scheduleModel->find($id);

        if ($schedule) {
            // Format the date for datetime-local input
            $schedule['date'] = date('Y-m-d\TH:i', strtotime($schedule['date']));
            return $this->response->setJSON($schedule);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Schedule not found'
            ])->setStatusCode(404);
        }
    }




    public function printTicketsReturn($paymentId, $schedule_return_id)
    {
        // Ambil data reservasi berdasarkan ID pembayaran
        $data['payment'] = $this->db->table('payments a')
            ->select('a.*, 
                d.fullname, 
                c.boat_name AS boat_departure_name, 
                f.boat_name AS boat_return_name,
                b.date AS date_departure, 
                e.date AS date_return')
            ->join('schedule b', 'b.id = a.schedule_departure_id AND b.type = "DEPARTURE"', 'left')
            ->join('schedule e', 'e.id = a.schedule_return_id AND e.type = "RETURN"', 'left')
            ->join('users d', 'd.id = a.user_id', 'left')
            ->join('boat c', 'c.id = b.boat_id', 'left') // Boat for departure
            ->join('boat f', 'f.id = e.boat_id', 'left') // Boat for return
            ->where('a.id', $paymentId)
            ->where('a.schedule_return_id', $schedule_return_id)
            ->get()
            ->getResult();

        // Ambil data kursi yang sudah dipesan
        $bookedSeats = $this->db->table('booked_seats bs')
            ->select('bs.*, s.seat_number') // Pilih semua kolom dari bookedSeat dan seat_number dari tabel seats
            ->join('seat s', 's.id = bs.seat_id', 'left') // Join dengan tabel seats berdasarkan seat_id
            ->where('bs.payment_id', $paymentId)
            ->where('bs.schedule_return_id', $schedule_return_id)
            ->get()
            ->getResultArray();

        // Validasi jika data tidak ditemukan
        if (empty($data['payment']) || empty($bookedSeats)) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $payment = $data['payment'][0]; // Ambil data pertama dari hasil query

        // Inisialisasi FPDF
        $pdf = new FPDF('L', 'mm', [296, 100]); // Atur ukuran halaman (contoh: 150x100 mm)
        $pdf->SetAutoPageBreak(true, 10); // Atur auto-break di margin bawah
        $pdf->SetFont('Arial', '', 12);

        // Template tiket
        $templatePath = 'assets/img/ticket.png'; // Pastikan file template tersedia di lokasi ini

        // Dapatkan dimensi template
        list($imgWidth, $imgHeight) = getimagesize($templatePath); // Ukuran dalam pixel
        $dpi = 96; // Asumsikan resolusi gambar 96 DPI
        $imgWidthMm = ($imgWidth / $dpi) * 25.4; // Konversi pixel ke mm
        $imgHeightMm = ($imgHeight / $dpi) * 25.4; // Konversi pixel ke mm
        // Tambahkan font custom
        $pdf->AddFont('Urbanist', '', 'Urbanist-VariableFont_wght.json'); // Gunakan font cus
        $pdf->SetFont('Urbanist', '', 12); // Gunakan font cus
        $pdf->SetTitle('Tickets-Departure-' . $data['payment'][0]->fullname);

        $pdf->AddPage();

        // Sisipkan gambar template tiket
        $pdf->Image($templatePath, 0, 0, $imgWidthMm, $imgHeightMm); // Sesuaikan dengan ukuran gambar

        // Jarak antar elemen
        $spacing = 20; // Nilai jarak antar elemen
        $pdf->AddFont('Urbanist', 'M', 'Urbanist-VariableFont_wght.json');
        $pdf->SetFont('Urbanist', 'M', 12);

        function CellWithLetterSpacing($pdf, $x, $y, $text, $spacing, $height = 10)
        {
            $pdf->SetXY($x, $y);
            $len = strlen($text);
            for ($i = 0; $i < $len; $i++) {
                $char = $text[$i];
                // Cetak tiap karakter dengan jarak tambahan
                $pdf->Cell($pdf->GetStringWidth($char) + $spacing, $height, $char, 0, 0);
            }
        }

        // Jarak antar huruf
        $letterSpacing = 1.5;

        // Set teks full name berwarna hitam
        $pdf->SetTextColor(0, 0, 0); // hitam
        $pdf->SetXY(234, 51);
        CellWithLetterSpacing($pdf, 187, 51, 'Mr/Mrs. ' . $payment->fullname  . ' ' . $payment->jml_pax . 'PAX', $letterSpacing);

        // Set warna teks untuk bagian lainnya menjadi putih
        $pdf->SetTextColor(255, 255, 255); // putih
        $pdf->SetXY(200, 50 + $spacing);
        CellWithLetterSpacing($pdf, 200, 50 + $spacing, 'BOAT: ' . $payment->boat_departure_name, $letterSpacing);

        $pdf->SetXY(200, 55 + $spacing);
        CellWithLetterSpacing($pdf, 200, 55 + $spacing, 'PORT: ' . $payment->package_name, $letterSpacing);

        $formattedDate = date("d M Y H:i", strtotime($payment->date_departure));
        $pdf->SetXY(200, 60 + $spacing);
        CellWithLetterSpacing($pdf, 200, 60 + $spacing, 'DATE: ' . $formattedDate, $letterSpacing);

        // $pdf->SetXY(110, 10 + $spacing * 2);
        // $pdf->Cell(80, 10, 'Seat Number: ' . $seat['seat_number'], 0, 1);

        foreach ($bookedSeats as $seat) {
            // Generate QR Code
            $qrContent = 'Ticket-' . $seat['seat_number'] . '-' . $payment->id;
        }

        // Initialize Endroid QR Code
        $writer = new PngWriter();
        $qrCode = QrCode::create($qrContent)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(200)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        // Simpan QR Code sementara
        $qrFilePath = WRITEPATH . 'uploads/qr_codes/' . uniqid() . '.png';
        $result = $writer->write($qrCode);
        $result->saveToFile($qrFilePath);

        // Tambahkan QR Code ke PDF
        $pdf->Image($qrFilePath, $imgWidthMm - 244, $imgHeightMm - 46, 30, 30); // Sesuaikan posisi QR Code

        // Hapus file QR Code sementara
        unlink($qrFilePath);

        // Output PDF ke browser
        $this->response->setContentType('application/pdf');
        $pdf->Output('Tickets.pdf', 'I');
    }

    public function printTicketsDeparture($paymentId, $schedule_departure_id)
    {
        // Ambil data reservasi berdasarkan ID pembayaran
        $data['payment'] = $this->db->table('payments a')
            ->select('a.*, 
                d.fullname, 
                c.boat_name AS boat_departure_name, 
                f.boat_name AS boat_return_name,
                b.date AS date_departure, 
                e.date AS date_return')
            ->join('schedule b', 'b.id = a.schedule_departure_id AND b.type = "DEPARTURE"', 'left')
            ->join('schedule e', 'e.id = a.schedule_return_id AND e.type = "RETURN"', 'left')
            ->join('users d', 'd.id = a.user_id', 'left')
            ->join('boat c', 'c.id = b.boat_id', 'left') // Boat for departure
            ->join('boat f', 'f.id = e.boat_id', 'left') // Boat for return
            ->where('a.id', $paymentId)
            ->where('a.schedule_departure_id', $schedule_departure_id)
            ->get()
            ->getResult();

        // Ambil data kursi yang sudah dipesan
        $bookedSeats = $this->db->table('booked_seats bs')
            ->select('bs.*, s.seat_number') // Pilih semua kolom dari bookedSeat dan seat_number dari tabel seats
            ->join('seat s', 's.id = bs.seat_id', 'left') // Join dengan tabel seats berdasarkan seat_id
            ->where('bs.payment_id', $paymentId)
            ->where('bs.schedule_departure_id', $schedule_departure_id)
            ->get()
            ->getResultArray();

        // Validasi jika data tidak ditemukan
        if (empty($data['payment']) || empty($bookedSeats)) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $payment = $data['payment'][0]; // Ambil data pertama dari hasil query

        // Inisialisasi FPDF
        $pdf = new FPDF('L', 'mm', [296, 100]); // Atur ukuran halaman (contoh: 150x100 mm)
        $pdf->SetAutoPageBreak(true, 10); // Atur auto-break di margin bawah
        $pdf->SetFont('Arial', '', 12);

        // Template tiket
        $templatePath = 'assets/img/ticket.png'; // Pastikan file template tersedia di lokasi ini

        // Dapatkan dimensi template
        list($imgWidth, $imgHeight) = getimagesize($templatePath); // Ukuran dalam pixel
        $dpi = 96; // Asumsikan resolusi gambar 96 DPI
        $imgWidthMm = ($imgWidth / $dpi) * 25.4; // Konversi pixel ke mm
        $imgHeightMm = ($imgHeight / $dpi) * 25.4; // Konversi pixel ke mm
        // Tambahkan font custom
        $pdf->AddFont('Urbanist', '', 'Urbanist-VariableFont_wght.json'); // Gunakan font cus
        $pdf->SetFont('Urbanist', '', 12); // Gunakan font cus
        $pdf->SetTitle('Tickets-Departure-' . $data['payment'][0]->fullname);

        $pdf->AddPage();

        // Sisipkan gambar template tiket
        $pdf->Image($templatePath, 0, 0, $imgWidthMm, $imgHeightMm); // Sesuaikan dengan ukuran gambar

        // Jarak antar elemen
        $spacing = 20; // Nilai jarak antar elemen
        $pdf->AddFont('Urbanist', 'M', 'Urbanist-VariableFont_wght.json');
        $pdf->SetFont('Urbanist', 'M', 12);

        function CellWithLetterSpacing($pdf, $x, $y, $text, $spacing, $height = 10)
        {
            $pdf->SetXY($x, $y);
            $len = strlen($text);
            for ($i = 0; $i < $len; $i++) {
                $char = $text[$i];
                // Cetak tiap karakter dengan jarak tambahan
                $pdf->Cell($pdf->GetStringWidth($char) + $spacing, $height, $char, 0, 0);
            }
        }

        // Jarak antar huruf
        $letterSpacing = 1.5;

        // Set teks full name berwarna hitam
        $pdf->SetTextColor(0, 0, 0); // hitam
        $pdf->SetXY(234, 51);
        CellWithLetterSpacing($pdf, 187, 51, 'Mr/Mrs. ' . $payment->fullname  . ' ' . $payment->jml_pax . 'PAX', $letterSpacing);

        // Set warna teks untuk bagian lainnya menjadi putih
        $pdf->SetTextColor(255, 255, 255); // putih
        $pdf->SetXY(200, 50 + $spacing);
        CellWithLetterSpacing($pdf, 200, 50 + $spacing, 'BOAT: ' . $payment->boat_departure_name, $letterSpacing);

        $pdf->SetXY(200, 55 + $spacing);
        CellWithLetterSpacing($pdf, 200, 55 + $spacing, 'PORT: ' . $payment->package_name, $letterSpacing);

        $formattedDate = date("d M Y H:i", strtotime($payment->date_departure));
        $pdf->SetXY(200, 60 + $spacing);
        CellWithLetterSpacing($pdf, 200, 60 + $spacing, 'DATE: ' . $formattedDate, $letterSpacing);

        // $pdf->SetXY(110, 10 + $spacing * 2);
        // $pdf->Cell(80, 10, 'Seat Number: ' . $seat['seat_number'], 0, 1);

        foreach ($bookedSeats as $seat) {
            // Generate QR Code
            $qrContent = 'Ticket-' . $seat['seat_number'] . '-' . $payment->id;
        }

        // Initialize Endroid QR Code
        $writer = new PngWriter();
        $qrCode = QrCode::create($qrContent)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(200)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        // Simpan QR Code sementara
        $qrFilePath = WRITEPATH . 'uploads/qr_codes/' . uniqid() . '.png';
        $result = $writer->write($qrCode);
        $result->saveToFile($qrFilePath);

        // Tambahkan QR Code ke PDF
        $pdf->Image($qrFilePath, $imgWidthMm - 244, $imgHeightMm - 46, 30, 30); // Sesuaikan posisi QR Code

        // Hapus file QR Code sementara
        unlink($qrFilePath);

        // Output PDF ke browser
        $this->response->setContentType('application/pdf');
        $pdf->Output('Tickets.pdf', 'I');
    }



    /**
     * Generate Boarding Pass PDF using FPDF (same approach as generateTicketPDF).
     * GET /admin/boarding-pass-pdf/(:num)
     *
     * Draws ONE unified boarding pass card per assigned seat (no split
     * "two floating cards" like the HTML/print-window version) — a single
     * bordered card with an orange header, info fields, and a QR code
     * for check-in scanning.
     */
 public function printBoardingPassPdf($bookingId = 0)
{
    try {
        $db = \Config\Database::connect();
 
        $query = $db->query("
            SELECT
                p.id,
                p.group_name,
                u.fullname as user_name,
                p.jml_pax,
                p.package_name,
                p.qr_code,
                b.date as date_departure,
                c.boat_name as boat_departure_name,
                GROUP_CONCAT(s.id ORDER BY s.seat_number) as seat_ids,
                GROUP_CONCAT(s.seat_number ORDER BY s.seat_number) as seat_numbers,
                GROUP_CONCAT(bp.name ORDER BY s.seat_number SEPARATOR '|') as passenger_names
            FROM payments p
            LEFT JOIN users u ON u.id = p.user_id
            LEFT JOIN schedule b ON b.id = p.schedule_departure_id AND b.type = 'DEPARTURE'
            LEFT JOIN boat c ON c.id = b.boat_id
            LEFT JOIN booked_seats bs ON bs.payment_id = p.id
            LEFT JOIN seat s ON s.id = bs.seat_id
            LEFT JOIN booking_passengers bp ON bp.seat_id = s.id AND bp.payment_id = p.id
            WHERE p.id = ?
            GROUP BY p.id
        ", [$bookingId]);
 
        $result = $query->getRow();
 
        if (!$result) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Booking not found',
            ]);
        }
 
        // "Group" = whoever made the booking. "Name" (per seat) = the actual
        // occupant of that seat, entered by the group leader at checkout.
        $groupName = $result->group_name ?: $result->user_name;
 
        $seats = [];
        if ($result->seat_ids) {
            $seatNumbers = explode(',', $result->seat_numbers);
            foreach ($seatNumbers as $num) {
                $seats[] = $num;
            }
        } else {
            $seats = ['-'];
        }
 
        // Align passenger names 1:1 with $seats; fall back to the group
        // name for any seat that doesn't have a stored passenger name.
        $rawPassengerNames = $result->passenger_names !== null
            ? explode('|', $result->passenger_names)
            : [];
        $passengerNamesAligned = count($rawPassengerNames) === count($seats);
        $passengerNames = [];
        foreach ($seats as $i => $seatNum) {
            $passengerNames[] = $passengerNamesAligned ? $rawPassengerNames[$i] : $groupName;
        }
 
        $boatName = $result->boat_departure_name ?: 'NAMA KAPAL';
 
        // ── Generate QR code lokal (payload sama seperti yang dipakai scanner) ──
        $qrDir = WRITEPATH . 'uploads/qr_codes/';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0775, true);
        }
 
        $qrContent = 'NAMA_MARINE_TICKET_' . $result->id;
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $qrCode = \Endroid\QrCode\QrCode::create($qrContent)
            ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->setSize(300)
            ->setMargin(8)
            ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
            ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
 
        $qrFilePath = $qrDir . uniqid('bp_') . '.png';
        $writer->write($qrCode)->saveToFile($qrFilePath);
 
        // ── Bangun PDF (1 halaman per kursi) ──
        $pdf = new BoardingPassPDF('L', 'mm', [210, 95]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle('Boarding-Pass-' . $result->id);
 
        $formattedDate = $result->date_departure
            ? strtoupper(date('d F Y', strtotime($result->date_departure)))
            : 'N/A';
        $boardingTime = $result->date_departure
            ? date('H:i', strtotime($result->date_departure))
            : 'N/A';
 
        foreach ($seats as $seatIndex => $seatNumber) {
            $passengerName = $passengerNames[$seatIndex] ?? $groupName;
            $pdf->AddPage();
 
            // ===== Ukuran dasar kartu (SATU kartu menyatu, tidak terpisah) =====
            $marginX = 6;
            $marginY = 6;
            $cardX = $marginX;
            $cardY = $marginY;
            $cardW = 210 - ($marginX * 2);   // 198
            $cardH = 95 - ($marginY * 2);    // 83
 
            $mainCardW = $cardW;              // kartu utama = full width kartu
            $vStripW   = 9;                   // lebar strip vertikal nama kapal
            $perfX     = $cardX + 130;         // posisi garis titik-titik (pemisah stub)
 
            $orange = [242, 136, 28];
            $borderGray = [220, 222, 226];
            $labelGray  = [150, 155, 160];
 
            // ======================================================
            // KARTU (border & header tunggal, menyatu penuh)
            // ======================================================
            $pdf->SetDrawColor(...$borderGray);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect($cardX, $cardY, $cardW, $cardH);
 
            $headerHMain = 15;
            $pdf->SetFillColor(...$orange);
            $pdf->Rect($cardX, $cardY, $cardW, $headerHMain, 'F');
 
            // Ikon yacht (auto: pakai PNG kalau ada, kalau tidak pakai vector) + judul
            $pdf->YachtIconAuto($cardX + 4, $cardY + 2.5, 11, 10);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetXY($cardX + 17, $cardY + 4);
            $pdf->Cell($cardW - 20, 7, 'Boarding Pass', 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
 
            $bodyTopMain = $cardY + $headerHMain;
 
            // Garis titik-titik pemisah antara boarding pass utama & stub
            $pdf->SetFillColor(...$borderGray);
            $pdf->DottedLine($perfX, $bodyTopMain + 2, $perfX, $cardY + $cardH - 2);
 
            // --- Strip vertikal: nama kapal (rotated) ---
            $pdf->SetDrawColor(...$borderGray);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($cardX + $vStripW, $bodyTopMain, $cardX + $vStripW, $cardY + $cardH);
 
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(60, 60, 60);
            $stripCenterY = $bodyTopMain + (($cardY + $cardH) - $bodyTopMain) / 2;
            $pdf->RotatedText($cardX + $vStripW - 2.5, $stripCenterY + 5, strtoupper($boatName), 90);
            $pdf->SetTextColor(0, 0, 0);
 
            // --- Kolom isi kartu utama ---
            $contentX = $cardX + $vStripW + 4;
            $col1X = $contentX;
            $col2X = $col1X + 42;
            $qrSize = 26;
            $qrX = $perfX - $qrSize - 5;
 
            $labelSizeMain = 6.5;
            $valueSizeMain = 9.5;
            $colWMain = 40;
 
            $fieldMain = function ($x, $y, $label, $value, $valueColor = [26, 26, 26]) use ($pdf, $labelSizeMain, $valueSizeMain, $colWMain) {
                $pdf->SetFont('Arial', '', $labelSizeMain);
                $pdf->SetTextColor(...[150, 155, 160]);
                $pdf->SetXY($x, $y);
                $pdf->Cell($colWMain, 3.5, strtoupper($label), 0, 2);
 
                $pdf->SetFont('Arial', 'B', $valueSizeMain);
                [$r, $g, $b] = $valueColor;
                $pdf->SetTextColor($r, $g, $b);
                $pdf->SetXY($x, $y + 4);
                $pdf->Cell($colWMain, 5.5, $value, 0, 2);
                $pdf->SetTextColor(0, 0, 0);
            };
 
            $y = $bodyTopMain + 7;
            $fieldMain($col1X, $y, 'Group', $groupName); $y += 13.5;
            $fieldMain($col1X, $y, 'Name', $passengerName); $y += 13.5;
            $fieldMain($col1X, $y, 'Boarding Time', $boardingTime); $y += 13.5;
 
            $pdf->SetFont('Arial', '', $labelSizeMain);
            $pdf->SetTextColor(...$labelGray);
            $pdf->SetXY($col1X, $y);
            $pdf->Cell(20, 3.5, 'TOTAL PAX', 0, 0);
            $pdf->SetXY($col1X + 20, $y);
            $pdf->Cell(20, 3.5, 'SEAT NO.', 0, 0);
 
            $pdf->SetFont('Arial', 'B', $valueSizeMain);
            $pdf->SetTextColor(26, 26, 26);
            $pdf->SetXY($col1X, $y + 4);
            $pdf->Cell(20, 5.5, (string) $result->jml_pax, 0, 0);
            $pdf->SetTextColor(...$orange);
            $pdf->SetXY($col1X + 20, $y + 4);
            $pdf->Cell(20, 5.5, (string) $seatNumber, 0, 0);
            $pdf->SetTextColor(0, 0, 0);
 
            $y = $bodyTopMain + 7;
            $fieldMain($col2X, $y, 'Date', $formattedDate); $y += 13.5;
            $fieldMain($col2X, $y, 'From', 'Baywalk'); $y += 13.5;
            $fieldMain($col2X, $y, 'To', $result->package_name ?: 'N/A'); $y += 13.5;
            $fieldMain($col2X, $y, 'Boat', $result->boat_departure_name ?: 'N/A');
 
            // --- QR code kartu utama ---
            $pdf->Image($qrFilePath, $qrX, $bodyTopMain + 4, $qrSize, $qrSize, 'PNG');
            $pdf->SetFont('Arial', 'B', 6.5);
            $pdf->SetTextColor(...$labelGray);
            $pdf->SetXY($qrX, $bodyTopMain + 4 + $qrSize + 2);
            $pdf->Cell($qrSize, 3.5, 'SCAN TO CHECK-IN', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
 
            // ======================================================
            // STUB (kanan) - sobekan untuk check-in, MENYATU dengan
            // kartu utama, hanya dipisahkan garis titik-titik di atas.
            // ======================================================
            $stubCardX = $perfX + 4;
            $stubCardW = ($cardX + $cardW) - $stubCardX - 4;
 
            $bodyTopStub = $bodyTopMain + 4;
            $stubPadX = 0;
            $stubColW = ($stubCardW - $stubPadX * 2 - 3) / 2;
 
            $labelSizeStub = 5.6;
            $valueSizeStub = 7.6;
 
            $fieldStub = function ($x, $y, $w, $label, $value, $valueColor = [26, 26, 26]) use ($pdf, $labelSizeStub, $valueSizeStub, $labelGray) {
                $pdf->SetFont('Arial', '', $labelSizeStub);
                $pdf->SetTextColor(...$labelGray);
                $pdf->SetXY($x, $y);
                $pdf->Cell($w, 3, strtoupper($label), 0, 2);
 
                $pdf->SetFont('Arial', 'B', $valueSizeStub);
                [$r, $g, $b] = $valueColor;
                $pdf->SetTextColor($r, $g, $b);
                $pdf->SetXY($x, $y + 3.4);
                $pdf->Cell($w, 4.5, $value, 0, 2);
                $pdf->SetTextColor(0, 0, 0);
            };
 
            $sy = $bodyTopStub + 5;
            $fieldStub($stubCardX + $stubPadX, $sy, $stubCardW - $stubPadX * 2, 'Group', $groupName);
            $sy += 10.5;
            $fieldStub($stubCardX + $stubPadX, $sy, $stubCardW - $stubPadX * 2, 'Passenger', $passengerName);
            $sy += 10.5;
 
            $fieldStub($stubCardX + $stubPadX, $sy, $stubColW, 'Boarding Time', $boardingTime);
            $fieldStub($stubCardX + $stubPadX + $stubColW + 3, $sy, $stubColW, 'Total Pax', (string) $result->jml_pax);
            $sy += 10.5;
 
            $fieldStub($stubCardX + $stubPadX, $sy, $stubColW, 'From', 'Baywalk');
            $fieldStub($stubCardX + $stubPadX + $stubColW + 3, $sy, $stubColW, 'To', $result->package_name ?: 'N/A');
            $sy += 10.5;
 
            $fieldStub($stubCardX + $stubPadX, $sy, $stubColW, 'Boat', $result->boat_departure_name ?: 'N/A');
            $fieldStub($stubCardX + $stubPadX + $stubColW + 3, $sy, $stubColW, 'Seat', (string) $seatNumber, $orange);
        }
 
        @unlink($qrFilePath);
 
        $this->response->setContentType('application/pdf');
        $pdf->Output('I', 'boarding-pass-' . $bookingId . '.pdf');
    } catch (\Exception $e) {
        return $this->response->setStatusCode(500)->setJSON([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}
    public function getTicketData($bookingId)
{
    // Set CORS headers
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    
    $db = \Config\Database::connect();

    // Query matching MVC pattern - joins payments → users, schedule, boat
    $query = $db->query("
        SELECT
            p.id,
            p.group_name,
            u.fullname as user_name,
            u.email,
            p.jml_pax,
            CAST(COALESCE(NULLIF(p.amount, ''), 0) AS DECIMAL(10,2)) as amount,
            p.package_name,
            p.qr_code,
            b.date as date_departure,
            e.date as date_return,
            c.boat_name as boat_departure_name,
            f.boat_name as boat_return_name,
            GROUP_CONCAT(s.id ORDER BY s.seat_number) as seat_ids,
            GROUP_CONCAT(s.seat_number ORDER BY s.seat_number) as seat_numbers,
            GROUP_CONCAT(bp.name ORDER BY s.seat_number SEPARATOR '|') as passenger_names
        FROM payments p
        LEFT JOIN users u ON u.id = p.user_id
        LEFT JOIN schedule b ON b.id = p.schedule_departure_id AND b.type = 'DEPARTURE'
        LEFT JOIN schedule e ON e.id = p.schedule_return_id AND e.type = 'RETURN'
        LEFT JOIN boat c ON c.id = b.boat_id
        LEFT JOIN boat f ON f.id = e.boat_id
        LEFT JOIN booked_seats bs ON bs.payment_id = p.id
        LEFT JOIN seat s ON s.id = bs.seat_id
        LEFT JOIN booking_passengers bp ON bp.seat_id = s.id AND bp.payment_id = p.id
        WHERE p.id = ?
        GROUP BY p.id
    ", [$bookingId]);

    $result = $query->getRow();

    if (!$result) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Booking not found'
        ])->setStatusCode(404);
    }

    // "Group" = the person who made the booking (falls back to the account
    // fullname if no custom group name was set at checkout).
    $groupName = $result->group_name ?: $result->user_name;

    $seats = [];
    if ($result->seat_ids) {
        $seatIds = explode(',', $result->seat_ids);
        $seatNumbers = explode(',', $result->seat_numbers);
        // NULLs from GROUP_CONCAT collapse the '|' separators away, so this
        // only lines up 1:1 with seatIds when every seat has a passenger
        // name. Fall back to the group name per-seat when it doesn't.
        $passengerNames = $result->passenger_names !== null
            ? explode('|', $result->passenger_names)
            : [];
        $namesAligned = count($passengerNames) === count($seatIds);
        foreach ($seatIds as $i => $id) {
            $seats[] = [
                'id' => (int)$id,
                'seat_number' => $seatNumbers[$i],
                'passenger_name' => $namesAligned ? $passengerNames[$i] : $groupName,
            ];
        }
    }

    // Generate QR code if not already exists
    $qrCode = $result->qr_code;
    if (!$qrCode) {
        // Create QR code data: booking ID
        $qrData = "NAMA_MARINE_TICKET_{$result->id}";
        
        // Generate QR code using Google Charts API (simple, reliable)
        $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData);
        
        // Save to database for caching
        $db->table('payments')->update(['qr_code' => $qrCode], ['id' => $result->id]);
    }

    $allPassengers = (new \App\Models\BookingPassengerModel())->getNamesForBooking((int) $result->id);
    $seatNumberById = array_column($seats, 'seat_number', 'id');
    $allPassengerNames = array_column($allPassengers, 'name');
    // Clean manifest-ready list: name + nik + which seat (if assigned yet).
    $manifestPassengers = array_map(function ($p) use ($seatNumberById) {
        return [
            'name'        => $p['name'],
            'nik'         => $p['nik'] ?? null,
            'seat_number' => $p['seat_id'] ? ($seatNumberById[(int) $p['seat_id']] ?? null) : null,
        ];
    }, $allPassengers);

    return $this->response->setJSON([
        'status' => 'success',
        'data' => [
            'id' => $result->id,
            'group_name' => $groupName,
            'user_name' => $result->user_name,
            'email' => $result->email,
            'jml_pax' => $result->jml_pax,
            'amount' => $result->amount,
            'package_name' => $result->package_name,
            'date_departure' => $result->date_departure,
            'date_return' => $result->date_return,
            'boat_departure_name' => $result->boat_departure_name,
            'boat_return_name' => $result->boat_return_name,
            'qr_code' => $qrCode,
            'seats' => $seats,
            'passenger_names' => $allPassengerNames,
            'passengers' => $manifestPassengers,
        ]
    ]);
}

/**
 * Check-in endpoint: Update attendance field in payments table
 * Expects: POST /admin/checkin with booking_id parameter
 * Returns: { status: "success", message: "Checked in" }
 */
public function checkin()
{
    // Set CORS headers
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    
    if ($this->request->getMethod() === 'OPTIONS') {
        return $this->response->setStatusCode(200);
    }

    $bookingId = null;
    
    // Get booking_id - try POST first, then JSON body
    $bookingId = $this->request->getVar('booking_id');
    
    if (!$bookingId && $this->request->getContentType() === 'application/json') {
        $json = $this->request->getJSON(true); // Get as array
        if ($json) {
            $bookingId = $json['booking_id'] ?? null;
        }
    }
    
    if (!$bookingId) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Booking ID required'
        ])->setStatusCode(400);
    }

    try {
        $db = \Config\Database::connect();
        
        // Update attendance field with check-in timestamp
        $checkinTime = date('Y-m-d H:i:s');
        $result = $db->table('payments')->update(
            ['attendance' => $checkinTime],
            ['id' => $bookingId]
        );

        if ($result === false) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to update attendance'
            ])->setStatusCode(400);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Checked in successfully',
            'checkin_time' => $checkinTime
        ]);

    } catch (\Exception $e) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}

}
