<?php
/**
 * LANG_SWITCHER.PHP
 *
 * [TR] Başlık/menüde dil seçimi. <select> yerine Bootstrap dropdown.
 * [EN] Language selection in header/menu using Bootstrap dropdown.
 */

global $languages, $lang;
?>
<div class="btn-group btn-group-sm btn-tt" role="group" aria-label="language button">
  <div class="btn-group btn-group-sm" role="group">
    <button class="btn btn-secondary dropdown-toggle" type="button" id="langDropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
      <?= $languages[$lang]['lang_name'] ?>
    </button>
    <ul class="dropdown-menu" aria-labelledby="langDropdown">
      <?php foreach ($languages as $lngKey => $data): ?>
      <li>
        <a class="dropdown-item <?= ($lngKey == $lang) ? 'active' : '' ?>" href="?lang=<?= $lngKey ?>">
          <?= $data['lang_name'] ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="lang-icon"><i class="fa-solid fa-earth-americas"></i></div>
</div>
<script>
if (typeof window.languageData === 'undefined') {
  window.currentLanguage = "<?= $lang ?>";
}
</script>