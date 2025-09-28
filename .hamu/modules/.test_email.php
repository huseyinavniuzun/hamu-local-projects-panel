<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_name = $_POST["sender_name"];
    $sender_email = $_POST["sender_email"];
    $receiver_email = $_POST["receiver_email"];
    $subject = $_POST["subject"];
    $message = $_POST["message"];

    $headers = "From: $sender_name <$sender_email>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    if (mail($receiver_email, $subject, $message, $headers)) {
        echo "E-posta başarıyla gönderildi.";
    } else {
        echo "E-posta gönderilemedi.";
    }
} else {
    echo "Geçersiz istek.";
}
?>
