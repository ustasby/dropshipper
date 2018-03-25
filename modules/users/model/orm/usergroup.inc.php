<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/  

namespace Users\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - группа пользователей
* @ingroup Users
*/
class UserGroup extends \RS\Orm\OrmObject
{    
    const
        //Предустановленные группы
        GROUP_SUPERVISOR = 'supervisor',
        GROUP_ADMIN = 'admins',
        GROUP_GUEST = 'guest',
        GROUP_CLIENT = 'clients';
        
    protected static
        $table = "users_group";
    
    protected
        $non_delete_groups = array('supervisor','clients','admins','guest'),
        $access_menu_table = 'access_menu',
        $access_module_table = 'access_module';
        
    function __construct()
    {
        $this->access_menu_table = "`".\Setup::$DB_NAME."`.`".\Setup::$DB_TABLE_PREFIX."{$this->access_menu_table}`";
        $this->access_module_table = "`".\Setup::$DB_NAME."`.`".\Setup::$DB_TABLE_PREFIX."{$this->access_module_table}`";
        parent::__construct();
    }

    protected function _init()
    {
        $this->getPropertyIterator()->append(array(
            t('Основные'),
                    'alias' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'description' => t('Псевдоним(англ.яз)'),
                        'primaryKey' => true,
                        'Checker' => array('chkPattern', t('Псевдоним должен состоять из латинских букв и цифр.'), '/^[a-zA-Z0-9]+$/'),
                    )),
                    'name' => new Type\Varchar(array(
                        'maxLength' => '100',
                        'description' => t('Название группы'),
                        'Checker' => array('chkEmpty', t('Необходимо заполнить название группы')),
                    )),
                    'description' => new Type\Text(array(
                        'maxLength' => '100',
                        'description' => t('Описание'),
                    )),
                    'is_admin' => new Type\Integer(array(
                        'maxLength' => '1',
                        'description' => t('Администратор'),
                        'hint' => t('Администратор имеет доступ в панель управления'),
                        'CheckboxView' => array(1,0),
                    )),
            t('Права'),
                    '__access__' => new Type\UserTemplate('%users%/form/group/access.tpl')
            
        ));
    }
    
    function getPrimaryKeyProperty()
    {
        return 'alias';
    }
    
    function delete()
    {
        if (in_array($this['alias'], $this->non_delete_groups)) return false; //Группу администраторы - удалить нельзя. Это базовыя группа.
        $this->setModuleAccess(array()); //Удаляем записи о правах к модулям
        $this->setMenuAccess(array()); //Удаляем записи о правах к пунктам меню
        return parent::delete();
    }
    
    /**
    * Возвращает массив с id доступных пунктов меню для этой группы
    * array(-1,...) - доступ ко всем пунктам меню пользователя
    * array(-2,...) - доступ ко всем пунктам меню администратора
    * array() - нет доступа ни к одному пункту меню
    */
    function getMenuAccess()
    {
        if (empty($this['alias'])) return array();

        return \RS\Orm\Request::make()
            ->select('menu_id')
            ->from(new AccessMenu())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'group_alias' => $this['alias']
            ))
            ->exec()
            ->fetchSelected(null, 'menu_id');
    }
    
    /**
    * Получить права к модулям
    */
    function getModuleAccess()
    {
        if (empty($this['alias'])) return array();
        
        return \RS\Orm\Request::make()
            ->select('*')
            ->from(new AccessModule())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'group_alias' => $this['alias']
            ))
            ->exec()
            ->fetchSelected('module', 'access');
    }
    
    /**
    * Установить права к пунктам меню
    * 
    * @param array $menu_ids - array('МЕНЮ_ID','МЕНЮ_ID',...)
    */
    function setMenuAccess(array $menu_ids, $menu_type = \Users\Model\Orm\AccessMenu::USER_MENU_TYPE)
    {
        if (empty($this['alias'])) return false;
        \RS\Orm\Request::make()
            ->delete()
            ->from(new AccessMenu())
            ->where(array(
                'group_alias' => $this['alias'], 
                'menu_type' => $menu_type,
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->exec();
        
        if (empty($menu_ids)) return;
        $val = array();
        foreach ($menu_ids as $menu_id) {
            $item = new AccessMenu();
            $item['site_id'] = \RS\Site\Manager::getSiteId();
            $item['menu_id'] = $menu_id;
            $item['menu_type'] = $menu_type;
            $item['group_alias'] = $this['alias'];
            $item->insert();
        }
    }
    
    /**
    * Установить права к пунктам меню административной панели
    * 
    * @param array $menu_ids - array('МЕНЮ_ID','МЕНЮ_ID',...)
    */
    function setAdminMenuAccess(array $menu_ids)
    {
        if (empty($this['alias'])) return false;
        \RS\Orm\Request::make()
            ->delete()
            ->from(new AccessMenu())
            ->where(array(
                'group_alias' => $this['alias'], 
                'menu_type' => 'admin',
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->exec();
        
        if (empty($menu_ids)) return;
        $val = array();
        foreach ($menu_ids as $menu_id) {
            $item = new AccessMenu();
            $item['site_id'] = \RS\Site\Manager::getSiteId();
            $item['menu_id'] = $menu_id;
            $item['menu_type'] = 'admin';
            $item['group_alias'] = $this['alias'];
            $item->insert();
        }
    }    
    
    /**
    * Установить права к модулям
    * 
    * @param array $module_rights - array('МОДУЛЬ' => '0..255', 'МОДУЛЬ' => '0..255',...)
    */
    function setModuleAccess($module_rights)
    {
        if (empty($this['alias'])) return false;
       \RS\Orm\Request::make()
            ->delete()
            ->from(new AccessModule())
            ->where(array(
                'group_alias' => $this['alias'],
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->exec();
                    
        if (empty($module_rights)) return;
        $val = array();
        foreach ($module_rights as $module=>$access) {
            $item = new AccessModule();
            $item['site_id'] = \RS\Site\Manager::getSiteId();
            $item['module'] = $module;
            $item['group_alias'] = $this['alias'];
            $item['access'] = $access;
            $item->insert();
        }
    }
    
    /**
    * Устанавливает право доступа к администрированию сайта
    * 
    * @param integer $site_id
    * @param boolean $bool
    */
    function setSiteAccess($site_id, $bool = true)
    {
        if ($bool) {
            $access_site = new AccessSite();
            $access_site['site_id'] = $site_id;
            $access_site['group_alias'] = $this['alias'];
            $access_site->replace();
        } else {
            \RS\Orm\Request::make()
                ->delete()
                ->from(new AccessSite())
                ->where(array(
                    'site_id' => $site_id,
                    'group_alias' => $this['alias']
                ))
                ->exec();
        }
    }
    
    /**
    * Возвращает true, если у группы есть доступ к сайту site_id
    * 
    * @param integer $site_id
    */
    function getSiteAccess($site_id)
    {
        $access = AccessSite::loadByWhere(array(
            'site_id' => $site_id,
            'group_alias' => $this['alias']
        ));

        return $access['site_id']>0;
    }
    
}

