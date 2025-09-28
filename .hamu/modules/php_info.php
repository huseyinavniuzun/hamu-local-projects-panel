<?php
$page_title		 = "Php Info";		  	  		  							 // Sayfa Başlığı
$body_class	 	 = "";			   			  							// <body> tagı css sitili
$include_db	 	 = 1;			   	  		 						   // 0 = hayır, 1 = evet
$menu_type		 = 1;												  // 0 = Sadece mobil ekranlarda gösteriliyor, 1 = Her ekranda gösteriliyor
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_header.php');	 // Header 
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';		// Navbar
?>

  <div class="container-module">
  <div class="content-large">
      <!-- Türkçe Versiyon -->
    <div id="php-tr" style="display:none;">
      <h1 class="mb-4 text-center">PHP Bilgileri</h1>
      <div class="row">
        <!-- Genel Bilgiler -->
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">Genel Bilgiler</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <th>PHP Sürümü</th>
                    <td><?= phpversion(); ?></td>
                  </tr>
                  <tr>
                    <th>PHP SAPI</th>
                    <td><?= php_sapi_name(); ?></td>
                  </tr>
                  <tr>
                    <th>İşletim Sistemi</th>
                    <td><?= PHP_OS; ?></td>
                  </tr>
                  <tr>
                    <th>Sunucu Yazılımı</th>
                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                  </tr>
                  <tr>
                    <th>Döküman Kökü</th>
                    <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
                  </tr>
                  <tr>
                    <th>Yüklü Konfigürasyon Dosyası</th>
                    <td><?= php_ini_loaded_file() ?: 'N/A'; ?></td>
                  </tr>
                  <tr>
                    <th>Varsayılan Zaman Dilimi</th>
                    <td><?= date_default_timezone_get(); ?></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- Önemli PHP Yapılandırması -->
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">Önemli PHP Yapılandırması</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <th>Bellek Limiti</th>
                    <td><?= ini_get('memory_limit'); ?></td>
                  </tr>
                  <tr>
                    <th>Maksimum Çalışma Süresi</th>
                    <td><?= ini_get('max_execution_time'); ?> saniye</td>
                  </tr>
                  <tr>
                    <th>Maksimum Giriş Süresi</th>
                    <td><?= ini_get('max_input_time'); ?> saniye</td>
                  </tr>
                  <tr>
                    <th>Dosya Yüklemeleri</th>
                    <td><?= ini_get('file_uploads') ? 'Etkin' : 'Devre Dışı'; ?></td>
                  </tr>
                  <tr>
                    <th>Yükleme Maks Dosya Boyutu</th>
                    <td><?= ini_get('upload_max_filesize'); ?></td>
                  </tr>
                  <tr>
                    <th>Post Maks Boyutu</th>
                    <td><?= ini_get('post_max_size'); ?></td>
                  </tr>
                  <tr>
                    <th>Hata Gösterimi</th>
                    <td><?= ini_get('display_errors') ? 'Açık' : 'Kapalı'; ?></td>
                  </tr>
                  <tr>
                    <th>Hata Raporlama</th>
                    <td><?= error_reporting(); ?></td>
                  </tr>
                  <tr>
                    <th>Varsayılan Karakter Seti</th>
                    <td><?= ini_get('default_charset'); ?></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Yüklü Uzantılar -->
      <div class="card mb-4">
        <div class="card-header">Yüklü Uzantılar</div>
        <div class="card-body">
          <div class="row">
            <?php
            $extensions = get_loaded_extensions();
            sort($extensions);
            foreach ($extensions as $ext):
            ?>
              <div class="col-6 col-sm-4 col-md-3 mb-2">
                <span class="badge bg-secondary"><?= htmlspecialchars($ext) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Sunucu & Ortam Değişkenleri -->
      <div class="card mb-4">
        <div class="card-header">Sunucu & Ortam Değişkenleri</div>
        <div class="card-body">
          <div class="table-responsive">
            <pre><?php print_r($_SERVER); ?></pre>
          </div>
        </div>
      </div>
      
      <!-- Tüm PHP INI Ayarları -->
      <div class="card mb-4">
        <div class="card-header">Tüm PHP INI Ayarları</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th>Direktif</th>
                  <th>Yerel Değer</th>
                  <th>Küresel Değer</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($ini_all as $key => $values): ?>
                <?php
                  $local_raw = isset($values['local_value']) ? $values['local_value'] : ini_get($key);
                  $global_raw = isset($values['global_value']) ? $values['global_value'] : ini_get($key);
                  $local = format_ini_value($key, $local_raw);
                  $global = format_ini_value($key, $global_raw);
                ?>
                <tr>
                  <td><?= htmlspecialchars($key) ?></td>
                  <td><?= htmlspecialchars($local) ?></td>
                  <td><?= htmlspecialchars($global) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Oturum Bilgileri -->
      <div class="card mb-4">
        <div class="card-header">Oturum Bilgileri</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <tr>
                <th>Oturum Kaydetme Yolu</th>
                <td><?= session_save_path(); ?></td>
              </tr>
              <tr>
                <th>Oturum Adı</th>
                <td><?= session_name(); ?></td>
              </tr>
              <tr>
                <th>Oturum ID</th>
                <td><?= session_id(); ?></td>
              </tr>
              <tr>
                <th>Oturum Çerez Ayarları</th>
                <td><pre><?php print_r(session_get_cookie_params()); ?></pre></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
    
    <!-- English Version -->
    <div id="php-en" style="display:none;">
      <h1 class="mb-4 text-center">PHP Information</h1>
      <div class="row">
        <!-- General Information -->
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">General Information</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <th>PHP Version</th>
                    <td><?= phpversion(); ?></td>
                  </tr>
                  <tr>
                    <th>PHP SAPI</th>
                    <td><?= php_sapi_name(); ?></td>
                  </tr>
                  <tr>
                    <th>Operating System</th>
                    <td><?= PHP_OS; ?></td>
                  </tr>
                  <tr>
                    <th>Server Software</th>
                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                  </tr>
                  <tr>
                    <th>Document Root</th>
                    <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
                  </tr>
                  <tr>
                    <th>Loaded Configuration File</th>
                    <td><?= php_ini_loaded_file() ?: 'N/A'; ?></td>
                  </tr>
                  <tr>
                    <th>Default Time Zone</th>
                    <td><?= date_default_timezone_get(); ?></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- Important PHP Configuration -->
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">Important PHP Configuration</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <th>Memory Limit</th>
                    <td><?= ini_get('memory_limit'); ?></td>
                  </tr>
                  <tr>
                    <th>Max Execution Time</th>
                    <td><?= ini_get('max_execution_time'); ?> seconds</td>
                  </tr>
                  <tr>
                    <th>Max Input Time</th>
                    <td><?= ini_get('max_input_time'); ?> seconds</td>
                  </tr>
                  <tr>
                    <th>File Uploads</th>
                    <td><?= ini_get('file_uploads') ? 'Enabled' : 'Disabled'; ?></td>
                  </tr>
                  <tr>
                    <th>Upload Max Filesize</th>
                    <td><?= ini_get('upload_max_filesize'); ?></td>
                  </tr>
                  <tr>
                    <th>Post Max Size</th>
                    <td><?= ini_get('post_max_size'); ?></td>
                  </tr>
                  <tr>
                    <th>Display Errors</th>
                    <td><?= ini_get('display_errors') ? 'On' : 'Off'; ?></td>
                  </tr>
                  <tr>
                    <th>Error Reporting</th>
                    <td><?= error_reporting(); ?></td>
                  </tr>
                  <tr>
                    <th>Default Charset</th>
                    <td><?= ini_get('default_charset'); ?></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Loaded Extensions -->
      <div class="card mb-4">
        <div class="card-header">Loaded Extensions</div>
        <div class="card-body">
          <div class="row">
            <?php
            $extensions = get_loaded_extensions();
            sort($extensions);
            foreach ($extensions as $ext):
            ?>
              <div class="col-6 col-sm-4 col-md-3 mb-2">
                <span class="badge bg-secondary"><?= htmlspecialchars($ext) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Server & Environment Variables -->
      <div class="card mb-4">
        <div class="card-header">Server & Environment Variables</div>
        <div class="card-body">
          <div class="table-responsive">
            <pre><?php print_r($_SERVER); ?></pre>
          </div>
        </div>
      </div>
      
      <!-- All PHP INI Settings -->
      <div class="card mb-4">
        <div class="card-header">All PHP INI Settings</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th>Directive</th>
                  <th>Local Value</th>
                  <th>Global Value</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($ini_all as $key => $values): ?>
                <?php
                  $local_raw = isset($values['local_value']) ? $values['local_value'] : ini_get($key);
                  $global_raw = isset($values['global_value']) ? $values['global_value'] : ini_get($key);
                  $local = format_ini_value($key, $local_raw);
                  $global = format_ini_value($key, $global_raw);
                ?>
                <tr>
                  <td><?= htmlspecialchars($key) ?></td>
                  <td><?= htmlspecialchars($local) ?></td>
                  <td><?= htmlspecialchars($global) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Session Information -->
      <div class="card mb-4">
        <div class="card-header">Session Information</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <tr>
                <th>Session Save Path</th>
                <td><?= session_save_path(); ?></td>
              </tr>
              <tr>
                <th>Session Name</th>
                <td><?= session_name(); ?></td>
              </tr>
              <tr>
                <th>Session ID</th>
                <td><?= session_id(); ?></td>
              </tr>
              <tr>
                <th>Session Cookie Params</th>
                <td><pre><?php print_r(session_get_cookie_params()); ?></pre></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
 </div>
  
  <script>
    var currentLang = "<?= $lang ?>";
    if(currentLang === "TR"){
      document.getElementById("php-tr").style.display = "block"; 
      document.getElementById("php-en").style.display = "none"; 
    } else {
      document.getElementById("php-tr").style.display = "none"; 
      document.getElementById("php-en").style.display = "block"; 
    }
  </script>
<!-- Footer include -->
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_footer.php';?>