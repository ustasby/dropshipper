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
* Схема экспорта/импорта категорий товаров в CSV
*/
class Dir extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Catalog\Model\Orm\Dir(),
            'excludeFields' => array(
                'id', 'site_id', 'parent', 'processed', 'level', 'itemcount', 'image', 'tax_ids'
            ),
            'multisite' => true,
            'searchFields' => array('name', 'parent'),
        )), 
        array(
            new Preset\SinglePhoto(array(
                'linkPresetId' => 0,
                'linkForeignField' => 'image',
                'title' => t('Изображение')
            )),
            new Preset\TreeParent(array(
                'ormObject' => new \Catalog\Model\Orm\Dir(),
                'titles' => array(
                    'name' => t('Родитель')
                ),
                'idField' => 'id',
                'parentField' => 'parent',
                'treeField' => 'name',
                'rootValue' => 0,
                'multisite' => true,                
                'linkForeignField' => 'parent',
                'linkPresetId' => 0
            ))
        ));
    }
}
?>