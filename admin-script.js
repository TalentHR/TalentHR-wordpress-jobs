document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('save-button').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent the default form submission behavior

        var domainInput = document.getElementById('talenthr_domain_name');
        var domainValue = domainInput.value.trim();

        // Validate input (optional)
        if (domainValue === '') {
            alert('Please enter a valid domain.');
            return;
        }

        // Save the domain name using AJAX with nonce verification
        var xhr = new XMLHttpRequest();
        xhr.open('POST', talenthr_ajax_obj.ajax_url);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-WP-Nonce', talenthr_ajax_obj.nonce); // Add nonce header
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Domain name saved successfully.');
                // Optionally, you can clear the input field after successful save
                domainInput.value = '';
            } else {
                alert('Error occurred while saving domain name.');
            }
        };
        // Include nonce in the data sent to the server
        var formData = new FormData();
        formData.append('action', 'save_talenthr_domain_name');
        formData.append('domain_name', domainValue);
        formData.append('nonce', talenthr_ajax_obj.nonce);
        xhr.send(formData);
    });
});
