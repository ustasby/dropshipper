<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;

class DeliveryXZone extends \RS\Orm\AbstractObject
{
    protected static
        $table = "order_delivery_x_zone";
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'delivery_id' => new Type\Integer(array(
                'description' => t('ID Доставки')
            )),
            'zone_id' => new Type\Integer(array(
                'description' => t('ID Зоны')
            )),
        ));
        
        $this->addIndex(array('delivery_id', 'zone_id'), self::INDEX_UNIQUE);
    }
}

