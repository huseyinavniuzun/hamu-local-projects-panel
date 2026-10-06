    $(document).ready(function(){
      $("#runBtn").click(function(){
        var code = $("#phpCode").val();
        $("#result").html("<b>Running code...</b>");
        // Dýþarýdaki runner dosyasý php_runner.php'ye POST isteði gönderiyoruz.
        $.post(".php_runner.php", { phpCode: code }, function(res) {
          $("#result").html(res);
        }).fail(function(){
          $("#result").html("<b>Error executing code.</b>");
        });
      });
    });