<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

/**
* API функции для работы с налогами
*/
class TaxApi extends \RS\Module\AbstractModel\EntityList
{
    protected static
        $all_tax_ids,
        $cache_dir_tax,
        $cache_tax;
    
        
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Tax,
        array(
            'nameField' => 'title'
        ));
    }
    
    /**
    * Возвращает id налогов, которые могут быть применены к товару
    * 
    * @param \Catalog\Model\Orm\Product $product
    * @return array
    */
    public static function getProductTaxIds(\Catalog\Model\Orm\Product $product)
    {
        $tax_ids = $product['tax_ids'];
        if ($tax_ids == 'category') {
            $dir_id = $product['maindir'];
            if (!isset(self::$cache_dir_tax[$dir_id])) {
                self::$cache_dir_tax[$dir_id] = $product->getMainDir()->tax_ids;
            }
            $tax_ids = self::$cache_dir_tax[$dir_id];
        }
        
        if ($tax_ids == 'all') {
            if (!isset(self::$all_tax_ids)) {
                self::$all_tax_ids  = array_keys(self::staticSelectList());
            }
            $ids = self::$all_tax_ids;
        } else {
            $ids = explode(',', $tax_ids);
        }
        return $ids;
    }
    
    /**
    * Возвращает список налогов, которые применяются к товару
    * 
    * @param \Catalog\Model\Orm\Product $product - загруженный товар
    * @param \Users\Model\Orm\User $user
    * @param Orm\Address $address - адрес, который необходим для расчета налогов
    * @return array
    */
    public static function getProductTaxes(\Catalog\Model\Orm\Product $product, \Users\Model\Orm\User $user, Orm\Address $address, array $tax_id_list = null)
    {
        $address_id = $address['country_id'].':'.$address['region_id'];
        if ($tax_id_list === null) {
            $tax_id_list = self::getProductTaxIds($product);
        }
        return self::getTaxesByIds($tax_id_list, $user, $address);
    }
    
    /**
    * Возвращает список налогов, которые применяются к доставке
    * 
    * @param \Shop\Model\Orm\Delivery $delivery - доставка
    * @param \Users\Model\Orm\User $user
    * @param Orm\Address $address - адрес, который необходим для расчета налогов
    * @return array
    */
    public static function getDeliveryTaxes(\Shop\Model\Orm\Delivery $delivery, \Users\Model\Orm\User $user, Orm\Address $address, array $tax_id_list = null)
    {
        if ($tax_id_list === null) {
            $tax_id_list = ($delivery['tax_ids']) ?: array();
        }
        return self::getTaxesByIds($tax_id_list, $user, $address);
    }
    
    /**
    * Возвращает список налогов до списку id
    * 
    * @param array $tax_id_list - id налогов
    * @param \Users\Model\Orm\User $user
    * @param Orm\Address $address - адрес, который необходим для расчета налогов
    * @return array
    */
    protected static function getTaxesByIds(array $tax_id_list, \Users\Model\Orm\User $user, Orm\Address $address)
    {
        $address_id = $address['country_id'].':'.$address['region_id'];
        $tax_ids = implode(',', $tax_id_list);
        if (!isset(self::$cache_tax[$address_id][$tax_ids])) {
            self::$cache_tax[$address_id][$tax_ids] = array();
            if (count($tax_id_list)) {
                $taxes = \RS\Orm\Request::make()
                    ->from(new Orm\Tax())
                    ->whereIn('id', $tax_id_list)
                    ->objects();
                    
                foreach($taxes as $tax) {
                    if ($tax->canApply($user, $address)) {
                        self::$cache_tax[$address_id][$tax_ids][] = $tax;
                    }
                }
            }
        }
        return self::$cache_tax[$address_id][$tax_ids];
    }
}