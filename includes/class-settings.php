<?php
if (!defined('ABSPATH')) {
	exit;
}

class WWS_Settings
{

	private $option_name = 'wws_settings';

	public function __construct()
	{
		add_action('admin_init', array($this, 'register_settings'));
	}

	public function register_settings()
	{
		register_setting(
			'wws_settings_group',
			$this->option_name,
			array($this, 'sanitize')
		);
	}

	public function get_default_message()
	{
		return "Merhaba, şu ürünle ilgileniyorum:\n\n*Urun*: {urun_adi}\n*Fiyat*: {fiyat}\n*Adet*: {adet}\n*Not*: {varyasyon}\n\n*Link*: {url}";
	}

	public function get_options()
	{
		$defaults = array(
			// General
			'phone' => '',
			'message' => $this->get_default_message(),
			'open_new_tab' => '1',

			// PRO Design (Ürün İçi Buton)
			'btn_text' => 'WhatsApp ile Sipariş Ver',
			'btn_type' => 'solid',
			'btn_bg_color' => '#25D366',
			'btn_gradient_1' => '#25D366',
			'btn_gradient_2' => '#128C7E',
			'btn_gradient_deg' => '45',
			'btn_text_color' => '#FFFFFF',
			'btn_radius' => '50',
			'btn_shadow' => '1',
			'show_icon' => '1',
			'btn_size' => 'medium',
			'btn_animation' => 'none',

			// Floating Widget (Sticky)
			'enable_floating' => '0',
			'floating_text' => 'Bize Ulaşın',
			'floating_text_mobile' => '',
			'floating_pos' => 'right',
			'floating_mobile' => '1',
			'floating_mobile_text' => '0',

			// Visibility & Features
			'esnaf_mode' => '0',
			'auto_insert_types' => array('product'),
			'enable_qty' => '1',
			'enable_variations' => '1',
		);

		$options = get_option($this->option_name);

		if (!is_array($options)) {
			$options = array();
		}

		// Force fallback for empty message
		if (isset($options['message']) && trim($options['message']) === '') {
			$options['message'] = $defaults['message'];
		}

		return wp_parse_args($options, $defaults);
	}

	public function sanitize($input)
	{
		$new_input = array();

		$new_input['phone'] = isset($input['phone']) ? sanitize_text_field($input['phone']) : '';

		// If message cleared, it will reset to default in get_options, allow empty save technically but handled in retrieval
		$new_input['message'] = isset($input['message']) ? sanitize_textarea_field($input['message']) : '';
		$new_input['open_new_tab'] = isset($input['open_new_tab']) ? '1' : '0';

		$new_input['btn_text'] = sanitize_text_field($input['btn_text']);
		$new_input['btn_type'] = sanitize_key($input['btn_type']);
		$new_input['btn_bg_color'] = sanitize_hex_color($input['btn_bg_color']);
		$new_input['btn_gradient_1'] = sanitize_hex_color($input['btn_gradient_1']);
		$new_input['btn_gradient_2'] = sanitize_hex_color($input['btn_gradient_2']);
		$new_input['btn_gradient_deg'] = absint($input['btn_gradient_deg']);
		$new_input['btn_text_color'] = sanitize_hex_color($input['btn_text_color']);
		$new_input['btn_radius'] = absint($input['btn_radius']);
		$new_input['btn_shadow'] = isset($input['btn_shadow']) ? '1' : '0';
		$new_input['show_icon'] = isset($input['show_icon']) ? '1' : '0';
		$new_input['btn_size'] = sanitize_key($input['btn_size']);
		$new_input['btn_animation'] = sanitize_key($input['btn_animation']);

		$new_input['enable_floating'] = isset($input['enable_floating']) ? '1' : '0';
		$new_input['floating_text'] = sanitize_text_field($input['floating_text']);
		$new_input['floating_text_mobile'] = sanitize_text_field($input['floating_text_mobile']);
		$new_input['floating_pos'] = sanitize_key($input['floating_pos']);
		$new_input['floating_mobile'] = isset($input['floating_mobile']) ? '1' : '0';
		$new_input['floating_mobile_text'] = isset($input['floating_mobile_text']) ? '1' : '0';

		$new_input['esnaf_mode'] = isset($input['esnaf_mode']) ? '1' : '0';
		$new_input['enable_qty'] = isset($input['enable_qty']) ? '1' : '0';
		$new_input['enable_variations'] = isset($input['enable_variations']) ? '1' : '0';

		if (isset($input['auto_insert_types']) && is_array($input['auto_insert_types'])) {
			$new_input['auto_insert_types'] = array_map('sanitize_key', $input['auto_insert_types']);
		} else {
			$new_input['auto_insert_types'] = array();
		}

		return $new_input;
	}
}
