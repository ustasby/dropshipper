<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Orm\Type;

/**
* Тип - любое значение. только run-time тип.
*/
class Mixed extends AbstractType
{
    protected
        $php_type = '',
        $vis_form = false,
        $runtime = true;
    
    
    public function validate($value)
    {
        //Данный тип позволяет писать любые данные
        return true;
    }
    
    public function cast($value)
    {
        $this->set($value);
    }    
}
