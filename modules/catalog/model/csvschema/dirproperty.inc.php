<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\CsvSchema;
use \RS\Csv\Preset;

/**
* Схема экспорта/импорта
*/
class DirProperty extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
                'ormObject' => new \Catalog\Model\Orm\Property\Link(),
                'fields' => array(
                    'public'
                ),
                'titles' => array(
                    'public' => t('Отображать в поиске')
                ),
                'replaceMode' => true,
                'nullFields' => array(
                    'val_str', 'val_int'
                ),
                'multisite' => true,
                'selectRequest' => \RS\Orm\Request::make()->select('DISTINCT public, prop_id, group_id')->from(new \Catalog\Model\Orm\Property\Link())->where('group_id>0'),
                'searchFields' => array('site_id', 'group_id'),
            )), array(
                new Preset\LinkedTable(array(
                    'ormObject' => new \Catalog\Model\Orm\Property\Item(),
                    'save' => false,
                    'fields' => array('title'),
                    'titles' => array('title' => t('Характеристика')),
                    'idField' => 'id',
                    'multisite' => true,                
                    'linkForeignField' => 'prop_id',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0
                )),
                new Preset\LinkedTable(array(
                    'ormObject' => new \Catalog\Model\Orm\Dir(),
                    'save' => false,
                    'fields' => array('name'),
                    'titles' => array('name' => t('Категория')),
                    'idField' => 'id',
                    'multisite' => true,                
                    'linkForeignField' => 'group_id',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0
                )),                
            )
        );
    }
}
?>