<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Debug;

/**
* Группа инструментов для одного блока на экране
*/
class Group
{
    const
        DEBUG_CONTEXT_ATTRIBUTE = 'data-debug-contextmenu';
        
    protected static 
        $instance = array(),
        $counter = 1;
    
    protected 
        $refresh_url,
        $actions = array(),
        $uniq, //Уникальный номер группы. 
        $num, //Порядковый номер на странице
        $debug_tools = array();
        
    
    protected function __construct($uniq)
    {
        $this->uniq = $uniq;
        $this->num = self::$counter;
        self::$counter++;
    }        
    
    /**
    * Возвращает true, если для заданного $uniq существует объект debug
    * 
    * @param string $uniq
    * @return bool
    */
    function existsInstance($uniq)
    {
        return isset(self::$instance[$uniq]);
    }
    
    /**
    * Возвращает уникальный идентификатор блока
    * @return string
    */
    function getUniq()
    {
        return $this->uniq;
    }
    
    /**
    * Возвращает экземпляр класса по $uniq
    * @return Group
    */
    public static function getInstance($uniq)
    {
        if (!isset(self::$instance[$uniq])) self::$instance[$uniq] = new self($uniq);
        return self::$instance[$uniq];
    }
    
    /**
    * Возвращает следующий номер объекта группы
    * @return integer
    */
    public static function getNextCounter()
    {
        return self::$counter;
    }

    
    /**
    * Добавляет инструмент, отображаемый в режиме отладки.
    * Каждый новый элемент добавляется в начало списка.
    * @param string $name                       - имя ключа инструмента(кнопки), для массива инструментов
    * @param \RS\Debug\Tool\ToolInterface $tool - класс инструмента(кнопки)
    * 
    * @return void
    */    
    function addTool($name, Tool\ToolInterface $tool)
    {
        $tool->setUniq($this->uniq);
        $this->debug_tools =  array($name => $tool) + $this->debug_tools;
    }
    
    /**
    * Возвращает список(массив экземпляров класса html_debug_.......)
    * инструментов для текущего контроллера
    * @return array
    */
    function getTools($key = null)
    {
        return isset($key) ? $this->debug_tools[$key] : $this->debug_tools;
    }
    
    /**
    * Сохраняет в сессии любые данные, относящиеся к какому-нибудь инструменту в панели отладки
    * @return void
    */
    function addData($tool, $key, $value)
    {
        $_SESSION['DEBUG'][$this->uniq][$tool][$key] = $value;
    }
    
    /**
    * Возвращает данные из сессии для инструмента.
    * @return mixed
    */
    function getData($tool, $key, $default = false)
    {
        return isset($_SESSION['DEBUG'][$this->uniq][$tool][$key]) ? $_SESSION['DEBUG'][$this->uniq][$tool][$key] : $default;
    }
    
    /**
    * В режиме отладки возвращает строку с атрибутами для вставки в html
    * 
    * @param \RS\Debug\Action\AbstractAction[] $actions - массив с действиями в для контекстного меню
    * @param mixed $data - массив с данными для подстановки
    * @return string | null
    */
    public static function getContextAttributes($actions, $data)
    {
        if (Mode::isEnabled() && !empty($actions)) {
            $attributes = array();
            foreach($actions as $action) {
                //Запрашиваем у каждого действия параметры
                $attributes[] = $action->getData($data);
            }
            return self::DEBUG_CONTEXT_ATTRIBUTE."='".json_encode($attributes)."'";
        }
    }
    
    /**
    * Добавляет одно действие, которое будет отображено в контекстном меню в режиме отладки сайта, 
    * при клике правой кнопкой мыши в зоне блока.
    * 
    * @param \RS\Debug\Action\AbstractAction $action - объект действия
    * @return Group
    */
    function addDebugAction(Action\AbstractAction $action)
    {
        $this->actions[] = $action;
        return $this;
    }
    
    /**
    * Возвращает строку с необходимыми атрибутами блочного элемента для вставки в html
    * @return string | null
    */    
    function getDebugAttributes()
    {
            return self::getContextAttributes($this->actions, array());
    }
    
    /**
    * Устанавливает URL, по которому можно обновить блок AJAX запрсом
    * 
    * @param string $url
    * @return void
    */
    function setRefreshUrl($url)
    {
        $this->refresh_url = $url;
    }
    
    /**
    * Возвращает URL, по которому можно обновить блок AJAX запросом
    * @return string | null
    */
    function getRefreshUrl()
    {
        return $this->refresh_url;
    }
    
}

