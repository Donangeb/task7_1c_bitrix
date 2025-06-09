<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CustomNewsListComponent extends CBitrixComponent
{
    protected $errors = [];

    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_TYPE'] = trim($arParams['IBLOCK_TYPE'] ?? '');
        $arParams['IBLOCK_ID'] = isset($arParams['IBLOCK_ID']) ? (int)$arParams['IBLOCK_ID'] : 0;
        $arParams['FILTER'] = is_array($arParams['FILTER'] ?? null) ? $arParams['FILTER'] : [];
        $arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? (int)$arParams['CACHE_TIME'] : 3600;

        return $arParams;
    }

    public function executeComponent()
    {
        try {
            if ($this->checkParams()) {
                if ($this->startResultCache($this->arParams['CACHE_TIME'])) {
                    $this->getItems();
                    $this->groupItemsByIBlock();
                    $this->includeComponentTemplate();
                }
            }
        } catch (Exception $e) {
            $this->abortResultCache();
            $this->errors[] = $e->getMessage();
        }

        if (!empty($this->errors)) {
            $this->showErrors();
        }
    }

    protected function checkParams(): bool
    {
        if (empty($this->arParams['IBLOCK_TYPE'])) {
            $this->errors[] = 'Не указан тип инфоблока';
            return false;
        }

        return true;
    }

    protected function getItems(): void
    {
        $filter = [
            'ACTIVE' => 'Y',
            'IBLOCK_TYPE' => $this->arParams['IBLOCK_TYPE'],
        ];

        if ($this->arParams['IBLOCK_ID'] > 0) {
            $filter['IBLOCK_ID'] = $this->arParams['IBLOCK_ID'];
        }

        if (!empty($this->arParams['FILTER'])) {
            $filter = array_merge($filter, $this->arParams['FILTER']);
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . 'array.json', json_encode($this->arParams));
        }

        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            $filter,
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'DATE_ACTIVE_FROM',
                'PREVIEW_TEXT',
                'DETAIL_PAGE_URL'
            ]
        );

        while ($item = $res->GetNext()) {
            $this->arResult['ITEMS'][$item['ID']] = $item;
        }
    }

    protected function groupItemsByIBlock(): void
    {
        $this->arResult['ITEMS_BY_IBLOCK'] = [];

        if (!empty($this->arResult['ITEMS'])) {
            foreach ($this->arResult['ITEMS'] as $item) {
                $iblockId = $item['IBLOCK_ID'];
                if (!isset($this->arResult['ITEMS_BY_IBLOCK'][$iblockId])) {
                    $this->arResult['ITEMS_BY_IBLOCK'][$iblockId] = [];
                }
                $this->arResult['ITEMS_BY_IBLOCK'][$iblockId][] = $item;
            }
        }
    }

    protected function showErrors(): void
    {
        foreach ($this->errors as $error) {
            ShowError($error);
        }
    }
}
