<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm;
use \RS\Orm\Type;

/**
* Комплектация товара. (или товарное предложение)
*/
class Offer extends \RS\Orm\OrmObject
{
    protected static
        $table = "product_offer";
    
    public
        $first_sortn = 0; //Сортировочный индекс, который следует присваивать первой добавляемой комплектации
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'product_id' => new Type\Integer(array(
                'description' => t('ID товара'),
                'index'=>true,
                'visible' => false,
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Название'),
                'maxLength' => 300,
                'mainVisible' => false,
            )),            
            'barcode' => new Type\Varchar(array(
                'description' => t('Артикул'),
                'maxLength' => 50,
                'index' => true,
                'allowEmpty' => false,
                'mainVisible' => false,
            )),
            'weight' => new Type\Real(array(
                'description' => t('Вес'),
                'default' => 0
            )),
            'pricedata' => new Type\TinyText(array(
                'description' => t('Цена (сериализован)'),
                'visible' => false,
            )),
            'pricedata_arr' => new Type\ArrayList(array(
                'description' => t('Цена'),
                'template' => '%catalog%/form/offer/price_data.tpl',
                'mainVisible' => false,
            )),
            'propsdata'  => new Type\TinyText(array(
                'description' => t('Характеристики комплектации (сериализован)'),
                'visible' => false,
            )),
            'propsdata_arr' => new Type\ArrayList(array(
                'description' => t('Характеристики комплектации'),
                'visible' => false,
            )),
            '_propsdata' => new Type\ArrayList(array(
                'description' => t('Характеристики комплектации'),
                'template' => '%catalog%/form/offer/props_data.tpl',
                'mainVisible' => false,
            )),
            'num' => new Type\Decimal(array(
                'maxLength' => 11,
                'decimal' => 3,
                'allowEmpty' => false,
                'default' => 0,
                'description' => t('Остаток на складе'),
                'visible' => false,
            )),
            'stock_num' => new Type\Mixed(array(
                'description' => t('Остатки на складах'),
                'template' => '%catalog%/form/offer/stock_num.tpl',
                'visible' => true,
                'mainVisible' => false,
            )),
            'photos' => new Type\Varchar(array(
                'maxLength' => 1000,
                'description' => t('Фотографии комплектаций'),
                'visible' => false,
            )),
            'photos_arr' => new Type\ArrayList(array(
                'description' => t('Связанные фото'),
                'template' => '%catalog%/form/offer/photos.tpl',
                'mainVisible' => false,
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядковый номер'),
                'visible' => false,
            )),            
            'unit' => new Type\Integer(array(
                'description' => t('Единица измерения'),
                'default' => 0,
                'List' => array(array('\Catalog\Model\UnitApi', 'selectList')),
            )),
            'position' => new Type\Varchar(array(
                'description' => t(''),
                'runtime' => true,
                'template' => '%catalog%/form/offer/position.tpl',
                'visible' => false
            )),
            'processed' => new Type\Integer(array(
                        'description' => t('Флаг обработанной во время импорта комплектации'),
                        'maxLength' => '2',
                        'visible' => false,
            )),
            'xml_id' =>  new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Идентификатор товара в системе 1C'),
                'visible' => false,
            )),
            'import_hash' => new Type\Varchar(array(
                'maxLength' => '32',
                'description' => t('Хэш данных импорта'),
                'visible' => false
            )),
            'sku' =>  new Type\Varchar(array(
                'maxLength' => 50,
                'description' => t('Штрихкод'),
                'mainVisible' => false,
            )),
        ));
        
        $this->addIndex(array('site_id', 'xml_id'), self::INDEX_UNIQUE);
    }
    
    /**
    * Вызывается после загрузки объекта
    * @return void
    */
    function afterObjectLoad()
    {
        // Развернем фото комплектаций и Если photos_arr не задан
        if (!empty($this['photos']) && !$this->isModified('photos_arr')) {
            $photos = @unserialize($this['photos']);
            if (is_array($photos) && count($photos)==1 && !$photos[0]){
               $photos = array(); 
            }
            $this['photos_arr'] = $photos ? (array)$photos : array();
        }
        
        // Развернем данные по ценам
        if (!empty($this['pricedata'])) {
            $this['pricedata_arr'] = @unserialize($this['pricedata']) ?: array();
        }else{
            if(empty($this['pricedata_arr'])){
                $this['pricedata_arr'] = array();
            }
        }
        
        //Развернем данные по характеристикам
        if (!empty($this['propsdata'])) {
            $this['propsdata_arr'] = @unserialize($this['propsdata']) ?: array();
        }
        
        //Если это нулевая комплектация, то записываем в аркинул null, чтобы брался артикул товара
        if ($this['sortn'] === "0") {
            $this['barcode'] = null;
        }
        
        // Приведение типов
        $this['num'] = (float)$this['num'];
    }
    
    /**
    * Функция срабатывает перед записью 
    * 
    * @param string $flag - строка означающая тип действия insert или update
    */
    function beforeWrite($flag)
    {
        if (!$this->dont_reset_hash) {
            $this['import_hash'] = null; // при любом изменении - сбрасываем хэш
        }
        if ($this['xml_id'] == '') unset($this['xml_id']);
        
        //Поле "photos_named_arr" - виртуальное, используется для импорта CSV, при указании у комплектаций фотографий привязанных к ней.
        if (isset($this['photos_named_arr']) && count($this['photos_named_arr'])){
            $arr = array();
            foreach($this['photos_named_arr'] as $filename){ //Переберём и найдём истенные id-шники
                
                $photo = \RS\Orm\Request::make()
                    ->from(new \Photo\Model\Orm\Image())
                    ->where(array(
                        'site_id' => \RS\Site\Manager::getSiteId(),
                        'type' => 'catalog',
                        'filename' => trim($filename),
                        'linkid' => $this['product_id'],
                    ))
                    ->object();
                if ($photo){
                    $arr[] = $photo['id'];
                }
                
            }
            $this['photos_arr'] = $arr;
        }
        
        //Преобразуем свойства из виртуального свойства _propsdata
        if ($this->isModified('_propsdata')) {
            $this['propsdata_arr'] = $this->convertPropsData($this['_propsdata']);
        }
        
        //Сериализуем необходимые поля
        if ($this->isModified('photos_arr')) {
            $this['photos'] = serialize($this['photos_arr']);
        }
        
        //Если value не установлено - установим 0                                                   
        $this_pricedata_arr = $this['pricedata_arr'];
        if(isset($this_pricedata_arr['oneprice'])){
            if(!isset($this_pricedata_arr['oneprice']['value'])){
                $this_pricedata_arr['oneprice']['value'] = 0;
            } 
        }                                                    
        if(isset($this_pricedata_arr['price'])){
            foreach($this_pricedata_arr['price'] as &$price){
                if(!isset($price['value'])){
                    $price['value'] = 0;
                }
            }
        }     
        $this['pricedata_arr'] = $this_pricedata_arr;
        if($this['pricedata_arr'] == null){
            $this['pricedata_arr'] = array();
        }   
        if ($this->isModified('pricedata_arr')){
            $pricedata_arr = $this['pricedata_arr'];
            if (empty($pricedata_arr['oneprice']['use'])) { 
                //Удаляем секцию oneprice, если цены заданы индивидуально
                unset($pricedata_arr['oneprice']);
                $this['pricedata_arr'] = $pricedata_arr;
            }            
            $this['pricedata_arr'] = $this->convertValues($this['pricedata_arr']);            
            $this['pricedata'] = serialize($this['pricedata_arr'] ?: array());
        }
        if ($this->isModified('propsdata_arr')) {
            $this['propsdata'] = serialize($this['propsdata_arr'] ?: array());
        }
        
        //Обновим сортировку у вновь созданной комплектации
        if ($flag != self::UPDATE_FLAG && !$this->isModified('sortn')) {
            $q = \RS\Orm\Request::make()
                ->select('MAX(sortn)+1 as next_sort')
                ->from($this)
                ->where(array(
                    'product_id' => (int)$this['product_id']
                ));
                    
            if ($this['xml_id']) { //Если это подкомплектация
                if (mb_strpos($this['xml_id'], "#")!==false){ //Если это подкомплектация
                   $this['sortn'] = $q->exec()->getOneField('next_sort', ($this->cml_207_no_offer_params !== null) ? 0 : 1);
                }else{ //Если основная комплектация
                   $this['sortn'] = 0; 
                }
            } else {
                $this['sortn'] = $q->exec()->getOneField('next_sort', $this->first_sortn);
            }
            
        }
        
        //Обновим общий остаток комплектации
        if ($this->isModified('stock_num')) {
            $cnt = 0;
            foreach ($this['stock_num'] as $warehouse_id => $stock_num) {
               $cnt += (float) $stock_num; 
            }
            $this['num'] = $cnt;
        }
    }
    
    /**
    * Функция срабатывает после записи комплектации 
    * 
    * @param string $flag - строка означающая тип действия insert или update
    */
    function afterWrite($flag){
        //Обновим общий остаток комплектации
        if ($this->isModified('stock_num')) {
           //Очистим остатки по складам
           \RS\Orm\Request::make()
                ->delete()           
                ->from(new \Catalog\Model\Orm\Xstock())
                ->where(array(
                    'offer_id' => $this['id'],
                    'product_id' => $this['product_id'],
                ))
                ->exec();
           
           foreach ($this['stock_num'] as $warehouse_id => $stock_num) {
              //Добавим остатки по складам  
              $offer_stock = new \Catalog\Model\Orm\Xstock(); 
              $offer_stock['product_id']   = $this['product_id'];
              $offer_stock['offer_id']     = $this['id'];
              $offer_stock['warehouse_id'] = $warehouse_id;
              $offer_stock['stock']        = $stock_num;
              $offer_stock->insert(false, array('stock'), array('product_id','offer_id','warehouse_id'));
           }
        }
    }
    
    /**
    * Конвертирует формат сведений о характеристиках комплектации
    * 
    * @param array $_propsdata ['key' => [ключ1, ключ2,...],  'value' => [значение1, значение2, ...]]
    * @return array ['ключ1' => 'значение1', 'ключ2' => 'значение2',...]
    */
    function convertPropsData($_propsdata)
    {
        $props_data_arr = array();
        if (!empty($_propsdata)) {
            foreach($_propsdata['key'] as $n => $val) {
                if ($val !== '') {
                    $props_data_arr[$val] = $_propsdata['val'][$n];
                }
            }
        }
        return $props_data_arr;
    }
    
    /**
    * Конвертирует валюты в комплектациях
    * 
    * @param array $pricedata секция pricedata из offers
    * @return array возвращает тот же с массив, только с добавленной секцией value
    */
    function convertValues(array $pricedata)
    {
        if (!$pricedata) return $pricedata;
                                                 
        if (!empty($pricedata['oneprice'])) {
            //Задана одна цена на все типы цен
            $pricedata['oneprice']['value'] = @$pricedata['oneprice']['original_value'];
            if(isset($pricedata['oneprice']['unit'])){
                if ($pricedata['oneprice']['unit'] != '%') {
                    $source_curr = \Catalog\Model\Orm\Currency::loadSingle($pricedata['oneprice']['unit']);
                    if ($source_curr['id']) {
                        $pricedata['oneprice']['value'] = \Catalog\Model\CurrencyApi::convertToBase($pricedata['oneprice']['original_value'], $source_curr);
                    }
                }
            }
        } else {
            //Для каждой цены задано персональное значение
            if(!empty($pricedata['price'])){
                foreach($pricedata['price'] as $cost_id => &$data) {
                    $data['value'] = @$data['original_value'];
                    if(isset($data['unit'])){
                        if ($data['unit'] != '%') {
                            $source_curr = \Catalog\Model\Orm\Currency::loadSingle($data['unit']);
                            if ($source_curr['id']) {
                                $data['value'] = \Catalog\Model\CurrencyApi::convertToBase($data['original_value'], $source_curr);
                            }
                        }
                    }                
                }
            }    
        }
        return $pricedata;
    }    
    
    /**
    * Загружает остатки по складам для комплектации
    * 
    * @return array
    */
    function fillStockNum()
    {
        $this['stock_num'] = \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Xstock)
            ->where(array(
                'product_id' => $this['product_id'],
                'offer_id' => $this['id']
            ))->exec()->fetchSelected('warehouse_id', 'stock');
            
        return $this['stock_num'];
    }
    
    /**
    * Возвращает JSON с параметрами комплектаций
    * 
    * @return string
    */
    function getPropertiesJson()
    {
        $result = array();
        if (is_array($this['propsdata_arr'])) {
            foreach($this['propsdata_arr'] as $key => $value) {
                $result[] = array($key, $value);
            }
        }
        return json_encode($result);
    }
    
    /**
    * Возвращает JSON с остатками на складах для данной комплектации
    * @return array
    */
    function getStickJson()
    {
        return json_encode($this['sticks'] ?: array());
    }
    
    /**
    * Получает массив из ID фото комплектации
    *
    * @return string
    */
    function getPhotosJson()
    {
        return json_encode((array)$this['photos_arr']);
    }
    
    /**
    * Получает id главной(первой отмеченной) фотографии у товара или false
    * 
    */
    function getMainPhotoId(){
       if (!empty($this['photos_arr'])){
         return $this['photos_arr'][0];  
       }
       return false;
    }

    /**
    * Получает ID фото комплектации через разделитель
    * 
    * @param string $glue - символ склейки
    * @return string
    */    
    function getImplodePhotos($glue = ',')
    {
        return implode($glue, (array)$this['photos_arr']);
    }
    
    
    /**
    * Возвращает объект единицы измерения, в котором измеряется данный продукт
    * 
    * @param string $property - имя свойства объекта Unit. Используется для быстрого обращения
    * @return Unit
    */
    function getUnit($property = null)
    {
        $unit_id = $this['unit'] ?: \RS\Config\Loader::byModule($this)->default_unit;
        $unit = new Unit($unit_id);
        return ($property === null) ? $unit : $unit[$property];
    }
    
    /**
    * Возвращает объект товара, которому принадлежит комплектация
    * 
    * @return Product
    */
    function getProduct()
    {
        return new Product($this['product_id']);
    }
    
    /**
    * Возвращает следующий по порядку артикул для комплектации
    * 
    * @param string $prefix
    * @return string
    */
    function setNextBarcode($prefix = '')
    {
        $next = \RS\Orm\Request::make()
                    ->from($this)
                    ->where(array(
                        'product_id' => $this['product_id']
                    ))->count() + 1;
        
        $this['barcode'] = $prefix.$next;
        return $this['barcode'];
    }

}

