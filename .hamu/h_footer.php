 <!-- [TR] Footer: SQL Terminal / [EN] Footer: SQL Terminal -->
<?php if (!empty($config["database_s"]) && (!isset($include_db) || $include_db != 0)) : ?>
<div class="footer">
    <div class="input-group">
        <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<b>Kullanım:</b> Enter tuşu her durumda terminale giriş sağlar.<br><b>Geçmiş:</b> Shift+Yukarı/Aşağı Ok tuşları yazılmış eski sorguları gösterir."><i class="fas fa-terminal"></i></span>
        <input 
            type="text" 
            id="sqlQuery" 
            class="form-select <?php if (empty($active_db)) echo ' terminalnodb '; ?>"  
            autocomplete="off" 
            autocorrect="off" 
            spellcheck="false" 
            autocapitalize="off" 
            <?php if (empty($active_db)) {
                // Veritabanı kapalıysa placeholder ve disable ekliyoruz
                echo 'placeholder="'.__l('sql_terminal_placeholder_disabled').'" disabled="disabled"';
            } else {
                // Veritabanı açıksa normal placeholder
                echo 'placeholder="'.__l('sql_terminal_placeholder_enabled').'"';
            } ?>
        >
    </div>
	<?php endif; ?>
</div>

<!-- [TR] Modal: SQL Terminal / [EN] Modal: SQL Terminal -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
      <div class="modal-content">
          <div class="modal-header">
              <h6 class="modal-title">SQL</h6>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
              <div id="queryResult">...</div>
          </div>
      </div>
  </div>
</div>
<!-- Footer include -->
<!-- [TR] Ayarlar modal / [EN] Settings modal -->
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

			<!-- Giriş inputları için konteyner -->
<div id="dbSettings" style="display: <?= config('database_s') ? 'block' : 'none' ?>; margin-top: 10px;">
  <div class="row mb-3">
    <div class="col-md-4">
      <label for="db_server_s" class="form-label"><?= __l('database_server') ?></label>
    </div>
    <div class="col-md-8">
      <input type="text" class="form-control" name="db_server_s" id="db_server_s" value="<?= config('db_server_s') ?>">
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-4">
      <label for="db_user_s" class="form-label"><?= __l('database_user') ?></label>
    </div>
    <div class="col-md-8">
      <input type="text" class="form-control" name="db_user_s" id="db_user_s" value="<?= config('db_user_s') ?>">
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-4">
      <label for="db_pass_s" class="form-label"><?= __l('database_pass') ?></label>
    </div>
    <div class="col-md-8">
      <input type="password" class="form-control" name="db_pass_s" id="db_pass_s" value="<?= config('db_pass_s') ?>">
    </div>
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
                     placeholder="<?= __l('file_manager') ?> URL (.hamu/modules/file_manager.php)" 
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
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($config["database_s"])): ?>
<script src="/.hamu/js/dbactions.js"></script>
<?php endif; ?>
<script src="/.hamu/js/general.js"></script>
<script src="/.hamu/js/extra.js"></script>
</body>
</html>