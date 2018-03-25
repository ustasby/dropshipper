<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Model;

class FieldApi extends \RS\Module\AbstractModel\EntityList
{
        
    function __construct()
    {
        parent::__construct(new \Feedback\Model\Orm\FormFieldItem(), 
        array(
            'multisite' => true,
            'sortField' => 'sortn',
            'idField' => 'id',
            'aliasField' => 'alias',
            'defaultOrder' => 'sortn'
        ));
    }
        
}

