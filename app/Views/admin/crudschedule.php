<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">CRUD Schedule</h1>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Table CRUD Schedule</h6>
            <!-- Button untuk membuka modal -->
            <a class="btn btn-success btn-sm" data-toggle="modal" data-target="#addBoatModal">
                <i class="fas fa-plus"></i> Add Schedule
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Boat Name</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Total Pax</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php
                        $no = 1;
                        foreach ($schedules as $sch) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $sch['boat_name']; ?></td>
                                <td><?= $sch['type']; ?></td>
                                <td><?= $sch['date']; ?></td>
                                <td><?= $sch['total_pax']; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm editSchedule"
                                        data-id="<?= $sch['id']; ?>"
                                        data-name="<?= $sch['boat_name']; ?>"
                                        data-type="<?= $sch['type']; ?>"
                                        data-date="<?= $sch['date']; ?>"
                                        data-total_pax="<?= $sch['total_pax']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                    <button class="btn btn-danger btn-sm deleteSchedule" data-id="<?= $sch['id']; ?>">
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
                <h5 class="modal-title" id="addBoatModalLabel">Add New Schedule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form untuk input data boat -->
                <form id="addBoatForm">
                    <div class="form-group">
                        <label for="boat_name">Choose Boat</label>
                        <select class="form-control" name="boat_id" id="boat_id">
                            <option value="">--Choose Boat--</option>
                            <?php foreach ($boats as $bt): ?>
                                <option value="<?= $bt['id']; ?>"><?= $bt['boat_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select class="form-control" name="type" id="type">
                            <option value="">--Choose Type--</option>
                            <option value="DEPARTURE">DEPARTURE</option>
                            <option value="RETURN">RETURN</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="datetime-local" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="total_pax">Total Pax</label>
                        <input type="number" class="form-control" id="total_pax" name="total_pax" required readonly>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Edit Schedule -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editScheduleForm">
                    <input type="hidden" id="editScheduleId" name="id">
                    <div class="form-group">
                        <label for="editBoatId">Boat</label>
                        <select class="form-control" id="editBoatId" name="boat_id">
                            <?php foreach ($boats as $bt): ?>
                                <option value="<?= $bt['id']; ?>" <?= ($bt['boat_name'] == $sch['boat_name']) ? 'selected' : ''; ?>>
                                    <?= $bt['boat_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editType">Type</label>
                        <select class="form-control" id="editType" name="type">
                            <option value="DEPARTURE">DEPARTURE</option>
                            <option value="RETURN">RETURN</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDate">Date</label>
                        <input type="datetime-local" class="form-control" id="editDate" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="editTotalPax">Total Pax</label>
                        <input type="number" class="form-control" id="editTotalPax" name="total_pax" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<?= $this->include('admin_layout/footer'); ?>

<script>
    $(document).ready(function() {
        // Handle boat selection change
        $('#boat_id').on('change', function() {
            var boatId = $(this).val();

            if (boatId) {
                // Make AJAX request to get the boat's capacity
                $.ajax({
                    url: '<?= base_url('get-boat-capacity/'); ?>' + boatId, // Call the controller method
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.capacity) {
                            // Set the capacity in the Total Pax input field
                            $('#total_pax').val(response.capacity);
                        } else {
                            $('#total_pax').val(''); // Clear the field if no capacity is found
                        }
                    },
                    error: function() {
                        // Show error SweetAlert if something goes wrong
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'There was an error fetching the boat capacity.',
                        });
                    }
                });
            } else {
                // Clear the capacity field if no boat is selected
                $('#total_pax').val('');
            }
        });

        // Handling the form submit with AJAX
        $('#addBoatForm').on('submit', function(e) {
            e.preventDefault(); // Prevent the form from submitting the traditional way

            // Capture form data
            var formData = $(this).serialize();

            // Send data using AJAX
            $.ajax({
                url: '<?= base_url('createSchedule'); ?>', // Endpoint for creating boat
                type: 'POST',
                data: formData,
                dataType: 'json', // Expected response type
                success: function(response) {
                    if (response.status === 'success') {
                        // Append the new boat to the table
                        var newRow = '<tr><td>' + response.no + '</td><td>' + response.boat_name + '</td><td>' + response.capacity + '</td><td></td></tr>';
                        $('#scheduleTableBody').append(newRow);

                        // Show success SweetAlert
                        Swal.fire({
                            icon: 'success',
                            title: 'Schedule Added!',
                            text: 'Schedule has been successfully added.',
                            timer: 2000
                        });

                        window.location.reload();

                        // Close the modal
                        $('#addBoatModal').modal('hide');
                    } else {
                        // Show error SweetAlert
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message,
                        });
                    }
                },
                error: function() {
                    // Show error SweetAlert for any error
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'There was an error adding the schedule.',
                    });
                }
            });
        });
    });


    $(document).ready(function() {
        // Event untuk membuka modal Edit Schedule
        $(document).on('click', '.editSchedule', function() {
            let id = $(this).data('id');
            let boatName = $(this).data('name');
            let type = $(this).data('type');
            let date = $(this).data('date');
            let totalPax = $(this).data('total_pax');

            $('#editScheduleId').val(id);
            $('#editType').val(type);
            $('#editDate').val(date);
            $('#editTotalPax').val(totalPax);

            // Pilih kapal yang sesuai dalam dropdown
            $('#editBoatId option').each(function() {
                if ($(this).text() === boatName) {
                    $(this).prop('selected', true);
                }
            });

            $('#editScheduleModal').modal('show');
        });

        // Submit form Edit Schedule dengan AJAX
        $('#editScheduleForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();

            $.ajax({
                url: '<?= base_url("updateSchedule"); ?>', // Pastikan endpoint benar
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Schedule Updated!',
                            text: 'Schedule has been successfully updated.',
                            timer: 2000
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Failed to update schedule.'
                    });
                }
            });
        });
    });


    $(document).on('click', '.deleteSchedule', function() {
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
                    url: '<?= base_url('delete-schedule'); ?>', // URL for delete action
                    type: 'POST',
                    data: {
                        id: id
                    }, // Send the ID of the news to be deleted
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                'Schedule data has been deleted.',
                                'success'
                            );
                            // Remove the row from the table
                            row.remove();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Failed to delete schedule.',
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Error deleting schedule',
                        });
                    }
                });
            }
        });
    });
</script>