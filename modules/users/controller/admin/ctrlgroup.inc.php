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
    \RS\Html\Table;

class Ctrlgroup extends \RS\Controller\Admin\Crud
{
    protected 
        $module_access = array(),
        $menu_access = array(),
        $site_access,
        
        $form_tpl = 'group_form.tpl';
        
    function __construct()
    {        
        parent::__construct(new \Users\Model\GroupApi());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Группы позволяют объединить пользователей по какому-либо признаку. Группе пользователей можно ограничить или делегировать определенный набор прав. Права пользователей суммируюся, если пользователь состоит одновременно в нескольких группах. В ReadyScript существует 2 системные зарезервированные группы: Гости, Клиенты. Гости - это абсолютно все пользователи, в том числе неавторизованные. Группа Клиенты присваивается всем пользователям на время, пока пользователь авторизован на сайте.'));
        $helper->setTopTitle(t('Группы пользователей'));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('alias'),            
                new TableType\Text('name', t('Название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@alias')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('description', t('Описание')),
                new TableType\Text('alias', t('Псевдоним'), array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)),                
                new TableType\StrYesno('is_admin', t('Админ')),
                new TableType\Actions('alias', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array('disableAjax' => true)),
                    ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),                 
        ))));
        
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
                            'title' => t('Экспорт групп пользователей в CSV'),
                            'attr' => array(
                                'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'users-usersgroup', 'referer' => $this->url->selfUri()), 'main-csv'),
                                'class' => 'crud-add'
                            )
                        ),
                        array(
                            'title' => t('Импорт групп пользователей из CSV'),
                            'attr' => array(
                                'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'users-usersgroup', 'referer' => $this->url->selfUri()), 'main-csv'),
                                'class' => 'crud-add'
                            )
                        ),
                )),
            )
        )));
        
        
        return $helper;
    }
    
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, $this->api, $this->url);        
        if ($primaryKeyValue) {
            $helper->setTopTitle(t('Редактирование группы {name}'));
        } else {
            $helper->setTopTitle(t('Добавить группу'));
        }
        
        $table_structure = array(
        'Columns' => array(
            new TableType\Text('class', t('Модуль'), array('TdAttr' => array('align' => 'center'))),
            new TableType\Text('name', t('Название')),
            new TableType\Text('description', t('Описание')),
            new TableType\Usertpl('access', t('Уровень доступа'), '%users%/modright.tpl', array(
                'TdAttr' => array('style' => 'white-space:nowrap'), 'bitcount' => \RS\Orm\ConfigObject::BIT_COUNT))
        ));        
        
        //Если пост идет для текущего модуля
        if ($this->url->isPost()) 
        {            
            $this->user_post_data['menu_access'] = $this->request('menu_access', TYPE_ARRAY);
            $this->user_post_data['menu_admin_access'] = $this->request('menu_admin_access', TYPE_ARRAY);
            $this->user_post_data['module_access'] = $this->api->prepareModAccessBits( $this->request('module_access', TYPE_ARRAY) );
            $this->user_post_data['site_access'] = $this->url->request('site_access', TYPE_INTEGER, 0);
            
            $this->result->setSuccess( $this->api->save($primaryKeyValue, $this->user_post_data) );

            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if (!$this->result->isSuccess()) {
                    $this->result->setErrors($this->api->getElement()->getDisplayErrors());
                } else {
                    $this->result->setSuccessText(t('Изменения успешно сохранены'));
                }
                return $this->result->getOutput();
            }
            
            if ($this->result->isSuccess()) {
                $this->successSave();
            } else {
                $error = $this->api->getElement()->getLastError();
                $this->module_access = $this->user_post_data['module_access'];
                $this->menu_access = array_flip($this->user_post_data['menu_access']);
                $this->site_access = $this->user_post_data['site_access'];
            }
        }             
        
        //Формируем доступное меню
        $user_menu_api = new \Menu\Model\Api();
        $user_menu_api->uniq = 'usermenu';
        $user_menu_api->setCheckAccess(false);
        $user_menu_api->setFilter('menutype', 'user');
        $user_menu_api->setFilter('typelink', 'separator', '!=');        
        
        $admin_menu_api = new \Menu\Model\Api();
        $admin_menu_api->uniq = 'adminmenu';
        $admin_menu_api->setCheckAccess(false);
        //$admin_menu_api->setFilter('menutype', 'admin');
        //$admin_menu_api->setFilter('typelink', 'separator', '!=');
    

        $admin_tree = new \RS\Html\Tree\Element(array(
                'checked' => $this->menu_access,
                'uniq' => $admin_menu_api->uniq,
                'sortIdField' => 'alias',
                'activeField' => 'alias',
                'mainColumn' => new TableType\Text('title', t('Название'), array()),
                'checkboxName' => 'menu_admin_access[]'
            ));
        $user_tree = clone $admin_tree;        
        $user_tree->setCheckboxName('menu_access[]');
        $user_tree->setSortIdField('id');
        $user_tree->setActiveField('id');
        $user_tree->setOption('uniq', $user_menu_api->uniq);
        
        $user_tree->setData( $user_menu_api->getTreeList(0) );
        $admin_tree->setData( $admin_menu_api->getAdminMenu(true, false) );        
        
        //Готовим массив с данными для таблицы
        $list_fot_table = $this->api->prepareModuleAccessData($this->module_access);
        
        $helper->setListFunction('');
        $helper->setTable(new Table\Element($table_structure));
        $helper['table']->getTable()->setData($list_fot_table);
        
        $helper->setBottomToolbar($this->buttons(array('save', 'cancel')));
        
        $this->view->assign(array(
            'admin_tree' => $admin_tree,
            'user_tree' => $user_tree,
            'elem' => $this->api->getElement(),
            'site_access' => $this->site_access,
            'menu_access' => $this->menu_access,
            'module_access' => $this->module_access,
            'elements' => $helper
        ));        
        $helper->viewAsForm();
        
        return $this->result->setHtml($this->view->fetch( $helper['template'] ))->getOutput();
    }
    
    /**
    * Редактирование элемента
    */
    function actionEdit()
    {
        $id = $this->url->get('id', TYPE_STRING, 0);
        if ($id) $this->api->getElement()->load($id);
        
        $obj = $this->api->getElement();
        $obj['__alias']->setReadOnly();
        $obj['__alias']->setHint( t('Данное поле является идентификатором и не доступно для редактирования') );
        
        //Загружаем сведения о правах доступа
        $this->module_access = $obj->getModuleAccess();
        $this->menu_access = array_flip($obj->getMenuAccess());
        $this->site_access = $obj->getSiteAccess(\RS\Site\Manager::getSiteId());
        
        return $this->actionAdd($id);
    }

}


