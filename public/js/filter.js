document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form"); // Your form selector

    form.addEventListener("submit", function (event) {
        event.preventDefault(); // Prevent the form from submitting the traditional way

        const formData = new FormData(form); // Get form data

        // Send the request using fetch API
        fetch(form.action, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest", // Signal that this is an AJAX request
            },
        })
        .then(response => response.json()) // Expect a JSON response from the server
        .then(data => {
            const tripsTbody = document.querySelector("#trips-tbody"); // The tbody where trips are displayed
            tripsTbody.innerHTML = data.html; // Update the tbody with the new content
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});
