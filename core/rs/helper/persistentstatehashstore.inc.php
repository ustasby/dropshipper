<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Helper;


class PersistentStateHashStore extends PersistentState
{

    public function __construct($prefix = "")
    {
        parent::__construct($prefix);
    }


    function get($name)
    {
        return \RS\HashStore\Api::get($name);
    }

    function set($name, $value)
    {
        \RS\HashStore\Api::set($name, $value);
    }

}