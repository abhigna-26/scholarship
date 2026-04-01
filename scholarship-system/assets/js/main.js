document.addEventListener('DOMContentLoaded', () => {
    // Basic form validation script
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    // Add basic visual feedback
                    field.style.borderColor = 'var(--danger)';
                } else {
                    field.classList.remove('error');
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Please fill out all required fields.');
            }
        });
    });

    // File upload size validation (Max 5MB)
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 5) {
                    alert('File size exceeds 5MB limit.');
                    this.value = ''; // clear input
                }
            }
        });
    });
});
