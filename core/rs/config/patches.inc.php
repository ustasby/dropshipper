<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Config;

/**
* Патчи к ядру
*/
class Patches extends \RS\Module\AbstractPatches
{
    /**
    * Возвращает список имен существующих патчей
    */
    function init()
    {
        return array(
            '200166',
        );
    }
    
    /**
    * Патч, удаляет ошибочную строку из модуля extcsv, site
    */
    function beforeUpdate200166()
    {
        $fix_file = \Setup::$PATH.'/modules/extcsv/config/handlers.inc.php';
        if (file_exists($fix_file)) {
            $content = file_get_contents($fix_file);
            $content = str_replace("\$this->bind('orm.init.catalog-product');", '', $content);
            file_put_contents($fix_file, $content);
        }
        
        $fix_file = \Setup::$PATH.'/modules/site/config/handlers.inc.php';
        if (file_exists($fix_file)) {
            $content = file_get_contents($fix_file);
            if (strpos($content, 'static function start()') === false) {
                $content = str_replace("->bind('start')", '', $content);
                file_put_contents($fix_file, $content);
            }
        }
            
        \RS\Cache\Cleaner::obj()->cleanOpcache();
        \RS\Cache\Cleaner::obj()->clean(\RS\Cache\Cleaner::CACHE_TYPE_COMMON);        
    }
}
