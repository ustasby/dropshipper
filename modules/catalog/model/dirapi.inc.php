<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;

class Dirapi extends \RS\Module\AbstractModel\TreeCookieList
{
    const
        BC_SESSION_VAR = 'BREADCRUMB_DIRID';
        
    protected
        static $instance = array();
        
    protected
        $max_level = 20;
        
    function __construct()
    {
        parent::__construct(new \Catalog\Model\Orm\Dir, 
        array(
            'multisite' => true,
            'parentField' => 'parent',
            'sortField' => 'sortn',
            'idField' => 'id',
            'aliasField' => 'alias',
            'nameField' => 'name',
            'defaultOrder' => 'sortn'
        ));
    }

    /**
     * Возвращает экземпляр текущего класса
     *
     * @param string $key ID экземпляра класса
     * @return self
     */
    static function getInstance($key = 'default')
    {
        if (!isset(self::$instance[$key])) {
            self::$instance[$key] = new self();
            if ($key == 'default') self::$instance[$key]->getAllCategory(); //в экземпляре по умолчанию должны быть загружены все категории
        }
        self::$instance[$key]->clearFilter();
        return self::$instance[$key];
    }     
    
    /**
    * Функция быстрого группового редактирования
    */
    function multiUpdate(array $data, $ids = array())
    {                            
        //Добавим событие перед обновлением
        $event_result = \RS\Event\Manager::fire('orm.beforemultiupdate.catalog-dir',array(
            'data' => $data, 
            'ids' => $ids, 
            'api' => $this, 
        ));
        if ($event_result->getEvent()->isStopped()) return false;
        list($data, $ids) = $event_result->extract();

        //Обновляем рекоммендуемые товары
        if (isset($data['recommended_arr']) && isset($data['recommended_arr']['product'])){
            $recomended = serialize($data['recommended_arr']);
            \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Dir())
                ->set(array(
                    'recommended' => $recomended
                ))
                ->whereIn('id', $ids)
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->update()
                ->exec();
        }
        unset($data['recommended_arr']);

        //Обновляем сопутсвующие товары
        if (isset($data['concomitant_arr']) && isset($data['concomitant_arr']['product'])){
            $concomitant = serialize($data['concomitant_arr']);
            \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Dir())
                ->set(array(
                    'concomitant' => $concomitant
                ))
                ->whereIn('id', $ids)
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->update()
                ->exec();
        }
        unset($data['concomitant_arr']);
        
        //Обновляем характеристики списка
        if (isset($data['in_list_properties_arr'])){
            $in_list_properties = serialize($data['in_list_properties_arr']);
            \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Dir())
                ->set(array(
                    'in_list_properties' => $in_list_properties
                ))
                ->whereIn('id', $ids)
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->update()
                ->exec();
        }
        unset($data['in_list_properties_arr']);
        
        $ret = parent::multiUpdate($data, $ids);
        //Добавим событие на обновлении
        \RS\Event\Manager::fire('orm.multiupdate.catalog-dir',array(
            'ids' => $ids,
            'api' => $this, 
        ));
        return $ret;
    }   
    
    /**
    * Загружает полный список всех записей, для дальнейшей работы с древовидными списками
    * 
    * @param bool $cache - Если true, то будет испльзоваться кэш
    * @return array
    */
    function getAllCategory()
    {
        $query_hash = crc32($this->queryObj()->toSql());
        
        $list = \RS\Cache\Manager::obj()
                        ->watchTables($this->obj_instance)
                        ->request(array($this, 'getList'), $query_hash);
        
        $this->cat = array();
        $this->catByParent = array();
        $this->catByAlias = array();
        $this->catByAliasParent = array();

        foreach ($list as $row) {
            $this->cat[$row[$this->id_field]]=$row;
            $this->catByParent[$row[$this->parent_field]][] = $row;
            if (isset($this->alias_field)) {
                $alias = ($this->case_insensitive) ? mb_strtolower($row[$this->alias_field]) : $row[$this->alias_field];
                $this->catByAlias[$alias] = $row[$this -> id_field];
                
                if (!empty($this->parent_field)) {
                    $this->catByAliasParent[$alias][$row[$this->parent_field]] = $row[$this->id_field];
                }
            }
        }
        $this->afterloadList();
        
        return $this->cat;
    }
    
    public function listWithAll()
    {
        $this->load_parents = true;
        $treedata = $this->getTreeList(0);
        
        //Устанавливаем собственные инструменты спецкатегориям
        $router = \RS\Router\Manager::obj();
        foreach($treedata as &$item) {
            if ($item['fields']['is_spec_dir'] == 'Y') {
                $item['fields']['treeTools'] = new \RS\Html\Table\Type\Actions('id', array(
                    new \RS\Html\Table\Type\Action\Edit($router->getAdminPattern('edit_dir', array('id' => $item['fields']['id'])), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                    new \RS\Html\Table\Type\Action\DropDown(array(
                        array(
                            'title' => t('показать на сайте'),
                            'attr' => array(
                                '@href' => $router->getUrlPattern('catalog-front-listproducts', array('category' => $item['fields']['alias'])),
                                'target' => 'blank'
                            )
                        ),                    
                        array(
                            'title' => t('удалить'),
                            'attr' => array(
                                '@href' => $router->getAdminPattern('del_dir', array(':chk[]' => $item['fields']['id'])),
                                'class' => 'crud-remove-one'
                            )
                        )
                    ))
                ));
            }
        }
        return $treedata;
    }
    
    /**
    * Возвращает список объектов, в случае, когда установлен фильтр подгружает родительские элементы также.
    * 
    * @param integer $page - номер страницы запроса
    * @param integer $page_size - Размер страницы запроса
    * @param string|null $order - тип и направление сортировки
    * @return array
    */
    function getList($page = null, $page_size = null, $order = null)
    {   
        $this->setPage($page, $page_size);
        $this->setOrder($order);
        $list = $this->q->objects($this->obj, 'id');
                
        if ($this->filter_active && $this->load_parents) {
            $list += $this->loadParents($list);
        }
        return $list;
    }    
    
    /**
    * Загружает недостающих родителей элементов
    * 
    * @param mixed $list
    * @return \Catalog\Model\Orm\Dir[]
    */
    function loadParents($list)
    {
        $parents = array();
        $parent_ids = array();
        foreach($list as $item) {
            if ($item['parent']>0) {
                $parent_ids[$item['parent']] = $item['parent'];
            }
        }
        if (count($parent_ids)) {
            $parents = \RS\Orm\Request::make()
                ->from($this->obj_instance)
                ->whereIn('id', $parent_ids)
                ->objects(null, 'id');
            $parents += $this->loadParents($parents);
        }        
        return $parents;
    }
        
    /**
    * Возвращает полный список категорий и спецкатегорий в плоском списке
    * 
    * @param bool $include_root - Если true, то включается корневой элемент, иначе возвращаются только существующие категории
    * @return array
    */
    static function selectList($include_root = true)
    {
        $_this = self::getInstance();
        return ($include_root ? array(0 => t('Верхний уровень')) : array() )+ $_this -> getSelectList(0);
    }
    
    function getNospecSelectList()
    {
        $this->setFilter('is_spec_dir', 'N');
        return $this->getSelectList(0);
    }
    
    public static function staticNospecSelectList()
    {
        $_this = new self();
        return $_this->getNospecSelectList();
    }
    
    public static function specSelectList($include_no_select = false)
    {
        $_this = new self();
        $_this->setFilter('is_spec_dir', 'Y');
        return ($include_no_select ? array(0 => t('Не выбрано')) : array() ) + $_this->getSelectList(0);
    }
    
    function getParentsId($id, $addroot = false)
    {
        if (!isset($this->dirs)) {
            $res = $this->getListAsResource();
            $this->dirs = $res->fetchSelected("{$this->id_field}","{$this->parent_field}"); //Здесь массив [[id] => [parent],....]
        }

        $result = array();
        while(isset($this->dirs[$id])) {
            $result[$id] = $id;
            $id = $this->dirs[$id];
        }
        if ($addroot) {
            $result[0] = 0; //Добавляем корневую категорию, если addroot - true
        }
        return $result;
    }
    
    function save($id = null, $user_post = array())
    {       
        if ($id !== 0) {
            $ret = parent::save($id, $user_post);
            $ins_id = $this->obj_instance[$this->id_field];
        } else {
            $this->obj_instance->checkData($user_post);
            $this->obj_instance->clearErrors();
            $ins_id = 0; //Для корневой записи
            $ret = true;
        }
        if ($ret) {
            //Сохраняем свойства
            $prop = new Propertyapi();
            $prop->saveProperties($ins_id, 'group', $this->obj_instance['prop']);
        }
        
        return $ret;
    }
    
    /**
    * Обновляет счетчики у каталога товаров. 
    */
    public static function updateCounts()
    {
        $product_table = \RS\Orm\Tools::getTable( new Orm\Product() );
        $dir_table = \RS\Orm\Tools::getTable( new Orm\Dir() );
        $xdir = \RS\Orm\Tools::getTable( new Orm\Xdir() );
                
        $max_level = \RS\Db\Adapter::sqlExec("SELECT MAX(level) as maxlevel FROM $dir_table")->getOneField('maxlevel', false);
        if ($max_level === false) return false;

        $sql = "UPDATE $dir_table SET itemcount = 0";
        \RS\Db\Adapter::sqlExec($sql);
        
        $config = \RS\Config\Loader::byModule('catalog');
        $num_filter = ($config['hide_unobtainable_goods'] == 'Y') ? 'AND P.num>0' : '';
        
        $sql = "UPDATE $dir_table D, (SELECT dir_id, COUNT(*) as itemcount FROM $xdir X 
                    INNER JOIN $product_table P ON P.id = X.product_id
                    WHERE P.public=1 $num_filter GROUP BY dir_id) as C
                SET D.itemcount = C.itemcount WHERE D.id = C.dir_id";
                
        \RS\Db\Adapter::sqlExec($sql); 
        
        //С литьев до корня переносим цифры
        for($i = $max_level; $i > 0; $i--) {
            $sql = "INSERT INTO $dir_table(id, itemcount) 
                    (SELECT B.parent, SUM(B.itemcount) FROM $dir_table B 
                    WHERE B.level='$i' AND B.parent>0 GROUP BY B.parent)
                    ON DUPLICATE KEY UPDATE itemcount = itemcount + VALUES(itemcount)";
            \RS\Db\Adapter::sqlExec($sql);
        }
    }
    
    /**
    * Обновляет флаг уровня вложенности всем элементам дерева категорий
    */
    public static function updateLevels()
    {
        $true = true;
        
        $dir_table = \RS\Orm\Tools::getTable(new Orm\Dir());        
        
        \RS\Orm\Request::make()->update($dir_table)->set("level = NULL")->exec();
        \RS\Orm\Request::make()->update($dir_table)->set("level = 0")->where('parent = 0')->exec();
        
        $level = 0;
        while($true) {
            $sql = "INSERT INTO $dir_table(id, level) (SELECT id, '".($level+1)."' FROM $dir_table WHERE parent IN (SELECT id FROM $dir_table WHERE level='{$level}'))
            ON DUPLICATE KEY UPDATE level = VALUES(level)";
            
            \RS\Db\Adapter::sqlExec($sql);
            $true = \RS\Db\Adapter::affectedRows()>0;
            $level++;            
        }
    }    
    
    /**
    * Сохраняет последний открытый каталог в сессии
    * 
    * @param integer $dir_id
    */
    function PutInBreadcrumb($dir_id)
    {
        $_SESSION[self::BC_SESSION_VAR] = $dir_id;
    }
    
    /**
    * Возвращает категорию для отображения в навигационной цепочке
    * 
    * @param \Catalog\Model\Orm\Product $product - Объект товара. 
    * Если передан, то будет произведена проверка на допустимость категории в сессии для данного товара
    * 
    * @return integer | bool(false)
    */
    function getBreadcrumbDir(Orm\Product $product = null)
    {
        $dir_id = isset($_SESSION[self::BC_SESSION_VAR]) ? $_SESSION[self::BC_SESSION_VAR] : false;
        
        if ($dir_id && $product) {
            $product->fillCategories();
            $all_x_dirs = array_merge((array)$product['xdir'], (array)$product['xspec']);
            if (!in_array($dir_id, $product['xdir'])){
                $dir_id = $product['maindir'];
            }
        }
        
        return $dir_id;
    }
    
    /**
    * Добавляет символьные идентификаторы товарам, у которых они не установлены
    * 
    * @return integer
    */
    function addTranslitAliases()
    {
        $count = 0;
        $this->queryObj()->where("(alias IS NULL OR alias='')");
        $res = $this->getListAsResource();
        while($row = $res->fetchRow()) {
            $count++;
            $dir = new Orm\Dir();
            $dir->getFromArray($row);
            $i = 0;
            $ok = false;
            while(!$ok && $i<15) {
                $dir[$this->alias_field] = \RS\Helper\Transliteration::str2url($dir['name']).(($i>0) ? "-$i" : '');
                $ok = $dir->update();
                $i++;
            }
        }
        return $count;
    }    
    
    /**
    * Конвертирует массив с псевдонимами категорий в ID
    * 
    * @param array $aliases - список псевдонимов
    * @param bool $cache - Если true, то будет использовано кэширование
    * @return array
    */
    function convertAliasesToId($aliases, $cache = true) {
        if ($cache) {
            return \RS\Cache\Manager::obj()
                        ->request(array($this, __FUNCTION__), $aliases, false);
        } else {
            foreach($aliases as $k => $id) {
                if ($dir = $this->getByAlias($id)) {
                    $aliases[$k] = $dir['id'];
                }
            }
            
            return $aliases;
        }
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

