<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Html\Filter\Type;

class Select extends AbstractType
{
    public
        $tpl = 'system/admin/html_elements/filter/type/select.tpl';
        
    protected
        $list;
        
    function __construct($key, $title, $list, $options = array())
    {
        $this->list = $list;
        parent::__construct($key, $title, $options);
    }
    
    function getList()
    {
        return $this->list;
    }
    
    function getTextValue()
    {
        return $this->list[$this->getValue()];
    }
    
}

