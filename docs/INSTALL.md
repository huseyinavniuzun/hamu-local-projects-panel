# Kurulum Rehberi (INSTALL.md)

Bu doküman, **HAMU Local Projects Panel** uygulamasının kurulumu için adım adım yönergeleri içerir.

---

## 1. Dosya Yapısı

Uygulama şu yapıda olmalıdır:

```
/docroot/
  index.php
  .htaccess
  .hamu/
    config.php
    config.json
    functions.php
    db_actions.php
    css/
    js/
    images/
    modules/
    cache/
    logs/
```

- `index.php` kök dizinde bulunmalıdır.
- Tüm uygulama kodları `.hamu/` klasöründe bulunur.
- `cache/` ve `logs/` klasörleri yazılabilir olmalıdır.

---

## 2. Apache Yapılandırması

Kök dizinde bulunan `.htaccess` dosyası, gerekli yönlendirme ve güvenlik ayarlarını içerir.

### Örnek `.htaccess`

```apache
# JSON, ENV, INI, LOG dosyalarını kapat
<FilesMatch "\.(json|env|ini|log)$">
    Require all denied
</FilesMatch>

# Dosya yoksa index.php'ye yönlendir
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

> Not: Apache yapılandırmanızda `AllowOverride All` aktif olmalıdır.

---

## 3. Nginx Yapılandırması

Nginx kullanıcıları için örnek yapılandırma `docs/NGINX_Bilgilendirme.md` dosyasında verilmiştir.

Kısaca:

```nginx
server {
    listen 80;
    server_name seninsite.com;
    root /var/www/hamu;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.json$ {
        deny all;
        return 403;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(env|ini|log)$ {
        deny all;
        return 403;
    }
}
```

---

## 4. Config Dosyası

- `config.json` uygulama tarafından otomatik oluşturuluyorsa, ayrıca işlem yapmanıza gerek yoktur.
- Eğer otomatik oluşmazsa, `config.example.json` dosyasını kopyalayarak kendi bilgilerinize göre düzenleyin:

```bash
cp .hamu/config.example.json .hamu/config.json
```

---

## 5. İzinler

`cache` ve `logs` klasörlerinin yazılabilir olduğundan emin olun:

```bash
chmod -R 775 .hamu/cache .hamu/logs
```

Paylaşımlı hosting kullanıyorsanız, kontrol panelinden bu klasörleri yazılabilir olarak işaretleyin.

---

## 6. İlk Çalıştırma

- Apache/Nginx kurulumunuzu yaptıktan sonra tarayıcıdan:

```
http://alanadiniz/
```

adresine gidin.

- Lokal test için PHP’nin dahili server’ını da kullanabilirsiniz:

```bash
php -S localhost:8000 -t .
```

---

## 7. Güvenlik Notları

- `config.json`, `db_actions.php`, `database.php`, `functions.php` gibi dosyalar doğrudan dış erişime kapalı olmalıdır.
- Apache veya Nginx yapılandırmasında `.json`, `.env`, `.ini`, `.log` gibi dosyaları engelleyin.
- Üretim ortamında `.hamu` klasörünü tamamen kapatıp sadece `index.php` üzerinden yönlendirme yapmak daha güvenlidir.

---

Bu kurulum adımlarını tamamladıktan sonra uygulamanız çalışmaya hazırdır.
