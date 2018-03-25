<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;

/**
* Базовый класс блочных и фронтальных контроллеров администраторской части
*/
abstract class AbstractAdmin extends AbstractModule
{
    /**
    * Возвращает false, если нет ограничений на запуск контроллера, иначе вызывает исключение 404.
    * Вызывается при запуске метода exec() у контроллера, перед исполнением действия(action).
    * В методе можно проверять права доступа ко всему контроллеру или к конкретному действию.
    * 
    * @return bool(false)
    */
    function checkAccessRight()
    {
        //Для доступа к контроллеру пользователь должен быть администратором
        if (!\RS\Application\Auth::getCurrentUser()->isAdmin() || \RS\Application\Auth::getCurrentUser()->getRight($this->mod_name) < $this->access_right) {
            return t('Недостаточно прав к запрашиваемому модулю "%0"', array($this->mod_name));
        }
        return false;
    }
}
