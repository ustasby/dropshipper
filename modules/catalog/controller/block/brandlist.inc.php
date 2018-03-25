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
* Класс выводящи все бренды
*/
class BrandList extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Все бренды',        //Краткое название контроллера
        $controller_description = 'Выводит список всех брендов';  //Описание контроллера
        
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/brands/brands.tpl',
        );
        
    public
        $api;
        
    function init()
    {
        $this->api = new \Catalog\Model\BrandApi(); 
    }
        
        
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * @return \RS\Orm\ControllerParamObject | false
    */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
                'pageSize' => new Type\Integer(array(
                    'description' => t('Количество элементов для отображения. 0 - все'),
                    'default' => 0,
                )),
            ));
    }   
                          
    function actionIndex()
    {
        $pageSize = $this->getParam('pageSize', 0);
        $brands   = $this->api->getBrandsForBlock($pageSize);
        
        if ($debug_group = $this->getDebugGroup()) {
            $create_href = $this->router->getAdminUrl('add', array(), 'catalog-brandctrl');
            $debug_group->addDebugAction(new \RS\Debug\Action\Create($create_href));
            $debug_group->addTool('create', new \RS\Debug\Tool\Create($create_href));
        }                        
        
        $this->view->assign(array(
            'brands' => $brands
        ));
        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }
    
}                                   