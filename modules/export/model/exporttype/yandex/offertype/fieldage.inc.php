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
class FieldAge extends Field implements ComplexFieldInterface
{
    /**
    * Добавляет необходимую структуру тегов в итоговый XML
    */
    function writeSomeTags(\XMLWriter $writer, $profile, $product, $offer_index = null){
        $export_type_object = $profile->getTypeObject();
        if (isset($export_type_object['fieldmap'][$this->name])) {
            if (!empty($export_type_object['fieldmap'][$this->name]['prop_id'])
                && $export_type_object['fieldmap'][$this->name]['prop_id'] != -1
            ) {

                $prop_id = (int)$export_type_object['fieldmap'][$this->name]['prop_id'];
                $value = $product->getPropertyValueById($prop_id);
            } else {
                $value = $export_type_object['fieldmap'][$this->name]['value'];
            }
           if($value){
                $writer->startElement('age');
                $writer->writeAttribute('unit', "year");
                $writer->text($value);
                $writer->endElement();
          }

        }
    }
}