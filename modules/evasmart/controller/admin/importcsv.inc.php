<?php
namespace Evasmart\Controller\Admin;

use EvaSmart\Model\ImportCsvApi;
use RS\Controller\Admin\Front;
use RS\Controller\Admin\Helper\CrudCollection;
use \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar;

class ImportCsv extends Front
{
    public
        $api,
        $post_data,
        $helper;


    function init()
    {
        $this->api = new ImportCsvApi();
        $this->helper = new CrudCollection($this);
        $this->helper
            ->setTopTitle(t('Импорт товаров из EvaSmart каталога'))
            ->viewAsForm();

    }

    function actionImport()
    {
        if ($this->url->isPost()) {
            if (!$this->api->hasError() && ($this->api->uploadFile($this->url->files('csvfile') )=== true)) {
                return $this->result
                    ->addSection('callCrudAdd', $this->router->getAdminUrl('ajaxProcess', array(
                        'step_data' => array(
                            'step'          => 0,
                            'offset'        => 0
                        ))))
                    ->setSuccess(true);
            } else {
                return $this->result
                    ->setSuccess(false)
                    ->setErrors($this->api->getDisplayErrors());
            }
        }

        $this->helper
            ->setBottomToolbar(new Toolbar\Element(array(
                'Items' => array(
                    'save' => new ToolbarButton\SaveForm(null, t('Начать импорт')),
                    'cancel' => new ToolbarButton\Cancel($this->url->getSavedUrl($this->controller_name.'index')),
                )
            )));

        $this->helper['form'] = $this->view->fetch('%evasmart%/import/import_form.tpl');

        return $this->result->setTemplate($this->helper['template']);
    }

    function actionAjaxProcess()
    {
        $step_data = $this->url->request('step_data', TYPE_ARRAY);
        if (isset($step_data['step']) && $step_data['step'] == 0) {
            $next_step['step'] = 1;
        }elseif (isset($step_data['step']) && $step_data['step'] == 1) {
            $next_step = $this->api->stepImport();
        } elseif (isset($step_data['step']) && $step_data['step'] == 2) {
            $next_step = true;
        } else {
            $next_step = false;
        }

        $this->view->assign(array(
            'next_step' => $next_step,
            'error' => $this->api->getErrorsStr()
        ));

        $this->helper
            ->setBottomToolbar(new Toolbar\Element(array(
                'Items' => array(
                    'cancel' => new ToolbarButton\Cancel($this->url->getSavedUrl($this->controller_name.'index'), t('Закрыть')),
                )
            )));

        $this->helper['form'] = $this->view->fetch('%evasmart%/import/import_form_result.tpl');


        if (isset($step_data['step']) && $step_data['step'] > 0) {
            return $this->result->setHtml($this->helper['form']);
        }

        return $this->result
            ->setSuccess(true)
            ->setTemplate($this->helper['template']);

    }

}