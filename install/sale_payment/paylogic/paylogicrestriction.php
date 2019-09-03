<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Services\Base\Restriction;

class PayLogicRestriction extends Restriction
{
    public static function getClassTitle()
    {
        return Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_NAME');
    }

    public static function getClassDescription()
    {
        return Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_DESC');
    }

    public static function check($params, array $restrictionParams, $deliveryId = 0)
    {
        if ($restrictionParams['DEVICE'] == 'TPO') {
            //Показывать только на терминалах
			return $params == 'PayLogicKiosk' || strpos($params, 'Electron') != false;
        }
        if ($restrictionParams['DEVICE'] == 'SITE') {
            //Показывать только на сайтах
             return $params != 'PayLogicKiosk' && strpos($params, 'Electron') == false;
        }
        return true;
    }

    protected static function extractParams(Entity $shipment)
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    public static function getParamsStructure($entityId = 0)
    {
        return array(
            "DEVICE" => array(
                'TYPE' => 'ENUM',
                'LABEL' => Loc::getMessage("SALE_HANDLERS_PAY_SYSTEM_SHOW"),
                'OPTIONS' => array(
                    'SITE' => Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_SITE'),
                    'TPO' => Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_TPO'),
                )
            ),
        );
    }
}
