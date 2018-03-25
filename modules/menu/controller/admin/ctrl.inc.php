<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Menu\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar,
    \RS\Html\Tree;
        
/**
* Контроллер меню в админки
* @ingroup Menu
*/
class Ctrl extends \RS\Controller\Admin\Crud
{
    protected
        $user_menu_type = 'user';
        
    function __construct($param = array())
    {
        parent::__construct(new \Menu\Model\Api());
        $this->api->setFilter('menutype', $this->user_menu_type);
        
        $this->app
            ->addCss($this->mod_css.'menucontrol.css', null, BP_ROOT);
            
        $this->multiedit_check_func = array($this->api, 'checkParent');
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('В этом разделе можно создавать различные текстовые и информационные страницы, которые, при необходимости, могут отображаться в меню на сайте. Каждая страница может иметь необходимый URL-адрес. Данным разделом следует также воспользоваться, если вы желаете сконструировать на вашем сайте отдельную страницу с собственным набором модулей. Для этого, создайте здесь страницу, укажите у неё тип "Страница" и настройте затем её в разделе <i>Веб-сайт &rarr; Конструктор сайта</i>.'));
        $helper->setTopTitle(t('Меню'));
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Dropdown(array(
                    array(
                        'title' => t('добавить пункт меню'),
                        'attr' => array(
                            'class' => 'btn-success crud-add',
                            'href' => $this->router->getAdminUrl('add')
                        )
                    ),
                    array(
                        'title' => t('добавить разделитель'),
                        'attr' => array(
                            'class' => 'crud-add',
                            'href' => $this->router->getAdminUrl('add', array('sep' => 1))
                        )
                    )
                )),
            )
        )));        
        $helper->addCsvButton('menu-menu');
        
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->viewAsTree();
        $helper->setTreeListFunction('getTreeData');
        $helper->setTree(new Tree\Element( array(
            'disabledField' => 'public',
            'disabledValue' => '0',        
            'activeField'   => 'id',
            'sortIdField'   => 'id',
            'hideFullValue' => true,
            'sortable'      => true,
            'sortUrl'       => $this->router->getAdminUrl('move_dir'),
            'mainColumn'    => new TableType\Usertpl('title', t('Название'), '%menu%/tree_column.tpl'),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                new TableType\Action\DropDown(array(
                    array(
                        'title' => t('Редактировать мета-теги'),
                        'attr' => array(
                            '@href' => $this->router->getAdminPattern('edit', array(':id' => 'menu.item_{id}', 'create' => 1), 'pageseo-ctrl'),
                            'class' => 'crud-add'
                        )
                    ),
                    array(
                        'title' => t('Клонировать'),
                        'attr' => array(
                            'class' => 'crud-add',
                            '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                        )
                    ),
                    array(
                        'title' => t('Добавить дочерний элемент'),
                        'attr' => array(
                            '@href' => $this->router->getAdminPattern('add', array(':pid' => '@id')),
                            'class' => 'crud-add'
                        )
                    ),
                    array(
                        'title' => t('Добавить разделитель'),
                        'attr' => array(
                            '@href' => $this->router->getAdminPattern('add', array(':pid' => '@id', 'sep' => 1)),
                            'class' => 'crud-add'
                        )
                    ),    
                    array(
                        'title' => t('Показать на сайте'),
                        'attr' => array(
                            '@href' => $this->router->getAdminPattern('itemRedirect', array(':id' => '@id')),
                            'target' => '_blank'
                        )
                    )                                     
                )),
            )),            
        )));
        
        return $helper;
    }
    
    /**
    * Делает редирект на соотвествующий url на сайте
    * Через Request передаётся id пункта меню
    * 
    */
    function actionItemRedirect()
    {
        $id = $this->url->request('id',TYPE_INTEGER);
        $menu = $this->api->getOneItem($id);
        
        if ($menu){
            $this->redirect($menu->getHref());
        }
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if ($this->url->isPost()) {
            $this->user_post_data = array('menutype' => $this->user_menu_type);
        }

        $parent = $this->url->get('pid', TYPE_INTEGER, null);        
        $obj = $this->api->getElement();
        
        if ($parent) {
            $obj['parent'] = $parent;
        }
        
        if (($obj['id'] && $obj['typelink'] == 'separator') || $this->request('sep', TYPE_INTEGER)==1) {
            $obj['__title']->removeAllCheckers();
            $obj['__alias']->removeAllCheckers();
            $obj['typelink'] = 'separator';
            $this->getHelper()->setFormSwitch('spec');
            $title = $obj['id'] ? t('Редактировать разделитель ') : t('Добавить разделитель');
        } else {
            $title = $obj['id'] ? t('Редактировать меню ').'{title}' : t('Добавить меню');
        }

        $obj['tpl_module_folders'] = \RS\Module\Item::getResourceFolders('templates');
        $this->getHelper()->setTopTitle($title);        
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    function successSave()
    {
        $obj = $this->api->getElement();
        $this->redirect($this->url->replaceKey(array($this->action_var => '', 'pid' => $obj['parent'])));
    }    
    
    
    function actionMove_dir()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $flag = $this->url->request('flag', TYPE_STRING); //Указывает выше или ниже элемента to находится элемент from
        $parent = $this->url->request('parent', TYPE_INTEGER);
        
        if ($this->api->moveElement($from, $to, $flag, null, $parent)) {
            $this->result->setSuccess(true);
        } else {
            $this->result->setSuccess(false)->setErrors($this->api->getErrorsStr());
        }
        
        return $this->result->getOutput();
    }    
    
    function actionGetMenuTypeForm()
    {
        $type = $this->url->request('type', TYPE_STRING);
        $types = $this->api->getMenuTypes();
        
        if (isset($types[$type])) {
            $this->view->assign(array(
                'changeType' => true,
                'type_object' => $types[$type]
            ));
            $this->result->setTemplate( 'form/menu/type_form.tpl' );
        }
        return $this->result;
    }
}
