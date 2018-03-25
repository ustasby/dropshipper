<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;

class UnitApi extends \RS\Module\AbstractModel\EntityList
{
    function __construct()
    {
        parent::__construct(new \Catalog\Model\Orm\Unit,
        array(
            'multisite' => true,
            'defaultOrder' => 'sortn',
            'sortField' => 'sortn'
        ));
    }
    
    public static function selectList()
    {
        $_this = new self();
        return array(0 => '-') + $_this->getAssocList('id', 'stitle');
    }
}

