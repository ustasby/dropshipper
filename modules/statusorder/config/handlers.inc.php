<?php
namespace StatusOrder\Config;
use RS\Orm\Type as OrmType;

/**
* Класс содержит обработчики событий, на которые подписан модуль
*/
class Handlers extends \RS\Event\HandlerAbstract
{

    function init()
    {
        $this
            ->bind('getroute')  //событие сбора маршрутов модулей
            ->bind('orm.init.shop-order')
            ->bind('orm.beforewrite.shop-order');
    }
    
    public static function getRoute(array $routes) 
    {        
        $routes[] = new \RS\Router\Route('statusorder-front-ctrl',
        array(
            '/statusorder/'
        ), null, 'Роут модуля StatusOrder');
        
        return $routes;
    }

    public static function ormInitShopOrder(\Shop\Model\Orm\Order $order)
    {
        $order->getPropertyIterator()->append(array( //Добавляем свойства к объекту             
            'track_number' => new OrmType\Varchar(array( //Тип поля.
                'maxLength' => 30, // Длина поля в базе 
                'description' => t('Трек-номер'), //Название поля 
                'deliveryVisible' => true,               
            )),
        ));
    }

    public static function ormBeforeWriteShopOrder ($data) {

        $order = $data['orm'];//Получаем объект заказа
        $flag = $data['flag'];//флаг

        if (isset($_POST['track-number'])){ 
            $order['track_number'] = \RS\Http\Request::commonInstance()->request('track-number', TYPE_STRING); 
        }
    }

}