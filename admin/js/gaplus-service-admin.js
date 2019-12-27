(function( $ ) {
	'use strict';
	var cft_rows_cache = [];
	var cft_attributes = [];
	$(window).load(function() {
		var mb_selector = $(`div.cmb2-metabox[id$=${cf_generator_form.metabox_id}]`);
		$(mb_selector).append('<div id="gaplus_cf_templates"></div><div class="cf_row" id="gaplus_cf_row"></div><div class="gaplus_info_row"></div>');

		gp_draw_cft_table(cft_rows_data);

		var cf_row = $("#gaplus_cf_row");

		Object.keys(cf_generator_form['atts']).forEach(function(key) {
				var index = cf_generator_form['atts'][key]['order'] * 1;

				cft_attributes[index] = cf_generator_form['atts'][key];
		});

		for (var i = 0, attr_container = ''; i < cft_attributes.length; i++) {
			attr_container = `<div class="attr_container"> \
					<label for="${cft_attributes[i].attr_name}" data-hint="${cft_attributes[i].placeholder}"> \
						${cft_attributes[i].label} \
					<span> *</span></label>`;

			switch (cft_attributes[i].type) {
				case 'text':
					attr_container += `<input id="fld_${cft_attributes[i].attr_name}" type="${cft_attributes[i].type}" \
						value="${cft_attributes[i].value}" class="fld_attr" />`;
					break;

				case 'textarea':
					attr_container +=`<textarea id="fld_${cft_attributes[i].attr_name}" value="${cft_attributes[i].value}" \
						 rows="4" class="fld_attr"></textarea>`;
					break;

				case 'select':
					attr_container += `<select id="fld_${cft_attributes[i].attr_name}" class="fld_attr"> \
								<option selected disabled value=''>Select...</option>`;
						for (var j = 0; j < cft_attributes[i].value.length; j++) {
							attr_container += `<option value="${cft_attributes[i].value[j]}">${cft_attributes[i].value[j]}</option>`;
						}
						attr_container += "</select>";
					break;
			}
			attr_container += '</div>';
			$(cf_row).append(attr_container);
			$('#fld_options').prop('disabled', true);
		}
		$(cf_row).append(`<input type="hidden" id="gaplus_nonce" value="${cf_generator_form.nonce}">`);
		$(cf_row).append('<input type="submit" id="new_ga_cf_btn" class="button button-ga right" value="Add field">');

		$('.attr_container .fld_attr').on('change focusout', function(){
			$(this).removeClass('not_valid');
			if ($(this).val() === "" || $(this).val() === null) {
				$(this).addClass('not_valid');
			}
			$('.gaplus_info_row').hide(500);
		});

		$('#fld_type').on('change', function() {
			if ($(this).val() === "radio" || $(this).val() === "checkbox" || $(this).val() === "select" ) {
				$('#fld_options')
				.removeAttr('disabled')
				.parents('.attr_container').show(500);
			} else {
				$('#fld_options')
					.prop('disabled', true)
					.removeClass('not_valid')
					.parents('.attr_container').hide(500);
			}
		});

		$('.attr_container .fld_attr').on('focus', function(){
			$(this).removeClass('not_valid');
		});

		$('#fld_shortcode')
		.focusout(function(){
			if ($(this).val().length > 0) {
				$(this).val('%'+$(this).val().replace(/%/gi, "")+'%');
			}
		})
		.focus(function(){
			$(this).val( $(this).val().replace(/%/gi, "") );
		})
		.keyup(function(e){
			$(this).val(
				$(this).val()
					.toLowerCase()
					.replace(/ /gi, "_")
					.replace(/%/gi, "")
			);
		});

		$('#fld_label').focusout(function(){
			var txt = $(this).val().toLowerCase().replace(/ /gi, "_").replace(/%/gi, "");
			if ( txt.length > 0 /* && $('#fld_shortcode').val().length === 0 */ ) {
				$('#fld_shortcode').val('%'+txt+'%');
			}
		})

/*           Send AJAX requet                   */
		$('#new_ga_cf_btn').click(function(e){
			e.preventDefault();
			var data = {};

			data['action'] = 'update_cf_template';
			data['post_id'] = cf_generator_form['post_id'];
			data['nonce'] = $('#gaplus_nonce').val();

			for (var i = 0; i < cft_attributes.length; i++) {
				var selector = `#fld_${cft_attributes[i].attr_name}`;
				data[`${cft_attributes[i].attr_name}`] = $(selector).val();
			}

			data['shortcode'] = data['shortcode'].replace(/^%|%$/gi, "");
			if( !data['required'] ) {
				data['required'] = 'No';
			}

			if ( !data['shortcode'] || !data['type'] || !data['label'] || !data['required'] ) {
				gp_cft_alert('Some field is not filled!', 'gp_error')
			} else if ( gp_check_shortcode( data['shortcode'] ) ) {
				gp_cft_alert('Shortcode already exists!', 'gp_error')
			} else {
				gp_ajax_call(data);
			}
		});
	});

	function gp_ajax_call(post_data){
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: post_data,
			success: function(responce) {
				gp_draw_cft_table(responce);
			}
		});
	};

	function gp_draw_cft_table(rows_data) {
		cft_rows_cache = rows_data;
		console.log(cft_rows_cache);
		if ( rows_data[0] !== '' ) {
			var out = "<!-- table head -->";
			out += "<div class='gaplus_row' id='gaplus_cf_head'>";
			Object.keys(rows_data[0]).forEach(function(key){
				out += `<div class='gaplus_cell'>${key}</div>`;
			});
			out += "</div><!-- / table head -->";
			for (var i = 0; i < rows_data.length; i++ ) {
				var row = rows_data[i];
				out += `<div class='gaplus_row' id="gaplus_row_${row.shortcode}">`;
				for (var key in row) {
					var str = row[key].replace(/(?:\r\n|\r|\n)/g, '<br>');
					out += `<div class='gaplus_cell'>${str}</div>`;
				}
				out += "</div>";
			}
			var cft_table = $('#gaplus_cf_templates');
			$(cft_table).hide(200, () => {
					for (var i = 0; i < cft_attributes.length; i++) {
						$(`#fld_${cft_attributes[i].attr_name}`).val('').removeClass('not_valid');
						$('.attr_container').show(100);
					}
			});
			$(cft_table).empty();
			$(cft_table).prepend(out);
			$(cft_table).delay(100).show(200);
		};
	};

	function gp_check_shortcode(text) {
		if ( !cft_rows_cache ) {
			return false;
		}
		return cft_rows_cache.some((row) => row['shortcode'] == text );
	};

	function gp_cft_alert(text, class_name) {
				$('.gaplus_info_row').stop()
				.removeClass('gp_hint gp_error').addClass(class_name)
				.html(text)
				.fadeIn(300);
	}

		$(window).load(function() {
			$('#show_gpac').on('click', function(){
				$('#location_autocomplete').toggleClass('ga_hide');
			});
		});

})( jQuery );
