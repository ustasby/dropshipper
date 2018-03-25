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
* Набор колонок описывающих связь товара с ценами для комплектаций и цен при обновлении только цен и колонок
*/
class StockCost extends \RS\Csv\Preset\AbstractPreset
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
        $link_id_field_product,
        $sortn_field,
        $array_product_field, //Поле для сохранения цены основной комлектации у товара
        $array_offer_field, //Поле для сохранения цены всех комплектаций, кроме нулевой
        $manylink_orm,
        $orm_object;
        
    
    function __construct($options)
    {
        $this->sortn_field           = 'sortn';
        $this->link_id_field_product = 'product_id';
        $this->array_product_field   = 'excost';
        $this->array_offer_field     = 'pricedata_arr';
        
        parent::__construct($options);
        $this->orm_object = new Orm\Typecost();
        $this->id_field   = 'id';
                
        $this->manylink_orm              = new Orm\Xcost();
        $this->manylink_foreign_id_field = 'cost_id';
        $this->manylink_id_field         = 'product_id';
        
        $this->link_id_field         = 'id';
        $this->link_preset_id        = 0;
         
        $this->loadCurrenciesAndCosts();
    }
    
    
    /**
    * Задает поле для установки цены основной комлпектации
    * 
    * @param string $field - id поля
    */
    function setArrayProductField($field)
    {
       $this->array_product_field = $field;
    }
    
    /**
    * Задает поле для установки цены всех комплектаций кроме нулевой
    * 
    * @param string $field - id поля
    */
    function setArrayOfferField($field)
    {
       $this->array_offer_field = $field;
    }
    
    /**
    * Задает поле отвечающее за сортировку комплектаций
    * 
    * @param string $field - id поля
    */
    function setSortnField($field)
    {
       $this->sortn_field = $field;
    }
    
    /**
    * Задает поле для связывания с ценой комплектации
    * 
    * @param string $field - id поля
    */
    function setLinkIdField($field)
    {
       $this->link_id_field = $field;
    }
    
    
    /**
    * Задает поле для связывания с ценой товара
    * 
    * @param string $field - id поля
    */
    function setLinkIdFieldProduct($field)
    {
       $this->link_id_field_product = $field;
    }
    
    
    /**
    * Подгружаем сведения о валюте по названию
    * 
    */
    function loadCurrenciesAndCosts()
    {
        //Получим сведения по Валютам
        $api = new \Catalog\Model\CurrencyApi();
        $api->setOrder('`default` DESC');
        $list = $api->getList();
        foreach($list as $cost) {
            self::$currencies[$cost['id']] = $cost['title'];
        }
        self::$currencies_by_title = array_flip(self::$currencies);
        //Валюта по умолчанию
        self::$default_currency = current(self::$currencies);
        
        //Получим сведения по ценам
        $type_api = new \Catalog\Model\CostApi();
        $type_api->setFilter('type', 'manual');
        $list = $type_api->getList();
        foreach($list as $typecost) {
            self::$type_cost[$typecost['id']] = $typecost['title'];
        }                                                            
        self::$type_cost_by_title = array_flip(self::$type_cost);
    }

    /**
    * Загружает связанные данные
    * 
    * @return void
    */
    function loadData()
    {
        //id характеристик для подгрузки цен
        $product_ids = array();
        foreach($this->schema->rows as $row) {
            $product_ids[] = $row[$this->link_id_field_product];
        }
        
        $this->row = array();
        if ($product_ids) {
            $this->row = \RS\Orm\Request::make()
                ->from($this->manylink_orm, 'X')
                ->whereIn($this->manylink_id_field, $product_ids)
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
        /** 
        * @var \Catalog\Model\Orm\Offer
        */
        $offer      = $this->schema->rows[$n];
        $id         = $offer[$this->link_id_field]; //id комплектации
        $product_id = $offer[$this->link_id_field_product]; //id товара
        $sortn      = $offer[$this->sortn_field]; //Номер сортировки
        
        $values_array = array();
        if (isset($this->row[$product_id]) && !$sortn) { //Если это нулевая комплектация
            foreach($this->row[$product_id] as $n => $item) {
                $currency = isset(self::$currencies[$item['cost_original_currency']]) ? self::$currencies[$item['cost_original_currency']] : self::$default_currency;
                $values_array[$this->id.'-costlistname_'.$item['cost_id']]     = str_replace(".", ",", $item['cost_original_val']);
                $values_array[$this->id.'-costlistcurrency_'.$item['cost_id']] = $currency;
            }
        }elseif (isset($this->row[$product_id]) && $sortn){  //Если не нулевая комплектация
            $price_arr = $offer['pricedata_arr'];
            //Разберём цены в зависимости от типа заданных параметров цены
            if (isset($price_arr['oneprice']) && $price_arr['oneprice']['use']) { //Если единая цена всех типов цен
                foreach (self::$type_cost_by_title as $title=>$cost_id){
                    $znak     = ($price_arr['oneprice']['znak'] == "=") ? "" : "(".$price_arr['oneprice']['znak'].")"; 
                    
                    $currency = "%";
                    if ($price_arr['oneprice']['unit']!="%"){ //Если числовое значение
                      $currency = isset(self::$currencies[$price_arr['oneprice']['unit']]) ? self::$currencies[$price_arr['oneprice']['unit']] : ''; 
                    }
                    $values_array[$this->id.'-costlistname_'.$cost_id]     = $znak.$price_arr['oneprice']['original_value']; 
                    $values_array[$this->id.'-costlistcurrency_'.$cost_id] = $currency;
                }
            }elseif (!isset($price_arr['oneprice']) && isset($price_arr['price'])){ //Если цены на комплектацию разные
                foreach ($price_arr['price'] as $cost_id=>$price_data){
                    $znak     = ($price_data['znak'] == "=") ? "" : "(".$price_data['znak'].")"; 
                    
                    $currency = "%";
                    if ($price_data['unit']!="%"){ //Если числовое значение
                      $currency = isset(self::$currencies[$price_data['unit']]) ? self::$currencies[$price_data['unit']] : ''; 
                    }
                    $values_array[$this->id.'-costlistname_'.$cost_id]     = $znak.$price_data['original_value']; 
                    $values_array[$this->id.'-costlistcurrency_'.$cost_id] = $currency;
                }    
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
    * Добавляет данные цены для массива цены комплектации
    * 
    * @param array $pricedata_arr - массив в данными о ценах комплектации 
    * @param integer $cost_id - id цены
    * @param string $value - значение цены
    */
    function addCostInPriceArray($pricedata_arr, $cost_id, $value)
    {
        if (empty($value) && $value!='0'){
            return $pricedata_arr;
        }
        $znak = "=";
        if (!is_numeric($value)){ //Если это строка
            if (preg_match('/\(([\+|=])\)?([\d|.]+)?/', $value, $matches)){
               $znak  = $matches[1];
               $value = isset($matches[2]) ? $matches[2] : 0;
            }    
        }
        $pricedata_arr['price'][$cost_id]['znak']           = $znak;
        $pricedata_arr['price'][$cost_id]['original_value'] = $value;
        
        return $pricedata_arr;
    }
    
    
    /**
    * Добавляет дополнительный 
    * 
    * @param array $pricedata_arr - массив цены для комплектации
    */
    function addOnePriceIfNeeded($pricedata_arr)
    {
       $one_price       = true;
       $last_seriallize = ''; //Сериализованная строка для проверки 
       foreach ($pricedata_arr['price'] as $cost_id=>$info){
          if (empty($last_seriallize)){
              $last_seriallize = serialize($info);
          }else{
              if ($last_seriallize!=serialize($info)){
                 $one_price = false;
                 break; 
              }
          } 
       }
       if ($one_price){ //Если "Для всех типов цен" признак найден
          $first = reset($pricedata_arr['price']);
          $pricedata_arr['oneprice']['use']            = 1; 
          $pricedata_arr['oneprice']['znak']           = isset($first['znak']) ? $first['znak'] : '+';
          $pricedata_arr['oneprice']['original_value'] = isset($first['original_value']) ? $first['original_value'] : 0; 
          $pricedata_arr['oneprice']['unit']           = isset($first['unit']) ? $first['unit'] : \Catalog\Model\CurrencyApi::getDefaultCurrency()->id; 
       }
       return $pricedata_arr;
    }
    
    /**
    * Импортирует одну строку данных
    * 
    * @return void
    */
    function importColumnsData()
    {
        if (isset($this->row)) {  
            
            //Создадим массив, который нужен для импорта цен комплектаций
            $excost        = array();                    
            $pricedata_arr = array();            
            foreach($this->row as $key_info=>$item) {    
                $item     = trim($item);                  //Значение ячейки
                $key_info = explode("_",trim($key_info)); //Получим информацию из поля
                $cost_id  = $key_info[1];
                
                switch($key_info[0]){  //Пройдёмся по типу поля 
                    case "costname": //Название цены
                            $value = str_replace(array(","," "), array(".",""), $item);
                            $excost[$cost_id]['cost_original_val'] = $value;
                            $pricedata_arr = $this->addCostInPriceArray($pricedata_arr, $cost_id, $value); //Разложим для импорта не многомерной комлектации
                            break;
                    case "costlistcurrency": //Валюта цены
                            $currency_id = $item;
                            if ($currency_id!="%"){
                               $currency_id = isset(self::$currencies_by_title[$currency_id]) ? self::$currencies_by_title[$currency_id] : 0;
                            }
                            $excost[$cost_id]['cost_original_currency'] = $currency_id;
                            $pricedata_arr['price'][$cost_id]['unit']   = $currency_id; 
                            break;
                }
            }
            
            if (!empty($pricedata_arr)){ //Если данные есть, то проверим, нужно ли объединять и добавлять признак "Для всех типов цен"
                $pricedata_arr = $this->addOnePriceIfNeeded($pricedata_arr);
            }
            
            //Проверим заданы ли значения валют, если нет то берём ту что поумолчанию
            foreach ($excost as $k=>$excost_row){
                if (isset($excost_row['cost_original_val']) && !isset($excost_row['cost_original_currency'])){
                    $excost[$k]['cost_original_currency'] = \Catalog\Model\CurrencyApi::getDefaultCurrency()->id; 
                }
            }
                                 
            $this->schema->getPreset($this->link_preset_id)->row[$this->array_product_field] = $excost;
            $this->schema->getPreset($this->link_preset_id)->row[$this->array_offer_field]   = $pricedata_arr;
        }
    }
}