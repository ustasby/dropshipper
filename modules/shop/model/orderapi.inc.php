<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;
use \Shop\Model\Orm\UserStatus,
    \RS\Module\AbstractModel;

/**
* API для работы с заказами
*/
class OrderApi extends AbstractModel\EntityList
                implements \Main\Model\NoticeSystem\HasMeterInterface
{
    const
        /**
         * Идентификатор счетчика заказов
         */
        METER_ORDER = 'rs-admin-menu-allorders',

        ORDER_FILTER_ALL = 'all',
        ORDER_FILTER_SUCCESS = 'success',
        
        ORDER_SHOW_TYPE_NUM = 'num',
        ORDER_SHOW_TYPE_SUMM = 'summ';
        
    
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Order,
        array(
            'multisite' => true,
            'defaultOrder' => 'id DESC'
        ));
    }

    /**
     * Возвращает класс, который отвечает за управление счетчиками просмотров
     *
     * @param integer|null user_id ID пользователя. Если пользователь не задан, то используется текущий пользователь
     * @return \Main\Model\NoticeSystem\MeterApi
     */
    function getMeterApi($user_id = null)
    {
        return new \Main\Model\NoticeSystem\MeterApi($this->obj_instance,
                                                     self::METER_ORDER,
                                                     $this->getSiteContext(),
                                                     $user_id);
    }
    
    /**
    * Возвращает статистику по заказам
    * 
    * @return array
    */
    function getStatistic()
    {
        $query = \RS\Orm\Request::make()
            ->select('COUNT(*) cnt')
            ->from($this->obj_instance);
        
        $queries = array();
        $queries['total_orders'] = $query;
        $queries['open_orders'] = clone $query->where("status NOT IN (#statuses)", array(
            'statuses' => implode(',', UserStatusApi::getStatusesIdByType(UserStatus::STATUS_SUCCESS))
        ));
        $queries['closed_orders'] = clone $query->whereIn('status', UserStatusApi::getStatusesIdByType(UserStatus::STATUS_SUCCESS));
        $queries['last_order_date'] = \RS\Orm\Request::make()
            ->select('dateof cnt')
            ->from($this->obj_instance)
            ->where(array('site_id' => \RS\Site\Manager::getSiteId()))
            ->orderby('dateof DESC')->limit(1);
        
        foreach($queries as &$one) {
            $one = $one->exec()->getOneField('cnt', 0);
        }
        return $queries;
    }
    
    /**
    * Возвращает года, за которые есть статистика
    * 
    * @param integer $lastrange максимальное количество годов в списке
    * @return array
    */
    function getOrderYears($order_filter = self::ORDER_FILTER_ALL, $lastrange = 5)
    {
        $site_id = \RS\Site\Manager::getSiteId();
        $q = \RS\Orm\Request::make()
                ->select('YEAR(dateof) as year')
                ->from($this->obj_instance)
                ->where('dateof >= NOW()-INTERVAL #lastrange YEAR', array('lastrange' => $lastrange))
                ->where(array('site_id' => $site_id))
                ->groupby('YEAR(dateof)');
                
        if ($order_filter == self::ORDER_FILTER_SUCCESS) {
            $statuses_id = UserStatusApi::getStatusesIdByType(Orm\UserStatus::STATUS_SUCCESS);
            $q->whereIn('status', $statuses_id);
        }
        
        return $q->exec()->fetchSelected(null, 'year');
    }
    
    /**
    * Возвращает даты заказов, сгруппированные по годам. Для видета статистики
    * 
    * @param string $order_filter фильтр заказов. Если all - то все заказы, success - только завершенные
    * @param mixed $lastrange
    * @param bool $cache - флаг кэширования, если true, то кэш будет использоваться
    * @return array
    */
    function ordersByYears($order_filter = self::ORDER_FILTER_ALL, $show_type = self::ORDER_SHOW_TYPE_NUM, $lastrange = 5, $cache = true)
    {
        $site_id = \RS\Site\Manager::getSiteId();
        
        if ($cache) {
            $result = \RS\Cache\Manager::obj()
                ->expire(300)
                ->request(array($this, 'ordersByYears'), $order_filter, $show_type, $lastrange, false, $site_id);
        } else {
            $q = \RS\Orm\Request::make()
                ->select('dateof, COUNT(*) cnt, SUM(totalcost) total_cost')
                ->from($this->obj_instance)
                ->where('dateof >= NOW()-INTERVAL #lastrange YEAR', array('lastrange' => $lastrange))
                ->where(array('site_id' => $site_id))
                ->groupby('YEAR(dateof), MONTH(dateof)')
                ->orderby('dateof');
            
            if ($order_filter == self::ORDER_FILTER_SUCCESS) {
                $statuses_id = UserStatusApi::getStatusesIdByType(Orm\UserStatus::STATUS_SUCCESS);
                $q->whereIn('status', $statuses_id);
            }
            
            $res = $q->exec();
            $result = array();
            while($row = $res->fetchRow()) {
                $date = strtotime($row['dateof']);
                $year = date('Y', $date);
                $result[$year]['label'] = $year;
                $result[$year]['data'][date('n', $date)-1] = array(
                    'x' => mktime(4,0,0, date('n', $date), 1)*1000,
                    'y' => $show_type == self::ORDER_SHOW_TYPE_NUM ? $row['cnt'] : $row['total_cost'],
                    'pointDate' => $date*1000,
                    'total_cost' => $row['total_cost'],
                    'count' => $row['cnt']
                );
            }
            
            //Добавляем нулевые месяцы
            foreach($result as $year=>$val) {
                $month_list = array();
                for($month=1; $month<=12; $month++) {
                    $month_list[$month-1] = isset($result[$year]['data'][$month-1])? $result[$year]['data'][$month-1] : array(
                        'x' => mktime(4,0,0, $month, 1)*1000,
                        'y' => 0,
                        'pointDate' => mktime(4,0,0, $month, 1, $year)*1000,
                        'total_cost' => 0,
                        'count' => 0
                    );
                }
                $result[$year]['data'] = $month_list;
            }

        }
        return $result;
    }
    
    /**
    * Возвращает даты заказов, сгруппированные по годам. Для видета статистики
    * 
    * @param mixed $lastrange
    * @param string $order_filter фильтр заказов. Если all - то все заказы, success - только завершенные
    * @param bool $cache - флаг кэширования, если true, то кэш будет использоваться
    * @return array
    */
    function ordersByMonth($order_filter = self::ORDER_FILTER_ALL, $show_type = self::ORDER_SHOW_TYPE_NUM, $lastrange = 1, $cache = true)
    {
        $site_id = \RS\Site\Manager::getSiteId();
        
        if ($cache) {
            $result = \RS\Cache\Manager::obj()
                ->expire(300)
                ->request(array($this, 'ordersByMonth'), $order_filter, $show_type, $lastrange, false, $site_id);
        } else {
            $currency = \Catalog\Model\CurrencyApi::getBaseCurrency()->stitle;
            $start_time = strtotime('-1 month');
            
            $q = \RS\Orm\Request::make()
                ->select('dateof, COUNT(*) cnt, SUM(totalcost) total_cost')
                ->from($this->obj_instance)
                ->where("dateof >= '#starttime'", array('starttime' => date('Y-m-d', $start_time)))
                ->where(array('site_id' => $site_id))
                ->groupby('DATE(dateof)')
                ->orderby('dateof');
            
            if ($order_filter == self::ORDER_FILTER_SUCCESS) {
                $statuses_id = UserStatusApi::getStatusesIdByType(Orm\UserStatus::STATUS_SUCCESS);
                $q->whereIn('status', $statuses_id);
            }
            
            $res = $q->exec();
            $min_date = null;
            $max_date = null;
            $result = array();
            while($row = $res->fetchRow()) {
                $date = strtotime($row['dateof']);
                if ($min_date === null || $date<$min_date) {
                    $min_date = $date;
                }
                if ($max_date === null || $date>$max_date) {
                    $max_date = $date;
                }
                $ymd = date('Ymd', $date);
                $result[0][$ymd] = array(
                    'x' => $date*1000,
                    'y' => $show_type == self::ORDER_SHOW_TYPE_NUM ? $row['cnt'] : $row['total_cost'],
                    'total_cost' => $row['total_cost'],
                    'count' => $row['cnt']
                );
            }
            
            //Заполняем пустые дни
            $i=0;
            $today = mktime(23,59,59);
            while( ($time = strtotime("+$i day",$start_time)) && $time <=$today ) {
                $ymd = date('Ymd', $time);
                if (!isset($result[0][$ymd])) {
                    $result[0][$ymd] = array(
                        'x' => $time*1000,
                        'y' => 0,
                        'total_cost' => 0,
                        'count' => 0
                    );
                }
                $i++;
            }
            ksort($result[0]);
            $result[0] = array_values($result[0]);
        }
        return $result;
    }    
    
    /**
    * Возвращает количество заказов для каждого из существующих статусов
    * 
    * @return array
    */
    function getStatusCounts()
    {
        $q = clone $this->queryObj();
        $q->select = 'status, COUNT(*) cnt';
        $q->groupby('status');
        return $q->exec()->fetchSelected('status', 'cnt');
    }
    
    /**
    * Генерирует уникальный идентификатор заказа
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    *
    * @return string
    */
    function generateOrderNum($order){
        $config    = \RS\Config\Loader::byModule('shop');
        $mask      = $config['generated_ordernum_mask'];
        $numbers   = $config['generated_ordernum_numbers'];
        
        $order_num = \RS\Helper\Tools::generatePassword($numbers,'0123456789');
        
        $code = mb_strtoupper(str_replace("{n}",$order_num,$mask));
        
        //Посмотрим есть ли такой код уже в базе
        $found_order = \RS\Orm\Request::make()
                ->from(new \Shop\Model\Orm\Order())
                ->where(array(
                    'order_num' => $code,
                    'site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->object();
                
        if ($found_order){
            return $this->generateOrderNum($order);
        }
                
        return $code;
    }
    
    /**
    * Возвращает количество заказов, оформленных пользователем
    * 
    * @param integer $user_id - id пользователя
    *
    * @return Orm\Order|false
    */
    function getUserOrdersCount($user_id)
    {
        return \RS\Orm\Request::make()
            ->from(new Orm\Order)
            ->where(array(
                'user_id' => $user_id,
                'site_id' => \RS\Site\Manager::getSiteId()
            ))->count();
    }
    
    /**
    * Создаёт отчёт о заказах за выбранный период и при заданных параметрах
    * 
    * @param \RS\Orm\Request $request - объект запроса списка заказов
    */
    function getReport(\RS\Orm\Request $request)
    {
       $list = array();
       //Соберём общую статистику 
       $request->select = ""; 
       $request->limit  = "";    
       $list['all'] = $request
            ->select('
                COUNT(id) as orderscount,
                SUM(totalcost) as totalcost,
                SUM(user_delivery_cost) as user_delivery_cost,
                SUM(deliverycost) as single_deliverycost
            ')
            ->exec()
            ->fetchRow();     
            
       //Цена без учёта стоимости доставки
       $list['all']['deliverycost']           = $list['all']['user_delivery_cost'] + $list['all']['single_deliverycost'];
       $list['all']['total_without_delivery'] = $list['all']['totalcost'] - $list['all']['deliverycost']; 
       //Соберём статистику по типам оплаты
       $request->select = "";
       $list['payment'] = $request
            ->select('
                payment,
                COUNT(id) as orderscount,
                SUM(totalcost) as totalcost,
                SUM(user_delivery_cost) as user_delivery_cost,
                SUM(deliverycost) as single_deliverycost
            ')
            ->groupby('payment')
            ->exec()
            ->fetchSelected('payment');
       
       if (!empty($list['payment'])){
            
           foreach($list['payment'] as $key=>$item){
              $list['payment'][$key]['deliverycost'] = $item['user_delivery_cost'] + $item['single_deliverycost']; 
              $list['payment'][$key]['total_without_delivery'] = $item['totalcost'] - $list['payment'][$key]['deliverycost'];  
           }
       }
       
       //Соберём статистику по типам доставки
       $request->select  = "";
       $request->groupby = "";
       $list['delivery'] = $request
            ->select('
                delivery,
                COUNT(id) as orderscount,
                SUM(totalcost) as totalcost,
                SUM(user_delivery_cost) as user_delivery_cost,
                SUM(deliverycost) as single_deliverycost
            ')
            ->groupby('delivery')
            ->exec()
            ->fetchSelected('delivery');  
       
       if (!empty($list['delivery'])){
           
           foreach($list['delivery'] as $key=>$item){
              $list['delivery'][$key]['deliverycost'] = $item['user_delivery_cost'] + $item['single_deliverycost']; 
           }
       }
            
       return $list;
    }
    
    /**
    * Добавляет массив дополнительных экстра данных в заказ, которые будут отображаться в заказе в админ панели
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param string $step - идентификатор шага оформления (address, delivery, payment, confirm)
    * @param array $order_extra - массив доп. данных
    * 
    * @return void
    */
    function addOrderExtraDataByStep(\Shop\Model\Orm\Order $order, $step = 'order', $order_extra = array())
    {
         if (!empty($order_extra)) { //Заносим дополнительные данные, если они есть
            $arr = array($step => $order_extra);
            $order['order_extra'] = array_merge((array)$order['order_extra'], $arr);
         }
    }
    
    /**
    * Ищет город по части слова
    * 
    * @param string $query - часть слова города для его запроса
    * @param integer $region_id - id региона
    * @param integer $country_id - id страны
    * 
    * @return array
    */
    function searchCityByRegionOrCountry($query, $region_id = false, $country_id = false)
    {
        $q = \RS\Orm\Request::make()
                ->from(new \Shop\Model\Orm\Region(), "R1")
                ->where("R1.title like '%".$query."%'")
                ->where(array(
                    'R1.site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->orderby('title');
        if ($region_id){ //Если задан регион
            $q->where(array(
                'R1.parent_id' => $region_id
            ));
        }elseif ($country_id){ //Если задана только страна
            $sql = \RS\Orm\Request::make()
                        ->select('R2.id')
                        ->from(new \Shop\Model\Orm\Region(), 'R2')
                        ->where(array(
                            'R2.site_id' => \RS\Site\Manager::getSiteId(),
                            'R2.parent_id' => $country_id
                        ))
                        ->toSql();
            $q->where('R1.parent_id IN ('.$sql.')');
        }
        $cities = $q->objects();
        return $cities ? $cities : array();
    }
    
    /**
    * Пересчитывает доходность ранее оформленных заказов на основе Закупочной цены товаров.
    * Процесс выполняется пошагово по $timeout сек.
    * 
    * @param integer $position - стартовая позиция
    * @param integer $timeout - лимит по времени выполнения одного шага в секундах
    * 
    * @return bool(true) | int - True в случае успешного завершения, иначе позиция для следующего запуска
    */
    public static function calculateOrdersProfit($position = 0, $timeout = 20)
    {
        $order_item = new Orm\OrderItem();
        $start_time = microtime(true);
        
        $offset = $position;
        $limit = 50;
        
        $q = \RS\Orm\Request::make()
            ->from($order_item)
            ->where('entity_id>0')
            ->where(array(
                'type' => Orm\OrderItem::TYPE_PRODUCT
            ))
            ->limit($limit);
        
        while($items = $q->offset($offset)->objects()) {
            $i = 0;
            foreach($items as $item) {
                $item['profit'] = $item->getProfit();
                if ($item['profit'] !== null) {
                    $item->update();
                }
                $i++;
                if ($timeout>0 && microtime(true)-$start_time > $timeout) return $offset + $i;
            }
            $offset += $limit;
        }

        $subsql = \RS\Orm\Request::make()
            ->select('SUM(profit)')
            ->from($order_item, 'I')
            ->where('I.order_id = O.id');
        
        $q = \RS\Orm\Request::make()
            ->update()
            ->from(new \Shop\Model\Orm\Order(), 'O')
            ->set('profit = ('.$subsql.')')
            ->exec();
        
        return true;
    }
    
    /**
    * Возвращает список пользователей-менеджеров заказов.
    * Группа, пользователи которой считаются менеджерами устанавливается в настройках модуля Магазин
    * 
    * @return array
    */
    public static function getUsersManagers()
    {
        if ($manager_group = \RS\Config\Loader::byModule(__CLASS__)->manager_group) {
            return \RS\Orm\Request::make()
                ->select('U.*')
                ->from(new \Users\Model\Orm\User(), 'U')
                ->join(new \Users\Model\Orm\UserInGroup(), 'G.user = U.id', 'G')
                ->where(array(
                    'G.group' => \RS\Config\Loader::byModule(__CLASS__)->manager_group
                ))
                ->objects(null, 'id');
        }
        return array();
    }
    
    /**
    * Возвращает список пользователей-менеджеров заказов.
    * Группа, пользователи которой считаются менеджерами устанавливается в настройках модуля Магазин
    * 
    * @return array
    */
    public static function getUsersManagersName($root = array())
    {
        $result = array();
        foreach(self::getUsersManagers() as $user) {
            $result[$user['id']] = $user->getFio()." ({$user['id']})";
        }
        return $root + $result;
    }

    /**
     * Создаёт новый заказ из переданного
     *
     * @param integer $order_id - id заказа, который надо повторить
     * @return \Shop\Model\Orm\Order $order
     */
    function repeatOrder($order_id)
    {
        $old_order = new \Shop\Model\Orm\Order($order_id);
        $new_order = new \Shop\Model\Orm\Order();
        $new_order->getFromArray($old_order->getValues());
        //Удалим ненужные поля
        $new_order->setTemporaryId();
        unset($new_order['order_num']);
        unset($new_order['ip']);
        unset($new_order['manager_user_id']);
        unset($new_order['create_refund_receipt']);
        unset($new_order['is_payed']);
        unset($new_order['status']);
        unset($new_order['basket']);
        unset($new_order['admin_comments']);
        unset($new_order['user_text']);
        unset($new_order['is_exported']);
        unset($new_order['userfields']);
        unset($new_order['profit']);
        unset($new_order['contact_person']);
        unset($new_order['comments']);
        unset($new_order['substatus']);
        unset($new_order['courier_id']);
        unset($new_order['user_delivery_cost']);
        $new_order['extra'] = $old_order['extra'];
        //Добавим в корзину тоже товары, которые присутствуют на сайте
        $new_cart = $new_order->getCart();
        $order_cart = $old_order->getCart()->getOrderData(); //Получим данные по корзине
        
        $items = array();
        foreach ($order_cart['items'] as $uniq_id=>$item) {
            $items[$uniq_id] = $item['cartitem']->getValues();
            $items[$uniq_id]['multioffers'] = unserialize($items[$uniq_id]['multioffers']);
        }
        $new_order->getCart()->updateOrderItems($items);
        
        //Посчитаем доставку
        $new_order['user_delivery_cost'] = null;
        
        return $new_order;
    }
}

