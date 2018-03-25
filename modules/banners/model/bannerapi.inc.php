<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Model;

class BannerApi extends \RS\Module\AbstractModel\EntityList
{
    function __construct()
    {
        parent::__construct(new \Banners\Model\Orm\Banner, 
        array(
            'multisite' => true
        ));
    }
    
    public function setFilter($key, $value = null, $type = '=', $prefix = 'AND', array $options = array())
    {
        if ($key == 'zone_id') {
            $q = $this->queryObj();
            if (!$q->issetTable(new Orm\Xzone())) {
                $q->join(new Orm\Xzone(), "{$this->def_table_alias}.id = X.banner_id", 'X');
            }

            parent::setFilter('X.zone_id', $value, $type, 'AND');
            return true;
        }

        return parent::setFilter($key, $value, $type, $prefix, $options);
    }
    
    /**
    * Обновляет свойства у группы объектов
    * 
    * @param array $data - ассоциативный массив со значениями обновляемых полей
    * @param array $ids - список id объектов, которые нужно обновить
    * @return integer - возвращает количество обновленных элементов
    */    
    function multiUpdate(array $data, $ids = array())
    {
        if (isset($data['xzone'])) {
            \RS\Orm\Request::make()->delete()
                ->from(new Orm\Xzone())
                ->whereIn('banner_id', $ids)->exec();
            
            if (!empty($data['xzone'])) {
                foreach ($ids as $id) {
                    foreach($data['xzone'] as $zone_id) {
                        $zone_item = new Orm\Xzone();
                        $zone_item['banner_id'] = $id;
                        $zone_item['zone_id'] = $zone_id;
                        $zone_item->insert();
                    }
                }
            }
            unset($data['xzone']);
        }        
        
        return parent::multiUpdate($data, $ids);
    }
}