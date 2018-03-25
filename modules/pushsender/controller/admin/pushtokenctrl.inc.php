<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace PushSender\Controller\Admin;

use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar\Element as ToolbarElement,
    \RS\Html\Filter,
    \RS\Html\Table;
    
/**
* Контроллер списка правил для 301 редиректов
*/
class PushTokenCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        //Устанавливаем, с каким API будет работать CRUD контроллер
        parent::__construct(new \PushSender\Model\PushTokenApi());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex(); //Получим helper по-умолчанию
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('Добавить токен'))));
        $helper->setTopTitle(t('Список push токенов')); //Установим заголовок раздела
        $helper->setTopHelp(t('Push токен выдается устройству. Далее с помощью данного токена можно отправлять на данное устройство push-уведомления.'));
        
        //Отобразим таблицу со списком объектов
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                    new TableType\Checkbox('id'),
                    new TableType\Userfunc('user_id', t('Пользователь'), function($value) {
                        if ($value){
                            $user = new \Users\Model\Orm\User($value);    
                            $user_name = $user->getFio()."(".$value.")";
                        }else{
                            $user_name = t("Неизвестный пользователь");
                        }
                        return $user_name;
                    }, array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Text('model', t('Модель'), array('Sortable' => SORTABLE_BOTH)),  
                    new TableType\Text('manufacturer', t('Производитель'), array('Sortable' => SORTABLE_BOTH)),  
                    new TableType\Text('platform', t('Платформа'), array('Sortable' => SORTABLE_BOTH)),  
                    new TableType\Text('uuid', t('Уникальный идентификатор'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),  
                    new TableType\Text('version', t('Версия платформы'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),  
                    new TableType\Text('cordova', t('Версия cordova'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),  
                    
                    new TableType\Datetime('dateofcreate', t('Дата создания'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Text('app', t('Приложение'), array('Sortable' => SORTABLE_BOTH)),                                        
                    new TableType\Text('push_token', t('Токен'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Отправить сообщение'),
                                    'attr' => array(
                                        'class' => 'crud-edit',
                                        '@href' => $this->router->getAdminPattern('addsendmessage', array(':chk[]' => '@id')),
                                    )
                                ),  
                            ))
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    )
        ))));
        
        //Добавим фильтр значений в таблице по названию
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                            new Filter\Type\User('user_id', t('Пользователь')),
                                            new Filter\Type\Text('model', t('Модель'), array('SearchType' => '%like%')),
                                            new Filter\Type\Text('platform', t('Платформа'), array('SearchType' => '%like%')),
                                            new Filter\Type\Text('app', t('Приложение'), array('SearchType' => '%like%')),
                                            new Filter\Type\DateRange('dateofcreate', t('Дата создания')),
                                            
                                        )
                                    ))
                                ),
                                'SecContainers' => array(
                                    new Filter\Seccontainer(array(
                                        'Lines' => array(
                                            new Filter\Line( array(
                                                'Items' => array(   
                                                    new Filter\Type\Text('push_token', t('Push токен'), array('SearchType' => '%like%')), 
                                                    new Filter\Type\Text('manufacturer', t('Производитель'), array('SearchType' => '%like%')),      
                                                    new Filter\Type\Text('uuid', t('Уникальный идентификатор'), array('SearchType' => '%like%')),
                                                    new Filter\Type\Text('version', t('Версия платформы'), array('SearchType' => '%like%')),
                                                    new Filter\Type\Text('cordova', t('Версия cordova'), array('SearchType' => '%like%')),
                                                )
                                            )),                                          
                                        )
                                    )
                                ))
        )))));
        
        if (\RS\Module\Manager::staticModuleExists('mobilesiteapp') && \RS\Module\Manager::staticModuleEnabled('mobilesiteapp')){
            $helper->setBottomToolbar(new \RS\Html\Toolbar\Element(array(
                'Items' => array(
                    new ToolbarButton\Button($this->router->getAdminUrl('addsendmessage'), t('Отправить сообщение'), array(
                        'attr' => array(
                            'class' => 'edit crud-multiedit'
                        ),
                        'noajax' => false
                    )),
                    $this->buttons('delete')
                ) 
            )));
        }

        return $helper;
    }
    
    /**
    * Отправляет сообшение для уведомления
    * 
    */
    function actionAddSendMessage()
    {
        $ids  = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array()) );
        $elem = new \PushSender\Model\Orm\PushTokenMessage();
        
        if ($this->url->isPost()){ //Если это POST запрос и сообщение заполнено   
            $elem->getFromArray($this->url->getSource(POST));
            if ($elem['send_type'] == $elem::TYPE_PAGE  && empty($elem['message'])){
                return $this->result->setSuccess(true)
                                    ->addEMessage(t('Сообщение не отправлено. Необходимо заполнить текст сообщения для отправки.'));
            }
            if ($elem['send_type'] == $elem::TYPE_PRODUCT  && empty($elem['product_id'])){
                return $this->result->setSuccess(true)
                                    ->addEMessage(t('Сообщение не отправлено. Необходимо указать товар.'));
            }
            if (($elem['send_type'] == $elem::TYPE_CATEGORY  && empty($elem['category_id'])) || ($elem['send_type'] == $elem::TYPE_CATEGORY && !$elem['category_id'])){
                return $this->result->setSuccess(true)
                                    ->addEMessage(t('Сообщение не отправлено. Необходимо указать категории.'));
            }
            
            
            $push_token_api = new \PushSender\Model\PushTokenApi();
            $push_token_api->sendPushMessageToUsers($elem, $ids);
            return $this->result->setSuccess(true)
                                ->addMessage(t('Сообщение успешно отправлено'));
        }
        
        $helper = parent::helperAdd();
        $helper->setHeaderHtml(t('Выбрано элементов: <b>%0</b>', array(count($ids))));
        $helper->setFormObject($elem);   
        
        return $this->result->setTemplate( $helper['template'] );
    }

}
