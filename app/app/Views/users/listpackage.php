<?= $this->extend('users_layout/page_layout'); ?>
<?= $this->section('content'); ?>

<!-- Start Hero Section -->
<div class="hero mt-5"> <!-- Add a larger margin-top here -->
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-lg-5">
				<div class="intro-excerpt">
					<h1 class="mt-5">Choose your trip</h1>
					<p class="mb-4">A Hidden Paradise Awaits.</p>
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
<div class="untree_co-section product-section before-footer-section mt-5">
	<div class="row mb-5">
		<div class="col-12 text-center">
			<h2 class="section-title">Choose your trip</h2>
			<h6 class="section-title">We are currently preparing your Gateway to Paradise</h6>
		</div>
	</div>

	<div class="container">
		<div class="row">
			<?php if (!empty($packages)): ?>
				<?php foreach ($packages as $package): ?>
					<div class="col-12 col-md-4 col-lg-3 mb-4">
						<?php
						$whatsappNumber = "628123456789"; // Ganti dengan nomor WhatsApp yang dituju
						$message = urlencode("Halo, saya tertarik dengan paket *Private Package*.");
						$whatsappLink = "https://wa.me/$whatsappNumber?text=$message";
						$packageLink = ($package['title'] == 'Private Package') ? $whatsappLink : "/detailpackage/" . $package['id'];
						?>
						<a class="product-item" href="<?= $packageLink; ?>" target="<?= ($package['title'] == 'Private Package') ? '_blank' : '_self'; ?>">
							<img src="<?= base_url('assets_users/images/' . $package['photo1']); ?>" class="img-fluid product-thumbnail">
							<h3 class="product-title"><?= $package['title']; ?></h3>
							<strong class="product-price">Rp <?= number_format($package['price_per_pax'], 0, ',', '.'); ?></strong>
							<span class="icon-cross">
								<img src="<?= base_url() ?>assets_users/images/cross.svg" class="img-fluid">
							</span>
						</a>
					</div>
				<?php endforeach; ?>
			<?php else: ?>
				<p class="text-center">No packages available at the moment.</p>
			<?php endif; ?>
		</div>
	</div>
</div>
</main>

<?= $this->endSection() ?>