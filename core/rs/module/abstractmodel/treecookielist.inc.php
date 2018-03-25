<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module\AbstractModel;

/**
* Древовидный список, способный дополнять данные узлов информацией из cookie. (развернут он или свернут)
*/
abstract class TreeCookieList extends TreeList
{
    public
        $uniq;
        
    function __construct(\RS\Orm\AbstractObject $orm_element, array $options = array())
    {
        $this->uniq = md5(get_class($this));
        parent::__construct($orm_element, $options);
    }
    
    /**
    * Возвращает - какие элементы были закрыты. берет информацию из cookie
    * @return array
    */
    function getClosedElement($uniq)
    {
        return isset($_COOKIE[$uniq]) ? explode(',', $_COOKIE[$uniq]) : array();
    }
    
    /**
    * Вызывается после вызова getAllCategory, дополняет выборку
    * 
    * @return void
    */
    function afterloadList()
    {
        $closed = $this->getClosedElement($this->uniq);        
        if (!empty($closed)) {
            foreach ($closed as $dirid) {
                if (isset($this->cat[$dirid])) {
                    $this->cat[$dirid]['closed'] = 1;
                }
            }
        }
    }
}