<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table,
    \RS\Html\Filter;

class LogCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $api;
    
    function __construct()
    {
        parent::__construct(new \ExternalApi\Model\LogApi());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Журнал запросов к внешним API'));
        $helper->setTopToolbar($this->buttons(array()));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),                
                new TableType\Text('dateof', t('Дата'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC, 'LinkAttr' => array(
                    'class' => 'crud-edit'
                ),
                'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')),)),
                new TableType\Text('ip', t('IP'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Userfunc('user_id', t('Пользователь'), function($value, $cell) {
                    if ($value) {
                        $user = new \Users\Model\Orm\User($value);
                        return $user->getFio()."($value)";
                    }
                }),                
                new TableType\Text('method', t('Метод API'), array('Sortable' => SORTABLE_BOTH)),                
                new TableType\Text('error_code', t('Код ошибки'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('request_uri', t('URL запроса'), array('hidden' => true)),                
                new TableType\Text('client_id', t('ID приложения'), array('hidden' => true)),
                new TableType\Text('token', t('Авторизационный токен'), array('hidden' => true)),
                new TableType\Actions('id', array(
                                new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
        ))));
        
        $helper->setFilter(new Filter\Control( array(
            'container' => new Filter\Container( array( 
                                'lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\DateRange('dateof', t('Дата')),
                                                            new Filter\Type\Text('ip', t('IP')),
                                                            new Filter\Type\User('user_id', t('Пользователь')),
                                                            new Filter\Type\Text('method', t('Метод API'), array('searchType' => '%like%')),
                                                        )
                                    )),
                                ),
                                'SecContainers' => array(
                                    new Filter\Seccontainer( array(
                                    'lines' => array(
                                        new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('request_uri', t('URL запроса'), array('searchType' => '%like%')),
                                                            new Filter\Type\Text('error_code', t('Код ошибки')),                                    
                                                        )))
                                    )
                                ))),
                            )),
            
            'field_prefix' => $this->api->getElementClass()
        )));
        
        $helper->setBottomToolbar($this->buttons(array('delete')));
        return $helper;
    }
    
    function helperAdd()
    {
        $helper = parent::helperAdd();
        $helper->setBottomToolbar($this->buttons(array('cancel')));
        return $helper;
    }

    /**
     * Очистка лога журнали API запросов
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionClearLog()
    {
        $api = new \ExternalApi\Model\LogApi();
        $api->clearLog();
        return $this->result->setSuccess(true)->addMessage(t('Лог очищен'));
    }
    
}


