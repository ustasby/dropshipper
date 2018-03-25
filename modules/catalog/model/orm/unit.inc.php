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
* Объект - единица измерения
* @ingroup Catalog
*/
class Unit extends \RS\Orm\OrmObject
{
    protected static
        $table = 'product_unit';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'code' => new Type\Integer(array(
                'description' => t('Код ОКЕИ'),
            )),
            'icode' => new Type\Varchar(array(
                'maxLength' => '25',
                'description' => t('Международное сокращение'),
            )),
            'title' => new Type\Varchar(array(
                'maxLength' => '70',
                'description' => t('Полное название единицы измерения'),
            )),
            'stitle' => new Type\Varchar(array(
                'maxLength' => '25',
                'description' => t('Короткое обозначение'),
            )),
            'amount_step' => new Type\Decimal(array(
                'description' => t('Шаг изменения количества товара в корзине'),
                'maxLength' => 11,
                'decimal' => 3,
                'allowEmpty' => false,
                'default' => 1,
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Сорт. номер'),
                'visible' => false,
            )),
        ));
    }
    
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as next_sort')
                ->from($this)
                ->exec()->getOneField('next_sort', 0) + 1;
        }
    }
    
    /**
    * Вызывается после загрузки объекта
    * @return void
    */
    function afterObjectLoad()
    {
        // Приведение типов
        $this['amount_step'] = (float)$this['amount_step'];
    }
}

