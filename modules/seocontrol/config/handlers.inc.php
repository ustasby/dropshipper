<?php
namespace SeoControl\Config;
use \RS\Router;

class Handlers extends \RS\Event\HandlerAbstract
{
    /**
     * Инийиализация модуля, навешивание событий
     */
    function init()
    {
        $this
            ->bind('controller.beforewrap')
            ->bind('start')
            ->bind('getmenus');
    }

    /**
     * Действия при старте системе
     */
    public static function start()
    {
        if (\RS\Application\Auth::isAuthorize() && \RS\Application\Auth::getCurrentUser()->isAdmin()){
            $header_panel = \RS\Controller\Admin\Helper\HeaderPanel::getPublicInstance();
            $header_panel->addItem(t('SEO контроль'), \RS\Router\Manager::obj()->getAdminUrl('editPublicRule', array(
                'uri' => \RS\Http\Request::commonInstance()->server('REQUEST_URI')
            ), 'seocontrol-ctrl'), array('id'=>'seocontrol-top-button', 'class' => 'addseorule debug-action-edit crud-edit', 'icon' => 'zmdi zmdi-tune'));
        }
    }

    /**
     * Возвращает пункты меню установленные из других модулей и добавляет свои
     *
     * @param array $items - массив пунктов меню
     * @return array
     */
    public static function getMenus($items)
    {
        $items[] = array(
                'title' => t('SEO контроль'),
                'alias' => 'seocontrol',
                'link' => '%ADMINPATH%/seocontrol-ctrl/',
                'typelink' => 'link',
                'parent' => 'modules'
            );
        return $items;
    }

    /**
     * Действия перед оборачиваем контента контроллера
     *
     * @param array $params
     */
    public static function controllerBeforeWrap(array $params)
    {
        $controller = $params['controller'];
        if (!\RS\Router\Manager::obj()->isAdminZone()) {
            $api = new \SeoControl\Model\Api();
            $rule = $api->getRuleForUri(\RS\Http\Request::commonInstance()->server('REQUEST_URI'));
            
            //Инициализируем SEO генератор
            $seoGen = new \SeoControl\Model\SeoReplace\SeoRule();
            
            //Подменим значния мета-данных
            $rule_title       = $seoGen->replace($rule['meta_title']);
            $rule_keywords    = $seoGen->replace($rule['meta_keywords']);
            $rule_description = $seoGen->replace($rule['meta_description']);
            
            if ($rule['meta_title']) {
                $controller->app->title->clean()->addSection($rule_title);
            }
            if ($rule['meta_keywords']) {
                $controller->app->meta->cleanMeta('keywords')->addKeywords($rule_keywords);
            }
            if ($rule['meta_description']) {
                $controller->app->meta->cleanMeta('description')->addDescriptions($rule_description);
            }

            if (\RS\Application\Auth::isAuthorize() && \RS\Application\Auth::getCurrentUser()->isAdmin()){
                \RS\Application\Application::getInstance()->addJs('%seocontrol%/location_change.js');
            }
        }
    }
}