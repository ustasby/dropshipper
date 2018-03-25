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
* Схема экспорта/импорта заказов в CSV
*/
class Order extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
                'ormObject' => new Orm\Order(),
                'excludeFields' => array(
                    'id', 'site_id', '_serialized', 'hash', 'user_id', 'delivery', 'payment', 
                    'warehouse', 'currency_stitle', 'status', 'use_addr', 'userfields', 'expired', 'is_exported'
                ),
                'savedRequest' => \Shop\Model\OrderApi::getSavedRequest('Shop\Controller\Admin\OrderCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
                'multisite' => true,
                'searchFields' => array('order_num')          
            )), array(
                new CsvPreset\OrderWeight(array()),            
                new CsvPreset\OrderProducts(array( //Товары заказа
                    'idField' => 'id',
                    'linkForeignField' => 'id',
                )),
                new Preset\SerializedArray(array(
                    'linkForeignField' => 'userfields',
                    'title' => t('Дополнительные сведения')
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Shop\Model\Orm\Address(),
                    'multisite' => true, 
                    'fields' => array(
                        'country',
                        'city',
                        'zipcode',
                        'address',
                    ),
                    'titles' => array( 
                        'country' => t('Страна доставки'),
                        'city' => t('Город доставки'),
                        'zipcode' => t('Индекс доставки'),
                        'address' => t('Адрес доставки'),
                    ),
                    'idField' => 'id',
                    'linkForeignField' => 'use_addr',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0,
                    'save' => false
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Shop\Model\Orm\UserStatus(),
                    'multisite' => true, 
                    'fields' => array('title'),
                    'titles' => array( 'title' => t('Статус')),
                    'idField' => 'id',
                    'linkForeignField' => 'status',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0,
                    'save' => false
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Shop\Model\Orm\Payment(),
                    'multisite' => true, 
                    'fields' => array('title'),
                    'titles' => array( 'title' => t('Тип оплаты')),
                    'idField' => 'id',
                    'linkForeignField' => 'payment',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0,
                    'save' => false
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Shop\Model\Orm\Delivery(),
                    'multisite' => true, 
                    'fields' => array('title'),
                    'titles' => array( 'title' => t('Тип доставки')),
                    'idField' => 'id',
                    'linkForeignField' => 'delivery',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0,
                    'save' => false
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Catalog\Model\Orm\WareHouse(),
                    'multisite' => true, 
                    'fields' => array('title'),
                    'titles' => array( 'title' => t('Склад')),
                    'idField' => 'id',
                    'linkForeignField' => 'warehouse',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0,
                    'save' => false
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Users\Model\Orm\User(),
                    'fields' => array('company', 'company_inn', 'surname', 'name',  'midname', 'e_mail', 'phone'),
                    'idField' => 'id',
                    'linkForeignField' => 'user_id',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0,
                    'save' => false
                )),
            )
        );
    }
}