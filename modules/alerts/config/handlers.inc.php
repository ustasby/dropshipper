<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Alerts\Config;

use Alerts\Model\Orm\NoticeLock;
use RS\Orm\Type\ArrayList;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getmenus')
            ->bind('getroute')
            ->bind('alerts.getsmssenders')
            ->bind('orm.init.users-user')
            ->bind('orm.afterwrite.users-user')
            ->bind('getapps');
    }
    
    public static function alertsGetSmsSenders($list)
    {
        $list[] = new \Alerts\Model\SMS\SMSUslugi\Sender();
        return $list;
    }
    
    public static function getRoute($routes) 
    {
        return $routes;
    }    
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
          
       $items[] = array(
                'typelink'  => 'separator',
                'alias'     => 'alerts_separator',
                'parent'    => 'website',
                'sortn'     => 80
            );
       $items[] = array(
                'title'     => t('Уведомления'),
                'alias'     => 'alerts',
                'link'      => '%ADMINPATH%/alerts-ctrl/',
                'typelink'  => 'link',
                'parent'    => 'website',
                'sortn'     => 90
            );
       return $items;
    }
    
    /**
    * Привносим Desktop приложение Уведомления ReadyScript в список приложений для API
    * 
    * @param \NotifierApp\Model\AppTypes\Notifier[] $app_types
    * @return \NotifierApp\Model\AppTypes\Notifier[]
    */
    public static function getApps($app_types)
    {
        $app_types[] = new \Alerts\Model\AppTypes\Notifier();
        return $app_types;
    }

    /**
     * Расширяем объект пользователя, добавляем поля, связанные с выбором
     * доступных типов уведомлений для Desktop приложения
     *
     * @param \Users\Model\Orm\User $user
     */
    public static function ormInitUsersUser($user)
    {
        $user->getPropertyIterator()->append(array(
            t('Desktop-уведомления'),
                'desktop_notice_locks' => new ArrayList(array(
                    'description' => t('Запретить Desktop уведомления'),
                    'hint' => t('Отметьте уведомления, которые не будут доступны в Desktop приложении для данного пользователя'),
                    'template' => '%alerts%/form/user/user_desktop_notices.tpl',
                    'alerts_api' => new \Alerts\Model\Api(),
                    'sites' => \RS\Site\Manager::getSiteList(),
                    'meVisible' => false,
                ))
        ));
    }

    /**
     * Сохраняем сведения о запретах на Desktop уведомления
     *
     * @param $param
     */
    public static function ormAfterwriteUsersUser($param)
    {
        $user = $param['orm'];

        if ($user->isModified('desktop_notice_locks')) {
            $site_id = \RS\Site\Manager::getSiteId();

            \RS\Orm\Request::make()
                ->delete()
                ->from(new NoticeLock())
                ->where(array(
                    'user_id' => $user['id']
                ))->exec();

            foreach($user['desktop_notice_locks'] as $site_id => $data) {
                foreach($data as $type) {
                    $lock = new NoticeLock();
                    $lock['site_id'] = $site_id;
                    $lock['user_id'] = $user['id'];
                    $lock['notice_type'] = $type;
                    $lock->insert();
                }
            }
        }
    }
}