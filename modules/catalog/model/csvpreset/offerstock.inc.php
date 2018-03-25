<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvPreset;

class OfferStock extends \RS\Csv\Preset\AbstractPreset
{
    protected static
        $warehouses;
    
    protected
        $link_preset_id,
        $link_id_field,
        $link_foreign_field,
        $link_offer_id_field,
        $array_field = 'stock_num',
        $ormObject,
        $offer_api,
        $warehouse_api;
        
    function __construct($options)
    {        
        $defaults = array(
            'ormObject'   => new \Catalog\Model\Orm\Xstock(),
        );
        
        $this->offer_api     = new \Catalog\Model\OfferApi();
        $this->warehouse_api = new \Catalog\Model\WareHouseApi();
        parent::__construct($options + $defaults);
        $this->loadWarehouses(); //Загрузим склады
    }
    
    /**
    * Загружает склады
    * 
    */
    function loadWarehouses(){
        if (!isset(self::$warehouses)) {
        self::$warehouses = \RS\Orm\Request::make()
                                ->from(new \Catalog\Model\Orm\WareHouse())
                                ->where(array(
                                    'site_id' => \RS\Site\Manager::getSiteId()
                                ))
                                ->objects(null, 'id');
        }
    }
    
    /**
    * Устанавливает ORM объект для работы
    * 
    * @param \RS\Orm\AbstractObject $orm_object - ORM объект
    */
    function setOrmObject(\RS\Orm\AbstractObject $orm_object){
        $this->ormObject = $orm_object;
    }
    
    
    /**
    * Определяет foreign key другого объекта
    * 
    * @param string $field
    * @return void
    */
    function setLinkForeignField($field)
    {
        $this->link_foreign_field = $field;
    }
    
    /**
    * Устанавливает номер пресета, к которому линкуется текущий пресет
    * 
    * @param integer $n - номер пресета
    * @return void
    */
    function setLinkPresetId($n)
    {
        $this->link_preset_id = $n;
    }
    
    /**
    * Определяет foreign key объекта комплектаций
    * 
    * @param string $field
    * @return void
    */
    function setLinkOfferIdField($field)
    {
        $this->link_offer_id_field = $field;
    }
    
    /**
    * Возвращает колонки, которые добавляются текущим набором 
    * 
    * @return array
    */
    function getColumns()
    {
        $columns = array();
        
        if (!empty(self::$warehouses)){
            foreach(self::$warehouses as $warehouse_id=>$warehouse){
               $columns[$this->id.'-offerstock_'.$warehouse_id] = array(
                    'key' => 'offerprice_'.$warehouse_id,
                    'warehouse_id' => $warehouse_id,
                    'title' => t('Остаток по складу "').$warehouse['title'].'"'
                ); 
            }
        }
        
        return $columns;
    }
    
    
    /**
    * Возвращает набор колонок с данными для одной строки
    * 
    * @param mixed $n
    */
    function getColumnsData($n)
    {
        $id = $this->schema->rows[$n][$this->link_offer_id_field];
        $data         = array();
        $values_array = array();
        
        if (isset($this->rows[$id])) {
            
            foreach($this->rows[$id] as $offer_stock) {
                $values_array[$this->id.'-offerstock_'.$offer_stock['warehouse_id']] = $offer_stock['stock'];
            }
       
        }
        
        return $values_array;
    }
    
    
    /**
    * Импортирует данные одной строки текущего пресета в базу
    */
    function importColumnsData()
    {
        if (isset($this->row)) { 
            $result_array = array();
            foreach($this->row as $column_title=>$value){
                $warehouse_info = explode("_",$column_title); 
                $warehouse_id   = $warehouse_info[1];             //id характеристики
                
                if ($value !== ''){
                   $result_array[$warehouse_id] = trim($value); 
                }
                
            }
            if ($result_array) {
                $this->schema->getPreset($this->link_preset_id)->row[$this->array_field] = $result_array;
            }
        }
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
            $this->rows = \RS\Orm\Request::make()
                 ->from($this->ormObject)
                 ->whereIn($this->link_foreign_field, $this->schema->ids)
                 ->objects(null, $this->link_foreign_field, true);
        } 
        
    }
    
    
    
    
}