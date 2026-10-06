<?php
/**
* HAMU DevPanel - Language
* Location: /.hamu/lang.php
* [TR] Çoklu dil ayarlarını içerir. Yeni bir dil eklemek için, $languages dizisine yeni bir anahtar (örneğin, 'FR') ekleyip o dile ait tüm metinleri tanımlamanız yeterlidir.
* [EN] Multi-language settings. To add a new language, simply add a new key (e.g., 'FR') to the $languages array and define all texts for that language.
*/
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }


$languages = [

	'EN' => [
// ===== [TR] SABİT TANIMLAR | [EN] CONSTANT DEFINITIONS =====
		'charset'								=> 'UTF-8',
		'lang_name'							=> 'English',
		'title_app'							=> 'Local Server - HAMU DevPanel',
		'about_app'							=> 'Project Panel',

// ===== [TR] TEMEL METİNLER & DURUM MESAJLARI | [EN] COMMON TEXTS & STATUS MESSAGES =====
		'yes'								=> 'Yes',
		'no'									=> 'No',
		'ok'									=> 'Ok',
		'cancel'								=> 'Cancel',
		'hide'								=> 'Hide',
		'unhide'								=> 'Unhide',
		'show'								=> 'Show',
		'new'								=> 'New',
		'empty'								=> 'Empty',
		'copy'								=> 'Copy',
		'active'								=> 'Active',
		'disable'								=> 'Disable',
		'run'								=> 'Run',
		'send'								=> 'Send',
		'use'								=> 'Use',
		'save'								=> 'Save',
		'close'								=> 'Close',
		'refresh'								=> 'Refresh',
		'search'								=> 'Search',
		'next'								=> 'Next',
		'previous'							=> 'Previous',
		'first'								=> 'First',
		'end'								=> 'End',
		'sure'								=> 'Are u sure?',
		'private' 							=> 'Private',
		'preview'								=> 'Preview',
		'username'							=> 'Username',
		'password'							=> 'Password',
		'project'								=> 'Project',
		'answer'								=> 'Answer:',
		'permission'							=> 'Permission',
		'loading'								=> 'Loading...',
		'disabled'							=> 'Disabled',
		'jsonSaved'							=> 'JSON saved!',
		'theme_select'							=> 'Selected Theme',
		'other'								=> 'Other',
		'light_theme'							=> 'Turn Off Dark Mode',
		'dark_theme'							=> 'Turn On Dark Mode',
		'theme_ok'							=> 'Theme changed on page',
		'language_select'						=> 'Language Select',
		'login'								=> 'Login',
		'logout' 								=> 'Logout',
		'login_ok'							=> 'Successful Login',
		'login_fail'							=> 'Failed Login Attempt',
		'csrf_error'							=> 'Invalid CSRF Token',
		'pass_error'							=> 'Wrong Password',
		'app_pass'							=> 'App Password',
		'ask_pass'							=> 'Use Password',
		'ask_2fa'								=> 'Use 2FA',
		'2fa_method'							=> '2FA Method',
		'backup_not_ack'						=> 'Backup Codes Not Saved',
		'disable_2fa_app'						=> 'Disable',
		'show_backup_codes'						=> 'Backup Codes',
		'disable_2fa_email'						=> 'Disable',
		'verify_failed_retry'					=> 'Verification failed. Please try again.',
		'email_2fa'							=> 'Email',
		'confirm_disable_2fa_email'				=> 'Confirm disabling two-factor authentication via email',
		'confirm_disable_2fa_app'				=> 'Confirm disabling two-factor authentication with the App',
		'i_copied_them_hide'					=> 'Save and Close',
		'backup_already_saved_msg'				=> 'Codes have been backed up. Create new codes by clicking the "Generate New Codes" button.',
		'regenerate_codes'						=> 'Generate New Codes',
		'not_verified'							=> 'Not Verified',
		'setup'								=> 'Setup',
		'resend_in'							=> 'Resend In',
		'check_your_email'						=> 'Check the code sent to your email.',
		'enter_2fa_code'						=> 'Enter 2FA Code',
		'code_expires_in'						=> 'Code expires in %s seconds',
		'use_auth_app'							=> 'Use Authenticator App',
		'start_setup'							=> 'Start Setup',
		'generate_backup_codes'					=> 'Generate Backup Codes',
		'backup_not_saved'						=> 'Backup codes not saved!',
		'confirm_disable_2fa'					=> 'Confirm Disable 2FA',
		'auth_app_ready'						=> 'Your authenticator app is ready!',
		'backup_codes'							=> 'Backup Codes',
		'email_address'						=> 'Email Address',
		'verify'								=> 'Verify',
		'verifying'							=> 'Verifying',
		'verified'							=> 'Verified',
		'send_code'							=> 'Send Code',
		'sending_code'							=> 'Code sending',
		'scan_qr_in_auth_app'					=> 'Scan the QR code in your authentication app',
		'code_sent'							=> 'Code sent to your email.',
		'linked'								=> 'App Linked',
		'backup_codes_hint'						=> 'Backup codes can be used when you cannot access your authenticator app or email. Each code can be used only once.',
		'save_backup_codes' 					=> 'Save Backup Codes',
		'backup_generate_done_save'				=> 'Backup codes generated. Save them in a secure place:',
		'send_failed'							=> 'Sending code failed.',
		'invalid_or_expired_code'				=> 'Invalid or expired code.',
		'enter_code'							=> 'Enter Code',
		'enter_auth_code'						=> 'Enter Verification Code',
		'scan_and_enter_code'					=> 'Scan the QR code and enter the verification code',
		'verify_and_save'						=> 'Verify',
		'cant_scan_qr_show_secret'				=> 'If you can\'t scan the QR, add the key manually:',
		'pass_entry'							=> 'Entry Password',
		'pass_nopass'							=> 'Type to set a password',
		'pass_changepass'						=> 'Type to change password',
		'session_timeout'						=> 'Session Timeout',
		'session_down'							=> 'Session Closed',
		'server_down'							=> 'Server is Down',
		'error_occurred'						=> 'An error occurred.',
		'error_reporting'						=> 'Error Reporting',
		'error'								=> 'Error:',
		'edits'								=> 'Edits:',
		'request_error'						=> 'Request error',
		'unexpected_response'					=> 'Unexpected response',
		'network_error'						=> 'Network error',
		'op_failed'							=> 'Operation failed',
		'gotoback'							=> 'Go Back',
		'backto_projects'						=> 'Back to Projects',
		'time_label'							=> 'Time',
		'install_secret'						=> 'Install Secret',
		'settings_desc'						=> 'Your %1$p files must be in %2$p',


// ===== [TR] ARAYÜZ & MENÜ BAŞLIKLARI | [EN] UI & MENU TITLES =====
		'local-panel'							=> 'Local Server - Panel',
		'home'								=> 'Homepage',
		'menu'								=> 'MENU',
		'about'								=> 'About',
		'readme'								=> 'Readme',
		'settings'							=> 'Settings',
		'settings_ok'							=> 'Settings saved.',
		'root'								=> 'Root',
		'working_root'							=> 'Working Root',
		'server_info_title'						=> 'Server Info',
		'server_name'							=> 'Domain Name',
		'host'								=> 'Server',
		'host_name'							=> 'Host Name',
		'server_time'							=> 'Server Time',
		'memory_limit'							=> 'Memory Limit',
		'web_server'							=> 'Web Server',
		'php_version'							=> 'PHP Version',
		'upload_limit'							=> 'Upload Limit',
		'file_manager'							=> 'File Manager',
		'include_tab'							=> 'Includes',
		'services'							=> 'Services',
		'modules'								=> 'Modules',
		'reader'								=> 'File Viewer',
		'local_projects'						=> 'Local Projects',
		'all_projects'							=> 'All Projects',
		'sessions_logs'						=> 'Session Logs',
		'sql_terminal_logs'						=> 'Terminal Error Logs',
		'upload_logs'							=> 'Upload Server Logs',
		'file'								=> 'File',
		'folder'								=> 'Folder',
		'size'								=> 'Size',
		'ops'								=> 'Ops',
		'delete'								=> 'Delete',
		'rename'								=> 'Rename',
		'download'							=> 'Download',
		'folder_up'							=> 'Up Folder',
		'up'									=> 'Up',
		'path_'								=> 'Path',
		'favorites'							=> 'Favorites',
		'add_favorite'							=> 'Add to Favorites',
		'rmv_favorite'							=> 'Remove from Favorites',
		'filters'								=> 'Filters',
		'sort'								=> 'Sorts',
		'to_name'								=> 'Name',
		'to_ctime'							=> 'Creation',
		'to_mtime'							=> 'Modification',
		'name_asc'							=> 'To Name (A → Z)',
		'name_desc'							=> 'To Name (Z → A)',
		'ctime_desc'							=> 'Creation Time (New → Old)',
		'ctime_asc'							=> 'Creation Time (Old → New)',
		'mtime_desc'							=> 'Recent Modification (New → Old)',
		'mtime_asc'							=> 'Recent Modification (Old → New)',
		'new_old'								=> 'New → Old',
		'old_new'								=> 'Old → New',
		'j_index'								=> 'Only Indexed',
		'show_hiddens'							=> 'Hidden Folders',
		'show_modules'							=> 'Show Modules',
		'favorite_update_success'				=> 'Favorite updated',
		'favorite_update_failed'					=> 'Failed to update favorite',
		'hide_failed'							=> 'Hide failed',
		'unhide_failed'						=> 'Unhide failed',
		'unknown_op'							=> 'Unknown operation',
		'invalid_op'							=> 'Invalid operation type',
		'show_perms'							=> 'Show Perms/Owner Columns',
		'folder_for'							=> '%s folder path.',
		'use_for'								=> 'Using for %s',
		'confirm_delete_perm'					=> 'Warning! Permanently delete "%s"?',
		'confirm_irreversible'					=> 'This action is irreversible. Are you sure?',
		'invalid_folder_name'					=> 'Invalid folder name',
		'folder_name'							=> 'Folder name',
		'file_name'							=> 'File Name',
		'invalid_name'							=> 'Invalid name. Only letters, digits, ".", "_" and "-" are allowed.',
		'file_here'							=> 'File is already here.',
		'no_folder_res'						=> 'Resource folder is not here',
		'no_folder'							=> 'Folder is not found',
		'rename_failed'						=> 'Rename failed',
		'delete_failed'						=> 'Delete failed',
		'delete_confirm_phrase'					=> 'PERMANENT DELETE',
		'pass_note' 							=> 'Password is not shown for security. Enter a new password to change it, otherwise the current one is kept.',
		'noindexfile'							=> 'No index file!',
		'rename_desc'							=> '(Changing the display name also changes the folder name)',
		'tags_desc'							=> 'Tags (separate with commas)',
		'folder_img_desc'						=> 'Click on the image to add/change photo<br>(recommended 150×150)',
		'badge_color'							=> 'Badge Color',
		'badge_text'							=> 'Badge Text',
		'general'								=> 'General',
		'cover_upload_failed'					=> 'Cover upload failed',
		'folder_deleted_permanent'				=> 'Folder permanently deleted',
		'protected_path'						=> 'Protected path',
		'rename_success'						=> 'Renamed',
		'already_hidden'						=> 'Already hidden',
		'already_visible'						=> 'Already visible',
		'hide_success'							=> 'Hidden',
		'unhide_success'						=> 'Unhidden',
		'ftp_info_saved'						=> 'FTP information is saved',


// ===== [TR] PROJE YÖNETİMİ & FTP | [EN] PROJECT MANAGEMENT & FTP =====
		'projects_title'					=> 'Project Operations',
		'proj_title'							=> 'Project Operations',
		'select_project'						=> 'Select a project from the list to get started.',
		'proj_sub_title_1'						=> 'A unified <strong>Local + FTP file manager</strong> for your projects.',
		'proj_sub_title_2'						=> '<div class="list-group list-group-flush">
													<div class="list-group-item d-flex align-items-start bg-transparent px-1">
														<i class="fa-solid fa-folder-open fa-fw text-primary me-3 mt-1 fs-5"></i>
														<div>
															<h6 class="mb-0">Local Files</h6>
															<small class="text-muted">Browse your project folder, manage files, and upload them to the server individually or in bulk.</small>
														</div>
													</div>
													<div class="list-group-item d-flex align-items-start bg-transparent px-1 mt-2">
														<i class="fa-solid fa-server fa-fw text-success me-3 mt-1 fs-5"></i>
														<div>
															<h6 class="mb-0">FTP Manager</h6>
															<small class="text-muted">Browse the remote server, manage files, and download them to your local "Download Folder".</small>
														</div>
													</div>
													<div class="list-group-item d-flex align-items-start bg-transparent px-1 mt-2">
														<i class="fa-solid fa-gear fa-fw text-warning me-3 mt-1 fs-5"></i>
														<div>
															<h6 class="mb-0">Settings & Logs</h6>
															<small class="text-muted">Configure project-specific FTP credentials and view operation logs. All settings are saved in a <code>.json</code> file inside your project folder.</small>
														</div>
													</div>
												</div>',
		'ftp_manager'							=> 'FTP Manager',
		'local_files'							=> 'Local Files',
		'upload_server'						=> 'Upload to Server',
		'ftp_upload_info'						=> 'This operation uploads the entire project folder to the server. Are you sure you want to proceed?',
		'upload_modal'							=> 'Upload to Server',
		'upload_overwrite'						=> 'Overwrite existing files?',
		'down_folder'							=> 'Download Folder',
		'ftp_folder'							=> 'FTP Folder',
		'description'							=> 'Description',
		'selected_proj'						=> 'Selected Project',
		'show_hidden'							=> 'Show Hidden Files',
		'ignore_folders'						=> 'Ignore Folders',
		'ok_json'								=> 'Setting file is avaiable, connectable!',
		'no_json'								=> 'Setting file not found!',
		'error_json'							=> 'Setting file is invalid or password is wrong!',
		'no_admin'							=> 'You do not have login authorization!',
		'missing_fields'						=> 'Error: Missing or invalid fields on settings!',
		'download_failed'						=> 'Download failed!',
		'downloaded_to_folder'					=> 'File downloaded to',
		'ftp_error'							=> 'FTP Connect Error',
		'ftp_login_err'						=> 'FTP Login Error',
		'ftp_error_up'							=> 'FTP file upload failed.',
		'upload_ok_single'						=> 'Single File Upload Successful',
		'upload_fail_single'					=> 'Single File Upload Failed.',
		'ftp_error_pass_empty'   				=> 'Password not saved. Please enter and save the FTP password in the settings.',
		'ftp_error_connect_host' 				=> 'Could not connect to the server. The server name or port may be incorrect or unreachable.',
		'ftp_error_ssl_connect'  				=> 'Could not establish FTPS (SSL/TLS) connection. The server may not support FTPS, or the certificate/port is incorrect.',
		'ftp_error_login'        				=> 'FTP username or password incorrect.',
		'ftp_error_pasv'         				=> 'Could not switch to PASV mode. Check firewall or NAT settings.',
		'file_not_found'						=> 'File not found.',
		'ftp_err1'							=> 'The Php FTP extension is not effective on the server. Please enable "extension = ftp"  in php.ini file.',
		'ftp_err2'							=> 'FTPS (SSL/TLS) cannot be used. "Extension = OpenSSL" is not effective or "ftp_ssl_connect" is not supported.',
		'ftp_err3'							=> 'You can select "FTP SSL: No" or enable the OpenSSL plug -in.',
		'pass_desc'							=> 'Password (if you leave it blank, the password will not change)',
		'project_settings'						=> 'Project Settings',
		'json_parse_err'						=> 'JSON Parse Error',
		'ajax_err'							=> 'AJAX Error',


// ===== [TR] VERİTABANI & SQL TERMİNALİ | [EN] DATABASE & SQL TERMINAL =====
		'database_server'						=> 'Server',
		'db_info_title'						=> 'Database Info',
		'db_driver'							=> 'Database Driver',
		'use_database'							=> 'Use Database',
		'no_database'							=> 'No Database Selected',
		'sql_terminal'							=> 'SQL Terminal',
		'sql_terminal_placeholder_disabled'		=> 'SQL Mini Terminal (DB Off)',
		'sql_terminal_placeholder_enabled'			=> 'SQL Mini Terminal...',
		'run_query'							=> 'Running query...',
		'tables'								=> 'Tables',
		'views'								=> 'Views',
		'functions'							=> 'Functions',
		'procedures'							=> 'Procedures',
		'notable'								=> 'No tables/views/procedures/functions in this database',
		'tableloading'							=> 'Tables are loading..',
		'tableload_failed'						=> 'Tables could not be loaded.',
		'tableload_success'						=> 'Tables loaded successfully.',
		'testphpmodule'						=> 'Here you can try PHP code (eval). It is safe for local use.',
		'runcode'								=> 'Code running',
		'error_could_not_run'					=> 'Error: Could not run',
		'sql_spam'							=> 'This query was sent too quickly.',
		'no_active_db_type'						=> 'Active database type could not be determined (server may be down).',
		'db_changed'							=> 'Database changed: %s',
		'db_not_found_fallback'					=> "Error: Database '%s' not found. Falling back...",
		'db_fallback'							=> 'Fallback database: %s',
		'no_database_found'						=> 'No database found.',
		'db_already_exists'						=> 'Database already exists: %s',
		'db_created'							=> 'Database created: %s',
		'create_db_not_supported'				=> 'CREATE DATABASE is not supported for this driver.',
		'db_not_exists'						=> "Error: Database '%s' does not exist.",
		'db_dropped'							=> 'Database dropped: %s',
		'drop_db_not_supported'					=> 'DROP DATABASE is not supported for this driver.',
		'db_auto_fallback'						=> "Automatically switched to database '%s'.",
		'db_list_update_failed'					=> 'Failed to update database list.',
		'no_db_session_closed'					=> 'No database available; session closed.',
		'empty_query'							=> 'Empty query',
		'no_db_selected'						=> 'You must select a database first (USE dbName).',
		'noconn'								=> 'No connection (server may be down).',
		'zero_results'							=> '0 results',
		'query_success'						=> 'Query successful (Affected rows: %d)',
		'query_error'							=> 'Error: %s',
		'db_not_selected'						=> 'No database selected.',
		'server_or_driver_not_found'				=> 'Server is down or driver not found.',
		'db_selected'							=> "Database selected: %s",
		'db_created_selected'					=> "Database created and selected: %s",
		'used_explicit_db'						=> "Used explicit: %s",
		'used_session_db'						=> "Used from session: %s",
		'table_created'						=> "Table created: %s",
		'table_dropped'						=> "Table dropped: %s",
		'view_created'							=> "View created: %s",
		'view_dropped'							=> "View dropped: %s",
		'proc_created'							=> "Procedure created: %s",
		'proc_dropped'							=> "Procedure dropped: %s",
		'func_created'							=> "Function created: %s",
		'func_dropped'							=> "Function dropped: %s",
		'proc_func_not_supported'				=> 'Stored Procedure/Function queries are not supported in the terminal.',
		'db_manager'							=> 'Database Manager',
		'terminal_desc'						=> '<b>Usage:</b><br>The Enter key provides direct access to the terminal and runs the command.<br>Typed commands are auto-completed and Tab switches between commands.<br><b>History:</b> Shift+Up/Down Arrow keys show previously typed queries.',
	],

		'TR' => [
// ===== [TR] SABİT TANIMLAR | [EN] CONSTANT DEFINITIONS =====
		'charset'								=> 'UTF-8',
		'lang_name'							=> 'Türkçe',
		'title_app'							=> 'Yerel Sunucu - HAMU DevPanel',
		'about_app'							=> 'Proje Paneli',

// ===== [TR] TEMEL METİNLER & DURUM MESAJLARI | [EN] COMMON TEXTS & STATUS MESSAGES =====
		'yes'								=> 'Evet',
		'no'									=> 'Hayır',
		'ok'									=> 'Tamam',
		'cancel'								=> 'İptal',
		'hide'								=> 'Gizle',
		'unhide'								=> 'Göster',
		'show'								=> 'Göster',
		'new'								=> 'Yeni',
		'empty'								=> 'Boş',
		'copy'								=> 'Kopyala',
		'active'								=> 'Aktif',
		'run'								=> 'Çalıştır',
		'send'								=> 'Gönder',
		'use'								=> 'Kullan',
		'save'								=> 'Kaydet',
		'close'								=> 'Kapat',
		'refresh'								=> 'Yenile',
		'disable'								=> 'Devre Dışı Bırak',
		'search'								=> 'Ara',
		'next'								=> 'Sonraki',
		'previous'							=> 'Önceki',
		'first'								=> 'İlk',
		'end'								=> 'Son',
		'sure'								=> 'Emin misiniz?',
		'preview'								=> 'Önizleme',
		'username'							=> 'Kullanıcı Adı',
		'password'							=> 'Şifre',
		'private' 							=> 'Özel',
		'project'								=> 'Proje',
		'answer'								=> 'Cevap :',
		'permission'							=> 'İzinler',
		'other'								=> 'Diğer',
		'loading'								=> 'Yükleniyor...',
		'disabled'							=> 'Devre Dışı',
		'jsonSaved'							=> 'JSON kaydedildi!',
		'theme_select'							=> 'Seçilen Renk Modu',
		'light_theme'							=> 'Koyu Modu Kapat',
		'dark_theme'							=> 'Koyu Modu Aç',
		'theme_ok'							=> 'Tema sayfada değiştirildi',
		'language_select'						=> 'Dil Seçimi',
		'login'								=> 'Giriş',
		'logout'								=> 'Çıkış',
		'login_ok'							=> 'Başarılı Giriş',
		'login_fail'							=> 'Başarısız Giriş Denemesi',
		'csrf_error'							=> 'Geçersiz CSRF Token',
		'pass_error'							=> 'Hatalı Şifre',
		'app_pass'							=> 'Uygulama Şifresi',
		'ask_pass'							=> 'Şifre Kullan',
		'ask_2fa'								=> '2FA Kullan',
		'2fa_method'							=> '2FA Metodu',
		'save_backup_codes' 					=> 'Yedek Kodları Kaydet',
		'enter_2fa_code'						=> 'Doğrulama Kodunu Girin',
		'code_expires_in'						=> 'Kod %s saniye içinde geçersiz olacak',
		'use_auth_app'							=> 'Uygulama',
		'start_setup'							=> 'Kurulumu Başlat',
		'generate_backup_codes'					=> 'Yedek Kodlar Oluştur',
		'backup_not_saved'						=> 'Yedek kodlar kaydedilmedi!',
		'confirm_disable_2fa'					=> 'Çift Faktörlü Doğrulamayı Devre Dışı Bırakmayı Onayla',
		'auth_app_ready'						=> 'Kimlik doğrulayıcı uygulamanız hazır!',
		'backup_not_ack'						=> 'Yedek Kodlar Kaydedilmedi',
		'backup_saved'						     => 'Yedek Kodlar Kaydedildi',
		'disable_2fa_app'						=> 'Devre Dışı Bırak',
		'show_backup_codes'						=> 'Yedek Kodlar',
		'disable_2fa_email'						=> 'Devre dışı bırak',
		'verify_failed_retry'					=> 'Doğrulama başarısız. Lütfen tekrar deneyin.',
		'email_2fa'							=> 'Email',
		'confirm_disable_2fa_email'				=> 'Email ile çift faktörlü doğrulamayı devre dışı bırakmayı onayla',
		'confirm_disable_2fa_app' 				=> 'Uygulama İle çift faktörlü doğrulamayı devre dışı bırakmayı onayla',
		'i_copied_them_hide'					=> 'Kaydet ve Kapat',
		'backup_already_saved_msg'				=> 'Kodlar yedeklendi. "Yeni Kodlar Oluştur" düğmesine tıklayarak yeni kodlar oluştur.',
		'regenerate_codes'						=> 'Yeni Kodlar Oluştur',
		'not_verified'							=> 'Doğrulanmadı',
		'setup'								=> 'Kurulum',
		'resend_in'							=> 'Yeniden Gönder',
		'check_your_email'						=> 'Mailinize gelen kodu kontrol edin.',
		'backup_codes'							=> 'Yedek Kodlar',
		'verify'								=> 'Doğrula',
		'verifying'							=> 'Doğrulanıyor',
		'verified'							=> 'Doğrulandı',
		'send_code'							=> 'Kod Gönder',
		'sending_code'							=> 'Kod gönderiliyor',
		'scan_qr_in_auth_app'					=> 'Kimlik doğrulayıcı uygulamanızda QR kodunu tarayın',
		'code_sent'							=> 'Kod emailinize gönderildi.',
		'linked'								=> 'Uygulama aktif',
		'backup_codes_hint'						=> 'Yedek kodlar, kimlik doğrulayıcı uygulamanıza erişemediğinizde kullanılabilir. Her kod yalnızca bir kez kullanılabilir.',
		'backup_generate_done_save'				=> 'Yedek kodlar oluşturuldu. Güvenli bir yere kaydedin:',
		'send_failed'							=> 'Kod gönderme başarısız.',
		'invalid_or_expired_code'				=> 'Geçersiz veya süresi dolmuş kod.',
		'enter_code'							=> 'Kodu Girin',
		'enter_auth_code'						=> 'Doğrulama kodu',
		'scan_and_enter_code'					=> 'QR okutup kodu girin',
		'verify_and_save'						=> 'Doğrula',
		'cant_scan_qr_show_secret'				=> 'QR tarayamıyorsanız, anahtarı manuel olarak ekleyin:',
		'pass_nopass'							=> 'Şifre oluşturmak için şifrenizi girin',
		'pass_entry'							=> 'Giriş Şifresi',
		'pass_changepass'						=> 'Mevcut şifreyi değiştirmek için şifre girin',
		'session_timeout'						=> 'Oturum Zaman Aşımı',
		'session_down'							=> 'Oturum Kapatıldı',
		'server_down'							=> 'Sunucu Kapalı',
		'error_occurred'						=> 'Bir hata oluştu.',
		'error_reporting'						=> 'Hata Raporlama',
		'error'								=> 'Hata :',
		'edits'								=> 'Düzenlemeler:',
		'request_error'						=> 'İstek hatası',
		'unexpected_response'					=> 'Beklenmeyen yanıt',
		'network_error'						=> 'Ağ hatası',
		'op_failed'							=> 'İşlem başarısız',
		'gotoback'							=> 'Geri Dön',
		'backto_projects'						=> 'Projelere Dön',
		'time_label'							=> 'Zaman',
		'email_address'						=> 'Email Adresi',
		'install_secret'						=> 'Gizli Key',
		'settings_desc'						=> '%1$p dosyalarınız %2$p içinde olmalı.',


// ===== [TR] ARAYÜZ & MENÜ BAŞLIKLARI | [EN] UI & MENU TITLES =====
		'local-panel'							=> 'Yerel Sunucu - Panel',
		'home'								=> 'Anasayfa',
		'menu'								=> 'MENÜ',
		'about'								=> 'Hakkında',
		'readme'								=> 'Dökümanlar',
		'settings'							=> 'Ayarlar',
		'settings_ok'							=> 'Ayarlar kaydedildi.',
		'root'								=> 'Ana Dizin',
		'working_root'							=> 'Çalışma Dizini',
		'server_info_title'						=> 'Sunucu Bilgisi',
		'server_name'							=> 'Alan Adı',
		'host'								=> 'Sunucu',
		'host_name'							=> 'Sunucu Adı',
		'server_time'							=> 'Sunucu Zamanı',
		'memory_limit'							=> 'Hafıza Limiti',
		'upload_limit'							=> 'Yükleme Limiti',
		'web_server'							=> 'Web Sunucusu',
		'php_version'							=> 'PHP Versiyon',
		'include_tab'							=> 'Dahil Edilenler',
		'file_manager'							=> 'Dosya Yöneticisi',
		'services'							=> 'Servisler',
		'modules'								=> 'Modüller',
		'reader'								=> 'Dosya Görüntüleyici',
		'local_projects'						=> 'Yerel Projeler',
		'all_projects'							=> 'Tüm Projeler',
		'sessions_logs'						=> 'Oturum Kayıtları',
		'sql_terminal_logs'						=> 'Terminal Hata Kayıtları',
		'upload_logs'							=> 'Sunucu Yükleme Kayıtları',
		'file'								=> 'Dosya',
		'folder'								=> 'Klasör',
		'size'								=> 'Boyut',
		'ops'								=> 'İşlemler',
		'delete'								=> 'Sil',
		'rename'								=> 'Yeniden Adlandır',
		'download'							=> 'İndir',
		'folder_up'							=> 'Üst Klasör',
		'up'									=> 'Yukarı',
		'path_'								=> 'Dizin',
		'favorites'							=> 'Favoriler',
		'add_favorite'							=> 'Favorilere Ekle',
		'rmv_favorite'							=> 'Favorilerden Kaldır',
		'filters'								=> 'Filtreler',
		'sort'								=> 'Sıralama',
		'to_name'								=> 'Ada Göre',
		'to_ctime'							=> 'Oluşturma',
		'to_mtime'							=> 'Değiştirme',
		'name_asc'							=> 'Ada göre (A → Z)',
		'name_desc'							=> 'Ada göre (Z → A)',
		'ctime_desc'							=> 'Oluşturma tarihi (Yeni → Eski)',
		'ctime_asc'							=> 'Oluşturma tarihi (Eski → Yeni)',
		'mtime_desc'							=> 'Değiştirme tarihi (Yeni → Eski)',
		'mtime_asc'							=> 'Değiştirme tarihi (Eski → Yeni)',
		'new_old'								=> 'Yeni → Eski',
		'old_new'								=> 'Eski → Yeni',
		'j_index'								=> 'İndex olanlar',
		'show_hiddens'							=> 'Gizli Klasörler',
		'show_modules'							=> 'Modülleri Göster',
		'favorite_update_success'				=> 'Favori güncellendi',
		'favorite_update_failed'					=> 'Favori güncellenemedi',
		'hide_failed'							=> 'Gizleme başarısız',
		'unhide_failed'						=> 'Gösterme başarısız',
		'unknown_op'							=> 'Bilinmeyen işlem',
		'show_perms'							=> 'Yetki/Sahip Bilgilerini Göster',
		'invalid_op'							=> 'Geçersiz işlem türü',
		'confirm_delete_perm'					=> 'Dikkat! "%s" kalıcı olarak silinsin mi?',
		'folder_for'							=> '%s klasörü konumu.',
		'use_for'								=> '%s için kullanılır',
		'confirm_irreversible'					=> 'Bu işlem geri alınamaz. Emin misiniz?',
		'invalid_folder_name'					=> 'Geçersiz klasör adı',
		'folder_name'							=> 'Klasör adı',
		'file_name'							=> 'Dosya adı',
		'file_here'							=> 'Hedef zaten mevcut.',
		'no_folder_res'						=> 'Kaynak klasör yok',
		'no_folder'							=> 'Klaör bulunamadı',
		'invalid_name'							=> 'Geçersiz ad. Sadece harf, rakam, ".", "_" ve "-" kullanılabilir.',
		'pass_desc'							=> 'Parola (boş bırakırsanız parola değişmez)',
		'rename_failed'						=> 'Yeniden adlandırma başarısız',
		'delete_failed'						=> 'Silme başarısız',
		'delete_confirm_phrase'					=> 'KALICI SİL',
		'noindexfile'							=> 'İndex dosyası yok!',
		'rename_desc'							=> '(Görünen adı değiştirdiğinizde klasör adı da değiştirilir)',
		'tags_desc'							=> 'Etiketler (virgül ile ayırın)',
		'folder_img_desc'						=> 'Eklemek ya da değiştirmek için fotoğrafa tıklayın<br>(150×150 önerilir)',
		'badge_color'							=> 'Rozet Rengi',
		'badge_text'							=> 'Rozet Metni',
		'general'								=> 'Genel',
		'cover_upload_failed'					=> 'Kapak yüklenemedi',
		'folder_deleted_permanent'				=> 'Klasör kalıcı olarak silindi',
		'protected_path'						=> 'Korumalı yol',
		'rename_success'						=> 'Yeniden adlandırıldı',
		'already_hidden'						=> 'Zaten gizli',
		'already_visible'						=> 'Zaten görünür',
		'hide_success'							=> 'Klasör gizlendi',
		'unhide_success'						=> 'Gizlilik kaldırıldı',
		'ftp_info_saved'						=> 'FTP bilgiler kaydedildi',


// ===== [TR] PROJE YÖNETİMİ & FTP | [EN] PROJECT MANAGEMENT & FTP =====
		'projects_title'				 	=> 'Proje Yönetimi',
		'proj_title'							=> 'Proje İşlemleri',
		'select_project'						=> 'Başlamak için listeden bir proje seçin.',
		'proj_sub_title_1'						=> 'Projeleriniz için birleşik bir <strong>Yerel + FTP dosya yöneticisi</strong>.',
		'proj_sub_title_2'						=> '<div class="list-group list-group-flush">
													<div class="list-group-item d-flex align-items-start bg-transparent px-1">
														<i class="fa-solid fa-folder-open fa-fw text-primary me-3 mt-1 fs-5"></i>
														<div>
															<h6 class="mb-0">Yerel Dosyalar</h6>
															<small class="text-muted">Proje klasörünüze göz atın, dosyaları yönetin ve sunucuya tek tek veya toplu olarak yükleyin.</small>
														</div>
													</div>
													<div class="list-group-item d-flex align-items-start bg-transparent px-1 mt-2">
														<i class="fa-solid fa-server fa-fw text-success me-3 mt-1 fs-5"></i>
														<div>
															<h6 class="mb-0">FTP Yöneticisi</h6>
															<small class="text-muted">Uzak sunucuya göz atın, dosyaları yönetin ve yerel "İndirme Klasörünüze" indirin.</small>
														</div>
													</div>
													<div class="list-group-item d-flex align-items-start bg-transparent px-1 mt-2">
														<i class="fa-solid fa-gear fa-fw text-warning me-3 mt-1 fs-5"></i>
														<div>
															<h6 class="mb-0">Ayarlar & Kayıtlar</h6>
															<small class="text-muted">Projeye özel FTP kimlik bilgilerini yapılandırın ve işlem kayıtlarını görüntüleyin. Tüm ayarlar proje klasörünüzdeki bir <code>.json</code> dosyasına kaydedilir.</small>
														</div>
													</div>
												</div>',
		'ftp_manager'							=> 'FTP Yönetici',
		'local_files'							=> 'Yerel Dosyalar',
		'upload_server'						=> 'Sunucuya Yükle',
		'ftp_upload_info'						=> 'Bu işlem tüm proje klasörünü sunucuya aktarır. Devam etmek istediğinize emin misiniz?',
		'upload_modal'							=> 'Sunucuya Yükle',
		'upload_overwrite'						=> 'Var olan dosyaların üzerine yazılsın mı?',
		'down_folder'							=> 'İndirme Klasörü',
		'ftp_folder'							=> 'FTP Klasörü',
		'description'							=> 'Açıklama',
		'selected_proj'						=> 'Seçili Proje',
		'show_hidden'							=> 'Gizli Dosyaları Göster',
		'ignore_folders'						=> 'Klasörleri Gizle',
		'ok_json'								=> 'Ayar dosyası bulundu, bağlanılabilir.!',
		'no_json'								=> 'Ayar dosyası bulunamadı!',
		'error_json'							=> 'Ayar dosyası hatalı yada bozuk!',
		'no_admin'							=> 'Giriş yetkiniz yok!',
		'missing_fields'						=> 'Hata: Ayarlarda eksik yada hatalı alanlar!',
		'download_failed'						=> 'İndirme başarısız!',
		'downloaded_to_folder'					=> 'Dosya indirildi, indirilen klasör:',
		'ftp_error'							=> 'FTP Bağlantı Hatası',
		'ftp_login_err'						=> 'FTP Giriş Hatası',
		'ftp_error_up'							=> 'FTP dosya yüklemesi başarısız.',
		'upload_ok_single'						=> 'Tek dosya yükleme başarılı',
		'upload_fail_single'					=> 'Tek dosya yüklenemedi.',
		'ftp_error_pass_empty'   				=> 'Parola kaydedilmemiş. Ayarlardan FTP şifresini girip kaydedin.',
		'ftp_error_connect_host' 				=> 'Sunucuya bağlanılamadı. Sunucu adı veya portu hatalı ya da erişilemiyor.',
		'ftp_error_ssl_connect'  				=> 'FTPS (SSL/TLS) bağlantısı kurulamadı. Sunucu FTPS desteklemiyor olabilir veya sertifika/port hatalı.',
		'ftp_error_login'        				=> 'FTP kullanıcı adı veya parola hatalı.',
		'ftp_error_pasv'         				=> 'PASV moduna geçilemedi. Güvenlik duvarı veya NAT ayarlarını kontrol edin.',
		'file_not_found'						=> 'Dosya bulunamıyor.',
		'ftp_err1'							=> 'Sunucuda PHP FTP uzantısı etkin değil. Lütfen php.ini içinde "extension=ftp" etkinleştirin.',
		'ftp_err2'							=> 'FTPS (SSL/TLS) kullanılamıyor. "extension=openssl" etkin değil ya da "ftp_ssl_connect" desteklenmiyor.',
		'ftp_err3'							=> 'Ayarlardan "FTP SSL: No" seçebilir veya OpenSSL eklentisini etkinleştirebilirsiniz.',
		'project_settings'						=> 'Proje Ayarları',
		'pass_note' 							=> 'Güvenlik nedeniyle parola gösterilmez. Değiştirmek için yeni parola girin, aksi halde mevcut parola korunur.',
		'json_parse_err'						=> 'JSON Ayrıştırma Hatası',
		'ajax_err'							=> 'AJAX Hatası',


// ===== [TR] VERİTABANI & SQL TERMİNALİ | [EN] DATABASE & SQL TERMINAL =====
		'database_server'						=> 'Sunucu',
		'db_info_title'						=> 'Veritabanı Bilgisi',
		'db_driver'							=> 'Veritabanı Sürücüsü',
		'use_database'							=> 'Veritabanı Kullan',
		'no_database'							=> 'Veritabanı Kapalı',
		'sql_terminal'							=> 'SQL Terminali',
		'sql_terminal_placeholder_disabled'		=> 'SQL Mini Terminal (Veritabanı Kapalı)',
		'sql_terminal_placeholder_enabled'			=> 'SQL Mini Terminal...',
		'run_query'							=> 'Sorgu çalıştırılıyor...',
		'tables'								=> 'Tablolar',
		'views'								=> 'Görünümler',
		'functions'							=> 'Fonksiyonlar',
		'procedures'							=> 'Yordamlar',
		'notable'								=> 'Bu veritabanında tablo/görünüm/yordam/fonksiyon yok',
		'tableloading'							=> 'Tablolar yükleniyor.',
		'tableload_failed'						=> 'Tablolar yüklenemedi.',
		'tableload_success'						=> 'Tablolar başarıyla yüklendi.',
		'testphpmodule'						=> 'Burada PHP kodu deneyebilirsiniz (eval). Lokal kullanım için güvenlidir.',
		'runcode'								=> 'Kod çalıştırılıyor',
		'error_could_not_run'					=> 'Hata: Kod çalıştırılamadı',
		'sql_spam'							=> 'Bu sorgu çok hızlı tekrar gönderildi.',
		'no_active_db_type'						=> 'Aktif veritabanı türü belirlenemedi (sunucu kapalı olabilir).',
		'db_changed'							=> 'Veritabanı değişti: %s',
		'db_not_found_fallback'					=> "Hata: Veritabanı '%s' yok. Fallback'e geçiliyor...",
		'db_fallback'							=> 'Son veritabanı: %s',
		'no_database_found'						=> 'Hiç veritabanı bulunamadı.',
		'db_already_exists'						=> 'Veritabanı zaten var: %s',
		'db_created'							=> 'Veritabanı oluşturuldu: %s',
		'create_db_not_supported'				=> 'CREATE DATABASE bu sürücüde desteklenmiyor.',
		'db_not_exists'						=> "Hata: '%s' veritabanı yok.",
		'db_dropped'							=> 'Veritabanı silindi: %s',
		'drop_db_not_supported'					=> 'DROP DATABASE bu sürücüde desteklenmiyor.',
		'db_auto_fallback'						=> "Otomatik olarak '%s' veritabanına geçildi.",
		'db_list_update_failed'					=> 'Veritabanı listesi güncellenemedi.',
		'no_db_session_closed'					=> 'Hiç veritabanı yok; session kapatıldı.',
		'empty_query'							=> 'Boş sorgu',
		'no_db_selected'						=> 'Önce bir veritabanı seçmelisiniz (USE dbName).',
		'noconn'								=> 'Bağlantı yok (sunucu kapalı olabilir).',
		'zero_results'							=> '0 sonuç',
		'query_success'						=> 'Sorgu başarılı (Etkilenen satır: %d)',
		'query_error'							=> 'Hata: %s',
		'db_not_selected'						=> 'Veritabanı seçilmedi.',
		'server_or_driver_not_found'				=> 'Sunucu kapalı veya sürücü bulunamadı.',
		'db_selected'							=> "Veritabanı seçildi: %s",
		'db_created_selected'					=> "Veritabanı oluşturuldu ve seçildi: %s",
		'used_explicit_db'						=> "Explicit olarak kullanıldı: %s",
		'used_session_db'						=> "Session'dan kullanıldı: %s",
		'table_created'						=> "Tablo oluşturuldu: %s",
		'table_dropped'						=> "Tablo silindi: %s",
		'view_created'							=> "View oluşturuldu: %s",
		'view_dropped'							=> "View silindi: %s",
		'proc_created'							=> "Prosedür oluşturuldu: %s",
		'proc_dropped'							=> "Prosedür silindi: %s",
		'func_created'							=> "Fonksiyon oluşturuldu: %s",
		'func_dropped'							=> "Fonksiyon silindi: %s",
		'proc_func_not_supported'				=> 'Stored Procedure/Fonksiyon sorguları terminalde desteklenmiyor.',
		'db_manager'							=> 'DB Yöneticisi',
		'terminal_desc'						=> '<b>Kullanım:</b><br>Enter tuşu terminale direk erişim sağlar ve komutu çalıştırır.<br>Yazılan komutlar otomatik tamamlanır ve Tab komutlar arasında geçiş yapar.<br><b>Geçmiş:</b> Shift+Yukarı/Aşağı Ok tuşları yazılmış eski sorguları gösterir.',
	]

];

$lang = strtoupper($_GET['lang'] ?? ($_SESSION['hamu_lang'] ?? 'EN'));
if (!isset($languages[$lang])) { $lang = 'EN'; }

/* ---------------------------------------------------------
 * i18n helpers (PHP): __l(), _fmt(), J(), get_used_lang_array()
 * - __l(): Dil anahtarı çevirisi + kullanılan anahtar havuzu
 * - _fmt(): Düz metin biçimlendirici (used_lang_keys'i kirletmez)
 * - J(): JSON yanıt (anahtar/düz metni otomatik ayırır)
 * - get_used_lang_array(): kullanılan + zorunlu anahtarları döndürür (eksikleri placeholder ile doldurur)
 * --------------------------------------------------------- */

/* Kullanılan anahtar havuzu */
$used_lang_keys = $used_lang_keys ?? [];

/* __l(): Çevirici (tek otorite) */
if (!function_exists('__l')) {
    function __l($key, ...$params) {
        global $languages, $lang, $used_lang_keys;

        // Havuz güvenliği
        if (!is_array($used_lang_keys)) { $used_lang_keys = []; }
        if (!in_array($key, $used_lang_keys, true)) { $used_lang_keys[] = $key; }

        // Çeviri ya da placeholder
        $text = $languages[$lang][$key] ?? $key;
        if (!$params) { return $text; }

        // 1) printf tarzı: %s, %1$s ...
        if (preg_match('/%(\d+\$)?s/', $text)) {
            return @vsprintf($text, $params);
        }

        // 2) %p, %1$p, %2$p ... desteği
        $iParams = array_values($params);
        $text = preg_replace_callback('/%(\d+\$)?p/', function($m) use ($iParams) {
            if (!empty($m[1])) { // ör: %2$p
                $idx = max(((int) rtrim($m[1], '$')) - 1, 0);
                return array_key_exists($idx, $iParams) ? (string)$iParams[$idx] : '';
            }
            return isset($iParams[0]) ? (string)$iParams[0] : '';
        }, $text);

        return $text;
    }
}

/* _fmt(): __l()’e dokunmadan düz metin biçimlendirici */
if (!function_exists('_fmt')) {
    function _fmt(string $text, array $params) {
        if (!$params) return $text;

        // printf tarzı: %s, %1$s ...
        if (preg_match('/%(\d+\$)?s/', $text)) {
            return @vsprintf($text, $params);
        }

        // %p, %1$p, %2$p ...
        $iParams = array_values($params);
        return preg_replace_callback('/%(\d+\$)?p/', function($m) use ($iParams) {
            if (!empty($m[1])) { // %2$p
                $idx = max(((int) rtrim($m[1], '$')) - 1, 0);
                return array_key_exists($idx, $iParams) ? (string)$iParams[$idx] : '';
            }
            return isset($iParams[0]) ? (string)$iParams[0] : '';
        }, $text);
    }
}

/* J(): JSON yanıt (ok/msg + success/message; lang key ise otomatik çeviri)
   Geriye dönük uyumluluk:
   - Eski: J(true, 'key', ['x'=>1], 200)
   - Yeni kısayol: J('key', 'CSS')  // ok=true varsayılan
   - Çoklu param: J('key', ['A','B'])  // %1$p, %2$p veya %1$s, %2$s ile
   - Eski extra içinde param: J(true, 'key', ['_params'=>['A','B']])
*/
if (!function_exists('J')) {
    function J($ok_or_key, $msg_or_param = null, array $extra = [], ?int $status = null): void {
        global $languages, $lang;

        // Parametreleri normalize et
        if (is_bool($ok_or_key)) {
            // Eski mod: (bool $ok, $msg, array $extra, ?int $status)
            $ok     = $ok_or_key;
            $msg    = $msg_or_param;
            $params = [];
            if (isset($extra['_params'])) {
                $params = is_array($extra['_params']) ? $extra['_params'] : [$extra['_params']];
                unset($extra['_params']);
            }
        } else {
            // Yeni kısayol: ($msgKey, $paramsOrScalar = null, array $extra = [], ?int $status = null)
            $ok  = true;
            $msg = $ok_or_key;
            if (is_array($msg_or_param)) {
                $params = $msg_or_param;
            } elseif ($msg_or_param !== null) {
                $params = [$msg_or_param];
            } else {
                $params = [];
            }
        }

        // Anahtar mı? (array_key_exists ile net ayırım)
        $isKey = is_string($msg) && isset($languages[$lang]) && array_key_exists($msg, $languages[$lang]);

        // Anahtar ise __l() (havuzu günceller), değilse _fmt() (havuzu kirletmez)
        $translated = $isKey ? __l($msg, ...$params) : (is_string($msg) ? _fmt($msg, $params) : $msg);

        if ($status === null) { $status = $ok ? 200 : 400; }
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode(
            array_merge([
                'ok'      => $ok,
                'msg'     => $translated,
                // backward-compat:
                'success' => $ok,
                'message' => $translated,
            ], $extra),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}

/* get_used_lang_array(): kullanılan + zorunlu anahtarları döndür (eksikler placeholder) */
if (!function_exists('get_used_lang_array')) {
    function get_used_lang_array(array $extraMandatory = [], bool $onlyActiveLang = true) {
        global $languages, $lang, $used_lang_keys;

        $languages      = is_array($languages ?? null) ? $languages : [];
        $lang           = (string)($lang ?? 'en');
        $used_lang_keys = is_array($used_lang_keys ?? null) ? $used_lang_keys : [];

        // Zorunlu anahtarlar (UI temel hata/eylem metinleri)
        $mandatory_keys = [
            'theme_ok','error','host','db_list_update_failed','tableload_failed','tableload_success','run_query','tableloading','error_occurred',
            'favorite_update_failed','hide_failed','unhide_failed','confirm_delete_perm','confirm_irreversible',
            'rename_failed','network_error','op_failed','folder_name','file_name','invalid_name','disable','verify_failed_retry',
            'network_error_rename','save_error_meta','network_error_meta','save_error_ftp','network_error_ftp','cancel','backup_saved',
            'delete_confirm_phrase','preview','request_error','unexpected_response','loading','delete_failed','project','check_your_email','disabled',
		  'verify','verifying','send_code','code_sent','code_sending','send_failed','invalid_or_expired_code','enter_code','setup','resend_in',
		  'enter_auth_code','scan_and_enter_code','verify_and_save','verified','sending_code','backup_generate_done_save','active','hide','not_verified',
		  'confirm_disable_2fa','confirm_disable_2fa_email','confirm_disable_2fa_app','copy','i_copied_them_hide','linked','backup_already_saved_msg',
        ];
        if (!empty($extraMandatory)) {
            $mandatory_keys = array_values(array_unique(array_merge($mandatory_keys, $extraMandatory)));
        }

        // Kullanılan + zorunlu
        $all_keys = array_values(array_unique(array_merge($used_lang_keys, $mandatory_keys)));
        sort($all_keys, SORT_STRING);

        // Tek dil (aktif dil) çıktısı
        if ($onlyActiveLang) {
            $dict = $languages[$lang] ?? [];
            $out  = [];
            foreach ($all_keys as $k) {
                $out[$k] = array_key_exists($k, $dict) ? $dict[$k] : $k; // eksikse placeholder=anahtar
            }
            return $out;
        }

        // Tüm diller çıktısı
        $out = [];
        foreach ($languages as $code => $dict) {
            $row = [];
            foreach ($all_keys as $k) {
                $row[$k] = array_key_exists($k, $dict ?? []) ? $dict[$k] : $k; // eksikse placeholder
            }
            $out[$code] = $row;
        }
        return $out;
    }
}

/* -JSON- (dosya doğrudan çağrılırsa) */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $payload = get_used_lang_array();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['currentLanguage' => $lang, 'languageData' => $payload],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
?>