<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Comments\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table,
    \RS\Html\Filter;

class Ctrl extends \RS\Controller\Admin\Crud
{
    protected
        /**
        * @var \Comments\Model\Api
        */
        $api;
    
    function __construct()
    {
        parent::__construct(new \Comments\Model\Api());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Здесь отображаются комментарии ко всем объектам на сайте, для которых доступно комментирование. Воспользуйтесь фильтром, чтобы отобрать комментарии к нужному объекту. В настройках модуля можно установить, необходимо ли премодерировать комментарии, возможно ли написать более одного комментария к одному объекту с одного IP, а также другие настройки.'));
        $helper->setTopTitle(t('Комментарии'));

        $href = $this->router->getAdminPattern('edit', array(':id' => '@id'));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Viewed(null, $this->api->getMeterApi()),
                new TableType\Text('message', t('Комментарий'), array('href' => $href, 'LinkAttr' => array('class' => 'crud crud-edit'))),
                new TableType\Text('aid', t('Связь №'), array('Sortable' => SORTABLE_BOTH, 'Hidden' => true)),
                new TableType\Text('ip', 'IP'),                
                new TableType\StrYesno('moderated', t('Промодерированно')),
                new TableType\Datetime('dateof', t('Дата'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Userfunc('type', t('Тип'), function($value, $cell) {
                    return $cell->getRow()->getTypeObject()->getTitle();
                }, array('TdAttr' => array('class' => 'cell-sgray'))),                
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),                
                new TableType\Actions('id', array(
                                new TableType\Action\Edit($href),
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
        ))));
        
        $typelist = array('' => t('Любой тип')) + $this->api->getTypeList();
        $helper->setFilter(new Filter\Control( array(
            'container' => new Filter\Container( array( 
                                'lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('id','№'),
                                                            new Filter\Type\Select('type', t('Категория'), $typelist ),
                                                            new Filter\Type\Select('moderated', t('Промодерированно?'),array(
                                                                '' => t('Не важно'),
                                                                0 => t('Нет'),
                                                                1 => t('Да'),
                                                            )),
                                                            new Filter\Type\Text('aid', t('Связь №')),
                                                            new Filter\Type\Date('dateof', t('Дата'), array('showtype' => true)),
                                                        )
                                    ))
                                ),
                                'open' => true
                            )),
            'field_prefix' => $this->api->getElementClass()
        )));
        
        $helper->setBottomToolbar($this->buttons(array('delete')));
        return $helper;
    }
    
    function actionEdit()
    {
        $elem = $this->api->getElement();
        $elem['__type']->setReadOnly();
        return parent::actionEdit();
    }
    
    function helperEdit($primaryKey = null)
    {
        $helper = parent::helperEdit();
                
        $id = $this->url->get('id', TYPE_STRING, 0);
        $helper['bottomToolbar']->addItem(
            new ToolbarButton\delete( $this->router->getAdminUrl('delComment', array('id' => $id, 'dialogMode' => $this->url->request('dialogMode', TYPE_INTEGER))), null, array(
                'noajax' => true,
                'attr' => array(
                    'class' => 'delete crud-get crud-close-dialog',
                    'data-confirm-text' => t('Вы действтельно хотите удалить данный комментарий?')
                )
            )), 'delete'
        );
        
        return $helper;
    }
    
    function actionDelComment()
    {
        $id = $this->url->request('id', TYPE_INTEGER);
        if (!empty($id)) {
            $comment = new \Comments\Model\Orm\Comment($id);
            $comment->delete();
        }
        
        if (!$this->url->request('dialogMode', TYPE_INTEGER)) {
            $this->result->setAjaxWindowRedirect($this->url->getSavedUrl($this->controller_name.'index'));
        }
        
        return $this->result
            ->setSuccess(true)
            ->setNoAjaxRedirect($this->url->getSavedUrl($this->controller_name.'index'));
    }
    
    
}


