<?php
$page_title		 = "About";		  	  		  							 // Sayfa Başlığı
$body_class	 	 = "";			   			  							// <body> tagı css sitili
$include_db	 	 = 0;			   	  		 						   // 0 = hayır, 1 = evet
$menu_type		 = 1;												  // 0 = Sadece mobil ekranlarda gösteriliyor, 1 = Her ekranda gösteriliyor
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_header.php');	 // Header 
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';		// Navbar
?>
<!-- İçerik başlangıcı -->

<div class="container pt-4 my-5">
 
    <div class="content-middle my-5" id="about-tr">
	

        <h1>Hakkında</h1>
		
        <p>
            Bu sistem, proje klasörlerini otomatik olarak listeleyen, yerel sunucuda SQL sorgularını çalıştırabileceğiniz, modül ekleyebileceğiniz, eklenen modülleri dinamik olarak tarayıp gösteren ve çoklu dil desteği sağlayan basit bir kontrol panelidir.
        </p>
        <ul>
			<li><strong>Projeler:</strong> Ana dizindeki klasörler otomatik olarak taranır. Her klasörün içinde, aynı isimde bir PNG dosyası varsa, o logo olarak kullanılır ve projeler 6'şar sütun halinde gösterilir.</li>
            <li><strong>SQL Terminali:</strong> Terminal üzerinden SQL sorguları girip çalıştırabilir, sorgu geçmişinde Shift + ArrowUp/ArrowDown tuşları ile önceki sorgular arasında gezinebilirsiniz.</li>
            
            <li><strong>Modüller:</strong> <code>.hamu/modules</code> dizinindeki PHP dosyaları taranır; her dosyanın &lt;title&gt; etiketi okunarak menüye eklenir. Böylece yeni modül eklemek son derece kolaydır.</li>
            <li><strong>Çoklu Dil Desteği:</strong> Sistem, <code>lang.php</code> dosyasında tanımlı metinlerle çalışır. Yeni dil eklemek çok basittir; sadece yeni bir dil dizisi ekleyip, anahtarları oluşturmanız yeterlidir. Seçili dil session üzerinden tutulur.</li>
        </ul>
        <p>
            Ek olarak, SQL terminali tüm veritabanı sürücülerini (MySQL, PostgreSQL, SQLite, SQL Server, Oracle) destekler; "USE", "CREATE", "DROP", "ALTER" gibi komutlar dinamik olarak işlenir ve eğer sunucu kapalıysa veya seçili veritabanı bulunamazsa otomatik fallback mekanizması devreye girer.
        </p>
    </div>

    <div class="content" id="about-en">
	
        <h1>About</h1>
        <p>
            This system is a comprehensive control panel that allows you to execute SQL queries on a local server, automatically lists project folders, dynamically scans modules, and supports multi-language functionality.
        </p>
        <ul>
            <li><strong>SQL Terminal:</strong> You can enter and execute SQL queries through the terminal and navigate through your query history using Shift + ArrowUp/ArrowDown keys.</li>
            <li><strong>Projects:</strong> Folders in the root directory are automatically scanned. If a folder contains a PNG file with the same name, that image is used as its logo. Projects are displayed in a 6-column grid layout.</li>
            <li><strong>Modules:</strong> PHP files in the <code>.hamu/modules</code> directory are scanned, and each module's &lt;title&gt; tag is read and added to the module menu, making it very easy to add new modules.</li>
            <li><strong>Multi-Language Support:</strong> The system uses a language file (<code>lang.php</code>) that contains all text keys. Adding a new language is as simple as adding a new array entry with the corresponding keys. The selected language is stored in the session.</li>
        </ul>
        <p>
            Additionally, the SQL terminal supports all major DB drivers (MySQL, PostgreSQL, SQLite, SQL Server, Oracle). Commands like "USE", "CREATE", "DROP", "ALTER" are dynamically processed; if the server is down or no active database is selected, an automatic fallback mechanism is activated.
        </p>
    </div>
 <div class="text-center mb-3">
    <?php $logoh = 125; include 'images/logo.php'; ?>
	<br>
	  </div>
	</div>
	<div id="result" style="display:none;"></div>

    <script>
        // "currentLang" değişkenini PHP'den alıyoruz.
        var currentLang = "<?= $lang ?>";
        // Eğer dil TR ise Türkçe div, değilse İngilizce div gösterilsin.
        if(currentLang === "TR"){
            document.getElementById("about-tr").style.display = "block";
            document.getElementById("about-en").style.display = "none";
        } else {
            document.getElementById("about-tr").style.display = "none";
            document.getElementById("about-en").style.display = "block";
        }
    </script>

<!-- İçerik sonu -->

<?php
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_footer.php');		// Header 
?>