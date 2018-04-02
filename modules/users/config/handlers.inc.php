<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Config;
use \RS\Router;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('start')
            ->bind('getmenus')
            ->bind('getroute')
            ->bind('orm.afterwrite.site-site');
    }    
    
    public static function getRoute(array $routes)
    {
        
        $routes[] = new Router\Route('users.authadmin', '/authadmin/', array(
            'controller' => 'users-front-authadmin'
        ), t('Авторизация в административную панель'), true);
                
        $routes[] = new Router\Route('users-front-auth', array('/auth/{Act}/','/auth/'), null, t('Авторизация'));
        $routes[] = new Router\Route('users-front-register', '/register/', null, t('Регистрация пользователя'));
        $routes[] = new Router\Route('users-front-profile', '/my/', null, t('Профиль пользователя'), false, '^{pattern}$');
        
        return $routes;
    }
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
        $items[] = array(
                'title' => t('Учетные записи'),
                'alias' => 'users',
                'link' => '%ADMINPATH%/users-ctrl/',
                'typelink' => 'link',
                'parent' => 'userscontrol',  
                'sortn' => 10
            );
        $items[] = array(
                'title' => t('Группы'),
                'alias' => 'groups',
                'link' => '%ADMINPATH%/users-ctrlgroup/',
                'typelink' => 'link',
                'parent' => 'userscontrol',  
                'sortn' => 20
            );
        return $items;
    }
    
    /**
    * Очищает логи по вероятности
    */
    public static function start()
    {
        if (\Setup::$INSTALLED) {
            $config = \RS\Config\Loader::byModule('users');

            $clear_random = $config['clear_random'];
            $clear_hours  = $config['clear_for_last_time'];

            //Попытаемся очитить логи в зависимости от вероятности 
            srand();
            $value = rand(0,100);
            if ($value <= $clear_random){ //Если попали в вероятность
              $time = time()-($clear_hours * 60 * 60);
              //Очистим логи 
              \RS\Orm\Request::make()
                    ->delete()              
                    ->from(new \Users\Model\Orm\Log()) 
                    ->where("dateof < '".date('Y-m-d H:i:s',$time)."'")
                    ->exec();
            }
            
            //Сохраняем дату последнего посещения
            if (!\RS\Router\Manager::obj()->isAdminZone()) {
                \RS\Application\Auth::getCurrentUser()->saveVisitDate();
            }
        }
    }

    /**
     * Обработка события создания cайта, копирование прав доступа групп пользователей
     *
     * @param array $data - массив данных
     */
    public static function ormAfterwriteSiteSite($params)
    {
        if (\Setup::$INSTALLED) {
            $new_site_id = $params['orm']['id'];
            \Users\Model\GroupApi::cloneRightFromDefaultSite($new_site_id);
        }
    }
}


