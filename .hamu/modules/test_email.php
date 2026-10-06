<?php
$page_title		 = "Test Email";		  	  		  							 // Sayfa Başlığı
$body_class	 	 = "";			   			  							// <body> tagı css sitili
$include_db	 	 = 0;			   	  		 						   // 0 = hayır, 1 = evet
$menu_type		 = 1;												  // 0 = Sadece mobil ekranlarda gösteriliyor, 1 = Her ekranda gösteriliyor
$side_bar		 = 1;										  // 0 = sidebar ekleme, 1 = sidebar ekle
require_once __DIR__. '/../header.php';	 // Header
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
<?= $side ?>
<div class="container-module">
     <div class="content-large">
          <div id="result"></div>
          <h2>Mail Test</h2>
          <label class="test">Gönderen - Sender:</label>
          <input type="text" id="sender_name" value="Adı - Name" class="test">

          <label class="test">Gönderen ePosta - Sender Mail:</label>
          <input type="email" id="sender_email" value="abc@test.com" class="test">

          <label class="test">Alıcı / Receiver Mail:</label>
          <input type="email" id="receiver_email" value="cde@test.com" class="test">

          <label class="test">Konu / Subject</label>
          <input type="text" id="subject" value="Test Mail" class="test">

          <label class="test">Mesaj / Message (+HTML):</label>
          <textarea id="message" class="test">Test</textarea>

          <button onclick="sendEmail()" class="test">Gönder / Send </button>

     </div>
</div>

<script>
tinymce.init({
     selector: '#message',
     menubar: false,
     plugins: 'lists link image table code',
     toolbar: 'undo redo | bold italic underline | bullist numlist | link image | table | code'
});

function sendEmail() {
     let senderName = document.getElementById('sender_name').value;
     let senderEmail = document.getElementById('sender_email').value;
     let receiverEmail = document.getElementById('receiver_email').value;
     let subject = document.getElementById('subject').value;
     let message = tinymce.get('message').getContent();

     fetch('.test_email.php', {
               method: 'POST',
               headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
               },
               body: `sender_name=${encodeURIComponent(senderName)}&sender_email=${encodeURIComponent(senderEmail)}&receiver_email=${encodeURIComponent(receiverEmail)}&subject=${encodeURIComponent(subject)}&message=${encodeURIComponent(message)}`
          })
          .then(response => response.text())
          .then(data => {
               const resultEl = document.getElementById('result');
               if (data.includes('başarıyla')) {
                    resultEl.className = "alert alert-success";
               } else {
                    resultEl.className = "alert alert-danger";
               }
               resultEl.innerText = data;
          })
          .catch(error => {
               const resultEl = document.getElementById('result');
               resultEl.className = "alert alert-danger";
               resultEl.innerText = 'Hata oluştu: ' + error;
          });
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/footer.php';?>