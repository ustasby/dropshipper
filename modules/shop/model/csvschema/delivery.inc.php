<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\CsvSchema;
use \RS\Csv\Preset,
    \Shop\Model\Orm;

/**
* Схема экспорта/импорта доставок в CSV
*/
class Delivery extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new Orm\Delivery(),
            'excludeFields' => array('id', 'site_id', 'first_status'),
            'multisite' => true,
            'savedRequest' => \Shop\Model\DeliveryApi::getSavedRequest('Shop\Controller\Admin\DeliveryCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
            'searchFields' => array('title')
        )), array(
            new Preset\LinkedTable(array(
                'ormObject' => new Orm\UserStatus(),
                'fields' => array('title'),
                'titles' => array('title' => t('Начальный статус заказа')),
                'idField' => 'id',
                'multisite' => true,
                'linkForeignField' => 'first_status',
                'linkPresetId' => 0,
                'linkDefaultValue' => 0,
                'save' => false
            )),
            new Preset\ManyToMany(array(
                'ormObject' => new Orm\Zone(),
                'idField' => 'id',
                'manylinkOrm' => new Orm\DeliveryXZone(),
                'title' => t('Зоны'),
                'manylinkIdField' => 'delivery_id',
                'manylinkForeignIdField' => 'zone_id',
                'mask' => '{title}',
                'linkPresetId' => 0,
                'linkIdField' => 'id',
                'searchFields' => array('title'),
                
                'arrayField' => 'xzone',
                'arrayValue' => 'id'
            )),            
        ));
    }
}