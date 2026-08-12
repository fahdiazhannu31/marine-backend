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
  <meta name="author" content="Untree.co">
  <link rel="shortcut icon" href="favicon.png">

  <meta name="description" content="" />
  <meta name="keywords" content="bootstrap, bootstrap4" />

		<!-- Bootstrap CSS -->
		<link href="<?= base_url (); ?>/assets_users/css/bootstrap.min.css" rel="stylesheet">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
		<link href="<?= base_url (); ?>/assets_users/css/tiny-slider.css" rel="stylesheet">
		<link href="<?= base_url (); ?>/assets_users/css/style.css" rel="stylesheet">
		<title>Nama Marine</title>
	</head>

	<body>

<!-- As a heading -->
<div class="container-fluid d-flex justify-content-center align-items-center p-2" style="background-color: rgba(255, 52, 76, 0.78); height: 50px; position: sticky; top: 0; z-index: 1051;">
  <span class="navbar-brand mb-0 h1 text-center" style="color:white; font-size: 16px; white-space: nowrap;">Your Island Adventure is just around the corner!</span>
</div>

<!-- Start Header/Navigation -->
<nav class="custom-navbar navbar navbar-expand-md navbar-dark bg-dark fixed-top" aria-label="Furni navigation bar" style="margin-top: 50px;"> <!-- Adjusted margin-top -->
  <div class="container">
    <a class="navbar-brand" href="#"><img src="<?= base_url (); ?>/assets_users/images/logo.webp" class="pt-2" style="width:8rem"></a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsFurni" aria-controls="navbarsFurni" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarsFurni">
      <ul class="custom-navbar-nav navbar-nav ms-auto mb-2 mb-md-0">
        <li><a class="nav-link" href="/">Beranda</a></li>
        <li><a class="nav-link" href="/listpackage">Produk Kami</a></li>
        <li><a class="nav-link" href="/aboutus.">Tentang Kami</a></li>
        <li><a class="nav-link" href="#">FAQ</a></li>
      </ul>

      <!-- User Profile Section -->
      <ul class="custom-navbar-cta navbar-nav mb-2 mb-md-0 ms-5 d-flex align-items-center">
        <li class="nav-item">
          <!-- Logout Icon -->
            <a class="btn btn-sm" style="background-color: rgba(255, 52, 76, 0.78); border-color: rgba(255, 52, 76, 0.78);" href="/login">
                Login
            </a>
        </li>
        <li class="nav-item">
          <!-- Logout Icon -->
            <a class="btn btn-sm" style="background-color: rgba(255, 52, 76, 0.78); border-color: rgba(255, 52, 76, 0.78);" href="/register">
                Register
            </a>
        </li>
      </ul>
    </div>
  </div>
</nav>


<!-- End Header/Navigation -->

<div style="height: 40px;"></div>
