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
class FieldRec extends Field implements ComplexFieldInterface
{
    static
        $count_offers = null; 
    
    /**
    * Добавляет необходимую структуру тегов в итоговый XML
    */
    function writeSomeTags(\XMLWriter $writer, $profile, $product, $offer_index = null) {
        if (self::$count_offers === null) {
            self::$count_offers = \RS\Orm\Request::make()
                ->select('product_id, count(*) as count')
                ->from(new \Catalog\Model\Orm\Offer())
                ->groupby('product_id')
                ->where(array(
                    'site_id' =>\RS\Site\Manager::getSiteId()
                ))
                ->exec()->fetchSelected('product_id', 'count');
        }
        
        if (!empty($product['recommended_arr']['product'])) {
            $recommended = array_slice($product['recommended_arr']['product'], 0, 30); // не более 20 рекомендованных товаров
            $rec_arr = array();
            foreach($recommended as $item) {
                if (isset(self::$count_offers[$item])) {
                    $rec_arr[] = (self::$count_offers[$item] > 1) ? $item.'x0' : $item.'x';
                }
            }
            $rec = implode(',', $rec_arr);
            
            $writer->writeElement('rec', $rec);
        }
    }
}