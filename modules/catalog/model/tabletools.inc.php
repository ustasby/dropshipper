<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model;

/**
* Тип колонки для таблицы, в которой будет checkbox для выделения элементов на всех страницах.
* @ingroup Catalog
*/
class Tabletools extends \RS\Html\Table\Type\Tools
{
    function getHead()
    {
        return '<input type="checkbox" name="selectAll" title="{t}Выделить элементы на всех страницах{/t}" value="on">';
    }    
}

