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
                                <a class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Data
                                </a>
                            </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Gambar Jumbotron</th>
                                            <th>Judul Jumbotron</th>
                                            <th>Deskripsi Jumbotron</th>
                                            <th>Judul About Us</th>
                                            <th>Deskripsi About Us</th>
                                            <th>Gambar About Us</th>
                                            <th>Nama About Us</th>
                                            <th>Jabatan About Us</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                          <td>1.</td>
                                          <td><img src="<?= base_url() ?>assets_users/images/jumbotron.webp" width="150px"></td>
                                          <td>Tentang Kami</td>
                                          <td>A hidden paradise awaits</td>
                                          <td>Tentang Kami</td>
                                          <td>“Welcome to Nama Marine! Nestled in the heart of the stunning Sepa Island, part of the beautiful Pulau Seribu archipelago in Jakarta, Indonesia, we are dedicated to providing exceptional transportation and accommodation services for those seeking to explore this tropical paradise.”</td>
                                          <td><img src="<?= base_url() ?>assets/img/default.svg" width="100px"></td>
                                          <td>Matthew</td>
                                          <td>CEO, Co-Founder, XYZ Inc.</td>
                                          <td>
                                          <a class="btn btn-primary btn-sm m-1">
                                            <i class="fas fa-pencil-alt"></i> Edit
                                          </a>
                                          <a class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                          </a>
                                          </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>No</th>
                                            <th>Gambar Jumbotron</th>
                                            <th>Judul Jumbotron</th>
                                            <th>Deskripsi Jumbotron</th>
                                            <th>Judul About Us</th>
                                            <th>Deskripsi About Us</th>
                                            <th>Gambar About Us</th>
                                            <th>Nama About Us</th>
                                            <th>Jabatan About Us</th>
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