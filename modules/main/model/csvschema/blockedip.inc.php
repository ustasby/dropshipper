<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Model\CsvSchema;
use \RS\Csv\Preset;

/**
* Схема экспорта/импорта справочника цен в CSV
*/
class BlockedIp extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Main\Model\Orm\BlockedIp(),
            'savedRequest' => \Main\Model\BlockedIpApi::getSavedRequest('Main\Controller\Admin\BlockedIp_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
            'searchFields' => array('ip')
        )));
    }
}