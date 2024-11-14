<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Detail Users</h1>

                    <div class="card mb-3" style="max-width: 540px;">
                        <div class="row no-gutters">
                            <div class="col-md-4">
                            <img src="<?= base_url('../assets/img/' . $user->user_image) ?>" alt="<?= $user->username ?>">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <h4><?= $user->username; ?></h4>
                                        </li>
                                        <?php if ($user->email) : ?>
                                        <li class="list-group-item"><?= $user->email; ?></li>
                                        <?php endif; ?>
                                        <?php if ($user->fullname) : ?>
                                        <li class="list-group-item"><?= $user->fullname; ?></li>
                                        <?php endif; ?>
                                        <li class="list-group-item">
                                            <span class="badge badge-<?= ($user->name == 'admin') ? 'primary' : 'success'; ?>"><?= $user->name; ?></span>
                                        </li>
                                        <li class="list-group-item">
                                            <small><a href="<?= base_url('/admin') ?>">&laquo; kembali ke admin</a></small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

    <?= $this->include('admin_layout/footer'); ?>