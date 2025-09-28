# HAMU LOCAL PROJECTS PANEL

**HAMU LOCAL PROJECTS PANEL**, yerel sunucunuzda bulunan projeleri kolayca yönetebilmeniz için geliştirilmiş, modüler yapıya sahip ve genişletilebilir bir kontrol panelidir. Proje; çoklu dil desteği, dinamik veritabanı kontrolü, modül entegrasyonu, yerel dosya yöneticisi, mini SQL terminali gibi pek çok özelliği bünyesinde barındırır.

---

## 1. Giriş

Bu dokümantasyon, sistemin genel yapısını, klasör/dosya organizasyonunu, modül entegrasyon sürecini ve temel kullanım yönergelerini kapsamaktadır. Geliştiriciler ve kullanıcılar için sistemi daha iyi anlamaya yardımcı olmayı amaçlar.

---

## 2. Klasör Yapısı

Projenin ana dizini ve alt klasörlerinin yapısı aşağıdaki gibidir:

```
/ (Proje Kök Dizin)
│
├── index.php                    # Uygulamanın giriş noktası, ana kontrol paneli
│
└── .hamu/
    │
    ├── h_header.php             # Sayfa üst tag yapısı + css / bootsrap vb
    ├── h_navbar.php             # Navigasyon yapısı (özelleştirilebilir)
    ├── h_sidebar.php            # Menü yapısı, responsive ve offcanvas mod
    ├── h_footer.php             # Sayfa alt tag yapısı (result modal + settings modal)
    ├── config.json              # Sistem ayarlarının tanımlandığı JSON dosyası
    ├── config.php               # Ayarları yöneten PHP dosyası
    ├── database.php             # Veritabanı bağlantı işlemleri
    ├── functions.php            # Genel yardımcı fonksiyonlar
    ├── db_actions.php           # SQL terminal işlevselliği, sorgu yardımı, otomatik tamamlama
    ├── lang.php               	 # Çoklu dil desteği için dil dosyası
    ├── lang_switcher.php        # Dil değiştirme arayüzü
	├── about.php        			 # Geliştirici ve uygulama bilgisi
	├── readme.php        			 # Markdown okuma sayfası
    ├── css/
	│   └── custom.css           # Özel stil dosyası
    │
    ├── js/
    │   ├── general.js           # Genel JavaScript işlevleri
    │   ├── dbactions.js         # SQL terminali ve veritabanı etkileşimleri
	│   └── extra.js           	 # Ekstra sayfa ve modül scriptleri
    │
    ├── images/
    │   ├── favicon/             # Favicon dosyaları
    │   └── manifest.json        # Uygulama manifest dosyası
    │
    └── modules/
         ├── file_manager.php    # Responsive dosya yöneticisi (örnek modül)
         └── (Diğer modül dosyaları)
```

---

## 3. Dosya ve Fonksiyon Açıklamaları

### 3.1 Ana Dosyalar ve Çalıştırma Gereksinimleri

- **index.php:** Panelin giriş noktasıdır. Tüm modüller, navigasyon ve içerik yüklemelerini gerçekleştirir.
- **config.json:** Sistem ayarları (veritabanı ayarları, modüllerin aktiflik durumları vb.) bu dosyada tutulur ve kullanıcı tarafından düzenlenebilir.
- **config.php:** Ayarları global hale getirir, bir kez yükleyerek verimli çalışmayı sağlar.
- **database.php:** Veritabanına bağlanmak için PDO kullanan bağlantı ayarlarını sağlar.
- **functions.php:** Sistemin genel fonksiyonlarını içerir; dosya listeleme, modül tarama, sürüm kontrolü.
- **db_actions.php:** SQL terminali ve sorgu işlemlerini yönetir; kullanıcıların sorgularını çalıştırmasına ve sonuçları görmesine imkân verir.
- **lang.php & lang_switcher.php:** Çoklu dil desteği için gerekli metinleri ve kullanıcı tarafından dil değiştirme işlevlerini sağlar.
- **file_manager.php:** Responsive dosya yöneticisi, dosya yükleme, silme, düzenleme işlemlerini destekler.
- **custom.css:** Görünüm ve temaların özelleştirilmesi için gerekli CSS tanımlarını içerir.

### 3.2 JavaScript Dosyaları

- **general.js:** Arayüz bileşenleri ve genel olay yönetimini sağlar.
- **dbactions.js:** SQL terminali fonksiyonları; sorgu geçmişi, otomatik tamamlama, hata yönetimi.
- **extra.js:** Ekstra modül ve sayfaların JavaScript kodlarını yüklemek için kullanılır.

---

## 4. Modül Ekleme ve Entegrasyon

Yeni modüller, `.hamu/modules/` dizinine eklenir ve aşağıdaki kriterlere uymalıdır:

- Dosya adlarında nokta (.) kullanılmaz.
- Her modül dosyası içinde `$page_title` tanımı yapılmalıdır:

```php
<?php
$page_title = "Modül Başlığı";
require_once $_SERVER['DOCUMENT_ROOT'].'/.hamu/h_header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/.hamu/h_navbar.php';
?>

<h1>Modül İçeriği</h1>

<?php require_once $_SERVER['DOCUMENT_ROOT'].'/.hamu/h_footer.php'; ?>
```

Sistem otomatik olarak yeni modülleri algılar ve kullanıcı arayüzünde listeler.

---

## 5. SQL Terminal Kullanım Yönergeleri

SQL terminalinde sorgularınızı yazıp çalıştırabilirsiniz:

- Terminal üzerinden sorguları çalıştırmak için giriş alanına yazıp çalıştır düğmesine veya Enter tuşuna basınız.
- Çoklu sorguları noktalı virgül (;) ile ayırarak çalıştırabilirsiniz.
- Geçmiş sorgularınız, yukarı/aşağı yön tuşlarıyla erişilebilir.
- Otomatik tamamlama özelliği aktif olup sorgularınızı yazarken öneriler sunulur.

---

## 6. Kullanım ve Özelleştirme

- Sistem ayarları `config.json` üzerinden yapılandırılır.
- Dil ayarları, tema özelleştirme ve ekstra fonksiyonları özelleştirmek mümkündür.

---

## 7. Lisans ve Kullanım

Proje MIT Lisansı altında dağıtılır. Detaylar için:

- [Responsive File Manager GitHub](https://github.com/trippo/ResponsiveFilemanager)

---

## 8. Sistem Gereksinimleri

- PHP 7.4 veya üzeri
- MySQL veya SQLite
- Apache veya Nginx
- PHP Eklentileri: `pdo`, `json`, `mbstring`, `curl`

---

## 9. Sonuç

HAMU LOCAL PROJECTS PANEL, kapsamlı ve modüler bir yapı ile kullanıcı ve geliştiricilere yerel projelerini etkin biçimde yönetmeleri için güçlü araçlar sağlar. Sürekli olarak güncellenmeye devam edecektir.

