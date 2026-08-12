<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <style>
        body {
            background-color: #f04c5c;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .card-qr {
            position: relative;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .checkmark-icon {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 40px;
            height: 40px;
            background-color: #28a745;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .qr-frame img {
            border-radius: 8px;
        }

        .btn-group-custom {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="card card-qr">
        <!-- Floating checkmark icon -->
        <div class="checkmark-icon">
            <i class="fas fa-check"></i>
        </div>

        <h2 class="text-success">Successful Payment!</h2>
        <p class="lead">Thank you, your payment has been successfully processed.</p>

        <?php if (!empty($qrcode) && !empty($qrcode['qr_code'])): ?>
            <!-- QR Code Frame and Save QR Button -->
            <div class="qr-frame">
                <img src="<?= base_url($qrcode['qr_code']); ?>" alt="QR Code Ticket" class="img-fluid" style="max-width: 200px;">
            </div>

            <div class="card card-qr-sm bg-light mt-4">
                <h5>Scan QR Code</h5>
                This QR code will be used for access to the boat and check-in at the destination. Please save it on your device.
            </div>

            <div class="btn-group-custom">
                <a href="<?= base_url($qrcode['qr_code']); ?>" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Save QR Code
                </a>
                <button id="generateQRCodeBtn" class="btn btn-success" disabled>
                    <i class="fas fa-check-circle"></i> QR Code Generated
                </button>
            </div>
        <?php else: ?>
            <!-- Generate QR Code Button -->
            <button id="generateQRCodeBtn" class="btn btn-success mt-4">
                <i class="fas fa-qrcode"></i> Generate QR Code
            </button>
            <p class="mt-3 text-muted">No QR code available.</p>
        <?php endif; ?>
        <br>
        <a href="<?= base_url('/') ?>" class="text-primary">
            Back to Homepage
        </a>
    </div>

    <script>
        // Event listener for Generate QR Code button
        document.getElementById('generateQRCodeBtn').addEventListener('click', function() {
            fetch('<?= base_url('/generate-qrcode') ?>', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'QR Code generated!',
                            text: data.success,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Reload page to display the new QR code
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'An error occurred while generating the QR Code.',
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to generate QR Code. Please try again later.',
                    });
                });
        });
    </script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>