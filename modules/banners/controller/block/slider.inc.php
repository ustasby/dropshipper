<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Controller\Block;
use \RS\Orm\Type;

class Slider extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Слайдер',
        $controller_description = 'Отображает зону, в которой последовательно отображаются баннеры';
    
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/slider/slider.tpl', //Должен быть задан у наследника
            'zone' => null
        );
    
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'zone' => new Type\Integer(array(
                'description' => t('Зона баннеров'),
                'list' => array(array('\Banners\Model\ZoneApi', 'staticSelectList'))
            ))
        ));
    }
    
    function actionIndex()
    {
        $zone_id = $this->getParam('zone');

        if ($debug_group = $this->getDebugGroup()) {
            $create_href = $this->router->getAdminUrl('add', array('zone' => $zone_id), 'banners-ctrl');
            $debug_group->addTool('create', new \RS\Debug\Tool\Create($create_href));
        }
                
        $zone_api = new \Banners\Model\ZoneApi();
        $zone = $zone_api->getById($zone_id);
        $this->view->assign(array(
            'zone' => $zone
        ));
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
    
    
}

?>
