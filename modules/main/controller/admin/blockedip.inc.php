<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Filter,
    \RS\Html\Table;

class BlockedIp extends \RS\Controller\Admin\Crud
{
    protected 
        $api;

    function __construct()
    {
        parent::__construct(new \Main\Model\BlockedIpApi());
    }
    
    function helperIndex()
    {  
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Заблокированные IP'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить IP'))));
        $helper->addCsvButton('main-blockedip');
        $helper->setTopHelp(t('В этом разделе вы можете заблокировать IP адреса с которых идет вредоносный трафик. Обработка запросов с данных IP будет прекращена на самом начальном этапе выполнения скрипта, до инициализации основных подсистем.'));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                    new TableType\Checkbox('ip', array('showSelectAll' => true)),
                    new TableType\Text('ip', t('IP-адрес'), array('Sortable' => SORTABLE_BOTH, 
                                                                 'href' => $this->router->getAdminPattern('edit', array(':id' => '@ip')), 
                                                                 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Userfunc('expire', t('Заблокирован до'), 
                        function($value, $type) {
                            return $value ? date('d.m.Y H:i', strtotime($value)) : t('бессрочно');
                        }, 
                        array('Sortable' => SORTABLE_BOTH)),
                    new TableType\Text('comment', t('Комментарий'), array('Sortable' => SORTABLE_BOTH)),
                    new TableType\Actions('ip', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    ), 
        ),
        )));
        
        
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('ip', t('IP-адрес'), array('SearchType' => '%like%')),
                                                            new Filter\Type\DateRange('expire', t('Дата разблокировки')),
                                                            new Filter\Type\Text('comment', t('Комментарий'), array('SearchType' => '%like%')),
                                                        )
                                    ))
                                ),
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));
        
        return $helper;
    }    
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать {ip}') : t('Добавить IP'));
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
}


