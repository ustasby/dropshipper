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


class Simple extends CommonOfferType
{
    /**
    * Возвращает название типа описания
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Упрощенное');
    }
    
    /**
    * Возвращает идентификатор данного типа описания. (только англ. буквы)
    * 
    * @return string
    */
    public function getShortName()
    {
        return 'simple';
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
    function writeEspecialOfferTags(ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index)
    {
        $writer->writeElement('name', $product->title.' '.(($offer_index !== false && !$profile->no_export_offers && !$profile->no_export_offers_title) ? $product->getOfferTitle($offer_index) : '') );
        if ($vendor = $product->getBrand()->title) {
            $writer->writeElement('vendor', $vendor);
        }
        $writer->writeElement('description', $product->short_description);
        
        parent::writeEspecialOfferTags($profile, $writer, $product, $offer_index);
    }
}
