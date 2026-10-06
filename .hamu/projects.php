<?php
/**
 * HAMU DevPanel - FTP & Project Manager
 * Location: /.hamu/projects.php
 * [TR] Projeler için birleşik bir FTP ve yerel dosya yöneticisi. Projeleri listeler, proje bazlı FTP ayarlarını yönetir,
 *      yerel ve uzak dosya sistemlerinde gezinmeye olanak tanır. Tekil veya toplu dosya yükleme ve indirme işlemlerini destekler.
 * [EN] A unified FTP and local file manager for projects. It lists projects, manages per-project FTP settings,
 *      allows browsing local and remote filesystems, and supports single or bulk file uploads and downloads.
 *
 * --- KULLANIM / USAGE ---
 * API Endpoint: /?p=projects
 *
 * Parametreler / Parameters:
 * &project=<proje_adi>
 *   [TR] Belirtilen projeyi seçer ve ayarlarını görüntüler.
 *   [EN] Selects the specified project and displays its settings.
 *
 * &action=local
 *   [TR] Seçili projenin yerel dosya yöneticisini açar.
 *   [EN] Opens the local file manager for the selected project.
 *
 * &action=ftpmanager
 *   [TR] Seçili projenin FTP sunucusundaki dosya yöneticisini açar.
 *   [EN] Opens the remote file manager on the FTP server for the selected project.
 *
 * &d=<dizin_yolu>
 *   [TR] Yerel veya FTP yöneticisinde belirtilen alt dizine gider.
 *   [EN] Navigates to the specified subdirectory in the local or FTP manager.
 */
ob_start();
require_once __DIR__ . '/auth.php';

/** HTML — header/footer ile **/

/* ------------------------------ Tanımlar ------------------------------ */
// [TR] Sayfa yapılandırma değişkenleri
// [EN] Page configuration variables
$page_title = "projects_title"; // [TR] Sayfa başlığı için dil anahtarı. [EN] Language key for the page title.
$body_class = "";              // [TR] <body> etiketine eklenecek özel CSS sınıfı. [EN] Custom CSS class for the <body> tag.
$include_db = 1;              // [TR] Veritabanı bağlantısı gerekli mi? (1=Evet, 0=Hayır). [EN] Is a database connection required? (1=Yes, 0=No).
$side_bar   = 1;             // [TR] Sol menü (sidebar) gösterilsin mi? (1=Evet, 0=Hayır). [EN] Should the left sidebar be displayed? (1=Yes, 0=No).
$menu_type  = 1;            // [TR] Menü davranışını kontrol eder. 0: Mobil menü butonu gösterilir. 1: Her zaman görünür menü. [EN] Controls menu behavior. 0: Show mobile menu button. 1: Always visible menu.


require_once $HAMU_DIR.'/header.php';

/* --------------------------------------------------
 * VIEW: LOCAL MANAGER
 * --------------------------------------------------*/
if ($mode==='local' && $selectedProject) {
  $p    = isset($_GET['d'])? trim($_GET['d'],'/'): '';
  $base = HAMU_DOCROOT.'/'.$selectedProject;
  $cur  = $p? $base.'/'.$p : $base;
  if (!is_dir($cur)){ $p=''; $cur=$base; }

  $items= @scandir($cur)?:[];
  $list = [];
  foreach($items as $it){
    if ($it === '.' || $it === '..') continue;
    if (!$showHiddenFtp && isset($it[0]) && $it[0] === '.') continue; // gizli filtre (Local manager)
    $fp    = $cur.'/'.$it;
    $isDir = is_dir($fp);
    $sz    = $isDir ? 0 : @filesize($fp);
    $list[] = ['name'=>$it, 'isDir'=>$isDir, 'size'=>$sz];
  }
  usort($list, function($a,$b){
    if($a['isDir']!==$b['isDir']) return $a['isDir']? -1:1;
    return strcasecmp($a['name'],$b['name']);
  });
  $parent= '';
  if($p){ $pos=strrpos($p,'/'); $parent=($pos!==false)? substr($p,0,$pos):''; }
  ?>
<div class="container-module container-fluid">
     <div class="content-ftp py-3">
          <?php if($msgGlobal): ?><div class="alert alert-info mb-3"><?= $msgGlobal ?></div><?php endif; ?>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
               <h3 class="mb-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-folder-open"></i>
                    <span><?= __l('local_files') ?> (<?= htmlspecialchars($selectedProject) ?>)</span>
               </h3>
               <a class="btn btn-sm btn-secondary" href="?p=projects&project=<?= urlencode($selectedProject) ?>">
                    <i class="fa-solid fa-arrow-left me-1"></i> <?= __l('backto_projects') ?>
               </a>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
               <div class="d-flex align-items-center gap-2">
                    <?php if($p): ?>
                    <a class="btn btn-sm btn-outline-secondary"
                         href="?p=projects&action=local&project=<?= urlencode($selectedProject) ?>&d=<?= urlencode($parent) ?>"
                         title="<?= __l('folder_up') ?>"><i class="fa-solid fa-arrow-up"></i></a>
                    <?php endif; ?>
                    <div class="fw-semibold">
                         <span class="text-muted"><?= __l('path_') ?>:</span>
                         <span><?= $p? htmlspecialchars($p):'[root]' ?></span>
                    </div>
               </div>
               <form method="post" class="d-flex align-items-center gap-2 rounded-3 simple-color p-2">
                    <input type="hidden" name="toggleHiddenForm" value="1">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <label class="form-check-label" for="showHiddenFtpLocal" data-bs-toggle="tooltip"
                         data-bs-placement="bottom" title="<?= __l('show_hidden') ?>">
                         <i class="fa-solid mt-1 fa-eye<?= $showHiddenFtp?'-slash':'' ?>"></i>
                    </label>
                    <div class="form-check form-switch m-0">
                         <input class="form-check-input" type="checkbox" id="showHiddenFtpLocal" name="showHidden"
                              <?= $showHiddenFtp?'checked':'' ?> onchange="this.form.submit();">
                    </div>
               </form>
          </div>

          <div class="card shadow-sm mb-3">
               <div class="card-body">
                    <div class="row g-2">
                         <div class="col-12 col-md-6">
                              <form method="post" class="d-flex gap-2">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="localAction" value="newFolder">
                                   <input type="text" name="folderName" class="form-control form-control-sm"
                                        placeholder="<?= __l('new').' '.__l('folder_name') ?>">
                                   <button class="btn btn-sm btn-outline-secondary"><i
                                             class="fa-solid fa-folder"></i></button>
                              </form>
                         </div>
                         <div class="col-12 col-md-6">
                              <form method="post" class="d-flex gap-2">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="localAction" value="newFile">
                                   <input type="text" name="fileName" class="form-control form-control-sm"
                                        placeholder="<?= __l('new').' '.__l('file_name') ?>">
                                   <button class="btn btn-sm btn-outline-secondary"><i
                                             class="fa-regular fa-file"></i></button>
                              </form>
                         </div>
                    </div>
               </div>
          </div>

          <!-- Masaüstü tablo -->
          <div class="table-responsive d-none d-md-block">
               <table class="table table-hover align-middle">
                    <thead class="table-light">
                         <tr>
                              <th><?= __l('file') ?></th>
                              <th style="width:120px;"><?= __l('size') ?></th>
                              <th style="width:320px;"><?= __l('ops') ?></th>
                         </tr>
                    </thead>
                    <tbody>
                         <?php if($list): foreach($list as $li):
            $szDisp= $li['isDir']? '-' : ( round($li['size']/1024,2).' KB'); ?>
                         <tr>
                              <td>
                                   <?php if($li['isDir']): ?>
                                   <i class="fa-solid fa-folder text-warning me-1"></i>
                                   <a
                                        href="?p=projects&action=local&project=<?= urlencode($selectedProject) ?>&d=<?= urlencode($p? $p.'/'.$li['name']:$li['name']) ?>"><?= htmlspecialchars($li['name']) ?></a>
                                   <?php else: ?>
                                   <i class="fa-regular fa-file me-1"></i> <?= htmlspecialchars($li['name']) ?>
                                   <?php endif; ?>
                              </td>
                              <td class="text-muted"><?= $szDisp ?></td>
                              <td>
                                   <form method="post" class="d-inline"
                                        onsubmit="return confirm('<?= __l('sure') ?>');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="localAction" value="delete">
                                        <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                   </form>
                                   <button class="btn btn-sm btn-warning" data-bs-toggle="collapse"
                                        data-bs-target="#rn-<?= md5($li['name']) ?>"><i
                                             class="fa-solid fa-pen"></i></button>
                                   <div class="collapse mt-2" id="rn-<?= md5($li['name']) ?>">
                                        <form method="post" class="row g-1 mt-1">
                                             <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                             <input type="hidden" name="localAction" value="rename">
                                             <input type="hidden" name="target"
                                                  value="<?= htmlspecialchars($li['name']) ?>">
                                             <div class="col-auto"><input type="text" name="newName"
                                                       class="form-control form-control-sm" required></div>
                                             <div class="col-auto"><button class="btn btn-sm btn-success"><i
                                                            class="fa-solid fa-check"></i></button></div>
                                        </form>
                                   </div>
                                   <?php if(!$li['isDir']):
                  $relFile = $p? $p.'/'.$li['name'] : $li['name']; ?>
                                   <form method="post" class="d-inline ms-1">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="localAction" value="uploadSingle">
                                        <input type="hidden" name="relFile" value="<?= htmlspecialchars($relFile) ?>">
                                        <button class="btn btn-sm btn-primary"><i
                                                  class="fa-solid fa-cloud-arrow-up"></i></button>
                                   </form>
                                   <?php else:
                  $relDir = $p? $p.'/'.$li['name'] : $li['name']; ?>
                                   <form method="post" class="d-inline ms-1">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="localAction" value="uploadFolder">
                                        <input type="hidden" name="relDir" value="<?= htmlspecialchars($relDir) ?>">
                                        <button class="btn btn-sm btn-primary"><i
                                                  class="fa-solid fa-cloud-arrow-up"></i></button>
                                   </form>
                                   <?php endif; ?>
                              </td>
                         </tr>
                         <?php endforeach; else: ?>
                         <tr>
                              <td colspan="3" class="text-muted"><?= __l('empty') ?></td>
                         </tr>
                         <?php endif; ?>
                    </tbody>
               </table>
          </div>

          <!-- Mobil kartlar -->
          <div class="d-md-none">
               <?php if($list): foreach($list as $li):
          $szDisp= $li['isDir']? '-' : ( round($li['size']/1024,2).' KB'); ?>
               <div class="card shadow-sm mb-2">
                    <div class="card-body d-flex align-items-start justify-content-between gap-3">
                         <div class="flex-grow-1">
                              <div class="d-flex align-items-center gap-2">
                                   <?php if($li['isDir']): ?>
                                   <i class="fa-solid fa-folder text-warning"></i>
                                   <a class="fw-semibold"
                                        href="?p=projects&action=local&project=<?= urlencode($selectedProject) ?>&d=<?= urlencode($p? $p.'/'.$li['name']:$li['name']) ?>"><?= htmlspecialchars($li['name']) ?></a>
                                   <?php else: ?>
                                   <i class="fa-regular fa-file"></i>
                                   <span class="fw-semibold"><?= htmlspecialchars($li['name']) ?></span>
                                   <?php endif; ?>
                              </div>
                              <div class="small text-muted mt-1"><?= __l('size') ?>: <?= $szDisp ?></div>
                         </div>
                         <div class="d-flex flex-wrap gap-1">
                              <form method="post" onsubmit="return confirm('<?= __l('sure') ?>');">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="localAction" value="delete">
                                   <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                                   <button class="btn btn-outline-danger btn-sm"><i
                                             class="fa-solid fa-trash"></i></button>
                              </form>
                              <button class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse"
                                   data-bs-target="#m-rn-<?= md5('m'.$li['name']) ?>"><i
                                        class="fa-solid fa-pen"></i></button>
                              <?php if(!$li['isDir']):
                  $relFile = $p? $p.'/'.$li['name'] : $li['name']; ?>
                              <form method="post">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="localAction" value="uploadSingle">
                                   <input type="hidden" name="relFile" value="<?= htmlspecialchars($relFile) ?>">
                                   <button class="btn btn-primary btn-sm"><i
                                             class="fa-solid fa-cloud-arrow-up"></i></button>
                              </form>
                              <?php else:
                  $relDir = $p? $p.'/'.$li['name'] : $li['name']; ?>
                              <form method="post">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="localAction" value="uploadFolder">
                                   <input type="hidden" name="relDir" value="<?= htmlspecialchars($relDir) ?>">
                                   <button class="btn btn-primary btn-sm"><i
                                             class="fa-solid fa-cloud-arrow-up"></i></button>
                              </form>
                              <?php endif; ?>
                         </div>
                    </div>
                    <div class="collapse px-3 pb-3" id="m-rn-<?= md5('m'.$li['name']) ?>">
                         <form method="post" class="d-flex gap-2">
                              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                              <input type="hidden" name="localAction" value="rename">
                              <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                              <input type="text" name="newName" class="form-control form-control-sm"
                                   placeholder="<?= __l('file_name') ?>" required>
                              <button class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i></button>
                         </form>
                    </div>
               </div>
               <?php endforeach; else: ?>
               <div class="text-muted"><?= __l('empty') ?></div>
               <?php endif; ?>
          </div>
     </div>
</div>
<?php
  echo get_footer();
  exit;
}

/* --------------------------------------------------
 * VIEW: FTP MANAGER
 * --------------------------------------------------*/
if ($mode==='ftp' && $selectedProject) {
  $jf=HAMU_DOCROOT.'/'.$selectedProject.'/'.$selectedProject.'.json';
  if (!is_file($jf)){ setMsgL('no_json'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); }
  $cfg=_ftp_read_block($jf);
  if (empty($cfg)){ setMsgL('error_json'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); }

  $host=$cfg['ftp_host']; $port=(int)$cfg['ftp_port']; $ssl= ($cfg['ftp_ssl']==='true');
  $user=$cfg['ftp_user']; $pass= $cfg['ftp_pass_plain']; $fold= trim($cfg['ftp_folder']);
  $hurl=rtrim($cfg['host_url'],'/');
  if ($pass===''){ setMsgL('ftp_err3'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); }
  if ($warn=ensureFtpExtensions($ssl)){ setMsgL($warn); safe_redirect('?p=projects&project='.urlencode($selectedProject)); }

  $cid = $ssl ? @ftp_ssl_connect($host,$port) : @ftp_connect($host,$port);
  if (!$cid){ setMsgL('ftp_error'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); }
  if (!@ftp_login($cid,$user,$pass)){ @ftp_close($cid); setMsgL('ftp_error_login'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); }
  @ftp_pasv($cid,true);

  $p = isset($_GET['d'])? trim($_GET['d'],'/'): '';
  $baseRel = ftp_base_relative($cid, $fold);
  $curDir  = ($baseRel===''? $p : ($p===''? $baseRel : $baseRel.'/'.$p));
  $list    = ftp_list_dir_smart($cid, ($curDir===''?'.':$curDir), $showHiddenFtp); // gizli filtre (FTP)
  @ftp_close($cid);

  $msg = $msgGlobal;
  ?>
<div class="container-module container-fluid">
     <div class="content-large py-3">
          <?php if($msg): ?><div class="alert alert-info mb-3"><?= $msg ?></div><?php endif; ?>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
               <h3 class="mb-0 d-flex align-items-center gap-2">
                    <a href="?p=projects"><i class="fa-solid fa-upload"></i>
                         <span><?= __l('ftp_manager') ?> (<?= htmlspecialchars($selectedProject) ?>)</span></a>
               </h3>
               <a class="btn btn-sm btn-secondary" href="?p=projects&project=<?= urlencode($selectedProject) ?>">
                    <i class="fa-solid fa-arrow-left me-1"></i> <?= __l('backto_projects') ?>
               </a>
          </div>

          <div class="toolbar sticky-top border rounded-3 p-2 mb-3 bg-body">
               <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                         <?php
              $parent=''; if($p){ $pos=strrpos($p,'/'); $parent=($pos!==false)? substr($p,0,$pos):''; }
            ?>
                         <?php if($p): ?>
                         <a class="btn btn-sm btn-outline-secondary"
                              href="?p=projects&action=ftpmanager&project=<?= urlencode($selectedProject) ?>&d=<?= urlencode($parent) ?>"
                              title="<?= __l('folder_up') ?>"><i class="fa-solid fa-arrow-up"></i></a>
                         <?php endif; ?>
                         <div class="fw-semibold">
                              <span class="text-muted"><?= __l('path_') ?>:</span>
                              <span><?= $p? htmlspecialchars($p):'[root]' ?></span>
                         </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                         <!-- Toggle FORM -->
                         <form method="post" class="d-flex align-items-center gap-3 rounded-3 simple-color p-2 m-0">
                              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                              <div class="d-flex align-items-center gap-2">
                                   <input type="hidden" name="toggleHiddenForm" value="1">
                                   <label class="form-check-label" for="showHiddenFtpManager" data-bs-toggle="tooltip"
                                        data-bs-placement="bottom" title="<?= __l('show_hidden') ?>">
                                        <i class="fa-solid mt-1 fa-eye<?= $showHiddenFtp?'-slash':'' ?>"></i>
                                   </label>
                                   <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="showHiddenFtpManager"
                                             name="showHidden" <?= $showHiddenFtp?'checked':'' ?>
                                             onchange="this.form.submit();">
                                   </div>
                              </div>
                         </form>

                         <div class="vr d-none d-md-block"></div>

                         <!-- Yeni klasör/dosya FORM -->
                         <form method="post" class="d-flex flex-wrap gap-2 m-0">
                              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                              <div class="input-group input-group-sm">
                                   <span class="input-group-text"><i class="fa-solid fa-folder"></i></span>
                                   <input type="text" name="folderName" class="form-control"
                                        placeholder="<?= __l('new').' '.__l('folder_name') ?>">
                                   <button class="btn btn-outline-secondary" name="ftpAction"
                                        value="newFolder"><?= __l('new') ?></button>
                              </div>
                              <div class="input-group input-group-sm">
                                   <span class="input-group-text"><i class="fa-regular fa-file"></i></span>
                                   <input type="text" name="fileName" class="form-control"
                                        placeholder="<?= __l('new').' '.__l('file_name') ?>">
                                   <button class="btn btn-outline-secondary" name="ftpAction"
                                        value="newFile"><?= __l('new') ?></button>
                              </div>
                         </form>
                    </div>
               </div>
          </div>

          <!-- Masaüstü tablo -->
          <div class="table-responsive d-none d-md-block">
               <table class="table table-hover align-middle">
                    <thead class="table-light">
                         <tr>
                              <th><?= __l('file') ?></th>
                              <th style="width:120px;"><?= __l('size') ?></th>
                              <th style="width:240px;"><?= __l('ops') ?></th>
                         </tr>
                    </thead>
                    <tbody>
                         <?php if($list):
              usort($list,function($a,$b){ if($a['isDir']!==$b['isDir']) return $a['isDir']? -1:1; return strcasecmp($a['name'],$b['name']); });
              foreach($list as $li):
                $szDisp= $li['isDir']? '-' : ( round($li['size']/1024,2).' KB'); ?>
                         <tr>
                              <td>
                                   <?php if($li['isDir']): ?>
                                   <i class="fa-solid fa-folder text-warning me-1"></i>
                                   <?php $next = $p? $p.'/'.$li['name'] : $li['name']; ?>
                                   <a
                                        href="?p=projects&action=ftpmanager&project=<?= urlencode($selectedProject) ?>&d=<?= urlencode($next) ?>"><?= htmlspecialchars($li['name']) ?></a>
                                   <?php else: ?>
                                   <i class="fa-regular fa-file me-1"></i> <?= htmlspecialchars($li['name']) ?>
                                   <?php
                    $ext=strtolower(pathinfo($li['name'],PATHINFO_EXTENSION));
                    if(in_array($ext,['jpg','jpeg','png','gif','pdf','webp','svg'])){
                        $showUrl= $hurl.'/'.($p? $p.'/':'').$li['name'];
                        echo ' <a class="ms-1" href="'.htmlspecialchars($showUrl).'" target="_blank" title="'. __l('show') .'"><i class="fa-solid fa-eye"></i></a>';
                    }
                  ?>
                                   <?php endif; ?>
                              </td>
                              <td class="text-muted"><?= $szDisp ?></td>
                              <td>
                                   <form method="post" class="d-inline"
                                        onsubmit="return confirm('<?= __l('sure') ?>');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="ftpAction" value="delete">
                                        <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                   </form>
                                   <button class="btn btn-sm btn-warning" data-bs-toggle="collapse"
                                        data-bs-target="#rn-<?= md5($li['name'].$li['path']) ?>"><i
                                             class="fa-solid fa-pen"></i></button>
                                   <div class="collapse mt-2" id="rn-<?= md5($li['name'].$li['path']) ?>">
                                        <form method="post" class="row g-1 mt-1">
                                             <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                             <input type="hidden" name="ftpAction" value="rename">
                                             <input type="hidden" name="target"
                                                  value="<?= htmlspecialchars($li['name']) ?>">
                                             <div class="col-auto"><input type="text" name="newName"
                                                       class="form-control form-control-sm" required></div>
                                             <div class="col-auto"><button class="btn btn-sm btn-success"><i
                                                            class="fa-solid fa-check"></i></button></div>
                                        </form>
                                   </div>
                                   <?php if(!$li['isDir']): ?>
                                   <form method="post" class="d-inline ms-1">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="ftpAction" value="download">
                                        <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                                        <button class="btn btn-sm btn-outline-secondary"><i
                                                  class="fa-solid fa-download"></i></button>
                                   </form>
                                   <?php endif; ?>
                              </td>
                         </tr>
                         <?php endforeach; else: ?>
                         <tr>
                              <td colspan="3" class="text-muted"><?= __l('empty') ?></td>
                         </tr>
                         <?php endif; ?>
                    </tbody>
               </table>
          </div>

          <!-- Mobil kartlar -->
          <div class="d-md-none">
               <?php if($list):
          usort($list,function($a,$b){ if($a['isDir']!==$b['isDir']) return $a['isDir']? -1:1; return strcasecmp($a['name'],$b['name']); });
          foreach($list as $li):
            $szDisp= $li['isDir']? '-' : ( round($li['size']/1024,2).' KB');
            $next = $p? $p.'/'.$li['name'] : $li['name']; ?>
               <div class="card shadow-sm mb-2">
                    <div class="card-body d-flex align-items-start justify-content-between gap-3">
                         <div class="flex-grow-1">
                              <div class="d-flex align-items-center gap-2">
                                   <?php if($li['isDir']): ?>
                                   <i class="fa-solid fa-folder text-warning"></i>
                                   <a class="fw-semibold"
                                        href="?p=projects&action=ftpmanager&project=<?= urlencode($selectedProject) ?>&d=<?= urlencode($next) ?>"><?= htmlspecialchars($li['name']) ?></a>
                                   <?php else: ?>
                                   <i class="fa-regular fa-file"></i>
                                   <span class="fw-semibold"><?= htmlspecialchars($li['name']) ?></span>
                                   <?php endif; ?>
                              </div>
                              <div class="small text-muted mt-1"><?= __l('size') ?>: <?= $szDisp ?></div>
                         </div>
                         <div class="d-flex flex-wrap gap-1">
                              <form method="post" onsubmit="return confirm('<?= __l('sure') ?>');">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="ftpAction" value="delete">
                                   <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                                   <button class="btn btn-outline-danger btn-sm"><i
                                             class="fa-solid fa-trash"></i></button>
                              </form>
                              <button class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse"
                                   data-bs-target="#mfrn-<?= md5('m'.$li['name'].$li['path']) ?>"><i
                                        class="fa-solid fa-pen"></i></button>
                              <?php if(!$li['isDir']): ?>
                              <form method="post">
                                   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                   <input type="hidden" name="ftpAction" value="download">
                                   <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                                   <button class="btn btn-outline-secondary btn-sm"><i
                                             class="fa-solid fa-download"></i></button>
                              </form>
                              <?php endif; ?>
                         </div>
                    </div>
                    <div class="collapse px-3 pb-3" id="mfrn-<?= md5('m'.$li['name'].$li['path']) ?>">
                         <form method="post" class="d-flex gap-2">
                              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                              <input type="hidden" name="ftpAction" value="rename">
                              <input type="hidden" name="target" value="<?= htmlspecialchars($li['name']) ?>">
                              <input type="text" name="newName" class="form-control form-control-sm"
                                   placeholder="<?= __l('file_name') ?>" required>
                              <button class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i></button>
                         </form>
                    </div>
               </div>
               <?php endforeach; else: ?>
               <div class="text-muted"><?= __l('empty') ?></div>
               <?php endif; ?>
          </div>
     </div>
</div>
<?php
  echo get_footer();
  exit;
}

/* --------------------------------------------------
 * VIEW: HOME (sağ kart / sol liste)
 * --------------------------------------------------*/
?>
<div class="container-module container-fluid">
     <div class="content-ftp py-3">

          <?php if($msgGlobal): ?>
          <div class="alert alert-info mb-3"><?= $msgGlobal ?></div>
          <?php endif; ?>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 ">
               <h2 class="mb-0 d-flex align-items-center gap-2">
                    <a href="?p=projects"><i class="fa-solid fa-folder-tree"></i>
                         <span><?= __l('proj_title')?></span></a>
               </h2>

               <form method="post" class="d-flex align-items-center gap-2 rounded-3 simple-color p-2">
                    <input type="hidden" name="toggleHiddenForm" value="1">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <label class="form-check-label" for="showHiddenFtpHome" data-bs-toggle="tooltip"
                         data-bs-placement="bottom" title="<?= __l('show_hidden') ?>">
                         <i class="fa-solid mt-1 fa-eye<?= $showHiddenFtp?'-slash':'' ?>"></i>
                    </label>
                    <div class="form-check form-switch m-0">
                         <input class="form-check-input" type="checkbox" id="showHiddenFtpHome" name="showHidden"
                              <?= $showHiddenFtp?'checked':'' ?> onchange="this.form.submit();">
                    </div>
                    <div class="vr mx-3"></div>
                    <a href="?p=reader&log=project_actions.log" data-bs-toggle="tooltip" data-bs-placement="bottom"
                         title="<?= __l('upload_logs')?>"><i class="fa-solid fa-file-lines pe-3"></i></a>
               </form>
          </div>

          <div class="row g-3">
               <!-- Sol Liste -->
               <div class="col-12 col-md-3">
                    <div class="list-group list-limit">
                         <?php foreach($projects as $prj): ?>
                         <a href="?p=projects&project=<?= urlencode($prj) ?>"
                              class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?= $prj===$selectedProject? 'active':'' ?>">
                              <i class="fa-solid fa-folder"></i><span><?= htmlspecialchars($prj) ?></span>
                         </a>
                         <?php endforeach; ?>
                    </div>
               </div>

               <!-- Sağ Kart -->
               <div class="col-12 col-md-9">
                    <?php
        if ($selectedProject){
          $jf = HAMU_DOCROOT.'/'.$selectedProject.'/'.$selectedProject.'.json';
          $hasJson= is_file($jf);
          $jsonErr= false;
          $arr=[]; $realPass='';

          if ($hasJson){
            $cfg=_ftp_read_block($jf);
            if (empty($cfg)){
              $jsonErr=true;
            } else {
              $arr = [
                'ftp_host'        => $cfg['ftp_host']        ?? '',
                'ftp_port'        => $cfg['ftp_port']        ?? '21',
                'ftp_ssl'         => $cfg['ftp_ssl']         ?? 'false',
                'ftp_user'        => $cfg['ftp_user']        ?? '',
                'ftp_pass'        => $cfg['ftp_pass_enc']    ?? '',
                'ftp_folder'      => $cfg['ftp_folder']      ?? '',
                'host_url'        => $cfg['host_url']        ?? '',
                'description'     => $cfg['description']     ?? '',
                'download_folder' => $cfg['download_folder'] ?? '',
              ];
              $realPass = $cfg['ftp_pass_plain'] ?? '';
            }
          }
          $desc= $arr['description']??'';
          ?>
                    <div class="card shadow-sm">
                         <div class="card-header d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-2">
                                   <span class="text-muted"><?= __l('selected_proj'); ?>:</span>
                                   <span class="text-truncate"><?= htmlspecialchars($selectedProject); ?></span>
                              </div>
                              <?php
                                $logoPath = HAMU_DOCROOT.'/'.$selectedProject.'/logo.png';
                                $pngPath = HAMU_DOCROOT.'/'.$selectedProject.'/'.$selectedProject.'.png';
                                $logoSrc = null;
                                if (is_file($logoPath)) {
                                    $logoSrc = "/{$selectedProject}/logo.png";
                                } elseif (is_file($pngPath)) {
                                    $logoSrc = "/{$selectedProject}/{$selectedProject}.png";
                                } else {
                                    $logoSrc = "/?a=assets/images/folder.png";
                                }
                              ?>
                              <img src="<?= $logoSrc ?>" style="height:40px;" alt="logo" class="rounded">
                         </div>
                         <div class="card-body">
                              <?php
                if(!$hasJson)      echo '<div class="alert alert-warning mb-2">'.__l("no_json").'</div>';
                elseif($jsonErr)   echo '<div class="alert alert-danger mb-2">'.__l("error_json").'</div>';
                else               echo '<div class="alert alert-success mb-2">'.__l("ok_json").'</div>';
                if($desc) echo '<p class="mb-3"><strong>'.__l('description').':</strong> '.htmlspecialchars($desc).'</p>';
              ?>

                              <div class="d-flex flex-wrap gap-2 align-items-center">
                                   <a class="btn btn-secondary btn-sm"
                                        href="?p=projects&action=local&project=<?= urlencode($selectedProject) ?>">
                                        <i class="fa-solid fa-folder-open me-1"></i> <?= __l('local_files') ?>
                                   </a>

                                   <?php if($hasJson && !$jsonErr): ?>
                                   <button class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#uploadModal">
                                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> <?= __l('upload_server') ?>
                                   </button>
                                   <a class="btn btn-secondary btn-sm"
                                        href="?p=projects&action=ftpmanager&project=<?= urlencode($selectedProject) ?>">
                                        <i class="fa-solid fa-upload me-1"></i> <?= __l('ftp_manager') ?>
                                   </a>
                                   <?php endif; ?>

                                   <button class="btn btn-secondary btn-sm ms-auto" data-bs-toggle="modal"
                                        data-bs-target="#jsonModal">
                                        <i class="fa-solid fa-gear me-1"></i> <?= __l('settings') ?>
                                   </button>
                              </div>
                         </div>
                    </div>

                    <!-- Modal: JSON Settings -->
                    <div class="modal fade" id="jsonModal" tabindex="-1" aria-hidden="true">
                         <div class="modal-dialog modal-lg modal-dialog-scrollable">
                              <div class="modal-content">
                                   <form method="post">
                                        <div class="modal-header">
                                             <h5 class="modal-title"><?= __l('settings') ?>
                                                  (<?= htmlspecialchars($selectedProject) ?>)</h5>
                                             <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                             <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                             <input type="hidden" name="projName"
                                                  value="<?= htmlspecialchars($selectedProject) ?>">
                                             <div class="mb-3">
                                                  <label class="form-label"><?= __l('description') ?></label>
                                                  <textarea name="description"
                                                       class="form-control"><?= $arr['description']??'' ?></textarea>
                                             </div>
                                             <hr class="my-2">
                                             <div class="row g-3">
                                                  <div class="col-12 col-md-6">
                                                       <label class="form-label">FTP <?= __l('host')?></label>
                                                       <input type="text" name="ftp_host" class="form-control"
                                                            value="<?= $arr['ftp_host']??'' ?>"
                                                            placeholder="ftp.example.com">
                                                  </div>
                                                  <div class="col-6 col-md-3">
                                                       <label class="form-label">FTP Port</label>
                                                       <input type="text" name="ftp_port" class="form-control"
                                                            value="<?= $arr['ftp_port']??'21' ?>">
                                                  </div>
                                                  <div class="col-6 col-md-3">
                                                       <label class="form-label">FTP SSL</label>
                                                       <select name="ftp_ssl" class="form-select">
                                                            <option value="false"
                                                                 <?= (($arr['ftp_ssl']??'false')==='false')?'selected':''; ?>>
                                                                 <?= __l('no')?></option>
                                                            <option value="true"
                                                                 <?= (($arr['ftp_ssl']??'false')==='true')?'selected':''; ?>>
                                                                 <?= __l('yes')?></option>
                                                       </select>
                                                  </div>
                                                  <div class="col-12 col-md-6">
                                                       <label class="form-label"><?= __l('username')?></label>
                                                       <input type="text" name="ftp_user" class="form-control"
                                                            value="<?= $arr['ftp_user']??'' ?>">
                                                  </div>
                                                  <div class="col-12 col-md-6">
                                                       <label class="form-label"><?= __l('password')?></label>
                                                       <?php $realPass=''; if(!empty($arr['ftp_pass'])) { $dec= decryptData($arr['ftp_pass']); if($dec!=='') $realPass=$dec; } ?>
                                                       <?php $hasPass = ($realPass !== ''); ?>
                                                       <div class="input-group">
                                                            <input type="password" name="ftp_pass" class="form-control"
                                                                 value="" placeholder="<?= $hasPass?'••••••':'' ?>"
                                                                 autocomplete="new-password">
                                                       </div>
                                                       <div class="form-text small">
                                                            <?= __l('pass_note') ?>
                                                       </div>
                                                  </div>
                                                  <div class="col-12 col-md-6">
                                                       <label class="form-label"><?= __l('ftp_folder')?></label>
                                                       <input type="text" name="ftp_folder" class="form-control"
                                                            value="<?= $arr['ftp_folder']??'' ?>"
                                                            placeholder="/public_html/">
                                                  </div>
                                                  <div class="col-12 col-md-6">
                                                       <label class="form-label">Site URL</label>
                                                       <input type="text" name="host_url" class="form-control"
                                                            value="<?= $arr['host_url']??'' ?>"
                                                            placeholder="https://example.com">
                                                  </div>
                                                  <div class="col-12 col-md-6">
                                                       <label class="form-label"><?= __l('down_folder')?></label>
                                                       <input type="text" name="download_folder" class="form-control"
                                                            value="<?= $arr['download_folder']??'' ?>"
                                                            placeholder="/download">
                                                  </div>
                                             </div>
                                        </div>
                                        <div class="modal-footer">
                                             <button class="btn btn-secondary"
                                                  data-bs-dismiss="modal"><?= __l('close') ?></button>
                                             <button type="submit" name="save_json" class="btn btn-success">
                                                  <i class="fa-solid fa-floppy-disk me-1"></i> <?= __l('save') ?>
                                             </button>
                                        </div>
                                   </form>
                              </div>
                         </div>
                    </div>

                    <!-- Modal: Toplu Upload -->
                    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
                         <div class="modal-dialog">
                              <form method="post">
                                   <div class="modal-content">
                                        <div class="modal-header">
                                             <h5 class="modal-title"><?= __l('upload_modal') ?>
                                                  (<?= htmlspecialchars($selectedProject) ?>)</h5>
                                             <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                             <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                             <input type="hidden" name="projName"
                                                  value="<?= htmlspecialchars($selectedProject) ?>">
                                             <div class="form-check form-switch mb-3">
                                                  <input class="form-check-input" type="checkbox" name="overwrite"
                                                       value="1" id="overwriteSwitch" checked>
                                                  <label class="form-check-label"
                                                       for="overwriteSwitch"><?= __l('upload_overwrite') ?></label>
                                             </div>
                                             <p class="mb-0"><?= __l('ftp_upload_info') ?></p>
                                        </div>
                                        <div class="modal-footer">
                                             <button class="btn btn-secondary"
                                                  data-bs-dismiss="modal"><?= __l('close') ?></button>
                                             <button type="submit" name="upload_project" class="btn btn-primary">
                                                  <i class="fa-solid fa-cloud-arrow-up me-1"></i>
                                                  <?= __l('upload_server') ?>
                                             </button>
                                        </div>
                                   </div>
                              </form>
                         </div>
                    </div>

                    <?php
        } else {
          echo '
          <div class="alert alert-light">'.__l('select_project').'</div>
          <div class="card shadow-sm my-3"><div class="card-body">
            <div class="mb-3">'.__l('proj_sub_title_1').'</div>
            <div class="mb-3">'.__l('proj_sub_title_2').'</div>
          </div></div>';
        }
        ?>
               </div>
          </div>

     </div>
</div>
<?= $side ?>
<?= get_footer(); ?>