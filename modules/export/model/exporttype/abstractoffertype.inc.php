<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType;

use \Export\Model\Orm\ExportProfile as ExportProfile;
use \Catalog\Model\Orm\Product as Product;


abstract class AbstractOfferType
{
    protected
        $export_type_name;
    
    /**
    * Возвращает название типа описания
    * 
    * @return string
    */
    abstract function getTitle();
    
    /**
    * Возвращает идентификатор данного типа описания. (только англ. буквы)
    * 
    * @return string
    */
    abstract function getShortName();
    
    /**
    * Устанавливает идентификатор типа экспорта
    * 
    * @param string $export_type_name - идентификатор типа экспорта
    * @return void
    */
    public function setExportTypeName($export_type_name)
    {
        $this->export_type_name = $export_type_name;
    }
    
    /**
    * Получить список "особенных" полей для данного типа описания
    * Возвращает массив объектов класса Field.
    * 
    * @param string $exporttype_name - короткое название типа экспорта
    * @return Filed[]
    */
    public function getEspecialTags()
    {
        $fields = array();
        // Начинаем с общих полей типа экспорта
        $fields = $this->addCommonEspecialTags($fields);
        // Добавляем поля, персональные для типа описания
        $fields = $this->addSelfEspecialTags($fields);
        // Добавим дополнительные поля через событие
        $fields = $this->addCustomEspecialTags($fields);
        
        return $fields;
    }
    
    /**
    * Дополняет список "особенных" полей, общими для всех типов описания данного типа экспорта
    * 
    * @param $fields - массив "особенных" полей
    * @return Filed[]
    */
    protected function addCommonEspecialTags($fields)
    {
        return $fields;
    }
    
    /**
    * Дополняет список "особенных" полей, персональными для данного типа описания
    * 
    * @param $fields - массив "особенных" полей
    * @return Filed[]
    */
    protected function addSelfEspecialTags($fields)
    {
        return $fields;
    }
    
    /**
    * Дополняет список "особенных" полей для данного типа описания, полученными через событие
    * Возвращает модифицированный массив объектов полей.
    * 
    * @param array $fields - массив полей
    * @return $fields
    */
    protected function addCustomEspecialTags($fields)
    {
        $class_name_pieces = explode('\\', get_called_class());
        $offer_type_name = strtolower(end($class_name_pieces));
        $event_name = 'export.' . $this->export_type_name . '.getespecialtags.' . $offer_type_name;            
        $result = \RS\Event\Manager::fire($event_name, $fields);
        return $result->getResult();
    }
    
    /**
    * Запись товарного предложения
    * 
    * @param ExportProfile $profile
    * @param \XMLWriter $writer
    * @param mixed $product
    * @param mixed $offer_index
    */
    abstract public function writeOffer(ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index);


    /**
    * Запись тега name у характеристики
    * @param $key  - title характеристики
    */

    public function getExportName($product,$key)
    {
        static $cachename = array();
        $cachename['site_id'] = \RS\Site\Manager::getSiteId();
        if (!isset($cachename[$key])) {
            /**
            * @var \Catalog\Model\Orm\Product $product
            */
            if (isset ($product['multioffers']['levels'])) {
                foreach ($product['multioffers']['levels'] as $item) {
                    if ($item['title'] == $key) {
                        $prop = \Catalog\Model\Orm\Property\Item::loadByWhere(array(
                            'id' => $item['prop_id'],
                        ));
                        $cachename[$key] = $prop->name_for_export;
                    }
                }
            }
            else {
                $prop = \Catalog\Model\Orm\Property\Item::loadByWhere(array(
                    'title' => $key,
                    'site_id'=>$cachename['site_id'],
                ));
                $cachename[$key] = $prop->name_for_export;
            }
        }
        
        return $cachename[$key];
    }


    /**
     * Получение значения unit для экспорта
     * @param Product $product
     * @param  $key  название характеристики у оффера
     */
    function getUnit($product, $key)
    {
        static $cache = array();
        $cache['site_id'] = \RS\Site\Manager::getSiteId();
        if (!isset($cache[$key])) {
            /**
             * @var \Catalog\Model\Orm\Product $product
             */
            if (isset ($product['multioffers']['levels'])) {
                foreach ($product['multioffers']['levels'] as $item) {
                    if ($item['title'] == $key) {
                        $prop = \Catalog\Model\Orm\Property\Item::loadByWhere(array(
                            'id' => $item['prop_id'],
                        ));
                        $cache[$key] = $prop->unit_export;
                    }
                }
            }
            else {
                $prop = \Catalog\Model\Orm\Property\Item::loadByWhere(array(
                    'title' => $key,
                    'site_id'=>$cache['site_id'],
                ));
                $cache[$key] = $prop->unit_export;
            }
        }
        return $cache[$key];
    }

    /**
     * Выгрузка всех изображений товара, если у оффера не указаны конкретные изображения
     *
     */
    function writeProductPictures($product,$profile,$writer)
    {
        $n = 0;
        foreach($product->images as $image){
            if($image instanceof \Photo\Model\Orm\Image && $n<10) {
                //Yandex допускает не более 10 фото на одно предложение
                $image_url = ($profile['export_photo_originals']) ? $image->getOriginalUrl(true) : $image->getUrl(800, 800, 'axy', true);
                $writer->writeElement('picture', $image_url);
                $n++;
            }
        }
    }




    /**
     * Выгрузка изображений, согласно привязки к офферу
     *
     */
    function writeOfferPictures($product,$offer_index,$profile,$writer)
    {
        foreach($product['offers']['items'] as $item){
            if ($item['sortn'] == $offer_index){
                $n = 0;
                foreach ($item['photos_arr'] as $imageid ) {

                    $image = new \Photo\Model\Orm\Image($imageid);
                    if  ($n<10){
                        $image_url = ($profile['export_photo_originals']) ? $image->getOriginalUrl(true) : $image->getUrl(800, 800, 'axy', true);
                        $writer->writeElement('picture', $image_url);
                        $n++;
                    }
                }
                break;
            }
        }
    }


    /**
    * Запись элемента в соответсвии с настройками сопоставления полей экспорта свойствам товара
    * 
    * @param Field $field
    * @param ExportProfile $profile
    * @param \XMLWriter $writer
    * @param Product $product
    * @param int $offer_index
    */
    protected function writeElementFromFieldmap(Field $field, ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index = null)
    {
        if ($field instanceof ComplexFieldInterface) {
            $field->writeSomeTags($writer, $profile, $product, $offer_index);
        } else {
            $value = $this->getElementFromFieldmap($field, $profile, $writer, $product);
            if ($value!==null){
                $writer->writeElement($field->name, $value);
            }
        }
    }
    
    /**
    * Получить элемент в соответсвии с настройками сопоставления полей экспорта свойствам товара
    * 
    * @param Field $field
    * @param ExportProfile $profile
    * @param \XMLWriter $writer
    * @param Product $product
    * @return string
    */    
    protected function getElementFromFieldmap(Field $field, ExportProfile $profile, \XMLWriter $writer, Product $product)
    {
        // Получаем объект типа экспорта (в нем хранятся соотвествия полей - fieldmap)
        $export_type_object = $profile->getTypeObject();
        if(!empty($export_type_object['fieldmap'][$field->name]['prop_id'])){
            // Идентификатор свойстава товара
            $property_id = (int) $export_type_object['fieldmap'][$field->name]['prop_id'];
            // Значение по умолчанию
            $default_value = $export_type_object['fieldmap'][$field->name]['value'];
            // Получаем значение свойства товара
            $value = $product->getPropertyValueById($property_id);
            // Если яндекс ожидает строку (true|false)
            if($field->type == TYPE_BOOLEAN){
                // Если значение свойства 1 или непустая строка - выводим 'true', в противном случае 'false'

                if($field->boolAsInt ){
                    return $value === 'есть' ? '1' : (!isset($value) ? '1' : '0');
                }
                if (!$value && !$default_value){
                    return "false";
                }
                return "true";
            }
            else{
                // Выводим значение свойства, либо значение по умолчанию
                return $value === null ? $default_value : $value;
            }
        }
        return null;
    }
    
    /**
    * Запись "Особенных" полей, для данного типа описания
    * Перегружается в потомке. По умолчанию выводит все поля в соответсвии с fieldmap
    * 
    * @param ExportProfile $profile
    * @param \XMLWriter $writer
    * @param Product $product
    * @param mixed $offer_index
    */
    protected function writeEspecialOfferTags(ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index)
    {
        foreach($this->getEspecialTags() as $field) {
            $this->writeElementFromFieldmap($field, $profile, $writer, $product, $offer_index);
        }
    }
    
    /**
    * Событие, которое вызывается при записи каждого товарного предложения
    * 
    * @param string $event_name - уникальная часть итогового имени события
    * @param \Export\Model\Orm\ExportProfile $profile - объект профиля экспорта
    * @param \XMLWriter $writer - объект библиотеки для записи XML
    * @param \Catalog\Model\Orm\Product $product - объект товара
    * @param integer $offer_index - индекс комплектации для отображения
    */
    protected function fireOfferEvent($event_name, ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index)
    {
        $event_name = "export.{$profile['class']}.$event_name";
        $export_params = array(
            'profile' => $profile,
            'writer' => $writer,
            'product' => $product,
            'offer_index'  => $offer_index
        );
        \RS\Event\Manager::fire("export.{$profile['class']}.$event_name", $export_params);
    }
}
