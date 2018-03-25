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
 * Причина отмены заказов
 */
class SubStatus extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_substatus';

    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'description' => t('Название статуса')
            )),
            'alias' => new Type\Varchar(array(
                'description' => t('Псевдоним')
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядок сортировки'),
                'visible' => false
            ))
        ));

        $this->addIndex(array('site_id', 'alias'), self::INDEX_UNIQUE);
    }

    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                    ->select('MAX(sortn) as max')
                    ->from($this)
                    ->where(array(
                        'site_id' => $this->__site_id->get()
                    ))
                    ->exec()->getOneField('max', 0) + 1;
        }
    }
}