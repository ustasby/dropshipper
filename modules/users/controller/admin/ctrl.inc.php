<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Users\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar,
    \RS\Html\Filter,
    \RS\Html\Table;

/**
* Контроллр пользователей
* @ingroup Users
*/
class Ctrl extends \RS\Controller\Admin\Crud
{
        
    function __construct()
    {
        parent::__construct(new \Users\Model\Api());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Здесь представлены пользователи вашего интернет-магазина (покупатели и администраторы). Разграничивайте права пользователей, с помощью присвоения им необходимых групп. В данном разделе вы можете редактировать любые сведения пользователей, устанавливать им необходимый тип цен, при необходимости блокировать, менять пароль и т.д.'));
        $helper->setTopTitle(t('Пользователи'));
        $edit_pattern = $this->router->getAdminPattern('edit', array(':id' => '@id'));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),            
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Text('login', t('Логин'), array('href' => $edit_pattern, 'Sortable' => SORTABLE_BOTH, 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('surname', t('Фамилия'), array('href' => $edit_pattern, 'Sortable' => SORTABLE_BOTH, 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('name', t('Имя'), array('href' => $edit_pattern, 'Sortable' => SORTABLE_BOTH, 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('midname', t('Отчество'), array('href' => $edit_pattern, 'Sortable' => SORTABLE_BOTH, 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('company', t('Компания'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Text('company_inn', t('ИНН'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Text('phone', t('Телефон'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),

                new TableType\Datetime('dateofreg', t('Дата регистрации'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Datetime('last_visit', t('Последний визит'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Usertpl('group', t('Группа'), '%users%/form/filter/group.tpl'),
                new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('клонировать пользователя'),
                                'attr' => array(
                                    'class' => 'crud-add',
                                    '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                )
                            ),  
                            array(
                                'title' => t('заказы пользователя'),
                                'attr' => array(
                                    '@href' => $this->router->getAdminPattern(false, array(':f[user_id]' => '~field~'), 'shop-orderctrl'),
                                )
                            ),
                        )) 
                    ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
        ))));
        
        $group_api  = new \Users\Model\GroupApi();
        $group_list = array('' => t('Любой')) + array('NULL' => t('Без группы')) + array_diff_key($group_api->getSelectList(), array('guest', 'clients'));

        $is_catalog_exists = \RS\Module\Manager::staticModuleExists('catalog');
        
        if ($is_catalog_exists) {
            $filter_by_cost = array(new Filter\Type\Select('typecost', t('Тип цен'), array(
                    '' => t('- Любой -'),
                    '0' => t('- По умолчанию -'))
                + \Catalog\Model\CostApi::staticSelectList()));
        } else {
            $filter_by_cost = array();
        }

        
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('id', t('№')),
                                                            new Filter\Type\Text('e_mail',t('E-mail'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('surname',t('Фамилия'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('phone',t('Телефон'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('company',t('Компания'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('company_inn',t('ИНН'), array('SearchType' => '%like%')),
                                                        )
                                    ))
                                ),
                                'SecContainers' => array(
                                    new Filter\Seccontainer( array(
                                        'Lines' => array(
                                            new Filter\Line( array('items' => array(
                                                                    new Filter\Type\Text('login', t('Логин')),
                                                                    new Filter\Type\Text('name', t('Имя'), array('SearchType' => '%like%')),
                                                                    new Filter\Type\Text('midname', t('Отчество'), array('SearchType' => '%like%')),
                                                                    new Filter\Type\Date('dateofreg_from', t('Дата регистрации, от')),
                                                                    new Filter\Type\Date('dateofreg_to', t('Дата регистрации, до')),
                                                           )
                                            )),
                                            new Filter\Line( array('items' => array_merge(array(
                                                                    new Filter\Type\Select('group', t('Группа'), $group_list),

                                                           ), $filter_by_cost)
                                            ))
                                        )))
                                )
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias(), 'TitleAttr' => array('class' => 'standartkey w60')),
            'beforeSqlWhere' => array($this->api, 'beforeSqlWhereCallback')
        )));
        
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Add($this->router->getAdminUrl('add'),t('добавить'),array(
                        array(
                            'attr' => array(
                                'class' => 'btn-success crud-add'
                            )
                        ),
                )),
                new ToolbarButton\Dropdown(array(
                        array(
                            'title' => t('Импорт/Экспорт'),
                            'attr' => array(
                                'class' => 'button',
                                'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                            )
                        ),
                        array(
                            'title' => t('Экспорт пользователей в CSV'),
                            'attr' => array(
                                'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'users-users', 'referer' => $this->url->selfUri()), 'main-csv'),
                                'class' => 'crud-add'
                            )
                        ),
                        array(
                            'title' => t('Импорт пользователей из CSV'),
                            'attr' => array(
                                'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'users-users', 'referer' => $this->url->selfUri()), 'main-csv'),
                                'class' => 'crud-add'
                            )
                        ),
                )),
            )
        )));
        
        $helper->setBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                $this->buttons('multiedit'),
                new Toolbar\Button\Button(null, t('Сбросить пароли'), array(
                    'attr' => array(
                        'class' => 'crud-post',
                        'data-url' => $this->router->getAdminUrl('generatePassword'),
                        'data-confirm-text' => t('Вы действительно хотите сгенерировать новые пароли для выбранных пользователей и отправить их пользователям на почту?')
                    )
                )),
                $this->buttons('delete'),
            )
        )));
        
        return $helper;
    }
    
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null) 
    {
        $conf_userfields = \RS\Config\Loader::byModule($this)->getUserFieldsManager();
        $conf_userfields->setErrorPrefix('userfield_');
        $conf_userfields->setArrayWrapper('data');
        
        $elem = $this->api->getElement();
                
        $groups = new \Users\Model\Groupapi();
        $glist = $groups->getList(0,0,'name');
        
        $conf_userfields->setValues($elem['data']);
        $elem['conf_userfields'] = $conf_userfields;
        $elem['groups'] = $glist;
        if (isset($primaryKeyValue) && $primaryKeyValue>0){
            $elem['usergroup'] = $this->api->getElement()->getUserGroups();
        }elseif (!isset($primaryKeyValue)){
            $elem['usergroup'] = array();
        }

        //Отключаем автозаполнение полей в Chrome
        $autocomplete_off = array(
            'autocomplete' => 'new-password'
        );
        $elem['__e_mail']->setAttr($autocomplete_off);
        $elem['__login']->setAttr($autocomplete_off);
        $elem['__openpass']->setAttr($autocomplete_off);

        if ($primaryKeyValue) {
            $elem['full_fio'] = $elem->getFio();
            $this->getHelper()->setTopTitle(t('Редактировать профиль пользователя {full_fio}'));
        } else {
            $this->getHelper()->setTopTitle(t('Добавить пользователя'));
        }

        $return = parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);

        $conf_userfields->setValues($elem['data']);
        
        return $return;
    }
    
    
    /**
    * Метод для клонирования
    * 
    */ 
    function actionClone()
    {
        $this->setHelper( $this->helperAdd() );
        $id = $this->url->get('id', TYPE_INTEGER);
        
        $elem = $this->api->getElement();
        
        if ($elem->load($id)) {
            $clone_id = null;
            if (!$this->url->isPost()) {
                $clone = $elem->cloneSelf();
                $this->api->setElement($clone);
                $clone_id = $clone['id']; 
            }
            unset($elem['id']);
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
    
    function actionGeneratePassword()
    {
        $ids = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array(), false) );        
        $this->result->setSuccess($this->api->generatePasswords($ids));
        
        if ($this->result->isSuccess()) {
            $this->result->addMessage(t('Пароли успешно сброшены'));
        } else {
            $this->result->addEMessage($this->api->getErrorsStr());
        }
        
        return $this->result;
    }
    
    /**
    * Вызывает окно мультиредактирования
    */
    function actionMultiedit()
    {
        $elem = $this->api->getElement();
        $group_api = new \Users\Model\GroupApi();
        $group_list = $group_api->getList(0, 0, 'name');
        $elem['groups'] = $group_list;
        
        return parent::actionMultiedit();
    } 
}

