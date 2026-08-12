<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Detail Reservation Departure</h1>

    <div class="row">
        <?php foreach ($payments as $pay) : ?>
            <!-- Card 1: User (Full Name) -->
            <div class="col-md-4 mb-4">
                <div class="card bg-gradient-to-r from-purple-500 via-pink-500 to-red-500">
                    <div class="card-body">
                        <div class="text-muted small">Customer Name's</div> <!-- Teks kecil di atas -->
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user mr-2"></i>
                            <span><?= $pay->fullname; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Package Name -->
            <div class="col-md-4 mb-4">
                <div class="card bg-gradient-to-r from-green-400 via-blue-500 to-teal-500">
                    <div class="card-body">
                        <div class="text-muted small">Route</div> <!-- Teks kecil di atas -->
                        <div class="d-flex align-items-center">
                            <i class="fas fa-route mr-2"></i>
                            <span><?= $pay->package_name; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Pax -->
            <div class="col-md-4 mb-4">
                <div class="card bg-gradient-to-r from-yellow-500 via-orange-500 to-red-600">
                    <div class="card-body">
                        <div class="text-muted small">Total Pax</div> <!-- Teks kecil di atas -->
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users mr-2"></i>
                            <span><?= $pay->jml_pax; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Departure Date -->
            <div class="col-md-4 mb-4">
                <div class="card bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-700">
                    <div class="card-body">
                        <div class="text-muted small">Departure Date</div> <!-- Teks kecil di atas -->
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span><?= $pay->date_departure; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 5: Return Date -->
            <div class="col-md-4 mb-4">
                <div class="card bg-gradient-to-r from-pink-400 via-purple-500 to-indigo-600">
                    <div class="card-body">
                        <div class="text-muted small">Return Date</div> <!-- Teks kecil di atas -->
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-check mr-2"></i>
                            <span><?= empty($pay->date_return) ? '-' : $pay->date_return; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 6: Departure Schedule ID -->
            <div class="col-md-4 mb-4">
                <div class="card bg-gradient-to-r from-pink-400 via-purple-500 to-indigo-600">
                    <div class="card-body">
                        <div class="text-muted small">Boat's Name</div> <!-- Teks kecil di atas -->
                        <div class="d-flex align-items-center">
                            <i class="fas fa-ship mr-2"></i>
                            <span><?= empty($boat_name) ? '-' : $boat_name; ?></span>
                        </div>
                    </div>
                </div>
            </div>
    </div>
<?php endforeach; ?>
</div>

</div>
<div class="container">
    <div class="card mb-2">
        <div class="card-body">
            <div class="container-in mb-2">
                <div class="ship-container">
                    <div class="d-flex flex-column align-items-center">
                        <!-- Driver seat -->
                        <div class="up-down">
                            <!-- <div class="seat occupied float-end">Driver</div> -->
                        </div>

                        <!-- Seat layout -->
                        <form id="seatForm" action="<?= base_url('insert-bookedseats'); ?>" method="post">
                            <input type="hidden" name="payment_id" value="<?= $pay->id; ?>" />
                            <input type="hidden" name="schedule_departure_id" value="<?= $pay->schedule_departure_id; ?>" />
                            <input type="hidden" name="schedule_return_id" value="<?= $pay->schedule_return_id; ?>" />
                            <?php
                            $columns = count($seats) <= 20 ? 4 : 6;
                            $rows = array_chunk($seats, $columns);

                            foreach ($rows as $index => $row) : ?>
                                <div class="d-flex align-items-center">
                                    <?php foreach ($row as $key => $s) :
                                        // Cek apakah kursi sudah dipesan oleh ID pembayaran saat ini
                                        $bookedSeat = null;
                                        foreach ($booked_seats as $bs) {
                                            if ($bs['seat_id'] == $s->id) {
                                                $bookedSeat = $bs;
                                                break;
                                            }
                                        }

                                        // Tentukan warna kursi berdasarkan status pemesanan
                                        $seatColorClass = '';
                                        $seatDisabled = '';  // Default, kursi tidak dinonaktifkan

                                        // Jika sudah ada pemesanan pada pembayaran saat ini
                                        if ($bookedSeat) {
                                            if ($bookedSeat['payment_id'] == $pay->id) {
                                                // Kursi sudah dipesan oleh pengguna saat ini
                                                $seatColorClass = 'seat-booked-current';  // Biru untuk pengguna saat ini
                                                $seatDisabled = 'disabled';  // Nonaktifkan checkbox untuk pengguna ini
                                            } else {
                                                // Kursi sudah dipesan oleh pengguna lain
                                                $seatColorClass = 'seat-booked-other';  // Merah untuk pengguna lain
                                                $seatDisabled = 'disabled';  // Nonaktifkan checkbox untuk pengguna lain
                                            }
                                        } else {
                                            // Jika kursi tersedia
                                            $seatColorClass = 'seat-available';  // Kursi tersedia


                                        }


                                    ?>
                                        <label class="seat-label">
                                            <input type="checkbox" name="seats[]" value="<?= $s->id; ?>" class="seat-checkbox" <?= $seatDisabled; ?>>
                                            <div class="seat <?= $seatColorClass; ?>"><?= $s->seat_number; ?></div>
                                        </label>

                                        <!-- Divider logic -->
                                        <?php if (($columns == 4 && $key == 1) || ($columns == 6 && $key == 2)) : ?>
                                            <div class="vertical-divider mx-2">
                                                <hr />
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php
                            // Cek apakah pengguna sudah melakukan pemesanan
                            $isBookedByCurrentPayment = false;
                            foreach ($booked_seats as $bs) {
                                if ($bs['schedule_departure_id'] == $pay->schedule_departure_id && $bs['payment_id'] == $pay->id) {
                                    $isBookedByCurrentPayment = true;
                                    break;
                                }
                            }

                            // Cek apakah status pembayaran PENDING
                            $isPending = $pay->status === 'PENDING';
                            ?>

                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <a href="<?= base_url('print-tickets-departure/' . $pay->id . '/' . $pay->schedule_departure_id); ?>" 
                    class="btn btn-success">
                    Print Tickets
                </a>
            
                <!-- Nonaktifkan tombol jika pengguna sudah memesan atau jika status PENDING -->
                <button type="submit" class="btn btn-primary" id="bookSeatsBtn"
                    <?= ($isBookedByCurrentPayment || $isPending) ? 'disabled' : ''; ?>>
                    Book Selected Seats
                </button>
            </div>
            </form>
        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const seatCheckboxes = document.querySelectorAll('.seat-checkbox');
            const paxCount = <?= $pay->jml_pax; ?>; // Jumlah pax
            const bookSeatsBtn = document.getElementById('bookSeatsBtn');
            const selectedCountDisplay = document.createElement('div');
            selectedCountDisplay.id = 'selectedCountDisplay';
            bookSeatsBtn.before(selectedCountDisplay);

            const isBookedByCurrentPayment = <?= json_encode($isBookedByCurrentPayment); ?>;
            const isPending = <?= json_encode($isPending); ?>;

            function updateSeatSelection() {
                if (isPending || isBookedByCurrentPayment) {
                    bookSeatsBtn.disabled = true;
                    bookSeatsBtn.textContent = isPending ? 'Booking Pending' : 'Seats Already Booked';

                    // **Nonaktifkan semua checkbox jika kursi sudah dibooking oleh payment saat ini**
                    seatCheckboxes.forEach(checkbox => {
                        checkbox.disabled = true;
                    });

                    return;
                }

                const selectedSeats = document.querySelectorAll('.seat-checkbox:checked');
                const selectedCount = selectedSeats.length;

                // Tampilkan jumlah kursi yang dipilih
                selectedCountDisplay.textContent = `Selected Seats: ${selectedCount}/${paxCount}`;

                // Nonaktifkan checkbox tambahan jika jumlah kursi sudah cukup
                seatCheckboxes.forEach(checkbox => {
                    if (selectedCount >= paxCount && !checkbox.checked) {
                        checkbox.disabled = true;
                    } else if (!checkbox.closest('.seat-label').classList.contains('seat-booked-other') &&
                        !checkbox.closest('.seat-label').classList.contains('seat-booked-current')) {
                        checkbox.disabled = false;
                    }
                });

                // Tombol submit dinonaktifkan jika 0 kursi dipilih atau melebihi batas
                bookSeatsBtn.disabled = selectedCount === 0 || selectedCount > paxCount || isBookedByCurrentPayment === true || isPending === true;
                bookSeatsBtn.textContent = selectedCount > paxCount ?
                    `Max ${paxCount} seats can be selected` :
                    'Book Selected Seats';
            }

            // Event listener untuk checkbox kursi
            seatCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSeatSelection);
            });

            // Handling form submit dengan SweetAlert
            // Replace your current form submit handler with this
            document.getElementById('seatForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const selectedSeats = document.querySelectorAll('.seat-checkbox:checked');

                if (selectedSeats.length > paxCount) {
                    Swal.fire({
                        title: 'Warning!',
                        text: `You can only select up to ${paxCount} seats.`,
                        icon: 'warning',
                        confirmButtonText: 'Okay'
                    });
                } else {
                    Swal.fire({
                        title: 'Confirm Booking?',
                        text: 'Do you want to proceed with the selected seats?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Confirm!',
                        cancelButtonText: 'Cancel'
                    }).then(result => {
                        if (result.isConfirmed) {
                            // Use AJAX to submit the form
                            const formData = new FormData(this);

                            fetch(this.action, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire({
                                            title: 'Success!',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            // Optional: reload or redirect
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message,
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                })
                                .catch(error => {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An unexpected error occurred: ' + error,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                });
                        }
                    });
                }
            });

            // **Inisialisasi awal**
            updateSeatSelection();
        });
    </script>


    <?= $this->include('admin_layout/footer'); ?>