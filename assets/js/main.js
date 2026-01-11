jQuery(document).ready(function ($) {

	$('.wws-track-me').on('click', function (e) {

		// If it's the main Product Button, we have custom JS logic to build the URL.
		// So we prevent default only for that specific button type.
		if ($(this).hasClass('wws-button')) {
			e.preventDefault();

			var $btn = $(this);
			var $container = $btn.closest('.wws-container');

			// Get Product Data
			var title = $container.data('title');
			// We don't rely only on data-url because we reconstruct it safely
			var currentUrl = window.location.href;
			// Or better, use the one passed from PHP but clean it.
			var url = $container.data('url') || currentUrl;

			var price = $container.data('price');

			// Get User Input
			var qty_val = $container.find('.wws-qty-input').val();
			var qty = qty_val ? qty_val : '1';

			var var_val = $container.find('.wws-var-input').val() || $container.find('select.wws-var-input').val();
			var variation = var_val ? var_val : '-';

			// Prepare Message
			var message = wws_vars.template || "";

			// Replace Logic
			message = message.replace(/{urun_adi}/g, title);
			message = message.replace(/{url}/g, url);
			message = message.replace(/{adet}/g, qty);
			message = message.replace(/{varyasyon}/g, variation);
			message = message.replace(/{fiyat}/g, wws_vars.esnaf_mode ? 'Sorunuz' : (price ? price : 'Belirtilmedi'));

			// 1. Convert all newlines to simple \n
			var clean_message = message.replace(/\r\n/g, "\n").replace(/\r/g, "\n");

			// 2. Encode URI Component
			var encoded_message = encodeURIComponent(clean_message);

			// 3. Force %0A for newlines (just to be 100% sure although encodeURIComponent catches \n as %0A usually)
			// Note: encodeURIComponent('\n') is '%0A'. 
			// But sometimes we get '%0D%0A' from existing bad encoding. We fix it.
			encoded_message = encoded_message.replace(/%0D%0A/g, '%0A');

			var phone = wws_vars.phone.replace(/[^0-9]/g, '');

			// STABLE ENDPOINT: wa.me
			var final_link = 'https://wa.me/' + phone + '?text=' + encoded_message;

			// Open
			if (wws_vars.open_new_tab) {
				window.open(final_link, '_blank');
			} else {
				window.location.href = final_link;
			}
		}
	});

});
