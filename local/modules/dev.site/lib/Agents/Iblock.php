<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs()
    {
        
        
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
        
        // Получаем общее количество элементов
        $rsCount = \CIBlockElement::GetList([], [
            'IBLOCK_ID' => $iblockId
        ], [], false, ['ID']);
        $totalCount = $rsCount->SelectedRowsCount();
        
        // Если элементов больше 10, удаляем старые
        if ($totalCount > 10) {
            // Получаем ID 10 самых новых элементов
            $rsNewest = \CIBlockElement::GetList(
                ['TIMESTAMP_X' => 'DESC'],
                ['IBLOCK_ID' => $iblockId],
                false,
                ['nTopCount' => 10],
                ['ID']
            );
            
            $excludeIds = [];
            while ($arItem = $rsNewest->Fetch()) {
                $excludeIds[] = $arItem['ID'];
            }
            
            // Удаляем все элементы, кроме 10 самых новых
            $rsToDelete = \CIBlockElement::GetList(
                ['TIMESTAMP_X' => 'ASC'],
                [
                    'IBLOCK_ID' => $iblockId,
                    '!ID' => $excludeIds
                ],
                false,
                false,
                ['ID']
            );
            
            while ($arItem = $rsToDelete->Fetch()) {
                \CIBlockElement::Delete($arItem['ID']);
            }
        }
    }
    return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }

    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
