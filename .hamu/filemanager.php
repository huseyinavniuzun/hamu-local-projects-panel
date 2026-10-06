<?php
/**
 * HAMU DevPanel - File Manager
 * Location: /.hamu/filemanager.php
 * [TR] Tiny File Manager için bir sarmalayıcı (wrapper) görevi görür. Projeler arasında geçiş yapmak için bir
 *      dropdown menü ve dosya yöneticisinin davranışını (gizli dosyalar, izinler) kontrol etmek için anahtarlar sunar.
 * [EN] Acts as a wrapper for Tiny File Manager. It provides a dropdown menu to switch between projects and
 *      toggles to control the file manager's behavior (hidden files, permissions).
 *
 * --- USAGE ---
 * Endpoint: /?p=file-manager
 * The project selection dropdown changes the `src` of the iframe to point to the selected project's root directory.
 * The toggle switches control the view settings within the iframe.
 */

/* ------------------------------ Tanımlar ------------------------------ */
// [TR] Sayfa yapılandırma değişkenleri
// [EN] Page configuration variables
$page_title = "file_manager"; // [TR] Sayfa başlığı için dil anahtarı. [EN] Language key for the page title.
$body_class = "";             // [TR] <body> etiketine eklenecek özel CSS sınıfı. [EN] Custom CSS class for the <body> tag.
$include_db = 0;              // [TR] Veritabanı bağlantısı gerekli mi? (1=Evet, 0=Hayır). [EN] Is a database connection required? (1=Yes, 0=No).
$side_bar   = 0;              // [TR] Sol menü (sidebar) gösterilsin mi? (1=Evet, 0=Hayır). [EN] Should the left sidebar be displayed? (1=Yes, 0=No).
$menu_type  = 1;              // [TR] Menü davranışını kontrol eder. 0: Mobil menü butonu gösterilir. 1: Her zaman görünür menü. [EN] Controls menu behavior. 0: Show mobile menu button. 1: Always visible menu.

require_once __DIR__ .'/header.php';
?>

<?= $side ?>

<div class="container-fm">
     <div class="content-middle file-manager">
          <div class="row headerline">
               <!-- Proje seçimi -->
               <div class="col-12 col-md-9 text-start">
                    <div class="dropdown">
                         <button extra-target="iframe" class="btn dropdown-toggle" type="button"
                              id="folderDropdownButton" data-bs-toggle="dropdown" aria-expanded="false">
                              <span class="dropdown-label"
                                   style="padding-top: 0px !important;"><?= __l('all_projects') ?></span>
                         </button>
                         <ul class="dropdown-menu" extra-target="iframe" id="folderDropdown"
                              aria-labelledby="folderDropdownButton">
                              <li>
                                   <a class="dropdown-item active" extra-target="iframe" href="#" data-folder="">
                                        <?= __l('all_projects') ?>
                                   </a>
                              </li>
                              <?php foreach ($folders as $dir):
              $folderName = basename($dir);
              $encodedFolderName = urlencode($folderName);
            ?>
                              <li>
                                   <a class="dropdown-item" extra-target="iframe" href="#"
                                        data-folder="<?= $encodedFolderName ?>">
                                        <?= htmlspecialchars($folderName) ?>
                                   </a>
                              </li>
                              <?php endforeach; ?>
                         </ul>
                    </div>
               </div>

               <!-- Anahtar anahtarları -->
               <div class="col-12 col-md-3">
                    <div class="fm-switchbar align-items-center justify-content-center">
                         <div class="form-check form-switch m-0">
                              <input class="form-check-input" extra-target="iframe" type="checkbox" id="hideColsSwitch"
                                   value="yes" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                   aria-label="<?= __l('permission')?>" title="<?= __l('show_perms') ?>">
                              <label class="form-check-label small ms-1"
                                   for="hideColsSwitch"><?= __l('permission') ?></label>
                         </div>

                         <div class="form-check form-switch m-0">
                              <input class="form-check-input" extra-target="iframe" type="checkbox"
                                   id="showHiddenSwitch" value="yes" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                   aria-label="<?= __l('show_hidden')?>" title="<?= __l('show_hidden') ?>">
                              <label class="form-check-label small ms-1"
                                   for="showHiddenSwitch"><?= __l('hide') ?></label>
                         </div>

                         <div class="form-check form-switch m-0">
                              <input class="form-check-input" extra-target="iframe" type="checkbox"
                                   id="errorReportingSwitch" value="yes" data-bs-toggle="tooltip"
                                   data-bs-placement="bottom" aria-label="<?= __l('error_reporting')?>"
                                   title="<?= __l('error_reporting')?>">
                              <label class="form-check-label small ms-1"
                                   for="errorReportingSwitch"><?= __l('error_reporting') ?></label>
                         </div>
                    </div>
               </div>
          </div>

          <!-- Iframe -->
          <iframe id="FM_iframe" class="FM-iframe" src="/?p=fmi&iframe=1&f=" frameborder="0"
               style="max-height: 55rem !important; "></iframe>
     </div>
</div>

<?= get_footer(); ?>