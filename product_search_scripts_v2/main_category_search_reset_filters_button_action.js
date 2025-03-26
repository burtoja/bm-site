/**
 * Handles the action of the reset button which
 * resets all filters on the page
 */
function resetFilters() {
    const form = document.getElementById('product-filter-form');

    // Reset checkboxes and radios
    form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(input => {
        input.checked = input.defaultChecked;
    });

    // Clear number inputs (like min/max price)
    form.querySelectorAll('input[type="number"]').forEach(input => {
        input.value = '';
    });

    // Hide custom price fields again
    form.querySelectorAll('.custom-price-fields').forEach(field => {
        field.style.display = 'none';
    });
}