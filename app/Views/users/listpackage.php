<?= $this->include('users_layout/header'); ?>


<!-- Start Hero Section -->
<div class="hero mt-5" style="height: 400px;"> <!-- Add a larger margin-top here -->
  <div class="container">
    <div class="row justify-content-between">
      <div class="col-lg-5">
        <div class="intro-excerpt">
          <h1 class="mt-5">Produk Kami</h1>
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

		<div class="untree_co-section product-section before-footer-section mt-5">
		<div class="row mb-5">
            <div class="col-12 text-center"> <!-- Add text-center class here -->
                <h2 class="section-title">Produk Kami</h2>
                <h6 class="section-title">We are currently preparing your Gateway to Paradise</h6>
            </div>
        </div>

		    <div class="container">
		      	<div class="row">

		      		<!-- Start Column 1 -->
					<div class="col-12 col-md-4 col-lg-3">
						<a class="product-item" href="/detailpackage">
							<img src="<?= base_url() ?>assets_users/images/foto2.webp" class="img-fluid product-thumbnail">
							<h3 class="product-title">Nordic Chair</h3>
							<strong class="product-price">$50.00</strong>

							<span class="icon-cross">
								<img src="<?= base_url() ?>assets_users/images/cross.svg" class="img-fluid">
							</span>
						</a>
					</div> 
					<!-- End Column 1 -->
						
					<!-- Start Column 2 -->
					<div class="col-12 col-md-4 col-lg-3">
						<a class="product-item" href="/detailpackage">
							<img src="<?= base_url() ?>assets_users/images/foto2.webp" class="img-fluid product-thumbnail">
							<h3 class="product-title">Nordic Chair</h3>
							<strong class="product-price">$50.00</strong>

							<span class="icon-cross">
								<img src="<?= base_url() ?>assets_users/images/cross.svg" class="img-fluid"> 
							</span>
						</a>
					</div> 
					<!-- End Column 2 -->

					<!-- Start Column 3 -->
					<div class="col-12 col-md-4 col-lg-3">
						<a class="product-item" href="/detailpackage">
							<img src="<?= base_url() ?>assets_users/images/foto2.webp" class="img-fluid product-thumbnail">
							<h3 class="product-title">Kruzo Aero Chair</h3>
							<strong class="product-price">$78.00</strong>

							<span class="icon-cross">
								<img src="<?= base_url() ?>assets_users/images/cross.svg" class="img-fluid">
							</span>
						</a>
					</div>
					<!-- End Column 3 -->

					<!-- Start Column 4 -->
					<div class="col-12 col-md-4 col-lg-3">
						<a class="product-item" href="/detailpackage">
							<img src="<?= base_url() ?>assets_users/images/foto2.webp" class="img-fluid product-thumbnail">
							<h3 class="product-title">Ergonomic Chair</h3>
							<strong class="product-price">$43.00</strong>

							<span class="icon-cross">
								<img src="<?= base_url() ?>assets_users/images/cross.svg" class="img-fluid">
							</span>
						</a>
					</div>
					<!-- End Column 4 -->
		      	</div>
		    </div>
		</div>
<?= $this->include('users_layout/footer'); ?>