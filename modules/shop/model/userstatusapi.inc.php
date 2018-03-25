<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class UserStatusApi extends \RS\Module\AbstractModel\TreeCookieList
{
    protected static
        $ids_by_type = array(),
        $id_by_type;
    
    public 
        $uniq;
        
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\UserStatus,
        array(
            'parentField' => 'parent_id',
            'nameField' => 'title',
            'aliasField' => 'type',
            'defaultOrder' => 'id',
            'multisite' => true
        ));
    }
    
    function tableData()
    {
        $list = $this->getList();
        foreach($list as $n => $status) {
            if ($status['is_system']) {
                $list[$n]['checkbox_attribute'] = array('disabled' => 'disabled');
            }
        }
        return $list;
    }
    
    /**
    * Возвращает id статуса по символьному идентификатору
    * 
    * @param mixed $type
    * @return integer
    */
    public static function getStatusIdByType($type = null)
    {
        if (!isset(self::$id_by_type)) {
            self::$id_by_type = \RS\Orm\Request::make()
                ->select('id, type')
                ->from(new Orm\UserStatus())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->exec()->fetchSelected('type', 'id');
        }
        
        return ($type !== null) ? self::$id_by_type[$type] : self::$id_by_type;
    }
    
    /**
    * Возвращает ID основного и дублирующих статусов
    * 
    * @param string $type - символьный идентификатор статуса
    * @return array - массив с ID статусов
    */
    public static function getStatusesIdByType($type)
    {
        if (!isset(self::$ids_by_type[$type])) {
            self::$ids_by_type[$type] = \RS\Orm\Request::make()
                ->select('id, type')
                ->from(new Orm\UserStatus())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                ))
                ->where(array(
                    'type' => $type,
                    'copy_type' => $type,
                ), null, 'AND', 'OR')
                ->exec()->fetchSelected(null, 'id');
        }
        
        return self::$ids_by_type[$type];
    }
    
    /**
    * Возвращает сгруппированный список статусов для отображения в административной панели
    * 
    * @return array
    */
    function getGroupedList()
    {
        $list = $this->getAssocList('type');
        $result = array();
        foreach(Orm\UserStatus::getStatusesSort() as $type) {
            $result[$type] = isset($list[$type]) ? $list[$type] : null;
            unset($list[$type]);
        }
        $result[Orm\UserStatus::STATUS_USER] = $list;
        return $result;
    }
    
    /**
    * Возвращает список статусов для отображения в разделе для администрирования заказов
    * 
    * @return array
    */
    function getAdminTreeList()
    {
        $statuses = $this->getTreeList();
        
        foreach($statuses as &$status) {
            if ($status['fields']->isSystem()) {
                $status['fields']['disabledCheckbox'] = true;
                $status['fields']['noRedMarker'] = true;
            }
        }

        return $statuses;
    }
    
    /**
    * Аналог getSelectList, только для статичского вызова
    * 
    */
    static function staticSelectList(array $first_element = array())
    {
        $_this = new static();
        return $first_element + $_this->getSelectList();
    }
    
    /**
    * Возвращает список элементов первого уровня + корневой элемент
    * 
    * @return array
    */
    static function staticRootList()
    {
        $_this = new self();
        $_this->setFilter('parent_id', 0);
        $root = $_this->getListAsResource()->fetchSelected('id', 'title');
        
        return array(0 => t('Верхний уровень')) + $root;
    }
}
