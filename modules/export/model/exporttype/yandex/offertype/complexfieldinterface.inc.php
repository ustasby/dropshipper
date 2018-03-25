<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Yandex\OfferType;

/**
* Интерйфейс поля, которое может добавлять сложную структуру XML данных
*/
interface ComplexFieldInterface
{
    /**
    * Добавляет необходимую структуру тегов в итоговый XML
    */
    function writeSomeTags(\XMLWriter $writer, $profile, $product, $offer_index = null);
}
