<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Awz\Ymapfix\Helper;
use Bitrix\Main\SiteTable;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.ymapfix";
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
$zr = "";
if (! ($MODULE_RIGHT >= "R"))
$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$APPLICATION->SetTitle(Loc::getMessage('AWZ_YMAPFIX_OPT_TITLE'));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if(!Loader::includeModule($module_id)) return;

Asset::getInstance()->addJs("/bitrix/js/".$module_id."/sett.js");
Extension::load("ui.progressbar");
Extension::load("ui.buttons");
Extension::load("ui.forms");

$request = Application::getInstance()->getContext()->getRequest();
$siteRes = SiteTable::getList(['select'=>['LID','NAME'],'filter'=>['ACTIVE'=>'Y']])->fetchAll();

$checkIb = [];
if ($request->getRequestMethod()==='POST' && $MODULE_RIGHT == "W" && $request->get('Update'))
{
    $shows = $request->get('ACTIVE');
    if(!is_array($shows)) $shows = [];
    $DSBL_GET = $request->get('DSBL_GET');
    if(!is_array($DSBL_GET)) $DSBL_GET = [];
    $DSBL_REJ = $request->get('DSBL_REJ');
    if(!is_array($DSBL_REJ)) $DSBL_REJ = [];
    $ENBL_REJ = $request->get('ENBL_REJ');
    if(!is_array($ENBL_REJ)) $ENBL_REJ = [];
    $URL = $request->get('URL');
    if(!is_array($URL)) $URL = [];

    foreach($siteRes as $arSite){
        if(!isset($shows[$arSite['LID']]) || !$shows[$arSite['LID']]) {
            $shows[$arSite['LID']] = 'N';
        }
        Option::set($module_id, 'ACTIVE', $shows[$arSite['LID']], $arSite['LID']);
        Option::set($module_id, 'DSBL_GET', $DSBL_GET[$arSite['LID']], $arSite['LID']);
        Option::set($module_id, 'DSBL_REJ', $DSBL_REJ[$arSite['LID']], $arSite['LID']);
        Option::set($module_id, 'ENBL_REJ', $ENBL_REJ[$arSite['LID']], $arSite['LID']);
        Option::set($module_id, 'URL', $URL[$arSite['LID']], $arSite['LID']);
    }
}
$aTabs = array();

$aTabs[] = array(
    "DIV" => "edit1",
    "TAB" => Loc::getMessage('AWZ_YMAPFIX_OPT_SECT1'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_YMAPFIX_OPT_SECT1')
);

$aTabs[] = array(
    "DIV" => "edit3",
    "TAB" => Loc::getMessage('AWZ_YMAPFIX_OPT_SECT3'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_YMAPFIX_OPT_SECT3')
);


$defMapUrl = "//#HOST#/2.1/?lang=ru_RU&apikey=#KEY1#&suggest_apikey=#KEY2#";
$findKeys = ['#KEY1#'=>'', '#KEY2#'=>'','#HOST#'=>'api-maps.yandex.ru'];
if(Loader::includeModule('awz.ydelivery')){
    if(!$findKeys['#KEY1#'])
        $findKeys['#KEY1#'] = Option::get('awz.ydelivery', 'yandex_map_api_key', '', '');
    if(!$findKeys['#KEY2#'])
        $findKeys['#KEY2#'] = Option::get('awz.ydelivery', 'yandex_map_suggest_api_key', '', '');
}
if(Loader::includeModule('awz.belpost')){
    if(!$findKeys['#KEY1#'])
        $findKeys['#KEY1#'] = Option::get('awz.belpost', 'yandex_map_api_key', '', '');
    if(!$findKeys['#KEY2#'])
        $findKeys['#KEY2#'] = Option::get('awz.belpost', 'yandex_map_suggest_api_key', '', '');
}
if(Loader::includeModule('awz.europost')){
    if(!$findKeys['#KEY1#'])
        $findKeys['#KEY1#'] = Option::get('awz.europost', 'yandex_map_api_key', '', '');
    if(!$findKeys['#KEY2#'])
        $findKeys['#KEY2#'] = Option::get('awz.europost', 'yandex_map_suggest_api_key', '', '');
}
if(Loader::includeModule('fileman')){
    if(!$findKeys['#KEY1#'])
        $findKeys['#KEY1#'] = Option::get('fileman', 'yandex_map_api_key', '', '');
}
if($findKeys['#KEY1#']){
    $findKeys['#HOST#'] = 'enterprise.api-maps.yandex.ru';
}
$defMapUrl = str_replace(array_keys($findKeys), array_values($findKeys), $defMapUrl);

$saveUrl = $APPLICATION->GetCurPage(false).'?mid='.htmlspecialcharsbx($module_id).'&lang='.LANGUAGE_ID.'&mid_menu=1';
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
    <form method="POST" action="<?=$saveUrl?>" id="FORMACTION">
        <?
        $tabControl->BeginNextTab();
        ?>
        <?
        $currentSite = $request->get('SITE_ID') ? str_replace($saveUrl.'&SITE_ID=','',$request->get('SITE_ID')) : current($siteRes)['LID'];
        ?>
        <tr>
            <td>
                <?=Loc::getMessage('AWZ_YMAPFIX_OPT_SITE_ID')?>
            </td>
            <td>
                <select name="SITE_ID" onchange="window.location.href=this.value;">
                    <?foreach($siteRes as $arSite){
                        ?>
                        <option value="<?=$saveUrl?>&SITE_ID=<?=$arSite['LID']?>"<?if($arSite['LID']==$currentSite){?> selected="selected"<?}?>>
                            [<?=$arSite['LID']?>] - <?=$arSite['NAME']?>
                        </option>
                    <?}?>
                </select>
            </td>
        </tr>

        <tr>
            <td style="width:20%;"><?=Loc::getMessage('AWZ_YMAPFIX_OPT_ACTIVE')?></td>
            <td>
                <?$val = Option::get($module_id, "ACTIVE", "N",$currentSite);?>
                <input type="checkbox" value="Y" name="ACTIVE[<?=$currentSite?>]" <?if ($val=="Y") echo "checked";?>>
            </td>
        </tr>
        <tr>
            <td style="width:20%;"><?=Loc::getMessage('AWZ_YMAPFIX_OPT_DSBL_GET')?></td>
            <td>
                <?$val = Option::get($module_id, "DSBL_GET", "",$currentSite);?>
                <input size="38" type="text" value="<?=$val?>" name="DSBL_GET[<?=$currentSite?>]">
            </td>
        </tr>
        <tr>
            <td style="width:20%;"><?=Loc::getMessage('AWZ_YMAPFIX_OPT_URL')?></td>
            <td>
                <?$val = Option::get($module_id, "URL", $defMapUrl, $currentSite);?>
                <input size="38" type="text" value="<?=$val?>" name="URL[<?=$currentSite?>]"><br>
                <b><?=Loc::getMessage('AWZ_YMAPFIX_OPT_URL_DEF')?>:</b> <br><?=$defMapUrl?>
            </td>
        </tr>
        <tr>
            <td style="width:20%;"></td>
            <td>
            <pre style="padding:5px;background:#ffffff;font-size:11px;line-height: 12px;text-align:left;"><?=Loc::getMessage('AWZ_YMAPFIX_OPT_DSBL_REJ_DESC')?></pre>
            </td>
        </tr>
        <tr>
            <td style="width:20%;"><?=Loc::getMessage('AWZ_YMAPFIX_OPT_DSBL_REJ')?></td>
            <td>
                <?$val = Option::get($module_id, "DSBL_REJ", "",$currentSite);?>
                <textarea cols="40" rows="4" name="DSBL_REJ[<?=$currentSite?>]"><?=$val?></textarea>
            </td>
        </tr>
        <tr>
            <td style="width:20%;"><?=Loc::getMessage('AWZ_YMAPFIX_OPT_ENBL_REJ')?></td>
            <td>
                <?$val = Option::get($module_id, "ENBL_REJ", "",$currentSite);?>
                <textarea cols="40" rows="4" name="ENBL_REJ[<?=$currentSite?>]"><?=$val?></textarea>
            </td>
        </tr>

        <?
        $tabControl->BeginNextTab();
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
        ?>
        <?
        $tabControl->Buttons();
        ?>
        <input <?if ($MODULE_RIGHT<"W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_YMAPFIX_OPT_BTN_SAVE')?>" />
        <input type="hidden" name="Update" value="Y" />
        <?$tabControl->End();?>
    </form>
    <?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");