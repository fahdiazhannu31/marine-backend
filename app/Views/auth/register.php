<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Page Register</title>

    <!-- Custom fonts for this template-->
    <link href="<?= base_url(); ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?= base_url(); ?>assets/css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary" style="background-image: url('<?= base_url () ?>assets_users/images/jumbotron.webp'); background-size: cover; background-position: center center; background-repeat: no-repeat;">

    <div class="container">
    <div class="row justify-content-center">
        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg">
                        <div class="p-5">
                            <div class="text-center">
                                <img src="<?= base_url() ?>assets_users/images/logo.webp" width="50%">
                                <h1 class="h4 text-gray-900 mb-3 mt-5"><?=lang('Auth.register')?></h1>
                            </div>

                            <?= view('Myth\Auth\Views\_message_block') ?>
                            
                            <form action="<?= url_to('register') ?>" method="post" class="user">
                            <?= csrf_field() ?>
                                <!-- <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="text" class="form-control form-control-user" id="exampleFirstName"
                                            placeholder="First Name">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" id="exampleLastName"
                                            placeholder="Last Name">
                                    </div>
                                </div> -->
                                <div class="form-group row">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <input 
                                        type="text" 
                                        class="form-control form-control-user <?php if (session('errors.fullname')) : ?>is-invalid<?php endif ?>" 
                                        name="fullname"
                                        placeholder="fullname" 
                                        value="<?= old('fullname') ?>">
                                </div>
                                <div class="col-sm-6">
                                    <input 
                                        type="text" 
                                        class="form-control form-control-user <?php if (session('errors.phone')) : ?>is-invalid<?php endif ?>" 
                                        name="phone"
                                        placeholder="phone" 
                                        value="<?= old('phone') ?>">
                                </div>
                                </div>
                                <div class="form-group">
                                    <input 
                                        type="text" 
                                        class="form-control form-control-user <?php if (session('errors.username')) : ?>is-invalid<?php endif ?>" 
                                        name="username"
                                        placeholder="<?=lang('Auth.username')?>" 
                                        value="<?= old('username') ?>">
                                </div>
                                <div class="form-group">
                                    <input 
                                        type="email" 
                                        class="form-control form-control-user <?php if (session('errors.email')) : ?>is-invalid<?php endif ?>" 
                                        name="email"
                                        placeholder="<?=lang('Auth.email')?>" 
                                        value="<?= old('email') ?>">
                                    <small id="emailHelp" class="form-text text-muted"> <?=lang('Auth.weNeverShare')?></small>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input 
                                            type="password" 
                                            class="form-control form-control-user <?php if (session('errors.password')) : ?>is-invalid<?php endif ?>"
                                            name="password"
                                            placeholder="<?=lang('Auth.password')?>" 
                                            autocomplete="off">
                                    </div>
                                    <div class="col-sm-6">
                                        <input 
                                            type="password" 
                                            class="form-control form-control-user <?php if (session('errors.pass_confirm')) : ?>is-invalid<?php endif ?>" 
                                            name="pass_confirm"
                                            placeholder="<?=lang('Auth.repeatPassword')?>" 
                                            autocomplete="off">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-user btn-block" style="background-color: rgba(255, 52, 76, 0.78); color:black">
                                    <?=lang('Auth.register')?>
                                </button>
                                <!-- <hr>
                                <a href="index.html" class="btn btn-google btn-user btn-block">
                                    <i class="fab fa-google fa-fw"></i> Register with Google
                                </a>
                                <a href="index.html" class="btn btn-facebook btn-user btn-block">
                                    <i class="fab fa-facebook-f fa-fw"></i> Register with Facebook
                                </a> -->
                            </form>
                            <hr>
                            <!-- <div class="text-center">
                                <a class="small" href="forgot-password.html">Forgot Password?</a>
                            </div> -->
                            <div class="text-center"> 
                                <a class="small" href="<?= url_to('login') ?>"> <?=lang('Auth.alreadyRegistered')?> <?=lang('Auth.signIn')?> </a>
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