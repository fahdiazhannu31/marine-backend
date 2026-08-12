<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">CRUD Home</h1>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Table CRUD Home</h6>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                        <?php foreach ($home as $h) : ?>
                            <tr>
                                <td><?= $i++; ?>.</td>
                                <td><img src="<?= base_url() ?>assets_users/images/<?= $h['jb_photo']; ?>" width="150px"></td>
                                <td><?= $h['jb_title']; ?></td>
                                <td><?= $h['jb_desc']; ?></td>
                                <td>
                                    <a
                                        href="<?= base_url('/crudhomeedit/' . $h['id']); ?>" class="btn btn-warning">
                                        <i class="fas fa-pencil-alt mr-1"></i>Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?= $this->include('admin_layout/footer'); ?>