(function( $ ) {
	'use strict';

	$(window).load(function() {

		$(document).on('click',function (e) {
			var trg = $(e.target);
			var wrapper_closed = false;
			if( trg.closest('.gaplus_cf_wrapper').length ) return;
			if( trg.is('.gaplus_cf_wrapper_label') ) {
				wrapper_closed = ( trg.next('.gaplus_cf_wrapper').css('display') == 'none' );
			}
			$('.gaplus_cf_wrapper').hide();
			if( wrapper_closed ) {
				trg.next('.gaplus_cf_wrapper').show();
			}
		});


		$('.gaplus_preconfirm_buttons [name="hide_button"]').on('click', function(){
			$(this).parents('.gaplus_cf_wrapper').hide();
		});

		$('.gaplus_app_field.gaplus_required input').on('keyup', function() {
			$(this).removeClass('not_valid');
		});

		$('.gaplus_app_field.gaplus_required textarea').on('keyup', function() {
			$(this).removeClass('not_valid');
		});

		$('.gaplus_app_field.gaplus_required select').on('change', function() {
			$(this).removeClass('not_valid');
		});

		$('.gaplus_app_field.gaplus_required input[type=checkbox]').on('change', function() {
			$(this).parent().removeClass('not_valid');
		});

		$('.gaplus_app_field.gaplus_required input[type=radio]').on('change', function() {
			$(this).parent().removeClass('not_valid');
		});

		$('.gaplus_preconfirm_buttons [name="save_button"]').on('click', function(e){
			e.preventDefault();
			var data = {};
			var wrapper = $(this).parent().parent();

			var label = $(wrapper).prev('.gaplus_cf_wrapper_label');
			if ( ! validate_fields(wrapper) ) {
				$(label).removeClass('action_done');
			} else {
				data = cf_data_prepare_to_ajax(wrapper);
				data['nonce'] = $(wrapper).find('input[id="app-nonce"]').val();
				data['app_id'] = $(wrapper).parent().find('div.appointment-status > a.provider-confirm').attr('app-id');
				data['action'] = 'save_app_cf_data';
				if ( 'undefined' != data ) {
					post_cf_ajax(data, label, wrapper);
				}
			}
		});


	}); // End of $(window).load(function()

	function post_cf_ajax(post_data, label, wrapper){
		$.ajax({
			url: gaplus_ajax.url,
			type: 'POST',
			dataType: 'json',
			data: post_data,
			beforeSend: function(){
				$(label).text('Saving data...');
			},
			success: function(responce) {
				$(label).text('Preconfirmation done').addClass('action_done');
//				console.log(responce);
				fill_wrapper(wrapper, responce);
			},
			error: function(xhr, status, error) {
				console.log("Error has arised:");
				console.log(status);
				console.log(error);
				console.log(xhr);
			}
		});
	}
	function validate_fields(wrapper) {
		var valid = true;
		var fields = $(wrapper).find('.gaplus_app_field.gaplus_required');
		for (var i = 0; i < fields.length; i++) {

			var el = $(fields[i]).find('input[type="text"]:not([class="not_valid"])');
			if ( ( el.length > 0 ) && $(el[0]).val() == '' ) {
				$(el[0]).addClass('not_valid');
				valid = false;
			}

			el = $(fields[i]).find('textarea:not([class="not_valid"])');
			if ( ( el.length > 0 ) && $(el[0]).val() == '' ) {
				$(el[0]).addClass('not_valid');
				valid = false;
			}

			el = $(fields[i]).find('select:not([class="not_valid"])');
			if ( ( el.length > 0 ) && $(el[0]).children('option:selected').val() == '' ) {
				$(el[0]).addClass('not_valid');
				valid = false;
			}

			el = $(fields[i]).find('.gaplus_cf_checkbox_group:not([class="not_valid"])');
			if ( el.length > 0) {
				if ( $(el[0]).children('input[type=checkbox]:checked').length == 0 ) {
					$(el[0]).addClass('not_valid');
					valid = false;
				}
			}
			el = $(fields[i]).find('.gaplus_cf_radio_group:not([class="not_valid"])');
			if ( el.length > 0) {
				if ( $(el[0]).children('input[type=radio]:checked').length == 0 ) {
					$(el[0]).addClass('not_valid');
					valid = false;
				}
			}
		}

		return valid;
	}
	function cf_data_prepare_to_ajax(wrapper) {
		var data = {};
		var fields = {};

		$(wrapper).find('input[type="text"]').each(function() {
			var shortcode = $(this).attr('name');
			fields[ shortcode ] = {
				'name': $(this).prev('label').text().split(':')[0],
				'value': $(this).val(),
			};
		});

		$(wrapper).find('textarea').each(function() {
			var shortcode = $(this).attr('name');
			fields[ shortcode ] = {
				'name': $(this).prev('label').text().split(':')[0],
				'value': $(this).val(),
			};
		});

		$(wrapper).find('select').each(function() {
			var shortcode = $(this).attr('name');
			fields[ shortcode ] = {
				'name': $(this).prev('label').text().split(':')[0],
				'value': $(this).val(),
			};
		});

		$(wrapper).find('.gaplus_cf_checkbox_group').each(function() {
			var shortcode = $(this).attr('data-name');
			fields[ shortcode ] = {
				'name': $(this).prev('label').text().split(':')[0],
			};
			var values = [];
			$(this).find('input[type=checkbox]:checked').each(function(ind, el) {
				values[ind] = $(el).val();
			});
			if ( 0 < values.length ) {
				fields[ shortcode ]['value'] = values.join(', ');
			}
		});

		$(wrapper).find('.gaplus_cf_radio_group').each(function() {
			var shortcode = $(this).attr('data-name');
			fields[ shortcode ] = {
				'name': $(this).prev('label').text().split(':')[0],
				'value': $(this).find('input[type=radio]:checked').val(),
			};
		});
		data['app_fields'] = fields;
		console.log("POST_DATA: ");
		console.log(data);

		return data;
	}

	function fill_wrapper(wrapper, fields) {
		$(wrapper).children().each( function(i, el) {
			$(el).hide('slow', function() {
				$(el).remove();
			});
		});
		for( var key in fields) {
			var value = fields[key]["value"].replace(/(?:\r\n|\r|\n)/g, '<br>');
			var out = `<div class="gaplus_app_cf"><div class="gaplus_app_cf_name"><b>${fields[key]["name"]}</b><br>%${key}%</div><div class="gaplus_app_cf_value">${value}</div>`;
			$(wrapper).append(out);
			$(wrapper).find('.gaplus_app_cf').fadeIn();
			$(wrapper).addClass('flx');
		}
	}
})( jQuery );
