<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Config;

/**
* Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getmenus')
            ->bind('getroute');
    }
    
    public static function getRoute($routes) 
    {
        $routes[] = new \RS\Router\Route('feedback-front-form', array(
            '/feedback-{form_id:[\d]+}/'
        ), null, t('Форма связи'));

        return $routes;
    }    
    
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
         //Добавляем пункт меню
        $items[] = array(
                'title' => t('Формы'),
                'alias' => 'connectform',
                'link' => '%ADMINPATH%/feedback-resultctrl/', //здесь %ADMINPATH% - URL админ. панели; feedback - модуль; resultctrl - класс фронт контроллера
                'typelink' => 'link', //Тип пункта меню - ссылка
                'parent' => 'modules', //Alias родителя. Пункт будет добавлен в раздел "Разное", у него alias = modules
                'sortn' => 0,
            );
        $items[] = array(
                'title' => t('Результаты форм'),
                'alias' => 'formresult',
                'link' => '%ADMINPATH%/feedback-resultctrl/', //здесь %ADMINPATH% - URL админ. панели; feedback - модуль; resultctrl - класс фронт контроллера
                'typelink' => 'link', //Тип пункта меню - ссылка
                
                'parent' => 'connectform', //Alias родителя. Пункт будет добавлен в раздел "Разное", у него alias = modules
                'sortn' => 1,
            );
        $items[] = array(
                'title' => t('Конструктор форм'),
                'alias' => 'formconstructor',
                'link' => '%ADMINPATH%/feedback-ctrl/', //здесь %ADMINPATH% - URL админ. панели; feedback - модуль; ctrl - класс фронт контроллера
                'typelink' => 'link', //Тип пункта меню - ссылка
                'parent' => 'connectform', //Alias родителя. Пункт будет добавлен в раздел "Разное", у него alias = modules
                'sortn' => 2,
            );
        return $items;
    }
    
}