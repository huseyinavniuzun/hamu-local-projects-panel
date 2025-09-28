    $(document).ready(function(){
      $("#runBtn").click(function(){
        var code = $("#phpCode").val();
        $("#result").html("<b>Running code...</b>");
        // Dışarıdaki runner dosyası php_runner.php'ye POST isteği gönderiyoruz.
        $.post(".php_runner.php", { phpCode: code }, function(res) {
          $("#result").html(res);
        }).fail(function(){
          $("#result").html("<b>Error executing code.</b>");
        });
      });
    });