<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvSchema;
use \RS\Csv\Preset,
    \Catalog\Model\Orm\Property as OrmProperty;

/**
* Схема экспорта/импорта характеристик в CSV
*/
class Property extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new OrmProperty\Item(),
            'nullFields' => array('xml_id'),
            'excludeFields' => array(
                'id', 'site_id', 'parent_sortn', 'group_id', 'product_id', 'interval_from', 
                'interval_to', 'step', 'value', 'is_my', 'public', 'useval', 
                'allowed_values', 'allowed_values_objects', 'parent_id'
            ),
            //'savedRequest' => \Catalog\Model\PropertyApi::getSavedRequest('Catalog\Controller\Admin\Propctrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
            'multisite' => true,
            'searchFields' => array('title','parent_id'),
            'beforeRowExportCallback' => function($_this, $row_index) {
                $property = $_this->getSchema()->rows[$row_index];
                $property['values'] = implode(';', $property->valuesArr());
            }
        )), array(
            new Preset\LinkedTable(array(
                'ormObject' => new OrmProperty\Dir(),
                'fields' => array('title'),
                'titles' => array('title' => t('Группа')),
                'idField' => 'id',
                'multisite' => true,                
                'linkForeignField' => 'parent_id',
                'linkPresetId' => 0,
                'linkDefaultValue' => 0
            ))
        ));
    }
}
