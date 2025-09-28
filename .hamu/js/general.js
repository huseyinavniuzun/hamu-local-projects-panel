/**
 * general.js
 * [TR] Genel javascript dosyası
 * [EN] General javascript file
 */
function l_js__(key) {
  if (
    window.languageData &&
    window.currentLanguage &&
    window.languageData[window.currentLanguage] &&
    window.languageData[window.currentLanguage][key]
  ) {
    return window.languageData[window.currentLanguage][key];
  }
  return key;
}
// Ayarlarda veritabanı açıkca bilgilerini soruyoruz.
  document.getElementById('database_s').addEventListener('change', function() {
    var dbSettings = document.getElementById('dbSettings');
    dbSettings.style.display = this.checked ? 'block' : 'none';
  });

// Global olarak openSettingsModal fonksiyonunu tanımlıyoruz.
function openSettingsModal() {
  const modalElem = document.getElementById('settingsModal');
  if (modalElem) {
    const modal = new bootstrap.Modal(modalElem);
    modal.show();
  } else {
    console.error("settingsModal"+l_js__('error'));
  }
}

document.addEventListener("DOMContentLoaded", function() {
  // Modal elementini seç
  const settingsModal = document.getElementById("settingsModal");
  if (!settingsModal) {
    console.error("settingsModal"+l_js__('error'));
    return;
  }
  
  // Bootstrap modal instance oluştur
  const modalInstance = new bootstrap.Modal(settingsModal);
  
  // Modal açıldığında arka planı bulanık yap
  settingsModal.addEventListener("shown.bs.modal", function() {
    document.body.classList.add("modal-blur");
  });
  
  // Modal kapandığında arka planı temizle ve backdrop'u kaldır
  settingsModal.addEventListener("hidden.bs.modal", function() {
    document.body.classList.remove("modal-blur");
    const backdrop = document.querySelector(".modal-backdrop");
    if (backdrop) {
      backdrop.remove();
    }
  });
  
  // settingsForm elementini seç
  const settingsForm = document.getElementById("settingsForm");
  if (!settingsForm) {
    console.error("settingsForm "+l_js__('error'));
    return;
  }
  
  // Form submit olayını ekle
  settingsForm.addEventListener("submit", function(event) {
    event.preventDefault();
    
    // Form verilerini topla
    const formData = {
      database_s: document.querySelector("[name='database_s']")?.checked || false,
	        db_server_s: document.querySelector("[name='db_server_s']")?.value || "",
			db_user_s: document.querySelector("[name='db_user_s']")?.value || "",
			db_pass_s: document.querySelector("[name='db_pass_s']")?.value || "",
      modul_s: document.querySelector("[name='modul_s']")?.checked || false,
      file_manager_s: document.querySelector("[name='file_manager_s']")?.checked || false,
      file_manager_s_url: document.querySelector("[name='file_manager_s_url']")?.value || "",
      phpmyadmin_s: document.querySelector("[name='phpmyadmin_s']")?.checked || false,
      phpmyadmin_s_url: document.querySelector("[name='phpmyadmin_s_url']")?.value || "",
      mail_server_s: document.querySelector("[name='mail_server_s']")?.checked || false,
      mail_server_s_url: document.querySelector("[name='mail_server_s_url']")?.value || "",
      ftp_server_s: document.querySelector("[name='ftp_server_s']")?.checked || false,
      ftp_server_s_url: document.querySelector("[name='ftp_server_s_url']")?.value || "",
      first_setup: false
    };
    
    console.log(l_js__('edits'), formData);
    
    // Fetch ile AJAX isteği gönder
    fetch("/.hamu/config.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData)
    })
    .then(response => response.text())
    .then(text => {
      console.log(l_js__('answer_'), text);
      try {
        const data = JSON.parse(text);
        if (data.success) {
          console.log(l_js__('settings_ok'));
          modalInstance.hide();
          setTimeout(() => {
            const backdrop = document.querySelector(".modal-backdrop");
            if (backdrop) backdrop.remove();
            document.body.classList.remove("modal-open", "modal-blur");
            location.reload();
          }, 300);
        } else {
          console.error(l_js__('host')+" "+l_js__('error'), data.error);
        }
      } catch (error) {
        console.error("JSON"+l_js__('error') +" :", error);
      }
    })
    .catch(error => console.error("AJAX"+l_js__('error')+":", error));
  });
});
document.addEventListener("DOMContentLoaded", () => {
	// Tema switcher öğesini seçiyoruz
	const themeSwitch = document.getElementById('mySwitch');

	// AJAX isteğini hangi sayfadaysak oraya yönlendirecek
	const requestURL = window.location.pathname.split("/").pop(); // Sayfanın kendisini hedef al

	// Tarayıcıda kayıtlı tema var mı kontrol et
	const savedTheme = localStorage.getItem("theme");

	// Eğer kayıtlı tema varsa, HTML'e uygula ve switch'i güncelle
	if (savedTheme !== null) {
		document.documentElement.setAttribute("data-bs-theme", savedTheme === "true" ? "dark" : "light");
		if (themeSwitch) themeSwitch.checked = savedTheme === "true";
	}

	// Tema güncelleme fonksiyonu
	const updateTheme = (isDarkMode) => {
		const newTheme = isDarkMode ? "dark" : "light";
		document.documentElement.setAttribute("data-bs-theme", newTheme);
		if (themeSwitch) themeSwitch.checked = isDarkMode;

		// Tarayıcıya kaydet
		localStorage.setItem("theme", isDarkMode);

		// Sunucuya JSON olarak gönder
		const themeData = {
			theme_s: isDarkMode
		};

		fetch(requestURL, { // AJAX isteğini mevcut sayfaya yönlendiriyoruz
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify(themeData)
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				console.log(l_js__('theme_ok'));
			} else {
				console.error(l_js__('error'), data.error);
			}
		})
		.catch(error => console.error("AJAX " + l_js__('error') + ":", error));
	};

	// Tema switcher için event listener ekliyoruz
	if (themeSwitch) {
		themeSwitch.addEventListener("change", function() {
			updateTheme(this.checked);
		});
	}
});

document.addEventListener("DOMContentLoaded", function() {
  var offcanvasSidebar = document.getElementById('offcanvasSidebar');
  if (!offcanvasSidebar) return;

  offcanvasSidebar.addEventListener('show.bs.offcanvas', function() {
    var offcanvasContent = document.getElementById('offcanvasSidebarContent');
    if (!offcanvasContent) return;

    var inlineSidebar = document.getElementById('sidebarContent');
    if (inlineSidebar && inlineSidebar.innerHTML.trim() !== "") {
      offcanvasContent.innerHTML = inlineSidebar.innerHTML;
    } else {
      console.error(__l('error'));
    }
  });


});
document.addEventListener('DOMContentLoaded', function(){
  // Orijinal öğe ve ebeveynini saklayın.
  var langDarkEl = document.getElementById('langDarkSelectionContent');
  if(!langDarkEl) return;
  var origParent = langDarkEl.parentNode;
  var origNext = langDarkEl.nextElementSibling; // Eğer toggle butonundan sonra yer alıyorsa
  
  function updateLangDarkPlacement(){
    if(window.innerWidth < 992){
      // Mobil: Offcanvas'daki konteynere taşıyın.
      var offcanvasContainer = document.getElementById('langDarkSelectionOffcanvasContent');
      if(offcanvasContainer && langDarkEl.parentNode !== offcanvasContainer){
        offcanvasContainer.appendChild(langDarkEl);
      }
    } else {
      // Masaüstü: Öğeyi orijinal ebeveynine geri koyun.
      if(origParent && langDarkEl.parentNode !== origParent){
        if(origNext){
          origParent.insertBefore(langDarkEl, origNext);
        } else {
          origParent.appendChild(langDarkEl);
        }
      }
    }
  }
  
  updateLangDarkPlacement();
  window.addEventListener('resize', updateLangDarkPlacement);
});
