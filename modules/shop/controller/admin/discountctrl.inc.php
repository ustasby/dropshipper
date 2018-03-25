<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Admin;

use \RS\Html\Table\Type as TableType,
    \RS\Html\Filter,
    \RS\Html\Table;
    
/**
* Контроллер Управление скидочными купонами
*/
class DiscountCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $api;
    
    function __construct()
    {
        parent::__construct(new \Shop\Model\DiscountApi());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Скидочный купон - это уникальный набор символов, который можно указать в корзине для получения скидки на заданные товары. Скидочный купон может иметь срок действия, что позволяет создавать ограниченные по времени рекламные кампании.'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('Добавить купон'))));
        $helper->setBottomToolbar($this->buttons(array('multiedit','delete')));
        $helper->setTopTitle(t('Купоны на скидку'));
        $helper->addCsvButton('shop-discount');
                 
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Text('code', t('Код'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit') )),
                new TableType\Usertpl('endtime', t('Истекает'), '%shop%/discount/endtime.tpl', array('Sortable' => SORTABLE_BOTH)),
                
                new TableType\Text('descr', t('Описание'), array('hidden' => true)),
                new TableType\Yesno('active', t('Включен'), array('toggleUrl' => $this->router->getAdminPattern('toggleCoupon', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH )),
                new TableType\Usertpl('discount', t('Скидка'), '%shop%/discount/discount.tpl', array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('wasused', t('Использована'), array('Sortable' => SORTABLE_BOTH)),
                
                new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                            'attr' => array(
                                '@data-id' => '@id'
                            ))),
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('Клонировать купон на скидку'),
                                'attr' => array(
                                    'class' => 'crud-add',
                                    '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                )
                            ),                                 
                        )),
                    ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));
        
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('Items' => array(
                                                            new Filter\Type\Text('code', t('Код'), array('attr' => array('class' => 'w100'))),
                                                            new Filter\Type\Text('discount', t('Скидка'), array('showType' => true)),
                                                            new Filter\Type\Date('endtime', t('Истекает'), array('showType' => true))
                                                        )
                                    ))
                                )
                            )),
            'Caption' => t('Поиск по купонам')
        )));
        
        
        return $helper;
    }
    
    /**
    * Добавление купонов
    */
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if ($primaryKey === null) {
            $this->getHelper()->setTopTitle(t('Добавить купон'));        
            $elem = $this->api->getElement();
            $elem->__makecount->setVisible(true);
            $elem['active'] = 1;
            $elem['code'] = $elem->generateCode();
            $elem['uselimit'] = 1;
            $elem['round'] = 1;
        } else {
            $this->getHelper()->setTopTitle(t('Редактировать купон').' {code}');        
        }
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    /**
    * Включить/выключить купон
    */
    function actionToggleCoupon()
    {
        $id = $this->url->request('id', TYPE_INTEGER);
        if ($coupon = $this->api->getOneItem($id)) {   
            $coupon['active'] = !$coupon['active'];
            $update_status = $coupon->update();
        }
        return ($this->result->setSuccess($update_status));
    }
    
    /**
    * Мультиредактирование купонов
    */
    function actionMultiedit()
    {
        $doedit = $this->url->request('doedit', TYPE_ARRAY);
        
        if (in_array('type', $doedit)) $doedit[] = 'products';
        if (in_array('period', $doedit)) $doedit[] = 'endtime';
        $this->url->set('doedit', $doedit, POST);
        
        return parent::actionMultiedit();
    }

}
