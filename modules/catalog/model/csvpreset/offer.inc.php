<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvPreset;

/**
* Добавляет к экспорту колонки соответствующие свойствам ORM объекта. 
* Самый простой набор колонок. В качестве названия колонок выступают названия свойств Orm объекта
*/
class Offer extends \RS\Csv\Preset\Base
{
    protected
        $orm_unset_field = array(); //Массив полей, которые нужно исключить
        
        
        
    /**
    * Устанавливет поля которые нужно убрать из подгруженного объекта
    * 
    * @param array $field - массив полей, которые нужно исключит при обновлении объекта
    */
    function setOrmUnsetFields($field)
    {
        $this->orm_unset_field = $field; 
    }
      
    /**
    * Загружает объект из базы по имеющимся данным в row или возвращает false
    * 
    * @return \RS\Orm\AbstractObject
    */
    function loadObject()
    {
        $cache_key = implode('.', array_keys($this->getSearchExpr())).implode('.', $this->getSearchExpr());
        
        if (!$this->use_cache || !isset($this->cache[$cache_key])) {
            $q = \RS\Orm\Request::make()
                    ->from($this->getOrmObject())
                    ->where($this->getSearchExpr())
                    ->where($this->getMultisiteExpr());
                    
            if ($this->load_expression) {
                $q->where($this->load_expression);
            }
            $object = $q->object();
            if ($object) {
                //Очистим поле которое надо переписать
                unset($object['pricedata']);
                $this->cache[$cache_key] = $object;
            } else {
                return false;
            }
        }
        return $this->cache[$cache_key];
    } 
}