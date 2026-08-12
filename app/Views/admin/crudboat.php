<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">CRUD Boat</h1>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Table CRUD Boat</h6>
            <!-- Button untuk membuka modal -->
            <a class="btn btn-success btn-sm" data-toggle="modal" data-target="#addBoatModal">
                <i class="fas fa-plus"></i> Add Boat
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Boat Name</th>
                            <th>Capacity</th>
                            <th>Photos</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="boatTableBody">
                        <?php
                        $no = 1;
                        foreach ($boats as $bt) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $bt['boat_name']; ?></td>
                                <td><?= $bt['capacity']; ?></td>
                                <td>
                                    <div class="d-flex">
                                        <?php for ($i = 1; $i <= 5; $i++) :
                                            $photoKey = "photo$i";
                                            if (!empty($bt[$photoKey])) : ?>
                                                <img
                                                    src="<?= base_url('uploads/boats/' . $bt[$photoKey]); ?>"
                                                    alt="Boat Photo <?= $i; ?>"
                                                    class="img-thumbnail mr-2"
                                                    style="width: 100px; height: 100px; object-fit: cover;">
                                        <?php endif;
                                        endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-warning btn-sm editBoat"
                                        data-id="<?= $bt['id']; ?>"
                                        data-name="<?= $bt['boat_name']; ?>"
                                        data-capacity="<?= $bt['capacity']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button
                                        class="btn btn-danger btn-sm deleteBoat"
                                        data-id="<?= $bt['id']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
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

<!-- Modal untuk Add Boat -->
<div class="modal fade" id="addBoatModal" tabindex="-1" role="dialog" aria-labelledby="addBoatModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBoatModalLabel">Add New Boat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form untuk input data boat -->
                <form id="addBoatForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="boat_name">Boat Name</label>
                        <input type="text" class="form-control" id="boat_name" name="boat_name" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" required>
                    </div>
                    <!-- Input File untuk Foto -->
                    <div class="form-group">
                        <label for="photo1">Photo 1</label>
                        <input type="file" class="form-control-file" id="photo1" name="photo1">
                    </div>
                    <div class="form-group">
                        <label for="photo2">Photo 2</label>
                        <input type="file" class="form-control-file" id="photo2" name="photo2">
                    </div>
                    <div class="form-group">
                        <label for="photo3">Photo 3</label>
                        <input type="file" class="form-control-file" id="photo3" name="photo3">
                    </div>
                    <div class="form-group">
                        <label for="photo4">Photo 4</label>
                        <input type="file" class="form-control-file" id="photo4" name="photo4">
                    </div>
                    <div class="form-group">
                        <label for="photo5">Photo 5</label>
                        <input type="file" class="form-control-file" id="photo5" name="photo5">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Boat</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal untuk Edit Boat -->
<div class="modal fade" id="editBoatModal" tabindex="-1" role="dialog" aria-labelledby="editBoatModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBoatModalLabel">Edit Boat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editBoatForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_boat_id" name="boat_id">
                    <div class="form-group">
                        <label for="edit_boat_name">Boat Name</label>
                        <input type="text" class="form-control" id="edit_boat_name" name="boat_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_capacity">Capacity</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity" required>
                    </div>
                    <!-- Input File untuk Foto -->
                    <div class="form-group">
                        <label for="edit_photo1">Photo 1</label>
                        <input type="file" class="form-control-file" id="edit_photo1" name="photo1">
                    </div>
                    <div class="form-group">
                        <label for="edit_photo2">Photo 2</label>
                        <input type="file" class="form-control-file" id="edit_photo2" name="photo2">
                    </div>
                    <div class="form-group">
                        <label for="edit_photo3">Photo 3</label>
                        <input type="file" class="form-control-file" id="edit_photo3" name="photo3">
                    </div>
                    <div class="form-group">
                        <label for="edit_photo4">Photo 4</label>
                        <input type="file" class="form-control-file" id="edit_photo4" name="photo4">
                    </div>
                    <div class="form-group">
                        <label for="edit_photo5">Photo 5</label>
                        <input type="file" class="form-control-file" id="edit_photo5" name="photo5">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Boat</button>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <!-- Image will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div>

<?= $this->include('admin_layout/footer'); ?>


<script>
    $(document).ready(function() {
        // Handling the form submit with AJAX
        $('#addBoatForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: '<?= base_url('createBoat'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Success', 'Boat has been added!', 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'There was an error adding the boat.', 'error');
                }
            });
        });
    });

    $(document).ready(function() {
        // Event untuk membuka modal Edit Boat
        $(document).on('click', '.editBoat', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let capacity = $(this).data('capacity');

            $('#edit_boat_id').val(id);
            $('#edit_boat_name').val(name);
            $('#edit_capacity').val(capacity);

            $('#editBoatModal').modal('show');
        });

        // Submit form Edit Boat dengan AJAX
        $('#editBoatForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: '<?= base_url('updateBoat'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Success', 'Boat has been updated!', 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to update boat.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.deleteBoat', function() {
        var row = $(this).closest('tr');
        var id = $(this).data('id');

        // Show confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX request to delete the news item
                $.ajax({
                    url: '<?= base_url('deleteBoat'); ?>', // URL for delete action
                    type: 'POST',
                    data: {
                        id: id
                    }, // Send the ID of the news to be deleted
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                'Boat data has been deleted.',
                                'success'
                            );
                            // Remove the row from the table
                            row.remove();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Failed to delete boat.',
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Error deleting boat',
                        });
                    }
                });
            }
        });
    });

    // Optional: Add image preview modal
    $(document).ready(function() {
        $('.img-thumbnail').on('click', function() {
            $('#imagePreviewModal .modal-body').html(`<img src="${$(this).attr('src')}" class="img-fluid">`);
            $('#imagePreviewModal').modal('show');
        });
    });
</script>