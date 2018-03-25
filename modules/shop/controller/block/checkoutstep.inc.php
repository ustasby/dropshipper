<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Block;

/**
* Блок контроллер Шаг оформления заказа
*/
class CheckoutStep extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Текущий шаг оформления заказа',
        $controller_description = 'Отображает цепочку шагов оформления заказа, с подсветкой текущего и возможностью вернуться к предыдущему';
    
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/checkoutstep/step.tpl'
        );
        
    function actionIndex()
    {
        $step = 0;
        $route = $this->router->getCurrentRoute();
        if ($route->getId() == 'shop-front-checkout') {
            $act = isset($route->current_step) ? $route->current_step : $this->url->request('Act', TYPE_STRING);
            $config = $this->getModuleConfig();
            switch($act) {
                case 'address': $step = 1; break;
                case 'delivery': $step = 2; break;
                case 'warehouses': $step = 2; break;
                case 'payment': $config['hide_delivery'] ? $step = 2 : $step = 3;  break;
                case 'confirm': 
                        $config['hide_delivery'] ? $step = 3 : $step = 4;
                        $config['hide_payment'] ? $step-- : $step = $step;
                    break;
                case 'finish': 
                        $config['hide_delivery'] ? $step = 4 : $step = 5;
                        $config['hide_payment'] ? $step-- : $step = $step; 
                    break;
                default: $step = 1;
            }
        }
        $this->view->assign('step', $step);
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }

}