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
    \RS\Html\Filter,
    \RS\Html\Table;

class TopicsCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $api;
        
    function __construct()
    {
        parent::__construct(new \Support\Model\TopicApi);
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Это внутренняя поддержка для пользователей вашего сайта. Авторизованные пользователи могут создавать тему обращения и вести в ней переписку с администраций сайта (с Вами). В ReadyScript предусмотрены уведомления о поступлении новых сообщений для администратора и клиентов. Воспользуйтесь <a href="//readyscript.ru/downloads-desktop/" class="u-link">Desktop приложением ReadyScript</a>, чтобы не упустить ни одного обращения клиента.'));
        $helper->setTopTitle(t('Поддержка'));
        
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Viewed('newadmcount', $this->api->getMeterApi(), null, array('viewedValue' => 0)),
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern(null, array(':id' => '@id'), 'support-supportctrl'))),
                new TableType\Userfunc('title', t('Тема'), function ($value,$_this){
                   //Если есть не прочтённые сообщения
                   if ( $_this->getRow()->newadmcount > 0 ) { 
                       return "<b>".$value."</b>";
                   }
                   return $value;
                }, 
                    array(
                         'Sortable' => SORTABLE_BOTH, 
                         'href' => $this->router->getAdminPattern(null, array(':id' => '@id'), 'support-supportctrl')
                    )
                ),
                new TableType\Usertpl('user_id', t('Пользователь'), '%support%/table_user_cell.tpl',array(
                         'Sortable' => SORTABLE_BOTH, 
                         'href' => $this->router->getAdminPattern(null, array(':id' => '@id'), 'support-supportctrl')
                    )),
                new TableType\Text('updated', t('Дата'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)),
                new TableType\Text('newadmcount', t('Новых сообщений'), array('TdAttr' => array('align' => 'center'))),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        ))),
                        
                    ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                )
            ))
        ));    

        //Инициализируем фильтр
        $filter_control = new \RS\Html\Filter\Control(array(
            'Container' => new \RS\Html\Filter\Container( array( 
                                'Lines' =>  array(
                                    new \RS\Html\Filter\Line( array('items' => array(
                                                            new \RS\Html\Filter\Type\Select('newadmcount', t('Категория'), array('' => t('Все сообщения'), '0' => t('Только новые')), array('SearchType' => 'noteq')),
                                                            new \RS\Html\Filter\Type\Text('title', t('Тема'), array('SearchType' => '%like%')),
                                                            new \RS\Html\Filter\Type\User('user_id', t('Пользователь')),
                                                        )
                                    ))
                                ),
                                'SecContainers' => array(
                                    new Filter\Seccontainer(array(
                                        'Lines' => array(
                                            new Filter\Line( array(
                                                'Items' => array(
                                                    new Filter\Type\Date('updated', t('Дата'), array('showtype' => true))
                                                )
                                            ))
                                        )
                                ))
                                )
                            ))
        ));

        $helper->setFilter($filter_control);
        $helper->setBottomToolbar(new \RS\Html\Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Button(null, t('Закрыть вопросы'), array(
                    'attr' => array(
                        'class' => 'btn-warning crud-post-selected',
                        'data-url' => $this->router->getAdminUrl('closeTopic')
                    )
                )),
                $this->buttons('delete'),
            )
        )));
        
        return $helper;
    }

    function actionIndex()
    {
        return parent::actionIndex();
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $this->api->getElement()->_admin_creation_ = true;
        $result = parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
        if ($result->isSuccess()) {
            $result->setAjaxWindowRedirect( $this->router->getAdminUrl(false, array('id' => $this->api->getElement()->id), 'support-supportctrl') );
        }
        return $result;
    }
    
    function actionCloseTopic()
    {
        $topic_ids = $this->url->request('chk', TYPE_ARRAY);
        if ($topic_ids) {
            $this->api->getMeterApi()->markAsViewed($topic_ids);

            return $this->result->setSuccess(true)->addEMessage(count($topic_ids)>1 ? t('Заявки успешно закрыты') : t('Заявка успешно закрыта') );
        }

        return $this->result->setSuccess(true);
    }
}

