<?php

use Bitrix\Main\Localization\Loc;

$module_id = 'softlogic.kiosk';
$RIGHT_W = $RIGHT_R = $USER->IsAdmin();

if (!$RIGHT_W) {
    return;
}

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

$arAllOptions = array(
    Loc::getMessage('SL_REST_OPTION_GET_SECTION'),
    array(
        'GET_API_ALLOW',
        Loc::getMessage('SL_REST_OPTION_ALLOW'),
        'N',
        array(
            'checkbox',
            'Y'
        )
    ),
    array(
        'GET_API_KEY',
        Loc::getMessage('SL_REST_OPTION_KEY'),
        'Y',
        array(
            'text',
            '60'
        )
    ),
    Loc::getMessage('SL_REST_OPTION_EDIT_SECTION'),
    array(
        'EDIT_API_ALLOW',
        Loc::getMessage('SL_REST_OPTION_ALLOW'),
        'N',
        array(
            'checkbox',
            'Y'
        )
    ),
    array(
        'EDIT_API_KEY',
        Loc::getMessage('SL_REST_OPTION_KEY'),
        'Y',
        array(
            'text',
            '60'
        )
    ),
);

$aTabs = array(
    array(
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('MAIN_TAB_SET'),
        'ICON' => 'ib_settings',
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_SET')
    ),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
CModule::IncludeModule($module_id);

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && (
        isset($_REQUEST["Update"])
        || isset($_REQUEST["Apply"])
        || isset($_REQUEST["RestoreDefaults"])
    )
    && $RIGHT_W
    && check_bitrix_sessid()
) {
    foreach ($arAllOptions as $arOption) {
        $name = $arOption[0];
        $val = trim($_REQUEST[$name], " \t\n\r");
        if ($arOption[2][0] == "checkbox" && $val != "Y")
            $val = "N";
        COption::SetOptionString($module_id, $name, $val, $arOption[1]);
    }
}
?>

<form method="post"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();

    foreach ($arAllOptions as $arOption):
        $val = COption::GetOptionString($module_id, $arOption[0]);
        $type = $arOption[3];
        $required = $arOption[2] == 'Y';
        ?>
        <? if (!is_array($arOption)): ?>
        <tr class="heading">
            <td colspan="2"><?= htmlspecialcharsbx($arOption); ?></td>
        </tr>
    <? else: ?>
        <tr>
            <td width="40%" nowrap <? if ($type[0] == "textarea") echo 'class="adm-detail-valign-top"' ?>>
                <label for="<? echo htmlspecialcharsbx($arOption[0]) ?>"><?= $arOption[1] ?> <?= $required ? '*' : '' ?>:</label>
            <td width="60%">
                <? if ($type[0] == "checkbox"): ?>
                    <input type="checkbox" name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
                           id="<?= htmlspecialcharsbx($arOption[0]) ?>"
                           value="Y"<? if ($val == "Y") echo " checked"; ?>
                        <?= $required ? 'required' : '' ?>>
                <? elseif ($type[0] == "text"): ?>
                    <input type="text" size="<? echo $type[1] ?>" maxlength="<?= $type[1] ?>"
                           value="<?= htmlspecialcharsbx($val) ?>"
                           name="<?= htmlspecialcharsbx($arOption[0]) ?>"
                           id="<?= htmlspecialcharsbx($arOption[0]) ?>"
                        <?= $required ? 'required' : '' ?>>
                <? elseif ($type[0] == "textarea"): ?>
                    <textarea rows="<?= $type[1] ?>" cols="<? echo $type[2] ?>"
                              name="<?= htmlspecialcharsbx($arOption[0]) ?>"
                              id="<?= htmlspecialcharsbx($arOption[0]) ?>"
                        <?= $required ? 'required' : '' ?>><? echo htmlspecialcharsbx($val) ?></textarea>
                <? elseif ($type[0] == "selectbox"): ?>
                <select name="<?= htmlspecialcharsbx($arOption[0]) ?>" <?= $required ? 'required' : '' ?>>
                    <?
                    foreach ($type[1] as $key => $value):
                        ?>
                        <option value="<?
                        echo htmlspecialcharsbx($key) ?>"<?
                        if ($key == $val) echo ' selected="selected"' ?>><?
                        echo htmlspecialcharsEx($value) ?></option><?
                    endforeach;
                    ?></select><?
                endif ?>
            </td>
        </tr>
    <? endif; ?>

    <? endforeach ?>

    <? $tabControl->Buttons(); ?>
    <input <? if (!$RIGHT_W) echo "disabled" ?> type="submit" name="Update" value="<?= GetMessage("MAIN_SAVE") ?>"
                                                title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>" class="adm-btn-save">
    <input <? if (!$RIGHT_W) echo "disabled" ?> type="submit" name="Apply" value="<?= GetMessage("MAIN_OPT_APPLY") ?>"
                                                title="<?= GetMessage("MAIN_OPT_APPLY_TITLE") ?>">
    <? if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
        <input <? if (!$RIGHT_W) echo "disabled" ?> type="button" name="Cancel"
                                                    value="<?= GetMessage("MAIN_OPT_CANCEL") ?>"
                                                    title="<?= GetMessage("MAIN_OPT_CANCEL_TITLE") ?>"
                                                    onclick="window.location='<? echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'">
        <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx($_REQUEST["back_url_settings"]) ?>">
    <? endif ?>
    <?= bitrix_sessid_post(); ?>
    <? $tabControl->End(); ?>
</form>