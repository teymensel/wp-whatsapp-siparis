=== WP WhatsApp Sipariş & Teklif Sistemi ===
Contributors: Teymensel
Tags: whatsapp, order, woocommerce, siparis, teklif
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later

WooCommerce olmadan veya uyumlu çalışabilen, ürünler için WhatsApp üzerinden sipariş ve teklif alma eklentisi. Esnaf dostu.

== Description ==

Türkiye'deki küçük işletmeler ve esnaflar için geliştirilmiş, hızlı ve pratik bir WhatsApp sipariş eklentisidir. Müşterileriniz ürün sayfalarında "WhatsApp ile Sipariş Ver" butonuna tıklayarak, ürün bilgileriyle birlikte size otomatik mesaj gönderebilirler.

**Özellikler:**

*   **Esnaf Modu**: Fiyat gizleme veya "Fiyat Sor" özelliği.
*   **Tam Özelleştirme**: Buton rengi, metni ve mesaj şablonunu admin panelinden değiştirin.
*   **Dinamik Mesajlar**: Ürün adı, varyasyon, adet ve sayfa linki otomatik olarak mesaja eklenir.
*   **Kolay Kurulum**: Herhangi bir kod bilgisi gerektirmez.
*   **Adet ve Varyasyon**: Müşteriler adet seçebilir ve not/varyasyon ekleyebilir.
*   **Mobil/Masaüstü Uyumu**: Mobilde direkt uygulama, masaüstünde WhatsApp Web açılır.

== Installation ==

1.  Bu klasörü `/wp-content/plugins/` dizinine yükleyin.
2.  WordPress 'Eklentiler' menüsünden eklentiyi etkinleştirin.
3.  'WhatsApp Sipariş' menüsüne giderek telefon numaranızı ve ayarlarınızı yapılandırın.

== Screenshots ==

1.  Admin Ayarları ve Canlı Önizleme
2.  Ürün sayfasında görünüm

== Changelog ==

= 1.1.2 =
*   [Yenilik] GitHub Otomatik Güncelleme (Auto-Updater) entegrasyonu eklendi. Artık yeni sürümler panelden tek tıkla güncellenebilir.

= 1.1.1 =
*   [Düzeltme] Varsayılan mesaj şablonundaki emoji karakterleri, bazı sunucu ve tarayıcılarda kodlama sorunu () yarattığı için kaldırıldı.
*   [Düzeltme] Mesaj şablonu daha sade ve evrensel bir formata (Bold başlıklar) dönüştürüldü.

*   [Önemli] WhatsApp API uç noktası 'wa.me' olarak güncellendi (Stabilite artırıldı).
*   [Önemli] Mesaj kodlama altyapısı yenilendi (Newline ve Emoji sorunları giderildi).
*   [Yenilik] Admin paneli 'Varsayılana Döndür' fonksiyonu güçlendirildi (Tam sıfırlama).
*   [Düzeltme] Eklenti kaldırıldığında veritabanı temizliği eklendi.
*   [UI] Powered by Teymensel imzası eklendi.

= 1.0.0 =
*   İlk sürüm yayınlandı.
