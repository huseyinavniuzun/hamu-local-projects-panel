<?php
$page_title		 = "file_manager";		  	  		  							 // Sayfa Başlığı
$body_class	 	 = "";			   			  							// <body> tagı css sitili
$include_db	 	 = 1;			   	  		 						   // 0 = hayır, 1 = evet
$menu_type		 = 1;												  // 0 = Sadece mobil ekranlarda gösteriliyor, 1 = Her ekranda gösteriliyor
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_header.php');	 // Header 
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';		// Navbar
?>


	<div class="container-module">
		<div class="content-middle file-manager">
				<iframe src="filemanager/dialog.php?type=0"></iframe>
				<div class="text-end"><img src="filemanager/img/logo.png" width="100"></div>
		</div>
	</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_footer.php';?>
