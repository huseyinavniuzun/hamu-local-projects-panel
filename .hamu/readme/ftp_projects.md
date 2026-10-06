# Proje İşlemleri (FTP + Yerel Dosya Yöneticisi)

## Amaç

Bu modül, yerel geliştirme klasörleriniz ile uzak FTP sunucuları arasında **senkron şekilde** çalışmayı sağlar. Tek ekranda proje seçimi, yerel dosya yönetimi, toplu yükleme ve uzak FTP yönetimini birleştirir.

---

## Özellikler

### 1) Proje Seçimi ve Durum Alanı

- Sol panelde projeler listelenir.
- Seçili projeye ait **ayar dosyası** (ör. `projeAdi/projeAdi.json`) algılanır ve bağlantı durumu gösterilir.
- Kısayol butonları:
  - **Yerel Dosyalar**
  - **Sunucuya Yükle**
  - **FTP Yöneticisi**
  - **Ayarlar**

### 2) Ayarlar (Proje bazlı)

Proje bazlı JSON dosyasında şu alanlar saklanır:

- Açıklama
- FTP Sunucusu, Port, SSL
- Kullanıcı Adı, Şifre
- FTP Klasörü, Site URL
- İndirme Klasörü

> Not: Şifreleme fonksiyonları kancaları (`encryptData/decryptData`) hazırdır, güvenli hale getirilebilir.

### 3) Yerel Dosya Yöneticisi

- Proje klasöründe **listeleme**, **klasör/dosya oluşturma**, **yeniden adlandırma**, **silme**.
- **Tek dosya yükleme**: Seçili dosya ayarlardaki FTP klasörüne aktarılır.
- **Klasör yükleme**: Seçilen klasör özyinelemeli olarak yüklenir.
- **Üzerine yaz seçeneği**: Açık ise mevcut dosyalar güncellenir.

### 4) Toplu Proje Yükleme

- Seçili projenin tamamını **tek adımda** sunucuya gönderir.
- Üzerine yazma tercihi desteklenir.
- İşlem sonunda özet mesaj + log kaydı oluşturulur.

### 5) FTP Yöneticisi (Uzak)

- Uzak dizinde **listeleme**, **klasör/dosya oluşturma**, **yeniden adlandırma**, **silme**.
- **Dosya indirme**: Belirtilen indirme klasörüne çekilir.

### 6) Loglama

- Tüm işlemler `.hamu/logs/project_actions.log` dosyasına yazılır.
- Örnek: bağlantı denemeleri, indirme/yükleme, hata mesajları, üzerine yazma tercihleri.

---

## Kurulum / Gereksinimler

- PHP `ftp` uzantısı (SSL için `ftp_ssl_connect`).
- Web sunucusu yazma izinleri: proje klasörleri + `.hamu/logs/`.
- (Opsiyonel) Parola şifreleme için `openssl` veya `sodium`.

---

## Güvenlik

- Şifreleri **JSON’da düz metin yerine şifreli** saklayın.
- Loglarda hassas bilgi tutmayın.
- Yol güvenliği için `..` ve `~` girişleri filtrelenmiştir.
- Dosya izinlerinde en az ayrıcalık kullanın.

---

## Sık Karşılaşılan Durumlar

- **Bağlantı hatası**: Host/port/SSL modunu doğrulayın.
- **Üzerine yazma çalışmıyor**: Yetkileri kontrol edin.
- **Eksik PHP eklentisi**: `ftp`, `openssl` aktif olmalı.

---

## Öneriler

- Hariç tutma listesi eklenebilir (örn. `.hamu/`, `node_modules/`).
- Büyük dosyalar için yeniden deneme / pasif mod seçenekleri eklenebilir.
- Şifreleme fonksiyonları aktif edilmelidir.
