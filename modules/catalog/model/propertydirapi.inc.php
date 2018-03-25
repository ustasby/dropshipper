<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;

class PropertyDirApi extends \RS\Module\AbstractModel\EntityList
{
    public 
        $uniq;
        
    function __construct() 
    {
        parent::__construct(new \Catalog\Model\Orm\Property\Dir,
        array(
            'multisite' => true,
            'defaultOrder' => 'sortn',
            'nameField' => 'title',
            'sortField' => 'sortn'
        ));
    }
    
    public static function selectList()
    {
        $_this = new self();
        return array(0 => t('Без группы'))+ $_this -> getSelectList(0);
    }
    
    public function selectTreeList()
    {
        $list = array();
        $res = $this->getListAsResource();
        while($row = $res->fetchRow()) {
            $list[] = array('fields' => $row, 'child' => array());
        }
        return $list;
    }

}

