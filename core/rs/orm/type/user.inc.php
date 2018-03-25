<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Orm\Type;

class User extends Bigint
{
    protected
        $request_url,
        $form_template = '%system%/coreobject/type/form/user.tpl';
    
    private static $cache_users = array();
    
    function __construct(array $options = null)
    {
        $this->view_attr = array('size' => 40);        
        parent::__construct($options);
    }
    
    /**
    * Возвращает объект пользователя по значению текущего свойства
    */
    function getUser()
    {
        $user_id = ($this->get()>0) ? $this->get() : null;
        if ($user_id>0) {
            if (!isset(self::$cache_users[$user_id])) {
                $user = new \Users\Model\Orm\User($user_id);                            
                self::$cache_users[$user_id] = $user;
            }
            return self::$cache_users[$user_id];
        }
        return new \Users\Model\Orm\User();
    }
    
    /**
    * Возвращает URL, который будет возвращать результат поиска
    * 
    * @return string
    */
    function getRequestUrl()
    {
        return $this->request_url ?: \RS\Router\Manager::obj()->getAdminUrl('ajaxEmail', null, 'users-ajaxlist');
    }
    
    /**
    * Устанавливает URL, который будет возвращать результат поиска
    * 
    * @return void
    */
    function setRequestUrl($url)
    {
        $this->request_url = $url;
    }
}  


