<?php

chdir(dirname(__FILE__));
error_reporting(E_ALL);
ini_set('display_errors', 1);


#$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
#$dotenv->load();

$nameSERVER = $_ENV['NAMESERVER']; 
$nameDB = $_ENV['NAMEDB'];
$nameUSER = $_ENV['USERNAME'];
$passUSER = $_ENV['PASSUSER'];

// Telegram settings
$chatID = $_ENV['CHATID'];
$tokenTelegram = $_ENV['TOKENTELEGRAM'];
//SemySMS settings
$url = $_ENV['URL'];
$token = $_ENV['TOKEN'];
$device = $_ENV['DEVICE'];


$connection = pg_connect("host=$nameSERVER dbname=$nameDB user=$nameUSER password=$passUSER");
if (!$connection) {
    die("Нет соединения с сервером!");
}


//$test = "Это сообщение отправлено из примечания в карточке. заказ №#number готов!"; // для проверки в примечании

// тестирование на одном заказе
//$is_notify = false;
//$query = "select value from dbconfig where id='{4972aab6-69bf-11ea-a7f5-002590e90bd2}'";
//$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
//$r = pg_fetch_assoc($result);
//if ($r['value'] == 1) {
//    $is_notify = true;
//    $query = "update dbconfig set value=0 where id='{4972aab6-69bf-11ea-a7f5-002590e90bd2}'";
//    $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
//}
//if (!$is_notify) {
//    die("no data changes");
//}

$query = "select d.numdoc, d.phone, ch.telephon1 as phone_point, ch.notes_text
          from doc d
          join client ch on ch.id=d.id_client_l
          where data_production=now()::date";
$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
while ($r = pg_fetch_assoc($result)) {

    if (empty($r['notes_text'])) {
        $message = "Здравствуйте, памятник №".$r['numdoc']. " готов. Это информационное сообщение, не нужно на него отвечать. По всем вопросам обращайтесь по телефону пункта приёма заказов."; // дефолтное сообщение для всех
    } else {
        $message = $r['notes_text'];
        $message = str_replace("#number", $r['numdoc'], $message);
    }

    // Физ. лица
    if (!empty($r['phone'])) {
        $phone = $r['phone'];
        sendSMS($url, $device, $token, parsePhone($phone), $message);
        sendTelegramMessage($chatID, $tokenTelegram, "☎️ $phone\n💬 $message");
    }

    // Пункты приема
    if (!empty($r['phone_point'])) {
        $phone = $r['phone_point'];
        sendSMS($url, $device, $token, parsePhone($phone), $message);
        sendTelegramMessage($chatID, $tokenTelegram, "☎️ $phone\n💬 $message");
    }
}

pg_close($connection);

function parsePhone($phone) {
    return preg_replace('/\D/', '', $phone);
}

function sendTelegramMessage($chatID, $token, $message) {
    echo "sending message to " . $chatID . "\n";
    $url = "https://api.telegram.org/" . $token . "/sendMessage?chat_id=" . $chatID;
    $url = $url . "&text=" . urlencode($message);
    $curl = curl_init();
    $optArray = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    );
    curl_setopt_array($curl, $optArray);
    $output = curl_exec($curl);
    curl_close($curl);

    echo $output;
}

function sendSMS($url, $device, $token, $phone, $msg) {
    $sms = array(
        "device" => $device,
        "token" => $token,
        "phone" => $phone,
        "msg" => $msg
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $sms);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $output = curl_exec($curl);
    if ($output === false) {
        echo 'Ошибка: ' . curl_error($curl);
    } else {
        echo $output;
    }
    curl_close($curl);
}
