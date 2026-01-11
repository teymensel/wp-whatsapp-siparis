jQuery(document).ready(function ($) {

    /* ==========================================================================
       Reset to Default (Client Side)
       ========================================================================== */
    $('#wws_reset_default').on('click', function (e) {
        e.preventDefault();

        if (confirm(wwsAdmin.confirm_reset)) {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Sıfırlanıyor...');

            $.post(wwsAdmin.ajax_url, {
                action: 'wws_reset_settings',
                nonce: wwsAdmin.nonce
            }, function (response) {
                if (response.success) {
                    alert('Ayarlar başarıyla sıfırlandı. Sayfa yenileniyor...');
                    window.location.reload();
                } else {
                    alert('Hata: ' + response.data);
                    $btn.prop('disabled', false).text('Varsayılana Döndür');
                }
            }).fail(function () {
                alert('İletişim hatası oluştu.');
                $btn.prop('disabled', false).text('Varsayılana Döndür');
            });
        }
    });

    /* ==========================================================================
       JS Tabs Logic
       ========================================================================== */
    var $tabs = $('.wws-js-tabs .nav-tab');
    var $contents = $('.wws-tab-content');

    $tabs.on('click', function (e) {
        e.preventDefault();

        // Remove active class from all
        $tabs.removeClass('nav-tab-active');
        $contents.removeClass('active').hide(); // Ensuring hide() is called

        // Add active to clicked
        $(this).addClass('nav-tab-active');

        // Show content
        var target = $(this).attr('href');
        $(target).addClass('active').show(); // Ensuring show() is called
        var defaultMsg = "Merhaba, şu ürünle ilgileniyorum:\n\n*Urun*: {urun_adi}\n*Fiyat*: {fiyat}\n*Adet*: {adet}\n*Not*: {varyasyon}\n\n*Link*: {url}";
        // Preserve logic: if user was in a specific tab, we might want to store it in url hash, 
        // but since we want to avoid reloading, we just swap visibility. 
    });

    // Trigger click on first tab or hashmatch if present to ensure correct state on load
    // Actually CSS handles .active as display block, others none.
    $('.wws-tab-content:not(.active)').hide();


    /* ==========================================================================
       Color Picker & Preview Logic (Existing)
       ========================================================================== */
    $('.wws-color-field').wpColorPicker({
        change: function (event, ui) {
            setTimeout(updatePreview, 10);
        }
    });

    // Selectors
    var $inputs = $('input[name^="wws_settings"], textarea, select');

    // Settings Fields
    var $btnType = $('#wws_btn_type');
    var $rowSolid = $('#row-solid-color');
    var $rowGrad = $('#row-gradient-color');

    var $btnBg = $('#wws_btn_bg_color');
    var $btnGrad1 = $('#wws_btn_gradient_1');
    var $btnGrad2 = $('#wws_btn_gradient_2');
    var $btnGradDeg = $('#wws_btn_gradient_deg');

    var $btnSize = $('input[name="wws_settings[btn_size]"]');
    var $btnAnim = $('#wws_btn_animation');
    var $btnRadius = $('input[name="wws_settings[btn_radius]"]');
    var $btnShadow = $('input[name="wws_settings[btn_shadow]"]');

    // Preview Elements
    var $pBox = $('#preview-box');
    var $pBtn = $('#preview-btn');
    var $pText = $pBtn.find('.wws-btn-text');
    var $pIcon = $('#preview-icon');
    var $toggleMode = $('.wws-toggle-mode');

    function updatePreview() {
        // Toggle Gradient/Solid fields visibility
        if ($btnType.val() === 'gradient') {
            $rowSolid.addClass('hidden');
            $rowGrad.removeClass('hidden');

            // Apply Gradient
            var deg = $btnGradDeg.val() || '45';
            var c1 = $btnGrad1.val() || '#25D366';
            var c2 = $btnGrad2.val() || '#128C7E';
            $pBtn.css('background', 'linear-gradient(' + deg + 'deg, ' + c1 + ', ' + c2 + ')');
        } else {
            $rowSolid.removeClass('hidden');
            $rowGrad.addClass('hidden');
            $pBtn.css('background', $btnBg.val());
        }

        // Text Color
        var txtColor = $('#wws_btn_text_color').val();
        $pBtn.css('color', txtColor);

        // Text
        $pText.text($('#wws_btn_text').val());

        // Radius
        if ($btnRadius.length) {
            $pBtn.css('border-radius', $btnRadius.val() + 'px');
        }

        // Shadow
        if ($btnShadow.length) {
            if ($btnShadow.is(':checked')) {
                $pBtn.css('box-shadow', '0 6px 20px rgba(0,0,0,0.15)');
            } else {
                $pBtn.css('box-shadow', 'none');
            }
        }

        // Size (Mocking padding)
        var size = $btnSize.filter(':checked').val();
        if (size === 'small') {
            $pBtn.css({ 'padding': '8px 16px', 'font-size': '14px' });
        } else if (size === 'medium') {
            $pBtn.css({ 'padding': '12px 28px', 'font-size': '16px' });
        } else if (size === 'large') {
            $pBtn.css({ 'padding': '16px 36px', 'font-size': '18px' });
        }

        // Animation
        $pBtn.removeClass('wws-anim-mobile wws-anim-pulse wws-anim-shake wws-anim-bounce');
        var anim = $btnAnim.val();
        if (anim && anim !== 'none') {
            $pBtn.addClass('wws-anim-' + anim);
        }

        // Icon Visibility...
        var showIcon = $('input[name="wws_settings[show_icon]"]').is(':checked');
        showIcon ? $pIcon.show() : $pIcon.hide();
    }

    // Listeners
    $inputs.on('change input', updatePreview);
    $btnType.on('change', updatePreview);
    $btnSize.on('change', updatePreview);

    // Dark/Light Mode Toggle
    $toggleMode.on('click', function () {
        $pBox.toggleClass('dark-mode');
    });

    // Quick Tag Insert
    $('.wws-tags span').on('click', function () {
        var tag = $(this).text();
        var $msg = $('#wws_message');
        var v = $msg.val();
        $msg.val(v + tag);
    });

    // Initial
    updatePreview();

});
