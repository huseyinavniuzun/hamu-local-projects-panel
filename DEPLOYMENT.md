# HAMU — huseyinavniuzun.com için kurulum

Bu sürüm özel yönetim panelidir. HTTPS ve önceden tanımlı yönetici parolası olmadan internet isteklerini reddeder. Dosya yöneticisi, SQL terminali, FTP/proje işlemleri ve geliştirme modülleri yalnızca 127.0.0.1/::1 erişiminde kullanılabilir. Uzak sunucuda bu araçları çalıştırmak için SSH tüneli kullanın; ters proxy ile tüm ziyaretçileri loopback olarak göstermeyin.

1. ZIP içeriğini ayrı bir panel web köküne açın. Yerel www klasörünün tamamını yüklemeyin. PHP 8.2+; openssl, mbstring, PDO ve kullanılacak PDO sürücüsü gerekir.
2. Sunucu terminalinde en az 16 karakterlik benzersiz parola ile `HAMU_SETUP_PASSWORD='kendi-parolanız' php setup-admin.php` çalıştırın. Parolayı kabuk geçmişine yazmamak için ortam değişkenini gizli girişle ayarlayın. Kurulum .hamu/.env içinde rastgele şifreleme anahtarı oluşturur. Örnek parolayı kullanmayın.
3. .hamu/cache ve .hamu/logs yalnızca PHP kullanıcısına yazılabilir olmalı; .hamu/.env dosyasını 0600 yapın. Yapılandırma ve anahtarı birlikte, erişimi kısıtlı biçimde yedekleyin.
4. Apache 2.4 için AllowOverride ile paketin iki .htaccess dosyasını etkinleştirin. Nginx için .hamu/nginx.sample.conf kurallarını HTTPS server bloğuna uyarlayın; PHP-FPM socket yolunu sunucunuza göre değiştirin. .hamu altındaki hiçbir dosyaya doğrudan HTTP erişimi vermeyin.
5. HTTPS sertifikasını kurun, HTTP erişimini sunucu düzeyinde HTTPS'e yönlendirin. TLS ters proxyde sonlanıyorsa güvenilir sunucu yapılandırması PHP HTTPS değişkenini sağlamalıdır; ziyaretçinin gönderdiği X-Forwarded-Proto başlığına güvenilmez.
6. Panelde giriş yaptıktan sonra authenticator/TOTP kaydını tamamlayın, kurtarma kodlarını çevrimdışı saklayın. E-posta gönderimi mevcut PHP mail/SMTP yapılandırmasına bağlıdır; bu sunucuda doğrulanmadı.
7. /?api=2fa&op=status girişsiz 401; /.hamu/.env, /.git/config ve /.hamu/cache/app_config.json 403/404; /?a=assets/../cache/app_config.json 404 vermeli. Girişsiz ana sayfa yalnızca giriş formunu göstermeli.

Doğrulananlar: PHP 8.3 sözdizimi, HTTP giriş ve ana panel, oturumsuz 2FA API engeli, CSRF engeli, modül/asset dizin geçişi engeli, internet erişiminde HTTPS/yapılandırma/SQL kısıtları. Gerçek hosting, Apache/Nginx kuralları, SMTP/FTP ve canlı veritabanları bu ortamda uçtan uca doğrulanmadı. Bu paket canlı sunucuya yüklenmedi; sıfır açık garantisi değildir.

Yerel Laragon PHP ayarında pdo_oci yükleme uyarısı görüldü. Oracle kullanmıyorsanız php.ini içindeki ilgili extension satırını kapatın; kullanıyorsanız Oracle istemci bağımlılığını kurun.

Güvenlik dayanakları: https://www.php.net/manual/en/session.security.ini.php ve https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
