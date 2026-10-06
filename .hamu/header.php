<?php
/**
 * HAMU DevPanel - Header
 * Location: /.hamu/header.php
 * [TR] Sayfa başlığı, meta etiketleri, CSS dosyaları ve üst navigasyon çubuğunu içerir.
 * [EN] Contains the page header, meta tags, CSS files, and the top navigation bar.
 */
require_once __DIR__ . '/auth.php';

if (isset($include_db) && $include_db == 1) {
     $dbPath = HAMU_DIR . '/database.php';
     $includedFiles = get_included_files();
     if (!in_array($dbPath, $includedFiles, true) && file_exists($dbPath)) {
         require_once $dbPath;
     }
     if (function_exists('getActiveDatabase')) {
         $dbInfo        = getActiveDatabase();
         $active_db     = $dbInfo['active_db']     ?? '';
         $activeDBInfo  = $dbInfo['activeDBInfo']  ?? null;
         $databases     = $dbInfo['databases']     ?? [];
     }
 }
if (isset($page_title)) {
    $translated = __l(strtolower($page_title));
    $page_title = ($translated === strtolower($page_title)) ? $page_title : $translated;
}
$CSRF_TOKEN = csrf_token();
if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');

// ---- CUSTOM DEĞİŞKENLERİ: diğer tek sayfalar da kullanabilsin diye global üretelim ----
if (!isset($custom_css)) $custom_css = '';
if (!isset($custom_js))  $custom_js  = '';

// custom CSS (/.hamu/assets/css/*.css)
if (config('custom_css_s')) {
  $list = array_filter(array_map('trim', explode(',', config('custom_css_list_s') ?? '')));
  foreach ($list as $name) {
    // güvenli dosya adı
    $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '', $name);
    if ($safe === '') continue;
    if (!str_ends_with($safe, '.css')) $safe .= '.css';
    $custom_css .= '<link rel="stylesheet" href="/?a=assets/css/'.$safe.'">'."\n";
  }
}
$custom_js = '';

// Eğer veritabanı açık ise hamu.db.actions.min de eklensin
if (!empty($config["database_s"]) && (!isset($include_db) || $include_db != 0)) {
  $custom_js .= '<script src="/?a=assets/js/hamu.dbactions.min.js&v=1.0.0"></script>'."\n";
}


if (config('custom_js_s')) {
  $list = array_filter(array_map('trim', explode(',', config('custom_js_list_s') ?? '')));
  foreach ($list as $name) {
    $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '', $name);
    if ($safe === '') continue;
    if (!str_ends_with($safe, '.js')) $safe .= '.js';
    $custom_js .= '    <script src="/?a=assets/js/'.$safe.'"></script>'."\n";
  }
}


$_SESSION['themeFM'] = $theme;



?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES) ?>" data-bs-theme="<?= htmlspecialchars($theme, ENT_QUOTES) ?>">

<head>
     <meta charset="<?= __l('charset') ?>">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <?php if (config('jqueryui_s')): ?>
     <!-- jQuery UI (CSS) -->
     <link rel="stylesheet"
          href="https://code.jquery.com/ui/<?= htmlspecialchars(config('jqueryui_version_s') ?: '1.13.2') ?>/themes/base/jquery-ui.css">
     <?php endif; ?>

     <?php if (config('bootstrap_s')): ?>
     <!-- Bootstrap (CSS) -->
     <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/<?= htmlspecialchars(config('bootstrap_version_s') ?: '5.3.2') ?>/css/bootstrap.min.css">
     <?php endif; ?>

     <?php if (config('fontawesome_s')): ?>
     <!-- Font Awesome (CSS) -->
     <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/<?= htmlspecialchars(config('fontawesome_version_s') ?: '6.4.2') ?>/css/all.min.css">
     <?php endif; ?>

     <?php if (config('google_font_s') && trim((string)config('google_font_url_s')) !== ''): ?>
     <!-- Google Fonts (preconnect + families) -->
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?<?= htmlspecialchars(trim((string)config('google_font_url_s'))) ?>"
          rel="stylesheet">
     <?php endif; ?>

     <!-- PWA Manifest -->
     <link rel="manifest" href="/?a=assets/images/icons/manifest.webmanifest">

     <!-- Faviconlar (modern + eski tarayıcılar için) -->
     <link rel="icon" type="image/png" sizes="32x32" href="/?a=assets/images/icons/favicon-32x32.png">
     <link rel="icon" type="image/png" sizes="16x16" href="/?a=assets/images/icons/favicon-16x16.png">
     <link rel="icon" href="/?a=assets/images/icons/favicon.ico" sizes="any">

     <!-- iOS  -->
     <link rel="apple-touch-icon" sizes="180x180" href="/?a=assets/images/icons/apple-touch-icon.png">
     <link rel="apple-touch-icon" sizes="167x167" href="/?a=assets/images/icons/ios/167.png">
     <link rel="apple-touch-icon" sizes="152x152" href="/?a=assets/images/icons/ios/152.png">
     <link rel="apple-touch-icon" sizes="120x120" href="/?a=assets/images/icons/ios/120.png">

     <!-- Renkler -->
     <meta name="theme-color" content="#ededed">
     <meta name="msapplication-TileColor" content="#999">

     <!-- (Opsiyonel) iOS web app metaları -->
     <meta name="apple-mobile-web-app-title" content="HAMU Local Panel">
     <meta name="apple-mobile-web-app-status-bar-style" content="default">
     <!-- Main CSS -->
     <link rel="stylesheet" href="/?a=assets/css/hamu.main.min.css&v=1.0.0">
     <?php if (config('first_setup')): ?>
     <style>
     body::before {
          content: "";
          position: fixed;
          inset: 0;
          backdrop-filter: blur(8px);
          -webkit-backdrop-filter: blur(8px);
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 1040;
          /* modal (1050) arkasında kalır */
     }
     </style>
     <?php endif; ?>
     <!-- Custom CSS değişkeni -->
     <?= $custom_css ?>
     <meta name="csrf-token" content="<?=htmlspecialchars($CSRF_TOKEN, ENT_QUOTES)?>">
     <title><?= htmlspecialchars($page_title ?? 'HAMU', ENT_QUOTES) ?><?php if (!empty($_GET['p'])) echo " - HAMU"; ?>
     </title>
</head>
<body<?php if (empty($body_class)) { echo '';} else {echo ' class="'.$body_class.'"';} ?>>
     <input type="hidden" id="langValue" value="<?php echo $lang; ?>">

     <script>
  const originalFetch = window.fetch.bind(window);
  window.fetch = function(input, init = {}) {
    const request = new Request(input, init);
    if (new URL(request.url).origin === location.origin && !['GET','HEAD'].includes(request.method)) {
      const headers = new Headers(request.headers);
      headers.set('X-CSRF-Token', document.querySelector('meta[name="csrf-token"]')?.content || '');
      return originalFetch(new Request(request, {headers}));
    }
    return originalFetch(request);
  };
  window.getCsrfToken = window.getCsrfToken || function() {
    try {
      const m = document.querySelector('meta[name="csrf-token"]');
      if (m && m.content) return m.content;
      if (window.CSRF_TOKEN) return window.CSRF_TOKEN;
      return "";
    } catch(e){ return ""; }
  };
</script>

     <?php



// Varsayılanlar (güvenli)
$menu_type = $menu_type ?? 0;   // 0: sidebar kapalı, 1: açık
$side      = '';                // echo edilebilir HTML

// "Sadece side_bar=1 olduğunda $side üret"
if (isset($side_bar) && (int)$side_bar === 1) {
    ob_start();
    ?>
     <!-- [TR] Sidebar içeriği buraya eklenebilir; örn. ekstra servis linkleri -->
     <!-- [EN] Sidebar content can be added here; e.g., additional service links -->
     <div id="sidebarContent" class="sidebar d-none d-md-block"
          <?= (!empty($menu_type) && $menu_type != 0) ? 'style="display:none !important;"' : '' ?>>

          <div class="service-list">
               <!-- [TR] Sistemsel linkler, modüller hariç -->
               <!-- [EN] Link of Systems, without modules -->
               <?php if (!empty($menu_type)): ?><a href="/"><i class="fas fa-home"></i>
                    <?= __l('home') ?></a><?php endif ?>
               <a href="?p=projects" alt="<?= __l('projects_title') ?>" target="_self"><i class="fas fa-upload"></i>
                    <?= __l('projects_title') ?></a>
               <a href="?p=file-manager" alt="<?= __l('file_manager') ?>" target="_self"><i class="fas fa-file"></i>
                    <?= __l('file_manager') ?></a>
               <a href="?p=reader&md=readme.md" alt="<?= __l('readme') ?>" target="_self"><i
                         class="fas fa-info-circle"></i> <?= __l('readme') ?></a>
               <a href="#" onclick="openSettingsModal()"><i class="fas fa-cog"></i> <?= __l('settings') ?></a>
               <?php if ($config["ask_pass_s"] && !empty($config["app_pass_s"])): ?><a href="/?logout=1"><i
                         class="fas fa-sign-out"></i> <?= __l('logout') ?></a><?php endif; ?>


               <div class="server-info hide">
                    <h4><i class="fa-solid fa-toolbox"></i></i> <?= __l('services') ?></h4>
                    <?php if ($config["phpmyadmin_s"] && !empty($config["phpmyadmin_s_url"])): ?><a
                         href="<?= htmlspecialchars(jsonlink($config["phpmyadmin_s_url"])) ?>" target="_blank"><i
                              class="fas fa-database"></i> <?= __l('db_manager')?></a><?php endif; ?>
                    <?php if ($config["mail_server_s"] && !empty($config["mail_server_s_url"])): ?><a
                         href="<?= htmlspecialchars(jsonlink($config["mail_server_s_url"])) ?>" target="_blank"><i
                              class="fa fa-envelope"></i> Mail <?= __l('host') ?></a><?php endif; ?>
                    <?php if ($config["ftp_server_s"] && !empty($config["ftp_server_s_url"])): ?><a
                         href="<?= htmlspecialchars(jsonlink($config["ftp_server_s_url"])) ?>" target="_blank"><i
                              class="fa fa-server"></i> FTP <?= __l('host') ?></a><?php endif; ?>

               </div>
          </div>

          <?php if (!empty($config["modul_s"])): ?>
          <!-- modules dizinindekiler -->
          <div class="server-info hide">
               <h4><i class="fa fa-cubes"></i> <?= __l('modules') ?></h4>
               <div class="service-list">
                    <?php foreach ($modules as $mod): ?>
                    <a href="<?= module_href($mod['file']) ?>" target="_self">
                         <i class="fas fa-file-code"></i> <?= htmlspecialchars($mod['title']) ?>
                    </a>
                    <?php endforeach; ?>
               </div>
          </div>
          <?php endif; ?>


          <!-- Veritabanı Bilgisi -->
          <?php if (!empty($config["database_s"]) && (!isset($include_db) || $include_db != 0)) : ?>
          <div class="database-info">
               <?php if (!empty($activeDBInfo)): ?>
               <h4><i class="fas fa-database"></i>
                    <?= htmlspecialchars(trim(($activeDBInfo['dbName'] ?? 'Database') . ' ' . ($activeDBInfo['version'] ?? ''))) ?>
               </h4>
               <ul id="databaseList" class="desktop-view" data-active-db="<?= htmlspecialchars($active_db) ?>">
                    <?php foreach ($databases as $db): ?>
                    <li class="db-name <?= ($active_db === $db) ? 'active' : '' ?>"
                         data-db="<?= htmlspecialchars($db) ?>">
                         <i class="fas fa-database"></i> <?= htmlspecialchars($db) ?>
                    </li>
                    <?php endforeach; ?>
               </ul>
               <?php else: ?>
               <h5><i class="fas fa-database"></i> <?= __l('server_down')?></h5>
               <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Sunucu Bilgisi -->
          <div class="server-info">
               <h4 class="title"><i class="fas fa-server me-2"></i><?= __l('server_info_title') ?></h4>
               <?php
// Uzun path/host değerlerini şık kırmak için (sadece ayraçlardan kır)
if (!function_exists('softwrap')) {
  function softwrap($s) {
    $s = htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    // / \ . _ - : karakterlerinden sonra kırılabilir nokta ekle
    return preg_replace('~([/\\\\._:-])~', '$1<wbr>', $s);
  }
}
?>

               <dl class="kv pt-2">
                    <dt><?= __l('web_server') ?></dt>
                    <dd><?= htmlspecialchars($webServerName.' '.$webServerVersion) ?></dd>

                    <dt><?= __l('php_version') ?></dt>
                    <dd><?= htmlspecialchars($phpVersion) ?></dd>

                    <dt><?= __l('server_name') ?></dt>
                    <dd><code class="mono" title="<?= $serverName ?>"><?= softwrap($serverName) ?></code></dd>

                    <dt><?= __l('host_name') ?></dt>
                    <dd><code class="mono" title="<?= $hostName ?>"><?= softwrap($hostName) ?></code></dd>

                    <dt><?= __l('server_time') ?></dt>
                    <dd><?= date('d.m.Y H:i') ?></dd>

                    <dt>IP</dt>
                    <dd><code class="mono"
                              title="<?= $_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'] ?>"><?= softwrap(($_SERVER['SERVER_ADDR'] ?? '').':'.($_SERVER['SERVER_PORT'] ?? '')) ?></code>
                    </dd>

                    <dt><?= __l('root') ?></dt>
                    <dd><code class="mono" title="<?= HAMU_DIR ?>"><?= softwrap(HAMU_DIR ?? '') ?></code>
                    </dd>

                    <dt><?= __l('working_root') ?></dt>
                    <dd><code class="mono" title="<?= getcwd() ?>"><?= softwrap(getcwd()) ?></code></dd>

                    <dt><?= __l('memory_limit') ?></dt>
                    <dd><span class="kap text-dark"><?= htmlspecialchars(ini_get('memory_limit')) ?></span></dd>

                    <dt><?= __l('upload_limit') ?></dt>
                    <dd><span class="kap text-dark"><?= htmlspecialchars(ini_get('upload_max_filesize')) ?></span>
                         <span class="kap text-dark">P: <?= htmlspecialchars(ini_get('post_max_size')) ?></span>
                    </dd>
               </dl>
          </div>
     </div>
     <?php $side = ob_get_clean(); }?>

     <nav class="navbar">
          <!-- [TR] Sol bölüm: Logo / [EN] Left section: Logo -->
          <div class="logo">
               <a href="/" alt="Hamu Local Server Panel">
                    <?php $logoh = 35; include HAMU_IMAGE.'/logo.svg'?>
               </a>
          </div>
          <!-- [TR] Orta bölüm: Başlık / [EN] Center section: Title -->
          <div class="title"><?= __l('about_app') ?>
          </div>
          <!-- [TR] Sağ bölüm: Dil & Dark Mode Seçimi (dark-lang-wrapper ile birlikte) / [EN] Right section: Language & Dark Mode Switchers -->
          <div class="right-section" id="langDarkSelectionContent">
               <div class="dark-lang-wrapper">
                    <div class="dark-mode-icon"><i class="fa-solid fa-droplet"></i></div>
                    <div class="dark-mode-select">
                         <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" id="mySwitch" name="darkmode" value="yes"
                                   data-bs-toggle="tooltip" data-bs-placement="bottom" light="<?= __l('light_theme') ?>"
                                   dark="<?= __l('dark_theme') ?>" <?= config('theme_s') ? 'checked' : '' ?>>
                         </div>
                    </div>
               </div>
               <?php
///** Dil değiştirme dropdown menü  **///
global $languages, $lang;
?>
               <div class="btn-group btn-group-sm btn-tt" role="group" aria-label="language button">
                    <div class="btn-group btn-group-sm" role="group">
                         <button class="btn dropdown-toggle" type="button" id="langDropdown" data-bs-toggle="dropdown"
                              aria-expanded="false">
                              <?= $languages[$lang]['lang_name'] ?>
                         </button>
                         <ul class="dropdown-menu" aria-labelledby="langDropdown">
                              <?php foreach ($languages as $lngKey => $data): ?>
                              <li>
                                   <a class="dropdown-item <?= ($lngKey == $lang) ? 'active' : '' ?>"
                                        href="?lang=<?= $lngKey ?>">
                                        <?= $data['lang_name'] ?>
                                   </a>
                              </li>
                              <?php endforeach; ?>
                         </ul>
                    </div>
                    <div class="lang-icon"><i class="fa-solid fa-earth-americas"></i></div>
               </div>
          </div>
          <!-- Mobilde görünecek offcanvas butonu / Offcanvas toggle for mobile -->
          <button class="navbar-toggler <?= ($menu_type == 0 ) ? "d-block d-md-none" : ""; ?>" type="button"
               data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar"
               style="margin-left: 10px">
               <span class="navbar-toggler-icon"></span>
          </button>
     </nav>

     <!-- Mobil - Offcanvas Yanbar -->
     <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
          <div class="offcanvas-header" style="display: flex; justify-content: space-between; align-items: center;">
               <!-- [TR] Sol tarafta logo / [EN] Logo on the left -->
               <div class="offcanvas-logo"><span class="h3">DevPanel</span>
               </div>
               <!-- [TR] Sağ tarafta dil ve dark mode öğeleri ile kapat butonu, aralarında gap var / [EN] On the right side, language & dark mode elements and close button with a gap -->
               <div class="offcanvas-header-right" style="display: flex; align-items: center; gap: 10px;">
                    <div id="langDarkSelectionOffcanvasContent"></div>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                         aria-label="Close"></button>
               </div>
          </div>
          <div class="offcanvas-body">
               <div id="offcanvasSidebarContent"><?= $side ?></div>
          </div>
     </div>