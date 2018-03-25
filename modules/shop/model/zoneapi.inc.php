<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class ZoneApi extends \RS\Module\AbstractModel\EntityList
{
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Zone,
        array(
            'nameField' => 'title',
            'multisite' => true,
            'defaultOrder' => 'title'
        ));
    }
    
    
    static public function getZonesByRegionId($region_id, $country_id = null, $city_id = null)
    {
        $q = \RS\Orm\Request::make()
            ->from(new \Shop\Model\Orm\Xregion())
            ->where('zone_id IS NOT NULL')
            ->where(array('region_id' => $region_id));
        
        if ($country_id) {
            $q->where(array('region_id' => $country_id), null, 'OR');
        }
        
        if ($city_id) {
            $q->where(array('region_id' => $city_id), null, 'OR');
        }

        return $q->exec()->fetchSelected(null, 'zone_id');
    }    
    
    static public function getZoneByTitle($zone_title)
    {
        return \Shop\Model\Orm\Zone::loadByWhere(array('title' => $zone_title));
    }    
}
?>
