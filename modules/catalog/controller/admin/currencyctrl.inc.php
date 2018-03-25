<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Filter,
    \RS\Html\Table;

class CurrencyCtrl extends \RS\Controller\Admin\Crud
{
    protected 
        /**
        * @var \Catalog\Model\CurrencyApi
        */
        $api,
        $form_tpl = 'form_unit.tpl';

    function __construct()
    {
        parent::__construct(new \Catalog\Model\CurrencyApi());
    }
    
    function helperIndex()
    {  
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Валюты, указанные в данном разделе могут использоваться при пересчете цен на товары. Базовая валюта - это валюта, в которой хранятся все цены в базе данных. Именно к данной валюте приводятся все цены в системе. Курс базовой валюты должен быть равен еденице. Используйте валюты, если вы закупаете товары в одной валюте, а продаете в другой. Так вы можете обеспечить быстрый пересчет цен с учетом меняющегося курса.'));
        $helper->setTopTitle(t('Валюты'));
        $helper->setTopToolbar(
              $this->buttons(array('add'), array(
                            'add' => t('добавить валюту'))
              )
        );
        $helper['topToolbar']->addItem(new ToolbarButton\Button(
            $this->router->getAdminUrl('getCBRFCourse'),
            t('Получить курс ЦБ РФ'),
            array(
                'attr' => array(
                    'class' => 'crud-get'
                )
            )
        )); 
        $helper->addCsvButton('catalog-currency');       
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                    new TableType\Checkbox('id'),
                    new TableType\Text('title', t('Полное название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Text('stitle', t('Символ')),
                    new TableType\Text('ratio', t('Курс')),
                    new TableType\StrYesno('is_base', t('Базовая')),
                    new TableType\StrYesno('public', t('Видимость')),
                    new TableType\StrYesno('default', t('По-умолчанию')),
                    new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Клонировать валюту'),
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
        )));
                
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('title',t('Полное наименование'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('stitle',t('Короткое обозначение'), array('SearchType' => '%like%')),
                                                        )
                                    ))
                                ),
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));
        
        return $helper;
    }    
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $this->api->getElement()->reconvert = 1;
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать валюту {title}') : t('Добавить валюту'));
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    /**
    * Получает курс ЦБ РФ и обновляет состояние курсов валют
    * Расчёт ведётся от базовой валюты. Базовой валютой по умолчанию является 
    * Российский рубль
    * 
    */
    function actionGetCBRFCourse(){
        if ($this->api->getCBRFCourseWithUpdate()){
            return $this->result->addMessage(t('Валюты успешно обновлены'));
        }
        return $this->result->addEMessage($this->api->getErrorsStr());
    }
    
}


