<?php
namespace SeoControl\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Filter,
    \RS\Html\Table;

class Ctrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(new \SeoControl\Model\Api());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Управление SEO'));
        $helper->setTopHelp(t('Этот модуль может переназначить заголовки, ключевые слова и описания для определенной маски страниц.'));
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC,'CurrentSort' => SORTABLE_ASC)),
                new TableType\Text('id', t('Идентификатор'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id') ), 'LinkAttr' => array('class' => 'crud-edit') )),
                new TableType\Text('url_pattern', t('Маска URL'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('domain_list', t('Список доменов'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('meta_title', t('Заголовок'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'linkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('meta_keywords', t('Ключевые слова'), array('hidden' => true)),
                new TableType\Text('meta_description', t('Описание'), array('hidden' => true)),
                new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                                'attr' => array(
                                    '@data-id' => '@id'
                                )
                            )),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Показать на сайте'),
                                    'attr' => array(
                                        'class' => ' ',
                                        'target' => '_blank',
                                        '@href' => $this->router->getAdminPattern('show', array(':id' => '~field~')),
                                    )
                                ),
                                array(
                                    'title' => t('Клонировать правило'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                                ),
                            )),
                        ),

                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )                
        )));
        
        //Опишем фильтр, который следует добавить
        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container( array( //Контейнер визуального фильтра
                'Lines' =>  array(
                    new Filter\Line( array('Items' => array( //Одна линия фильтров
                            new Filter\Type\Text('id','№', array('attr' => array('class' => 'w50'))), //Фильтр по ID
                            new Filter\Type\Text('url_pattern',t('Маска URL'), array('searchType' => '%like%')), //Фильтр по названию производителя
                            new Filter\Type\Text('meta_title',t('Заголовок'), array('searchType' => '%like%')), //Фильтр по названию производителя
                        )
                    )),
                )
            )),
            'Caption' => t('Поиск по правилам')
        )));
        
        return $helper;
    }

    /**
     * Метод для показа на сайте отфильтрованого
     *
     */
    function actionShow()
    {
        $id = $this->url->get('id', TYPE_INTEGER);

        $elem = $this->api->getElement();

        if ($elem->load($id)) {
            $this->redirect(stripslashes($elem['url_pattern']));
        } else {
            $this->e404();
        }
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
                unset($elem['url_pattern']);
                $clone = $elem->cloneSelf();
                $this->api->setElement($clone);
                $clone_id = $clone['id'];
            }

            unset($elem['id']);
            return $this->actionAdd($clone_id);
        } else {
            $this->e404();
        }
    }
    
    /**
    * AJAX
    */
    function actionMove()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $direction = $this->url->request('flag', TYPE_STRING);
        return $this->result->setSuccess( $this->api->moveElement($from, $to, $direction) )->getOutput();
    }
    
    /**
    * Форма добавления элемента
    * 
    * @param mixed $primaryKeyValue - id редактируемой записи
    * @param mixed $returnOnSuccess - Если true, то будет возвращать === true при успешном сохранении, 
    *                                   иначе будет вызов стандартного _successSave метода
    * @return string|bool
    */
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $seoGen = new \SeoControl\Model\SeoReplace\SeoRule();
        $seoGen->replaceORMHint($this->api->getElement());
        
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }

    /**
     * Вызывает окно мультиредактирования
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionMultiedit()
    {
        $seoGen = new \SeoControl\Model\SeoReplace\SeoRule();
        $seoGen->replaceORMHint($this->api->getElement());
        
        return parent::actionMultiedit();
    }

    /**
     * Вызывает окно с редактирование мета тегов
     *
     * @return mixed
     */
    function actionEditPublicRule()
    {
        $uri  = $this->url->get('uri', TYPE_STRING);
        $rule = $this->api->getRuleForUri($uri);
        $orm  = $this->api->getElement();
        $this->setHelper($this->helperAdd());
        if ($rule){
            $orm->load($rule['id']);
            return $this->actionAdd($rule['id']);
        }
        $orm['is_source_regex'] = 0;
        $orm['url_pattern']     = htmlspecialchars_decode($uri);
        return parent::actionAdd();
    }
}
