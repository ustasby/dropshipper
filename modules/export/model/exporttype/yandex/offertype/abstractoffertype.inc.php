<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Yandex\OfferType;
use \Export\Model\Orm\ExportProfile as ExportProfile;
use \Catalog\Model\Orm\Product as Product;


abstract class AbstractOfferType   
{
    
    /**
    * Получить список "особенных" полей для данного типа описания
    * Возвращает массив объектов класса Field.
    * 
    * @return Filed[]
    */
    static public function getEspecialTags()
    {
        return array();
    }
    
    /**
    * Дополняет список "особенных" полей для данного типа описания, полученными через хук
    * Возвращает модифицированный массив объектов полей.
    * 
    * @param array $fields - массив полей
    * @return $fields
    */
    static public function addCustomEspecialTags($fields)
    {
        $class_name_pieces = explode('\\', get_called_class());
        $event_name = 'export.yandex.getespecialtags.' . strtolower(end($class_name_pieces));            
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
    public function writeOffer(ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index)
    {
        if ($profile->only_available && $product->getNum($offer_index) < 1) {
            return;
        }
        
        $this->getEspecialTags();
        if($offer_index !== false && !count($product['offers'])){
            throw new \Exception(t('Товарные предложения отсутсвуют, но передан аргумент offer_index'));
        }
        
        $writer->startElement("offer");
        $writer->writeAttribute('id', $product->id.'x'.$offer_index);  
        
        // Добавляем group_id, если у товара есть комплектации
        if ($product->isOffersUse()) {
            $writer->writeAttribute('group_id', $product->id);  
        }
        
        // Если указан тип описания
        if(isset($profile->data['offer_type'])){
            // Если это не Simple описание, то у тега offer добавляем аттрибут type
            if($profile->data['offer_type'] != 'simple'){
                $writer->writeAttribute('type', $profile->data['offer_type']);  
            }
        }
        
        // Берем цену по-умолчанию
        $prices = $product->getOfferCost($offer_index, $product['xcost']);
        $price = $prices[ \Catalog\Model\CostApi::getDefaultCostId() ];
        if ($old_cost_id = \Catalog\Model\CostApi::getOldCostId()) {
            $old_price = $prices[ $old_cost_id ];
        }

        // Определяем доступность товара
        $available = $product->getNum($offer_index) > 0 && $price > 0;

        //Дополнительные параметры адресе страницы
        $url_params = false;
        if (!empty($profile['url_params'])){
            $url_params = htmlspecialchars_decode($profile['url_params']);
        }
        $writer->writeAttribute('available', $available ? 'true' : 'false');  
        $writer->writeElement('url', $product->getUrl(true). ($url_params ? "?".$url_params : "") .( $offer_index ? '#'.$offer_index : '' ));  
        $writer->writeElement('price', $price);  
        if (!empty($old_price) && $old_price > $price) {
            $writer->writeElement('oldprice', $old_price);  
        }
        $writer->writeElement('currencyId', \Catalog\Model\CurrencyApi::getDefaultCurrency()->title);
        $sku = $product->getSKU($offer_index);
        if (!empty($sku)) {
            $writer->writeElement('barcode', $product->getSKU($offer_index));
        }
        $writer->writeElement('categoryId', $product->maindir);
        $exist = !empty($product['offers']['items'][$offer_index]['photos_arr']);      //проверка на наличие присвоенных к офферу изображений
        if ($exist == false){
            $this->writeProductPictures($product,$profile,$writer);//заполняем всеми изображениями товара(максимум 10)
        } else {
            $this->writeOfferPictures($product,$offer_index,$profile,$writer);//заполняем  изображениями оффера (максимум 10)
        }
        // Запись "особенных" элементов для каждого конкретного типа описания
        $this->writeEspecialOfferTags($profile, $writer, $product, $offer_index);
        // Записываем свойства товара в теги <param>
        $prop_list = $product->getVisiblePropertyList(true, true);
        foreach($prop_list as $group){
            if(!isset($group['properties'])) continue;

            foreach($group['properties'] as $prop){
                $value = $prop->textView();
                if (trim($value) !== '') {
                    $writer->startElement('param');

                    if($prop->name_for_export){
                        $writer->writeAttribute('name', $prop->name_for_export);
                    }elseif(!isset($prop->name_for_export)){
                        $writer->writeAttribute('name', $prop->title);
                    }
                    // Если у свойства товара указана единица измерения
                    if($prop->unit){
                        $writer->writeAttribute('unit', $prop->unit);
                    }
                    $writer->text($value);  
                    $writer->endElement();  
                }
            }
        }
        //Записываем свойства комплектации в теги <param>
        if (isset($product['offers']['items'][$offer_index])) {
            $offer = $product['offers']['items'][$offer_index];
            foreach((array)$offer['propsdata_arr'] as $key => $value) {
                $writer->startElement('param');
                    $name_for_export = $this->getExportName($product,$key);
                if(isset($name_for_export)){
                    $writer->writeAttribute('name',$name_for_export);
                }else{
                    $writer->writeAttribute('name',$key);
                }

                if($this->getUnit($product, $key)){
                    $writer->writeAttribute('unit', $this->getUnit($product, $key));
                }
                $writer->text($value);
                $writer->endElement();
            }
        }
        $writer->endElement();
        $writer->flush();
    }


    /**
     * Запись тега name у характеристики
     * @param $key  - title характеристики
     */

    function getExportName($product,$key)
    {
        static $cachename = array();
        $cachename['site_id'] = \RS\Site\Manager::getSiteId();
        file_put_contents('yandex.txt',var_export( $cachename ,true));
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
                    return $value === 'есть' ? '1' : (!isset($value)? '1':'0');
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
        foreach(static::getEspecialTags() as $field)
        {

            $this->writeElementFromFieldmap($field, $profile, $writer, $product, $offer_index);
        }
    }

}