<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock'))
{
    return;
}

$arTypesEx = CIBlockParameters::GetIBlockTypes();

$arIBlocks = [];
$iblockFilter = [
    'ACTIVE' => 'Y',
];

if (!empty($arCurrentValues['IBLOCK_TYPE']))
{
    $iblockFilter['TYPE'] = $arCurrentValues['IBLOCK_TYPE'];
}
if (isset($_REQUEST['site']))
{
    $iblockFilter['SITE_ID'] = $_REQUEST['site'];
}
$db_iblock = CIBlock::GetList(["SORT"=>"ASC"], $iblockFilter);
while($arRes = $db_iblock->Fetch())
{
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
}

$arPropertyFields = [];
if (!empty($arCurrentValues['IBLOCK_ID']))
{
    $rsProps = CIBlockProperty::GetList(
        ["SORT"=>"ASC", "NAME"=>"ASC"],
        ["ACTIVE"=>"Y", "IBLOCK_ID"=>$arCurrentValues['IBLOCK_ID']]
    );
    
    while ($arProp = $rsProps->Fetch())
    {
        $arPropertyFields[$arProp['CODE']] = "[{$arProp['CODE']}] {$arProp['NAME']}";
    }
}

$arComponentParameters = [
    'GROUPS' => [
        'BASE' => [
            'NAME' => 'Основные параметры',
        ],
        'FILTER' => [
            'NAME' => 'Настройки фильтрации',
        ],
    ],
    'PARAMETERS' => [
        'IBLOCK_TYPE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Тип инфоблока',
            'TYPE' => 'LIST',
            'VALUES' => $arTypesEx,
            'DEFAULT' => 'news',
            'REFRESH' => 'Y',
        ],
        'IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'Инфоблок',
            'TYPE' => 'LIST',
            'VALUES' => $arIBlocks,
            'DEFAULT' => '',
            'REFRESH' => 'Y',
            'ADDITIONAL_VALUES' => 'Y',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
    ],
];