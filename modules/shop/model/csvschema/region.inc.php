<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\CsvSchema;
use \RS\Csv\Preset;

/**
* Схема экспорта/импорта характеристик в CSV
*/
class Region extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Shop\Model\Orm\Region(),
            'excludeFields' => array('id', 'site_id', 'parent_id'),
            'multisite' => true,
            'searchFields' => array('title', 'parent_id'),
            'multisite' => true,
            'savedRequest' => \Shop\Model\RegionApi::getSavedRequest('Shop\Controller\Admin\RegionCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
        )), array(
            new Preset\TreeParent(array(
                'ormObject' => new \Shop\Model\Orm\Region(),
                'titles' => array(
                    'title' => t('Родитель')
                ),
                'idField' => 'id',
                'parentField' => 'parent_id',
                'treeField' => 'title',
                'rootValue' => 0,
                'multisite' => true,                
                'linkForeignField' => 'parent_id',
                'linkPresetId' => 0
            ))
        ));
    }
}
?>