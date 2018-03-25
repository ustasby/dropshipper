<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Block;
use \RS\Orm\Type;

/**
* Блок-контроллер Товары в виде баннера
*/
class BannerView extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Товары в виде баннера',
        $controller_description = 'Товары из заданных категорий отображаются в виде баннера';

    protected
        $action_var = 'bndo',
        $default_params = array(
            'indexTemplate' => 'blocks/bannerview/bannerview.tpl',
            'slideTemplate' => 'blocks/bannerview/slide.tpl'
        );
        
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * @return \RS\Orm\ControllerParamObject | false
    */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
                'categories' => new Type\ArrayList(array(
                    'description' => t('Товары каких спецкатегорий показывать?'),
                    'list' => array(array('\Catalog\Model\DirApi', 'specSelectList')),
                    'attr' => array(array(
                        'multiple' => 'multiple',
                        'size' => 10
                    ))
                )),
            ));
    }        

    function actionIndex()
    {
        $dir_api = new \Catalog\Model\Dirapi();
        $ids_or_aliases = $this->getParam('categories');
        $ids = array();
        foreach($ids_or_aliases as $some) {
            $ids[] = is_numeric($some) ? $some : (int)\Catalog\Model\Orm\Dir::loadByWhere(array('alias' => $some))->id;
        }
              
        if (!empty($ids)) {
            $dir_api->setFilter('id', (array)$ids, 'in');
            $dirs = $dir_api->getList();
            $current_dir = reset($dirs)->id;
            $element_html = $this->actionGetSlide( $current_dir )->getHtml();
            if ($element_html) {
                $this->view->assign(array(
                    'dirs' => $dirs,
                    'current_dir' => $current_dir,
                    'element_html' => $element_html
                ));
            }
        }
        return $this->result->setTemplate( $this->getParam('indexTemplate') );        
    }
    
    /**
    * Возвращает HTML одного товара
    */
    function actionGetSlide($default_dir = null)
    {
        $item = $this->url->post('item', TYPE_INTEGER);
        $dir = $this->url->post('dir', TYPE_INTEGER, $default_dir);
        if ($dir>0) {
            $product_api = new \Catalog\Model\Api();
            $product_api
                ->setFilter('dir', $dir)
                ->setFilter('public', 1);
            $product = $product_api->getList($item + 1, 1);
            
            $total = \RS\Cache\Manager::obj()->request(array($product_api, 'getListCount'), $dir);
        }
        
        if (!empty($product)) {
            $product = reset($product);
            $this->view->assign(array(
                'product' => $product,
                'item' => $item,
                'dir' => $dir,
                'total' => $total
            ));
            $this->result->setTemplate( $this->getParam('slideTemplate') );
        }
        return $this->result;
    }
}