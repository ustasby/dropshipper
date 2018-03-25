<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Config;
use \Shop\Model\Orm\OrderItem;
use Shop\Model\Orm\UserStatus;

/**
* Патчи к модулю
*/
class Patches extends \RS\Module\AbstractPatches
{
    /**
    * Возвращает массив имен патчей.
    */
    function init()
    {
        return array(
            '20073',
            '20063',
            '20087',
            '200152',
            '200198',
            '200197',
            '3021'
        );
    }

    /**
     * Добавляем для всех мультисайтов причины отмены заказов
     */
    function afterUpdate3021()
    {
        $sites = \RS\Site\Manager::getSiteList(false);
        if (!empty($sites)){
            $module = new \RS\Module\Item('shop');
            $installer = $module->getInstallInstance();
            foreach($sites as $site) {
                $installer->importCsv(new \Shop\Model\CsvSchema\SubStatus(), 'substatus', $site['id']);
            }
        }
    }
    
    /**
    * Патч для релиза 2.0.0.73 и ниже
    * Проставляет сортировки в доставках и оплатах
    */
    function afterUpdate20073()
    {
        //Обновление доставок
        $q = \RS\Orm\Request::make()
            ->update(new \Shop\Model\Orm\Delivery())
            ->set("`sortn` = `id`")
            ->where('sortn = 0')
            ->exec();
        
        //Обновление оплат    
        $q = \RS\Orm\Request::make()
            ->update(new \Shop\Model\Orm\Payment())
            ->set("`sortn` = `id`")
            ->where('sortn = 0')
            ->exec();
    } 
    
    /**
    * Плагие проставляет всем заказам уникальные номера заказа
    * 
    */
    function afterUpdate20063()
    {
        //Подгрузим все заказы
        \RS\Orm\Request::make()
            ->update(new \Shop\Model\Orm\Order())
            ->set('order_num = id')
            ->where('order_num IS NULL')
            ->exec();
    }    
    
    /**
    * Патч проставляет site_id идентификатор для адреса доставки
    */
    function afterUpdate20087()
    {
        \RS\Orm\Request::make()
            ->update(new \Shop\Model\Orm\Address())->asAlias('A')
            ->update(new \Shop\Model\Orm\Order())->asAlias('O')
            ->set('A.site_id = O.site_id')
            ->where('A.id = O.use_addr')
            ->exec();
        
        //Оставшиеся адреса привяжем к текущему сайту
        \RS\Orm\Request::make()
            ->update(new \Shop\Model\Orm\Address())
            ->set(array(
                'site_id' => \RS\Site\Manager::getSiteId()
            ))
            ->where('site_id IS NULL')
            ->exec();
    }
    
    /**
    * Устанавливаем дату обновления равную дате создания всем старым заказам
    */
    function afterUpdate200152()
    {
        \RS\Orm\Request::make()
            ->update(new \Shop\Model\Orm\Order())
            ->set('dateofupdate = dateof')
            ->where('dateofupdate IS NULL')
            ->exec();
    }
    
    /**
    * Добавляет ещё один статус для онлайн выбивания чеков
    */
    function afterUpdate200198()
    {
        //Получим текущие сайты
        $sites = \RS\Site\Manager::getSiteList(false);
        if (!empty($sites)){
            $statuses = UserStatus::getDefaultStatues();
            $status   = $statuses[UserStatus::STATUS_NEEDRECEIPT];

            $new_user_status = new UserStatus();
            $new_user_status->getFromArray($status);
            $new_user_status['type'] = UserStatus::STATUS_NEEDRECEIPT;
            $new_user_status['is_system'] = 1;

            foreach ($sites as $site){
               unset($new_user_status['id']);
               $new_user_status['site_id'] = $site['id'];
               $new_user_status->insert();
            }
        }
    }

    /**
     * Установим флаги системным статусам
     */
    function afterUpdate200197()
    {
        $statuses = UserStatus::getDefaultStatusesTitles();
        \RS\Orm\Request::make()
            ->update(new UserStatus() )
            ->set(array(
                'is_system' => 1
            ))
            ->whereIn('type', array_keys($statuses))
            ->exec();
    }
}
