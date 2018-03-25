<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Controller\Front;

/**
* Фронт контроллер страницы-статьи, которая добавлена через меню
*/
class MenuPage extends \RS\Controller\Front
{
    function actionIndex()
    {
        /**
        * @var \Menu\Model\Orm\Menu $menu_item
        */
        $menu_item = $this->url->parameters('menu_object');
        $this->app->title->addSection($menu_item['title']);//добавить тайтл по названию пункта меню
        //Наполняем Хлебные крошки
        $api  = new \Menu\Model\Api();
        $path = $api->queryParents($menu_item['id']);
        foreach($path as $item) {
            if ($item['public']) {
                $this->app->breadcrumbs->addBreadCrumb($item['title'], $item->getHref());
            }
        }
        
        //Устанавливаем инструменты для режима отладки
        if ($debug_group = $this->getDebugGroup()) {
            $create_href = $this->router->getAdminUrl('edit', array('id' => $menu_item['id']), 'menu-ctrl');
            $debug_group->addDebugAction(new \RS\Debug\Action\Edit($create_href));
            $debug_group->addTool('edit', new \RS\Debug\Tool\Edit($create_href));
        }        
        
        //Формируем вывод
        $this->view->assign( 
            array('menu_item' => $menu_item) +
            $menu_item->getTypeObject()->getTemplateVar() 
        );
        
        if ($template = $menu_item->getTypeObject()->getTemplate()) {
            return $this->result->setTemplate($template);
        }
    }
    
}

