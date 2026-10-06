<?php
/**
 * HAMU DevPanel - Anasayfa / Proje Listesi
 * Location: /index.php
 * [TR] Uygulamanın ana giriş noktası. Projeleri bir kart ızgarasında listeler, arama, sıralama ve filtreleme (favori, gizli, index dosyası olan) özellikleri sunar.
 *      Ayrıca, `?p=` (sayfa), `?a=` (asset) ve `?api=` (API) isteklerini ilgili yönlendiricilere (router) delege eder.
 * [EN] The main entry point of the application. Lists projects in a card grid, providing search, sorting, and filtering (favorite, hidden, has index file) features.
 *      It also delegates `?p=` (page), `?a=` (asset), and `?api=` (API) requests to their respective routers.
 */

require_once __DIR__.'/.hamu/security.php';

/* --- HAMU mini router  --- */
$hamuPub = __DIR__.'/.hamu/pub';

if (isset($_GET['api'])) {
  // JSON/AJAX
  $_GET['_prj'] = $_GET['_prj'] ?? '1';
  $_POST['_hamu_project'] = $_POST['_hamu_project'] ?? '1';
  require $hamuPub.'/api.php';
  exit;
}

if (isset($_GET['p']) || isset($_GET['a'])) {
  // Sayfa + (varsayılan) statik proxy
  require $hamuPub.'/p.php';
  exit;
}

/* ------------------------------ Tanımlar ------------------------------ */
// [TR] Sayfa yapılandırma değişkenleri
// [EN] Page configuration variables
$page_title = "title_app"; // [TR] Sayfa başlığı için dil anahtarı. [EN] Language key for the page title.
$body_class = "";          // [TR] <body> etiketine eklenecek özel CSS sınıfı. [EN] Custom CSS class for the <body> tag.
$include_db = 1;           // [TR] Veritabanı bağlantısı gerekli mi? (1=Evet, 0=Hayır). [EN] Is a database connection required? (1=Yes, 0=No).
$side_bar   = 1;           // [TR] Sol menü (sidebar) gösterilsin mi? (1=Evet, 0=Hayır). [EN] Should the left sidebar be displayed? (1=Yes, 0=No).
$menu_type  = 0;           // [TR] Menü davranışını kontrol eder. 0: Mobil menü butonu gösterilir. 1: Her zaman görünür menü. [EN] Controls menu behavior. 0: Show mobile menu button. 1: Always visible menu.

$paths = [
    __DIR__ . '/.hamu/header.php',
];

$includedFiles = get_included_files();
foreach ($paths as $path) {
    if (!in_array($path, $includedFiles, true)) {
        require_once $path;
    }
}

?>

<!-- Desktop Layout -->
<div class="dashboard">
     <?= $side ?>
     <div class="main-content">
          <div class="container">

               <!-- Projeler / Projects Header -->
               <div class="row pb-3 align-items-center g-2">
                    <div class="col-12 col-md-6 text-start d-flex align-items-center gap-2">
                         <h4 class="mb-0"><i class="fas fa-folder"></i> <?= __l('local_projects') ?></h4>
                    </div>

                    <div class="col-12 col-md-6 d-flex justify-content-md-end justify-content-start flex-wrap gap-2">
                         <!-- Arama -->
                         <div class="input-group" style="max-width: 175px;">
                              <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                              <input type="text" class="form-control form-control-sm" id="qInput" inputmode="search"
                                   placeholder="<?= __l('search')?>"
                                   value="<?= htmlspecialchars($q ?? ($_GET['q'] ?? '')) ?>">
                         </div>

                         <!-- Filtre Dropdown -->
                         <div class="dropdown">
                              <button class="btn btn-outline-secondary d-flex align-items-center gap-1" type="button"
                                   id="filterMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                   title="<?= __l('filters')?>">
                                   <i class="fa-solid fa-filter"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="filterMenuButton"
                                   style="min-width: 220px;">
                                   <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="fltFav"
                                             <?= !empty($favOnly) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="fltFav"><i
                                                  class="fa-solid fa-star me-1"></i><?= __l('favorites')?></label>
                                   </div>
                                   <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="fltHidden"
                                             <?= !empty($showHidden) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="fltHidden"><i
                                                  class="fa-solid fa-eye-slash me-1"></i><?= __l('show_hiddens')?></label>
                                   </div>
                                   <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="fltHasIndex"
                                             <?= !empty($hasIndex) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="fltHasIndex"><i
                                                  class="fa-solid fa-file-code me-1"></i><?= __l('j_index')?></label>
                                   </div>
                              </div>
                         </div>

                         <!-- Sıralama Dropdown -->
                         <div class="dropdown">
                              <button class="btn btn-outline-secondary d-flex align-items-center gap-1" type="button"
                                   id="sortMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                   title="<?= __l('sorts')?>">
                                   <i class="fa-solid fa-list-check"></i>
                              </button>
                              <ul class="dropdown-menu dropdown-menu-end small" style="font-size: 13px;"
                                   aria-labelledby="sortMenuButton">
                                   <li class="dropdown-header small text-muted px-3"><?= __l('to_name')?></li>
                                   <li>
                                        <a class="dropdown-item <?= $sort === 'name_asc' ? 'active' : '' ?>"
                                             href="?<?= build_query(['sort' => 'name_asc', 'page' => 1]) ?>">
                                             A → Z
                                        </a>
                                   </li>
                                   <li>
                                        <a class="dropdown-item <?= $sort === 'name_desc' ? 'active' : '' ?>"
                                             href="?<?= build_query(['sort' => 'name_desc', 'page' => 1]) ?>">
                                             Z → A
                                        </a>
                                   </li>
                                   <li>
                                        <hr class="dropdown-divider">
                                   </li>
                                   <li class="dropdown-header small text-muted px-3"><?= __l('to_ctime')?></li>
                                   <li>
                                        <a class="dropdown-item <?= $sort === 'ctime_desc' ? 'active' : '' ?>"
                                             href="?<?= build_query(['sort' => 'ctime_desc', 'page' => 1]) ?>">
                                             <?= __l('new_old')?>
                                        </a>
                                   </li>
                                   <li>
                                        <a class="dropdown-item <?= $sort === 'ctime_asc' ? 'active' : '' ?>"
                                             href="?<?= build_query(['sort' => 'ctime_asc', 'page' => 1]) ?>">
                                             <?= __l('old_new')?>
                                        </a>
                                   </li>
                                   <li>
                                        <hr class="dropdown-divider">
                                   </li>
                                   <li class="dropdown-header small text-muted px-3"><?= __l('to_mtime')?></li>
                                   <li>
                                        <a class="dropdown-item <?= $sort === 'mtime_desc' ? 'active' : '' ?>"
                                             href="?<?= build_query(['sort' => 'mtime_desc', 'page' => 1]) ?>">
                                             <?= __l('new_old')?>
                                        </a>
                                   </li>
                                   <li>
                                        <a class="dropdown-item <?= $sort === 'mtime_asc' ? 'active' : '' ?>"
                                             href="?<?= build_query(['sort' => 'mtime_asc', 'page' => 1]) ?>">
                                             <?= __l('old_new')?>
                                        </a>
                                   </li>
                              </ul>
                         </div>
                    </div>
               </div>

               <!-- Klasör Listeleme -->
               <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 g-3">
                    <?php foreach ($rows as $row):
          $dir        = $row['dir'];
          $folderName = $row['name'];
          $isHidden   = $row['isHidden'];

          $indexFile  = $row['indexFile'];
          $projectUrl = ($indexFile !== null) ? "/{$folderName}/{$indexFile}" : '#';

          // Kapak resmi: /{folder}/{folder}.png varsa göster
          // --- DÜZELTME: Önce logo.png, sonra {folderName}.png ara ---
          $logoPathAbs = "{$dir}/logo.png";
          $imagePathAbs = "{$dir}/{$folderName}.png";
          $logoSrc = null;
          if (is_file($logoPathAbs)) {
              $logoSrc = "/{$folderName}/logo.png";
          } elseif (is_file($imagePathAbs)) {
              $logoSrc = "/{$folderName}/{$folderName}.png";
          }
          $hasLogo = ($logoSrc !== null);
          // Klasör ikonu: index varsa normal, yoksa efolder
          $iconUrl      = ($indexFile !== null) ? '/?a=assets/images/folder.png' : '/?a=assets/images/efolder.png';

          // Tooltip
          $tooltip = ($indexFile === null)
              ? 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . htmlspecialchars($folderName) . '<br>' . __l('noindexfile') . '"'
              : 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . htmlspecialchars($folderName) . '"';

          // Badge / Kart aksanı
          $badgeText  = trim($row['badge_text'] ?? '');
          $badgeColor = trim($row['badge_color'] ?? '');

          // Kart aksanı için sınıf/stil (Bootstrap rengi ya da hex)
          $allowedColors = ['primary','secondary','success','danger','warning','info','light','dark'];
          $accentClass = '';
          $accentStyle = '';
          if ($badgeColor !== '') {
            if (in_array($badgeColor, $allowedColors, true)) {
              $accentClass = ' border-top border-3 border-' . $badgeColor;
            } elseif (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $badgeColor)) {
              $accentStyle = 'border-top:3px solid ' . htmlspecialchars($badgeColor) . ';';
            }
          }

          // Sabit badge görünümü (üstte), her zaman aynı yükseklik
          $badgeBg = '#d5d7d9'; // sabit arka plan
          $badgeFg = '#444';    // okunabilir koyu metin
      ?>
                    <div class="col">
                         <div class="folder text-center<?= $accentClass ?>" style="<?= $accentStyle ?>" <?= $tooltip ?>
                              <?= ($indexFile !== null) ? "onclick=\"window.open('{$projectUrl}','_blank')\"" : '' ?>>

                              <!-- SABİT BADGE SLOTU (üstte): hep aynı yükseklik, sabit renk (#d5d7d9) -->
                              <div class="mt-1 mb-1" style="min-height:2rem; line-height:1.5rem;">
                                   <?php if ($badgeText !== ''): ?>
                                   <span class="badge"
                                        style="background-color: <?= htmlspecialchars($badgeBg) ?>; color: <?= htmlspecialchars($badgeFg) ?> !important;">
                                        <?= htmlspecialchars($badgeText) ?>
                                   </span>
                                   <?php else: ?>
                                   <!-- boşsa boyut korunur -->
                                   <span class="badge bg-secondary invisible"
                                        style="background-color: <?= htmlspecialchars($badgeBg) ?>; color: <?= htmlspecialchars($badgeFg) ?>;">
                                        &nbsp;
                                   </span>
                                   <?php endif; ?>
                              </div>
                              <?php if ($hasLogo): ?>
                              <img src='<?= $logoSrc ?>' alt='<?= htmlspecialchars($folderName) ?>' class='img-fluid'>
                              <?php else: ?>
                              <img src='<?= $iconUrl ?>' class='img-fluid' alt='<?= htmlspecialchars($folderName) ?>'>
                              <?php endif; ?>

                              <div class="folder-word mt-2"><?= htmlspecialchars(short($folderName)) ?></div>

                              <!-- HOVER KISAYOLLARI -->
                              <button type="button" class="mobile-cog btn-icon" data-action="mobile-toggle"
                                   title="<?= __l('menu') ?>" onclick="event.stopPropagation();">
                                   <i class="fa-solid fa-gears"></i>
                              </button>
                              <div class="folder-actions" data-folder="<?= htmlspecialchars($folderName) ?>"
                                   data-project-url="<?= htmlspecialchars($projectUrl) ?>"
                                   data-has-index="<?= $indexFile ? '1' : '0' ?>"
                                   data-is-hidden="<?= $isHidden ? '1' : '0' ?>"
                                   data-favorite="<?= $row['isFav'] ? '1' : '0' ?>">

                                   <!-- Ayarlar -->
                                   <button type="button" class="btn-icon" data-action="settings"
                                        title="<?= __l('settings') ?>"
                                        onclick="event.stopPropagation(); openProjectSettingsModal('<?= htmlspecialchars($folderName) ?>');">
                                        <i class="fa-solid fa-cog"></i>
                                   </button>

                                   <!-- Favori -->
                                   <button type="button" class="btn-icon" data-action="fav"
                                        title="<?= __l($row['isFav'] ? 'rmv_favorite' : 'add_favorite') ?>"
                                        onclick="event.stopPropagation();">
                                        <i class="<?= $row['isFav'] ? 'fa-solid fa-star' : 'fa-regular fa-star' ?>"></i>
                                   </button>

                                   <!-- Gizle / Göster -->
                                   <?php if ($isHidden): ?>
                                   <button type="button" class="btn-icon" data-action="unhide"
                                        title="<?= __l('unhide') ?>" onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-eye"></i>
                                   </button>
                                   <?php else: ?>
                                   <button type="button" class="btn-icon" data-action="hide" title="<?= __l('hide') ?>"
                                        onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-eye-slash"></i>
                                   </button>
                                   <?php endif; ?>

                                   <!-- Kalıcı sil -->
                                   <button type="button" class="btn-icon" data-action="delete"
                                        title="<?= __l('delete') ?>" onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-trash"></i>
                                   </button>
                              </div>
                              <!-- /HOVER KISAYOLLARI -->

                         </div>
                    </div>
                    <?php endforeach; ?>
               </div>

               <!-- Sayfalama -->
               <?php if ($pages > 1): ?>
               <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                         <?= ($offset+1) ?>–<?= min($offset+$per, $total) ?> / <?= $total ?>
                    </div>
                    <nav>
                         <ul class="pagination pagination-sm mb-0">
                              <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                   <a class="page-link" href="?<?= build_query(['page' => 1]) ?>"
                                        aria-label="<?= __l('first') ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-angles-left"></i></span>
                                   </a>
                              </li>
                              <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                   <a class="page-link" href="?<?= build_query(['page' => max(1, $page-1)]) ?>"
                                        aria-label="<?= __l('previous') ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-angle-left"></i></span>
                                   </a>
                              </li>
                              <?php
              $start = max(1, $page-2);
              $end   = min($pages, $page+2);
              for ($p=$start; $p<=$end; $p++):
            ?>
                              <li class="page-item <?= $p==$page?'active':'' ?>">
                                   <a class="page-link" href="?<?= build_query(['page' => $p]) ?>"><?= $p ?></a>
                              </li>
                              <?php endfor; ?>
                              <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
                                   <a class="page-link" href="?<?= build_query(['page' => min($pages, $page+1)]) ?>"
                                        aria-label="<?= __l('next') ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-angle-right"></i></span>
                                   </a>
                              </li>
                              <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
                                   <a class="page-link" href="?<?= build_query(['page' => $pages]) ?>"
                                        aria-label="<?= __l('end') ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-angles-right"></i></span>
                                   </a>
                              </li>
                         </ul>
                    </nav>
               </div>
               <?php endif; ?>

          </div>
     </div>
</div>
<?= get_footer(); ?>