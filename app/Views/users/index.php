<?= $this->include('users_layout/header'); ?>

<!-- Start Hero Section -->
<div class="hero mt-5"> <!-- Add a larger margin-top here -->
  <div class="container">
    <div class="row justify-content-between">
      <div class="col-lg-5">
        <div class="intro-excerpt">
          <h1 class="mt-5">Coming Soon</h1>
          <p class="mb-4">A Hidden Paradise Awaits.</p>
          <p><a href="" class="btn btn-secondary me-2">Shop Now</a><a href="#" class="btn btn-white-outline">Explore</a></p>
        </div>
      </div>
      <div class="col-lg-7">
        <!-- You can remove the image here if you want the image to be handled by CSS -->
      </div>
    </div>
  </div>
</div>
<!-- End Hero Section -->


		<!-- Start Blog Section -->
        <div class="blog-section">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center"> <!-- Add text-center class here -->
                <h2 class="section-title">SEPA Island Transportation & Accomodation Service</h2>
                <h6 class="section-title">We are currently preparing your Gateway to Paradise</h6>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-sm-6 col-md-4 mb-4 mb-md-0">
                <div class="post-entry">
                    <a href="#" class="post-thumbnail">
                        <img src="<?= base_url (); ?>/assets_users/images/foto1.webp" alt="Image" class="img-fluid">
                    </a>
                    <div class="post-content-entry">
                        <h3><a href="#">First Time Home Owner Ideas</a></h3>
                        <div class="meta">
                            <span>by <a href="#">Kristin Watson</a></span> <span>on <a href="#">Dec 19, 2021</a></span>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-12 col-sm-6 col-md-4 mb-4 mb-md-0">
                <div class="post-entry">
                    <a href="#" class="post-thumbnail">
                        <img src="<?= base_url (); ?>/assets_users/images/foto2.webp" alt="Image" class="img-fluid">
                    </a>
                    <div class="post-content-entry">
                        <h3><a href="#">How To Keep Your Furniture Clean</a></h3>
                        <div class="meta">
                            <span>by <a href="#">Robert Fox</a></span> <span>on <a href="#">Dec 15, 2021</a></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 mb-4 mb-md-0">
                <div class="post-entry">
                    <a href="#" class="post-thumbnail">
                        <img src="<?= base_url (); ?>/assets_users/images/foto3.webp" alt="Image" class="img-fluid">
                    </a>
                    <div class="post-content-entry">
                        <h3><a href="#">Small Space Furniture Apartment Ideas</a></h3>
                        <div class="meta">
                            <span>by <a href="#">Kristin Watson</a></span> <span>on <a href="#">Dec 12, 2021</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Blog Section -->	

<?= $this->include('users_layout/footer'); ?>