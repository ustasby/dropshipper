<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;

/**
* Контроллер мои заказы
*/
class MyOrders extends \RS\Controller\AuthorizedFront
{
    public 
        $api;
    
    function init()
    {
        $this->api = new \Shop\Model\OrderApi();
    }
    
    function actionIndex()
    {
        $this->app->title->addSection(t('Мои заказы'));
        $this->app->breadcrumbs->addBreadCrumb(t('Мои заказы'));

        $config = $this->getModuleConfig();
        $page = $this->url->request('p', TYPE_INTEGER);
        
        $this->api->setFilter('user_id', $this->user['id']);
        $paginator = new \RS\Helper\Paginator($page, $this->api->getListCount(), $config['user_orders_page_size']);        
        $order_list = $this->api->getList($page, $config['user_orders_page_size']);
            
        $this->view->assign(array(
            'order_list' => $order_list,
            'paginator' => $paginator
        ));
    
        return $this->result->setTemplate('myorders.tpl');
    }
}
?>
