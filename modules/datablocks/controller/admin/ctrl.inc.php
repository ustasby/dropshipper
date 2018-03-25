<?php
namespace DataBlocks\Controller\Admin;

use DataBlocks\Model\DataNodeApi;
use DataBlocks\Model\Exception;
use DataBlocks\Model\Orm\DataNode;
use DataBlocks\Model\Orm\DataNodeField;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Filter,
    \RS\Html\Table;
    
/**
* Контроллер Управление списком узлов информационных блоков
*/
class Ctrl extends \RS\Controller\Admin\Crud
{
    protected $parent_id;
    protected $path;

    function __construct()
    {
        //Устанавливаем, с каким API будет работать CRUD контроллер
        parent::__construct(new \DataBlocks\Model\DataNodeApi());
    }
    
    function helperIndex()
    {
        $this->parent_id = $this->url->get('pid', TYPE_INTEGER, 0);
        $this->path = $this->api->getPathToFirst($this->parent_id);
        $this->api->setFilter('parent_id', $this->parent_id);

        if ($this->parent_id>0) {
            $parent_item = $this->api->getOneItem($this->parent_id);
        } else {
            $parent_item = false;
        }

        $helper = parent::helperIndex(); //Получим helper по-умолчанию
        $helper->setTopTitle(t('Структуры данных')); //Установим заголовок раздела
        $helper->setTopHelp(t('С помощью данного раздела можно создавать произвольные наборы данных с произвольными полями. Каждый родительский элемент описывает набор полей всех дочерних элементов.'));

        if ($parent_item && $parent_item['child_is_leaf']) {
            $title_href = $this->router->getAdminPattern('edit', array(':id' => '@id'));
            $link_attr = array(
                'class' => 'crud-edit'
            );
        } else {
            $title_href = $this->router->getAdminPattern(false, array(':pid' => '@id'));
            $link_attr = array();
        }

        //Отобразим таблицу со списком объектов
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                    new TableType\Checkbox('id'),
                    new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                    )),
                    new TableType\Text('title', 'Название', array('Sortable' => SORTABLE_BOTH, 'href' => $title_href, 'linkAttr' => $link_attr)),
                    new TableType\Actions('id', array(
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('Открыть на сайте'),
                                'attr' => array(
                                    '@href' => $this->router->getAdminPattern('viewOnSite', array(':id' => '@id')),
                                    'target' => '_blank'
                                )
                            )
                        ))
                    )),

        ))));
        
        //Добавим фильтр значений в таблице по названию
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                            new Filter\Type\Text('title', 'Название', array('SearchType' => '%like%')),
                                        )
                                    ))
                                ),
                            ))
        )));

        if ($parent_item) {
            $this->view->assign('current_path', $this->path);
            $helper->setBeforeTableContent($this->view->fetch('admin/breadcrumbs.tpl'));
            $helper->getTopToolbar()->addItem(new ToolbarButton\Button(
                $this->router->getAdminUrl('edit', array('id' => $this->parent_id)),
                t('Настройка родительского элемена'),
                array(
                    'attr' => array('class' => 'crud-edit')
                )));

            $helper['table']->getTable()->insertAnyRow(array(
                new TableType\Text(null, null, array('href' => $this->router->getAdminUrl(false, array('pid' => $parent_item['parent_id'])), 'Value' => t('.. (на уровень выше)'), 'LinkAttr' => array('class' => 'call-update'), 'TdAttr' => array('colspan' => 4)))
            ), 0);
        }

        return $helper;
    }

    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $element = $this->api->getElement();
        if (!$primaryKeyValue) {
            $parent_id = $this->url->get('pid', TYPE_INTEGER, 0);
            $element->setTemporaryId();

            if ($parent_id) {
                $parent_element = new DataNode($parent_id);
                $element->parent_id = $parent_id;

                if ($parent_element['child_inherit_structure'] != -1) {
                    if ($parent_element['child_inherit_structure'] > 0) {
                        //Наследование от другого элемента
                        $elaton = new DataNode($parent_element['child_inherit_structure']);
                    } else {
                        //Наследование от родительского элемента
                        $elaton = $parent_element;
                    }

                    if (!$elaton['id']) {
                        $this->e404(t('Элемент, от которого наследовать структуру параметров не найден'));
                    }

                    $elaton->loadChildStructure();
                    $element['child_structure'] = $elaton['child_structure'];
                }
            }
        } else {
            $element->loadChildStructure();
            $this->getHelper()->setTopTitle(t('Редактировать узел {title}'));
        }

        $this->api->getElement()->appendParentFields();

        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }

    /**
     * Возвращает шаблон строки настроек параметра
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAddField()
    {
        $type = $this->url->get('type', TYPE_STRING);

        $node_field_item = new DataNodeField();
        $node_field_item['type'] = $type;

        return $this->result->setHtml($node_field_item->getFieldView());
    }

    /**
     * Возвращает редирект на страницу просмотра узла
     */
    function actionviewOnSite()
    {
        $id = $this->url->get('id', TYPE_INTEGER);
        $node = $this->api->getElement();
        if ($node->load($id)) {
            $this->redirect($node->getUrl());
        }

        $this->e404();
    }
}
