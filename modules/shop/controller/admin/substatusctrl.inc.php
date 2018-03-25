<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Admin;

use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table;

/**
 * Контроллер Управление налогами
 */
class SubStatusCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(new \Shop\Model\SubStatusApi());
    }

    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('В данном разделе можно управлять причинами отмены заказов. Причины отображаются только, если вы переведете заказ в статус Отменен.'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('Добавить причину'))));
        $helper['topToolbar']->addItem(new ToolbarButton\Button($this->router->getAdminUrl('loadDefault'), t('Загрузить стандартные причины'), array(
            'attr' => array(
                'class' => 'crud-get',
                'data-confirm-text' => t('Вы действительно желаете загрузить стандартные причины отмены заказа(будут добавлены к существующим)?')
            )
        )));

        $helper->setTopTitle(t('Причины отмены заказа'));
        $helper->addCsvButton('shop-substatus');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'CurrentSort' => SORTABLE_ASC)),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id') ), 'LinkAttr' => array('class' => 'crud-edit') )),
                new TableType\Text('alias', t('Псевдоним'), array('Sortable' => SORTABLE_BOTH)),

                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        ))),
                ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )
        )));

        return $helper;
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

    function actionLoadDefault()
    {
        $site_id = \RS\Site\Manager::getSiteId();
        $module = new \RS\Module\Item('shop');
        $installer = $module->getInstallInstance();
        $installer->importCsv(new \Shop\Model\CsvSchema\SubStatus(), 'substatus', $site_id);

        return $this->result->addMessage(t('Статусы успешно добавлены'));
    }
}
