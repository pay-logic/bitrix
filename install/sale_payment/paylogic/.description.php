<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$data = array(
	'NAME' => Loc::getMessage('SL_SALE_NAME'),
	'SORT' => 500,
	'IS_AVAILABLE' => $isAvailable,
	'CODES' => array(
	)
);