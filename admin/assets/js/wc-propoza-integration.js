(function ($) {
	$(function () {
		var api_key_element = $('#woocommerce_propoza_api_key');
		var sub_domain_element = $('#woocommerce_propoza_web_address');
		var propoza_dashboard_link = $('#woocommerce_propoza_launch_propoza');
		var propoza_authenticate_link = $('#woocommerce_authenticate_propoza');
		var test_connection_button = $('#woocommerce_propoza_test_connection');
		var loader_container = $('#loader-container');
		var propoza_setup_button = $('#woocommerce_propoza_setup_button');

		propoza_dashboard_link.hideOnEmptyValue(sub_domain_element.attr('id'));
        propoza_authenticate_link.hideOnEmptyValue(sub_domain_element.attr('id'));
		test_connection_button.hideOnEmptyValue([api_key_element.attr('id'), sub_domain_element.attr('id')]);

		sub_domain_element.on('input', function () {
			propoza_dashboard_link.hideOnEmptyValue(sub_domain_element.attr('id'));
            propoza_authenticate_link.hideOnEmptyValue(sub_domain_element.attr('id'));
			test_connection_button.hideOnEmptyValue([api_key_element.attr('id'), sub_domain_element.attr('id')]);

			/**
			 * get sub-domain from input value if entered a full url e.g.
			 * demo.propoza.com
			 * demo.local.propoza.com
			 * http://demo.propoza.com
			 * http://demo.local.propoza.com
			 * https://demo.propoza.com
			 * https://demo.local.propoza.com
			 *
			 * @type {RegExp}
			 */
			var regex = /(http[s]?:\/\/)?(.*)\.propoza\.com/g;
			var result = regex.exec(sub_domain_element.val());
			if (result != null) {
				sub_domain_element.val(result[2]);
			}
		});

		api_key_element.on('input', function () {
			test_connection_button.hideOnEmptyValue([api_key_element.attr('id'), sub_domain_element.attr('id')]);
		});

		propoza_dashboard_link.click(function (e) {
			window.open(propoza_dashboard_link.attr('href').replace('%s', sub_domain_element.val()));
			e.preventDefault();
		});

        propoza_authenticate_link.click(function (e) {
            window.open(propoza_authenticate_link.attr('href').replace('%s', sub_domain_element.val()));
            e.preventDefault();
        });

		test_connection_button.click(function () {
			loader_container.show();
			$.post(propoza_object.ajax_url, {
				'action': 'test_connection',
				'api_key': api_key_element.val(),
				'sub_domain': sub_domain_element.val()
			}, function (data) {
				loader_container.hide();
				show_message(data.response);
			}, 'json').fail(function () {
				loader_container.hide();
				show_message(false);
			});
		});

		function show_message(success) {
			if (success == true) {
				alert('Test connection success!');
			} else {
				alert('Test connection failed!');
			}
		}
	});

	$.fn.hideOnEmptyValue = function (elementIds) {
		if ($.isArray(elementIds)) {
			hasValue = true;
			$.each(elementIds, function (index, value) {
				if ($('#' + value).val() == '') {
					return hasValue = false;
				}
			});
		}
		else {
			hasValue = $('#' + elementIds).val() != '';
		}
		if (hasValue) {
			$(this).show();
		} else {
			$(this).hide();
		}
	}
})(jQuery);