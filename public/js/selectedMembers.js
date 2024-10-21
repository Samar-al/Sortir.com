document.addEventListener('DOMContentLoaded', function() {
    const selectedMembersInput = document.getElementById('selected_members');
    let selectedMembers = [];
   
    // Handle "Add" button click event
    document.querySelectorAll('.add-participant-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const participantId = this.getAttribute('data-id');
            
            // Add the participant if not already in the list
            if (!selectedMembers.includes(participantId)) {
                selectedMembers.push(participantId);
                this.classList.replace('btn-outline-success', 'btn-success');
                this.innerText = 'Added';
            } else {
                // Remove participant if already added
                selectedMembers = selectedMembers.filter(id => id !== participantId);
                this.classList.replace('btn-success', 'btn-outline-success');
                this.innerText = 'Add';
            }

            // Update the hidden input field with selected participant IDs
            selectedMembersInput.value = selectedMembers.join(',');
        });
    });
});