<?php
// ============================================================
// НАСТРОЙКИ
// ============================================================
// Домен в punycode (кириллица → латиница):
// вирадмебель.рф = xn--80ajmebqcrka4a.xn--p1ai
$to        = "zayavki@xn--80ajmebqcrka4a.xn--p1ai";
$from      = "zayavki@xn--80ajmebqcrka4a.xn--p1ai";
$from_name = "ВИРАД Мебель";
$subject   = "Новая заявка с сайта ВИРАД";

// Логирование (файл создастся автоматически рядом с send.php)
$log_file = __DIR__ . "/mail.log";

// ============================================================
// ПРОВЕРКА МЕТОДА
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

// ============================================================
// ФОРМИРУЕМ ПИСЬМО
// ============================================================
$body  = "Новая заявка с сайта ВИРАД\n";
$body .= "================================\n\n";
$body .= "Имя: $name\n";
$body .= "Телефон: $phone\n";
$body .= "Комментарий: $message\n\n";
$body .= "================================\n";
$body .= "Дата: " . date("d.m.Y H:i") . "\n";
$body .= "IP: " . $_SERVER["REMOTE_ADDR"] . "\n";

// ============================================================
// ЗАГОЛОВКИ
// ============================================================
$headers  = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from>\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";

$encoded_body    = chunk_split(base64_encode($body));
$encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

// ============================================================
// ОТПРАВКА
// ============================================================
$result = mail($to, $encoded_subject, $encoded_body, $headers);

// Логируем результат
$log_message = date("Y-m-d H:i:s") . " | " . ($result ? "OK" : "FAIL") . " | $name | $phone | $to\n";
@file_put_contents($log_file, $log_message, FILE_APPEND);

if ($result) {
    header("Location: /thanks.html");
    exit;
} else {
    echo "Ошибка отправки письма. Попробуйте позвонить нам напрямую.";
}
