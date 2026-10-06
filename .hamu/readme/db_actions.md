# Database Manager & Mini Terminal

## Amaç

Bu modül, MySQL/MariaDB veritabanlarını **web tabanlı terminal** ile yönetmek için geliştirilmiştir.  
Kullanıcı, tarayıcı üzerinden SQL sorguları çalıştırabilir, autocomplete desteği alabilir ve geçmiş sorgular arasında gezinebilir.

---

## Bileşenler

### 1) `database.php`

- PDO üzerinden bağlantı açar.
- Bağlantı bilgilerini `.hamu/cache/app_config.json` dosyasından okur.
- Tüm sorgu çalıştırma işlemleri için ortak altyapıdır.

### 2) `actions.php`

- AJAX ile gelen istekleri yakalar.
- Fonksiyonları:
  - `refresh_db` → Sunucudaki veritabanlarını listeler.
  - `action=tooltip` → Üzerine gelinen DB’nin tablo listesini döndürür.
  - `action=autocomplete` → Aktif DB’deki tablo/kolon isimlerini autocomplete için gönderir.
  - `sql` → Gönderilen SQL sorgusunu çalıştırır, HTML tablo döndürür.

### 3) `hamu.db.actions.min`

- Terminalin client-side kontrolüdür.
- Özellikler:
  - **Autocomplete**: SQL keyword + dinamik tablo/kolon isimleri.
  - **Geçmiş**: Shift + ↑ / ↓ ile önceki sorgulara erişim.
  - **Enter**: Sorgu çalıştır.
  - **Modal**: Sonuç penceresi açılır.
  - **Tooltip**: DB üzerine gelince tablo yapısı gösterilir.

### 4) `index.php`

- Mini terminal arayüzünü render eder.
- Terminal barı alt kısımda bulunur:
  - Sol taraf: `>` ikonlu prompt.
  - Ortada: input kutusu (SQL sorgusu).
  - Sağ taraf: geçmiş ikonuna tıklayarak önceki sorgular listesi açılır.

---

## Kullanım

1. **Veritabanı Seçimi**

   - Sol panelden listelenen veritabanına tıklandığında `USE db;` sorgusu çalıştırılır.
   - Aktif DB yeşil renkle işaretlenir.

2. **SQL Yazma & Çalıştırma**

   - Terminal barına sorgu yazılır.
   - Enter → sorgu çalıştırılır.
   - Sonuç modal pencerede tablo şeklinde gösterilir.

3. **Autocomplete**

   - Yazmaya başlarken SQL komutları + tablo/kolon isimleri öneri olarak çıkar.
   - Tab tuşu ile seçim yapılır.

4. **Geçmiş**

   - Shift + ↑ / ↓ → önceki/sonraki sorgulara erişim.
   - Sağdaki ikon → geçmiş listesi açılır.

5. **Tooltip**
   - Veritabanı adı üzerine gelince tablo yapısı tooltip ile görünür.

---

## Güvenlik

- Kullanıcıdan gelen `sql` parametresi **doğrudan çalıştırılır**. Bu nedenle:
  - Yalnızca admin erişimine açık olmalı.
  - `DROP`, `TRUNCATE`, `ALTER` gibi kritik komutlar için **ek uyarı modalı** önerilir.
- Şifreler `.hamu/cache/app_config.json` içinde şifreli tutulmalıdır.
- Log dosyaları hassas bilgiler içermemelidir.

---

## Loglama

- Tüm sorgular `.hamu/logs/db_terminal.log` içine yazılır.
- Başarılı/başarısız durumlar kaydedilir.

---

## Sık Karşılaşılan Durumlar

- **Autocomplete gelmiyor** → aktif DB seçilmemiş olabilir. Önce `USE db;` çalıştır.
- **Modal açılmıyor** → JS tarafında bootstrap modal yüklenmemiş olabilir.
- **Bağlantı hatası** → `app_config.json` içindeki `db_host`, `db_user`, `db_pass` kontrol edilmeli.

---

## Öneriler

- **Export/Import**: Sorgu sonuçlarını CSV/Excel olarak indirme özelliği eklenebilir.
- **Yetkilendirme**: Kullanıcı rolleri (admin/read-only) eklenebilir.
- **Shortcut**: Ctrl+Enter = çalıştır, Ctrl+K = temizle gibi ek kısayollar konabilir.
- **Highlight**: Syntax highlighting için CodeMirror veya Monaco editor entegre edilebilir.
