<?php
/**
 * Plugin Name: WP WhatsApp Sipariş & Teklif Sistemi
 * Description: WooCommerce olmadan veya uyumlu çalışabilen, ürünler için WhatsApp üzerinden sipariş ve teklif alma eklentisi. Esnaf dostu.
 * Version: 1.1.1
 * Author: Teymensel
 * Author URI: https://teymensel.com
 * Text Domain: wp-whatsapp-siparis
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WWS_VERSION', '1.1.1');
define('WWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WWS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WWS_PLUGIN_DIR . 'includes/class-settings.php';
require_once WWS_PLUGIN_DIR . 'includes/class-admin.php';
require_once WWS_PLUGIN_DIR . 'includes/class-frontend.php';

class WP_WhatsApp_Siparis
{

	private static $instance = null;

	public $settings;
	public $admin;
	public $frontend;

	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		$this->settings = new WWS_Settings();

		if (is_admin()) {
			$this->admin = new WWS_Admin($this->settings);
		}

		$this->frontend = new WWS_Frontend($this->settings);

		add_action('plugins_loaded', array($this, 'load_textdomain'));

		// Metadata Filters
		add_filter('plugin_row_meta', array($this, 'custom_plugin_row_meta'), 10, 2);
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
	}

	public function load_textdomain()
	{
		load_plugin_textdomain('wp-whatsapp-siparis', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	/**
	 * Custom links in plugin metadata row
	 */
	public function custom_plugin_row_meta($links, $file)
	{
		if (strpos($file, 'wp-whatsapp-siparis.php') !== false) {
			// Add 'Ayrıntıları görüntüle' link pointing to settings page or custom modal logic
			// Standard 'View details' usually opens a modal from wp.org. 
			// Since local, we redirect to settings or a readme parser. 
			// Here we point to Settings as the "Details".
			$row_meta = array(
				'details' => '<a href="' . esc_url(admin_url('admin.php?page=wp-whatsapp-siparis')) . '" aria-label="' . esc_attr__('Ayrıntıları görüntüle', 'wp-whatsapp-siparis') . '">' . esc_html__('Ayrıntıları görüntüle', 'wp-whatsapp-siparis') . '</a>',
			);

			return array_merge($links, $row_meta);
		}
		return $links;
	}

	/**
	 * Settings link in plugin actions (Activate | Edit | Settings)
	 */
	public function add_settings_link($links)
	{
		$settings_link = '<a href="' . admin_url('admin.php?page=wp-whatsapp-siparis') . '">' . __('Ayarlar', 'wp-whatsapp-siparis') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
}

function WWS()
{
	return WP_WhatsApp_Siparis::get_instance();
}

// Init Plugin
WWS();
