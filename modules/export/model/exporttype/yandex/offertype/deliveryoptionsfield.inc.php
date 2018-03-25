<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Yandex\OfferType;
use \RS\Orm\Type;

/**
* Структура данных, описывающая поле в экспортируемом XML документе
*/
class DeliveryOptionsField extends Field implements ComplexFieldInterface
{
    /**
    * Добавляет необходимую структуру тегов в итоговый XML
    */
    function writeSomeTags(\XMLWriter $writer, $profile, $product, $offer_index = null){
        $export_type_object = $profile->getTypeObject();
        
        // Запись произойдёт только если заданы обе характеристики, и только 1 раз 
        if($this->name == 'offer_delivery_cost' && !empty($export_type_object['fieldmap'][$this->name]['prop_id'])){
            $cost_value = $this->getValue('offer_delivery_cost', $export_type_object, $product);
            $days_value = $this->getValue('offer_delivery_days', $export_type_object, $product);
            
            if (is_numeric($cost_value)) {
                $writer->startElement('delivery-options');
                    $writer->startElement('option');
                        $writer->writeAttribute('cost', $cost_value);
                        $writer->writeAttribute('days', $days_value);
                    $writer->endElement();
                $writer->endElement();  
            }
        }
    }
    
    /**
    * Возвращает значение свойства товара
    */
    function getValue($name, $export_type_object, $product){
        if(!empty($export_type_object['fieldmap'][$name]['prop_id'])){
            $property_id = (int) $export_type_object['fieldmap'][$name]['prop_id']; // Идентификатор свойстава товара
            $default_value = $export_type_object['fieldmap'][$name]['value']; // Значение по умолчанию
            $value = $product->getPropertyValueById($property_id); // Получаем значение свойства товара
            // Выводим значение свойства, либо значение по умолчанию
            return $value === null ? $default_value : $value;
        }else{
            return null;
        }
    } 
}