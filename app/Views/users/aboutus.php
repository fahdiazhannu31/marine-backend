<?= $this->include('users_layout/header'); ?>

<!-- Start Hero Section -->
<div class="hero mt-5" style="height: 400px;"> <!-- Add a larger margin-top here -->
  <div class="container">
    <div class="row justify-content-between">
      <div class="col-lg-5">
        <div class="intro-excerpt">
          <h1 class="mt-5">Tentang Kami</h1>
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

		<!-- Start Testimonial Slider -->
		<div class="testimonial-section before-footer-section">
			<div class="container">
				<div class="row">
					<div class="col-lg-7 mx-auto text-center">
						<h2 class="section-title">Tentang Kami</h2>
					</div>
				</div>

				<div class="row justify-content-center">
					<div class="col-lg-12">
						<div class="testimonial-slider-wrap text-center">

							<div id="testimonial-nav">
								<span class="prev" data-controls="prev"><span class="fa fa-chevron-left"></span></span>
								<span class="next" data-controls="next"><span class="fa fa-chevron-right"></span></span>
							</div>

							<div class="testimonial-slider">
								
								<div class="item">
									<div class="row justify-content-center">
										<div class="col-lg-8 mx-auto">

											<div class="testimonial-block text-center">
												<blockquote class="mb-5">
													<p>&ldquo;Welcome to Nama Marine! Nestled in the heart of the stunning Sepa Island, part of the beautiful Pulau Seribu archipelago in Jakarta, Indonesia, we are dedicated to providing exceptional transportation and accommodation services for those seeking to explore this tropical paradise.&rdquo;</p>
												</blockquote>

												<div class="author-info">
													<div class="author-pic">
														<img src="<?= base_url() ?>assets/img/default.svg" alt="Matthew" class="img-fluid">
													</div>
													<h3 class="font-weight-bold">Matthew</h3>
													<span class="position d-block mb-3">CEO, Co-Founder, XYZ Inc.</span>
												</div>
											</div>

										</div>
									</div>
								</div> 
								<!-- END item -->

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- End Testimonial Slider -->

        <?= $this->include('users_layout/footer'); ?>
