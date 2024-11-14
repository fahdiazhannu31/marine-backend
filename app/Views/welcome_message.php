<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Payment</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Menambahkan CDN jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Menambahkan CDN Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">

    <!-- Menggunakan CDN Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Menambahkan CDN Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Create Payment
        </h2>

        <form action="<?= base_url('payments') ?>" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

            <!-- Route Selection -->
            <div class="form-group">
                <label for="rute">Route</label>
                <select class="form-control" name="rute" id="rute" required>
                    <option value="10000" data-nama-rute="Pulau Sepa">Rute A</option>
                </select>
            </div>
            <!-- Hidden field for Route Name -->
            <input type="hidden" name="nama_rute" id="nama_rute" value="Pulau Sepa">
            <input type="hidden" name="fullname" id="fullname" value="<?= user()->fullname; ?>">
            <input type="hidden" name="email" id="email" value="<?= user()->email; ?>">
            <input type="hidden" name="phone" id="phone" value="<?= user()->phone; ?>">

            <!-- Number of Pax (Seats) Input -->
            <div class="form-group">
                <label for="jml_pax">Number of Pax (Seats)</label>
                <input type="number" class="form-control" name="jml_pax" id="jml_pax" required />
            </div>

            <!-- Display Total Price -->
            <div class="form-group">
                <label for="total_price">Total Price</label>
                <input type="text" class="form-control" name="amount" id="total_price" required readonly />
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-block">Submit</button>
        </form>
    </div>

    <script>
        // Update total price and route name based on selected route and pax
        document.getElementById('rute').addEventListener('change', updateTotalPrice);
        document.getElementById('jml_pax').addEventListener('input', updateTotalPrice);

        function updateTotalPrice() {
            const ruteElement = document.getElementById('rute');
            const rutePrice = parseInt(ruteElement.value, 10); // Ensure the route price is treated as a number
            const routeName = ruteElement.options[ruteElement.selectedIndex].getAttribute('data-nama-rute'); // Get route name

            // Set route name to hidden input field
            document.getElementById('nama_rute').value = routeName;

            const jml_pax = parseInt(document.getElementById('jml_pax').value, 10) || 0;

            // Calculate total price based on selected route price and number of pax
            const totalPrice = rutePrice * jml_pax;

            // Set the raw numeric value (no formatting with commas)
            document.getElementById('total_price').value = totalPrice;

            // Optionally, if you still want to display the formatted price in the UI, you can use toLocaleString for visual display only
            document.getElementById('total_price').setAttribute('data-formatted', totalPrice.toLocaleString());
        }

        // Initial price calculation when page loads
        updateTotalPrice();
    </script>
</body>

</html>