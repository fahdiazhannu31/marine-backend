<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Reservation</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Reservation Package</h6>
            <a class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Tambah Data
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Cust Name</th>
                            <th>Checkout Link</th>
                            <th>External ID</th>
                            <th>Status</th>
                            <th>Total Pax</th>
                            <th>Package Name</th>
                            <th>Attendance</th>
                            <th>Departure Date</th>
                            <th>Transaction Date</th>
                            <th>Transfer Slip (Manual TF)</th>
                            <th>QR Code</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($payments as $pay):

                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= !empty($pay->username) ? esc($pay->username) : '-'; ?></td>
                                 <td>
                                    <?php if ($pay->status === 'SETTLED'): ?>
                                        <span class="badge badge-success">DONE</span>
                                    <?php else: ?>
                                        <?php if (!empty($pay->external_id) && strpos($pay->external_id, 'MANUAL') !== false): ?>
                                            <a href="<?= base_url('manual-payment/detail/' . $pay->external_id); ?>" class="btn btn-warning">Manual Payment</a>
                                        <?php else: ?>
                                            <a href="<?= !empty($pay->checkout_link) ? esc($pay->checkout_link) : '#'; ?>" class="btn btn-primary">Checkout</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($pay->external_id) ? esc($pay->external_id) : '-'; ?></td>
                                 <td>
                                    <select class="form-control status-update" data-id="<?= $pay->id; ?>">
                                        <option value="ON VERIFICATION" <?= ($pay->status === 'ON VERIFICATION') ? 'selected' : ''; ?>>ON VERIFICATION</option>
                                        <option value="PENDING" <?= ($pay->status === 'PENDING') ? 'selected' : ''; ?>>PENDING</option>
                                        <option value="SETTLED" <?= ($pay->status === 'SETTLED') ? 'selected' : ''; ?>>SETTLED</option>
                                        <option value="EXPIRED" <?= ($pay->status === 'EXPIRED') ? 'selected' : ''; ?>>EXPIRED</option>
                                        <option value="CANCELLED" <?= ($pay->status === 'CANCELLED') ? 'selected' : ''; ?>>CANCELLED</option>
                                    </select>
                                </td>
                                <td><?= !empty($pay->jml_pax) ? esc($pay->jml_pax) : '-'; ?></td>
                                <td><?= !empty($pay->package_name) ? esc($pay->package_name) : '-'; ?></td>
                                <td><?= !empty($pay->attendance) ? esc($pay->attendance) : '-'; ?></td>
                                <td><?= !empty($pay->date) ? esc(date('l, F j, Y', strtotime($pay->date))) : '-'; ?></td>
                                <td><?= !empty($pay->created_at) ? esc(date('l, F j, Y', strtotime($pay->created_at))) : '-'; ?></td>
                                <td>
                                    <?php if (!empty($pay->transfer_slip)): ?>
                                        <img src="<?= base_url('uploads/payment_proofs/') . $pay->transfer_slip; ?>" width="100px" data-toggle="modal" data-target="#imageModal" onclick="openModal('<?= base_url('uploads/payment_proofs/') . $pay->transfer_slip; ?>')">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($pay->qr_code)): ?>
                                        <img src="<?= base_url() . $pay->qr_code; ?>" width="100px" data-toggle="modal" data-target="#imageModal" onclick="openModal('<?= base_url() . $pay->qr_code; ?>')">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Dropdown for selecting between Departure and Return -->
                                    <select class="form-control" onchange="window.location.href=this.value" class="form-select">
                                        <option value="" disabled selected>Select Seat Type</option>
                                        <option value="<?= base_url('detail-departure/' . $pay->id); ?>">Departure</option>
                                        <?php if (!empty($pay->schedule_return_id) && $pay->schedule_return_id != 0): ?>
                                            <option value="<?= base_url('detail-return/' . esc($pay->id)); ?>">Return</option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Preview">
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->include('admin_layout/footer'); ?>
<script>
    $(document).ready(function() {
        $('.status-update').change(function() {
            let status = $(this).val();
            let paymentId = $(this).data('id');

            $.ajax({
                url: "<?= base_url('admin/updateStatus') ?>",
                type: "POST",
                data: {
                    id: paymentId,
                    status: status
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: 'The reservation status has been updated successfully!',
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: 'An error occurred while updating the status.',
                    });
                }
            });
        });
    });
</script>
<script>
    function openModal(imageUrl) {
        document.getElementById('modalImage').src = imageUrl;
    }
</script>