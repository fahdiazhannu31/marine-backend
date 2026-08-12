<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<div class="container mt-4" style="margin-bottom: 40rem">

<h2>Edit Footer</h2>

<form action="<?= base_url('/crudfooterupdate/' . $footer['id']); ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="form-group">
        <label>Operating Days</label>
        <input type="text" name="day_op" class="form-control" value="<?= esc($footer['day_op']) ?>">
    </div>

    <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= esc($footer['phone']) ?>">
    </div>

    <div class="form-group">
        <label>Copyright</label>
        <input type="text" name="copyright" class="form-control" value="<?= esc($footer['copyright']) ?>">
    </div>

    <button type="submit" class="btn btn-primary">Update Footer</button>
    <a href="/crudfooter" class="btn btn-secondary">Back</a>
</form>
</div>


<?= $this->include('admin_layout/footer'); ?>

