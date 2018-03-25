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
class Zone extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_zone';
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'checker' => array('chkEmpty', t('Введите имя зоны')),
                'description' => t('Название')
            )),
            'xregion' => new Type\ArrayList(array(
                'checker' => array('chkEmpty', t('Укажите регионы')),
                'description' => t('Регионы'),
                'list' => array(array('\Shop\Model\RegionApi', 'selectListAll')),
                'attr' => array(array(
                    'size' => 20,
                    'multiple' => true,
                )),
            )),
        ));
    }

    /**
    * Действия после записи объекта
    * 
    * @param string $flag - insert или update
    * @return boolean
    */
    function afterWrite($flag)
    {
        // Удаляем старые связи с регионами
        $this->deleteRegions();
            
        // Записываем новые регионы
        if(is_array($this->xregion))
        {
            foreach($this->xregion as $region_id){
                $xregion = new Xregion();
                $xregion->zone_id   = $this->id;
                $xregion->region_id = $region_id;
                $xregion->insert();
            }
        }
        
    }
    
    /**
    * Удалить все связи этой зоны с регионами
    */
    function deleteRegions()
    {
        \RS\Orm\Request::make()->delete()
            ->from(new Xregion)
            ->where(array('zone_id' => $this->id))
            ->exec();
    }
    
    
    /**
    * Заполнить поле xregions массивом идентификаторов регионов
    */
    function fillRegions()
    {
        $regions = \RS\Orm\Request::make()->select('region_id')
            ->from(new Xregion)
            ->where(array('zone_id' => $this->id))
            ->exec()->fetchSelected(null, 'region_id');
        $this->xregion = $regions;
    }
    
    /**
    * Удаление
    */
    function delete()
    {
        // Удаляем cвязи с регионами
        $this->deleteRegions();
        
        // Удаляем себя
        return parent::delete();
    }    
    
    function cloneSelf()
    {
        $this->fillRegions();
        return parent::cloneSelf();
    }
    
}
