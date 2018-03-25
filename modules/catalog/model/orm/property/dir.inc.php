<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model\Orm\Property;
use \RS\Orm\Type;

/**
* Класс объектов - группа характеристик
* @ingroup Catalog
*/
class Dir extends \RS\Orm\OrmObject
{
    protected static
        $table = 'product_prop_dir';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'description' => t('Название')
            )),
            'hidden' => new Type\Integer(array(
                'description' => t('Не отображать в карточке товара'),
                'checkboxView' => array(1,0),
                'maxLength' => 1,
                'default' => 0
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Сорт. номер'),
                'visible' => false
            ))
        ));
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $flag - insert или update
    * @return void
    */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as max')
                ->from($this)
                ->exec()->getOneField('max', 0) + 1;
        }
    }
    
    /**
    * Удаление 
    * 
    */
    function delete()
    {
        $ret = parent::delete();
        if ($ret) {
            \RS\Orm\Request::make()->delete('I, L')
            ->from(new Item())->asAlias('I')
            ->leftjoin(new Link(), 'I.id = L.prop_id', 'L')
            ->where('I.parent_id="#parent"', array('parent' => $this['id']))
            ->exec();
        }
        return $ret;
    }
    
    function getChildren()
    {
        return \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Property\Item)
            ->where(array('parent_id' => (int)$this->id))
            ->objects();
    }
}

