<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\CsvSchema;
use \RS\Csv\Preset,
    \Shop\Model\CsvPreset,
    \Shop\Model\Orm;

/**
* Схема экспорта заказнных товаров в CSV
*/
class OrderItems extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new CsvPreset\OrderItemsBase(array(
                'ormObject' => new Orm\OrderItem(),
                'excludeFields' => array(
                    'uniq', 'type', 'entity_id', 'sortn', 'extra',
                ),
                'multisite' => true,
                'searchFields' => array('order_id')          
            )), array()
        );
    }
}
