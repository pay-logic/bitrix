<?php

define('MODULE_ID', 'softlogic.kiosk');
define('GET_API_ALLOW', 'GET_API_ALLOW');
define('GET_API_KEY', 'GET_API_KEY');

define('EDIT_API_ALLOW', 'EDIT_API_ALLOW');
define('EDIT_API_KEY', 'EDIT_API_KEY');

define('OK_CODE', 0);
define('OK_MSG', 'OK');

define('ORDER_NOT_FOUND_CODE', 1);
define('ORDER_NOT_FOUND_MSG', 'Заказ по номеру и телефону не найден');

define('ORDER_ALREADY_PAYED_CODE', 2);
define('ORDER_ALREADY_PAYED_MSG', 'Заказ уже оплачен');

define('API_KEY_ERROR_CODE', 100);
define('API_KEY_ERROR_MSG', 'API-key неверен');

define('PARAM_REQUIRED_CODE', 101);
define('PARAM_REQUIRED_MSG', 'Не найден необходимый параметр');

define('PARAM_FORMAT_ERROR_CODE', 102);
define('PARAM_FORMAT_ERROR_MSG', 'Ошибка формата одного из параметров');

define('DB_ERROR_CODE', 103);
define('DB_ERROR_MSG', 'Ошибка обращения к БД');

define('OTHER_ERROR_CODE', 104);
define('OTHER_ERROR_MSG', 'Другая ошибка');

define('PHONE_CODE', 'PHONE');
define('EMAIL_CODE', 'EMAIL');
define('FIO_CODE', 'FIO');
define('IN_DATE_FORMAT', 'Y-m-d?H:i:s?');
define('OUT_DATE_FORMAT', 'd.m.Y');

class PaylogicRestApi
{
    /**
     * Получаем описание товарных позиций по номеру заказа и номеру телефона клиента
     * @param string $key API ключ
     * @param int $order ID заказа
     * @param string $phone Телефон
     * @return array Ответ
     */
    public static function getOrderById($key, $order, $phone)
    {
        if (!self::verifyGetKey($key)) {
            //Ключ не валиден
            return self::createApiKeyError();
        }
        if (!$order || !$phone) {
            //Переданы не все обязательные параметры
            return self::createParamRequiredError();
        }
        if (!is_numeric($order) || !is_string($phone)) {
            //Не верный формат
            return self::createParamFormatError();
        }
        try {
            return self::getOrder($order, $phone);
        } catch (Exception $e) {
            return self::createDbError();
        }
    }

    /**
     * Оплата заказа по номеру заказа
     * @param string $key API ключ
     * @param int $order ID заказа
     * @param string $txid ID транзакции в заказе
     * @param string $time Время обработки транзакции
     * @param string $kioskId ID терминала
     * @return array Информация об оплате
     */
    public static function payOrderById($key, $order, $txid, $time, $kioskId)
    {
        if (!self::verifyPayKey($key)) {
            //Ключ не валиден
            return self::createApiKeyError();
        }
        if (!$order || !$txid || !$time || !$kioskId) {
            //Переданы не все обязательные параметры
            return self::createParamRequiredError();
        }
        if (!is_numeric($order) || !is_string($txid) || !is_string($time) || !is_string($kioskId)) {
            //Не верный формат
            return self::createParamFormatError();
        }
        try {
            return self::payOrder($order, $txid, $time, $kioskId);
        } catch (Exception $e) {
            return self::createDbError();
        }
    }

    /**
     * Оплата заказа
     * @param string $key API ключ
     * @param int $order ID заказа
     * @param string $txid ID транзакции в заказе
     * @param string $time Время обработки транзакции
     * @param string $kioskId ID терминала
     * @return array|bool Информация об оплате
     */
    private static function payOrder($order, $txid, $time, $kioskId)
    {
        if (!self::isExist($order)) {
            //Заказ не существует
            return self::createNotFoundError();
        }
        if (self::isPayed($order)) {
            //Заказ уже оплачен
            return self::createAlreadyPayedError();
        }
        $format = DateTime::createFromFormat(IN_DATE_FORMAT, $time);

        $fields = array(
            'PAY_SYSTEM_ID' => self::getPaymentSystem(),
            'PAY_VOUCHER_NUM' => $txid,
            'PAY_VOUCHER_DATE' => $format->format(OUT_DATE_FORMAT),
        );
        if ($kioskId) {
            $fields['COMMENTS'] = sprintf('Оплата через платежный киоск Paylogic № %s', $kioskId);
        }

        $saleOrder = new CSaleOrder();
        if (!$saleOrder->PayOrder($order, "Y", true, true, 0, $fields)) {
            return self::createOtherError();
        }
        return array(
            'code' => OK_CODE,
            'description' => OK_MSG,
            'message' => OK_MSG,
        );
    }

    /**
     *Получение платежной системы для проведения платежа
     */
    private static function getPaymentSystem()
    {
        $result = CSalePaySystem::GetList(
            $arOrder = Array(),
            $arFilter = Array(
                "LID" => SITE_ID,
                "PSA_ACTION_FILE" => "paylogic",
                "ACTIVE" => "Y"
            )
        );

        while ($ptype = $result->Fetch()) {
            return $ptype["ID"];
        }
        return null;
    }

    /**
     * Получение информации о заказе по номеру заказа и номеру телефона
     * @param int $order ID заказа
     * @param string $phone Телефон
     * @return array
     */
    private static function getOrder($order, $phone)
    {
        if (!self::isExist($order)) {
            //Заказ не существует
            return self::createNotFoundError();
        }
        //Получаем список атрибутов
        $attributes = self::getAttributes($order);
        if (!$attributes || count($attributes) == 0) {
            //Атрибутов нет, телефон нужен обязательно
            return self::createNotFoundError();
        }
        //Телефон
        $phoneVal = self::getAttribute($attributes, PHONE_CODE);
        //Почта
        $emailVal = self::getAttribute($attributes, EMAIL_CODE);
        //ФИО
        $nameVal = self::getAttribute($attributes, FIO_CODE);

        if (!self::comparePhones($phone, $phoneVal)) {
            //Переданный номер телефона не совпадает с номером телефона в заказе
            return self::createNotFoundError();
        }
        if (self::isPayed($order)) {
            //Заказ уже оплачен
            return self::createAlreadyPayedError();
        }
        //Получаем список товаров
        $products = self::getProducts($order);
        if (!$products || count($products) == 0) {
            //Товаров нет или заказ уже оплачен
            return self::createNotFoundError();
        }

        return array(
            'order' => $order,
            'name' => $nameVal,
            'phone' => $phoneVal,
            'email' => $emailVal,
            'items' => $products
        );
    }

    /**
     * Получение списка атрибутов заказа
     * @param int $order ID заказа
     * @return array Список атрибутов заказа
     */
    private static function getAttributes($order)
    {
        $attributes = array();
        $result_db = CSaleOrderPropsValue::GetOrderProps($order);
        while ($item = $result_db->Fetch()) {
            $attribute = array();
            $attribute['key'] = $item['CODE'];
            $attribute['keyTitle'] = $item['NAME'];
            $attribute['value'] = $item['VALUE'];
            $attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * Получение списка товаров заказа
     * @param int $order ID заказа
     * @return array|null Список товаров или null если заказ оплачен или не найден заказа с заданным ID
     */
    private static function getProducts($order)
    {
        $arResult = array();
        $result_db = CSaleBasket::GetList(
            $arOrder = array(),
            $arFilter = array(
                "=ORDER_ID" => $order,
            ),
            $arGroupBy = false,
            $arNavStartParams = false,
            $arSelectFields = array(
                'NAME', 'QUANTITY', 'PRICE', 'PAID'
            )
        );
        while ($item = $result_db->Fetch()) {
            $product = array();
            $product['name'] = $item['NAME'];
            $product['count'] = $item['QUANTITY'] * 1000;
            $product['price'] = intval($item['PRICE'] * 100);
            $product['taxId'] = 1;
            $product['subjectType'] = 1;

            $arResult[] = $product;
        }

        return $arResult;
    }

    /**
     * Проверка существования заказа
     * @param int $order ID заказа
     * @return bool true - Заказ существует, false - инчае
     */
    private static function isExist($order)
    {
        $o = CSaleOrder::GetByID($order);
        if (!$o) {
            return false;
        }
        return true;
    }

    /**
     * Проверка оплаты заказа
     * @param int $order ID заказа
     * @return bool true - Заказ оплачен, false - Иначе
     */
    private static function isPayed($order)
    {
        $o = CSaleOrder::GetByID($order);
        if (!$o || !array_key_exists('PAYED', $o)) {
            //Проверка статуса заказа
            return true;
        }
        return $o['PAYED'] == 'Y';
    }

    /**
     * Получение только цифр из строки
     * @param string $value Строка
     * @return mixed Цифры из строки
     */
    private static function getOnlyNumbers($value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Сравнение номеров телефона
     * @param string $phone1 Телефон
     * @param string $phone2 Телефон
     * @return bool true если номера совпадают, false иначе
     */
    private static function comparePhones($phone1, $phone2)
    {
        $formatPhone1 = self::formatPhone($phone1);
        $formatPhone2 = self::formatPhone($phone2);
        return $formatPhone1 === $formatPhone2;
    }

    /**
     * Форматирование номера телефона
     * @param string $phone Телефон
     * @return string Форматированный номер телефона
     */
    private static function formatPhone($phone)
    {
        $phone = self::getOnlyNumbers($phone);
        $length = strlen($phone);
        if ($length < 3) {
            return $phone;
        }
        $first = substr($phone, 0, 1);
        $second = substr($phone, 1, 2);

        if ($length < 10 || ($length == 12 && $first == '+' && $second == '7')) {
            //1111 или +7 999 888 77 66
            return $phone;
        }
        if ($length == 10) {
            // 999 888 77 66
            return '+7' . $phone;
        }
        if ($first == '7') {
            // 7 999 888 77 66
            return '+' . $phone;
        }
        if ($first == '8') {
            // 8 999 888 77 66
            return '+7' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Получение атрибута по коду
     * @param array $attributes Список атрибутов
     * @param string $key Ключ атрибута
     * @return string|null Значение атрибута
     */
    private static function getAttribute($attributes, $key)
    {
        if (!$attributes) {
            return null;
        }
        foreach ($attributes as $attribute) {
            if (mb_strtolower($attribute['key']) == mb_strtolower($key)) {
                return $attribute['value'];
            }
        }
        return null;
    }

    /**
     * Проверка валидности API ключа для получения данных
     * @param string $key API ключ
     * @return bool true ключ валиден
     */
    private static function verifyGetKey($key)
    {
        if (!$key) {
            return false;
        }
        $allow = COption::GetOptionString(MODULE_ID, GET_API_ALLOW);
        if (!$allow || $allow != 'Y') {
            //API на получение отключено
            return false;
        }
        $skey = COption::GetOptionString(MODULE_ID, GET_API_KEY);
        if (!$skey || $skey != $key) {
            //Передан не верный ключ
            return false;
        }
        return true;
    }

    /**
     * Проверка валидности API ключа для оплаты заказа
     * @param string $key API ключ
     * @return bool true ключ валиден
     */
    private static function verifyPayKey($key)
    {
        if (!$key) {
            return false;
        }
        $allow = COption::GetOptionString(MODULE_ID, EDIT_API_ALLOW);
        if (!$allow || $allow != 'Y') {
            //API на получение отключено
            return false;
        }
        $skey = COption::GetOptionString(MODULE_ID, EDIT_API_KEY);
        if (!$skey || $skey != $key) {
            //Передан не верный ключ
            return false;
        }
        return true;
    }

    /**
     * Формирование ошибочного ответа
     * @param string $code Код ошибки
     * @param string $description Описание ошибки
     * @param string $message Детализация ошибки
     * @return array
     */
    public static function error($code, $description, $message)
    {
        $arResult = array();

        $arResult['code'] = $code;
        $arResult['description'] = $description;
        $arResult['message'] = $message;

        return $arResult;
    }


    /**
     * Создание ответа "Заказ не найден"
     * @return array Ответ
     */
    private static function createNotFoundError()
    {
        return self::error(
            ORDER_NOT_FOUND_CODE,
            ORDER_NOT_FOUND_MSG,
            ORDER_NOT_FOUND_MSG
        );
    }

    /**
     * Создание ответа "Заказ уже оплачен"
     * @return array Ответ
     */
    private static function createAlreadyPayedError()
    {
        return self::error(
            ORDER_ALREADY_PAYED_CODE,
            ORDER_ALREADY_PAYED_MSG,
            ORDER_ALREADY_PAYED_MSG
        );
    }

    /**
     * Создание ответа "api-key неверен"
     * @return array Ответ
     */
    private static function createApiKeyError()
    {
        return self::error(
            API_KEY_ERROR_CODE,
            API_KEY_ERROR_MSG,
            API_KEY_ERROR_MSG
        );
    }

    /**
     * Создание ответа "Не найден необходимый параметр"
     * @return array Ответ
     */
    private static function createParamRequiredError()
    {
        return self::error(
            PARAM_REQUIRED_CODE,
            PARAM_REQUIRED_MSG,
            PARAM_REQUIRED_MSG
        );
    }

    /**
     * Создание ответа "Ошибка формата одного из параметров"
     * @return array Ответ
     */
    private static function createParamFormatError()
    {
        return self::error(
            PARAM_FORMAT_ERROR_CODE,
            PARAM_FORMAT_ERROR_MSG,
            PARAM_FORMAT_ERROR_MSG
        );
    }

    /**
     * Создание ответа "Ошибка обращения к БД"
     * @return array Ответ
     */
    private static function createDbError()
    {
        return self::error(
            DB_ERROR_CODE,
            DB_ERROR_MSG,
            DB_ERROR_MSG
        );
    }

    /**
     * Создание ответа "Другая ошибка"
     * @return array Ответ
     */
    public static function createOtherError()
    {
        return self::error(
            OTHER_ERROR_CODE,
            OTHER_ERROR_MSG,
            OTHER_ERROR_MSG
        );
    }
}