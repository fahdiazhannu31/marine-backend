<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">CRUD List Package</h1>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Table CRUD List Package</h6>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#tambahdataModal" data-whatever="Tambah Data">
                <i class="fas fa-plus"></i> Add List Package
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Island Name</th>
                            <th>Island Description</th>
                            <th>Island Photo</th>
                            <th>Island Price Per Pax Weekday</th>
                             <th>Island Price Per Pax Weekend</th>
                            <th>Island Pax Count</th>
                            <th>Island Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                   <tbody>
                        <?php $i = 1; ?>
                        <?php foreach ($packages as $p) : ?>
                            <tr>
                                <td><?= $i++; ?>.</td>
                                <td><?= $p['title']; ?></td>
                                <td><?= $p['description']; ?></td>
                                <td>
                                    1. <img class="m-2" src="<?= base_url() ?>assets_users/images/<?= $p['photo1']; ?>" width="150px"> <br>
                                    2. <img class="m-2" src="<?= base_url() ?>assets_users/images/<?= $p['photo2']; ?>" width="150px"> <br>
                                    3. <img class="m-2" src="<?= base_url() ?>assets_users/images/<?= $p['photo3']; ?>" width="150px">
                                </td>
                                <td>Rp. <?= number_format($p['price_per_pax'], 2, ',', '.'); ?></td>
                                <td>Rp. <?= number_format($p['price_per_pax_weekend'], 2, ',', '.'); ?></td>
                                <td><?= $p['pax_count']; ?></td>
                                <td><?= $p['status']; ?></td>
                                <td>
                                    <a
                                        href="<?= base_url('/crudlistpackageedit/' . $p['id']); ?>" class="btn btn-warning m-1">
                                        <i class="fas fa-pencil-alt mr-1"></i>Edit
                                    </a>
                                    <a
                                        href="<?= base_url('/crudlistpackagedelete/' . $p['id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash-alt mr-1"></i>Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<!-- Modal Tambah Data -->
<div class="modal fade" id="tambahdataModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add List Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="/crudlistpackageadd" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <!-- Input Island Name -->
                    <div class="form-group">
                        <label for="title" class="col-form-label">Island Name</label>
                        <input type="text"
                            class="form-control <?= ($validation->hasError('title')) ? 'is-invalid' : ''; ?>"
                            name="title"
                            id="title"
                            value="<?= old('title'); ?>">
                        <div class="invalid-feedback">
                            <?= $validation->getError('title') ?>
                        </div>
                    </div>

                    <!-- Input Island Description -->
                    <div class="form-group">
                        <label for="description" class="col-form-label">Island Description</label>
                        <textarea class="form-control <?= ($validation->hasError('description')) ? 'is-invalid' : ''; ?>"
                            name="description"
                            id="description"><?= old('description'); ?></textarea>
                        <div class="invalid-feedback">
                            <?= $validation->getError('description') ?>
                        </div>
                    </div>

                    <!-- Input Island Photos -->
                    <div class="form-group">
                        <label for="photo1" class="col-form-label">Island Photo 1</label>
                        <div class="custom-file mb-3">
                            <input type="file"
                                class="custom-file-input <?= ($validation->hasError('photo1')) ? 'is-invalid' : ''; ?>"
                                name="photo1"
                                id="photo1"
                                onchange="previewImage('photo1', 'imagePreview1')">
                            <label class="custom-file-label" for="photo1">Choose file</label>
                            <div class="invalid-feedback">
                                <?= $validation->getError('photo1') ?>
                            </div>
                        </div>
                        <img id="imagePreview1" src="" alt="Preview Image 1" class="img-thumbnail" style="max-width: 200px; display: none;">
                    </div>

                    <div class="form-group">
                        <label for="photo2" class="col-form-label">Island Photo 2</label>
                        <div class="custom-file mb-3">
                            <input type="file"
                                class="custom-file-input <?= ($validation->hasError('photo2')) ? 'is-invalid' : ''; ?>"
                                name="photo2"
                                id="photo2"
                                onchange="previewImage('photo2', 'imagePreview2')">
                            <label class="custom-file-label" for="photo2">Choose file</label>
                            <div class="invalid-feedback">
                                <?= $validation->getError('photo2') ?>
                            </div>
                        </div>
                        <img id="imagePreview2" src="" alt="Preview Image 2" class="img-thumbnail" style="max-width: 200px; display: none;">
                    </div>

                    <div class="form-group">
                        <label for="photo3" class="col-form-label">Island Photo 3</label>
                        <div class="custom-file mb-3">
                            <input type="file"
                                class="custom-file-input <?= ($validation->hasError('photo3')) ? 'is-invalid' : ''; ?>"
                                name="photo3"
                                id="photo3"
                                onchange="previewImage('photo3', 'imagePreview3')">
                            <label class="custom-file-label" for="photo3">Choose file</label>
                            <div class="invalid-feedback">
                                <?= $validation->getError('photo3') ?>
                            </div>
                        </div>
                        <img id="imagePreview3" src="" alt="Preview Image 3" class="img-thumbnail" style="max-width: 200px; display: none;">
                    </div>

                    <!-- Input Island Price Per Pax -->
                    <div class="form-group">
                        <label for="price_per_pax" class="col-form-label">Island Price Per Pax</label>
                        <input type="text"
                            class="form-control <?= ($validation->hasError('price_per_pax')) ? 'is-invalid' : ''; ?>"
                            name="price_per_pax"
                            id="price_per_pax"
                            value="<?= old('price_per_pax'); ?>">
                        <div class="invalid-feedback">
                            <?= $validation->getError('price_per_pax') ?>
                        </div>
                    </div>

                    <!-- Input Island pax_count -->
                    <div class="form-group">
                        <label for="pax_count" class="col-form-label">Island Pax Count</label>
                        <input type="text"
                            class="form-control <?= ($validation->hasError('pax_count')) ? 'is-invalid' : ''; ?>"
                            name="pax_count"
                            id="pax_count"
                            value="<?= old('pax_count'); ?>">
                        <div class="invalid-feedback">
                            <?= $validation->getError('pax_count') ?>
                        </div>
                    </div>

                    <!-- Input Island Status -->
                    <div class="form-group">
                        <label for="status" class="col-form-label">Island Status</label>
                        <select name="status" id="status" class="form-control <?= ($validation->hasError('status')) ? 'is-invalid' : ''; ?>">
                            <option value="active" <?= (old('status') == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?= (old('status') == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="invalid-feedback">
                            <?= $validation->getError('status'); ?>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End Modal Tambah Data -->

<?= $this->include('admin_layout/footer'); ?>

<script>
    // Fungsi untuk menampilkan preview gambar
    function previewImage(inputId, previewId) {
        const input = document.getElementById(inputId);
        const imagePreview = document.getElementById(previewId);
        const fileLabel = input.nextElementSibling;

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };

            reader.readAsDataURL(input.files[0]);
            fileLabel.textContent = input.files[0].name;
        }
    }
</script>