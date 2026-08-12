<?= $this->include('admin_layout/sidebar'); ?>
<?= $this->include('admin_layout/topbar'); ?>

<body>
    <div class="container">
        <h1 class="text-center mt-5">Bus Seat Layout</h1>
        <div class="row justify-content-center">
            <!-- Driver seat -->
            <div class="col-12 text-center mb-4">
                <div class="seat occupied mx-auto">Driver</div>
            </div>

            <!-- Baris 1 -->
            <div class="col-6 d-flex justify-content-end">
                <div class="seat available">1A</div>
                <div class="seat available">1B</div>
            </div>
            <div class="col-6 d-flex justify-content-start">
                <div class="seat available">1C</div>
                <div class="seat available">1D</div>
            </div>

            <!-- Baris 2 -->
            <div class="col-6 d-flex justify-content-end">
                <div class="seat available">2A</div>
                <div class="seat occupied">2B</div>
            </div>
            <div class="col-6 d-flex justify-content-start">
                <div class="seat available">2C</div>
                <div class="seat available">2D</div>
            </div>

            <!-- Baris 3 -->
            <div class="col-6 d-flex justify-content-end">
                <div class="seat available">3A</div>
                <div class="seat available">3B</div>
            </div>
            <div class="col-6 d-flex justify-content-start">
                <div class="seat available">3C</div>
                <div class="seat occupied">3D</div>
            </div>

            <!-- Baris 4 -->
            <div class="col-6 d-flex justify-content-end">
                <div class="seat available">4A</div>
                <div class="seat available">4B</div>
            </div>
            <div class="col-6 d-flex justify-content-start">
                <div class="seat available">4C</div>
                <div class="seat available">4D</div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>