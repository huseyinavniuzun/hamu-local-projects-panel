 <!-- [TR] Sidebar içeriği buraya eklenebilir; örn. ekstra servis linkleri -->
 <!-- [EN] Sidebar content can be added here; e.g., additional service links -->
    <div id="sidebarContent" class="sidebar d-none d-md-block" <?= (!empty($menu_type) && $menu_type != 0) ? 'style="display:none !important;"' : '' ?>>
        
        <div class="service-list">
				<!-- [TR] Sistemsel linkler, modüller hariç -->
				<!-- [EN] Link of Systems, without modules -->
			<?php if (!empty($menu_type)): ?><a href="/"><i class="fas fa-home"></i> <?= __l('home') ?></a><?php endif ?>
			<a href="/.hamu/about.php" alt="<?= __l('about_title') ?>" target="_self"><i class="fas fa-info-circle"></i>  <?= __l('about') ?></a>
			<a href="#" onclick="openSettingsModal()"><i class="fas fa-cog"></i> <?= __l('settings') ?></a>
			

			<div class="server-info hide">
			<h4><i class="fa-solid fa-toolbox"></i></i> <?= __l('services') ?></h4>
			<?php if ($config["file_manager_s"] && !empty($config["file_manager_s"])): ?><a href="<?= htmlspecialchars(jsonlink($config["file_manager_s_url"])) ?>"><i class="fas fa-folder"></i> <?= __l('file_manager') ?></a><?php endif; ?>
            <?php if ($config["phpmyadmin_s"] && !empty($config["phpmyadmin_s_url"])): ?><a href="<?= htmlspecialchars(jsonlink($config["phpmyadmin_s_url"])) ?>"><i class="fas fa-database"></i> PhpMyAdmin</a><?php endif; ?>
			<?php if ($config["mail_server_s"] && !empty($config["mail_server_s_url"])): ?><a href="<?= htmlspecialchars(jsonlink($config["mail_server_s_url"])) ?>"><i class="fa fa-envelope"></i> Mail <?= __l('host') ?></a><?php endif; ?>
			<?php if ($config["ftp_server_s"] && !empty($config["ftp_server_s_url"])): ?><a href="<?= htmlspecialchars(jsonlink($config["ftp_server_s_url"])) ?>"><i class="fa fa-server"></i> FTP <?= __l('host') ?></a><?php endif; ?>
			</div>
            </div>
		
		<?php if (!empty($config["modul_s"])): ?>
    		<!-- modules dizinindekiler -->
        <div class="server-info hide">
            <h4><i class="fa fa-cubes"></i> <?= __l('modules') ?></h4>
			<div class="service-list">
			 <?php foreach ($modules as $mod): ?>
                <a href="/.hamu/modules/<?= $mod['file'] ?>" target="_self">
                    <i class="fas fa-file-code"></i> <?= htmlspecialchars($mod['title']) ?>
                </a>
            <?php endforeach; ?>
			</div>
        </div>
		<?php endif; ?>


<!-- Veritabanı Bilgisi -->
<?php if (!empty($config["database_s"]) && (!isset($include_db) || $include_db != 0)) : ?>
  <div class="database-info">
            <?php if ($active_db): ?>
                <h4><i class="fas fa-database"></i> <?= htmlspecialchars($activeDBInfo['dbName'].' '.$activeDBInfo['version']) ?></h4>
                <ul id="databaseList" class="desktop-view" data-active-db="<?= htmlspecialchars($active_db) ?>">
                    <?php foreach ($databases as $db): ?>
                        <li class="db-name <?= ($active_db === $db) ? 'active-db' : '' ?>" data-db="<?= htmlspecialchars($db) ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?= __l('tableloading')?>">
                            <i class="fas fa-database"></i> <?= htmlspecialchars($db) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <h5><i class="fas fa-database"></i> <?= __l('server_down')?></h5>
            <?php endif; ?>
        </div>
		<?php endif; ?>

<!-- Sunucu Bilgisi -->
        <div class="server-info">
            <h4><i class="fas fa-server"></i> <?= __l('server_info_title') ?></h4>
            <p>
                <strong><?= __l('phphost')?>:</strong> <?= $webServerName . ' ' . $webServerVersion ?><br>
                <strong><?= __l('phpversion')?>:</strong> <?= $phpVersion ?><br>
				<strong><?= __l('server_name')?>:</strong> <?= $serverName ?><br>
				<strong><?= __l('host_name')?>:</strong> <?= $hostName ?>
            </p>
        </div>
    </div>