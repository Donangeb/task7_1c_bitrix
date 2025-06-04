<?php

namespace Only\Site\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;

class Iblock
{

    public static function addLog(&$arFields)
    {
        if (!Loader::includeModule('iblock')) {
            return;
        }

        $logIblockId = self::getIblockIdByCode('LOG');
        
        // Не логируем изменения самого инфоблока LOG
        if ($arFields['IBLOCK_ID'] == $logIblockId) {
            return;
        }

        // Получаем информацию об инфоблоке
        $iblock = IblockTable::getById($arFields['IBLOCK_ID'])->fetch();
        if (!$iblock) {
            return;
        }

        // Получаем или создаем раздел для логов
        $sectionId = self::getOrCreateLogSection($iblock['CODE'], $iblock['NAME']);

        // Получаем полный путь разделов для элемента
        $sectionPath = self::getElementSectionPath($arFields['ID'], $arFields['IBLOCK_ID']);

        $el = new \CIBlockElement;
        $result = $el->Add([
            'IBLOCK_ID' => $logIblockId,
            'IBLOCK_SECTION_ID' => $sectionId,
            'NAME' => $arFields['ID'],
            'DATE_ACTIVE_FROM' => $arFields['TIMESTAMP_X'] ?? $arFields['DATE_CREATE'] ?? ConvertTimeStamp(time(), 'FULL'),
            'PREVIEW_TEXT' => $iblock['NAME'] . ' -> ' . implode(' -> ', $sectionPath) . ' -> ' . $arFields['NAME'],
            'ACTIVE' => 'Y',
        ]);
        return $result;
    }

    protected static function getIblockIdByCode(string $code): int
    {
        $iblock = IblockTable::getList([
            'filter' => ['CODE' => $code],
            'select' => ['ID']
        ])->fetch();

        return $iblock['ID'] ?? 0;
    }

    protected static function getOrCreateLogSection(string $code, string $name): int
    {
        $logIblockId = self::getIblockIdByCode('LOG');
        $section = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => $logIblockId,
                'CODE' => $code
            ],
            'select' => ['ID']
        ])->fetch();

        if ($section) {
            return $section['ID'];
        }

        // Создаем новый раздел
        $section = new \CIBlockSection;
        return $section->Add([
            'IBLOCK_ID' => $logIblockId,
            'NAME' => $name,
            'CODE' => $code,
            'ACTIVE' => 'Y'
        ]);
    }

    protected static function getElementSectionPath(int $elementId, int $iblockId): array
    {
        $path = [];
        $res = \CIBlockElement::GetByID($elementId);
        if ($element = $res->GetNext()) {
            $sectionId = $element['IBLOCK_SECTION_ID'];
            if ($sectionId) {
                $nav = \CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME', 'CODE']);
                while ($section = $nav->GetNext()) {
                    $path[] = $section['NAME'];
                }
            }
        }
        return $path;
    }

    public static function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality
                    );
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList(
                                [],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ],
                                false,
                                false,
                                ['ID']
                            )
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList(
                                [],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ],
                                false,
                                false,
                                ['ID', 'IBLOCK_ID', 'NAME']
                            );
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }
}
