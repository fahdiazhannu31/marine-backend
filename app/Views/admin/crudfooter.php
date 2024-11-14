<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">CRUD Footer</h1>

                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Table CRUD Footer</h6>
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
                                            <th>Hari Operasional</th>
                                            <th>Nomor Ponsel</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                          <td>1.</td>
                                          <td>Senin - Minggu 07:00 - 22:00</td>
                                          <td>085728823456</td>
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
                                            <th>Hari Operasional</th>
                                            <th>Nomor Ponsel</th>
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