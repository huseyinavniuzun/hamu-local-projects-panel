<?php
/**
 * HAMU DevPanel - Footer
 * Location: /.hamu/footer.php
 * [TR] Sayfa altlığı, modal pencereler ve JS dosyalarını içerir.
 * [EN] Contains the page footer, modal windows, and JS files.
 */
?>
<!-- [TR] Footer: SQL Terminal / [EN] Footer: SQL Terminal -->
<?php if (!empty($config["database_s"]) && (!isset($include_db) || $include_db != 0)) : ?>
<div class="footer">
     <div class="input-group">
          <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="bottom"
               title="<?= __l('terminal_desc')?>"><i class="fas fa-terminal"></i></span>
          <input type="text" id="sqlQuery" class="form-select <?php if (empty($active_db)) echo ' terminalnodb '; ?>"
               autocomplete="off" autocorrect="off" spellcheck="false" autocapitalize="off" <?php if (empty($active_db)) {
		// Veritabanı kapalıysa placeholder ve disable ekliyoruz
		echo 'placeholder="'.__l('sql_terminal_placeholder_disabled').'" disabled="disabled"';
		} else {
		// Veritabanı açıksa normal placeholder
		echo 'placeholder="'.__l('sql_terminal_placeholder_enabled').'"';
		} ?>>
          <div class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top"
               title="<?= __l('sql_terminal_logs') ?>" onclick="window.location.href='?p=reader&log=db_terminal.log';"
               style="cursor: pointer;">
               <i class="fa fa-history"></i>
          </div>
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
<div class="modal fade <?= config('first_setup') ? 'show d-block' : '' ?>" id="settingsModal"
     data-bs-backdrop="<?= config('first_setup') ? 'static' : 'true' ?>" tabindex="-1"
     aria-labelledby="settingsModalLabel" aria-hidden="<?= config('first_setup') ? 'false' : 'true' ?>">
     <div class="modal-dialog modal-lg modal-dialog-centered" id="settingsModalDialog">
          <div class="modal-content">

               <!-- Modal Başlık -->
               <div class="modal-header">
                    <h5 class="modal-title" id="settingsModalLabel">
                         <?= config('first_setup') ? 'First Setup' : __l('settings') ?></h5>
                    <?php if (!config('first_setup')) : ?>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                         aria-label="<?= __l('close')?>"></button>
                    <?php endif; ?>
               </div>

               <!-- Modal İçerik -->
               <div class="modal-body">

                    <!-- Sekmeler -->
                    <div class="d-flex align-items-center border-bottom pb-2">
                         <ul class="nav nav-tabs border-0" role="tablist">
                              <li class="nav-item">
                                   <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGeneralSet"
                                        type="button" role="tab">
                                        <i class="fa fa-sliders me-1"></i> <?= __l('general')?>
                                   </button>
                              </li>
                              <li class="nav-item">
                                   <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInclude"
                                        type="button" role="tab">
                                        <i class="fa fa-layer-group me-1"></i> <?= __l('include_tab') ?>
                                   </button>
                              </li>
                         </ul>
                    </div>

                    <!-- TEK FORM -->
                    <form id="settingsForm" class="mt-2">
                         <div class="tab-content">

                              <!-- =================== GENEL SEKME =================== -->
                              <div class="tab-pane fade show active" id="tabGeneralSet" role="tabpanel">

<!-- Uygulama Parolası -->
<div class="row d-flex align-items-center mt-2">
  <div class="col-md-4 form-check form-switch d-flex align-items-center">
    <input class="form-check-input" type="checkbox" id="ask_pass_s"
           name="ask_pass_s" <?= config('ask_pass_s') ? 'checked' : '' ?>>
    <label class="form-check-label" for="ask_pass_s"><?= __l('ask_pass') ?></label>
  </div>
  <div class="col-md-8 border-start border-secondary border-end rounded-1 gap-2 <?= config('ask_pass_s') ? '' : 'hideS' ?> d-flex align-items-center"
       id="appPassSet">
    <?php $hasPass = !empty(config('app_pass_s')); ?>
    <input type="password" name="app_pass_s" class="form-control"
           id="app_pass_s" value="" placeholder="<?= $hasPass ? '••••••' : '' ?>">
    <a href="/?p=reader&log=app_sessions.log" class="input-group-text w-10"
       data-bs-toggle="tooltip" data-bs-placement="bottom"
       alt="<?= __l('sessions_logs') ?>"
       title="<?= __l('sessions_logs') ?>"><i class="fas fa-history"></i></a>
  </div>
  <hr class="mt-3">
</div>

<!-- 2FA: sadece parola kullan açıkken görünsün; 2FA kapalıysa içerik ve hr görünmesin -->
<?php
$m = config('2fa_method_s') ?: 'email';
$emailVerified = (bool) config('email_verified_at');
$totpLinked    = (bool) config('totp_secret_b32_enc');
$backupAck     = (bool) config('totp_backup_ack');
?>
<section class="db-toggle"
       data-enabled="ask_pass_s"
       data-radio="input[name='2fa_method_s']"
       data-default="<?= $m ?>"
       data-hide-class="hideS">

  <div class="row d-flex align-items-start">

    <!-- Sol sütun: 2FA ana anahtarı -->
    <div class="col-md-4 form-check form-switch d-flex align-items-center"
         data-show-when="*">
      <input class="form-check-input" type="checkbox" id="2fa_s"
             name="2fa_s" <?= (config('ask_pass_s') && config('2fa_s')) ? 'checked' : '' ?>>
      <label class="form-check-label ms-2" for="2fa_s">
        <?= __l('ask_2fa') ?>
      </label>
    </div>

    <!-- Sağ sütun: 2FA açık ise -->
    <div class="col-md-8 border-start border-secondary border-end rounded-1 <?= (config('ask_pass_s') && config('2fa_s')) ? '' : 'hideS' ?>"
         id="2faSettings"
         data-show-when="*"
         data-switch="2fa_s">

      <!-- Yöntem seçimi -->
      <div class="mb-2">
        <label class="form-label mb-1"><?= __l('2fa_method') ?>:</label>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach (['email' => 'E-posta', 'app' => 'Authenticator'] as $val => $label): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio"
                     name="2fa_method_s" id="method_<?= $val ?>"
                     value="<?= $val ?>" <?= $m === $val ? 'checked' : '' ?>>
              <label class="form-check-label" for="method_<?= $val ?>"><?= $label ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- --------- E-POSTA --------- -->
      <div id="emailFields"
           class="<?= ($m==='email' && config('ask_pass_s') && config('2fa_s')) ? '' : 'hideS' ?>"
           data-show-when="email"
           data-switch="2fa_s"
           data-email-verified="<?= $emailVerified ? '1':'0' ?>">

        <div class="d-flex align-items-center justify-content-between gap-2">
          <label class="form-label mb-1 d-flex align-items-center gap-2 mb-0">
            <?= __l('email_2fa') ?>
            <span id="emailBadge"
                  class="badge <?= $emailVerified ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
              <?= $emailVerified ? __l('verified') : __l('not_verified') ?>
            </span>
          </label>
        </div>

        <div class="input-group mt-2" id="emailSendRow">
          <input type="email" class="form-control form-control-sm" name="email_address_s" id="email_address_s"
                 placeholder="<?= __l('email_address') ?>"
                 value="<?= htmlspecialchars((string)config('email_address_s')) ?>">
          <button class="btn btn-sm btn-outline-success <?= $emailVerified ? 'hideS':'' ?>" type="button" id="emailSendBtn">
            <?= __l('send_code') ?>
          </button>
          <button class="btn btn-sm btn-outline-success d-none" type="button" id="emailResendBtn">
            <?= __l('resend_code') ?>
          </button>
          <button class="btn btn-sm btn-outline-dark d-none" type="button" id="emailCancelBtn">
            <?= __l('cancel') ?>
          </button>
        </div>

        <div class="input-group mt-2 <?= $emailVerified ? 'hideS':'' ?>" id="emailVerifyRow">
          <input type="text" class="form-control" id="emailCode" placeholder="<?= __l('enter_code') ?>" autocomplete="one-time-code" inputmode="numeric">
          <button class="btn btn-sm btn-success" type="button" id="emailVerifyBtn"><?= __l('verify') ?></button>
        </div>

        <div class="mt-2 <?= $emailVerified ? '' : 'hideS' ?>">
          <button class="btn btn-sm btn-outline-danger" type="button" id="emailDisableBtn">
            <?= __l('disable_2fa_email') ?>
          </button>
        </div>

        <small id="emailStatus" class="d-block mt-2 text-muted"></small>
        <div id="twofaExpiryWrap" class="small text-muted mt-1"></div>
      </div>

      <!-- --------- AUTHENTICATOR --------- -->
      <div id="appFields"
           class="<?= ($m==='app' && config('ask_pass_s') && config('2fa_s')) ? '' : 'hideS' ?>"
           data-show-when="app"
           data-switch="2fa_s"
           data-totp-linked="<?= $totpLinked ? '1':'0' ?>">

        <div class="d-flex align-items-center gap-2 mb-1">
          <label class="form-label mb-1 d-flex align-items-center gap-2 mb-0">
            <?= __l('use_auth_app') ?>
            <span id="appBadge"
                  class="badge <?= $totpLinked ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
              <?= $totpLinked ? __l('linked') : __l('setup') ?>
            </span>
          </label>
        </div>

        <div id="totpLinkedView" class="<?= $totpLinked ? '' : 'd-none' ?>">
          <div class="d-flex flex-wrap gap-2 mt-2">
             <button class="btn btn-sm btn-outline-danger" type="button" id="totpDisableBtn">
              <?= __l('disable_2fa_app') ?>
            </button>
          </div>
        </div>

        <div id="totpSetupView" class="<?= $totpLinked ? 'd-none' : '' ?>">
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-success" type="button" id="totpStartBtn">
              <?= __l('start_setup') ?>
            </button>
          </div>

          <div id="qrBox" class="border rounded p-3 mt-3 d-none">
            <div class="fw-semibold mb-2"><?= __l('scan_qr_in_auth_app') ?></div>
            <div id="qrCanvasHolder"></div>
            <small class="text-muted d-block mt-2">
              <?= __l('cant_scan_qr_show_secret') ?> <code id="totpSecretText" class="user-select-all"></code>
            </small>
          </div>

          <div class="input-group mt-3 d-none" id="totpVerifyRow">
            <input type="text" class="form-control" id="totpCode" placeholder="<?= __l('enter_auth_code') ?>" autocomplete="one-time-code" inputmode="numeric">
            <button class="btn btn-sm btn-success" type="button" id="totpVerifyBtn">
              <?= __l('verify_and_save') ?>
            </button>
          </div>

          <small id="totpStatus" class="d-block mt-2 text-muted"></small>
          <input type="hidden" id="totpSecretB32Hidden" value="">
        </div>

      </div>
      <!-- /Authenticator -->

      <!-- ORTAK YEDEK KODLAR -->
      <div id="backupBox" class="mt-3 d-none" data-backup-ack="<?= $backupAck ? '1':'0' ?>">
        <div class="fw-semibold mb-2"><?= __l('backup_codes') ?></div>
        <ul id="backupList" class="list-group small"></ul>
        <div class="d-flex gap-2 mt-2">
          <button type="button" class="btn btn-sm btn-primary" id="backupSaveBtn">
            <?= __l('i_copied_them_hide') ?>
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="backupGenBtn">
            <?= __l('regenerate_codes') ?>
          </button>
        </div>
        <small id="backupWarn" class="text-warning d-none mt-2"></small>
        <small class="text-muted d-block mt-1"><?= __l('backup_codes_hint') ?></small>
      </div>

    </div>
    <!-- /sağ sütun -->
  </div>

  <hr class="mt-3 <?= (config('ask_pass_s') && config('2fa_s')) ? '' : 'hideS' ?>" data-show-when="*">
</section>




<!-- Gizli Kurulum Anahtarı (enc_install_secret_b64) -->
<div class="row d-flex align-items-center">
  <div class="col-md-4 d-flex align-items-center ">
    <label class="form-label mb-0" for="enc_install_secret_b64"><?= __l('install_secret') ?></label>
  </div>
  <div class="col-md-8 d-flex align-items-center border-start border-secondary border-end rounded-1 gap-2">
    <input type="password" class="form-control" id="enc_install_secret_b64"
           name="enc_install_secret_b64"
           value="<?= htmlspecialchars(config('enc_install_secret_b64') ?? '') ?>"
           placeholder="base64…">
    <button class="btn btn-sm btn-outline-secondary w-10" type="button" id="toggleSecret"><i class="fa fa-eye"></i></button>
  </div>
  <hr class="mt-3">
</div>

<!-- Veritabanı -->
<?php $drv = config('db_driver_s') ?: 'auto'; ?>
<section class="db-toggle"
         data-enabled="database_s"
         data-radio="input[name='db_driver_s']"
         data-default="<?= $drv ?>"
         data-hide-class="hideS">
  <div class="row d-flex align-items-start">
    <div class="col-md-4 form-check form-switch d-flex align-items-center">
      <input class="form-check-input" type="checkbox" id="database_s"
             name="database_s" <?= config('database_s') ? 'checked' : '' ?>>
      <label class="form-check-label" for="database_s"><?= __l('use_database') ?></label>
    </div>

    <div class="col-md-8 border-start border-secondary border-end rounded-1 <?= config('database_s') ? '' : 'hideS' ?>"
         id="dbSettings" data-show-when="*">

      <!-- Sürücü Seçimi (hedef olarak işaretli; enabled=true iken her zaman görünür) -->
      <div class="mb-2" data-show-when="*">
        <label class="form-label mb-1"><?= __l('db_driver') ?>:</label>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach (['auto'=>'Otomatik','mysql'=>'MySQL','pgsql'=>'PostgreSQL','sqlsrv'=>'SQL Server','oci'=>'Oracle','sqlite'=>'SQLite'] as $val=>$label): ?>
          <div class="form-check">
            <input class="form-check-input" type="radio"
                   name="db_driver_s" id="drv_<?= $val ?>"
                   value="<?= $val ?>" <?= $drv===$val?'checked':'' ?>>
            <label class="form-check-label" for="drv_<?= $val ?>"><?= $label ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- RDBMS alanları -->
      <div id="rdbmsFields"
           class="<?= in_array($drv,['mysql','pgsql','sqlsrv','oci','auto']) ? '' : 'hideS' ?>"
           data-show-when="auto,mysql,pgsql,sqlsrv,oci">
        <input type="text" class="form-control mt-2" id="db_server_s"
               name="db_server_s" placeholder="<?= __l('database_server') ?>"
               value="<?= config('db_server_s') ?>">
        <input type="text" class="form-control mt-2" id="db_user_s"
               name="db_user_s" placeholder="<?= __l('username') ?>"
               value="<?= decryptData($config['db_user_s'] ?? '') ?? '' ?>">
        <input type="password" class="form-control mt-2" id="db_pass_s"
               name="db_pass_s" placeholder="<?= __l('password') ?>"
               value="<?= decryptData($config['db_pass_s'] ?? '') ?? '' ?>">
     <small class="text-muted d-block mt-1">
       <?= __l('use_for','MySQL/PG/SQL Server/Oracle')?>
     </small>
      </div>

      <!-- SQLite ayarları -->
      <div id="sqliteFields"
           class="<?= $drv==='sqlite' ? '' : 'hideS' ?>"
           data-show-when="sqlite">
        <div class="input-group mt-2">
          <span class="input-group-text">[<?= __l('root') ?>]/</span>
          <input type="text" class="form-control" id="sqlite_folder_s"
                 name="sqlite_folder_s" placeholder=".hamu/sqlite"
                 value="<?= config('sqlite_folder_s') ?>">
        </div>
        <small class="text-muted d-block mt-1">
          <?= __l('folder_for', 'SQLite') ?>
        </small>
      </div>
    </div>
    <hr class="mt-3">
  </div>
</section>


                                   <!-- Klasör Gizle -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="ignore_folders_s"
                                                  name="ignore_folders_s"
                                                  <?= config('ignore_folders_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label"
                                                  for="ignore_folders_s"><?= __l('ignore_folders') ?></label>
                                        </div>
                                        <div class="col-md-8 border-start border-secondary border-end rounded-1 <?= config('ignore_folders_s') ? '' : 'hideS' ?> d-flex align-items-center"
                                             id="ignorefoldersHd">
                                             <input type="text" class="form-control" id="ignore_folders_s_text"
                                                  name="ignore_folders_s_text" placeholder=".hamu,docs,.git"
                                                  value="<?= config('ignore_folders_s_text') ?>">
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- DB Yöneticisi -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="phpmyadmin_s"
                                                  name="phpmyadmin_s" <?= config('phpmyadmin_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label"
                                                  for="phpmyadmin_s"><?= __l('db_manager')?></label>
                                        </div>
                                        <div class="col-md-8 border-start border-secondary border-end rounded-1 <?= config('phpmyadmin_s') ? '' : 'hideS' ?> d-flex align-items-center"
                                             id="phpmyadminHd">
                                             <input type="text" class="form-control" id="phpmyadmin_s_url"
                                                  name="phpmyadmin_s_url" placeholder="/phpmyadmin"
                                                  value="<?= config('phpmyadmin_s_url') ?>">
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Mail Sunucusu -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="mail_server_s"
                                                  name="mail_server_s" <?= config('mail_server_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="mail_server_s">Mail
                                                  <?= __l('host') ?></label>
                                        </div>
                                        <div class="col-md-8 border-start border-secondary border-end rounded-1 <?= config('mail_server_s') ? '' : 'hideS' ?> d-flex align-items-center"
                                             id="mailServHd">
                                             <input type="text" class="form-control" id="mail_server_s_url"
                                                  name="mail_server_s_url" placeholder="https://localhost:8025"
                                                  value="<?= config('mail_server_s_url') ?>">
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- FTP Sunucusu -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="ftp_server_s"
                                                  name="ftp_server_s" <?= config('ftp_server_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="ftp_server_s">FTP
                                                  <?= __l('host') ?></label>
                                        </div>
                                        <div class="col-md-8 border-start border-secondary border-end rounded-1 <?= config('ftp_server_s') ? '' : 'hideS' ?> d-flex align-items-center"
                                             id="ftpServHd">
                                             <input type="text" class="form-control" id="ftp_server_s_url"
                                                  name="ftp_server_s_url" placeholder="ftp.localhost"
                                                  value="<?= config('ftp_server_s_url') ?>">
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Modüller -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-12 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="modul_s" name="modul_s"
                                                  <?= config('modul_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label"
                                                  for="modul_s"><?= __l('show_modules') ?></label>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                              </div><!-- /GENEL -->

                              <!-- =================== INCLUDE SEKME =================== -->
                              <div class="tab-pane fade" id="tabInclude" role="tabpanel">
                                   <!-- (mevcut içerik aynı kalır) -->
                                   <!-- Bootstrap -->
                                   <div class="row d-flex align-items-center mt-3">
                                        <div class="col-md-6 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="bootstrap_s"
                                                  name="bootstrap_s" <?= config('bootstrap_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="bootstrap_s">Bootstrap</label>
                                        </div>
                                        <div class="col-md-6">
                                             <select class="form-select" id="bootstrap_version_s"
                                                  name="bootstrap_version_s">
                                                  <?php $bs= config('bootstrap_version_s'); foreach (['5.3.3','5.3.2','5.2.3','5.1.3'] as $v): ?>
                                                  <option value="<?= $v ?>" <?= $bs==$v?'selected':'' ?>>v<?= $v ?>
                                                  </option>
                                                  <?php endforeach; ?>
                                             </select>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Bootstrap Bundle -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-6 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="bootstrap_bundle_s"
                                                  name="bootstrap_bundle_s"
                                                  <?= config('bootstrap_bundle_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="bootstrap_bundle_s">Bootstrap
                                                  Bundle</label>
                                        </div>
                                        <div class="col-md-6">
                                             <select class="form-select" id="bootstrap_bundle_version_s"
                                                  name="bootstrap_bundle_version_s">
                                                  <?php $bsb= config('bootstrap_bundle_version_s'); foreach (['5.3.3','5.3.2','5.2.3','5.1.3'] as $v): ?>
                                                  <option value="<?= $v ?>" <?= $bsb==$v?'selected':'' ?>>v<?= $v ?>
                                                  </option>
                                                  <?php endforeach; ?>
                                             </select>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Google Fonts -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="google_font_s"
                                                  name="google_font_s" <?= config('google_font_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="google_font_s">Google Fonts</label>
                                        </div>
                                        <div class="col-md-8">
                                             <input type="text" class="form-control" id="google_font_url_s"
                                                  name="google_font_url_s" placeholder="family=Poppins:wght@300.."
                                                  value="<?= htmlspecialchars(config('google_font_url_s') ?? '') ?>">
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Font Awesome -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-6 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="fontawesome_s"
                                                  name="fontawesome_s" <?= config('fontawesome_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="fontawesome_s">Font Awesome</label>
                                        </div>
                                        <div class="col-md-6">
                                             <select class="form-select" id="fontawesome_version_s"
                                                  name="fontawesome_version_s">
                                                  <?php $fa= config('fontawesome_version_s'); foreach (['6.5.2','6.4.2','6.2.1','5.15.4'] as $v): ?>
                                                  <option value="<?= $v ?>" <?= $fa==$v?'selected':'' ?>>v<?= $v ?>
                                                  </option>
                                                  <?php endforeach; ?>
                                             </select>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Custom CSS -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="custom_css_s"
                                                  name="custom_css_s" <?= config('custom_css_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="custom_css_s"><?= __l('private') ?>
                                                  css</label>
                                        </div>
                                        <div class="col-md-8">
                                             <input type="text" class="form-control" id="custom_css_list_s"
                                                  name="custom_css_list_s" placeholder="reset,theme,utilities"
                                                  value="<?= htmlspecialchars(config('custom_css_list_s') ?? '') ?>">
                                             <small
                                                  class="text-muted"><?= __l('settings_desc','CSS','<code>/.hamu/assets/css</code>') ?></small>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- jQuery -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-6 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="jquery_s"
                                                  name="jquery_s" <?= config('jquery_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="jquery_s">jQuery</label>
                                        </div>
                                        <div class="col-md-6">
                                             <select class="form-select" id="jquery_version_s" name="jquery_version_s">
                                                  <?php $jq= config('jquery_version_s'); foreach (['3.7.1','3.6.4','3.5.1'] as $v): ?>
                                                  <option value="<?= $v ?>" <?= $jq==$v?'selected':'' ?>>v<?= $v ?>
                                                  </option>
                                                  <?php endforeach; ?>
                                             </select>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- jQuery UI -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-6 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="jqueryui_s"
                                                  name="jqueryui_s" <?= config('jqueryui_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="jqueryui_s">jQuery UI</label>
                                        </div>
                                        <div class="col-md-6">
                                             <select class="form-select" id="jqueryui_version_s"
                                                  name="jqueryui_version_s">
                                                  <?php $jqu= config('jqueryui_version_s'); foreach (['1.13.3','1.13.2','1.12.1'] as $v): ?>
                                                  <option value="<?= $v ?>" <?= $jqu==$v?'selected':'' ?>>v<?= $v ?>
                                                  </option>
                                                  <?php endforeach; ?>
                                             </select>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                                   <!-- Custom JS -->
                                   <div class="row d-flex align-items-center">
                                        <div class="col-md-4 form-check form-switch d-flex align-items-center">
                                             <input class="form-check-input" type="checkbox" id="custom_js_s"
                                                  name="custom_js_s" <?= config('custom_js_s') ? 'checked' : '' ?>>
                                             <label class="form-check-label" for="custom_js_s"><?= __l('private') ?>
                                                  Js</label>
                                        </div>
                                        <div class="col-md-8">
                                             <input type="text" class="form-control" id="custom_js_list_s"
                                                  name="custom_js_list_s" placeholder="core,widgets,charts"
                                                  value="<?= htmlspecialchars(config('custom_js_list_s') ?? '') ?>">
                                             <small
                                                  class="text-muted"><?= __l('settings_desc','JS','<code>/.hamu/assets/js</code>') ?></small>
                                        </div>
                                        <hr class="mt-3">
                                   </div>

                              </div><!-- /include -->

                         </div><!-- /tab-content -->

                         <!-- KAYDET -->
                         <div class="modal-footer">
                              <button type="button" class="btn btn-outline-secondary mb-2"
                                   data-bs-dismiss="modal"><?= __l('close')?></button>
                              <button type="submit" class="btn btn-success mb-2"><?= __l('save') ?></button>
                    </form>
               </div>
          </div><!-- /modal-body -->

     </div>
</div>
</div>

<!-- Proje Ayarları Modal -->
<div class="modal fade" id="projectSettingsModal" tabindex="-1" aria-hidden="true">
     <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">

               <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                         <i class="fa fa-cog"></i>
                         <span><?= __l('project_settings')?></span>
                         <small class="text-muted" id="psmFolderName"></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
               </div>

               <form id="projectSettingsForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="project_meta_write">
                    <input type="hidden" name="folder" id="psmFolder">
                    <input type="hidden" name="csrf_token"
                         value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="modal-body">

                         <!-- Sekmeler + sağda Favori yıldızı -->
                         <div class="d-flex align-items-center border-bottom pb-2">
                              <ul class="nav nav-tabs border-0" role="tablist">
                                   <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab"
                                             data-bs-target="#tabGeneral" type="button" role="tab">
                                             <i class="fa fa-sliders me-1"></i> <?= __l('general')?>
                                        </button>
                                   </li>
                                   <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFTP"
                                             type="button" role="tab">
                                             <i class="fa fa-server me-1"></i> FTP
                                        </button>
                                   </li>
                              </ul>
                              <div class="ms-auto">
                                   <input type="checkbox" class="btn-check" id="psmFav" name="favorite" value="1"
                                        autocomplete="off">
                                   <label class="btn btn-outline-secondary" id="psmFavBtn" for="psmFav" title="Favori">
                                        <i class="fa fa-star"></i>
                                   </label>
                              </div>
                         </div>

                         <div class="tab-content pt-3">

                              <!-- GENEL (sol form / sağ kapak, kompakt) -->
                              <div class="tab-pane fade show active" id="tabGeneral" role="tabpanel">
                                   <div class="row g-3">
                                        <!-- SOL: Form -->
                                        <div class="col-lg-7">
                                             <!-- Klasör adı -->
                                             <div class="mb-2">
                                                  <label
                                                       class="form-label fw-semibold small"><?= __l('folder_name')?></label>
                                                  <div class="input-group input-group-sm">
                                                       <span class="input-group-text"><i
                                                                 class="fa fa-folder"></i></span>
                                                       <input type="text" class="form-control form-control-sm"
                                                            id="psmNewName" name="new_name"
                                                            placeholder="yeni-klasor-adi">
                                                  </div>
                                                  <div class="form-text small"><?= __l('rename_desc')?></div>
                                             </div>

                                             <!-- Etiketler -->
                                             <div class="mb-2">
                                                  <label class="form-label small"><?= __l('tags_desc')?></label>
                                                  <input type="text" class="form-control form-control-sm" id="psmTags"
                                                       name="tags" placeholder="php, landing, prod">
                                             </div>

                                             <!-- Badge -->
                                             <div class="row g-2">
                                                  <div class="col-md-6">
                                                       <label class="form-label small"><?= __l('badge_text')?></label>
                                                       <input type="text" class="form-control form-control-sm"
                                                            id="psmBadgeText" name="badge_text"
                                                            placeholder="prod / stage / dev ...">
                                                  </div>
                                                  <div class="col-md-6">
                                                       <label class="form-label small"><?= __l('badge_color')?></label>
                                                       <select class="form-select form-select-sm" id="psmBadgeColor"
                                                            name="badge_color">
                                                            <option value=""></option>
                                                            <option value="light" class="bg-light">#f8f9fa</option>
                                                            <option value="primary" class="bg-primary">#007bff</option>
                                                            <option value="secondary" class="bg-secondary">#6c757d
                                                            </option>
                                                            <option value="success" class="bg-success">#28a745</option>
                                                            <option value="danger" class="bg-danger">#dc3545</option>
                                                            <option value="warning" class="bg-warning">#ffc107</option>
                                                            <option value="info" class="bg-info">#17a2b8</option>
                                                            <option value="dark" class="bg-dark text-white">#343a40
                                                            </option>
                                                       </select>
                                                  </div>
                                             </div>
                                        </div>
                                        <!-- SAĞ: Kapak (etiketsiz, resme tıklayınca dosya seç) -->
                                        <div class="col-lg-5">
                                             <input type="file" id="psmCover" name="cover"
                                                  accept="image/png,image/jpeg,image/webp" hidden>
                                             <div class="card border-0">
                                                  <div class="card-body p-2">
                                                       <div class="ratio ratio-1x1 rounded bg-light border"
                                                            style="cursor:pointer; max-width:150px; margin:auto;"
                                                            onclick="document.getElementById('psmCover').click()">
                                                            <img id="psmCoverPreview" alt="" class="w-100 h-100"
                                                                 style="object-fit:cover;" data-bs-toggle="tooltip"
                                                                 data-bs-placement="bottom"
                                                                 title="Değiştirmek için tıklayın">
                                                       </div>
                                                       <div class="text-muted small text-center mt-1">
                                                            <?= __l('folder_img_desc')?></div>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </div>

                              <!-- FTP -->
                              <div class="tab-pane fade" id="tabFTP" role="tabpanel">
                                   <div class="row g-3">
                                        <div class="col-md-6">
                                             <label class="form-label"><?= __l('host')?></label>
                                             <input type="text" class="form-control" id="psmFtpHost" name="ftp_host"
                                                  autocomplete="on">
                                        </div>
                                        <div class="col-md-3">
                                             <label class="form-label">Port</label>
                                             <input type="number" class="form-control" id="psmFtpPort" name="ftp_port"
                                                  min="1" max="65535" placeholder="21">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                             <div class="form-check">
                                                  <input class="form-check-input" type="checkbox" id="psmFtpSSL"
                                                       name="ftp_ssl" value="1">
                                                  <label class="form-check-label" for="psmFtpSSL">SSL</label>
                                             </div>
                                        </div>

                                        <div class="col-md-6">
                                             <label class="form-label"><?= __l('username')?></label>
                                             <input type="text" class="form-control" id="psmFtpUser" name="ftp_user"
                                                  autocomplete="section-ftp username" spellcheck="false">
                                        </div>
                                        <div class="col-md-6">
                                             <label class="form-label"><?= __l('password')?></label>
                                             <div class="input-group">
                                                  <input type="password" name="ftp_pass" class="form-control" value=""
                                                       placeholder="<?= isset($hasPass) && $hasPass ? '••••••' : '' ?>"
                                                       autocomplete="new-password">
                                             </div>
                                             <div class="form-text small">
                                                  <?= __l('pass_note') ?>
                                             </div>
                                        </div>

                                        <div class="col-md-6">
                                             <label class="form-label"><?= __l('ftp_folder')?></label>
                                             <input type="text" class="form-control" id="psmFtpFolder" name="ftp_folder"
                                                  placeholder="/public_html/">
                                        </div>
                                        <div class="col-md-6">
                                             <label class="form-label">Site URL</label>
                                             <input type="url" class="form-control" id="psmHostUrl" name="host_url"
                                                  placeholder="http://example.com" autocomplete="url">
                                        </div>

                                        <div class="col-12">
                                             <label class="form-label"><?= __l('description')?></label>
                                             <input type="text" class="form-control" id="psmDesc" name="description">
                                        </div>
                                        <div class="col-12">
                                             <label class="form-label"><?= __l('down_folder')?></label>
                                             <input type="text" class="form-control" id="psmDown" name="download_folder"
                                                  placeholder="/download" autocomplete="off">
                                        </div>
                                   </div>
                              </div>

                         </div><!-- /tab-content -->
                    </div><!-- /modal-body -->

                    <div class="modal-footer">
                         <button type="button" class="btn btn-outline-secondary"
                              data-bs-dismiss="modal"><?= __l('close')?></button>
                         <button type="submit" class="btn btn-primary">
                              <i class="fa fa-save me-1"></i> <?= __l('save')?>
                         </button>
                    </div>
               </form>

          </div>
     </div>
</div>
<?php if (config('jquery_s')): ?>
<script src="https://code.jquery.com/jquery-<?= htmlspecialchars(config('jquery_version_s') ?: '3.6.0') ?>.min.js">
</script>
<?php endif; ?>

<?php if (config('jqueryui_s')): ?>
<script
     src="https://code.jquery.com/ui/<?= htmlspecialchars(config('jqueryui_version_s') ?: '1.13.2') ?>/jquery-ui.min.js">
</script>
<?php endif; ?>

<?php if (config('bootstrap_bundle_s')): ?>
<!-- Bootstrap Bundle (JS) -->
<script
     src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/<?= htmlspecialchars(config('bootstrap_bundle_version_s') ?: '5.3.2') ?>/js/bootstrap.bundle.min.js">
</script>
<?php endif; ?>

<!-- Javascrip dosyaları -->
<!-- Sabit: Javascript -->
<script src="/?a=assets/js/hamu.main.min.js&v=1.0.0"></script>
<script src="/?a=assets/js/hamu.projects.min.js&v=1.0.0"></script>
<script src="/?a=assets/js/hamu.2fa.min.js&v=1.0.0"></script>
<script src="/?a=assets/js/qrcode.min.js"></script>
<!--Custom Javascript dosyaları - Değişken: include_db=1 db_actions.js + ayarlar js listesi  -->
<?= $custom_js ?? '' ?>

</body>

</html>