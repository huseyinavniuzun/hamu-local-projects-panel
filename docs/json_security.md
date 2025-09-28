# JSON Dosyalarını (config.json gibi) Güvenli Hale Getirme Rehberi

Bu doküman, sunucunuzdaki hassas JSON dosyalarını (örneğin `config.json`) güvenli hale getirme yöntemlerini açıklamaktadır. Doküman, hem Apache hem de Nginx sunucuları için gerekli ayarları detaylı olarak anlatmaktadır.

---

## Genel Bakış

Veritabanı bilgileri gibi hassas veriler içeren JSON yapılandırma dosyaları kesinlikle dışarıdan erişilebilir olmamalıdır. Bu belgede, Apache ve Nginx sunucularınız için gerekli yapılandırmalar sunulmuştur.

---

## Apache Sunucuları İçin

### JSON Dosyalarına Tüm Erişimi Engelleme

Hassas JSON dosyalarının bulunduğu klasörde (örneğin `.hamu/` dizininde) `.htaccess` dosyası oluşturup, aşağıdaki kuralları ekleyebilirsiniz:

#### Apache 2.4 ve üzeri için:

```apache
<FilesMatch "\.(json)$">
    Require all denied
</FilesMatch>
```

#### Apache 2.2 ve öncesi sürümler için:

```apache
<FilesMatch "\.(json)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Belirli IP Adreslerine İzin Verme

Eğer JSON dosyalarına sadece belirli IP adreslerinden (örneğin localhost ya da iç ağdan) erişime izin vermek istiyorsanız aşağıdaki yapılandırmayı kullanabilirsiniz:

```apache
<FilesMatch "\.(json)$">
    Require ip 127.0.0.1 192.168.1.25
    Deny from all
</FilesMatch>
```

### Önemli Notlar:
- `.htaccess` kullanabilmek için Apache yapılandırmanızda `AllowOverride All` seçeneğinin açık olması gerekmektedir.
- `.htaccess` dosyasını JSON dosyalarının bulunduğu dizine (örneğin `.hamu/`) yerleştirmeniz gerekmektedir.

---

## Nginx Sunucuları İçin

Nginx, `.htaccess` dosyasını kullanmaz. Bunun yerine sunucu yapılandırma dosyanıza doğrudan kural eklemeniz gereklidir.

### Belirli Bir Dizin İçinde JSON Dosyalarını Engelleme

Nginx yapılandırma dosyanıza (`/etc/nginx/sites-available/site.conf`) aşağıdaki kodu ekleyebilirsiniz:

```nginx
location ^~ /.hamu/ {
    location ~ \.json$ {
        deny all;
        return 403;
    }
}
```

### Belirli IP Adreslerine İzin Verme

Eğer sadece belirli IP adreslerine izin vermek isterseniz aşağıdaki yapılandırmayı kullanabilirsiniz:

```nginx
location ^~ /.hamu/ {
    location ~ \.json$ {
        allow 127.0.0.1;
        allow 192.168.1.25;
        deny all;
        return 403;
    }
}
```

### Yapılandırma Sonrası

Değişiklikleri yaptıktan sonra yapılandırmanızı test edin ve Nginx'i yeniden yükleyin:

```bash
sudo nginx -t
```

Eğer yapılandırmanızda hata yoksa, aşağıdaki komutla Nginx sunucunuzu yeniden yükleyebilirsiniz:

```bash
sudo systemctl reload nginx
```

---

Bu ayarlarla JSON yapılandırma dosyalarınız dışarıdan gelecek yetkisiz erişimlere karşı korunmuş olacaktır.

