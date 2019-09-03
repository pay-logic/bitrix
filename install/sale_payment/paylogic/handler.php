<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PaySystem\ServiceResult;
use CSaleBasket;
use CSaleOrderPropsValue;

Loc::loadMessages(__FILE__);


/**
 * Class PaylogicKioskHandler
 * @package Sale\Handlers\PaySystem
 */
class PaylogicHandler
    extends PaySystem\ServiceHandler
{
    /**
     * @param Payment $payment
     * @param Request|null $request
     * @return PaySystem\ServiceResult
     */
    public function initiatePay(Payment $payment, Request $request = null)
    {
        $params = array(
            'PAYMENT_SHOULD_PAY' => (float)$this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'),
            'ORDER' => $this->getOrder($payment->getField('ORDER_ID')),
            'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
    );
        $this->setExtraParams($params);
        return $this->showTemplate($payment, "template");
    }

    private function getOrder($order)
    {
        return array(
            'number' => $order,
            'items' => $this->getProducts($order),
            'attributes' => $this->getAttributes($order),
        );
    }

    /**
     * Получение списка атрибутов заказа
     * @param int $order ID заказа
     * @return array Список атрибутов заказа
     */
    private function getAttributes($order)
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
    private function getProducts($order)
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
                'ID', 'NAME', 'QUANTITY', 'PRICE', 'PAID'
            )
        );
        while ($item = $result_db->Fetch()) {
            $product = array();
            $product['id'] = $item['ID'];
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
     * Данная функция должна вернуть массив со списком валют.
     * @return array
     */
    public function getCurrencyList()
    {
        return array('RUB');
    }

    public function cancel(Payment $payment)
    {
        // TODO: Implement cancel() method.
    }

    public function confirm(Payment $payment)
    {
        // TODO: Implement confirm() method.
    }

    public function refund(Payment $payment, $refundableSum)
    {
        // TODO: Implement refund() method.
    }

    /**
     * @return bool
     */
    public function isRefundableExtended()
    {
        // TODO: Implement isRefundableExtended() method.
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return ServiceResult
     */
    public function processRequest(Payment $payment, Request $request)
    {
        // TODO: Implement processRequest() method.
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPaymentIdFromRequest(Request $request)
    {
        // TODO: Implement getPaymentIdFromRequest() method.
    }
}