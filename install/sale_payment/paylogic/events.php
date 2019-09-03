<?php

AddEventHandler('sale', 'onSalePaySystemRestrictionsClassNamesBuildList', array('RestrictEvent', 'paymentRestrict'));
class RestrictEvent {
    public static function paymentRestrict() {
        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            array(
                '\PayLogicRestriction' => '/bitrix/php_interface/include/sale_payment/paylogic/paylogicrestriction.php',
            )
        );
    }
}