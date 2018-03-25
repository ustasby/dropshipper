<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Admin\Widget;
use \Shop\Model\Orm\UserStatus;

/**
* Виджет - статусы заказов на круговой диаграмме
*/
class OrderStatuses extends \RS\Controller\Admin\Widget
{
    protected
        $orderapi,
        $info_title = 'Диаграмма статусов заказов',
        $info_description = 'Позволяет отслеживать статистику по выполнению заказов';
    
    
    function actionIndex()
    {
        $api = new \Shop\Model\OrderApi();
        $total = $api->getListCount();
        $counts = $api->getStatusCounts();
        $inwork = 0;
        $finished = 0;
        
        $status_api = new \Shop\Model\UserStatusApi();
        $statuses = $status_api->getAssocList('id');        
        
        $json_data = array();
        foreach($counts as $id => $count) {
            $json_data[] = array(
                'label' => (isset($statuses[$id]) ? $statuses[$id]['title'] : t('статус удален'))."($count)",
                'data' => (int)$count,
                'color' => isset($statuses[$id]) ? $statuses[$id]['bgcolor'] : ''
            );
            
            if (isset($statuses[$id])) {
                switch($statuses[$id]['type']) {
                    case UserStatus::STATUS_SUCCESS: {
                        $finished = $count;
                        break;
                    }
                    case UserStatus::STATUS_CANCELLED: {
                        break;
                    }
                    default: $inwork += $count;
                }
            }
        }
        
        $this->view->assign(array(
            'total' => $total,
            'counts' => $counts,
            'statuses' => $statuses,
            'inwork' => $inwork,
            'finished' => $finished,
            'json_data' => json_encode($json_data)
        ));
        
        return $this->view->fetch('widget/orderstatuses.tpl');
    }
}