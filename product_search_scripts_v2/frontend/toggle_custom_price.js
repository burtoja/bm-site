/**
 * Dictates behavior of custom price radio button.
 * When button is selected, the number boxes appear
 * for user to enter min/max prices.  When deselected, the boxes
 * disappear.
 *
 * @param radioBtn
 */
function toggleCustomPrice(radioBtn) {
    const container = radioBtn.closest('.filter-options');
    const customFields = container.querySelector('.custom-price-fields');
    const allRadios = container.querySelectorAll('input[type="radio"]');

    allRadios.forEach(radio => {
        if (radio.value !== 'custom') {
            radio.addEventListener('click', () => {
                customFields.style.display = 'none';
            });
        }
    });

    if (radioBtn.value === 'custom') {
        customFields.style.display = 'block';
    }
}

// On page load, add listeners for all "custom" price radios
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('input[type="radio"][value="custom"]').forEach(radio => {
        radio.addEventListener('click', function () {
            toggleCustomPrice(this);
        });
    });

    // Add fallback listeners for non-custom to hide custom fields
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        if (radio.value !== 'custom') {
            radio.addEventListener('click', function () {
                const container = this.closest('.filter-options');
                const customFields = container.querySelector('.custom-price-fields');
                if (customFields) {
                    customFields.style.display = 'none';
                }
            });
        }
    });
});