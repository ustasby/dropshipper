<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
/**
* Функции по работе с древовидными списками. (с полной загрузкой списка в массив)
*/
namespace RS\Module\AbstractModel;

abstract class TreeList extends EntityList
{
    protected
        $case_insensitive = true,
        $parent_field,
        
        $cat,
        $catByParent,
        $catByAlias,
        $catByAliasParent,
        $cache;
    
    /**
    * Устанавливает поле, в котором хранится ссылка на ID родителя записи
    * 
    * @param string $field - поле ORM Объекта
    * @return TreeList
    */
    function setParentField($field)
    {
        $this->parent_field = $field;
        return $this;
    }
    
    /**
    * Если задано true, то поиск по псевдонимам будет вестись без учета регистра
    * 
    * @return TreeList
    */
    function setCaseInsensitive($bool)
    {
        $this->case_insensitive = $bool;
        return $this;
    }
    
    /**
    * Загружает полный список всех записей, для дальнейшей работы с древовидными списками
    * 
    * @return array возвращает массив с категориями
    */
    function getAllCategory()
    {
        $list = $this->getList();
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
    
    function afterloadList()  {}
       
    /**
    * Возвращает список элементов, составляющих путь к элементу от корня
    * 
    * @param mixed $id
    * @return array
    */
    public function getPathToFirst($id)
    {
        if (!isset($this->cat)) $this->getAllCategory();
          $tmp=array();
          @$cur=$this -> cat[$id];
          while (isset($cur))
          {
              $tmp[$cur[$this->id_field]]=$cur;
              if ($cur[$this->parent_field]==0 || ($cur[$this->parent_field] == $cur[$this->id_field])) 
                  break;
              else {
                  $cur=$this -> cat[$cur[$this->parent_field]];
              }
        }
        $tmp=array_reverse($tmp,true);
        return $tmp;
    }

    /**
    * Возвращает элемент по ID или псевдониму
    * 
    * @param mixed $id_or_alias - ID или Псевдоним
    * @param mixed $alias_field - не используется
    * @return \RS\Orm\AbstractObject
    */
    public function getById($id_or_alias, $alias_field = null)
    {
        if ($this->case_insensitive) $id_or_alias = mb_strtolower($id_or_alias);
        if (!isset($this->cat)) $this->getAllCategory();
        if (isset($this->catByAlias[$id_or_alias])) {
            return $this->getByAlias($id_or_alias);
        } else {
            if(isset($this->cat[$id_or_alias]))
                return $this->cat[$id_or_alias];
        }
        return false;
    }
    
    /**
    * Возвращает полный список предварительно загруженных записей
    * 
    * @return array
    */
    public function getFullList()
    {
        return isset($this->cat) ? $this->cat : $this->getAllCategory();        
    }
    
    /**
    * Возвращает элемент по Псевдониму
    * 
    * @param mixed $alias - псевдоним
    * @param mixed $parent - ID родителя, если уникальность псевдонима в рамках родительского элемента
    * @return \RS\Orm\AbstarctObject | false
    */
    public function getByAlias($alias, $parent = null)
    {        
        if ($this->case_insensitive) $alias = mb_strtolower($alias);
        if (!isset($this->cat)) $this->getAllCategory();
        
        if ($parent === null) {
            if (isset($this->catByAlias[$alias]))
                    return $this->cat[$this->catByAlias[$alias]];
        } else {
            if (isset($this->catByAliasParent[$alias][$parent]))
                    return $this->cat[$this->catByAliasParent[$alias][$parent]];
        }
        return false;
    }
    
    /**
    * Возвращает список непосредственных детей у элемента с идентификатором $id
    * 
    * @param mixed $id - ID элемента
    * @return array | false
    */
    public function getChilds($id)
    {
        if (!isset($this->cat)) $this->getAllCategory();
        return isset($this->catByParent[$id]) ? $this->catByParent[$id] : array();
    }
    
    /**
    * True - если есть дочерние элементы, false - если нет
    * 
    * @param mixed $id
    */
    protected function hasChild($id) 
    {
        return isset($this->catByParent[$id]);
    }
    
    /**
    * Подготавливает древовидную структуру массива
    * 
    * @param mixed $list
    * @param mixed $parentid
    */
    protected function PrepareList(&$list, $parentid)
    {
        if (!isset($this->cat)) $this -> GetAllCategory();
        $i=0;
        $otherid = array();
        foreach($this -> cat as $k => $v) 
        {
            if ($v[$this -> parent_field] == $parentid)
            {
                $list[$i] = array('fields' => $v, 'child' => array());
                if ($this->hasChild($k)) $this->PrepareList($list[$i]['child'],$k);
                $i++;                
            }
        }
    }
    
    /**
    * Очищает кэш экземпляра класса
    * @return void
    */
    public function clearLocalCache()
    {
        $this->cache = array();
    }
    
    /**
    * Возвращает дерево элементов
    * 
    * @param mixed $parent_id
    */
    public function getTreeList($parent_id = 0)
    {
        if (!isset($this->cache[$parent_id])) {
            $this->cache[$parent_id] = array();
            $this -> PrepareList($this->cache[$parent_id], $parent_id);
        }
        return $this->cache[$parent_id];
    }    
    
    /**
    * Возвращает список всех категорий в одноуровневом массиве с соответствующим уровню вложенности количеством отступов
    * 
    * @return array
    */
    public function getSelectList($parent_id = 0, $level = 0)
    {
        $tmp = array();
        if (!isset($this->cat)) $this->getAllCategory();
        if (empty($this -> catByParent[$parent_id]))  return array();
        foreach ($this -> catByParent[$parent_id] as $v)
        {
            $tmp[$v[$this -> id_field]] = str_repeat('&nbsp;',$level*4).$v[$this -> name_field];
            $tmp = $tmp + $this -> getSelectList($v[$this -> id_field], $level+1);
        }
        return $tmp;
    }
    
    /**
    * Дополняет список $list идентификаторами всех дочерних элементов
    * 
    * @param array $list - список ID элементов, для которых необходимо найти дочерние ID
    * @return array
    */
    public function FindSubFolder(array $list)
    {
        if (!isset($this->cat)) $this->getAllCategory();  
        
        $result=array();
        foreach ($list as $v) {
            if (isset($this->catByParent[$v])) {
                foreach($this->catByParent[$v] as $sub) {
                    $result=array_merge($result, $this->FindSubFolder(array(0 => $sub[$this->id_field])));
                    $result[] = $sub[$this->id_field];
                }
            }
            $result[] = $v;
        }
        $result = array_unique($result);
        return $result;
    }        
    
    /**
    * Возвращает свой id и список ID всех дочерних элементов
    * 
    * @param mixed $id - ID элемента, чьих детей нужно найти
    * @return array
    */
    public function getChildsId($id)
    {
        return  $this->FindSubFolder(array(0 => $id));
    }    
    
    /**
    * Удаляет список объектов по id, включая дочерние элементы
    * 
    * @param array $ids - массив ID объектов
    * @return bool
    */
    public function del(array $ids)
    {
        if ($this->noWriteRights()) return false;
        
        $subIdList = $this->FindSubFolder($ids);
        return parent::del($subIdList);
    }
    
    /**
    * Возвращает массив родителей элемента. Аналог getPathToFirst только с использованием запросов к БД
    * 
    * @param mixed $id
    */
    function queryParents($id)
    {
        $q = new \RS\Orm\Request();
        $q->from($this->obj_instance);
        $path = array();
        $obj = true;
        while($obj) {
            $q->where = '';
            $obj = $q->where(array($this->id_field => $id))->object();
            if ($obj) {
                $path[ $obj[$this->id_field] ] = $obj;
                $id = $obj[$this->parent_field];
            }
        }
        return array_reverse($path, true);        
    }
    
    
    /**
    * Возвращает список непосредственных детей элемента $id используя запросы к БД
    * 
    * @param mixed $parent_id - ID родителя
    * @param string $order - сортировка
    * @return array
    */
    function queryGetChilds($id, $order = null)
    {
        if (!isset($order)) $order = $this->default_order;

        return 
            \RS\Orm\Request::make()->select('*')->from($this->obj_instance)
            ->where(array($this->parent_field => $id))
            ->orderby($order)
            ->objects($this->obj);
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
    public function moveElement($from, $to, $flag, \RS\Orm\Request $extra_expr = null, $new_parent_id = null)
    {
        if ($this->noWriteRights()) return false;
        
        //Если требуется перенос элемента к другому родителю
        //Сначала переносим элемент в конец колонки назначения, затем выполняем обычную сортировку
        $from_obj = $this->getOneItem($from);
        if ($new_parent_id !== null && $from_obj[$this->parent_field] != $new_parent_id) {
            $from_obj[$this->parent_field] = $new_parent_id;
            $from_obj[$this->sort_field] = \RS\Orm\Request::make()
                                                ->select('MAX(sortn) as maxid')
                                                ->from($from_obj)
                                                ->where(array(
                                                    $this->parent_field => $new_parent_id
                                                ))->exec()->getOneField('maxid', 0) + 1;
            $from_obj->update();
        }

        if (!$extra_expr) {
            $extra_expr = \RS\Orm\Request::make()->where(array($this->parent_field => $from_obj[$this->parent_field]));
            if ($this->isMultisite()) {
                $extra_expr->where(array($this->site_id_field => $from_obj[$this->site_id_field]));
            }
        }
        return parent::moveElement($from, $to, $flag, $extra_expr);
    }
}   