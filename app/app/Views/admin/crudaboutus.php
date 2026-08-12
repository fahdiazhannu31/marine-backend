<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">CRUD Aboutus</h1>

                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Table CRUD Aboutus</h6>
                            </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Jumbotron Photo</th>
                                            <th>Jumbotron Title</th>
                                            <th>Jumbotron Description</th>
                                            <th>About Us Title</th>
                                            <th>About Us Description</th>
                                            <th>About Us Photo</th>
                                            <th>About Us Name</th>
                                            <th>About Us Position</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i = 1; ?>
                                    <?php foreach ($aboutus as $as) : ?>
                                        <tr>
                                          <td><?= $i++; ?>.</td>
                                          <td><img src="<?= base_url() ?>assets_users/images/<?= $as['jb_photo']; ?>" width="150px"></td>
                                          <td><?= $as['jb_title']; ?></td>
                                          <td><?= $as['jb_desc']; ?></td>
                                          <td><?= $as['as_title']; ?></td>
                                          <td><?= $as['as_desc']; ?></td>
                                          <td><img src="<?= base_url() ?>assets_users/images/<?= $as['as_photo']; ?>" width="100px"></td>
                                          <td><?= $as['as_name']; ?></td>
                                          <td><?= $as['as_position']; ?></td>
                                          <td>
                                          <a 
                                            href="<?= base_url('/crudaboutusedit/' . $as['id']); ?>" class="btn btn-warning">
                                            <i class="fas fa-pencil-alt mr-1"></i>Edit
                                          </a>
                                          </td>
                                        </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>No</th>
                                            <th>Jumbotron Photo</th>
                                            <th>Jumbotron Title</th>
                                            <th>Jumbotron Description</th>
                                            <th>About Us Title</th>
                                            <th>About Us Description</th>
                                            <th>About Us Photo</th>
                                            <th>About Us Name</th>
                                            <th>About Us Position</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>  
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

    <?= $this->include('admin_layout/footer'); ?>