<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Orm\Type;

class Decimal extends AbstractType
{
    protected 
        $max_len = 10,
        $decimal = 0,
        $php_type = 'float',
        $sql_notation = "decimal";
}


