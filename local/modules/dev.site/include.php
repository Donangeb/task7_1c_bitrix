<?

/**
 * Автозагрузка классов из папки lib/
 * PSR-0
 * @param $className
 */
function dev_site_autoload($className)
{
    $className = ltrim($className, '\\');
    $arParts = explode('\\', $className);
    
    // Проверяем, что класс принадлежит нашему namespace
    if ($arParts[0] != 'Only' || $arParts[1] != 'Site') {
        return;
    }
    
    // Формируем путь к файлу
    $arParts = array_splice($arParts, 2);
    $fileName = dirname(__FILE__) . '/lib/' . implode('/', $arParts) . '.php';
    
    if (file_exists($fileName)) {
        require_once $fileName;
    }
}

spl_autoload_register('dev_site_autoload');