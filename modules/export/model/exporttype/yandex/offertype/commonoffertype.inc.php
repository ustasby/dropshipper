<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Yandex\OfferType;

use \Export\Model\ExportType\AbstractOfferType as AbstractOfferType;
use \Export\Model\ExportType\Field as Field;
use \Export\Model\ExportType\Yandex\OfferType\Fields as YandexField;
use \Export\Model\Orm\ExportProfile as ExportProfile;
use \Catalog\Model\Orm\Product as Product;


abstract class CommonOfferType extends AbstractOfferType
{
    /**
    * Добавляет в выгрузки яндекса общие поля
    */
    protected function addCommonEspecialTags($ret)
    {
        $field = new Field();
        $field->name        = 'country_of_origin';
        $field->title       = t('Страна производства');
        $ret[$field->name]  = $field;

        $field = new Field();
        $field->name        = 'sales_notes';
        $field->title       = t('Замечания продаж (sales_notes)');
        $ret[$field->name]  = $field;

        $field = new Field();
        $field->name        = 'delivery';
        $field->title       = t('Возможность доставки (delivery)');
        $field->hint        = t('Характеристика Да/Нет');
        $field->type        = true;
        $ret[$field->name]  = $field;

        $field = new Field();
        $field->name        = 'pickup';
        $field->title       = t('Возможность получения товара<br>в пункте выдачи/почте России (pickup)');
        $field->hint        = t('Характеристика Да/Нет');
        $field->type        = true;
        $ret[$field->name]  = $field;

        $field = new Field();
        $field->name        = 'store';
        $field->title       = t('Возможность покупки в точке продаж (store)');
        $field->hint        = t('Характеристика Да/Нет');
        $field->type        = true;
        $ret[$field->name]  = $field;

        $field = new YandexField\DeliveryOptionsField();
        $field->name        = 'offer_delivery_cost';
        $field->title       = t('Максимальная стоимость доставки товара по вашему региону (delivery-options)');
        $field->hint        = t('для выгрузки тега "delivery-options" необходимо указать оба поля');
        $ret[$field->name]  = $field;
        
        $field = new YandexField\DeliveryOptionsField();
        $field->name        = 'offer_delivery_days';
        $field->title       = t('Срок доставки товара по вашему региону (delivery-options)');
        $field->hint        = t('для выгрузки тега "delivery-options" необходимо указать оба поля');
        $ret[$field->name]  = $field;

        $field = new Field();
        $field->name        = 'manufacturer_warranty';
        $field->title       = t('Гарантия производителя');
        $field->type        = TYPE_BOOLEAN;
        $ret[$field->name]  = $field;

        $field = new YandexField\FieldDimensions();
        $field->name        = 'dimensions_l';
        $field->title       = t('Длинна товара (число, в см)');
        $field->hint        = t('для выгрузки тега "dimensions" необходимо указать все 3 габарита');
        $ret[$field->name]  = $field;

        $field = new YandexField\FieldDimensions();
        $field->name        = 'dimensions_w';
        $field->title       = t('Ширина товара (число, в см)');
        $field->hint        = t('для выгрузки тега "dimensions" необходимо указать все 3 габарита');
        $ret[$field->name]  = $field;

        $field = new YandexField\FieldDimensions();
        $field->name        = 'dimensions_h';
        $field->title       = t('Высота товара (число, в см)');
        $field->hint        = t('для выгрузки тега "dimensions" необходимо указать все 3 габарита');
        $ret[$field->name]  = $field;

        $field = new YandexField\FieldAge();
        $field->name        = 'age';
        $field->title       = t('Возрастное ограничение');
        $field->hint        = t('Допустимые значения: 0, 6, 12, 16, 18.');
        $ret[$field->name]  = $field;

        $field = new YandexField\FieldWeight();
        $field->name        = 'weight';
        $field->title       = t('Вес товара');
        $field->hidden      = true;
        $ret[$field->name]  = $field;
        
        $field = new Field();
        $field->name        = 'adult';
        $field->title       = t('Товар для взрослых');
        $field->hint        = t('Характеристика Да/Нет');
        $field->type        = true;
        $ret[$field->name]  = $field;
        
        $field = new Field();
        $field->name        = 'min-quantity';
        $field->title       = t('Минимальное количество для заказа');
        $ret[$field->name]  = $field;
        
        $field = new Field();
        $field->name        = 'step-quantity';
        $field->title       = t('Шаг количества товара при заказе');
        $ret[$field->name]  = $field;
        
        $field = new Field();
        $field->name        = 'cpa';
        $field->title       = t('Участие в "заказе на Маркете"');
        $field->hint        = t('Характеристика Да/Нет');
        $field->type        = true;
        $field->boolAsInt   = true;
        $ret[$field->name]  = $field;
        
        $field = new YandexField\FieldRec();
        $field->name        = 'rec';
        $field->title       = t('Рекомендованные товары');
        $field->hidden      = true;
        $ret[$field->name]  = $field;
        
        return $ret;
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
        $writer->startElement("offer");
            $this->fireOfferEvent('beforewriteoffer', $profile, $writer, $product, $offer_index);
            
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
            
            $this->fireOfferEvent('writeoffer', $profile, $writer, $product, $offer_index);
        $writer->endElement();
    }
}