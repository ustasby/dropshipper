<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Main\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Table;

/**
* Контроллер списка маршрутов. В данном разделе можно увидеть все маршруты, которые присутствуют в системе
* @ingroup Main
*/
class Routes extends \RS\Controller\Admin\Front
{
    function actionIndex()
    {
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, null, $this->url);
        $helper->setTopTitle(t('Маршруты в системе'));
        $helper->setTopHelp($this->view->fetch('help/routes_index.tpl'));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Text('id', 'ID'),
                new TableType\Text('description', t('Описание')),
                new TableType\Usertpl('patterns', t('Маска пути'), '%main%/pageseo_column_route.tpl', array('TdAttr' => array('class' => 'cell-small') )),
                new TableType\StrYesno('hidden', t('Скрытый'))
            )
        )));
        
        $uri = $this->url->request('uri', TYPE_STRING, false);
        $host = $this->url->request('host', TYPE_STRING, $this->url->server('HTTP_HOST', TYPE_STRING));
        
        
        $routes = \RS\Router\Manager::obj()->getRoutes();
        $data = array();
        $i = 0;
        $selected = null;
        foreach($routes as $route) {
            $data[$i] = array(
                'id' => $route->getId(),
                'patterns' => $route->getPatternsView(),
                'description' => $route->getDescription(),
                'hidden' => $route->isHidden()
            );
            if (!$selected && $route->match($host, $uri, false)) {
                $helper['table']->getTable()->setRowAttr($i, array('class' => 'hl-row'));
                $selected = $route->getId();
            }
            $i++;
        }
        
        $helper['table']->getTable()->setData($data);
        
        $this->view->assign(array(
            'elements' => $helper,
            'uri' => $uri,
            'host' => $host,
            'selected' => $selected
        ));

        $helper->viewAsAny();
        $helper['form'] = $this->view->fetch('routes.tpl');

        return $this->result->setTemplate($helper['template']);
    }
}
