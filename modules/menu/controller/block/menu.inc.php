<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Controller\Block;
use \RS\Orm\Type;

/**
* Блок - горизонтальное меню
*/
class Menu extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Меню',
        $controller_description = 'Отображает публичные пункты меню';

    protected
        $default_params = array(
            'indexTemplate' => 'blocks/menu/hor_menu.tpl',
            'root' => 0
        );     
    
    public
        $api;
    
    function init()
    {
        $this->api = new \Menu\Model\Api();
        
        \RS\Event\Manager::fire('init.api.'.$this->getUrlName(), $this);
    }       
        
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'root' => new Type\Varchar(array(
                'description' => t('Какой элемент принимать за корневой?'),
                'list' => array(array('Menu\Model\Api', 'selectList'))
            ))
        ));
    }
    
    function actionIndex()
    {
        if ($debug_group = $this->getDebugGroup()) {
            $create_href = $this->router->getAdminUrl('add', array(), 'menu-ctrl');
            $debug_group->addDebugAction(new \RS\Debug\Action\Create($create_href));
            $debug_group->addTool('create', new \RS\Debug\Tool\Create($create_href));
        }
        
        $root = $this->getParam('root');
        //Кэшируем меню только для неавторизованных пользователей, 
        //т.к. авторизованные могут иметь различные права доступа к пунктам меню
        $menu_vars = $this->api->getMenuItems($root);
        
        $this->view->assign($menu_vars);
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
    
}