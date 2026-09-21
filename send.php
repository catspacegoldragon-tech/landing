<?php
// ============================================================
// НАСТРОЙКИ
// ============================================================
$to       = "zayavki@вирадмебель.рф";        // Куда отправлять заявки
$from     = "zayavki@вирадмебель.рф";        // От кого
$from_name = "ВИРАД Мебель";
$subject  = "Новая заявка с сайта ВИРАД";

// Настройки SMTP
$smtp_host = "smtp.netangels.ru";
$smtp_port = 465;
$smtp_user = "zayavki@вирадмебель.рф";       // Логин
$smtp_pass = "juWa2tNf2rsr5Trv";           // Пароль

// ============================================================
// ПРОВЕРКА
// ============================================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo "Доступ запрещён.";
    exit;
}

$name    = isset($_POST["name"])    ? strip_tags(trim($_POST["name"]))    : "";
$phone   = isset($_POST["phone"])   ? strip_tags(trim($_POST["phone"]))   : "";
$message = isset($_POST["message"]) ? strip_tags(trim($_POST["message"])) : "";

if ($name === "" || $phone === "") {
    echo "Пожалуйста, заполните имя и телефон.";
    exit;
}

// Формируем письмо
$body  = "Новая заявка с сайта ВИРАД\n";
$body .= "================================\n\n";
$body .= "Имя: $name\n";
$body .= "Телефон: $phone\n";
$body .= "Комментарий: $message\n\n";
$body .= "================================\n";
$body .= "Дата: " . date("d.m.Y H:i") . "\n";
$body .= "IP: " . $_SERVER["REMOTE_ADDR"] . "\n";

// ============================================================
// ОТПРАВКА ЧЕРЕЗ SMTP
// ============================================================
function smtp_send($host, $port, $user, $pass, $from, $from_name, $to, $subject, $body) {
    $secure = ($port == 465) ? "ssl://" : "";
    $socket = @stream_socket_client($secure . $host . ":" . $port, $errno, $errstr, 20);
    if (!$socket) return "Не удалось подключиться к SMTP: $errstr ($errno)";

    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != "220") { fclose($socket); return "SMTP не отвечает: $response"; }

    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    while ($line = fgets($socket, 515)) { if (substr($line, 3, 1) == " ") break; }

    // Авторизация
    fwrite($socket, "AUTH LOGIN\r\n"); fgets($socket, 515);
    fwrite($socket, base64_encode($user) . "\r\n"); fgets($socket, 515);
    fwrite($socket, base64_encode($pass) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != "235") { fclose($socket); return "Ошибка авторизации: $response"; }

    // Отправитель/получатель
    fwrite($socket, "MAIL FROM:<$from>\r\n"); fgets($socket, 515);
    fwrite($socket, "RCPT TO:<$to>\r\n"); fgets($socket, 515);
    fwrite($socket, "DATA\r\n"); fgets($socket, 515);

    // Заголовки
    $headers  = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

    fwrite($socket, $headers . chunk_split(base64_encode($body)) . "\r\n.\r\n");
    $response = fgets($socket, 515);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return (substr($response, 0, 3) == "250") ? true : "Ошибка отправки: $response";
}

$result = smtp_send($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from, $from_name, $to, $subject, $body);

if ($result === true) {
    // Успех — редирект на страницу благодарности
    header("Location: /thanks.html");
    exit;
} else {
    echo "Ошибка: " . $result;
}
