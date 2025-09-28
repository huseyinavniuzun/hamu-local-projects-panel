<?php  
//	h_navbar.php
//	[TR] Navigasyon menüsü
//	[EN] Navigation menu
?>
<nav class="navbar">
	<!-- [TR] Sol bölüm: Logo / [EN] Left section: Logo -->
	<div class="logo">
		<a href="/" alt="Hamu Local Server Panel">
			<?php $logoh = 35; require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/images/logo.php'; ?>
		</a>
	</div>
	<!-- [TR] Orta bölüm: Başlık / [EN] Center section: Title -->
	<div class="title">
		<span class="h4"><?= __l('about_app') ?></span>
	</div>
	<!-- [TR] Sağ bölüm: Dil & Dark Mode Seçimi (dark-lang-wrapper ile birlikte) / [EN] Right section: Language & Dark Mode Switchers -->
	<div class="right-section" id="langDarkSelectionContent">
		<div class="dark-lang-wrapper">
			<div class="dark-mode-icon"><i class="fa-solid fa-droplet"></i></div>
			<div class="dark-mode-select">
				<div class="form-check form-switch">
					<form id="themeForm">
						<input class="form-check-input" type="checkbox" id="mySwitch" name="darkmode" value="yes" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= __l('dark_theme') ?>" <?= config('theme_s') ? 'checked' : '' ?>>
					</form>
				</div>
			</div>
		</div>
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/lang_switcher.php'; ?>
	</div>
  <!-- Mobilde görünecek offcanvas butonu / Offcanvas toggle for mobile -->
  <button class="navbar-toggler <?= ($menu_type == 0 ) ? "d-block d-md-none" : ""; ?>" type="button"
          data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar"
          aria-controls="offcanvasSidebar" style="margin-left: 10px">
    <span class="navbar-toggler-icon"></span>
  </button>
</nav>

<!-- Mobil - Offcanvas Yanbar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
<div class="offcanvas-header" style="display: flex; justify-content: space-between; align-items: center;">
	<!-- [TR] Sol tarafta logo / [EN] Logo on the left -->
	<div class="offcanvas-logo">
		<span class="h3"><?= __l('menu') ?></span>
	</div>
	<!-- [TR] Sağ tarafta dil ve dark mode öğeleri ile kapat butonu, aralarında gap var / [EN] On the right side, language & dark mode elements and close button with a gap -->
	<div class="offcanvas-header-right" style="display: flex; align-items: center; gap: 10px;">
		<div id="langDarkSelectionOffcanvasContent"></div>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
</div>
  <div class="offcanvas-body">
    <div id="offcanvasSidebarContent"></div>
  </div>
</div>
<input type="hidden" id="langValue" value="<?php echo $lang; ?>">