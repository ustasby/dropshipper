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

class AuthTokenCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $api;
    
    function __construct()
    {
        $this->setCrudActions(array(
            'index',
            'edit',
            'del'
        ));
        parent::__construct(new \ExternalApi\Model\TokenApi());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Журнал авторизационных токенов'));
        $helper->setTopToolbar($this->buttons(array()));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('token', array('showSelectAll' => true)),                
                new TableType\Datetime('dateofcreate', t('Дата'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC, 'LinkAttr' => array(
                    'class' => 'crud-edit'
                ),
                'href' => $this->router->getAdminPattern('edit', array(':id' => '@token')),)),
                new TableType\Text('token', t('Авторизационный токен')),                
                new TableType\Text('ip', t('IP'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Userfunc('user_id', t('Пользователь'), function($value, $cell) {
                    if ($value) {
                        $user = new \Users\Model\Orm\User($value);
                        return $user->getFio()."($value)";
                    }
                }),                
                new TableType\Text('app_type', t('Тип приложения'), array('Sortable' => SORTABLE_BOTH)),                
                new TableType\Datetime('expire', t('Дата истечения'), array('Sortable' => SORTABLE_BOTH)),                
                new TableType\Actions('token', array(
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
                                                            new Filter\Type\Text('token', t('Токен'), array('searchType' => '%like%')),
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
    
}