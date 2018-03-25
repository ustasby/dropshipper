<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Support\Controller\Admin;

use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table;

class SupportCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $api;
        
    function __construct()
    {
        parent::__construct(new \Support\Model\Api);
    }
    
    function helperIndex()
    {
        $topic_id = $this->url->request('id', TYPE_INTEGER);
        $topic = new \Support\Model\Orm\Topic($topic_id);
        $this->api->setFilter('topic_id', $topic_id);
        

        $helper = parent::helperIndex();
        $helper->setTopTitle($topic->title);             
        
        $helper->setTopToolbar(null);
        $helper->setListFunction('getReverseList');
        $this->api->setOrder('id desc');
        
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new \RS\Html\Table\Type\Text('dateof', t('Дата'), array('Sortable' => SORTABLE_BOTH, 'ThAttr' => array('width' => 150))),
                new \RS\Html\Table\Type\Usertpl('is_admin', '', '%support%/user_type_cell.tpl', array('ThAttr' => array('width' => 15))),
                new \RS\Html\Table\Type\Text('message', t('Сообщение')),
                new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                                'attr' => array('@data-id' => '@id')
                            )
                        ),
                        new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('удалить'),
                                    'attr' => array(
                                        '@href' => $this->router->getAdminPattern('del', array(':id' => '~field~')),
                                        'class' => 'crud-remove-one'
                                    )
                                ),
                            )
                        ),
                    )
                ),
            ))
        ));
        
        $helper->setBottomToolbar(null);
        return $helper;
    } 
    
    function actionIndex()
    {
        $helper = $this->getHelper();
        
        $this->app->addCss($this->mod_css.'/support.css', null, BP_ROOT);
        
        $topic_id = $this->url->request('id', TYPE_INTEGER);
        $topic = new \Support\Model\Orm\Topic($topic_id);
        
        
        if($this->url->isPost()){

            // Помечаем как прочитанные
            if (\RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE) === false ) 
            {
                $this->api->markViewedList($topic_id, false);

                $msg = $this->url->post('msg', TYPE_STRING);
                $support_message = new \Support\Model\Orm\Support;
                $support_message->message = \RS\Helper\Tools::toEntityString($msg);
                $support_message->user_id = \RS\Application\Auth::getCurrentUser()->id;
                $support_message->dateof  = date('Y-m-d H:i:s');
                $support_message->topic_id  = $topic_id;
                $support_message->is_admin = 1;
                $support_message->insert();
            }
            $this->redirect($this->url->selfUri());
        }
       
        $this->view->assign('topic', $topic);
        $this->view->assign('elements', $helper->active());
        return $this->result->setTemplate('adminview.tpl');
    }

}

