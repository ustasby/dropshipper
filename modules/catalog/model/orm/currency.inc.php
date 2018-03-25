<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - валюта
* @ingroup Catalog
*/
class Currency extends \RS\Orm\OrmObject
{
    protected static
        $table = 'currency';
        
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'maxLength' => '3',
                    'description' => t('Трехсимвольный идентификатор валюты (Ан. яз)'),
                    'checker' => array('chkEmpty', t('Идентификатор - обязательное поле'))
                )),
                'stitle' => new Type\Varchar(array(
                    'maxLength' => 10,
                    'description' => t('Символ валюты')
                )),
                'is_base' => new Type\Integer(array(
                    'description' => t('Это базовая валюта?'),
                    'hint' => t('Флажок в данном поле означает, что цены товаров в системе указаны в данной валюте'),
                    'checkboxview' => array(1,0)
                )),
                'ratio' => new Type\Real(array(
                    'description' => t('Коэффициент относительно базовой валюты')
                )),
                'public' => new Type\Integer(array(
                    'description' => t('Видимость'),
                    'checkboxview' => array(1,0)
                )),
                'default' => new Type\Integer(array(
                    'description' => t('Выбирать по-умолчанию'),
                    'checkboxview' => array(1,0)
                )),
                'reconvert' => new Type\Integer(array(
                    'description' => t('Пересчитать все цены'),
                    'checkboxview' => array(1,0),
                    'runtime' => true,
                    'appVisible' => false
                )),
            t('Обновление курсов'),
                'percent' => new Type\Real(array(
                    'description' => t('Увеличивать/уменьшать курс на %'),
                    'hint' => t('Можно указывать число как с положительно так и отрицательное. Действует при нажатии "Получить курс ЦБ РФ".'),
                    'default' => 0,
                    'appVisible' => false
                ))
                
        ));
        $this->addIndex(array('title', 'site_id'), self::INDEX_UNIQUE);
    }
    
    function beforeWrite($flag)
    {

    }
    
    /**
    * Действие после сохраниния объектра
    * 
    * @param string $flag - строковый флаг текущей оперпции (insert,update)
    */
    function afterWrite($flag)
    {
        if ($this['default'] == 1) {
            //Валюты по-умолчанию может быть только одна
            \RS\Orm\Request::make()->update($this)->set(array(
                'default' => 0
            ))->where(array('site_id' => $this['site_id']))
                ->where("id != ".$this['id'])
                ->exec();
        }
        if ($this['reconvert']) {
            \Catalog\Model\CostApi::recalculateCosts($this['site_id'], $this);
        }
    }
    
    function delete()
    {
        $count = \RS\Orm\Request::make()->from($this)
            ->where(array('site_id' => \RS\Site\Manager::getSiteId()))->count();
        
        if ($count>1) {
            return parent::delete();
        } else {
            return $this->addError(t('Должна присутствовать хотя бы одна валюта'));
        }
        
    }
}

