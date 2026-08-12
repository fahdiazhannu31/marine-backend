<?= $this->extend('users_layout/page_layout'); ?>

<?= $this->section('content'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-3">Thank You for Your Booking!</h2>
                    <h4 class="text-success mb-4">Your payment proof has been submitted successfully</h4>

                    <div class="alert alert-info mb-4">
                        <p class="mb-1">Transaction ID: <strong><?= $transaction_id ?></strong></p>
                        <p class="mb-0">Please save this transaction ID for your reference</p>
                    </div>

                    <div class="text-start mb-4">
                        <h5>What happens next?</h5>
                        <ol>
                            <li>Our team will verify your payment (usually within 1-2 business days)</li>
                            <li>You'll receive a confirmation email once your payment is verified</li>
                            <li>Your e-ticket/voucher will be sent to your email</li>
                        </ol>
                    </div>

                    <p>You can track your booking status in your account dashboard.</p>

                    <div class="mt-4">
                        <a href="<?= base_url('user/bookings') ?>" class="btn btn-primary me-2">View My Bookings</a>
                        <a href="<?= base_url('packages') ?>" class="btn btn-outline-secondary">Explore More Packages</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>