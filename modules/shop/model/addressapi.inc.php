<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class AddressApi extends \RS\Module\AbstractModel\EntityList
{
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Address, array(
            'multisite' => true
        ));
    }
    
    /**
    * Возвращает адрес по id города
    * 
    * @param integer $city_id
    * @return Orm\Address
    */
    static function getAddressByCityid($city_id)
    {
        $city = new Orm\Region($city_id);
        if (!$city['is_city']) {
            return false;
        }
        $region = $city->getParent();
        $country = $region->getParent();
        
        $address = new Orm\Address();
        $address['zipcode']    = $city['zipcode'];
        $address['city']       = $city['title'];
        $address['city_id']    = $city['id'];
        $address['region']     = $region['title'];
        $address['region_id']  = $region['id'];
        $address['country']    = $country['title'];
        $address['country_id'] = $country['id'];
        
        return $address;
    }
}
