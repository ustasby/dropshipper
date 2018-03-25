<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Controller\Admin\Widget;

use Main\Model\FastLinkApi;

class FastLinks extends \RS\Controller\Admin\Widget
{
    protected
        $info_title = 'Ссылки',
        $info_description = 'Позволяет быстро переходить по заранее созданным ссылкам';

    function actionIndex()
    {
        $api = new FastLinkApi();

        $this->view->assign(array(
            'links' => $api->getList()
        ));
        return $this->result->setTemplate('%main%/widget/fastlinks.tpl');
    }

    function getTools()
    {
        $router = \RS\Router\Manager::obj();
        return array(
            array(
                'title' => t('Добавить ссылку'),
                'class' => 'zmdi zmdi-plus crud-add',
                'href' => $router->getAdminUrl('add', array('context' => 'widget'), 'main-fastlinksctrl'),
                '~data-crud-options' => "{ \"updateBlockId\": \"main-widget-fastlinks\" }",
                'id' => 'notes-add'
            ),
            array(
                'title' => t('Все ссылки'),
                'class' => 'zmdi zmdi-open-in-new',
                'href' => $router->getAdminUrl(false, array(), 'main-fastlinksctrl')
            )
        );
    }
}