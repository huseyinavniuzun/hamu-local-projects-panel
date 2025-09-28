<?php  
$page_title      = "Test Php";                // Sayfa Başlığı
$body_class      = "";                        // <body> tagı CSS stili
$include_db      = 1;                         // 0 = hayır, 1 = evet
$menu_type       = 1;                         // 0 = Sadece mobil, 1 = Her ekranda gösteriliyor
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_header.php'; // Header
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';   // Navbar
?>

<div class="container-module">
<div class="content-large">
  <h1>Test PHP </h1>
	<div data-lang="tr" class="pb-4">Php Kodunu yaz ve çalıştır (Php açılış ve kapanış taglarını kullanma):</div>
	<div data-lang="en" class="pb-4">Enter PHP code below (do not include opening/closing PHP tags)</div>
   <textarea id="phpCode" style="width: 100%; height: 150px;">
   echo "Test, test...!";
   </textarea>
  
  <button id="runBtn" type="button" class="btn btn-secondary">
  ˂?php 
  <div data-lang="tr"> ' Kodu Çalıştır ' </div>
  <div data-lang="en"> ' Run Code '  </div>
  ?˃
  </button>
  
  
  <div class="pt-4" id="result"></div>
</div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_footer.php'; ?>

<script>
var currentLang = "<?= $lang ?>";
if (currentLang === "TR") {
  document.querySelectorAll('[data-lang="tr"]').forEach(function(el) {
    el.style.display = "block";
  });
  document.querySelectorAll('[data-lang="en"]').forEach(function(el) {
    el.style.display = "none";
  });
} else {
  document.querySelectorAll('[data-lang="tr"]').forEach(function(el) {
    el.style.display = "none";
  });
  document.querySelectorAll('[data-lang="en"]').forEach(function(el) {
    el.style.display = "block";
  });
}
</script>