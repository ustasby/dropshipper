<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;

use Catalog\Model\Orm\Property\ItemValue;

class PropertyApi extends \RS\Module\AbstractModel\EntityList
{
    const
        ECLIPSE_FLOAT_COMPARE = 0.00001; //Погрешность, связанная с особенностями поиска в Float полях mysql
        
    protected
        $pta = 'P', //Product table alias
        $filter_active,
        $post_var = 'prop',
        $name_field = 'title',
        $obj_link = '\Catalog\Model\Orm\Property\Link',
        $use_static_cache = true,
        
        $prop_item_table,
        $prop_link_table;

    public
        $last_filtered_props = array();

    function __construct()
    {
        parent::__construct(new \Catalog\Model\Orm\Property\Item,
        array(
            'multisite' => true,
            'defaultOrder' => 'parent_sortn, sortn',
            'sortField' => 'sortn'
        ));
        
        $link = new Orm\Property\Link();
        $this->prop_link_table = $link->_getTable();        
        $this->prop_item_table = $this->obj_instance->_getTable();
    }
    
    /**
    * Возвращает статическим методом полный список значений характеристик
    * 
    * @param boolean $add_unselected - добавлять к массиву пункт не выбрано.
    */
    static function staticSelectList($add_unselected = false)
    {
        $groups = array(0 => array('title' => t('Без группы'))) + 
            \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Property\Dir())
            ->orderby('sortn')
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->objects(null,'id');
        
        $props = \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Property\Item())
            ->orderby('sortn')
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->objects();
        $list = array();
        if (!empty($props)){
            foreach ($props as $prop){
               $title = $groups[$prop['parent_id']]['title'] ?: t('Без группы'); 
               $list[$title][$prop['id']] = $prop['title'];
            }
        } 
        if ($add_unselected){
           $list = array(0=>t('Не выбрано')) + $list;
        }
        
        return $list; 
    }
    
    /**
    * Возвращает полный список списковых характеристик 
    * 
    * @return array
    */
    static function getListTypeProperty()
    {
        $list = array();

        $groups = \RS\Orm\Request::make()
            ->from(new Orm\Property\Dir())
            ->orderby('title')
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->objects(null,'id');
        
        $props = \RS\Orm\Request::make()
            ->from(new Orm\Property\Item())
            ->orderby('title')
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->whereIn('type', Orm\Property\Item::getListTypes());
            
        $props = $props->objects();
        
        if (!empty($props)){
            foreach ($props as $prop){
               $title = ($prop['parent_id']) ? $groups[$prop['parent_id']]['title'] : t('Без группы'); 
               $list[$title][$prop['id']] = $prop['title'];
            }
        } 
        return $list; 
    }
    
    /**
    * Обновляет parent_sortn у всех характеристик группы dir_id
    * 
    * @param integer $dir_id
    */
    function updateParentSort($from, $to)
    {
        if ($from > $to) {
            $tmp = $from;
            $from = $to;
            $to = $tmp;
        }
        
        $q = new \RS\Orm\Request();
        $q->update()
        ->from($this->obj_instance)->asAlias('P')
        ->from(new Orm\Property\Dir())->asAlias('D')
        ->set('P.parent_sortn = D.sortn')
        ->where("P.parent_id = D.id AND D.id >= '#from' AND D.id <= '#to'", array(
            'from' => $from,
            'to' => $to
        ))
        ->exec();
    }
    
    /**
    * Получает сведения о характеристиках и группах в которых они состоят в виде массива.
    * Параметром передаются массив с id-шниками характеристик 
    * 
    * @param array $properties_id - массив id-шников характеристик, 
    * для которых нужно получить сведения 
    */
    function getPropertiesAndGroup(array $properties_id)
    {
        $properties = \RS\Orm\Request::make()
            ->from(new Orm\Property\Item())
            ->whereIn('id', $properties_id)
            ->orderby('sortn')
            ->objects(null, 'parent_id', true);
        
        $dir_ids = array_keys($properties);
        
        $root = new Orm\Property\Dir();
        $root['id'] = 0;
        
        $dirs = array(0 => $root); //Директории свойств
        if (count($dir_ids)) {
            $dirs += \RS\Orm\Request::make()
                ->from(new Orm\Property\Dir())
                ->whereIn('id', $dir_ids)
                ->orderby('sortn')
                ->objects(null, 'id');
        }
            
        return array(
            'properties' => $properties,
            'groups' => $dirs
        );
    }
    
    
    /**
    * Полностью обрабатывает POST со свойствами
    * 
    * @param int $obj_id
    * @param string group|product $link_type
    * @param array $property_list
    * @return bool
    */
    function saveProperties($obj_id, $link_type, $property_list)
    {
        $site_id = \RS\Site\Manager::getSiteId();        
        
        //Удаляем старые значения
        \RS\Orm\Request::make()
            ->delete()
            ->from(new Orm\Property\Link())
            ->where(array(
                'site_id' => $site_id,
                "{$link_type}_id" => $obj_id
            ))
            ->exec();
           
        if (!empty($property_list))
        {
            //Формируем справочник типов харктеристик
            $key_type = \RS\Orm\Request::make()
                ->select('id, type')
                ->from(new Orm\Property\Item())
                ->whereIn('id', array_keys($property_list))
                ->exec()
                ->fetchSelected('id', 'type');
            
                
            $types_data = Orm\Property\Item::getAllowTypeData();
            
            
            foreach($property_list as $prop) {
                if (!isset($key_type[$prop['id']])) continue;

                //Записываем новые значения
                if (isset($prop['value']) || !empty($prop['usevalue'])) {
                    $value = (array)$prop['value'];
                    
                    
                    foreach($value as $one_value) {
                        $link = new Orm\Property\Link();
                        $link['site_id'] = $site_id;
                        $link['prop_id'] = $prop['id'];
                        $link[$link_type.'_id'] = $obj_id;
                        $link['xml_id'] = isset($prop['xml_id']) ? $prop['xml_id'] : null;
                        $link['public'] = isset($prop['public']) ? $prop['public'] : 0;
                        $link['is_expanded'] = isset($prop['is_expanded']) ? $prop['is_expanded'] : 0;
                        
                        if (!empty($prop['usevalue']) || isset($prop['is_my'])) {
                            $link[$types_data[$key_type[$prop['id']]]['save_field']] = $one_value;
                        }
                        $link->insert();
                        
                    }
                }
            }
            
        }
    }
    
    /**
    * Возвращает характеристики возможные вместе с группами в виде массива
    * с ключами group и properties
    * 
    * @param boolean $allow_empty_group - выводить ли группы с отсутвующими характеристиками внутри
    */
    function getAllPropertiesAndGroups($allow_empty_group = false)
    {
        $data = array();
        //Получим группы харакеристик           
        $dirs = \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Property\Dir())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->orderby('sortn')
            ->objects(null, 'id');

        //Получим все характеристики сгруппированными
        $properties = \RS\Orm\Request::make()
                            ->from(new \Catalog\Model\Orm\Property\Item())
                            ->where(array(
                                'site_id' => \RS\Site\Manager::getSiteId()
                            ))                            
                            ->orderby('sortn')
                            ->objects(null, 'parent_id', true);
            
        if (!empty($dirs)) { //Если группы есть
          foreach($dirs as $dir){
               $data[$dir['id']]['group'] = $dir;
               if (!empty($properties[$dir['id']]) || $allow_empty_group){
                  $data[$dir['id']]['properties'] = $properties[$dir['id']]; 
               }else{
                  unset($data[$dir['id']]);
               }  
          } 
        }

        //Получим характеристики лежащие в корне             
        if (!empty($properties[0]) || $allow_empty_group){
          $data[0]['group'] = array(
            'id'    => 0,
            'title' => t('Без группы')
          );  
          
          $data[0]['properties'] = $properties[0];
        }

        return $data;
    }
    
    /**
    * Устанавливает, использовать ли статическое кэширование при вызове других методов данного API
    * 
    * @param mixed $bool
    */
    function setUseStaticCache($bool)
    {
        $this->use_static_cache = $bool;
    }
    
    /**
    * Возвращает характеристики, привязанные к категории товаров
    * 
    * @param integer|array $group_id     - id или массив с id категорий товаров, у которых запрашиваем свойства
    * @param true|false $include_parent  - true - будут в результат включатся ещё и унаследованные свойства 
    * от родительских категорий. По умолчанию true.
    * @param null|integer $public        - null - не учитывать публичное это свойство или нет. 
    * integer - значение поля public свойства. По умолчанию null.
    */
    function getGroupProperty($group_id, $include_parent = true, $public = null)
    {        
        static
            $cache;
        
        $hash = $group_id.','.$include_parent.','.$public;
        if ($this->use_static_cache && !isset($cache[$hash])) {
                
            if ($include_parent) {
                $dirapi      = Dirapi::getInstance();
                $groups_id   = array_keys($dirapi->getPathToFirst($group_id));
                $groups_id[] = 0; //Включаем в список корневой элемент. Содержащий свойства для всех элементов дерева
            } else {
                $groups_id   = (array)$group_id;
            }
            
            $public_where = ($public !== null) ? "public='".(int)$public."' " : null;
            $res = \RS\Orm\Request::make()
                ->select('T.*,  TL.val_str, TL.val_int, TL.val_list_id, TL.group_id, TL.public, TL.is_expanded')
                ->from($this->prop_item_table)->asAlias('T')
                ->join($this->prop_link_table, 'T.id=TL.prop_id', 'TL')
                ->where(array(
                    'TL.site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->whereIn('TL.group_id', $groups_id)
                ->where($public_where)
                ->orderby('T.parent_id, T.sortn')
                ->exec();
                
            $types_data = Orm\Property\Item::getAllowTypeData();            
                                
            $tmp = array();
            $all_property_parents = array();
            while($row = $res->fetchRow()) 
            {
                $row['value'] = $row[ $types_data[$row['type']]['save_field'] ];
                
                //Устанавливаем унаследованное или нет данное свойство
                if (isset($tmp[$row['parent_id']][$row['id']])) {
                    $tmp[$row['parent_id']][$row['id']]['is_my'] = $tmp[$row['parent_id']][$row['id']]['is_my'] && ($row['group_id'] == $group_id);
                } else {
                    $row['is_my'] = ($row['group_id'] == $group_id);
                }
                
                //Если это непосредственно запись для группы
                if ($row['group_id'] == $group_id) {
                    $row['useval'] = true;
                } else {
                    //Убираем перезапись собственного значения
                    if ( isset($tmp[$row['parent_id']][$row['id']]) && $tmp[$row['parent_id']][$row['id']]['useval']==true ) {
                        $row['useval'] = true;
                        $row['value'] = $tmp[$row['parent_id']][$row['id']]['value'];
                        $row['public'] = $tmp[$row['parent_id']][$row['id']]['public'];
                        $row['is_expanded'] = $tmp[$row['parent_id']][$row['id']]['is_expanded'];
                    }
                }
                
                $item = new Orm\Property\Item();
                $item->getFromArray($row);  
                
                //Если значение - массив
                if ($types_data[$row['type']]['is_list'] && isset($row['value'])) {
                    if (isset($tmp[$row['parent_id']][$row['id']])) {
                        $item = $tmp[$row['parent_id']][$row['id']];
                        
                        $item['useval'] = !empty($row['useval']);
                        $item['public'] = $row['public'];
                        $item['is_expanded'] = $row['is_expanded'];

                        $arr = $item['value'];
                    } else {
                        $arr = array();
                    }
                    if ($row['value'] !== null) {
                        $arr[] = $row['value'];
                    }
                    $item['value'] = $arr;
                }                
                
                $tmp[$row['parent_id']][$row['id']] = $item;
                $all_property_parents[$row['parent_id']] = $row['parent_id'];            
            }
            
            $root_dir = new Orm\Property\Dir();
            $root_dir['title'] = t('Без группы');
            $dirs = array(0 => $root_dir); //Директории свойств
            if (count($all_property_parents)) {
                $dirs += \RS\Orm\Request::make()
                    ->from(new Orm\Property\Dir())
                    ->whereIn('id', $all_property_parents)
                    ->orderby('sortn')
                    ->objects(null, 'id');
            }
            
            $result = array();
            foreach($dirs as $dir_id => $dir_object) {
                if (isset($tmp[$dir_id])) {
                    $result[$dir_id]['group'] = $dir_object;
                    $result[$dir_id]['properties'] = $tmp[$dir_id];
                }
            }
            $cache[$hash] = $result;
        }
        
        return $cache[$hash];
    }
    
    /**
    * Создает или обновляет характеристику
    * 
    * @param array $item
    */
    function createOrUpdate(array $item)
    {
        $id = $item['id'];
        
        //Создаем группу, если необходимо
        if ($this->getElement()->checkData($item)) {
            $dir = new Orm\Property\Dir();            
            $dir['id'] = 0;
            if (!empty($item['new_group_title'])) {
                $dir['title'] = $item['new_group_title'];
                $dir->insert();
                $item['parent_id'] = $dir['id'];
            } else {
                $item['parent_id'] = (int)$item['parent_id']; //Для корректной работы JS
                $dir->load($item['parent_id']);
            }
        } else {
            return false;
        }
        if ($id > 0) $this->getElement()->load($id);
        $this->getElement()->getFromArray($item);
        
        if ($id > 0) {
            $this->getElement()->update($id);
        } else {
            $this->getElement()->insert();
        }
        
        return array(
            'group' => $dir,
            'property' => $this->getElement()
        );
    }
    
    /**
    * Возвращает занчения характеристик, распределенные по товарам
    * 
    * @param array $product_ids - массив с ID товаров
    * @param array $all_property_parents - кэш-массив, в котором будут находиться идентификаторы групп характеристик
    * @param bool  $onlyVisible - если true, вернёт только видимые не пустые характеристики
    * @return array
    */
    private function getPropertyByProduct($product_ids, &$all_property_parents, $onlyVisible = false)
    {
        $propertyByProduct = array();  //[product_id][property_id] => array Property
        
        if (!empty($product_ids)) {
            $res = \RS\Orm\Request::make()
                ->select('T.*, L.val_str, L.val_int, L.val_list_id, L.product_id, L.available')
                ->from($this->prop_link_table)->asAlias('L')
                ->join($this->prop_item_table, 'T.id = L.prop_id', 'T')
                ->leftjoin(new Orm\Property\ItemValue(), 'IV.id = L.val_list_id', 'IV')
                ->whereIn('L.product_id', $product_ids);
            
            // добавляем условие если нужны только видимые характеристики
            if ($onlyVisible) {
                $res->leftjoin(new Orm\Property\Dir(), 'D.id = T.parent_id', 'D')
                    ->where('(L.val_str > "" or L.val_int is not null or L.val_list_id is not null)')
                    ->where('T.hidden = 0 AND (D.hidden = 0 OR T.parent_id = 0)');
            }
            
            $res = $res->orderBy('IV.sortn')->exec();

            $types_data = Orm\Property\Item::getAllowTypeData();
            
            while($row = $res->fetchRow()) {
                $row['value'] = $row[ $types_data[$row['type']]['save_field'] ];
                $row['is_my'] = true; //Это свойство создано у товара
                $item = new Orm\Property\Item();
                $item->getFromArray($row);

                //Если значение - массив
                if ($types_data[$row['type']]['is_list']) {
                    if (isset($propertyByProduct[$row['product_id']][$row['parent_id']][$row['id']])) {
                        $item = $propertyByProduct[$row['product_id']][$row['parent_id']][$row['id']];
                        $arr = $item['value'];
                        $arr_of_string = $item['value_in_string'];
                        $arr_available = $item['available_value'];
                        $arr_available_of_string = $item['available_value_in_string'];
                        
                    } else {
                        $arr = array();
                        $arr_of_string = array();
                        //Массив с характеристики которые есть в комплектациях
                        $arr_available_of_string = array();
                        $arr_available = array();
                    }
                    if ($row['val_str'] != '') {
                        $arr[] = $row['value'];
                        $arr_of_string[] = $row['val_str'];
                        if ($row['available']){
                            $arr_available_of_string[$row['value']] = $row['val_str'];    
                            $arr_available[] = $row['value'];    
                        }
                    }
                    $item['value'] = $arr; //Здесь ID значний списковых хар-к
                    $item['value_in_string'] = $arr_of_string; //Здесь строковые значения списковых хар-к
                    $item['available_value'] = $arr_available; //Здесь ID значний списковых хар-к, которые есть у товара в компллектациях
                    $item['available_value_in_string'] = $arr_available_of_string; //Здесь строковые значения списковых хар-к, которые есть у товара в компллектациях
                }

                $propertyByProduct[$row['product_id']][$row['parent_id']][$row['id']] = $item;
                $all_property_parents[$row['parent_id']] = $row['parent_id'];
            }
        }        
        
        return $propertyByProduct;
    }
    
    /**
    * Возвращает ID всех категорий всех уровней, к которым принадлежит товар, включая корневую категорию
    * 
    * @param array $xdir - массив ID категорий товара
    * @return array
    */
    private function getProductAllCategoryId($xdir)
    {
        static
            $cache = array();
        
        if (!$xdir) return array(0);
        
        $hash = implode('', $xdir);
        if ($this->use_static_cache && !isset($cache[$hash])) {
            
            $list = array(); //Полный список id групп, для которых нужно запросить характеристики
            $list[0] = 0; //Включаем в список корневой элемент. Содержащий свойства для всех элементов дерева
            if ($xdir) {
                $dirapi = Dirapi::getInstance();
                foreach($xdir as $dir_id) {
                    $list += $dirapi->getPathToFirst($dir_id);
                }
            }
            $cache[$hash] = array_reverse(array_keys($list));
        }
        return $cache[$hash];
    }
    
    /**
    * Добавляет характеристики от категорий к характеристикам товаров
    * 
    * @param array $products - массив товаров
    * @param array $propertyByProduct - характристики находящиеся непосредственно у товаров
    * @param array $all_property_parents - массив категорий родителей с характеристиками
    * @param bool  $onlyVisible - если true, вернёт только видимые не пустые характеристики
    * @return array
    */
    private function appendCategoryProperty($products, $propertyByProduct, &$all_property_parents, $onlyVisible = false)
    {
        $query_cache = array();
            
        //Загружам свойства, заданные у категорий (кол-во запросов = количеству разных категорий)
        if(isset($products) && is_array($products))   foreach($products as $product)
        {
            $product_id = $product['id'];
            $groups_id  = $product['xdir'];
            
            $group_list = $this->getProductAllCategoryId($groups_id);
            $group_list_sql = implode(',', $group_list);
            
            $types_data = Orm\Property\Item::getAllowTypeData();
            
            //Получаем характеристики для всех категорий товара, 
            //кэшируем значение, если у товаров одинаковый набор категорий
            $list_types = Orm\Property\Item::getListTypes();
            if (!isset($query_cache[$group_list_sql]))
            {
                $res = \RS\Orm\Request::make()
                    ->select('T.*, L.val_str, L.val_int, L.val_list_id, L.group_id')
                    ->from($this->prop_link_table)->asAlias('L')
                    ->join($this->prop_item_table, 'T.id = L.prop_id', 'T')
                    ->where(array(
                        'L.site_id' => \RS\Site\Manager::getSiteId()
                    ))
                    ->whereIn('L.group_id', $group_list);
                // добавляем условие если нужны только видимые характеристики
                if ($onlyVisible) {
                    $res->where('(L.val_str > "" or L.val_int is not null or L.val_list_id is not null)')
                    ->where('T.hidden = 0');
                }
                $res->orderby("field(group_id, $group_list_sql)");
                $res = $res->exec();
                
                $query_cache[$group_list_sql] = array();
                $descent_cache = array();
                
                while ($row = $res->fetchRow()) {
                    // Характеристика может быть наследована только от одной категории
                    if (!isset($descent_cache[$row['id']]) || $descent_cache[$row['id']] == $row['group_id']) {
                        $descent_cache[$row['id']] = $row['group_id'];
                        $row['value'] = $row[ $types_data[$row['type']]['save_field'] ];
                        
                        $item = new Orm\Property\Item();
                        $item->getFromArray($row);
                        
                        //Если значение - массив
                        if ($types_data[$row['type']]['is_list'] && isset($row['value']) ) {
                            if (isset($query_cache[$group_list_sql][$row['parent_id']][$row['id']])) {
                                $item = $query_cache[$group_list_sql][$row['parent_id']][$row['id']];
                                $arr = $item['value'];
                                $arr_of_string = $item['value_in_string'];
                            } else {
                                $arr = array();
                                $arr_of_string = array();
                                //Массив с характеристики которые есть в комплектациях
                                $arr_available_of_string = array();
                                $arr_available = array();
                            }
                            if ($row['val_str'] != '') {
                                $arr[] = $row['value']; //Здесь ID значний списковых хар-к
                                $arr_of_string[] = $row['val_str']; //Здесь строковые значения
                            }
                            $item['value'] = $arr;
                            $item['value_in_string'] = $arr_of_string;
                            $item['available_value'] = $arr_available; //Здесь ID значний списковых хар-к, которые есть у товара в компллектациях
                            $item['available_value_in_string'] = $arr_available_of_string; //Здесь доступные строковые значения списковых хар-к, которые есть у товара в компллектациях
                        }
                        
                        $query_cache[$group_list_sql][$row['parent_id']][$row['id']] = $item;
                        $all_property_parents[$row['parent_id']] = $row['parent_id'];
                    }
                }
            }
            
            if (!isset($propertyByProduct[$product_id])) $propertyByProduct[$product_id] = array();
            if (isset($query_cache[$group_list_sql])) {
                $to_merge = array();
                    foreach($query_cache[$group_list_sql] as $dir => $props) {
                        foreach($props as $id => $prop) {
                             if (isset($propertyByProduct[$product_id][$dir][$id])) {                      
                                $prop = clone $prop;
                                $prop['value'] = $propertyByProduct[$product_id][$dir][$id]['value'];
                                $prop['value_in_string'] = $propertyByProduct[$product_id][$dir][$id]['value_in_string'];
                                $prop['available_value'] = $propertyByProduct[$product_id][$dir][$id]['available_value'];
                                $prop['available_value_in_string'] = $propertyByProduct[$product_id][$dir][$id]['available_value_in_string'];
                                $prop['useval'] = true;
                            }
                            $to_merge[$dir][$id] = $prop;
                        }
                    }
                
                $propertyByProduct[$product_id] = array_replace_recursive($propertyByProduct[$product_id], $to_merge);
            }
        }
        return $propertyByProduct;
    }
    
    
    /**
    * Загружает характеристики для списка товаров, с 
    * учетом характеристик, установленных у категорий
    * 
    * @param array | Orm\Product $products
    * @param bool $onlyVisible - если true, вернёт только видимые не пустые характеристики
    * @return array
    */
    function getProductProperty($products, $onlyVisible = false)
    {
        
        $products_list = ($products instanceof Orm\Product) ? array($products) : $products;
        $all_property_parents = array();
        
        //Загружаем свойства и значения, заданные непосредственно у товаров (одним запросом)
        $product_list_sql = array();
        if(isset($products_list) && is_array($products_list))foreach($products_list as $product) {
            if (!is_null($product['id'])){
                $product_list_sql[] = $product['id'];
            }  
        }


        $propertyByProduct = $this->getPropertyByProduct($product_list_sql, $all_property_parents, $onlyVisible);

        $propertyByProduct = $this->appendCategoryProperty($products_list, $propertyByProduct, $all_property_parents, $onlyVisible);

        $dirs = array(0 => new Orm\Property\Dir());
        
        if (count($all_property_parents)) {
            $dirs += \RS\Orm\Request::make()
                ->from(new Orm\Property\Dir())
                ->whereIn('id', $all_property_parents)
                ->orderby('sortn')
                ->objects(null, 'id');
        }
        
        $result = array();
        
        foreach($propertyByProduct as $product_id => $propertiesByDirs)
        {
            $result[$product_id] = array();
            foreach($dirs as $dir_id => $dir_object) {
                if (isset($propertiesByDirs[$dir_id])) {
                    //Сортируем свойства
                    uasort($propertiesByDirs[$dir_id], array($this, 'sortFunc'));
                    
                    $result[$product_id][$dir_id]['group'] = $dir_object;
                    $result[$product_id][$dir_id]['properties'] = $propertiesByDirs[$dir_id];                    
                }
            }
        }
        
        return ($products instanceof Orm\Product) ? $result[$product_id] : $result;
    }
    
    /**
    * Сравнивает 2 элемента с сортировочным индексом sortn
    * 
    * @param array $a
    * @param array $b
    * @return integer
    */
    function sortFunc($a, $b)
    {
        $_a = $a['sortn'];
        $_b = $b['sortn'];
        if ($_a == $_b) {
            return 0;
        }
        return ($_a < $_b) ? -1 : 1;
    }
    
    /**
    * Исключает из массива не заданные фильтры
    * 
    * @param array $filters
    * @return array
    */
    function cleanNoActiveFilters(array $filters)
    {
        //Чистим неактивные фильтры
        foreach($filters as $key => $filter) {
            if ($filter === ''  || (is_array($filter) && isset($filter['from']) && empty($filter['from']) && empty($filter['to'])) ) 
            {
                unset($filters[$key]);
            }
        }                
        return $filters;
    }
    
    
    /**
    * Возвращает объект $q, в котором выставлены условия для фильтрации
    * 
    * @param array $filters - массив с установленными фильтрами
    * @param string $product_table_alias - alias таблицы с товарами, установленный в $q
    * @return \RS\Orm\Request
    */
    function getFilteredQuery(array $filters, $product_table_alias, \RS\Orm\Request $q)
    {
        $this->pta = $product_table_alias;
        $this->filter_active = false;

        $filters = $this->cleanNoActiveFilters($filters);

        $ids = array_keys($filters);
        if (empty($ids)) return false;
        
        $this->clearFilter();
        $this->setFilter('id', $ids, 'in');
        $list = $this->getList(); //Список свойств

        $this->last_filtered_props = array();
        $this->prop_link_table = \RS\Orm\Tools::getTable(new Orm\Property\Link());

        foreach($list as $n=>$prop) {

            $pn = "p{$n}"; //Псевдоним join'a
            $value = $filters[$prop['id']];

            //Экранируем значения, используемые в запросе
            if (is_string($value)) {
                $value = \RS\Db\Adapter::escape($value);
            }

            if (is_array($value) && isset($value['from']) && isset($value['to'])) {
                if ($prop['interval_from'] == $value['from']
                    && $prop['interval_to'] == $value['to']) {
                    continue; //Пропускаем фильтр, если установленные значения равны крайним взможным значениям
                }

                foreach($value as $k=>$v) {
                    $value[$k] = \RS\Db\Adapter::escape($v);
                }
            }

            $this->filter_active = true;
            $func = ($prop->isListType() ? 'list' : $prop['type']).'Filter';

            $this->$func($pn, $prop, $value, $q);
        }

        return $q;

    }

    /**
    * Возвращает id товаров, удовлетворяющих установленым фильтрам.
    * 
    * @param array $filters - массив с установленными фильтрами.
    * @return array
    */
    function getFilteredProductIds(array $filters)
    {
        $q = new \RS\Orm\Request();
        $q->select('id')
            ->from(new Orm\Product())
            ->asAlias('P');
            
        $q = $this->getFilteredQuery($filters, 'P', $q);
        
        if ($q) {
            $ids = $q->exec()->fetchSelected(null, 'id');
             //Если не существует ни одного товара, то возвращаем заведомо невыполнимое условие - у товара должен быть id = 0
            return empty($ids) ? array(0) : $ids;            
        }
        return false;
    }
    
    
    function isFilterActive()
    {
        return $this->filter_active;
    }
    
    /**
    * Добавляет в запрос
    * 
    * @param string $pn - наименование характеристики в БД
    */
    protected function getAvaliableExpr($pn)
    {
        $config = \RS\Config\Loader::byModule($this);
        return $config['link_property_to_offer_amount'] ? "$pn.available = 1" : null;
    }
    
    
    /**
    * Устанавливает фильтр для числового свойства
    * 
    * @param string $pn - наименование характеристики в БД
    * @param \Catalog\Model\Orm\Property\Item $prop - характеристика
    * @param string $value - значение
    * @param \RS\Orm\Request $q - объект запроса
    */
    protected function intFilter($pn, $prop, $value, \RS\Orm\Request $q)
    {
        $value = str_replace(',', '.', $value);
        if (!is_array($value)) {
            $value = array('from' => $value, 'to' => $value);
        }
        
        $q->openWGroup();
        // если передан ключ "empty" - выбираем товары, у которых не задана данная характеристика
        if (isset($value['empty'])) {
            $subquery = "select $pn.product_id from {$this->prop_link_table} $pn where $pn.prop_id = {$prop['id']} and $pn.product_id is not null group by $pn.product_id";
            $q->where("A.id not in ($subquery)");
            unset($value['empty']); 
        }
        // сам фильтр
        if ((isset($value['from']) && $value['from'] != '') || (isset($value['to']) && $value['to'] != '')) {
            $this->addPropertyJoin($pn, $prop, $q, 'LEFT');
            $expr = "$pn.prop_id = '{$prop['id']}'"; //Выражение
            $vars = array(); //Переменные выражения
            $filter_arr = array(); //Массив с заменами
            if (isset($value['from']) && $value['from'] != '') {
                $vars[]     = "$pn.val_int >= '#from'";
                $filter_arr += array('from' => $value['from']-self::ECLIPSE_FLOAT_COMPARE);
            }
            if (isset($value['to']) && $value['to'] != '') {
                $vars[]   = "$pn.val_int <= '#to'";
                $filter_arr += array('to' => $value['to']+self::ECLIPSE_FLOAT_COMPARE);
            }
            //Склеим всё.
            $vars = implode(" AND ",$vars);
            $q->where("(".$expr." AND (".$vars."))", $filter_arr, 'or');
            $q->where($this->getAvaliableExpr($pn));
        }
        
        $q->closeWGroup();

        //Сохраняем выбранные значения фильтров
        $this->last_filtered_props[$prop['id']] = array(
            'property' => $prop,
            'values' => $value
        );
    }
    
    
    /**
    * Устанавливает фильтр для спискового свойства
    */
    protected function listFilter($pn, $prop, $value, \RS\Orm\Request $q)
    {
        if (empty($value)) return;
        $q->openWGroup();
        // если передан ключ "empty" - выбираем товары, у которых не задана данная характеристика
        if (isset($value['empty'])) {
            $subquery = "select $pn.product_id from {$this->prop_link_table} $pn where $pn.prop_id = {$prop['id']} and $pn.product_id is not null group by $pn.product_id";
            $q->where("A.id not in ($subquery)");
            unset($value['empty']);
        }
        // сам фильтр
        if (!empty($value)) {
            $this->addPropertyJoin($pn, $prop, $q, 'LEFT')
            ->where("$pn.prop_id = '{$prop['id']}'", null, 'or')
            ->whereIn("$pn.val_list_id", (array)$value)
            ->where($this->getAvaliableExpr($pn));
        }
        $q->closeWGroup();
        
        //Сохраняем выбранные значения фильтров
        $this->last_filtered_props[$prop['id']] = array(
            'property' => $prop,
            'values' => ItemValue::getValueById($value)
        );
    }
    
    /**
    * Устанавливает фильтр для строкового свойства
    */
    protected function stringFilter($pn, $prop, $value, \RS\Orm\Request $q)
    {
        if (empty($value)) return;
        if (!is_array($value)) { // может быть передано просто число (1/0)
            $value = array('value' => $value);
        }
        $q->openWGroup();
        // если передан ключ "empty" - выбираем товары, у которых не задана данная характеристика
        if (isset($value['empty'])) {
            $subquery = "select $pn.product_id from {$this->prop_link_table} $pn where $pn.prop_id = {$prop['id']} and $pn.product_id is not null group by $pn.product_id";
            $q->where("A.id not in ($subquery)");
            unset($value['empty']); 
        }
        // сам фильтр
        if (!empty($value['value'])) {
            $expr = "$pn.prop_id = '{$prop['id']}'"; //Выражение
            $this->addPropertyJoin($pn, $prop, $q, 'LEFT')
            ->where("(".$expr." AND $pn.val_str LIKE '#value%')", array('value' => $value['value']), 'or')
            ->where($this->getAvaliableExpr($pn));
        }
        $q->closeWGroup();

        //Сохраняем строковое представление выбранных значений
        $this->last_filtered_props[$prop['id']] = array(
            'property' => $prop,
            'values' => array($value['value'])
        );
    }
    
    /**
    * Устанавливает фильтр для свойства типа Да/Нет
    */
    protected function boolFilter($pn, $prop, $value, \RS\Orm\Request $q)
    {
        if (empty($value) && $value !== '0') return;

        if (!is_array($value)) { // может быть передана просто строка
            $value = array('value' => $value);

        }
        $q->openWGroup();
        // если передан ключ "empty" - выбираем товары, у которых не задана данная характеристика
        if (isset($value['empty'])) {
            $subquery = "select $pn.product_id from {$this->prop_link_table} $pn where $pn.prop_id = {$prop['id']} and $pn.product_id is not null group by $pn.product_id";
            $q->where("A.id not in ($subquery)");
            unset($value['empty']); 
        }
        // сам фильтр
        if (isset($value['value']) && $value['value'] !== '') {
            $this->addPropertyJoin($pn, $prop, $q, 'LEFT');
            if ($value['value'] == 0) {
                $q->where("($pn.prop_id = '{$prop['id']}' AND ($pn.val_int IS NULL OR $pn.val_int = 0)) ", null, 'or');

            } else {
                $q->where("($pn.prop_id = '{$prop['id']}' AND $pn.val_int > ".(1-self::ECLIPSE_FLOAT_COMPARE).")", null, 'or');
            }
            $q->where($this->getAvaliableExpr($pn));
        }
        $q->closeWGroup();

        //Сохраняем выбранные значения фильтров
        if (isset($value['value'])) {
            $this->last_filtered_props[$prop['id']] = array(
                'property' => $prop,
                'values' => $value['value']
            );
        }
    }
    
    /**
    * Добавляет стандартный join к выборке
    * 
    * @param string $pn - alias join'a
    * @param \Catalog\Model\Orm\Property\Item $prop - характеристика
    * @param \RS\Orm\Request $q - объект модифицируемого запроса
    * @param string $type - тип объединения
    * @return \RS\Orm\Request
    */
    protected function addPropertyJoin($pn, $prop, $q, $type = 'INNER')
    {
        $q->join($this->prop_link_table, "$pn.product_id = {$this->pta}.id", $pn, $type);
    
        return $q;
    }
    
    /**
    * Возвращает URL для админ панели с очищенным фильтром
    * 
    * @return string
    */
    function getCleanFilterUrl()
    {
        return \RS\Http\Request::commonInstance()->replaceKey(array('filter' => null, 'bfilter' => null));
    }
    
    static public function getAllGroups()
    {
        $groups = \RS\Orm\Request::make()
            ->from(new Orm\Property\Dir())
            ->objects();

        array_unshift($groups, new Orm\Property\Dir());
        return $groups;    
    }
    
    /**
    * Удаляет характеристики из системы, которые не связаны с товарами или категориями товаров текущего сайта
    * 
    * @return bool
    */
    function cleanUnusedProperty()
    {
        $link_table = \RS\Orm\Tools::getTable(new Orm\Property\Link);
        $item_table = \RS\Orm\Tools::getTable(new Orm\Property\Item);
        $site_id = \RS\Site\Manager::getSiteId();
        
        \RS\Orm\Request::make()
            ->delete('L')
            ->from(new Orm\Property\Link, 'L')
            ->leftjoin(new Orm\Product, 'P.id = L.product_id', 'P')
            ->where("L.site_id = '#site_id' AND L.product_id > 0 AND P.id IS NULL", array('site_id' => $site_id))
            ->exec();

        \RS\Orm\Request::make()
            ->delete('L')
            ->from(new Orm\Property\Link, 'L')
            ->leftjoin(new Orm\Dir, 'D.id = L.group_id', 'D')
            ->where("L.site_id = '#site_id' AND L.group_id > 0 AND D.id IS NULL", array('site_id' => $site_id))
            ->exec();
        
        $count = \RS\Orm\Request::make()
            ->delete()
            ->from(new Orm\Property\Item)
            ->where(array(
                'site_id' => $site_id
            ))
            ->where("id NOT IN (SELECT prop_id FROM $link_table WHERE site_id='$site_id')")
            ->exec()->affectedRows();
        
        $count +=\RS\Orm\Request::make()
            ->delete()
            ->from(new Orm\Property\Dir)
            ->where(array(
                'site_id' => $site_id
            ))            
            ->where("id NOT IN (SELECT parent_id FROM $item_table WHERE site_id='$site_id')")
            ->exec()->affectedRows();

        \RS\Orm\Request::make()
            ->delete('V')
            ->from(new Orm\Property\ItemValue, 'V')
            ->leftjoin(new Orm\Property\Item, 'I.id = V.prop_id', 'I')            
            ->where("I.site_id = '#site_id' AND I.id IS NULL", array('site_id' => $site_id))
            ->exec();
            
        return $count;
    }
    
    /**
    * Возвращает все имеющиеся у товаров значения данного свойства
    * 
    * @param integer $property_id  - ID свойства
    * @param integer $site_id      - ID сайта
    * @return array
    */
    function getExistsValues($property_id, $site_id = null)
    {
        $values = \RS\Orm\Request::make()
            ->select('DISTINCT val_str')
            ->from(new Orm\Property\Link())
            ->where(array(
                'site_id' => \RS\SIte\Manager::getSiteId(),
                'prop_id' => $property_id
            ))
            ->orderby('val_str')
            ->exec()->fetchSelected('val_str', 'val_str');
        unset($values['']);
        return $values;
    }
    
    /**
    * Загружает в список характеристик список возможных значений
    * 
    * @param array $prop_list
    * @param array $allowable_values
    * @return array
    */
    function filterByAllowedValues($prop_list, $allowable_values)
    {
        $new_prop_list = array();
        foreach($prop_list as $item) {
            $allowed_properties = array();
            foreach($item['properties'] as $prop_id => $prop) {
                
                if (isset($allowable_values[$prop_id])) {
                    if ($prop['type'] == 'int'
                            && $allowable_values[$prop_id]['interval_from'] < $allowable_values[$prop_id]['interval_to']) 
                    {
                        $prop->getFromArray($allowable_values[$prop_id]);
                        $allowed_properties[$prop_id] = $prop;
                    }
                    
                    if ($prop['type'] == 'string'
                     || ($prop->isListType() && isset($allowable_values[$prop_id]['allowed_values']) 
                        && count($allowable_values[$prop_id]['allowed_values'])>1) )
                    {
                        $prop->getFromArray($allowable_values[$prop_id]);
                        $allowed_properties[$prop_id] = $prop;
                    }
                }
                
                if ($prop['type'] == 'bool') {
                    $allowed_properties[$prop_id] = $prop;
                }                    
            }
            if ($allowed_properties) {
                $new_prop_list[] = array('group' => $item['group'], 'properties' => $allowed_properties);
            }
        }
        
        return $new_prop_list;
    }
    
    /**
    * Конвертирует старые значения параметров фильтра (f) в новые.
    * В предыдущих версиях RS, в фильтр передавались значения списковых характеристик, 
    * в новых - ID этих значений
    * 
    * @param array $old_filter_values
    * @return array
    */
    function convertOldFilterValues($filter_values)
    {
        if (!$filter_values) {
            return $filter_values;
        }
            
        foreach($filter_values as $prop_id => $data) {
            if (is_array($data)) {
                foreach($data as $n => $value){
                    if (!in_array($n, array('to', 'from'), true)){
                        $filter_values[$prop_id][$n] = Orm\Property\ItemValue::getIdByValue($prop_id, $value, false);
                    }
                }
            }
        }
        return $filter_values;
    }

}
