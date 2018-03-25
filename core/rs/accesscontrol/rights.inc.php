<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\AccessControl;

/**
* Отвечает за проверку прав доступа
*/
class Rights
{
    protected static
        /**
        * Идентификатор системного модуля
        */
        $default_module = 'main';
        
    /**
    * Возвращает false - если у модуля $mod_name имеется разрешение на бит $bit иначе текст ошибки
    * 
    * @param object|string $mod_name - сокращенное имя модуля или любой объект модуля
    * @param mixed $bit - номер бита от 0 до 7
    * @return bool | string
    */
    public static function CheckRightError($mod_name, $bit)
    {
        if (gettype($mod_name) == 'object') { //Извлекаем из имени класса имя модуля
            //Если из имени класса не удалось извлечь имя модуля, то уравниваем его права с системным модулем
            $mod_name = \RS\Module\Item::nameByObject($mod_name, self::$default_module);
        }
        
        //Если у модуля нет конфигурационного файла, то уравниваем его права с системным модулем
        if (!\RS\Module\Manager::staticModuleExists($mod_name)) {
            $mod_name = self::$default_module;
        }
        
        $user = \RS\Application\Auth::getCurrentUser();
        
        if (!$user->checkModuleRight($mod_name, $bit)) {
            $err = t("Недостаточно прав");
            $module = new \RS\Module\Item($mod_name);
            if ( ($config = $module->getConfig()) && isset($config->access_bit[$bit])) {
                $err .= t(" (Необходимы права на '%rights')", array('rights' => $config->access_bit[$bit]));
            }
            return $err;
        }
        return false;
    }
}