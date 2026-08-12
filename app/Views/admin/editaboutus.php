<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<div class="container mt-4">
    <h2 class="mb-4">Edit About Us</h2>

    <form action="<?= base_url('/crudaboutusupdate/' . $aboutus['id']); ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="row">
            <!-- Jumbotron Photo -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Jumbotron Photo (optional)</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= !empty($aboutus['jb_photo']) ? base_url('assets_users/images/' . $aboutus['jb_photo']) : 'https://via.placeholder.com/300x200?text=No+Image'; ?>"
                             id="previewjb_photo" 
                             class="img-fluid rounded mb-3" 
                             style="max-height: 250px; object-fit: cover;">
                        <input type="file" name="jb_photo" class="form-control" id="jb_photo" onchange="previewImage('jb_photo', 'previewjb_photo')">
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Jumbotron Title -->
                <div class="form-group">
                    <label for="jb_title">Jumbotron Title</label>
                    <input type="text" name="jb_title" class="form-control" value="<?= esc($aboutus['jb_title']); ?>" required>
                </div>

                <!-- Jumbotron Description -->
                <div class="form-group">
                    <label for="jb_desc">Jumbotron Description</label>
                    <textarea name="jb_desc" class="form-control" rows="5" required><?= esc($aboutus['jb_desc']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- About Us Photo -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">About Us Photo (optional)</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= !empty($aboutus['as_photo']) ? base_url('assets_users/images/' . $aboutus['as_photo']) : 'https://via.placeholder.com/300x200?text=No+Image'; ?>"
                             id="previewas_photo" 
                             class="img-fluid rounded mb-3" 
                             style="max-height: 250px; object-fit: cover;">
                        <input type="file" name="as_photo" class="form-control" id="as_photo" onchange="previewImage('as_photo', 'previewas_photo')">
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- About Us Name -->
                <div class="form-group">
                    <label for="as_name">About Us Name</label>
                    <input type="text" name="as_name" class="form-control" value="<?= esc($aboutus['as_name']); ?>" required>
                </div>

                <!-- About Us Position -->
                <div class="form-group">
                    <label for="as_position">About Us Position</label>
                    <input type="text" name="as_position" class="form-control" value="<?= esc($aboutus['as_position']); ?>" required>
                </div>

                 <!-- About Us Title -->
                 <div class="form-group">
                    <label for="as_title">About Us Title</label>
                    <input type="text" name="as_title" class="form-control" value="<?= esc($aboutus['as_title']); ?>" required>
                </div>

                <!-- About Us Description -->
                <div class="form-group">
                    <label for="as_desc">About Us Description</label>
                    <textarea name="as_desc" class="form-control" rows="5" required><?= esc($aboutus['as_desc']); ?></textarea>
                </div>

                <!-- Buttons -->
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary mr-2">Update About Us</button>
                    <a href="<?= base_url('/crudaboutus'); ?>" class="btn btn-secondary me-2">Back</a>
                </div>
            </div>
        </div>
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
