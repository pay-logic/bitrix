<?

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/sale.css");
Loc::loadMessages(__FILE__);
?>

<div class="sale-paysystem-wrapper">
	<span class="tablebodytext">
        <?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_PAYLOGIC_DESCRIPTION') . " " . SaleFormatCurrency($params['PAYMENT_SHOULD_PAY'], $payment->getField('CURRENCY')); ?>
	</span>
    <p>
        <button id="js-payment-button" class="btn btn-default btn-md">
            <?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_PAYLOGIC_BUTTON_PAID') ?>
        </button>
    </p>
    <p>
        <span class="tablebodytext sale-paysystem-description">
            <?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_PAYLOGIC_WARNING_RETURN'); ?>
        </span>
    </p>
</div><!--sale-paysystem-wrapper-->

<script type="text/javascript">
    const $PAYMENT_BUTTON = document.getElementById('js-payment-button');
    const CART = <?= json_encode($params['ORDER'], JSON_PRETTY_PRINT)?>;
    $PAYMENT_BUTTON.onclick = function () {
        PayLogicKiosk.makePayment(CART);
    };
</script>
