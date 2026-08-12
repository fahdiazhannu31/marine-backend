<?= $this->extend('users_layout/page_layout'); ?>
<?= $this->section('content'); ?>

<!-- Start Hero Section -->
<div class="mt-5"></div>
<!-- End Hero Section -->
<?php if (session('error')): ?>
  <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>

<!-- content -->
<section class="py-5">
  <div class="container">
    <div class="row gx-5">
      <aside class="col-lg-6">
        <div class="text-center">
<img id="mainImage" src="<?= base_url('assets_users/images/' . $package['photo1']) ?>"
  class="img-fluid rounded border main-image" alt="Main Image">
        </div>

        <!-- Thumbnail -->
        <div class="d-flex justify-content-center mt-3">
          <div class="thumbnail mx-1">
            <img src="<?= base_url('assets_users/images/' . esc($package['photo1'])) ?>"
              class="rounded-2 border active" width="60" height="60" onclick="changeImage(this)">
          </div>
          <div class="thumbnail mx-1">
            <img src="<?= base_url('assets_users/images/' . esc($package['photo2'])) ?>"
              class="rounded-2 border" width="60" height="60" onclick="changeImage(this)">
          </div>
          <div class="thumbnail mx-1">
            <img src="<?= base_url('assets_users/images/' . esc($package['photo3'])) ?>"
              class="rounded-2 border" width="60" height="60" onclick="changeImage(this)">
          </div>
        </div>
      </aside>
      <main class="col-lg-6">
        <form action="<?= base_url('payments') ?>" method="POST">
          <!-- CSRF Token -->
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          <div class="ps-lg-3">
            <h4 class="title text-dark"><?= esc($package['title']) ?></h4>

            <div class="mt-3">
              <span class="h5" id="base_price">Rp. <?= number_format($package['price_per_pax'], 2, ',', '.') ?></span>
              <span class="text-muted">/per pax</span>
              <input type="hidden" id="price_per_pax" value="<?= $package['price_per_pax'] ?>">
              <input type="hidden" id="price_per_pax_weekend" value="<?= isset($package['price_per_pax_weekend']) ? esc($package['price_per_pax_weekend']) : esc($package['price_per_pax']) ?>">
            </div>

            <p><?= esc($package['description']) ?></p>

            <div class="row">
              <dt class="col-3">Pax Count:</dt>
              <dd class="col-9"><?= esc($package['pax_count']) ?></dd>

              <dt class="col-3">Status:</dt>
              <dd class="col-9"><?= esc($package['status']) ? 'Available' : 'Unavailable' ?></dd>
            </div>

            <hr />

            <!-- Trip Type Selection -->
            <div class="row mb-4">
              <div class="col-md-6 col-12">
                <label class="mb-2">Select Trip Type</label>
                <select id="trip_type" name="trip_type" class="form-select border border-secondary" style="height: 35px;">
                  <option value="">Select a trip type</option>
                  <option value="departure_only">Departure Only</option>
                  <option value="round_trip">Round Trip</option>
                </select>
              </div>
            </div>
            <!-- Date Picker for Schedule -->
            <div class="row mb-4">
              <div class="col-md-6 col-12">
                <label class="mb-2">Select Departure Date</label>
                <select id="schedule_departure_id" name="schedule_departure_id" class="form-select border border-secondary" style="height: 35px;">
                  <option value="">Select a date</option>
                  <?php foreach ($departure as $dpr) : ?>
                    <option value="<?= esc($dpr['id']); ?>" data-id="<?= esc($dpr['id']); ?>" data-slots="<?= esc($dpr['total_pax']); ?>" data-date="<?= $dpr['date']; ?>">
                        <?= esc($dpr['boat_name']); ?>
                      <?= date('d-m-Y', strtotime(esc($dpr['date']))) ?>
                      (<?= esc($dpr['total_pax']); ?> slots available)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>


            <!-- Return Date Picker (Initially Hidden) -->
            <div class="row mb-4 return-date-container" style="display: none;">
              <div class="col-md-6 col-12">
                <label class="mb-2">Select Return Date</label>
                <select id="schedule_return_id" name="schedule_return_id" class="form-select border border-secondary" style="height: 35px;">
                  <option value="">Select a date</option>
                  <?php foreach ($return as $rtn) : ?>
                    <option value="<?= esc($rtn['id']); ?>" data-id="<?= esc($rtn['id']); ?>" data-slots="<?= esc($rtn['total_pax']); ?>" data-date="<?= esc($rtn['date']); ?>"> <?= esc($rtn['boat_name']); ?>
                      <?= date('d-m-Y', strtotime(esc($rtn['date']))) ?>
                      (<?= esc($rtn['total_pax']); ?> slots available)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <input type="hidden" name="rute" id="rute" value="<?= esc($package['price_per_pax']) ?>">
            <input type="hidden" name="package_name" id="package_name" value="<?= esc($package['title']) ?>">
            <?php if (logged_in()): ?>
              <input type="hidden" name="user_id" id="user_id" value="<?= user()->id; ?>">
              <input type="hidden" name="fullname" id="fullname" value="<?= user()->fullname; ?>">
              <input type="hidden" name="email" id="email" value="<?= user()->email; ?>">
              <input type="hidden" name="phone" id="phone" value="<?= user()->phone; ?>">
            <?php else: ?>
              <input type="hidden" name="user_id" id="user_id" value="">
              <input type="hidden" name="fullname" id="fullname" value="">
              <input type="hidden" name="email" id="email" value="">
              <input type="hidden" name="phone" id="phone" value="">
            <?php endif; ?>
            <input type="hidden" name="package_id" id="package_id" value="<?= $package['id'] ?>">
            <input type="hidden" class="form-control" name="amount" id="amount" />
            <input type="hidden" id="hidden_schedule_departure_id" name="schedule_departure_id" value="">
            <input type="hidden" id="hidden_schedule_return_id" name="schedule_return_id" value="">
            <input type="hidden" id="current_price_per_pax" name="current_price_per_pax" value="<?= $package['price_per_pax'] ?>">


            <!-- Dropdown Pax -->
            <div class="row mb-4">
              <div class="col-md-4 col-6">
                <label class="mb-2">Pax</label>
                <select id="pax_count" name="jml_pax" class="form-select border border-secondary" style="height: 35px;">
                  <option value="">Select Pax</option>
                </select>
              </div>
            </div>

            <!-- Subtotal Price -->
            <div class="row">
              <div class="col-3">
                <strong>Subtotal:</strong>
              </div>
              <div class="col-9">
                <span id="subtotal_price">Rp. 0</span>
              </div>
            </div>
          </div>
          <?php if (!logged_in()): ?>
            <div class="alert alert-warning text-center" role="alert">
              Please log in to make a booking.
            </div>
            <button type="button" class="btn btn-secondary btn-block" disabled>
              Login Required
            </button>
          <?php elseif ($pendingTransaction): ?>
            <button type="button" class="btn btn-warning btn-block" disabled>
              You have a pending transaction. Please complete it before proceeding.
            </button>
          <?php elseif (esc($package['pax_count']) == 0): ?>
            <a href="https://wa.me/6281398744517?text=Halo, saya ingin memesan paket <?= urlencode($package['title']) ?> <?= urlencode(current_url()) ?>"
              target="_blank" class="btn btn-success btn-block">
              Booking via WhatsApp
            </a>
          <?php else: ?>
            <div class="d-flex gap-2">
              <!-- Tombol untuk Xendit Payment -->
              <button type="submit" name="payment_method" value="xendit" formaction="<?= base_url('payments') ?>" class="btn btn-primary flex-fill">
                Booking via Payment Gateway
              </button>

              <!-- Tombol untuk Manual Transfer -->
              <button type="submit" name="payment_method" value="manual" formaction="<?= base_url('payments-manual') ?>" class="btn btn-secondary flex-fill">
                Booking via Manual Transfer
              </button>
            </div>

          <?php endif; ?>

        </form>
      </main>
    </div>
  </div>
</section>
<script>
  document.getElementById('trip_type').addEventListener('change', function() {
    const tripType = this.value;
    const returnDateContainer = document.querySelector('.return-date-container');
    const returnDateDropdown = document.getElementById('schedule_return_id');

    if (tripType === 'departure_only') {
      returnDateContainer.style.display = 'none'; // Hide return date dropdown
      returnDateDropdown.value = ''; // Clear return date selection
    } else {
      returnDateContainer.style.display = 'block'; // Show return date dropdown
    }
    
    // Recalculate price after changing trip type
    calculatePrice();
  });

  // Menangani perubahan pada tanggal keberangkatan
  document.getElementById('schedule_departure_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const availableSlots = selectedOption.getAttribute('data-slots'); // Ambil data slots yang tersedia
    const dateStr = selectedOption.getAttribute('data-date'); // Ambil tanggal
    const paxDropdown = document.getElementById('pax_count');

    // Kosongkan dropdown Pax sebelumnya
    paxDropdown.innerHTML = '<option value="">Select Pax</option>';

    // Jika ada tanggal yang dipilih dan data slots ada
    if (availableSlots) {
      // Mengisi dropdown Pax berdasarkan jumlah slots
      for (let i = 1; i <= availableSlots; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `${i} Pax`;
        paxDropdown.appendChild(option);
      }
    }
    
    // Update price based on the day of week
    calculatePrice();
  });

  document.addEventListener('DOMContentLoaded', function() {
    const departureDropdown = document.getElementById('schedule_departure_id');
    const returnDropdown = document.getElementById('schedule_return_id');
    const hiddenDepartureField = document.getElementById('hidden_schedule_departure_id');
    const hiddenReturnField = document.getElementById('hidden_schedule_return_id');

    departureDropdown.addEventListener('change', function() {
      const selectedOption = departureDropdown.options[departureDropdown.selectedIndex];
      hiddenDepartureField.value = selectedOption.getAttribute('data-id') || '';
      console.log("Departure ID set to:", hiddenDepartureField.value); // Debugging line
    });

    returnDropdown.addEventListener('change', function() {
      const selectedOption = returnDropdown.options[returnDropdown.selectedIndex];
      hiddenReturnField.value = selectedOption.getAttribute('data-id') || '';
      console.log("Return ID set to:", hiddenReturnField.value); // Debugging line
      
      // Also update price when return date changes
      calculatePrice();
    });
  });

  function calculatePrice() {
    const regularPrice = parseFloat(document.getElementById('price_per_pax').value);
    const weekendPrice = parseFloat(document.getElementById('price_per_pax_weekend').value);
    const departureSelect = document.getElementById('schedule_departure_id');
    const returnSelect = document.getElementById('schedule_return_id');
    const tripType = document.getElementById('trip_type').value;
    let currentPrice = regularPrice;
    
    // Check if departure date is weekend
    if (departureSelect.selectedIndex > 0) {
      const departureDateStr = departureSelect.options[departureSelect.selectedIndex].getAttribute('data-date');
      const departureDate = new Date(departureDateStr);
      const dayOfWeek = departureDate.getDay();
      
      // Check if it's weekend (0 = Sunday, 6 = Saturday)
      if (dayOfWeek === 0 || dayOfWeek === 6) {
        currentPrice = weekendPrice;
        console.log("Weekend price applied for departure:", weekendPrice);
      }
    }
    
    // For round trip, also check if return date is weekend
    if (tripType === 'round_trip' && returnSelect.selectedIndex > 0) {
      const returnDateStr = returnSelect.options[returnSelect.selectedIndex].getAttribute('data-date');
      const returnDate = new Date(returnDateStr);
      const dayOfWeek = returnDate.getDay();
      
      // Check if it's weekend (0 = Sunday, 6 = Saturday)
      if (dayOfWeek === 0 || dayOfWeek === 6) {
        // If either departure or return is on weekend, use weekend price
        currentPrice = weekendPrice;
        console.log("Weekend price applied for return:", weekendPrice);
      }
    }
    
    // Update the displayed price and hidden input
    document.getElementById('base_price').textContent = `Rp. ${currentPrice.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}).replace(".", ",")}`;
    document.getElementById('current_price_per_pax').value = currentPrice;
    document.getElementById('rute').value = currentPrice;
    
    // Recalculate subtotal with the new price
    calculateSubtotal();
  }

  document.getElementById('pax_count').addEventListener('change', calculateSubtotal);
  document.getElementById('trip_type').addEventListener('change', calculateSubtotal);

  function calculateSubtotal() {
    const currentPrice = parseFloat(document.getElementById('current_price_per_pax').value);
    const pax = parseInt(document.getElementById('pax_count').value) || 0;
    const tripType = document.getElementById('trip_type').value;
    const departureSelect = document.getElementById('schedule_departure_id');
    const returnSelect = document.getElementById('schedule_return_id');

    let extraCharge = 0;
    let note = '';

    // Check for same day round trip
    if (tripType === 'round_trip' && departureSelect.selectedIndex > 0 && returnSelect.selectedIndex > 0) {
      const departureDateStr = departureSelect.options[departureSelect.selectedIndex].getAttribute('data-date');
      const returnDateStr = returnSelect.options[returnSelect.selectedIndex].getAttribute('data-date');
      
      const departureDate = new Date(departureDateStr);
      const returnDate = new Date(returnDateStr);
      
      // Check if departure and return are on the same day
      if (departureDate.toDateString() === returnDate.toDateString()) {
        extraCharge = 250000;
        note = '<br><small class="text-danger">*250k island credit required for one day trip / pax<br>Round trip same day charge 250k</small>';
      }
    }

    const subtotal = (currentPrice + extraCharge) * pax;

    // Tampilkan subtotal
    document.getElementById('subtotal_price').innerHTML = `Rp. ${subtotal.toLocaleString('id-ID')}${note}`;
    document.getElementById('amount').value = subtotal;
  }

  function changeImage(element) {
    // Ambil src dari gambar yang diklik
    const newSrc = element.src;

    // Ganti gambar utama
    document.getElementById("mainImage").src = newSrc;

    // Hapus class active dari semua thumbnail
    document.querySelectorAll('.thumbnail img').forEach(img => img.classList.remove('active'));

    // Tambahkan class active ke thumbnail yang diklik
    element.classList.add('active');
  }
</script>

<?= $this->endSection(); ?>