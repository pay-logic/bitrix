<?php

use Bitrix\Main\Loader;

define("NO_AGENT_CHECK", true);
define("NO_AGENT_STATISTIC", true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

define("GET_ORDER", "/paylogic/ecommerce/order/get");
define("GET_ORDER_SLASH", "/paylogic/ecommerce/order/get/");

define("PAY_ORDER", "/paylogic/ecommerce/order/payment");
define("PAY_ORDER_SLASH", "/paylogic/ecommerce/order/payment");

define("GET_METHOD", "GET");
define("X_API_KEY", "X-Api-Key");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/softlogic.kiosk/classes/general/PaylogicRestApi.php");

if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
    http_response_code(500);
    return PaylogicRestApi::createOtherError();
}

$url = explode('?', $_SERVER["REQUEST_URI"])[0];
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$key = array_key_exists(X_API_KEY, $headers) ? $headers[X_API_KEY] : null;


if ((GET_ORDER == $url || GET_ORDER_SLASH == $url) && $method == GET_METHOD) {
    $order = htmlspecialchars(is_set($_GET, 'order') ? $_GET['order'] : null);
    $phone = htmlspecialchars(is_set($_GET, 'phone') ? $_GET['phone'] : null);

    $arResult = PaylogicRestApi::getOrderById($key, $order, $phone);
} elseif ((PAY_ORDER == $url || PAY_ORDER_SLASH == $url) && $method == GET_METHOD) {
    $order = htmlspecialchars(is_set($_GET, 'order') ? $_GET['order'] : null);
    $txid = htmlspecialchars(is_set($_GET, 'txnId') ? $_GET['txnId'] : null);
    $time = htmlspecialchars(is_set($_GET, 'time') ? $_GET['time'] : null);
    $kioskId = htmlspecialchars(is_set($_GET, 'kioskId') ? $_GET['kioskId'] : null);

    $arResult = PaylogicRestApi::payOrderById($key, $order, $txid, $time, $kioskId);
} else {
    $arResult = PaylogicRestApi::error(
        500,
        'Метод API не найден',
        'Метод "API ' . $method . ':' . $url . '" не найден'
    );
}

$code = !empty($arResult['code']) && $arResult['code'] > 0 ? 500 : 200;
http_response_code($code);
header('Content-Type: application/json');
header('X-Paylogic-Header: PoweredByPaylogic');
echo json_encode($arResult);

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_after.php");