<?php
if (!defined('ABSPATH')) {
    exit;
}

class WWS_Admin
{

    private $settings_class;
    private $options;

    public function __construct($settings_class)
    {
        $this->settings_class = $settings_class;
        $this->options = $this->settings_class->get_options();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_wws_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('admin_notices', array($this, 'check_phone_setup'));
    }

    public function check_phone_setup()
    {
        $screen = get_current_screen();
        // Show everywhere in admin or just on dashboard/plugins? Usually everywhere or minimal check.
        // But definitely on our settings page.

        $phone = isset($this->options['phone']) ? trim($this->options['phone']) : '';
        if (empty($phone)) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>⚠️ WP WhatsApp Sipariş:</strong> Lütfen eklentinin çalışması için <a
                        href="<?php echo admin_url('admin.php?page=wp-whatsapp-siparis'); ?>">ayarlar sayfasından</a> geçerli bir
                    WhatsApp numarası giriniz. Numara girilene kadar butonlar aktif olmayacaktır.</p>
            </div>
            <?php
        }
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'WP WhatsApp Sipariş',
            'WhatsApp Sipariş',
            'manage_options',
            'wp-whatsapp-siparis',
            array($this, 'render_settings_page'),
            'dashicons-whatsapp',
            56
        );
    }

    public function enqueue_admin_assets($hook)
    {
        if ('toplevel_page_wp-whatsapp-siparis' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style('wws-admin-css', WWS_PLUGIN_URL . 'assets/css/admin.css', array(), WWS_VERSION);
        wp_enqueue_script('wws-admin-js', WWS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), WWS_VERSION, true);

        wp_localize_script('wws-admin-js', 'wwsAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wws_admin_nonce'),
            'confirm_reset' => 'Ayarları varsayılana döndürmek istediğinize emin misiniz? Bu işlem geri alınamaz!'
        ));
    }

    public function ajax_reset_settings()
    {
        check_ajax_referer('wws_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz işlem.');
        }

        delete_option('wws_settings');
        wp_send_json_success('Ayarlar sıfırlandı.');
    }

    public function render_settings_page()
    {
        $opts = $this->settings_class->get_options();
        $post_types = get_post_types(array('public' => true), 'objects');
        ?>
        <div class="wrap wws-wrap">
            <div class="wws-header">
                <div class="wws-header-content">
                    <h1><span class="dashicons dashicons-whatsapp"></span> WP WhatsApp Sipariş & Teklif</h1>
                    <p class="wws-subtitle">Profesyonel WhatsApp sipariş yönetimi.</p>
                </div>
                <div class="wws-header-brand">
                    Powered by <a href="https://teymensel.com" target="_blank"
                        style="text-decoration:none; color:inherit;"><em>Teymensel</em></a>
                </div>
            </div>

            <form method="post" action="options.php" class="wws-form-main">
                <?php settings_fields('wws_settings_group'); ?>

                <h2 class="nav-tab-wrapper wws-js-tabs">
                    <a href="#tab-general" class="nav-tab nav-tab-active" data-tab="general">Genel Ayarlar</a>
                    <a href="#tab-design" class="nav-tab" data-tab="design">🎨 Tasarım</a>
                    <a href="#tab-floating" class="nav-tab" data-tab="floating">Yüzen Buton (Sticky)</a>
                    <a href="#tab-visibility" class="nav-tab" data-tab="visibility">Görünürlük</a>
                </h2>

                <div class="wws-tabs-content-wrapper">

                    <!-- TAB: GENERAL -->
                    <div id="tab-general" class="wws-tab-content active">
                        <table class="form-table">
                            <tr>
                                <th><label for="wws_phone">WhatsApp Numarası</label></th>
                                <td>
                                    <div class="wws-input-group">
                                        <span class="wws-input-icon dashicons dashicons-phone"></span>
                                        <input type="text" id="wws_phone" name="wws_settings[phone]"
                                            value="<?php echo esc_attr($opts['phone']); ?>" placeholder="Örn: 905551234567"
                                            class="regular-text" />
                                    </div>
                                    <p class="description"><strong>Önemli:</strong> Numaranızı ülke koduyla birlikte (örn:
                                        90...) bitişik yazın. + veya boşluk kullanmayın.</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Otomatik Gösterim</th>
                                <td>
                                    <fieldset class="wws-checkbox-grid">
                                        <legend class="screen-reader-text"><span>Otomatik Gösterim</span></legend>
                                        <?php foreach ($post_types as $pt):
                                            if (in_array($pt->name, array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset')))
                                                continue;
                                            $label = $pt->labels->name;
                                            if ($pt->name === 'product')
                                                $label .= ' (WooCommerce)';
                                            ?>
                                            <label>
                                                <input type="checkbox" name="wws_settings[auto_insert_types][]"
                                                    value="<?php echo esc_attr($pt->name); ?>" <?php checked(in_array($pt->name, $opts['auto_insert_types'])); ?> />
                                                <?php echo esc_html($label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <p class="wws-info-box" style="margin-top:10px;">
                                        📝 <strong>Bilgi:</strong> Burada seçilen sayfa türlerinin (Post Types) altında buton
                                        otomatik çıkar.
                                        Örneğin "Ürünler" seçilmezse, ürün sayfalarında ana buton görünmez, sadece Yüzen Buton
                                        (Sticky) görünür (eğer aktifse).
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th>Mesaj Şablonu</th>
                                <td>
                                    <textarea id="wws_message" name="wws_settings[message]" rows="6"
                                        class="large-text code"><?php echo esc_textarea($opts['message']); ?></textarea>
                                    <div class="wws-tags">
                                        <span>{urun_adi}</span> <span>{fiyat}</span> <span>{url}</span> <span>{adet}</span>
                                        <span>{varyasyon}</span>
                                    </div>
                                    <p class="description">Boş bırakırsanız varsayılan şablon kullanılır.</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Davranış</th>
                                <td>
                                    <label class="wws-switch-label">
                                        <input type="checkbox" name="wws_settings[open_new_tab]" value="1" <?php checked('1', $opts['open_new_tab']); ?>>
                                        WhatsApp'ı her zaman yeni sekmede aç
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- TAB: DESIGN -->
                    <div id="tab-design" class="wws-tab-content">
                        <div class="wws-section-title">Ürün/Yazı İçi Buton Tasarımı</div>
                        <p class="description" style="margin-bottom:20px;">Bu ayarlar, sayfa içeriğinin altına eklenen ana buton
                            içindir.</p>

                        <table class="form-table">
                            <tr>
                                <th>Buton Metni</th>
                                <td>
                                    <input type="text" name="wws_settings[btn_text]"
                                        value="<?php echo esc_attr($opts['btn_text']); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th>Stil Tipi</th>
                                <td>
                                    <select id="wws_btn_type" name="wws_settings[btn_type]">
                                        <option value="solid" <?php selected('solid', $opts['btn_type']); ?>>Tek Renk (Solid)
                                        </option>
                                        <option value="gradient" <?php selected('gradient', $opts['btn_type']); ?>>Gradyan
                                            (Gradient)</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="row-solid-color" class="<?php echo $opts['btn_type'] === 'gradient' ? 'hidden' : ''; ?>">
                                <th>Arkaplan Rengi</th>
                                <td>
                                    <input type="text" name="wws_settings[btn_bg_color]"
                                        value="<?php echo esc_attr($opts['btn_bg_color']); ?>" class="wws-color-field"
                                        data-alpha="true" />
                                </td>
                            </tr>
                            <tr id="row-gradient-color" class="<?php echo $opts['btn_type'] === 'solid' ? 'hidden' : ''; ?>">
                                <th>Gradyan Renkleri</th>
                                <td>
                                    <div class="color-row">
                                        <div>
                                            <label>Başlangıç</label>
                                            <input type="text" name="wws_settings[btn_gradient_1]"
                                                value="<?php echo esc_attr($opts['btn_gradient_1']); ?>"
                                                class="wws-color-field" />
                                        </div>
                                        <div>
                                            <label>Bitiş</label>
                                            <input type="text" name="wws_settings[btn_gradient_2]"
                                                value="<?php echo esc_attr($opts['btn_gradient_2']); ?>"
                                                class="wws-color-field" />
                                        </div>
                                        <div>
                                            <label>Açı (°)</label>
                                            <input type="number" name="wws_settings[btn_gradient_deg]"
                                                value="<?php echo esc_attr($opts['btn_gradient_deg']); ?>" class="small-text" />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Yazı Rengi</th>
                                <td>
                                    <input type="text" name="wws_settings[btn_text_color]"
                                        value="<?php echo esc_attr($opts['btn_text_color']); ?>" class="wws-color-field" />
                                </td>
                            </tr>
                            <tr>
                                <th>Boyut & Efektler</th>
                                <td>
                                    <fieldset>
                                        <label>Boyut: </label>
                                        <select name="wws_settings[btn_size]">
                                            <option value="small" <?php selected('small', $opts['btn_size']); ?>>Küçük
                                            </option>
                                            <option value="medium" <?php selected('medium', $opts['btn_size']); ?>>Orta
                                            </option>
                                            <option value="large" <?php selected('large', $opts['btn_size']); ?>>Büyük
                                            </option>
                                        </select>
                                        <br><br>
                                        <label>Animasyon: </label>
                                        <select name="wws_settings[btn_animation]">
                                            <option value="none" <?php selected('none', $opts['btn_animation']); ?>>Yok
                                            </option>
                                            <option value="pulse" <?php selected('pulse', $opts['btn_animation']); ?>>Pulse
                                            </option>
                                            <option value="shake" <?php selected('shake', $opts['btn_animation']); ?>>Shake
                                            </option>
                                            <option value="bounce" <?php selected('bounce', $opts['btn_animation']); ?>>Bounce
                                            </option>
                                        </select>
                                        <br><br>
                                        <label>
                                            <input type="checkbox" name="wws_settings[btn_shadow]" value="1" <?php checked('1', $opts['btn_shadow']); ?>>
                                            Gölge Efekti
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="wws_settings[show_icon]" value="1" <?php checked('1', $opts['show_icon']); ?>>
                                            WhatsApp İkonu Göster
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th>Köşe Yuvarlama</th>
                                <td>
                                    <input type="number" name="wws_settings[btn_radius]"
                                        value="<?php echo esc_attr($opts['btn_radius']); ?>" min="0" max="100"
                                        class="small-text" /> px
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- TAB: FLOATING -->
                    <div id="tab-floating" class="wws-tab-content">
                        <div class="wws-section-title">Yüzen Buton (Sticky Widget)</div>
                        <p class="description">Ekranın köşesinde sabit duran buton ayarları.</p>

                        <table class="form-table">
                            <tr>
                                <th>Durum</th>
                                <td>
                                    <label class="wws-switch">
                                        <input type="checkbox" name="wws_settings[enable_floating]" value="1" <?php checked('1', $opts['enable_floating']); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <span
                                        class="switch-status"><?php echo $opts['enable_floating'] === '1' ? 'Aktif' : 'Pasif'; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Masaüstü Metni</th>
                                <td>
                                    <input type="text" name="wws_settings[floating_text]"
                                        value="<?php echo esc_attr($opts['floating_text']); ?>" placeholder="Örn: Bize Ulaşın"
                                        class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th>Mobil Ayarları</th>
                                <td>
                                    <fieldset class="wws-fieldset">
                                        <label>
                                            <input type="checkbox" name="wws_settings[floating_mobile]" value="1" <?php checked('1', $opts['floating_mobile']); ?> />
                                            <strong>Mobilde Göster</strong> (Açık önerilir)
                                        </label>
                                        <br>
                                        <hr><br>
                                        <label>
                                            <input type="checkbox" name="wws_settings[floating_mobile_text]" value="1" <?php checked('1', $opts['floating_mobile_text']); ?> />
                                            Mobilde de metni göster
                                        </label>
                                        <br>
                                        <input type="text" name="wws_settings[floating_text_mobile]"
                                            value="<?php echo esc_attr(isset($opts['floating_text_mobile']) ? $opts['floating_text_mobile'] : ''); ?>"
                                            placeholder="Mobil için kısa metin (Boşsa üsttekini kullanır)" class="regular-text"
                                            style="margin-top:5px;" />
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th>Pozisyon</th>
                                <td>
                                    <select name="wws_settings[floating_pos]">
                                        <option value="right" <?php selected('right', $opts['floating_pos']); ?>>Sağ Alt
                                        </option>
                                        <option value="left" <?php selected('left', $opts['floating_pos']); ?>>Sol Alt
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- TAB: VISIBILITY -->
                    <div id="tab-visibility" class="wws-tab-content">
                        <table class="form-table">
                            <tr>
                                <th>Gelişmiş Form</th>
                                <td>
                                    <fieldset>
                                        <label><input type="checkbox" name="wws_settings[enable_qty]" value="1" <?php checked('1', $opts['enable_qty']); ?> /> Müşteri <strong>Adet</strong>
                                            seçebilsin</label><br><br>
                                        <label><input type="checkbox" name="wws_settings[enable_variations]" value="1" <?php checked('1', $opts['enable_variations']); ?> /> Müşteri
                                            <strong>Not/Varyasyon</strong> girebilsin</label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th>Esnaf Modu</th>
                                <td>
                                    <div class="wws-alert">
                                        <label class="wws-switch">
                                            <input type="checkbox" name="wws_settings[esnaf_mode]" value="1" <?php checked('1', $opts['esnaf_mode']); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                        <strong>"Fiyat Sor" Modu</strong>
                                        <p>Bu mod aktifken, WhatsApp mesajında fiyat yerine "Fiyat Sorunuz" yazar.</p>
                                        <p><em>Not: Fiyatın sitede gizlenmesi temanıza bağlıdır. CSS ile
                                                <code>.woocommerce-Price-amount</code> sınıfını gizlemeniz gerekebilir.</em></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

                <div class="wws-footer-bar">
                    <?php submit_button('Ayarları Kaydet', 'primary large', 'submit', false); ?>
                    <button type="button" id="wws_reset_default" class="button button-secondary button-large"
                        style="float:left;">Varsayılana Döndür</button>
                </div>

            </form>
        </div>
        <?php
    }
}
