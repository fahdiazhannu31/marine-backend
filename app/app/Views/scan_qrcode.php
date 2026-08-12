<!DOCTYPE html>
<html lang="en">

<head>
    <title>Attendance Check</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Custom Styles */
        .container-fluid {
            margin-top: 30px;
        }

        .qr-reader-container {
            height: 400px;
            background-color: #f3f3f3;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .biodata-container {
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 20px;
            height: 400px;
            overflow-y: auto;
        }

        .row {
            display: flex;
            align-items: center;
        }

        .col {
            padding: 20px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- QR Code Reader Section -->
            <div class="col-md-6 col-sm-12">
                <div class="qr-reader-container" id="qr-reader"></div>
            </div>

            <!-- Biodata Section -->
            <div class="col-md-6 col-sm-12" id="biodata">
                <!-- Biodata will be dynamically injected here -->
                <div class="biodata-container">
                    <p class="text-center">Scan a QR Code to see the details here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Load script library jquery -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Load script library html5-qrcode -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <script type="text/javascript">
        var lastResult = ""; // Store the last result to prevent re-scanning the same QR
        var scanInProgress = false; // To prevent multiple scans while processing

        // Function triggered when QR code is successfully scanned
        function onScanSuccess(decodedText, decodedResult) {
            if (scanInProgress) return; // If scan is in progress, ignore the new scan
            scanInProgress = true; // Mark scanning as in progress

            if (decodedText === lastResult) return; // Skip if the same QR is scanned again

            lastResult = decodedText; // Store the decoded text for future checks

            // Log the decoded QR code data to the console
            console.log("Decoded QR Code Data:", decodedText);

            // Send the barcode data to the server via AJAX
            $.ajax({
                url: "/qrcode_scanner/attendance", // Ensure this URL matches your backend route
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    barcode: decodedText
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message using SweetAlert
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function() {
                            // Reload the page after the user acknowledges the success alert
                            window.location.reload();
                        });
                    } else if (response.status === 'info') {
                        // Show info if the QR code has already been scanned
                        Swal.fire({
                            title: 'Already Scanned!',
                            text: response.message,
                            icon: 'info',
                            confirmButtonText: 'OK'
                        }).then(function() {
                            // Reload the page after the user acknowledges the info alert
                            window.location.reload();
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            title: 'Failed!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        }).then(function() {
                            // Reload the page after the user acknowledges the error alert
                            window.location.reload();
                        });
                    }
                },

                error: function() {
                    // Show error message in case of request failure
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error processing the request. Please try again later.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Reload the page after the user acknowledges the error alert
                        window.location.reload();
                    });
                }

            });

            // Stop scanning after processing the result
            html5QrcodeScanner.pause();
        }

        // Initialize QR code scanner
        var html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", {
                fps: 10,
                qrbox: 250
            }
        );
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>

</html>