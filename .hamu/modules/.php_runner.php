<?php
// php_runner.php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
 if (isset($_GET['lang']) && in_array($_GET['lang'], ['TR', 'EN'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/lang.php';		 // Dil Dosyası


// POST isteği ile 'phpCode' gönderilmişse, kodu çalıştırır.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phpCode'])) {
    $code = $_POST['phpCode'];

    // Çıktıyı yakalamak için output buffering başlatıyoruz.
    ob_start();
    try {
        // Kodun çalıştırılmasını anonim fonksiyon içine alarak yapıyoruz.
        $runner = function() use ($code) {
            eval($code);
        };
        $runner();
    } catch (Throwable $e) {
        echo "<b>".__l('error')."</b> " . htmlspecialchars($e->getMessage());
    }
    $output = ob_get_clean();
    echo empty($output) ? "<b>".__l('error')."</b>" : $output;
    exit;
}
?>
  <div id="result"></div>

  <script>
    $(document).ready(function(){
      $("#runBtn").click(function(){
        var code = $("#phpCode").val();
        $("#result").html("<b>Running code...</b>");
        $.post("", { phpCode: code }, function(res) {
          $("#result").html(res);
        }).fail(function(){
          $("#result").html("<b>Error executing code.</b>");
        });
      });
    });
  </script>
