<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Model\CsvSchema;
use \RS\Csv\Preset;

/**
* Схема экспорта/импорта характеристик в CSV
*/
class Menu extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Menu\Model\Orm\Menu(),
            'excludeFields' => array('id', 'site_id', 'parent', 'closed'),
            'multisite' => true,
            'searchFields' => array('title','parent'),
            'selectRequest' => \RS\Orm\Request::make()
                ->from(new \Menu\Model\Orm\Menu())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'menutype' => 'user'
                ))
                ->orderby('parent')
        )), array(
            new Preset\TreeParent(array(
                'ormObject' => new \Menu\Model\Orm\Menu(),
                'titles' => array(
                    'title' => t('Родитель')
                ),
                'idField' => 'id',
                'parentField' => 'parent',
                'treeField' => 'title',
                'rootValue' => 0,
                'multisite' => true,                
                'linkForeignField' => 'parent',
                'linkPresetId' => 0
            ))
        ));
    }
}
?>