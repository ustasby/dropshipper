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
* Схема экспорта/импорта справочника цен в CSV
*/
class Unit extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Catalog\Model\Orm\Unit(),
            'excludeFields' => array(
                'id', 'site_id'
            ),
            'savedRequest' => \Catalog\Model\UnitApi::getSavedRequest('Catalog\Controller\Admin\UnitCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
            'multisite' => true,
            'searchFields' => array('title')
        )));
    }
}