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

class Zone extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new Orm\Zone(),
            'excludeFields' => array(
                'id', 'site_id'
            ),
            'multisite' => true,
            'searchFields' => array('title')
        )));
    }
} 
?>