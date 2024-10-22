document.addEventListener('DOMContentLoaded', function() {

    let departmentSelect = document.getElementById('city_department'); // Change this ID to match your form field
    let citySelect = document.getElementById('cityName'); // Change this ID to match your city field

    // let cityHiddenNode = document.getElementById('city_name'); // Change this ID to match your city field
    // let zipCodeNode = document.getElementById('city_ZipCode'); // Change this ID to match your city field
    let cityHiddenNode = document.querySelector('[data-custom="city_name"]') // Change this ID to match your city field
    let zipCodeNode = document.querySelector('[data-custom="city_ZipCode"]'); // Change this ID to match your city field

    departmentSelect.addEventListener('change', function() {
        let departmentCode = departmentSelect.value;

        fetch(`/ville/get-cities/${departmentCode}`)
        .then(response => response.json())
        .then(data => {
                citySelect.innerHTML = '';
                addPlaceholder(citySelect, 'Sélectionner une ville');
                zipCodeNode.value = '';

                // Populate the city select with new options
                if (data.error) {
                console.error(data.error);
                } else {
                    data.forEach(function(city) {
                        let option = document.createElement('option');
                        option.value = city.nom;
                        option.textContent = city.nom;
                        option.dataset.zipCode = city.code;
                        citySelect.appendChild(option);
                    });
                }
        })
        .catch(error => {
            console.error('Error fetching cities:', error);
        });
    });

    citySelect.addEventListener('change', function() {
        let selectedCityOption = citySelect.options[citySelect.selectedIndex]; // Récupère l'option sélectionnée
        let zipCode = selectedCityOption.dataset.zipCode; // Récupère le code postal de l'option

        // Met à jour le champ caché et le code postal
        cityHiddenNode.value = selectedCityOption.value; // Met à jour la valeur cachée avec le nom de la ville
        zipCodeNode.value = zipCode;
    });
});

function addPlaceholder(selectElement, placeholderText) {
    let placeholderOption = document.createElement('option');
    placeholderOption.textContent = placeholderText;
    placeholderOption.disabled = true;  // Désactiver l'option pour ne pas la sélectionner
    placeholderOption.selected = true;  // Sélectionner par défaut
    selectElement.appendChild(placeholderOption);
}