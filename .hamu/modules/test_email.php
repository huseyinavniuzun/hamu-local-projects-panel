<?php
$page_title		 = "Test Email";		  	  		  							 // Sayfa Başlığı
$body_class	 	 = "";			   			  							// <body> tagı css sitili
$include_db	 	 = 1;			   	  		 						   // 0 = hayır, 1 = evet
$menu_type		 = 1;												  // 0 = Sadece mobil ekranlarda gösteriliyor, 1 = Her ekranda gösteriliyor
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_header.php');	 // Header 
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';		// Navbar
?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>

	<div class="container-module">
    <div class="content-large">
        <h2>Mail Test</h2>
        <label  class="test">Gönderen - Sender:</label>
        <input type="text" id="sender_name" value="Adı - Name"  class="test">
        
        <label  class="test">Gönderen ePosta - Sender Mail:</label>
        <input type="email" id="sender_email" value="abc@test.com"  class="test">
        
        <label  class="test">Alıcı  / Receiver Mail:</label>
        <input type="email" id="receiver_email" value="cde@test.com"  class="test">
        
        <label  class="test">Konu / Subject</label>
        <input type="text" id="subject" value="Test Mail"  class="test">
        
        <label class="test">Mesaj / Message (+HTML):</label>
        <textarea id="message" class="test">Test</textarea>
        
        <button onclick="sendEmail()" class="test">Gönder / Send </button>
        <div id="result"></div>
        
    </div>
	</div>

    <script>
        tinymce.init({
            selector: '#message',
            menubar: false,
            plugins: 'lists link image table code',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link image | table | code'
        });

        function sendEmail() {
            let senderName = document.getElementById('sender_name').value;
            let senderEmail = document.getElementById('sender_email').value;
            let receiverEmail = document.getElementById('receiver_email').value;
            let subject = document.getElementById('subject').value;
            let message = tinymce.get('message').getContent();

            fetch('send_email.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `sender_name=${encodeURIComponent(senderName)}&sender_email=${encodeURIComponent(senderEmail)}&receiver_email=${encodeURIComponent(receiverEmail)}&subject=${encodeURIComponent(subject)}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.text())
            .then(data => document.getElementById('result').innerText = data)
            .catch(error => document.getElementById('result').innerText = 'Hata oluştu: ' + error);
        }
    </script>
<div class="modal fade <?= config('first_setup') ? 'show d-block' : '' ?>" 
     id="settingsModal"
     data-bs-backdrop="<?= config('first_setup') ? 'static' : 'true' ?>" 
     tabindex="-1"
     aria-labelledby="settingsModalLabel"
     aria-hidden="<?= config('first_setup') ? 'false' : 'true' ?>">

  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      
      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="settingsModalLabel"><?= __l('settings') ?></h5>
        <!-- first_setup modunda kapanmasın diyorsanız btn-close'u kaldırabilirsiniz -->
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Modal Body -->
      <div class="modal-body">
        <form id="settingsForm">
         <!-- Veritabanı Kullan & Modülleri Göster (yan yana) -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="database_s" 
                       id="database_s" 
                       <?= config('database_s') ? 'checked' : '' ?>>
                <label class="form-check-label" for="database_s">
                  <?= __l('use_database') ?>
                </label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="modul_s" 
                       id="modul_s" 
                       <?= config('modul_s') ? 'checked' : '' ?>>
                <label class="form-check-label" for="modul_s">
                  <?= __l('show_modules') ?>
                </label>
              </div>
            </div>
          </div>

          <!-- Dosya Yöneticisi -->
          <div class="row mb-3">
            <!-- Checkbox solda -->
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="file_manager_s" 
                       id="file_manager_s" 
                       <?= config('file_manager_s') ? 'checked' : '' ?>>
                <label class="form-check-label" for="file_manager_s">
                  <?= __l('file_manager') ?>
                </label>
              </div>
            </div>
            <!-- URL sağda -->
            <div class="col-md-8">
              <input type="text" 
                     name="file_manager_s_url" 
                     class="form-control" 
                     id="file_manager_s_url"
                     placeholder="<?= __l('file_manager') ?> URL (.hamu/file_manager.php)" 
                     value="<?= config('file_manager_s_url') ?>">
            </div>
          </div>

          <!-- PhpMyAdmin -->
          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="phpmyadmin_s" 
                       id="phpmyadmin_s" 
                       <?= config('phpmyadmin_s') ? 'checked' : '' ?>>
                <label class="form-check-label" for="phpmyadmin_s">
                  PhpMyAdmin
                </label>
              </div>
            </div>
            <div class="col-md-8">
              <input type="text" 
                     name="phpmyadmin_s_url" 
                     class="form-control" 
                     id="phpmyadmin_s_url"
                     placeholder="PhpMyAdmin URL" 
                     value="<?= config('phpmyadmin_s_url') ?>">
            </div>
          </div>

          <!-- Mail Sunucusu -->
          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="mail_server_s" 
                       id="mail_server_s" 
                       <?= config('mail_server_s') ? 'checked' : '' ?>>
                <label class="form-check-label" for="mail_server_s">
                  Mail <?= __l('host') ?>
                </label>
              </div>
            </div>
            <div class="col-md-8">
              <input type="text" 
                     name="mail_server_s_url" 
                     class="form-control" 
                     id="mail_server_s_url"
                     placeholder="Mail <?= __l('host') ?> URL" 
                     value="<?= config('mail_server_s_url') ?>">
            </div>
          </div>

          <!-- FTP Sunucusu -->
          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="ftp_server_s" 
                       id="ftp_server_s" 
                       <?= config('ftp_server_s') ? 'checked' : '' ?>>
                <label class="form-check-label" for="ftp_server_s">
                  FTP <?= __l('host') ?>
                </label>
              </div>
            </div>
            <div class="col-md-8">
              <input type="text" 
                     name="ftp_server_s_url" 
                     class="form-control" 
                     id="ftp_server_s_url"
                     placeholder="FTP <?= __l('host') ?> URL" 
                     value="<?= config('ftp_server_s_url') ?>">
            </div>
          </div>

          <!-- Kaydet Butonu -->
          <button type="submit" class="btn btn-primary w-100">
            <?= __l('save_') ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Footer include -->
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_footer.php';?>
