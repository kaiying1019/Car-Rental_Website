// Run the code after all page elements have loaded.
document.addEventListener('DOMContentLoaded', function() {
  // Open or close the mobile navigation menu.
  const hamburger = document.getElementById('hamburger');
  const nav = document.getElementById('nav');
  if (hamburger && nav) {
    hamburger.addEventListener('click', function() {
      nav.classList.toggle('open');
    });
  }

  // Find the cart rows and checkout button on the cart page.
  const cartRows = document.querySelectorAll('.cart-table tbody tr');
  const checkoutButton = document.getElementById('checkout-button');
  if (cartRows.length && checkoutButton) {
    const vehicleCount = document.getElementById('selected-vehicle-count');
    const daysTotal = document.getElementById('selected-days-total');
    const pricePerDayTotal = document.getElementById('selected-price-per-day');
    const depositTotal = document.getElementById('selected-deposit');
    const grandTotal = document.getElementById('selected-grand-total');

    // Calculate and display the totals for the selected vehicles.
    const updateCartSummary = function() {
      let selectedCount = 0;
      let selectedDays = 0;
      let selectedPricePerDay = 0;
      let selectedPrice = 0;

      cartRows.forEach(function(row) {
        const checkbox = row.querySelector('input[name="selected_cars[]"]');
        const daysInput = row.querySelector('.days-input');
        const subtotal = row.querySelector('[data-subtotal]');
        // Limit the rental period to a maximum of 90 days.
        const days = Math.max(1, Math.min(90, parseInt(daysInput.value, 10) || 1));
        const pricePerDay = parseFloat(subtotal.dataset.pricePerDay) || 0;
        const rowTotal = days * pricePerDay;

        daysInput.value = days;
        subtotal.textContent = 'RM' + rowTotal.toFixed(2);

        if (checkbox.checked) {
          selectedCount += 1;
          selectedDays += days;
          selectedPricePerDay += pricePerDay;
          selectedPrice += rowTotal;
        }
      });

      vehicleCount.textContent = selectedCount;
      daysTotal.textContent = selectedDays + ' day(s)';
      pricePerDayTotal.textContent = 'RM' + selectedPricePerDay.toFixed(2);
      // Add a refundable RM200 deposit for every selected vehicle.
      const selectedDeposit = selectedCount * 200;
      depositTotal.textContent = 'RM' + selectedDeposit.toFixed(2);
      grandTotal.textContent = 'RM' + (selectedPrice + selectedDeposit).toFixed(2);
      checkoutButton.disabled = selectedCount === 0;
    };

    // Update the summary whenever a vehicle or rental period changes.
    cartRows.forEach(function(row) {
      row.querySelector('input[name="selected_cars[]"]').addEventListener('change', updateCartSummary);
      row.querySelector('.days-input').addEventListener('input', updateCartSummary);
      row.querySelector('.days-input').addEventListener('change', updateCartSummary);
    });

    updateCartSummary();
    }
});
