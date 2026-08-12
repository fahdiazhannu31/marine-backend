<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin Nama Marine</title>

    <!-- Custom fonts for this template-->
    <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?= base_url('assets/css/sb-admin-2.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <!-- Custom styles for this page -->
    <link href="<?= base_url() ?>assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php $currentUrl = uri_string(); ?>

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-text mx-3 mt-2"><sup>Admin Nama Marine</sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?= ($currentUrl == 'admin') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('admin'); ?>">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users Data</span>
                </a>
            </li>
            <li class="nav-item <?= ($currentUrl == 'reservation-data') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('reservation-data'); ?>">
                    <i class="fas fa-fw fa-list-alt"></i>
                    <span>Reservation Data</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Content Management
            </div>

            <li class="nav-item <?= ($currentUrl == 'crudhome') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('crudhome'); ?>">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Manage Home</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentUrl == 'crudaboutus') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('crudaboutus'); ?>">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Manage About Us</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentUrl == 'crudlistpackage') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('crudlistpackage'); ?>">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Manage Package</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentUrl == 'crudfooter') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('crudfooter'); ?>">
                    <i class="fas fa-fw fa-info"></i>
                    <span>Manage Info</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentUrl == 'crudboat') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('crudboat'); ?>">
                    <i class="fas fa-fw fa-ship"></i>
                    <span>Manage Boat</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentUrl == 'crudschedule') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('crudschedule'); ?>">
                    <i class="fas fa-fw fa-calendar"></i>
                    <span>Manage Schedule</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Back to User's Page
            </div>

            <li class="nav-item">
                <a class="nav-link" href="<?= base_url(); ?>">
                    <i class="fas fa-fw fa-eye"></i>
                    <span>See Website</span>
                </a>
            </li>
        </ul>


        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

        </ul>
        <!-- End of Sidebar -->