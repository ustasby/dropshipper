<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Controller\Admin;

class Front extends \RS\Controller\AbstractAdmin
{
    protected
        /**
        * @var \RS\Controller\Result\Standard
        */
        $result,    
        $action_var = 'do',
        $wrap_output = true,
        $wrap_template = '%SYSTEM%/admin/body.tpl',
        $block_tpl = '%SYSTEM%/admin/block.tpl', //стандартный шаблон для оборачивания вывода в админке
        $block_form_tpl = '%SYSTEM%/admin/crud_form.tpl'; //стандартный шаблон для оборачивания форм в админке
    
    private
        $caption,
        $help;
    
    function __construct()
    {
        parent::__construct();
        $this->result = new \RS\Controller\Result\Standard($this); //Helper, который помогает возвращать стандартизированый вывод
    }
    
    /**
    * Устанавливает, оборачивать ли вывод шаблоном текущей страницы.
    * 
    * @param mixed $bool
    */
    function wrapOutput($bool)
    {
        $this->wrap_output = $bool;
        return $this;
    }

    /**
    * Возвращает false, если нет ограничений на запуск контроллера, иначе вызывает исключение 404
    * @return bool(false)
    */    
    function checkAccessRight()
    {
        //Для доступа к контроллеру пользователь должен быть администратором
        if (!\RS\Application\Auth::getCurrentUser()->isAdmin() || \RS\Application\Auth::getCurrentUser()->getRight($this->mod_name) < $this->access_right) {
            return $this->e404(t('Недостаточно прав к запрашиваемому модулю "%0"', array($this->mod_name)));
        }
        return false;
    }    
    
    /**
    * Выполняет action(действие) текущего контроллера, возвращает результат действия
    * 
    * @param boolean $returnAsIs - возвращать как есть. Если true, то метод будет возвращать точно то, 
    * что вернет действие, иначе результат будет обработан методом processResult
    * 
    * @return mixed
    */
    function exec($returnAsIs = false)
    {
        $result_html = parent::exec($returnAsIs);

        //Если имя метода начинается со слова "ajax", то отменяем оборачивание
        //Если в REQUEST есть ajax=1, то отменяем оборачивание
        if ($this->wrap_output && substr($this->act, 0, 4) != 'ajax' && !$this->url->isAjax()) {
            
            if (\RS\Module\Manager::staticModuleEnabled('partnership')){
                $partner = \Partnership\Model\Api::getCurrentPartner();
            }
            if (isset($partner)) {
                $root_url = $partner->getRootUrl(true);
            } else {
                $site = \RS\Site\Manager::getSite();
                $root_url = $site->getRootUrl(true);
            }
            $this->view->assign(array('site_root_url' => $root_url));
            
            $this->app->blocks
                ->setRouteId($this->router->getCurrentRoute()->getId())
                ->setMainContent($result_html)
                ->setView($this->view);
            return $this->wrapHtml($this->view->fetch($this->wrap_template));
        } else {
            return $result_html;
        }
    }
}


