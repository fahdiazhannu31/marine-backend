<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Page Login</title>

    <!-- Custom fonts for this template-->
    <link href="<?= base_url(); ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?= base_url(); ?>assets/css/sb-admin-2.min.css" rel="stylesheet" type="text/css">

</head>

<body class="bg-gradient-primary" style="background-image: url('<?= base_url () ?>assets_users/images/jumbotron.webp'); background-size: cover; background-position: center center; background-repeat: no-repeat;">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-md-6">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg">
                                <div class="p-5">
                                    <div class="text-center">
                                        <img src="<?= base_url() ?>assets_users/images/logo.webp" width="40%">
                                        <h1 class="h4 text-gray-900 mb-3 mt-5"><?=lang('Auth.loginTitle')?></h1>
                                    </div>

                                    <?= view('Myth\Auth\Views\_message_block') ?>

                                    <form action="<?= url_to('login') ?>" method="post" class="user">
						            <?= csrf_field() ?>

                                    <!-- End Kondisi Bisa login email atau password -->
                                    <?php if ($config->validFields === ['email']): ?>
                                        <div class="form-group">
                                            <input 
                                                type="email" 
                                                class="form-control form-control-user <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>"
                                                name="login" 
                                                placeholder="<?=lang('Auth.email')?>">
                                                <div class="invalid-feedback">
                                                    <?= session('errors.login') ?>
                                                </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="form-group">
                                            <input 
                                                type="email" 
                                                class="form-control form-control-user <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>"
                                                name="login" 
                                                placeholder="<?=lang('Auth.emailOrUsername')?>">
                                                <div class="invalid-feedback">
                                                    <?= session('errors.login') ?>
                                                </div>
                                        </div>
                                    <?php endif; ?>
                                    <!-- End Kondisi Bisa login email atau password -->

                                        <div class="form-group">
                                            <input 
                                                type="password" 
                                                class="form-control form-control-user <?php if (session('errors.password')) : ?>is-invalid<?php endif ?>" 
                                                name="password"
                                                placeholder="<?=lang('Auth.password')?>">
                                                <div class="invalid-feedback">
                                                    <?= session('errors.password') ?>
                                                </div>
                                        </div>

                                        <!-- Blok perkodisian ingat saya jika login -->
                                        <?php if ($config->allowRemembering): ?>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input 
                                                    type="checkbox" 
                                                    class="custom-control-input" 
                                                    name="remember" 
                                                    <?php if (old('remember')) : ?> checked <?php endif ?>>
                                                    <?=lang('Auth.rememberMe')?>
                                                <label class="custom-control-label" for="customCheck">Remember Me</label>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <!-- End Blok perkodisian -->

                                        <button type="submit" class="btn btn-user btn-block" style="background-color: rgba(255, 52, 76, 0.78); color:black">
                                            <?=lang('Auth.loginAction')?>
                                        </button>
                                        <!-- <hr>
                                        <a href="index.html" class="btn btn-google btn-user btn-block">
                                            <i class="fab fa-google fa-fw"></i> Login with Google
                                        </a>
                                        <a href="index.html" class="btn btn-facebook btn-user btn-block">
                                            <i class="fab fa-facebook-f fa-fw"></i> 
                                        </a> -->
                                    </form>
                                    <hr>
                                    <!-- <div class="text-center">
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
                                    </div> -->

                                    <?php if ($config->activeResetter) : ?>
                                    <div class="text-center">
                                        <a class="small" href="<?= url_to('forgot') ?>"> <?=lang('Auth.forgotYourPassword')?> </a>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($config->allowRegistration) : ?>
                                    <div class="text-center">
                                        <a class="small" href="<?= url_to('register') ?>"> <?=lang('Auth.needAnAccount')?> </a>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= base_url(); ?>assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?= base_url(); ?>assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?= base_url(); ?>assets/js/sb-admin-2.min.js"></script>

</body>

</html>