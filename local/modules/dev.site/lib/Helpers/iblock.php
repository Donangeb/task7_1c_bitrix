<?php

namespace Only\Site\Helpers;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

class IBlock
{
    /**
     * Получает ID инфоблока по его коду и типу
     *
     * @param string $code Символьный код инфоблока
     * @param string $type Тип инфоблока
     * @return int
     * @throws \Exception
     */
    public static function getIblockID($code, $type)
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Модуль iblock не подключен');
        }

        $cache = Application::getInstance()->getCache();
        $cacheId = md5("iblock_id_{$code}_{$type}");
        $cachePath = '/iblock_id/';

        if ($cache->initCache(3600, $cacheId, $cachePath)) {
            $result = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $res = \CIBlock::GetList(
                [],
                [
                    'CODE' => $code,
                    'TYPE' => $type,
                    'CHECK_PERMISSIONS' => 'N'
                ]
            );

            if ($iblock = $res->Fetch()) {
                $result = (int)$iblock['ID'];
                $cache->endDataCache($result);
            } else {
                $cache->abortDataCache();
                throw new \Exception("Инфоблок с кодом '{$code}' и типом '{$type}' не найден");
            }
        }

        return $result;
    }

    /**
     * Получает ID инфоблока по его коду (без указания типа)
     *
     * @param string $code Символьный код инфоблока
     * @return int
     * @throws \Exception
     */
    public static function getIblockIdByCode($code)
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Модуль iblock не подключен');
        }

        $cache = Application::getInstance()->getCache();
        $cacheId = md5("iblock_id_by_code_{$code}");
        $cachePath = '/iblock_id/';

        if ($cache->initCache(3600, $cacheId, $cachePath)) {
            $result = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $res = \CIBlock::GetList(
                [],
                [
                    'CODE' => $code,
                    'CHECK_PERMISSIONS' => 'N'
                ]
            );

            if ($iblock = $res->Fetch()) {
                $result = (int)$iblock['ID'];
                $cache->endDataCache($result);
            } else {
                $cache->abortDataCache();
                throw new \Exception("Инфоблок с кодом '{$code}' не найден");
            }
        }

        return $result;
    }

    /**
     * Получает символьный код инфоблока по его ID
     *
     * @param int $id ID инфоблока
     * @return string
     * @throws \Exception
     */
    public static function getIblockCode($id)
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Модуль iblock не подключен');
        }

        $cache = Application::getInstance()->getCache();
        $cacheId = md5("iblock_code_{$id}");
        $cachePath = '/iblock_code/';

        if ($cache->initCache(3600, $cacheId, $cachePath)) {
            $result = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $res = \CIBlock::GetByID($id);

            if ($iblock = $res->Fetch()) {
                $result = $iblock['CODE'];
                $cache->endDataCache($result);
            } else {
                $cache->abortDataCache();
                throw new \Exception("Инфоблок с ID '{$id}' не найден");
            }
        }

        return $result;
    }
}