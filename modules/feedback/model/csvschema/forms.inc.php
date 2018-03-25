<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Model\CsvSchema;
use 
    \RS\Csv\Preset,
    \Feedback\Model\Orm;

class Forms extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new Orm\FormFieldItem(),
            'excludeFields' => array(
                'form_id', 'id', 'site_id'
            ),
            'multisite' => true,
            'searchFields' => array('title', 'form_id')
        )), array(
            new Preset\LinkedTable(array(
                'ormObject' => new Orm\FormItem(),
                'fields' => array('title'),
                'titles' => array('title' => t('Форма')),
                'idField' => 'id',
                'multisite' => true,                
                'linkForeignField' => 'form_id',
                'linkPresetId' => 0,
                'linkDefaultValue' => 0
            ))
        ));
    }
}


?>
