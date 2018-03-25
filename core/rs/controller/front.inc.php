<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;

/**
* Базовый класс всех фронтальных контроллеров
*/
abstract class Front extends AbstractClient
{
    const
        CONTROLLER_ID_PARAM = '_controller_id';
        
    protected
        $wrap_template = DEFAULT_LAYOUT,
        $wrap_output = true;
    
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
        if (\RS\Application\Auth::getCurrentUser()->getRight($this->mod_name) < $this->access_right) {
            return $this->e404(t('Недостаточно прав'));
        }
        return false;
    }       
    
    function exec($returnAsIs = false)
    {
        //Устанавливаем заголовки для текущей страницы
        if (\Setup::$INSTALLED) {
            $pageseo = \RS\Cache\Manager::obj()
                            ->watchTables(new \PageSeo\Model\Orm\PageSeo())
                            ->request(array('\PageSeo\Model\PageSeoApi', 'getPageSeo'), $this->router->getCurrentRoute()->getId(), \RS\Site\Manager::getSiteId());
            if ($pageseo) {
                \RS\Application\Application::getInstance()->title->addSection($pageseo['meta_title']);
                \RS\Application\Application::getInstance()->meta->addKeywords($pageseo['meta_keywords']);
                \RS\Application\Application::getInstance()->meta->addDescriptions($pageseo['meta_description']);
            }
            $this->addFavicon();
        }        
        
        $result_html = parent::exec($returnAsIs);
        $this->view->caching = false;
        //Если имя метода начинается со слова "ajax", то отменяем оборачивание
        //Если в REQUEST есть ajax=1, то отменяем оборачивание
        if ($this->wrap_output && substr($this->act, 0, 4) != 'ajax' && $this->url->request('ajax', TYPE_INTEGER) != 1) {
            $this->app->blocks
                ->setRouteId($this->router->getCurrentRoute()->getId())
                ->setMainContent($result_html)
                ->setView($this->view);
            $result = $this->view->fetch($this->wrap_template);
            if ($this->user->isAdmin() && $this->user->checkSiteRights(\RS\Site\Manager::getSiteId())) {
                //Изменяем текущий сайт в админ панели.
                \RS\Site\Manager::setAdminCurrentSite( \RS\Site\Manager::getSiteId() );
                
                $this->app->addJsVar('customer_zone', true);
                
                if (\RS\Config\Loader::getSystemConfig()->show_debug_header) {
                    $debug_top_wrapper = new \RS\View\Engine();
                    $debug_top_wrapper->assign(array(
                        'result_html' => $result
                    ) + $this->view->getTemplateVars());
                    $result = $debug_top_wrapper->fetch('%system%/debug/top.tpl');
                    $this->app->addJsVar(array('adminSection' => '/'.\Setup::$ADMIN_SECTION, 'scriptType' => \Setup::$SCRIPT_TYPE ));
                }
            }            
            $result = \RS\Event\Manager::fire('controller.front.beforewrap', $result)->getResult();
            return $this->wrapHtml($result);
        } else {
            return $result_html;
        }
    }
    
    /**
    * Добавляет ссылку на favicon, загруженный в админ. панели в секцию HEAD
    * 
    * @return void
    */
    private function addFavicon()
    {
        $site_config = \RS\Config\Loader::getSiteConfig();
        if ($site_config['favicon']) {
            $favicon = $site_config['__favicon']->getLink();
            
            $params = array(
                'type' => false,
                'rel' => 'shortcut icon'
            );
            
            if (substr(strtolower($favicon), -4) == '.png') {
                $params = array(
                    'type' => 'image/png',
                    'rel' => 'icon'
                );
            }
            if (substr(strtolower($favicon), -4) == '.ico') {
                $params = array(
                    'type' => 'image/x-icon',
                    'rel' => 'shortcut icon'
                );
            }

            $this->app->addCss($favicon, null, 'root', true, $params);
        }
    }
    
    /**
    * Возвращает input[type="hidden"] с id текущего контроллера, чтобы отметить, что данный пост идет по его инициативе.
    * 
    * @return string
    */
    public function myBlockIdInput()
    {
        return '<input type="hidden" name="'.self::CONTROLLER_ID_PARAM.'" value="'.$this->getMyId().'">';
    }
    
    /**
    * Возвращает true, если инициатором POST запроса выступил данный контроллер
    * 
    * @return bool
    */
    public function isMyPost()
    {
        return $this->url->isPost() && $this->url->post(self::CONTROLLER_ID_PARAM, TYPE_STRING) == $this->getMyId();
    }    

    /**
    * Возвращает id текущего контроллера
    * 
    * @return integer
    */
    public function getMyId()
    {
        return sprintf('%u', crc32($this->getControllerName()));
    }
    
    
    /**
    * Выполняет redirect На страницу авторизации
    */
    function authPage($error = "", $referer = null)
    {
        if ($referer === null) {
            $referer = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        $_SESSION['auth_access_error'] = $error;
        
        return $this->result
                    ->setNeedAuthorize(true)
                    ->setNoAjaxRedirect($this->router->getUrl('users-front-auth', array('referer' => $referer)));     
    }

    /**
     * Проверяет нужен ли редирект на адрес, где адрес формируется не id объекта, а указанного значения в alias
     *
     * @param integer|string $id - идентификатор
     * @param \RS\Orm\OrmObject $item - объект для просмотра
     * @param string $redirect_url - адрес для редиректа
     * @param string $alias_field - наименование поля с alias
     */
    function checkRedirectToAliasUrl($id, $item, $redirect_url, $alias_field = 'alias', $id_field = 'id')
    {
        if ((int)$id == $item[$id_field] && $item[$id_field] != $item[$alias_field] && !empty($item[$alias_field])) {
            $this->redirect($redirect_url, 301);
        }
    }
}