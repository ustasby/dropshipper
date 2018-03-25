<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS;

/**
* ReadyScript Exception 
*/
class Exception extends \Exception 
{
    protected
        $extra_info = '';
    
    function __construct($message = '', $code = 0, Exception $previous = null, $extra_info = '')
    {
        $this->extra_info = $extra_info;
        parent::__construct($message, $code, $previous);
    }
    /**
    * Возвращает дополнительную информацию об ошибке
    * @return string
    */
    public function getExtraInfo()
    {
        return $this->extra_info;
    }    
} 

