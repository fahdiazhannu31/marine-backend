<?= $this->extend('users_layout/page_layout'); ?>
<?= $this->section('content'); ?>

<!-- Start Hero Section -->
<div class="hero mt-5"> <!-- Add a larger margin-top here -->
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-lg-5">
                <div class="intro-excerpt">
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
<!-- Start Testimonial Slider -->
<div class="testimonial-section before-footer-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-7 mx-auto text-center">
				<h2 class="section-title mb-5">Explore Us</h2>
			</div>
		</div>

		<div class="row justify-content-center">
			<!-- Google Drive Card -->
			<div class="col-md-6 col-lg-5 mb-4">
				<div class="card h-100 shadow-sm">
					<div class="card-body text-center py-5">
						<i class="fa fa-cloud fa-4x mb-3 text-primary"></i>
						<h3 class="card-title">Our Documents</h3>
						<p class="card-text">Access all our brochures, price lists, and information packages about Sepa Island and our services.</p>
						<a href="https://drive.google.com" target="_blank" class="btn btn-primary mt-3">
							<i class="fa fa-cloud-download me-2"></i>Google Drive
						</a>
					</div>
				</div>
			</div>
			
			<!-- WhatsApp Card -->
			<div class="col-md-6 col-lg-5 mb-4">
				<div class="card h-100 shadow-sm">
					<div class="card-body text-center py-5">
						<i class="fa fa-whatsapp fa-4x mb-3 text-success"></i>
						<h3 class="card-title">Contact Us</h3>
						<p class="card-text">Have questions or ready to book? Chat with our customer service team for fast and friendly assistance.</p>
						<a href="https://wa.me/your-whatsapp-number" target="_blank" class="btn btn-success mt-3">
							<i class="fa fa-whatsapp me-2"></i>WhatsApp Chat
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- End Testimonial Slider -->
</main>

<?= $this->endSection() ?>