<!-- /*
* Bootstrap 5
* Template Name: Furni
* Template Author: Untree.co
* Template URI: https://untree.co/
* License: https://creativecommons.org/licenses/by/3.0/
*/ -->
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="author" content="">
  <link rel="shortcut icon" href="<?= base_url(); ?>favicon.ico">

  <meta name="description" content="Pulau Sepa adalah salah satu destinasi eksotis di Kepulauan Seribu yang terkenal dengan pemandangan alam yang menakjubkan dan laut biru yang jernih. Pulau ini menawarkan pantai berpasir putih yang lembut, dikelilingi oleh air laut yang tenang dan berwarna turquoise, menjadikannya tempat sempurna untuk beristirahat dari hiruk-pikuk kota." />
  <meta name="keywords" content="Pulau Sepa, Jakarta Hidden Paradise, Pantai Indah di Jakarta, Liburan Pantai Jakarta" />

  <!-- Bootstrap CSS -->
  <link href="<?= base_url(); ?>assets_users/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link href="<?= base_url(); ?>assets_users/css/tiny-slider.css" rel="stylesheet">
  <link href="<?= base_url(); ?>assets_users/css/style.css" rel="stylesheet">
  <title><?= $title; ?></title>
  <!-- Include Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Include Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <style>
    @media (max-width: 768px) {
      .navbar-toggler {
        filter: invert(1);
        /* Mengubah warna ikon toggler menjadi hitam */
      }
    }
  </style>
</head>

<body>

  <!-- As a heading -->
  <div class="container-fluid d-flex justify-content-center align-items-center p-2" style="background-color: rgba(255, 52, 76, 0.78); height: 50px; position: sticky; top: 0; z-index: 1051;">
    <span class="navbar-brand mb-0 h1 text-center" style="color:white; font-size: 16px; white-space: nowrap;">Your Island Adventure is just around the corner!</span>
  </div>

  <!-- Start Header/Navigation -->
  <nav class="custom-navbar navbar navbar-expand-md navbar-dark bg-dark fixed-top shadow-sm" aria-label="Furni navigation bar" style="margin-top: 50px;"> <!-- Adjusted margin-top -->
    <div class="container">
      <a class="navbar-brand" href="#"><img src="<?= base_url(); ?>assets_users/images/logo.webp" class="pt-2" style="width:8rem"></a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsFurni" aria-controls="navbarsFurni" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsFurni">
        <?php $currentUrl = $_SERVER['REQUEST_URI']; ?>

        <ul class="custom-navbar-nav navbar-nav ms-auto mb-2 mb-md-0">
          <li><a class="nav-link <?= ($currentUrl == '/') ? 'active' : '' ?>" href="/">Home</a></li>

          <?php if (logged_in() && in_groups(['admin', 'users'])) : ?>
            <li><a class="nav-link <?= ($currentUrl == '/listtranscation') ? 'active' : '' ?>" href="/listtranscation">Your Transactions</a></li>
          <?php endif; ?>

          <li><a class="nav-link <?= ($currentUrl == '/listpackage') ? 'active' : '' ?>" href="/listpackage">Packages</a></li>
          <li><a class="nav-link <?= ($currentUrl == '/aboutus') ? 'active' : '' ?>" href="/aboutus">About Us</a></li>
          <li><a class="nav-link <?= ($currentUrl == '/explore') ? 'active' : '' ?>" href="/explore">Explore</a></li>
          <?php if (logged_in() && in_groups('admin')) : ?>
            <li><a class="nav-link <?= ($currentUrl == '/admin') ? 'active' : '' ?>" href="/admin">Admin Page</a></li>
          <?php endif; ?>
        </ul>

        <!-- User Profile Section -->
        <ul class="custom-navbar-cta navbar-nav mb-2 mb-md-0 ms-5 d-flex align-items-center">
          <?php if (logged_in()) : ?>
            <li class="nav-item">
              <!-- Profile Image and Username -->
              <a class="nav-link d-flex align-items-center" href="#">
                <span class="ms-3 text-black"><?= user()->username; ?></span>
              </a>
            </li>
            <li class="nav-item">
              <!-- Logout Icon -->
              <a class="nav-link" href="<?= base_url(); ?>logout">
                <i class="fas fa-sign-out-alt text-danger"></i>
              </a>
            </li>
          <?php else : ?>
            <ul class="custom-navbar-cta navbar-nav mb-2 mb-md-0 ms-5 d-flex align-items-center">
              <li class="nav-item">
                <!-- Logout Icon -->
                <a class="btn btn-sm" style="background-color: rgba(255, 52, 76, 0.78); border-color: rgba(255, 52, 76, 0.78);" href="<?= base_url(); ?>login">
                  Login
                </a>
              </li>
              <li class="nav-item">
                <!-- Logout Icon -->
                <a class="btn btn-sm" style="background-color: rgba(255, 52, 76, 0.78); border-color: rgba(255, 52, 76, 0.78);" href="<?= base_url(); ?>register">
                  Register
                </a>
              </li>
            </ul>
          <?php endif; ?>
        </ul>

      </div>
    </div>
  </nav>
  <a href="https://api.whatsapp.com/send?phone=51955081075&text=Hola%21%20Quisiera%20m%C3%A1s%20informaci%C3%B3n%20sobre%20Varela%202." class="float" target="_blank">
    <i class="fa fa-whatsapp my-float"></i>
  </a>
  <div class="content-wrapper">
    <?= $this->renderSection('content'); ?>
  </div>
  <!-- Start Footer Section -->
  <footer class="footer-section">
    <div class="container relative">

      <!-- <div class="sofa-img">
					<img src="<?= base_url(); ?>assets_users/images/sofa.png" alt="Image" class="img-fluid">
				</div> -->

      <div class="row g-5 mb-5">
        <div class="col-lg-4">
          <div class="mb-4 footer-logo-wrap" style="padding-bottom: 8%;"><a href="#" class="footer-logo"><img src="<?= base_url() ?>assets_users/images/logo.webp" width="40%"></a></div>
          <!-- <p class="mb-4">Donec facilisis quam ut purus rutrum lobortis. Donec vitae odio quis nisl dapibus malesuada. Nullam ac aliquet velit. Aliquam vulputate velit imperdiet dolor tempor tristique. Pellentesque habitant</p> -->
          <h6 class="mb-1"> Payment Methods </h6>
          <ul style="list-style: none; padding: 0; display: flex; gap: 10px;">
            <li><a href="#"><img src="<?= base_url() ?>assets_users/images/ovo.svg" style="width:100%"></a></li>
            <li><a href="#"><img src="<?= base_url() ?>assets_users/images/bca.svg" style="width:100%"></a></li>
            <li><a href="#"><img src="<?= base_url() ?>assets_users/images/bri.svg" style="width:100%"></a></li>
            <li><a href="#"><img src="<?= base_url() ?>assets_users/images/bni.svg" style="width:100%"></a></li>
            <li><a href="#"><img src="<?= base_url() ?>assets_users/images/mandiri.svg" style="width:100%"></a></li>
            <li><a href="#"><img src="<?= base_url() ?>assets_users/images/permata.svg" style="width:100%"></a></li>
          </ul>
          <hr>
        </div>

        <div class="col-lg-8">
          <div class="row links-wrap">
            <div class="col-6 col-sm-6 col-md-4">
              <ul class="list-unstyled">
              </ul>
            </div>

            <div class="col-6 col-sm-6 col-md-4">
              <ul class="list-unstyled">
                <li>
                  <h4>Menu</h4>
                </li>
                <li><a href="<?= base_url(); ?>">Home</a></li>
                <li><a href="<?= base_url(); ?>listpackage">Packages</a></li>
                <li><a href="<?= base_url(); ?>aboutus">About Us</a></li>
              </ul>
            </div>
            <?php foreach ($footer as $foot): ?>
              <div class="col-7 col-sm-7 col-md-4">
                <h6 class="mb-2" style="margin-top: 3%;">Customer Care</h6>
                <ul class="list-unstyled">
                  <li><?= $foot['day_op']; ?></li>
                  <li>
                    <a href="https://api.whatsapp.com/send?phone=<?= $foot['phone']; ?>&text=Hola%21%20Quisiera%20m%C3%A1s%20informaci%C3%B3n%20sobre%20Varela%202." class="btn btn-secondary btn-whatsapp me-2">
                      <i class="fab fa-whatsapp"></i> Get In Touch
                    </a>
                  </li>
                </ul>
              </div>
          </div>
        </div>
      </div>

      <div class="border-top copyright">
        <div class="row pt-4">
          <div class="col-lg-6">
            <p class="mb-2 text-center text-lg-start"><?= $foot['copyright']; ?></p>
          </div>
        <?php endforeach; ?>
        <!-- <div class="col-lg-6 text-center text-lg-end">
							<ul class="list-unstyled d-inline-flex ms-auto">
								<li class="me-4"><a href="#">Terms &amp; Conditions</a></li>
								<li><a href="#">Privacy Policy</a></li>
							</ul>
						</div> -->

        </div>
      </div>

    </div>
  </footer>
  <!-- End Footer Section -->


  <script src="<?= base_url(); ?>assets_users/js/bootstrap.bundle.min.js"></script>
  <script src="<?= base_url(); ?>assets_users/js/tiny-slider.js"></script>
  <script src="<?= base_url(); ?>assets_users/js/custom.js"></script>
</body>

</html>

<script>
  const hero = document.querySelector('.hero');
  const afterHero = document.querySelector('.after-hero');

  window.addEventListener('scroll', () => {
    const afterHeroTop = afterHero.getBoundingClientRect().top;

    if (afterHeroTop < window.innerHeight / 2) {
      hero.classList.add('hero-hidden');
    } else {
      hero.classList.remove('hero-hidden');
    }
  });
</script>

<div style="height: 40px;"></div>