# NGINX Bilgilendirme

Bu dosya, **HAMU Local Projects Panel** uygulamasını Nginx üzerinde çalıştırmak isteyenler için örnek yapılandırma içermektedir.  
Aşağıdaki ayarlar, uygulamanın PHP dosyalarının çalışmasını, `.json` gibi hassas dosyaların engellenmesini ve tüm isteklerin `index.php` üzerinden yönlendirilmesini sağlar.

---

## Örnek Sunucu Yapılandırması

`/etc/nginx/sites-available/hamu.conf` dosyası içine ekleyebilirsiniz:

```nginx
server {
    listen 80;
    server_name seninsite.com;   # kendi domain veya IP adresini yaz
    root /var/www/hamu;          # projenizin kök klasörü
    index index.php;

    # Tüm istekleri önce dosya/dizin kontrol et, yoksa index.php'ye yönlendir
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # JSON dosyalarını engelle
    location ~ \.json$ {
        deny all;
        return 403;
    }

    # PHP-FPM için ayarlar
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # PHP versiyonunuza göre güncelleyin
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Güvenlik için ek olarak kapatmak isteyebileceğiniz dosya tipleri
    location ~* \.(env|ini|log)$ {
        deny all;
        return 403;
    }
}