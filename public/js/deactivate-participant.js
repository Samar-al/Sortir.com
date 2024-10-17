document.getElementById('deactivate-button').addEventListener('click', function () {
    const checkboxes = document.querySelectorAll('input[name="participants[]"]:checked');
    const selectedParticipants = [];

    checkboxes.forEach(function (checkbox) {
        selectedParticipants.push(checkbox.value);
    });

    if (selectedParticipants.length === 0) {
        alert("Please select at least one participant to deactivate.");
        return;
    }

    // Send AJAX request to deactivate participants
    fetch("/profil/deactivate-participants", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': "{{ csrf_token('deactivate_participants') }}" // CSRF token for security
        },
        body: JSON.stringify({
            participants: selectedParticipants
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Selected participants have been deactivated.");
            location.reload(); // Reload the page to reflect the changes
        } else {
            alert("An error occurred while deactivating participants.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});