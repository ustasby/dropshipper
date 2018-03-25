<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Model;

class ZoneApi extends \RS\Module\AbstractModel\EntityList
{
    public 
        $uniq;
            
    function __construct()
    {
        parent::__construct(new \Banners\Model\Orm\Zone,
        array(
            'aliasField' => 'alias',
            'nameField' => 'title',
            'multisite' => true
        ));
    }
    
    public static function staticAdminSelectList()
    {
        return array(0 => t('Без связи с зоной')) + self::staticSelectList();
    }
    
    public static function selectTreeList()
    {
        $_this = new self();
        $list = array();
        
        $res = $_this->getListAsResource();
        while($row = $res->fetchRow()) {
            $list[] = array('fields' => $row, 'child' => null);
        }
        return $list;
    }    
    
    
    /**
    * Возвращает список зон баннеров с ключами в alias
    * 
    */
    public static function staticSelectAliasList()
    {
        $arr  = array("" => t("Не выбрано"));
        $api  = new self();
        $list = $api->getListAsArray();
        if (!empty($list)){
            foreach ($list as $zone){
                $arr[$zone['alias']] = $zone['title'];
            }
        }
        
        return $arr;
    }
}
