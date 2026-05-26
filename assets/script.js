// Eduvos Marketplace – script.js

// --- Image preview on create-listing form ---
const imageInput = document.getElementById('image');
const imagePreview = document.getElementById('image-preview');

if (imageInput && imagePreview) {
    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            imagePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });
}

// --- Confirm dangerous actions (e.g. delete listing) ---
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        const msg = el.dataset.confirm || 'Are you sure?';
        if (!confirm(msg)) e.preventDefault();
    });
});

// --- Auto-hide flash messages after 4 s ---
const flash = document.querySelector('[data-flash]');
if (flash) {
    setTimeout(() => {
        flash.style.transition = 'opacity .4s';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 400);
    }, 4000);
}

// --- Toggle "Wanted" price label in create-listing ---
const priceTypeInputs = document.querySelectorAll('input[name="price_type"]');
const priceField      = document.getElementById('price-field');

if (priceTypeInputs.length && priceField) {
    priceTypeInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (input.value === 'wanted') {
                priceField.style.display = 'none';
                priceField.querySelector('input').required = false;
            } else {
                priceField.style.display = '';
                priceField.querySelector('input').required = true;
            }
        });
    });
}
