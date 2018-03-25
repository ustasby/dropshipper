<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;

class Reservation extends \RS\Controller\Front
{
    /**
     * @var \Shop\Model\ReservationApi $api
     */
    public $api;

    function init()
    {
        $this->api = new \Shop\Model\ReservationApi();
    }

    function actionIndex()
    { 
        $product_id = $this->url->request('product_id', TYPE_INTEGER); 
        $product = new \Catalog\Model\Orm\Product($product_id); 
        
        if (!$product['id']) {
            $this->e404(t('Товар не найден'));
        }
        /**
        * @var \Shop\Model\Orm\Reservation $reserve
        */
        $reserve                = $this->api->getElement();
        $reserve['amount']      = $this->url->request('amount', TYPE_INTEGER, $product->getAmountStep());
        $reserve['phone']       = $this->user['phone'];
        $reserve['email']       = $this->user['e_mail'];
        $reserve['product_id']  = $product_id;
        $reserve['offer_id']    = $this->url->request('offer_id', TYPE_INTEGER, false);
        $reserve['multioffers'] = $this->url->request('multioffers', TYPE_ARRAY, null);
        
        if ($reserve['offer_id']) {
            $offer = new \Catalog\Model\Orm\Offer($reserve['offer_id']);
            $reserve['offer'] = $offer['title'];
        } else {
            $offer = \Catalog\Model\Orm\Offer::loadByWhere(array(
                                                            'product_id' => $reserve['product_id'],
                                                            'sortn' => 0
                                                        ));
            $reserve['offer']    = $offer['title'];
            $reserve['offer_id'] = $offer['id'];
        }
        
        $this->app->breadcrumbs
            ->addBreadCrumb($product['title'], $this->router->getUrl('catalog-front-product', array('id' => $product['_alias'])))
            ->addBreadCrumb(t('Заказать'));
        
        // если пользователь не авторизован - включим капчу
        if (!\RS\Application\Auth::isAuthorize()) {
            $reserve['__kaptcha']->setEnable(true);
        }
        
        $template = 'reservation.tpl';
        
        if ($this->url->isPost()) {
            //Сохраним предзаказ
            if ($this->api->save(null, array('is_notify' => 1))) {
                $template = 'reservation_success.tpl';
            }
        }   

        $this->view->assign(array(
            'reserve' => $reserve,
            'product' => $product,
        ));        
                
        return $this->result->setTemplate( $template );
    }
}