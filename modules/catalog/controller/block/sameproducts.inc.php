<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Block;
use \RS\Orm;

/**
 * Блок-контроллер Список категорий
 */
class SameProducts extends \RS\Controller\StandartBlock
{
    public 
            $api;

    protected static
            $controller_title = 'Похожие товары',
            $controller_description = 'Отображает товары из категории текущего с заданным отклонением цены';
    
    protected
            $default_params = array(
                'indexTemplate' => 'blocks/sameproducts/same_products.tpl',
                'delta' => 10,
                'page_size' => 10
            );

    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'delta' => new Orm\Type\Integer(array(
                'description' => t('Допустимое отклонение стоимости товаров, %'),
                'attr' => array(array(
                    'placeholder' => $this->default_params['delta']
                ))
             )),
            'page_size' => new Orm\Type\Integer(array(           
                'description' => t('Количество товаров'),
                'attr' => array(array(
                    'placeholder' => $this->default_params['page_size']
                ))                
             ))
        ));
    }

    function actionIndex()
    {
        $this->api = new \Catalog\Model\Api();
        $route = \RS\Router\Manager::obj()->getCurrentRoute();
        if ($route->getId() == 'catalog-front-product') {
            if (isset($route->product)) {
                $same_products = $this->api->getSameByCostProducts($this->router->getCurrentRoute()->product, $this->getParam('delta'), $this->getParam('page_size'));
                $this->view->assign('sameproducts', $same_products);
            }
        }

        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }

}