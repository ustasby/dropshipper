<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Model\CsvSchema;
use \RS\Csv\Preset,
    Banners\Model\Orm;

class Banner extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new Orm\Banner(),
            'excludeFields' => array(
                'file', 'xzone', 'id', 'site_id'
            ),
            'multisite' => true,
            'searchFields' => array('title')
        )), array(
            new Preset\SinglePhoto(array(
                'title' => t('Файл'),
                'linkForeignField' => 'file',
                'linkPresetId' => 0
            )),
            new Preset\ManyToMany(array(
                'ormObject' => new Orm\Zone(),
                'idField' => 'id',
                'manylinkOrm' => new Orm\Xzone(),
                'title' => t('Зоны'),
                'manylinkIdField' => 'banner_id',
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
?>
