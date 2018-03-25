<?php
namespace Evasmart\Controller\Admin;

use RS\Controller\Admin\Front;
use RS\Controller\Admin\Helper\HeaderPanel;
use \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar;
    
/**
* Контроллер Управление списком магазинов сети
*/
class Control extends Front
{
    /**
     * @var HeaderPanel
     */
    public $helper;

    function helperIndex()
    {

    }
    function init()
    {
        $this->helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $this->helper
            ->setTopTitle(t('Импорт товаров из YML'))
            ->viewAsForm();
    }

    function actionIndex()
    {
        //Передаем переменные в шаблон Smarty
        $this->view->assign(array(
            'key'   => 'value',
            'key2'  => 'value2'
        ));



        //Возвращаем HTML
        return $this->result->setTemplate('%evasmart%/control.tpl');
    }

    function actionajaxImportCsv()
    {
        $config = $this->getModuleConfig();



    }
}
