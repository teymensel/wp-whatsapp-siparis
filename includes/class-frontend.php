<?php
if (!defined('ABSPATH')) {
    exit;
}

class WWS_Frontend
{

    private $settings;
    private $options;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->options = $this->settings->get_options();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wp_whatsapp_order', array($this, 'render_shortcode'));
        add_filter('the_content', array($this, 'auto_insert_content'));
        add_action('wp_footer', array($this, 'render_floating_widget'));
    }

    public function enqueue_scripts()
    {
        if (empty($this->options['phone'])) {
            return;
        }

        wp_enqueue_style('wws-frontend-style', WWS_PLUGIN_URL . 'assets/css/style.css', array(), WWS_VERSION);
        wp_enqueue_script('wws-frontend-js', WWS_PLUGIN_URL . 'assets/js/main.js', array('jquery'), WWS_VERSION, true);

        wp_localize_script('wws-frontend-js', 'wws_vars', array(
            'phone' => $this->options['phone'],
            'template' => $this->options['message'],
            'open_new_tab' => $this->options['open_new_tab'] === '1',
            'esnaf_mode' => $this->options['esnaf_mode'] === '1',
            'is_mobile' => wp_is_mobile(),
        ));

        // Styles
        $radius = isset($this->options['btn_radius']) ? $this->options['btn_radius'] . 'px' : '50px';
        $shadow = isset($this->options['btn_shadow']) && $this->options['btn_shadow'] === '1'
            ? '0 6px 20px rgba(0,0,0,0.15)'
            : 'none';
        $hover_transform = isset($this->options['btn_shadow']) && $this->options['btn_shadow'] === '1'
            ? 'translateY(-3px)'
            : 'none';

        // Background Logic
        $bg = $this->options['btn_bg_color']; // Default solid
        if (isset($this->options['btn_type']) && $this->options['btn_type'] === 'gradient') {
            $bg1 = $this->options['btn_gradient_1'];
            $bg2 = $this->options['btn_gradient_2'];
            $deg = $this->options['btn_gradient_deg'];
            $bg = "linear-gradient({$deg}deg, {$bg1}, {$bg2})";
        }

        $custom_css = "
			:root {
				--wws-btn-bg: " . esc_attr($bg) . ";
				--wws-btn-text: " . esc_attr($this->options['btn_text_color']) . ";
				--wws-radius: " . esc_attr($radius) . ";
				--wws-shadow: " . $shadow . ";
			}
			.wws-button {
				background: var(--wws-btn-bg) !important;
			}
			.wws-button:hover {
				transform: " . $hover_transform . ";
			}
		";

        // Mobile Hide Logic for Sticky
        if (isset($this->options['floating_mobile']) && $this->options['floating_mobile'] !== '1') {
            $custom_css .= "
				@media (max-width: 768px) {
					.wws-floating-btn { display: none !important; }
				}
			";
        }

        wp_add_inline_style('wws-frontend-style', $custom_css);
    }

    public function track_click()
    {
        // Nonce check is good but sometimes caching plugins strip nonces for guests.
        // We'll trust check_ajax_referer but log if it fails silently or handle guest.
        if (!check_ajax_referer('wws_track_nonce', 'nonce', false)) {
            wp_send_json_error('Nonce failed');
        }

        $stats = get_option('wws_stats', array('total_clicks' => 0, 'last_click' => ''));
        $stats['total_clicks'] = isset($stats['total_clicks']) ? intval($stats['total_clicks']) + 1 : 1;
        $stats['last_click'] = current_time('mysql'); // Human timestamp

        update_option('wws_stats', $stats);

        wp_send_json_success(array('total' => $stats['total_clicks']));
    }

    public function render_shortcode($atts)
    {
        if (empty($this->options['phone'])) {
            return '';
        }

        global $post;

        $product_data = array(
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'url' => get_permalink(),
            'price' => '',
        );

        if (function_exists('wc_get_product') && is_singular('product')) {
            $product = wc_get_product(get_the_ID());
            if ($product) {
                $product_data['price'] = $product->get_price_html();
                $product_data['price_raw'] = strip_tags(wc_price($product->get_price()));
            }
        }

        if (!empty($atts['price'])) {
            $product_data['price_raw'] = $atts['price'];
        }

        return $this->generate_button_html($product_data);
    }

    public function auto_insert_content($content)
    {
        if (is_admin() || !is_main_query() || !in_the_loop()) {
            return $content;
        }

        $auto_types = $this->options['auto_insert_types'];
        if (!is_array($auto_types)) {
            return $content;
        }

        if (is_singular($auto_types)) {
            $button_html = $this->render_shortcode(array());
            $content .= $button_html;
        }

        return $content;
    }

    public function render_floating_widget()
    {
        if (empty($this->options['phone'])) {
            return;
        }

        if ($this->options['enable_floating'] !== '1') {
            return;
        }
        // Server-side mobile check is unreliable with caches, better use CSS (added in enqueue).
        // But if we want to save HTML size, we can check here too.
        // If option says hide on mobile, and we are detected as mobile, we skip rendering.
        if (wp_is_mobile() && (!isset($this->options['floating_mobile']) || $this->options['floating_mobile'] !== '1')) {
            return;
        }

        $pos_class = 'wws-float-' . ($this->options['floating_pos'] === 'left' ? 'left' : 'right');
        $url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $this->options['phone']);

        // Text Logic
        $desktop_text = $this->options['floating_text'];

        // Mobile Text Logic
        $mobile_show_text = isset($this->options['floating_mobile_text']) && $this->options['floating_mobile_text'] === '1';
        $mobile_custom_text = isset($this->options['floating_text_mobile']) && !empty($this->options['floating_text_mobile'])
            ? $this->options['floating_text_mobile']
            : $desktop_text;

        $has_text = !empty($desktop_text);

        // We render spans with classes to hide/show via CSS based on screen size
        echo '<a href="' . esc_url($url) . '" class="wws-floating-btn wws-track-me ' . esc_attr($pos_class) . '" target="_blank">';
        echo '<span class="dashicons dashicons-whatsapp"></span>';

        if ($has_text) {
            // Desktop Text
            echo '<span class="wws-float-text wws-desktop-text">' . esc_html($desktop_text) . '</span>';

            // Mobile Text (Only if enabled)
            if ($mobile_show_text) {
                echo '<span class="wws-float-text wws-mobile-text" style="display:none;">' . esc_html($mobile_custom_text) . '</span>';
            }
        }

        echo '</a>';
    }

    private function generate_button_html($data)
    {
        $enable_qty = $this->options['enable_qty'] === '1';
        $enable_var = $this->options['enable_variations'] === '1';
        $show_icon = $this->options['show_icon'] === '1';

        $size_class = 'wws-size-' . (!empty($this->options['btn_size']) ? $this->options['btn_size'] : 'medium');
        $anim_class = 'wws-anim-' . (!empty($this->options['btn_animation']) ? $this->options['btn_animation'] : 'none');

        ob_start();
        ?>
        <div class="wws-container" data-title="<?php echo esc_attr($data['title']); ?>"
            data-url="<?php echo esc_attr($data['url']); ?>"
            data-price="<?php echo isset($data['price_raw']) ? esc_attr($data['price_raw']) : ''; ?>">

            <div class="wws-controls">
                <?php if ($enable_qty): ?>
                    <div class="wws-control-group wws-qty-group">
                        <label class="wws-label">Adet</label>
                        <div class="wws-input-wrapper">
                            <input type="number" class="wws-qty-input" value="1" min="1" />
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($enable_var): ?>
                    <div class="wws-control-group wws-var-group">
                        <label class="wws-label">Seçenek / Not</label>
                        <div class="wws-input-wrapper">
                            <input type="text" class="wws-var-input" placeholder="Notunuz..." />
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <a href="#" class="wws-button wws-track-me <?php echo esc_attr($size_class . ' ' . $anim_class); ?>" role="button">
                <?php if ($show_icon): ?>
                    <span class="wws-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </span>
                <?php endif; ?>
                <span class="wws-text"><?php echo esc_html($this->options['btn_text']); ?></span>
            </a>

        </div>
        <?php
        return ob_get_clean();
    }
}
