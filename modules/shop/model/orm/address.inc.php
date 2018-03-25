<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;

/**
* Адреса доставок пользователя
*/
class Address extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_address';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'user_id' => new Type\Integer(array(
                'maxLength' => '11',
                'default' => 0,
                'description' => t('Пользователь')
            )),
            'order_id' => new Type\Integer(array(
                'maxLength' => '11',
                'default' => 0,
                'description' => t('Заказ пользователя')
            )),
            'zipcode' => new Type\Varchar(array(
                'maxLength' => '20',
                'description' => t('Индекс')
            )),
            'country' => new Type\Varchar(array(
                'maxLength' => '100',
                'description' => t('Страна')
            )),
            'region' => new Type\Varchar(array(
                'maxLength' => '100',
                'description' => t('Регион')
            )),
            'city' => new Type\Varchar(array(
                'maxLength' => '100',
                'description' => t('Город')
            )),
            'address' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Адрес')
            )),
            'house' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Дом')
            )),
            'block' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Корпус')
            )),
            'apartment' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Квартира')
            )),
            'entrance' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Подъезд')
            )),
            'entryphone' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Домофон')
            )),
            'floor' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Этаж')
            )),
            'subway' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Станция метро')
            )),
            'city_id' => new Type\Integer(array(
                'description' => t('ID города')
            )),
            'region_id' => new Type\Integer(array(
                'description' => t('ID региона')
            )),
            'country_id' => new Type\Integer(array(
                'description' => t('ID страны')
            )),
            'deleted' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Удалён?'),
                'default' => 0,
                'CheckboxView' => array(1, 0),
            )),
        ));
    }
    
    function beforeWrite($flag)
    {
        $regionApi = new \Shop\Model\RegionApi();
        
        $this->updateAddressTitles();
        
        // Попробуем найти город по названию и запишем id города
        $regionApi->setFilter('title', $this['city']); 
        $regionApi->setFilter('site_id', \RS\Site\Manager::getSiteId());
        $regionApi->setFilter('is_city', 1);
        $city = $regionApi->getFirst(); 
        $this['city_id'] = $city ? $city['id'] : null;
    }
    
    /**
    * Корректирует название страны и региона в соответствии с id
    */
    function updateAddressTitles()
    {
        $regionApi = new \Shop\Model\RegionApi();
        
        $country   = $regionApi->getOneItem($this['country_id']);
        $region    = $regionApi->getOneItem($this['region_id']);
        // Скорректируем название страны
        $this['country'] = $country['title'];
        // Скорректируем область
        if (!empty($this['region_id']) && $region['parent_id'] == $country['id']) {
            $this['region'] = $region['title'];
        } else {
            $this['region_id'] = 0;
        }
    }
    
    /**
    * Возвращает полный адрес в одну строку
    *
    * @param bool $full - если true, то возвращается полный адрес с индексом, страной, регионом, городом, полный адрес, ...
     * В противном случае возвращается только полный адрес, дом, корпус, подъезд, этаж, домофон.
    * @return string
    */
    function getLineView($full = true)
    {
        if ($full) {
            $keys = array('zipcode', 'country', 'region', 'city', 'address');

            $parts = array();
            foreach ($this->getValues() as $key => $val) {
                if (in_array($key, $keys) && !empty($val)) $parts[] = $val;
            }
            $address_base = trim(implode(', ', $parts), ',');
        } else {
            $address_base = $this['address'];
        }

        $parts2 = array($address_base);
        if ($this['house']) {
            $parts2[] = $this['house'].($this['block'] ?  '/'.$this['block'] : '');
        }

        if ($this['apartment']) $parts2[] = 'кв./офис '.$this['apartment'];
        if ($this['entrance']) $parts2[] = t('подъезд %0', array($this['entrance']));
        if ($this['floor']) $parts2[] = t('этаж %0', array($this['floor']));
        if ($this['entryphone']) $parts2[] = t('домофон %0', array($this['entryphone']));

        return trim(implode(', ', $parts2));
    }

    /**
     * Возвращает объект страны
     *
     * @return Region
     */
    function getCountry()
    {
        return new \Shop\Model\Orm\Region($this['country_id']);
    }
    
    /**
    * Возвращает объект региона
    * 
    * @return Region
    */
    function getRegion()
    {
        return new \Shop\Model\Orm\Region($this['region_id']);
    }
    
    /**
    * Возвращает объект города
    * 
    * @return Region
    */
    function getCity()
    {
        return new \Shop\Model\Orm\Region($this['city_id']);
    }
}
