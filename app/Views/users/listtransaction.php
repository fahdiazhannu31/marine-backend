<?= $this->extend('users_layout/page_layout'); ?>
<?= $this->section('content'); ?>
<!-- Start Hero Section -->
<div class="mt-5"></div>
<!-- End Hero Section -->
<?php if (session('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>
<!-- content -->
<section class="py-5">
    <div class="container">
        <div class="card">
            <div class="card-body">
                <div class="card-title">Departure History</div>
                <!-- content -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pay Now</th>
                                <th>Status</th>
                                <th>Total Pax</th>
                                <th>Package</th>
                                <th>Date</th>
                                <th>QR Code</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($payments as $pay):
                                // Initialize empty array for seats
                                $availableSeats = [];
                                $isBooked = false;

                                // Fetch available seats based on boat ID
                                foreach ($seats as $seat) {
                                    if ($seat['boat_id'] == 1 && $pay['jml_pax'] <= 20) {
                                        $availableSeats[] = $seat;
                                    } elseif ($seat['boat_id'] == 2 && $pay['jml_pax'] <= 40) {
                                        $availableSeats[] = $seat;
                                    } elseif ($seat['boat_id'] == 3 && $pay['jml_pax'] > 40) {
                                        $availableSeats[] = $seat;
                                    }
                                }

                                foreach ($bookedSeats as $booked) {
                                    if (isset($booked['payment_id']) && $booked['payment_id'] == $pay['id']) {
                                        $isBooked = true;
                                        break;
                                    }
                                }
                            ?>

                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <?php if ($pay['status'] === 'SETTLED'): ?>
                                            <span class="text-muted">Checkout</span>
                                        <?php else: ?>
                                            <?php if ($pay['checkout_link'] === NULL): ?>
                                                <a class="btn btn-danger" href="<?= base_url('manual-payment/detail/' . esc($pay['external_id'])); ?>">Checkout Manual</a>
                                            <?php else: ?>
                                                <a class="btn btn-danger" href="<?= esc($pay['checkout_link']); ?>">Checkout</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pay['status'] === 'SETTLED'): ?>
                                            <span class="text-primary font-weight-bold">SETTLED</span>
                                        <?php elseif ($pay['status'] === 'PAID'): ?>
                                            <span class="text-success font-weight-bold">PAID</span>
                                        <?php elseif ($pay['status'] === 'PENDING'): ?>
                                            <span class="text-danger font-weight-bold">PENDING</span>
                                        <?php else: ?>
                                            <?= esc($pay['status']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($pay['jml_pax']); ?></td>
                                    <td><?= esc($pay['package_name']); ?></td>
                                    <td><?= esc($pay['created_at']); ?></td>
                                    <td>

                                        <img src="<?= base_url() . esc($pay['qr_code']); ?>" width="100px">

                                    </td>

                                    <td>
                                        <?php if ($isBooked): ?>
                                            <a href="<?= base_url('print-tickets-departure/' . $pay['id'] . '/' . $pay['schedule_departure_id']); ?>" class="btn btn-success" target="_blank">
                                                <i class="fas fa-print"></i> Departure Tickets
                                            </a>

                                            <?php if (!empty($pay['schedule_return_id']) && $pay['schedule_return_id'] != 0): ?>
                                                <a href="<?= base_url('print-tickets-return/' . $pay['id'] . '/' . $pay['schedule_return_id']); ?>" class="btn btn-success" target="_blank">
                                                    <i class="fas fa-print"></i> Return Tickets
                                                </a>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#selectSeatModal<?= $no ?>" data-pax="<?= esc($pay['jml_pax']); ?>" data-boat="<?= esc($seat['boat_id']) ?>" disabled>
                                                Seats has not choosed yet
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</section>
<?= $this->endSection(); ?>