<?php
namespace DataBlocks\Controller\Front;
use DataBlocks\Model\DataNodeApi;
use RS\Controller\Front;

/**
* Фронт контроллер, выводящий один узел
*/
class NodeView extends Front
{
    public $data_node_api;

    function init()
    {
        $this->data_node_api = new DataNodeApi();
    }

    function actionIndex()
    {
        $alias = $this->url->get('alias', TYPE_STRING);
        $node = $this->data_node_api->getById($alias);

        if (!$node['id'] || !$node['public']) {
            $this->e404();
        }

        //Строим хлебные крошки
        foreach($node->getPathToFirst() as $item) {
            $this->app->title->addSection($item['title']);
            $this->app->breadcrumbs->addBreadCrumb($item['title'], $item->getUrl());
        }

        //Заполняем мета-теги
        $this->app->title->addSection($node['meta_title'] ?: $node['title']);
        $this->app->meta->addKeywords($node['meta_keywords'])
                        ->addDescriptions($node['meta_description']);

        $this->view->assign(array(
            'node' => $node
        ));

        return $this->result->setTemplate('node_view.tpl');
    }
}