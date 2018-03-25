<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Html\Table\Type;

abstract class AbstractType
{
    public 
        $property = array(),
        $sorturl;
        
    protected
        $container,
        $can_modificate_query = false,
        $option_prefixes = array('set', 'add'),
        $field,
        $row,
        $title,    
        $value;
    
    protected
        $head_template = 'system/admin/html_elements/table/coltype/strhead.tpl',
        $body_template = '';

    function __construct($field, $title = null, $property = null)
    {
        $this->field = $field;
        $this->title = $title;
        if ($property !== null) {
          
            foreach($property as $option => $value)
                foreach($this->option_prefixes as $prefix) {
                    $method_name = $prefix.$option;
                    if (method_exists($this, $method_name)) {
                        $this->$method_name($value);
                        unset($property[$option]);
                        break;
                    }
                }        
            $this->property = array_replace_recursive($this->property, $property);
        }
        if (!isset($this->property['customizable']))  $this->property['customizable'] = true;
        if (isset($this->property['Value'])) $this->value = $this->property['Value'];
                
        $this->_init();
    }
    
    /** 
    * Вызывается сразу после конструктора.
    */
    function _init() {}
    
    /**
    * Возвращает true, если колонку можно включать/отключать в настройках таблицы
    */
    function isCustomizable()
    {
        return !empty($this->property['customizable']);
    }
    
    /**
    * Устанавливает, скрывать ли данный столбец по-умолчанию
    * 
    * @param bool $bool Если true - то столбец не будет отображен по-умолчанию
    * @return AbstractType
    */
    function setHidden($bool)
    {
        $this->property['hidden'] = $bool;
        return $this;
    }
    
    /**
    * Возвращает true, если поле не отображается
    * @return bool
    */
    function isHidden()
    {
        return !empty($this->property['hidden']);
    }
    
    /**
    * Устанавливает гиперссылку для ячейки
    * 
    * @param string $href
    * @return AbstractType
    */
    function setHref($href)
    {
        $this->property['href'] = $href;
        return $this;
    }
    
    /**
    * Устанавливает, какого типа сортировка может быть у данной колонки
    * 
    * @param string $sortable - Возможно использовать константы: SORTABLE_ASC, SORTABLE_DESC, SORTABLE_BOTH, SORTABLE_NONE
    * @return AbstractType
    */
    function setSortable($sortable)
    {
        $this->property['Sortable'] = $sortable;
        return $this;
    }
    
    /**
    * Устанавливает, какая сортровка в данный момент применена
    * 
    * @param string $sortable - Возможно использовать константы: SORTABLE_ASC, SORTABLE_DESC
    * @return AbstractType
    */
    function setCurrentSort($sort)
    {
        $this->property['CurrentSort'] = $sort;
        return $this;
    }
    
    /**
    * Возвращает поле данной колонки
    */
    function getField()
    {
        return $this->field;
    }
    
    /**
    * Устанавливает строку значений
    * 
    * @param array $row
    * @return AbstractType
    */
    function setRow($row)
    {
        $this->row = $row;
        return $this;
    }
    
    /**
    * Возвращает строку значений и значение колонки $key
    * 
    * @param mixed $key - ключ колонки
    */
    function getRow($key = null)
    {
        return ($key !== null) ? $this->row[$key] : $this->row;
    }
    
    /**
    * Устанавлвает название колонки
    * 
    * @param mixed $title
    * @return AbstractType
    */
    function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    
    /**
    * Устанавливает значение текущей ячейки
    * 
    * @param mixed $value
    * @return AbstractType
    */
    function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    
    /**
    * Устанавливает аттрибуты для ячейки колонки
    * 
    * @param array $attributes
    * @return AbstractType
    */
    function setTdAttr($attributes)
    {
        $this->property['TdAttr'] = $attributes;
        return $this;
    }
    
    /**
    * Устанавливает аттрибуты для шапки колонки
    * 
    * @param mixed $attributes
    * @return AbstractType
    */
    function setThAttr($attributes)
    {
        $this->property['ThAttr'] = $attributes;
        return $this;
    }    
    
    
    /**
    * Возвращает название колонки
    */
    function getTitle()
    {
        return $this->title;
    }
        
    /**
    * Возвращает значение текущей ячейки
    */
    function getValue()
    {
        return $this->value;
    }
    
    /**
    * Возвращает аттрибуты в виде строки для элемента ячейки
    * 
    * @return string
    */
    function getCellAttr()
    {
        if (isset($this->property['cellAttrParam']) && isset($this->row[$this->property['cellAttrParam']])) {            
            return $this->abstractGetAttr($this->property['cellAttrParam'], array(), $this->row);
        }
    }

    /**
    * Возвращает аттрибуты для элемента
    */
    function getAttr(array $concat_arr)
    {  
        return $this->abstractGetAttr('attr', $concat_arr);
    }
            
    
    /**
    * Возвращает аттрибуты для шапки колонки
    */
    function getThAttr()
    {  
        return $this->abstractGetAttr('ThAttr');
    }
    
    /**
    * Возвращает аттрибуты для ячейки
    */
    function getTdAttr()
    {  
        return $this->abstractGetAttr('TdAttr');
    }
    
    protected function abstractGetAttr($index, array $concat_arr = array(), $source = null)
    {
        $str = '';
        if ($source === null) {
            $source = $this->property;
        }
        if (isset($source[$index]))
            foreach($source[$index] as $key=>$val) {
                if (isset($concat_arr[$key])) {
                    $val .= $concat_arr[$key];
                }
                if ($key{0} == '@') {
                    $val = $this->getHref($val);
                    $key = substr($key, 1);
                }
                $str .= " $key=\"$val\"";
            }
        return $str;
    }
    
    /**
    * Возвращает ссылку ячейки
    * 
    * @param string | Closure $href_pattern - шаблон для поставления значения реалной ссылки
    * @return string
    */
    function getHref($href_pattern = null)
    {
        if ($href_pattern === null) {
            $href_pattern = $this->property['href'];
        }
        
        if ($href_pattern instanceof \Closure) {
            return call_user_func($href_pattern, $this->row);
        }
        
        $href = $href_pattern;
        if (strpos($href, '~field~') !== false) {
            $href = str_replace('~field~', urlencode($this->getValue()), $href);            
        }
        if (strpos($href, '@') !== false) {
            $href = preg_replace_callback('/(@([^&\/]+))/', array($this, 'replaceCallback'), $href);
        }
        if (strpos($href, '{') !== false) {
            $href = preg_replace_callback('/({([^&\/]+)})/', array($this, 'replaceCallback'), $href);
        }
        return $href;        
    }
    
    protected function replaceCallback($matches)
    {
        return $this->row[$matches[2]];
    }
    
    /**
    * Возвращает аттрибуты для ссылки в формате строки
    */
    function getLinkAttr()
    {
        return $this->abstractGetAttr('LinkAttr');
    }
    
    /**
    * Устанавливает аттрибуты для обрамляющего элемента ссылки
    * 
    * @param array $link_attributes
    * @return AbstractType
    */
    function setLinkAttr(array $link_attributes)
    {
        $this->property['LinkAttr'] = $link_attributes;
        return $this;
    }
    
    function getHeadTemplate()
    {
        return $this->head_template;
    }
    
    function getBodyTemplate()
    {
        return $this->body_template;
    }
    
    /**
    * Возвращает шапку для колонки
    */
    function getHead()
    {
        $view = new \RS\View\Engine();
        $view->assign('cell', $this);        
        return $view->fetch($this->head_template);
    }

    /**
     * Устанавливает контейнер, в котором располагается ячейка,
     * например - объект таблицы.
     *
     * @param object $container
     */
    function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Возвращает контейнер, в котором располагается ячейка
     *
     * @return object
     */
    function getContainer()
    {
        return $this->container;
    }

    /**
     * Вызывается в момент установки данных один раз для одной колонки
     */
    function onSetData($data)
    {}

    /**
     * Модифицирует запрос для установки сортировки
     * @param \RS\Orm\Request $q
     */
    function modificateSortQuery(\RS\Orm\Request $q)
    {}

    /**
     * Возвращат true сли данная колонка способна модифицировать запрос для установки сортировки, в противном случае false
     * @return bool
     */
    function canModificateSortQuery()
    {
        return $this->can_modificate_query;
    }
}

