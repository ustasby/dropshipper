<?php
namespace DataBlocks\Controller\Block;

use DataBlocks\Model\DataNodeApi;
use RS\Orm\ControllerParamObject;
use RS\Orm\PropertyIterator;
use RS\Orm\Type;

/*
 * Блок-контроллер
 */
class NodeView extends \RS\Controller\Block
{
    protected static
        $controller_title = 'Просмотр элемента структуры данных',
        $controller_description = 'Позволяет отображать дерево элементов структуры данных';

    public function getParamObject()
    {
        return new ControllerParamObject(new PropertyIterator(array(
            'node_alias' => new Type\Varchar(array(
                'description' => t('Идентификатор элемента'),
                'hint' => t('Данный идентификатор будет перекрывать идентификатор полученный из URL')
            ))
        )));
    }

    function actionIndex()
    {
        $node_id = null;
        if ($this->router->getCurrentRoute()->getId() == 'datablocks-front-nodeview') {
            $node_id = $this->url->get('alias', TYPE_STRING);
        }

        $node_id = $this->getParam('node_alias', $node_id);

        $data_node_api = new DataNodeApi();
        $node = $data_node_api->getById($node_id);

        $this->view->assign(array(
            'node' => $node
        ));

        return $this->result->setTemplate('blocks/nodeview/node_view_block.tpl');
    }
}