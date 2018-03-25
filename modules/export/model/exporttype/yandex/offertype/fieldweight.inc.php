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
class FieldWeight extends Field implements ComplexFieldInterface
{
    /**
     * Добавляет необходимую структуру тегов в итоговый XML
     */
    function writeSomeTags(\XMLWriter $writer, $profile, $product, $offer_index = null)
    {
        $weight = ($product->getWeight($offer_index))/1000;
        if ($weight > 0){
            $writer->startElement('weight');
            $writer->text($weight);
            $writer->endElement();
        }
    }
}