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
* Регион доставки
*/
class Region extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_regions';
    
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'description' => t('Название')
                )),
                'parent_id' => new Type\Integer(array(
                    'description' => t('Родитель'),
                    'list' => array(array('\Shop\Model\RegionApi', 'selectList'))
                )),
                'zipcode' => new Type\Varchar(array(
                    'maxLength' => 20,
                    'visible' => false,
                    'cityVisible' => true,
                    'description' => t('Индекс'),
                )),
                'is_city' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Является городом?'),
                    'checkboxview' => array(1,0),
                    'visible' => false,
                    'default' => 0,
                )),
                'area' => new Type\Varchar(array(
                    'description' => t('Муниципальный район'),
                    'visible' => false,
                    'cityVisible' => true,
                )),
                'sortn' => new Type\Integer(array(
                    'description' => t('Порядок'),
                    'default' => 100,
                    'hint' => t('Чем меньше число, тем выше элемент в списке. Если у двух элементов одинаковый порядок, то сортировка происходит по Наименованию в алфавитном порядке')
                )),
            t('Срок доставки'),
                'russianpost_arriveinfo' => new Type\Varchar(array(
                    'description' => t('Срок доставки Почтой России (строка)'),
                    'visible' => false,
                    'cityVisible' => true,
                )),
                'russianpost_arrive_min' => new Type\Varchar(array(
                    'description' => t('Минимальное количество дней доставки Почтой России'),
                    'maxLength' => 10,
                    'visible' => false,
                    'cityVisible' => true,
                )),
                'russianpost_arrive_max' => new Type\Varchar(array(
                    'description' => t('Максимальное количество дней доставки Почтой России'),
                    'maxLength' => 10,
                    'visible' => false,
                    'cityVisible' => true,
                ))
        ));
        
        $this->addIndex(array('site_id', 'parent_id', 'is_city'));
    }
    
    /**
    * Удаление региона
    * 
    */
    function delete()
    {
        //Удаляем вместе с вложенными элементами
        if (parent::delete()) {
            $childs_id = $this->getChildsRecursive($this['id']);
            if ($childs_id) {
                \RS\Orm\Request::make()->delete()
                ->from($this)
                ->where('id IN (#ids)', array('ids' => implode(',', $childs_id)))
                ->exec()->affectedRows();
            }
            return true;
        }
        return false;
    }  
    
    function getChildsRecursive($parent)
    {
        $ids = \RS\Orm\Request::make()->select('id')->from($this)
            ->where(array('parent_id' => $parent))
            ->exec()->fetchSelected(null, 'id');
        
        $result = $ids;
        foreach ($ids as $id) {
            $result = array_merge($result, $this->getChildsRecursive($id));
        }
        return $result;
    }
    
    /**
    * Возвращает объект родителя
    */
    function getParent()
    {
        return new self($this->parent_id);
    }  
    
    /**
    * Возвращает магистральные зоны
    */
    function getZones()
    {
        $zoneApi = new \Shop\Model\ZoneApi();
        $zone_ids = $zoneApi->getZonesByRegionId($this['id']);
        if(empty($zone_ids)){
            return array();
        }
        return \RS\Orm\Request::make()
            ->from(new \Shop\Model\Orm\Zone)
            ->whereIn('id', $zone_ids)
            ->objects();
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $flag - insert или update
    */
    function beforeWrite($flag)
    {
        //Посмотрим родителя, чтобы посмотреть нужно ли выставлять признак города или нет.
        $this['is_city'] = 0;
        $parent = new \Shop\Model\Orm\Region($this['parent_id']);
        if ($parent['parent_id']){ //Если родитель это регион
            $this['is_city'] = 1;
        }
    }
}
