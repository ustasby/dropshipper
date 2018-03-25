<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Controller;

/**
* Контроллер, требующий для выполнения действия(actions) заданных прав у Пользователя.
*/
class AuthorizedFront extends Front
{
    protected
        /**
        * Псевдоним группы пользователей, в которой должен состоять пользователь
        * 
        * @var string
        */
        $need_group = null;
        
    /**
    * Проверяет является ли пользователь авторизованным,
    * соответствуют ли его права требуемым
    * 
    * @return mixed Возвращает false в случае отсутствия ошибок, иначе возвращает данные для отображения формы авторизации.
    */
    function checkAccessRight()
    {
        if ($this->user['id']<=0) {
            return $this->authPage();
        }
        
        if ( ($this->need_group !== null && !$this->user->inGroup($this->need_group)) 
            || ($this->user->getRight($this->mod_name) < $this->access_right) ) {
            return $this->authPage(t('Недостаточно прав для доступа в этот раздел'));
        }
        return false;
    }
    
}
?>
