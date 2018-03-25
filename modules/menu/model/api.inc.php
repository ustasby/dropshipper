<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/          
namespace Menu\Model;
use \RS\Html\Table\Type as TableType;

/**
* Класс содержит функции для работы со списком меню
* @ingroup Menu
*/
class Api extends \RS\Module\AbstractModel\TreeCookieList
{
    const
        TYPELINK_LINK = 'link';
    
    protected 
        $sort_field = 'sortn',
        $checkAccess = true, //Если true, то возвращаем только те пункты меню, на которые есть права у текущего пользователя. false - не прверять права
        $accessFilters = null;

        
    protected
        static $instance;
    
    function __construct()
    {
        parent::__construct(new \Menu\Model\Orm\Menu, 
        array(
            'multisite' => true,
            'parentField' => 'parent',
            'nameField' => 'title',
            'aliasField' => 'alias',
            'sortField' => 'sortn',
            'defaultOrder' => 'parent, sortn'
        ));
    }
        
    static function getInstance($type = 'default')
    {
        if (!isset(self::$instance[$type])) self::$instance[$type] = new self();
        return self::$instance[$type];
    }  
    
    /**
    * Возвращает массив с инструкциями для установки фильтра пунктов меню по ID, 
    * согласно правам доступа текущего пользователя к этим пунктам
    * 
    * @return array | bool(false) - Возвращает false в случае, если имеется полный доступ ко всем пунктам меню
    */
    function getAccessFilter()
    {        
        //Кэшируем внутри объекта
        if (!isset($this->accessFilters))
        {
            $current_user = \RS\Application\Auth::getCurrentUser();
            $allow_menu = $current_user->getMenuAccess();
            
            $this->accessFilters = array();
            
            if (in_array(FULL_USER_ACCESS, $allow_menu)) {
                $this->accessFilters = false; //Полный доступ
            } else {
                //Полного доступа нет, добавляем список доступных пунктов меню
                $ids = array_diff($allow_menu, array(FULL_USER_ACCESS));
                if (!empty($ids)) {
                    $this->accessFilters['|id:in'] = implode(',', \RS\Helper\Tools::arrayQuote($ids));
                } else {
                    $this->accessFilters['id'] = 0;
                }
            }
        }
        return $this->accessFilters;
    }
    
    /**
    * Переключает флаг $this->checkAccess
    * 
    * @param bool $checkAccess - если true, то будут возвращены только те пункты меню, к которым есть доступ
    * @return void
    */
    function setCheckAccess($checkAccess)
    {
        $this->checkAccess = $checkAccess;
    }
    
    /**
    * Загружает список всех элементов
    * 
    * @param bool $add_tools_column - задать собственную колонку с инструментами для разделителей (необходимо для админ. части)
    * @return array();
    */
    function getAllCategory($add_tools_column = false)
    {
        if ($this->checkAccess && ($access_filter = $this->getAccessFilter())) {
            $this->setFilter(array($access_filter));
        }
        
        $list = parent::getAllCategory();
        if ($add_tools_column) {
            foreach($list as $object) {
                if ($object['typelink'] == Orm\Menu::TYPELINK_SEPARATOR) {
                    $object['treeTools'] = new TableType\Actions('id', array(
                        new TableType\Action\Edit(\RS\Router\Manager::obj()->getAdminUrl('edit', array('id' => $object['id'], 'mod_controller' => 'menu-ctrl')))
                    ));
                }
            }
        }
        return $list;
    } 
    
    static function selectList()
    {
        $_this = new self();
        $_this->setFilter('menutype','user');
        return array(0 => t('Верхний уровень'))+ $_this -> getSelectList(0);
    }
    
    /**
    * Возвращает текущий пункт меню. В случае успешного обнаружения объект будет загружен (id>0)
    * 
    * @return Orm\Menu
    */
    function getCurrentMenuItem()
    {
        $item_id = \RS\Http\Request::commonInstance()->parameters('menu_item_id');
        return new Orm\Menu($item_id);
    }
    
    function getTreeData($parent_id = 0)
    {
        $this->getAllCategory(true);
        return $this->getTreeList($parent_id);
    }

    /**
    * Перемещает элемент from на место элемента to. Если flag = 'up', то до элемента to, иначе после
    * 
    * @param int $from - id элемента, который переносится
    * @param int $to - id ближайшего элемента, возле которого должен располагаться элемент
    * @param string $flag - up или down - флаг выше или ниже элемента $to должен располагаться элемент $from
    * @param \RS\Orm\Request $extra_expr - объект с установленными уточняющими условиями, для выборки объектов сортировки
    * @param int $new_parent_id - новый ID родительского элемента
    */    
    function moveElement($from, $to, $flag, \RS\Orm\Request $extra_expr = null, $new_parent_id = null)
    {
        $from_obj = $this->getOneItem($from);
        
        if (!$extra_expr) {
            $extra_expr = \RS\Orm\Request::make()->where(array(
                'parent' => $new_parent_id ?: $from_obj['parent'],
                'menutype' => $from_obj['menutype'],
            ));
        }
        
        if ($from_obj['menutype'] != 'admin') {
            $extra_expr->where(array('site_id' => $from_obj['site_id']));
        }
        
        return parent::moveElement($from, $to, $flag, $extra_expr, $new_parent_id);
    }
    
    
    /**
    * Получает меню для админки
    * 
    */
    function getAdminMenu($hide_separators = false, $cache=true)
    {
        $user = \RS\Application\Auth::getCurrentUser();
        
        if ($cache) {
            $site_id = \RS\Site\Manager::getSiteId();
            return \RS\Cache\Manager::obj()
                    ->request(array($this, 'getAdminMenu'), $hide_separators, false, $site_id, $user->id); 
       }else{ 
            $event_result = \RS\Event\Manager::fire('getmenus', array());
            $menu_list    = $event_result->getResult();
            if ($check_access = $this->checkAccess) {
                $allow_menu = $user->getAdminMenuAccess();
                if (in_array(FULL_ADMIN_ACCESS, $allow_menu)) {
                    $check_access = false;
                }
            }
            
            $menus = array();
            if (!empty($menu_list)){
               foreach($menu_list as $item) {
                   if (!$check_access || (in_array($item['alias'], $allow_menu) || $item['typelink'] == 'separator')) {
                       if (!$hide_separators || $item['typelink'] != 'separator') {
                          $item['link'] = $this->getHref($item);
                          $menus[$item['alias']]['fields'] = $item;
                       }
                   }
               } 
               uasort($menus, array($this,'resortAdminMenu'));
               $menus = $this->getAdminMenuLikeTree($menus);
            }
            return $menus;
       }
    }

    /**
    * Отсортировывает меню по номеру сортировки
    * 
    * @param array $a - массив со сведения о меню
    * @param array $b - массив со сведения о меню
    * @return int
    */
    private function resortAdminMenu($a, $b)
    {
        $al = isset($a['fields']['sortn']) ? $a['fields']['sortn'] : 0;
        $bl = isset($b['fields']['sortn']) ? $b['fields']['sortn'] : 0;
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }    

    
    /**
    * Возвращает URL в зависимости от типа пункта меню
    * 
    * @param array $item  - массив со сведениями о пункте меню
    * @return string
    */
    public static function getHref($item)
    {
        if ($item['typelink'] != self::TYPELINK_LINK) {
            return false;
        } else {
            return 
                str_replace('%ADMINPATH%', \Setup::$FOLDER.'/'.\Setup::$ADMIN_SECTION, $item['link']);
        }
    }
    
    /**
    * Ищет родителя для пункта меню и добавляет к нему ветку
    * 
    * @param array $item - пункт меню для которого ищем родителя
    * @param array $list - список меню
    * @return bool
    */
    private function seekParentInAdminMenu($item, &$list)
    {
        $found = false;
        foreach($list as $alias => $one_item) {
            if ($one_item['fields']['alias'] === $item['fields']['parent']) { //Если родитель найден
                $list[$alias]['child'][] = isset($list[$item['fields']['alias']]) ? $list[$item['fields']['alias']] : $item;
                $found = true;
                unset($list[$item['fields']['alias']]);
                break;
            } else {
                if (!empty($one_item['child'])) {
                    if ($found = $this->seekParentInAdminMenu($item, $list[$alias]['child'])) {
                        break;
                    }
                }
            }
        }
        
        return $found;
    }
    
    /**
    * Сортирует ассоциативный массив плоского меню в древовидные
    * 
    * @param array $items - массив пунктоа меню
    * @return array
    */
    private function getAdminMenuLikeTree($items){
        $unsets = array(); 
        foreach($items as $alias => $item) {
           if (!empty($item['fields']['parent'])) { //Если указан родитель
               $found = $this->seekParentInAdminMenu($item, $items);
               if ($found === true){ //Если родитель найден и добавлен, удалим из списка первого уровня перенесённые пункты
                   $unsets[] = $alias;
               }
           }
        }            
        
        foreach($unsets as $unset){
            unset($items[$unset]);
        }
        
        return $items;
    }    
    
    /**
    * Удаляет пункт меню
    * 
    * @param mixed $alias
    * @param mixed $parent_alias
    * @return bool
    */
    function deleteItem($alias, $parent_alias, $menutype)
    {
        $parent = Orm\Menu::loadByWhere(array(
            'alias' => $parent_alias,
            'menutype' => $menutype
        ));
        
        if ($parent['id']) {
            return \RS\Orm\Request::make()
                ->delete()
                ->from(new Orm\Menu)
                ->where(array(
                    'alias' => $alias,
                    'parent' => $parent['id'],
                    'menutype' => $menutype
                ))->exec()->affectedRows()>0;
        }
        return false;
    }
    
    /**
    * Возвращает пункты меню для заданного root
    * 
    * @param integer | string $root ID или ALIAS корневого элемента
    * @param bool $cache - если true, то 
    * @return array ['root' => корневой элемент, 'items' => [пункт меню, пункт меню, ...]]
    */
    function getMenuItems($root, $cache = true)
    {
        $site_id = \RS\Site\Manager::getSiteId();
        if ($cache) {
            $cache_id = json_encode($this->getAccessFilter()).$this->queryObj()->where;
            return \RS\Cache\Manager::obj()
                        ->request(array($this, __FUNCTION__), $root, false, $site_id, $cache_id);
        } else {
            
            $root_orm = Orm\Menu::loadByWhere('(#id = "#root" or #alias = "#root") and site_id = #site_id', array(
                'id' => $this->id_field,
                'alias' => $this->alias_field,
                'root' => $root,
                'site_id' => $site_id,
            ));
            
            $this -> setFilter('public', 1);
            $this -> setFilter('menutype', 'user');
            
            $items = $this->getTreeList( (int)$root_orm['id'] );        
            
            return array(
                'root' => $root_orm,
                'items' => $items
            );
        }
    }
    
    /**
    * Возвращает список зарегистрированных в системе типов меню
    * 
    * @return array
    */
    public static function getMenuTypes($cache = true)
    {
        static 
            $result;
        
        if (!isset($result) || !$cache) {
            $event_result = \RS\Event\Manager::fire('menu.gettypes', array());
            $result = array();
            foreach($event_result->getResult() as $type) {
                $result[$type->getId()] = $type;
            }
        }
        
        return $result;
    }
    
    /**
    * Возвращает массив с идентификатором типа в ключе и названием в значении
    * 
    * @return array
    */
    public static function getMenuTypesNames($only_visible = true)
    {
        $types = self::getMenuTypes();        
        $result = array();
        foreach($types as $key => $type) {
            if (!$only_visible || $type->isVisible()) {
                $result[$key] = $type->getTitle();
            }
        }
        return $result;
    }
    
    /**
    * Возвращает описание всех типов меню
    * 
    * @return string
    */
    public static function getMenuTypeDescriptions($only_visible = true)
    {
        $types = self::getMenuTypes();
        $description = '';
        foreach($types as $type) {
            if (!$only_visible || $type->isVisible()) {
                $description .= "<b>{$type->getTitle()}</b> - ".$type->getDescription().'<br>';
            }
        }
        return $description;
    }

    public function checkParent($obj, $post, $ids)
    {
        if(isset($post['parent'])){
            $parents_arrs = $this-> getPathToFirst($post['parent']);            
            foreach ($ids as $n => $id){
                if(isset($parents_arrs[$id])){
                    $obj->addError(t('Неверно указан родительский элемент'), 'parent');
                    return false;
                }
            }
        }
        return true;
    }
}

