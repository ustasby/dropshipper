<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Application;

/**
* Класс, отвечающий за содержимое тега title в head части страницы
*/
class Title
{
    const
        DELIMITER = ' :: ',
        ADD_REPLACE = 'replace',
        ADD_BEFORE = 'before',
        ADD_AFTER = 'after';
        
    protected 
        $sections = array();
        
    public
        $enabled = true;
    
    /**
    * Добавляет секцию в заголовок
    * 
    * @param string $str - заголовок
    * @param string $key - идентификатор секции
    * @param string $position - идентификатор секции, для последующей перезаписи
    * @return Title
    */
    function addSection($str, $key = null, $position = 'replace')
    {
        if ($str == '') return $this;
        if ($this->enabled) {
            if ($key !== null) {
                if(isset($this->sections[$key])) {
                    $len = ($position==self::ADD_REPLACE) ? 1 : 0;
                    $offset = 0;
                    foreach($this->sections as $itemkey=>$item) {
                        if($key == $itemkey) {
                            break;
                        }
                        $offset++;
                    }
                    if($position == self::ADD_BEFORE) {$offset++;}
                    array_splice($this->sections, $offset, $len, array($str));
                } else {
                    $this->sections[$key] = $str;
                }
                         
            } else {
                $this->sections[] = $str;
            }
        }
        return $this;
    }
    
    /**
    * Удаляет секцию из заголовка
    * 
    * @param string $key - идентификатор секции
    * @return Title
    */
    function removeSection($key)
    {
        unset($this->sections[$key]);
        return $this;
    }
    
    /**
    * Очищает заголовок
    * 
    * @return Title
    */
    function clean()
    {
        $this->sections = array();
        return $this;
    }
    
    /**
    * Возвращает заголовок
    * @return string
    */
    function get()
    {
        $sec = $this->sections;
        return implode(self::DELIMITER,array_reverse($sec));
    }
    
}

