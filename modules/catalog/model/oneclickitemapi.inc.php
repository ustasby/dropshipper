<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model;
use Main\Model\NoticeSystem\HasMeterInterface;


/**
 * Класс содержит API функции для работы с объектом купить в 1 клик
 * @ingroup Catalog
 */
class OneClickItemApi extends \RS\Module\AbstractModel\EntityList
                        implements HasMeterInterface
{
    const
        METER_ONECLICK = 'rs-admin-menu-oneclick';

    function __construct()
    {
        parent::__construct(new \Catalog\Model\Orm\OneClickItem(), array(
            'multisite' => true
        ));
    }


    /**
     * Возвращает API по работе со счетчиками
     *
     * @return \Main\Model\NoticeSystem\MeterApiInterface
     */
    function getMeterApi($user_id = null)
    {
        return new \Main\Model\NoticeSystem\MeterApi($this->obj_instance,
            self::METER_ONECLICK,
            $this->getSiteContext(),
            $user_id);
    }
    
    /**
    * Подготавливает сериализованный массив из товаров
    * 
    * @param array $products - массив товаров и выбранными комплектациями
    * @return string
    */
    function prepareSerializeTextFromProducts($products)
    {
        $arr = array();
        foreach ($products as $product){
            $arr[] = array(
                'id' => $product['id'],
                'title' => $product['title'],
                'barcode' => $product['barcode'],
                'offer_fields' => $product['offer_fields']
            );
        }
        return serialize($arr);
    }
      
}