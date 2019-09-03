<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UrlRewriter;

$pathInstall = dirname(__FILE__);
Loc::loadMessages(__FILE__);

if (class_exists('softlogic_kiosk')) return;

class softlogic_kiosk extends CModule
{
    const MODULE_ID = "softlogic.kiosk";
    var $MODULE_ID = "softlogic.kiosk";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME = '';
    var $MODULE_DESCRIPTION = '';
    var $PARTNER_NAME;
    var $PARTNER_URI;

    //init.php
    static protected $pathFileInit = null;
    //str module include
    static protected $strInclude = null;

    public function __construct()
    {
        global $DOCUMENT_ROOT;
        self::$pathFileInit = $DOCUMENT_ROOT . '/bitrix/php_interface/init.php';
        self::$strInclude = 'include_once($_SERVER[\'DOCUMENT_ROOT\']."/bitrix/modules/softlogic.kiosk/classes/general/PaylogicRestApi.php");';

        $arModuleVersion = array();
        $path = dirname(__FILE__);
        include($path . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->PARTNER_NAME = Loc::getMessage('SL_REST_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('SL_REST_PARTNER_URI');
        $this->MODULE_NAME = Loc::getMessage('SL_REST_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('SL_REST_MODULE_DESCRIPTION');
    }

    function DoInstall()
    {
        //Добавляем модуль
        RegisterModule($this->MODULE_ID);
        //Добавляем обработку адресов
        $arFields = array(
            'CONDITION' => '#^/paylogic/ecommerce/#',
            'RULE' => '',
            'ID' => null,
            'PATH' => '/bitrix/services/softlogic.kiosk/rest.php',
        );
        UrlRewriter::add(SITE_ID, $arFields);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        return true;
    }

    function DoUninstall()
    {
        //Удаляем модуль
        UnRegisterModule($this->MODULE_ID);
        //Удаляем обработку адресов
        $arFields = array(
            'CONDITION' => '#^/paylogic/ecommerce/#',
            'PATH' => '/bitrix/services/softlogic.kiosk/rest.php',
        );
        UrlRewriter::delete(SITE_ID, $arFields);

        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        return true;
    }

    function InstallFiles()
    {
        //Копируем API
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/softlogic.kiosk/lib/services/softlogic.kiosk",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/services/softlogic.kiosk", true, true);
        //Копируем обработчик платежной системы
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/softlogic.kiosk/install/sale_payment",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/include/sale_payment", true, true);
        //Копируем изображение платежной системы
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/softlogic.kiosk/install/images/paylogic.png",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/sale/sale_payments/paylogic.png", true, true
        );
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/services/softlogic.kiosk/");
        DeleteDirFilesEx("/bitrix/php_interface/include/sale_payment/paylogic/");
        DeleteDirFilesEx("/bitrix/images/sale/sale_payments/paylogic.png");
        return true;
    }

    function InstallDB()
    {
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallDB()
    {
        COption::RemoveOption($this->MODULE_ID, 'GET_API_ALLOW');
        COption::RemoveOption($this->MODULE_ID, 'GET_API_KEY');
        COption::RemoveOption($this->MODULE_ID, 'EDIT_API_ALLOW');
        COption::RemoveOption($this->MODULE_ID, 'EDIT_API_KEY');

        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }
}

?>