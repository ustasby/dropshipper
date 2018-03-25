<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Model;

class ResultFormatter
{
    /**
    * Подготавливает данные в заданном формате
    * 
    * @param array $data - произвольные данные
    * @param string $format - формат json или xml
    */
    public static function format($data, $format = 'json')
    {
        switch($format) {
            default: 
                if (defined('JSON_UNESCAPED_UNICODE')) { //Проверяем на PHP 5.4.0
                    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
                } else {
                    $flags = null;
                }
                $result = json_encode($data, $flags);
        }
        return $result;
    }    
}
