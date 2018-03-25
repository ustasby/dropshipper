<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvPreset;
use \Catalog\Model\Orm;

/**
* Набор колонок описывающих связь товара с ценами
*/
class Cost extends \RS\Csv\Preset\AbstractPreset
{
    protected static
        $type_cost = array(),
        $type_cost_by_title = array(),
        $default_currency = null, //Валюта по умолчанию
        $currencies = array(),
        $currencies_by_title = array();
        
    protected
        $delimiter = ';',
        $id_field,
        $link_preset_id,
        $link_id_field,
        $manylink_orm,
        $orm_object;
        
    
    function __construct($options)
    {
        $this->array_field = 'excost';
        $this->manylink_foreign_id_field = 'cost_id';
        $this->manylink_id_field = 'product_id';
        $this->manylink_orm = new Orm\Xcost();
        $this->orm_object = new Orm\Typecost();
        $this->id_field = 'id';
        $this->link_id_field = 'id';
        parent::__construct($options);
        
        $this->link_preset_id = 0;
        $this->loadCurrencies();
    }
    
    /**
    * Подгрузка сведений о валютах и ценах присутствующих в системе
    * 
    */
    function loadCurrencies()
    {
        $api = new \Catalog\Model\CurrencyApi();
        $api->setOrder('`default` DESC');
        $list = $api->getList();
        foreach($list as $cost) {
            self::$currencies[$cost['id']] = $cost['title'];
        }
        self::$currencies_by_title = array_flip(self::$currencies);
        //Валюта по умолчанию
        self::$default_currency = current(self::$currencies);
        
        $type_api = new \Catalog\Model\CostApi();
        $list = $type_api->getList();
        foreach($list as $typecost) {
            self::$type_cost[$typecost['id']] = $typecost['title'];
        }
        self::$type_cost_by_title = array_flip(self::$type_cost);
    }
    
    /**
    * Устанавливает объект, связанный с данным набором колонок
    * 
    * @param mixed $orm_object
    */
    function setOrmObject(\RS\Orm\AbstractObject $orm_object)
    {
        $this->orm_object = $orm_object;
    }

    /**
    * Загружает связанные данные
    * 
    * @return void
    */
    function loadData()
    {
        $ids = array();
        foreach($this->schema->rows as $row) {
            $ids[] = $row[$this->link_id_field];
        }
        $this->row = array();
        if ($ids) {
            $this->row = \RS\Orm\Request::make()
                ->from($this->manylink_orm, 'X')
                ->whereIn($this->manylink_id_field, $ids)
                ->objects(null, $this->manylink_id_field, true);
        }
    }    
    
    
    /**
    * Возвращает ассоциативный массив с одной строкой данных, где ключ - это id колонки, а значение - это содержимое ячейки
    * 
    * @param integer $n - индекс в наборе строк $this->rows
    * @return array
    */
    function getColumnsData($n)
    {   
        $id = $this->schema->rows[$n][$this->link_id_field];
        
        $values_array = array();
        if (isset($this->row[$id])) {
            foreach($this->row[$id] as $n => $item) {
                $currency = isset(self::$currencies[$item['cost_original_currency']]) ? ' '.self::$currencies[$item['cost_original_currency']] : '';
                $values_array[$this->id.'-costlistname_'.$item['cost_id']]     = str_replace(".", ",", $item['cost_original_val']);
                $values_array[$this->id.'-costlistcurrency_'.$item['cost_id']] = $currency;
            }
        }
        return $values_array;        
        
    }
    
    /**
    * Возвращает колонки, которые добавляются текущим набором 
    * 
    * @return array
    */
    function getColumns() {        
        $columns = array();         
        if (!empty(self::$type_cost)){
           foreach (self::$type_cost as $cost_id => $cost_title){
               $columns[$this->id.'-costlistname_'.$cost_id] = array(
                    'key'   => 'costname_'.$cost_id,
                    'title' => t('Цена').'_'.$cost_title
               ); 
               $columns[$this->id.'-costlistcurrency_'.$cost_id] = array(
                    'key'   => 'costlistcurrency_'.$cost_id,
                    'title' => t('Цена').'_'.$cost_title.'_'.t('Валюта')
               ); 
           } 
        }
        
        return $columns;
    }
    
    /**
    * Импортирует одну строку данных
    * 
    * @return void
    */
    function importColumnsData()
    {
        if (isset($this->row)) {  
            $excost = array();            
            foreach($this->row as $key_info=>$item) {
                $item     = trim($item);                  //Значение ячейки
                $key_info = explode("_",trim($key_info)); //Получим информацию из поля
                $cost_id  = $key_info[1];
                
                switch($key_info[0]){  //Пройдёмся по типу поля 
                    case "costname": //Название цены
                            $value = str_replace(array(","," "), array(".",""), $item);
                            $excost[$cost_id]['cost_original_val'] = $value;
                            break;
                    case "costlistcurrency": //Валюта цены
                            
                            if (!isset(self::$currencies_by_title[$item])){ //Если валюты такой нет
                               $currency_id = 0; 
                            }else{  //Если есть такая валюта
                               $currency_id = self::$currencies_by_title[$item];
                            }
                            $excost[$cost_id]['cost_original_currency'] = $currency_id;
                            break;
                }
            }
            
            $product_excost = false;
            if (isset($this->schema->getPreset($this->link_preset_id)->row[$this->array_field])){
               $product_excost = $this->schema->getPreset($this->link_preset_id)->row[$this->array_field]; 
               foreach ($excost as $cost_id=>$info){
                  $product_excost[$cost_id] = $info;  
               }
            }
            
            //Проверим заданы ли значения валют, если нет то берём ту что поумолчанию
            foreach ($excost as $k=>$excost_row){
                if (isset($excost_row['cost_original_val']) && !isset($excost_row['cost_original_currency'])){
                    $excost[$k]['cost_original_currency'] = \Catalog\Model\CurrencyApi::getDefaultCurrency()->id; 
                }
            }
           
            $this->schema->getPreset($this->link_preset_id)->row[$this->array_field] = $product_excost ? $product_excost : $excost;
        }
    }
}