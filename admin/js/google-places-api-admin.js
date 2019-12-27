function initAutocomplete() {
  var autocomplete = new google.maps.places.Autocomplete(
          document.getElementById('autocomplete'),
          {types: ['geocode']}
        );
  autocomplete.addListener('place_changed', function () {
    document.getElementById('ga_service_location').value = document.getElementById('autocomplete').value;
    document.getElementById('autocomplete').value = '';
    document.getElementById('location_autocomplete').classList.add('ga_hide');
  });
}
