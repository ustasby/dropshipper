<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Debug\Tool;

/**
* Класс кнопки "редактировать" в панели инструментов режима отладки
*/
class Edit extends AbstractTool
{
    /**
    * Конструктор кнопки "редактировать"
    * 
    * @param mixed $mod_name
    * @return Options
    */
    function __construct($href, $title = null, array $options = null)
    {
        if ($title === null) {
            $title = t('редактировать');
        }
        
        $this->options['attr'] = array(
            'title' => $title,
            'class' => " debug-icon-edit crud-edit",
            'href' => $href
        );
        parent::__construct($options);
    }
}