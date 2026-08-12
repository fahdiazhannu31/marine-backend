<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<div class="container mt-4">
    <h2 class="mb-4">Edit Home</h2>

    <form action="<?= base_url('/crudhomeupdate/' . $home['id']); ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="row">
            <!-- Jumbotron Photo -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Jumbotron Photo (optional)</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= !empty($home['jb_photo']) ? base_url('assets_users/images/' . $home['jb_photo']) : 'https://via.placeholder.com/300x200?text=No+Image'; ?>"
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
                    <input type="text" name="jb_title" class="form-control" value="<?= esc($home['jb_title']); ?>" required>
                </div>

                <!-- Jumbotron Description -->
                <div class="form-group">
                    <label for="jb_desc">Jumbotron Description</label>
                    <textarea name="jb_desc" class="form-control" rows="5" required><?= esc($home['jb_desc']); ?></textarea>
                </div>

                <!-- Buttons -->
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary mr-2">Update Home</button>
                    <a href="<?= base_url('/crudhome'); ?>" class="btn btn-secondary me-2">Back</a>
                </div>
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
