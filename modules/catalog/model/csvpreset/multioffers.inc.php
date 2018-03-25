<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvPreset;

/**
* Класс пресета для CSV для многомерных комплектаций
*/
class MultiOffers extends \RS\Csv\Preset\AbstractPreset
{
    protected static
        $props,
        $groups,
        $index;
    
    protected
        $delimiter = ";\n",
        $value_delimiter = "|",
        $id_field = 'id',
        $link_id_field,
        $link_preset_id,
        $mask,
        $mask_fields = array(),
        $title,
        $array_field = 'multioffers',
        $manylink_foreign_id_field = 'prop_id',
        $manylink_id_field = 'product_id',
        $manylink_orm;
    
    function __construct($options)
    {
        $defaults = array(
            'ormObject' => new \Catalog\Model\Orm\MultiOfferLevel(),
            'propertyOrm' => new \Catalog\Model\Orm\Property\Item(),
            'manylinkOrm' => new \Catalog\Model\Orm\Property\Link(),                    
            'mask' => '{title}:({group_name}){property}',
            'multisite' => true
        );
        parent::__construct($options + $defaults);
        $this->loadProperty();        
    }
    
    
    /**
    * Добавляет дополнительное условие в виде site_id = ТЕКУЩИЙ САЙТ, если задано true
    * 
    * @param bool $bool
    * @return void
    */
    function setMultisite($bool)
    {
        $this->is_multisite = $bool;
    }
    
    /**
    * Возвращает условие для добавления к Where, если установлено свойство multisite => true
    * 
    * @return array
    */
    function getMultisiteExpr()
    {
        return $this->is_multisite ? array('site_id' => \RS\Site\Manager::getSiteId()) : array();
    }        
    
    function loadProperty()
    {
        if (!isset(self::$props)) {
            self::$props = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Property\Item())
                ->where($this->getMultisiteExpr() ?: null)
                ->objects(null, 'id');
            
            self::$groups = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Property\Dir())
                ->where($this->getMultisiteExpr() ?: null)
                ->objects(null, 'id');
            
            foreach(self::$props as $prop) {
                $group_name = isset(self::$groups[$prop['parent_id']]) ? self::$groups[$prop['parent_id']]['title'] : '';
                self::$index["($group_name)".$prop['title']] = $prop['id'];
            }
        }
    }
            
    /**
    * Устанавливает ORM объект связки многие ко многим
    * 
    * @param \RS\Orm\AbstractObject $orm
    * @return void
    */
    protected function setManylinkOrm(\RS\Orm\AbstractObject $orm)
    {
        $this->manylink_orm = $orm;
    }
    
    
    function setLinkIdField($field)
    {
        $this->link_id_field = $field;
    }
    
    function setLinkPresetId($id)
    {
        $this->link_preset_id = $id;
    }
    
    /**
    * Устанавливает маску для формирования строки из данных в CSV файле
    * 
    * @param string $mask
    * @return void
    */
    protected function setMask($mask)
    {
        $this->mask = $mask;
        $this->mask_fields = array();
        if (preg_match_all('/\{(.*?)\}/', $this->mask, $match)) {
            foreach($match[1] as $field) {
                $this->mask_fields[] = $field;
            }
        }
        $pattern = preg_replace_callback('/(\{.*?\})/', function($match) {
                    $field = trim($match[1], '{}');
                    return "(?P<{$field}>.*?)";
                }, quotemeta($this->mask));
        $this->mask_pattern = '/^'.$pattern.'$/';        
    }
    
    /**
    * Устанавливает название экспортной колонки
    * 
    * @param mixed $title
    */
    function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
    * Загружает связанные данные
    * 
    * @return void
    */
    function loadData()
    {
        $this->row = array();
        if ($this->schema->ids) {
            
            //Подгрузим многомерные уровни комплектаций для товаров
            $this->rows = \RS\Orm\Request::make()
                            ->from(new \Catalog\Model\Orm\MultiOfferLevel())
                            ->whereIn($this->manylink_id_field, $this->schema->ids)
                            ->objects(null, $this->manylink_id_field, true);
        }
        
    }
    
    function getColumns()
    {
        return array(
            $this->id.'-multiooffers' => array(
                'key' => 'multioffer',
                'title' => $this->title
            )
        );
    }
    
    function getColumnsData($n)
    {
        $id = $this->schema->rows[$n][$this->link_id_field];
        
        $data = array();
        if (isset($this->rows[$id]) && !empty($this->rows[$id])) { //Если мы в нужной колонке и есть что ставить
            foreach($this->rows[$id] as $multilevel) {
                $prop_id  = $multilevel['prop_id'];
                $property = @self::$props[$prop_id]; //Характеристика
                $group    = @self::$groups[$property['parent_id']]; //Группа характеристик
                
                $data[] = $multilevel['title'].":(".$group['title'].")".$property['title'];
            }
        }
        
        
        return array($this->id.'-multiooffers' => implode($this->delimiter, $data));
    }
    
    /**
    * Импорт колонок с данными
    * 
    */
    function importColumnsData()
    {
        if (isset($this->row['multioffer'])) {
            $items = explode($this->delimiter, $this->row['multioffer']);
            
            $result_array = array();
            if (!empty($items)){
                $result_array = array(
                   'use'   => 1, 
                   'levels' => array()
                );
                
                foreach($items as $item) {
                    $item = trim($item);
                    if (preg_match($this->mask_pattern, $item, $match)) {
                        $index_name = "({$match['group_name']}){$match['property']}";
                        if (isset(self::$index[$index_name])){
                            $result_array['levels'][] = array(
                                'title' => $match['title'],
                                'prop'  => self::$index[$index_name]
                            );    
                        }
                    }
                } 
            }

            $this->schema->getPreset($this->link_preset_id)->row[$this->array_field] = $result_array;
        }
    }    
    
}


?>
