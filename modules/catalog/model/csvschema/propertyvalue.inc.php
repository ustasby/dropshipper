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
* Схема экспорта/импорта значений характеристик в CSV
*/
class PropertyValue extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new OrmProperty\ItemValue(),
            'selectOrder' => 'prop_id, sortn',
            'excludeFields' => array(
                'id', 'site_id', 'prop_id', 'image'
            ),
            'multisite' => true,
            'searchFields' => array('value','prop_id'),
        )), array(
            new \Catalog\Model\CsvPreset\PropertyId(array(
                'linkPresetId' => 0,
                'linkIdField' => 'prop_id',
                'title' => t('Характеристика'),
                'titleGroup' => t('Группа характеристик')
            )),
            new Preset\SinglePhoto(array(
                'linkPresetId' => 0,
                'linkForeignField' => 'image',
                'title' => t('Изображение')
            )),
        ));
    }
}
