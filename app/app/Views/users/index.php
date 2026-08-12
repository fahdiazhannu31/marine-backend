<?= $this->extend('users_layout/page_layout'); ?>
<?= $this->section('content'); ?>
<!-- Start Hero Section -->
<div class="hero mt-5"> <!-- Add a larger margin-top here -->
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-lg-5">
              <div class="intro-excerpt">
                <h1 class="mt-5">Find Your Happiness</h1>
                <p class="mb-4">Nama Marine, Your Gateway to Paradise!.</p>
                <p><a href="<?= base_url('listpackage'); ?>" class="btn btn-secondary me-2">Trip Now</a><a href="<?= base_url('listpackage'); ?>" class="btn btn-white-outline">Explore</a></p>
              </div>
            </div>
            <div class="col-lg-7">
                <!-- You can remove the image here if you want the image to be handled by CSS -->
            </div>
        </div>
    </div>
</div>
<!-- End Hero Section -->
<main class="after-hero">
<div class="blog-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mx-auto text-center mb-5">
                <h2 class="section-title">Exploring Island Beauty, Capturing Unforgettable Moments!</h2>
            </div>
        </div>
        <!-- Gallery with consistent margins and equal-sized photos -->
        <div class="row g-3">
            <!-- Column 1 -->
            <div class="col-lg-4 col-md-12">
                <div class="d-flex flex-column gap-3 h-100">
                    <div class="equal-photo-container">
                        <img
                        src="<?= base_url(); ?>assets_users/images/1.jpg"
                        class="equal-photo shadow-1-strong rounded"
                        alt="Group Photo in Water"
                        />
                    </div>
                    <div class="equal-photo-container">
                        <img
                        src="<?= base_url(); ?>assets_users/images/4.jpg"
                        class="equal-photo shadow-1-strong rounded"
                        alt="People Walking on Beach Path"
                        />
                    </div>
                    <div class="equal-photo-container">
                        <img
                        src="<?= base_url(); ?>assets_users/images/6.jpg"
                        class="equal-photo shadow-1-strong rounded"
                        alt="Floating House on Water"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Column 2 (Taller center column) -->
            <div class="col-lg-4 col-md-12">
                <div class="center-photo-container h-100">
                    <video
                    class="center-photo shadow-1-strong rounded"
                    autoplay
                    muted
                    loop
                    >
                    <source src="<?= base_url(); ?>assets_users/images/video.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                    </video>
                </div>
            </div>
            
            <!-- Column 3 -->
            <div class="col-lg-4 col-md-12">
                <div class="d-flex flex-column gap-3 h-100">
                    <div class="equal-photo-container">
                        <img
                        src="<?= base_url(); ?>assets_users/images/foto3.webp"
                        class="equal-photo shadow-1-strong rounded"
                        alt="Group Photo on Beach"
                        />
                    </div>
                    <div class="equal-photo-container">
                        <img
                        src="<?= base_url(); ?>assets_users/images/bridge.jpg"
                        class="equal-photo shadow-1-strong rounded"
                        alt="Wooden Bridge over Turquoise Water"
                        />
                    </div>
                    <div class="equal-photo-container">
                        <img
                        src="<?= base_url(); ?>assets_users/images/foto3.webp"
                        class="equal-photo shadow-1-strong rounded"
                        alt="Group Photo on Beach"
                        />
                    </div>
                </div>
            </div>
        </div>
        <!-- End Gallery -->
    </div>
</div>
<!-- End Blog Section -->
</main>
<?= $this->endSection(); ?>