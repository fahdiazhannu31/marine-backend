<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<div class="container mt-4">
    <h2>Edit Schedule</h2>

    <form action="<?= base_url('/crudlistpackageupdate/' . $package['id']); ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Island Name</label>
            <input type="text" name="title" class="form-control" value="<?= esc($package['title']); ?>" required>
        </div>

        <div class="form-group">
            <label>Island Description</label>
            <input type="text" name="title" class="form-control" value="<?= esc($package['description']); ?>" required>
        </div>

        <div class="row">
        <!-- Photo 1 -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Photo 1 (optional)</h6>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($package['photo1'])) : ?>
                        <img src="<?= base_url('assets_users/images/' . $package['photo1']); ?>" 
                            id="previewPhoto1" 
                            class="img-fluid rounded mb-3" 
                            style="max-height: 250px; object-fit: cover;">
                    <?php else : ?>
                        <img src="https://via.placeholder.com/300x200?text=No+Image" 
                            id="previewPhoto1" 
                            class="img-fluid rounded mb-3" 
                            style="max-height: 250px; object-fit: cover;">
                    <?php endif; ?>
                    <input type="file" name="photo1" class="form-control" id="photo1" onchange="previewImage('photo1', 'previewPhoto1')">
                </div>
            </div>
        </div>

        <!-- Photo 2 -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Photo 2 (optional)</h6>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($package['photo2'])) : ?>
                        <img src="<?= base_url('assets_users/images/' . $package['photo2']); ?>" 
                            id="previewPhoto2" 
                            class="img-fluid rounded mb-3" 
                            style="max-height: 250px; object-fit: cover;">
                    <?php else : ?>
                        <img src="https://via.placeholder.com/300x200?text=No+Image" 
                            id="previewPhoto2" 
                            class="img-fluid rounded mb-3" 
                            style="max-height: 250px; object-fit: cover;">
                    <?php endif; ?>
                    <input type="file" name="photo2" class="form-control" id="photo2" onchange="previewImage('photo2', 'previewPhoto2')">
                </div>
            </div>
        </div>

        <!-- Photo 3 -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">Photo 3 (optional)</h6>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($package['photo3'])) : ?>
                        <img src="<?= base_url('assets_users/images/' . $package['photo3']); ?>" 
                            id="previewPhoto3" 
                            class="img-fluid rounded mb-3" 
                            style="max-height: 250px; object-fit: cover;">
                    <?php else : ?>
                        <img src="https://via.placeholder.com/300x200?text=No+Image" 
                            id="previewPhoto3" 
                            class="img-fluid rounded mb-3" 
                            style="max-height: 250px; object-fit: cover;">
                    <?php endif; ?>
                    <input type="file" name="photo3" class="form-control" id="photo3" onchange="previewImage('photo3', 'previewPhoto3')">
                </div>
            </div>
        </div>
    </div>

        <div class="form-group">
            <label>Island Price Per Pax</label>
            <input type="number" name="price_per_pax" class="form-control" value="<?= esc($package['price_per_pax']); ?>" required>
        </div>

        <div class="form-group">
            <label>Island Pax Count</label>
            <input type="number" name="pax_count" class="form-control" value="<?= esc($package['pax_count']); ?>" required>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="active" <?= ($package['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?= ($package['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Package</button>
        <a href="<?= base_url('/crudlistpackage'); ?>" class="btn btn-secondary">Back</a>
    </form>
</div>

<?= $this->include('admin_layout/footer'); ?>

<!-- Script Preview Gambar -->
<script>
    function previewImage(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
