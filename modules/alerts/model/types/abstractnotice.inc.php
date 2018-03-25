<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Alerts\Model\Types;

abstract class AbstractNotice{
    
    final function __construct()
    {
        // Перегрузка конструктора невозможна
    }
    
    /**
    * Возвращает краткое описание уведомления
    * @return string
    */
    abstract public function getDescription();
    
    /**
    * Возвращает тип текущего уведомления, составленного из имени текущего класса
    * 
    * @return string
    */
    public function getSelfType()
    {
        return str_replace(array('\model\notice', '\\'), array('', '-'), strtolower(get_class($this)));
    }
    
    /**
    * Возвращает экземпляр класса уведомления по типу
    * 
    * @param string $type тип уведомления
    * @return self
    */
    public static function makeByType($type)
    {
        $class_name = self::getClassnameByType($type);
        if (class_exists($class_name)) {
            return new $class_name();
        }
        throw new \RS\Exception(t("Уведомления такого типа '%0' не существует", array($type)));
    }
    
    /**
    * Возвращает имя класса уведомления по типу
    * 
    * @param string $type тип уведомления
    * @return string
    */
    public static function getClassnameByType($type)
    {
        $pre_type = str_replace('-', '-model-notice', $type, $count);
        if ($count != 1) {
            throw new \RS\Exception(t('Передан некорректный тип уведомления, должно быть ИМЯ МОДУЛЯ-ИМЯ УВЕДОМЛЕНИЯ'));
        }
        
        return str_replace('-', '\\', $pre_type);
    }
}