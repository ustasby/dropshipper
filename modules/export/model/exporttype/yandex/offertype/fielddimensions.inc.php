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
class FieldDimensions extends Field implements ComplexFieldInterface
{
    /**
    * Добавляет необходимую структуру тегов в итоговый XML
    */
    function writeSomeTags(\XMLWriter $writer, $profile, $product, $offer_index = null){
        $export_type_object = $profile->getTypeObject();
        
        // Запись произойдёт только если заданы все 3 габарита, и только 1 раз 
        if ($this->name == 'dimensions_l'
                && !empty($export_type_object['fieldmap']['dimensions_l']['prop_id'])
                && !empty($export_type_object['fieldmap']['dimensions_w']['prop_id'])
                && !empty($export_type_object['fieldmap']['dimensions_h']['prop_id']) ) {
            
            $dimensions = array();        
            $dimensions[] = $this->getValue('dimensions_l', $export_type_object, $product);
            $dimensions[] = $this->getValue('dimensions_w', $export_type_object, $product);
            $dimensions[] = $this->getValue('dimensions_h', $export_type_object, $product);
            
            $writer->writeElement('dimensions', implode('/', $dimensions));
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