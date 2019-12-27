(function( $ ) {
	'use strict';

	$(window).load(function() {
		gp_draw_google_key_field();
	});

	function gp_draw_google_key_field() {
		var param =  window.location.search.split('&');
		if (param[1] === undefined || (param[1] === 'tab=main')) {
			var str = `<tr><th scope="row"><label for="clear_time">Google Places API key</label></th>
							<td>
						<input type="text"  size="30" name="ga_appointments_calendar[google_places_key]" id="google_places_key" placeholder="put here API key" value="${google_places_key}">
								<br>
								<p class="description">Google Places API key for autocomplete service location fields.</p>
							</td>
						</tr>`;
			$('#ga_appointments_settings').find('tbody').append(str);
		}
	};

})( jQuery );
