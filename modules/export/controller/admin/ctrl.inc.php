<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Controller\Admin;

use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Filter,
    \RS\Html\Table;
    
/**
* Контроллер Управление профилями экспорта данных
*/
class Ctrl extends \RS\Controller\Admin\Crud
{
    protected
        $api;
    
    function __construct()
    {
        parent::__construct(new \Export\Model\Api());
    }
    
    function helperIndex()
    {
        $menu_items = array();
        $menu_items[] = array(
            'title' => t('Создать профиль'),
            'attr' => array(
                'class' => 'btn-success',
                'onclick' => "$(this).parent().rsDropdownButton('toggle');"
            )
        );

        foreach($this->api->getTypes() as $one){
            $menu_items[] = array(
                'title' => $one->getTitle(),
                'attr' => array(
                    'class' => 'crud-add',
                    'href' => $this->router->getAdminUrl('add', array('class' => $one->getShortName()))
                )
            );
        }
        
        
        $helper = parent::helperIndex();
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Dropdown($menu_items),
            )
        )));

        $helper->setTopHelp(t('Профиль экспорта обеспечивает экспорт ваших товаров на различные торговые площадки, такие как Яндекс.Маркет, Товары@Mail.ru, Google Merchants, и др. Каждый профиль создаст уникальную ссылку на специальный фид, который потребуется вам для настройки автоматического обмена данными со сторонними площадками.'));
        $helper->setTopTitle(t('Профили экспорта данных'));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Text(
                    'title', 
                    t('Название'), 
                    array(
                        'Sortable' => SORTABLE_BOTH, 
                        'href' => $this->router->getAdminPattern('edit', array(':id' => '@id') ), 
                        'LinkAttr' => array('class' => 'crud-edit') 
                    )
                ),
                new TableType\Text('class', t('Тип экспорта')),
                new TableType\Usertpl('id', t('URL для экспорта'), '%export%/url_cell.tpl'),
                new TableType\Text('description', t('Описание'), array('Hidden' => true)),
                new TableType\Actions('id', 
                    array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, 
                            array(
                                'attr' => array('@data-id' => '@id')
                            )
                        ),
                    ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));
        
        return $helper;
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if (!$primaryKey) {
            $this->api->getElement()->class = $this->url->get('class', TYPE_STRING);
            $this->api->getElement()->initClass();
        }
        
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать профиль {title}') : t('Добавить профиль экспорта данных'));
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    function actionGetTypeForm()
    {
        $type = $this->url->request('type', TYPE_STRING);
        return $this->result;
    }
}
