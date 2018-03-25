<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvSchema;
use \RS\Csv\Preset,
    \ExtCsv\Model\CsvPreset as CustomPreset,
    \Catalog\Model\Orm;

/**
* Схема импорта/экспорта в CSV файл для обновления цен и остатков
*/
class SimplePriceStockUpdate extends \RS\Csv\AbstractSchema
{
    protected
        $reset_base_query = true;
    
    function __construct()
    {        
        $config = \RS\Config\Loader::byModule($this);
        $request = \RS\Orm\Request::make()->from(new \Catalog\Model\Orm\Offer())->where('product_id > 0');
                 
        parent::__construct(
            new \Catalog\Model\CsvPreset\SimplePriceStockBase(array(
                'idField' => 'title',
                'ormObject' => new Orm\Offer(),
                'ormProductObject' => new Orm\Product(),
                'ormUnsetFields' => array('pricedata', 'title'),
                'fields' => array(
                    'title', 'barcode'
                ),
                'multisite' => true,
                'selectOrder' => 'product_id ASC, sortn ASC',
                'selectRequest' => $request,
                'searchFields' => array('barcode'),
                'beforeRowImport' => function($_this) {
                    if (!$_this->row['barcode']) { //Если не указан артикул
                        return false;
                    }
                },
                'afterRowImport' => array($this, 'afterBaseRowImport')
            )),                                   
            array(     
                new \Catalog\Model\CsvPreset\OfferStock(array(
                    'linkPresetId' => 0,
                    'linkOfferIdField' => 'id',
                    'linkForeignField' => 'offer_id'
                )),                          
                new \Catalog\Model\CsvPreset\StockCost(array(
                    'linkPresetId' => 0,
                    'linkIdField' => 'id',
                    'sortnField' => 'sortn',
                    'linkIdFieldProduct' => 'product_id',
                    'arrayProductField' => 'excost', //Поле для обновления цены в основной комплектации в товаре
                    'arrayOfferField' => 'pricedata_arr', //Поле для обновления цены всех комплектаций, кроме нулевой
                )),
            ),
            array(
                'afterImport' => array($this, 'afterImport'),
                'fieldScope' => array(
                    'title' => self::FIELDSCOPE_EXPORT
                )
            )            
        );
    }
    
    /**
    * Возвращает запрос для базовой выборки комплектаций (Экспорт)
    * 
    * @return \RS\Orm\Request
    */
    function getBaseQuery()
    {
        if (!$this->query) {
            $this->query = $this->base_preset->getSelectRequest();
        }
        
        //Если есть запрос с выборкой в сессии
        if ($this->reset_base_query && $savedRequest = \Catalog\Model\Api::getSavedRequest('Catalog\Controller\Admin\Ctrl_list')){
            /**
            * @var \RS\ORM\Request
            */
            $q = clone $savedRequest;
            $q->select  = "OFFERS.*, A.title as product_title, A.barcode as product_barcode";
            $q->limit(null)
              ->orderby('OFFERS.product_id ASC, OFFERS.sortn ASC')
              ->leftjoin(new \Catalog\Model\Orm\Offer(), 'OFFERS.product_id = A.id', 'OFFERS')
              ->where('OFFERS.product_id>0')
              ->setReturnClass(new \Catalog\Model\Orm\Offer());
            
            $this->query = $q;
        }
        
        return $this->query;
    }

    /**
    * Устанавливает флаг сброса базового запроса
    * 
    * @param bool $value
    */
    function setResetBaseQuery($value)
    {
        $this->reset_base_query = $value;
    }
    
    /**
    * Обработчик, выполняющийся после импорта набора (которые уложились по 
    * времени в 1 шаг) данных
    * 
    * @param Catalog\Model\CsvSchema\Offer 
    */
    function afterImport()
    {
        //Производим пересчет общих остатков у товаров
        $offer = new \Catalog\Model\Orm\Offer();
        \RS\Orm\Request::make()
            ->update()
            ->from(new \Catalog\Model\Orm\Product(), 'P')
            ->set("P.num = (SELECT SUM(num) FROM {$offer->_getTable()} O WHERE O.product_id = P.id)")
            ->exec();
    }
    
    /**
    * Выполняется после импорта одной строки у основного пресета
    * 
    * @param \Catalog\Model\CsvPreset\SimplePriceStockBase $preset - текущий пресет
    */
    function afterBaseRowImport($preset)
    {
        //Подгрузим объект комплектации, чтобы иметь полные сведения
        $offer = $preset->loadObject();
        
        if ($offer){
            //Обновляем сведения о ценах основной комплектации у товара
            if ($preset->row['barcode'] && $offer && !$offer['sortn'] && isset($preset->row['excost'])) {  
                $product           = new \Catalog\Model\Orm\Product($offer['product_id']);
                $product->fillCost(); //Подгрузим цены
                
                //Добавим к существующим ценам свою или перезапишем
                $product_excost = $product['excost'];
                foreach($preset->row['excost'] as $cost_id=>$info){
                    $product_excost[$cost_id] = $info;
                }
                $product['excost'] = $product_excost;
                $product->update();
            }    
        }else{ //Если комплектации нет найденой, то попробуем найти товар напрямую
            $product = \RS\Orm\Request::make()
                            ->from(new \Catalog\Model\Orm\Product())
                            ->where(array(
                                'site_id' => \RS\Site\Manager::getSiteId(),
                                'barcode' => $preset->row['barcode'],
                            ))->object();
            if ($product){
                $product->fillCost(); //Подгрузим цены
                
                //Добавим к существующим ценам свою или перезапишем
                $product_excost = $product['excost'];
                foreach($preset->row['excost'] as $cost_id=>$info){
                    $product_excost[$cost_id] = $info;
                }
                $product['excost'] = $product_excost;
                $product->update();
            }
        }
    }  
}