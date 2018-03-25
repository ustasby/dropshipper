<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExtCsv\Model\CsvPreset;

/**
* Пресет выгружает/загружает остатки основной комплектации по складам
*/
class Stocks extends \Catalog\Model\CsvPreset\OfferStock
{
    /**
    * Импортирует данные одной строки текущего пресета в базу (от родителя отличается только тем, куда записывается $result_array)
    */
    function importColumnsData()
    {
        if (isset($this->row)) {
            $result_array = array();
            foreach($this->row as $column_title=>$value){
                $warehouse_info = explode("_",$column_title);
                $warehouse_id   = $warehouse_info[1];        //title склада
                
                if ($value !== ''){
                   $result_array[$warehouse_id] = trim($value);
                }
            }
            if ($result_array) {
                $this->schema->getPreset($this->link_preset_id)->row['offers']['main']['stock_num'] = $result_array;
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
                 ->from($this->ormObject, 'A')
                 ->join(new \Catalog\Model\Orm\Offer(), 'A.offer_id = O.id', 'O')
                 ->whereIn('A.'.$this->link_foreign_field, $this->schema->ids)
                 ->where(array('O.sortn' => 0))
                 ->objects(null, $this->link_foreign_field, true);
        }
    }
}