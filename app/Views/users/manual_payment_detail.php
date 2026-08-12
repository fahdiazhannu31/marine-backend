<?= $this->extend('users_layout/page_layout'); ?>
<?= $this->section('content'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">Manual Transfer Checkout</h4>
                </div>
                <div class="card-body">
                    <!-- Show any error messages -->
                    <?php if (session('error')): ?>
                        <div class="alert alert-danger">
                            <?= session('error') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2">Booking Details</h5>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Transaction ID:</strong> <?= $external_id ?></p>
                            <p><strong>Package:</strong> <?= esc($package_name) ?></p>
                            <p><strong>Trip Type:</strong> <?= ucfirst(str_replace('_', ' ', $trip_type)) ?></p>
                            <p><strong>Number of Pax:</strong> <?= $jml_pax ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Amount:</strong> <span class="text-primary fw-bold">Rp <?= number_format($amount, 0, ',', '.') ?></span></p>
                            <p><strong>Departure Date:</strong> <?= date('d-m-Y', strtotime($departure_date)) ?></p>
                            <?php if ($trip_type == 'round_trip' && !empty($return_date)): ?>
                                <p><strong>Return Date:</strong> <?= date('d-m-Y', strtotime($return_date)) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h5>Payment Instructions</h5>
                        <p>Please transfer the total amount to one of our bank accounts below:</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <img src="<?= base_url(); ?>assets_users/images/bca.svg"/ width="150px">
                                        <p class="card-text mb-1"><strong>Account Number:</strong> 0353298027</p>
                                        <p class="card-text"><strong>Account Name:</strong> PT Bahtera Nama Nusantara</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 mb-0"><strong>Important:</strong> Please include your Transaction ID (<?= $external_id ?>) in the payment reference/description.</p>
                    </div>

                    <form action="<?= base_url('process-manual-payment') ?>" method="post" enctype="multipart/form-data" class="mt-4">
                        <!-- CSRF Token -->
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                        <!-- Hidden fields to pass booking details -->
                        <input type="hidden" name="external_id" value="<?= $external_id ?>">

                        <h5 class="border-bottom pb-2 mb-3">Upload Payment Proof</h5>

                        <div class="mb-3">
                            <label for="payment_proof" class="form-label">Payment Proof (Image or PDF)</label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" required>
                            <div class="form-text">Upload an image or PDF of your payment receipt/proof (Max: 5MB)</div>
                        </div>

                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                        </div>

                        <div class="mb-3">
                            <label for="bank_name" class="form-label">Bank Used</label>
                            <select class="form-select" id="bank_name" name="bank_name" required>
                                <option value="">Select Bank</option>
                                <option value="BCA">BCA</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="BNI">BNI</option>
                                <option value="BRI">BRI</option>
                                <option value="CIMB Niaga">CIMB Niaga</option>
                                <option value="Permata">Permata</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special instructions or details about your payment"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Payment Proof</button>
                            <a href="<?= base_url('packages') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <small class="text-muted">Your booking is not confirmed until we verify your payment. Please upload your payment proof to complete your booking.</small>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set today as default date for payment date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('payment_date').value = today;

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const fileInput = document.getElementById('payment_proof');
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];

                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    event.preventDefault();
                    alert('Only JPG, PNG and PDF files are allowed');
                    return;
                }

                // Check file size
                if (file.size > maxSize) {
                    event.preventDefault();
                    alert('File size should not exceed 5MB');
                    return;
                }
            }
        });
    });

    <?php if (session()->getFlashdata('success')) : ?>
        Swal.fire({
            title: "Berhasil!",
            text: "<?= session()->getFlashdata('success') ?>",
            icon: "success",
            confirmButtonText: "OK"
        });
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        Swal.fire({
            title: "Error!",
            text: "<?= session()->getFlashdata('error') ?>",
            icon: "error",
            confirmButtonText: "OK"
        });
    <?php endif; ?>
</script>
<?= $this->endSection(); ?>