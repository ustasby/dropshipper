<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model;

class GroupApi extends \RS\Module\AbstractModel\EntityList
{    
    function __construct()
    {
        parent::__construct(new \Users\Model\Orm\UserGroup, 
        array(
            'nameField' => 'name',
            'idField' => 'alias'
        ));
    }
        
    public function save($id = null, $user_post = array())
    {
        $ret = parent::save($id, $user_post);
        if ($ret) 
        {
            if (isset($user_post['menu_access'])) {
                //Обновляем информацию о правах доступа
                $this->getElement()->setMenuAccess($user_post['menu_access']);
            }
            
            if (isset($user_post['menu_admin_access'])) {
                //Обновляем информацию о правах доступа
                $this->getElement()->setAdminMenuAccess($user_post['menu_admin_access']);
            }
            
            if (isset($user_post['module_access'])) {
                //Обновляем информацию о правах к модулям
                $this->getElement()->setModuleAccess( $user_post['module_access']);
            }
            
            if (isset($user_post['site_access'])) {
                $this->getElement()->setSiteAccess( \RS\Site\Manager::getSiteId(), $user_post['site_access']);
            }
        }
        return $ret;
    }
    
    /**
    * Подготавливает данные по правам к модулю. Конвертирует совокупность отмеченных битов в число.
    */
    function prepareModAccessBits($module_access)
    {
        $result = array();
        foreach($module_access as $mod => $bits)
        {
            if ($mod != 'all') {
                $result[$mod] = 0;
                foreach($bits as $selected) {
                    $result[$mod] = $result[$mod] | (1 << $selected);
                }
            } else {
                $result[$mod] = 255;
            }
            
        }
        return $result;
    }
    
    /**
    * Подготавливает информацию о модуле и его допустимых правах
    * @param $module_access - массив с парвами для модулей (возвращает Orm\UserGroup::getModuleAccess())
    * @return array
    */
    function prepareModuleAccessData($module_access)
    {
        $list_fot_table = array();
        $modules = new \RS\Module\Manager();
        $modlist = $modules->getAllConfig();
        $bit_count = \RS\Orm\ConfigObject::BIT_COUNT;
        foreach ($modlist as $modclass => $modconfig)
        {
            $linedata = array(
                'class' => $modclass,
                'name' => $modconfig['name'],
                'description' => $modconfig['description'],
                'access' => isset($module_access[$modclass]) ? $module_access[$modclass] : 0,
                'accessbit' => $modconfig->access_bit, //Подписи для битов
                'bits' => array()
            );
            
            //Создаем массив активных битов
            for ($i=$bit_count-1; $i>=0; $i--) {
                $linedata['bits'][$i] = ((1 << $i) & $linedata['access']) > 0;
            }
            
            $list_fot_table[] = $linedata;
        }
        return $list_fot_table;
    }
    
    /**
    * Аналог getSelectList, только для статичского вызова
    */
    static function staticSelectList($root = array())
    {
        $_this = new static();
        return $root + $_this->getSelectList();
    }
}

