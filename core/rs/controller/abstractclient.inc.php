<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;
use \RS\Debug;

/**
* Базовый класс блочных и фронтальных контроллеров клиентской части
*/
abstract class AbstractClient extends AbstractModule
{
    protected
        /**
        * @var \RS\Debug\Group
        */
        $debug_group = null; //Группа инструментов, отображаемая в режиме отладки
    
    function __construct($param = array())
    {
        
        parent::__construct($param);
        
        if (\RS\Debug\Mode::isEnabled() && !$this->router->isAdminZone()) {
            $uniq = sprintf("%u", crc32(Debug\Group::getNextCounter().$this->url->server('REQUEST_URI', TYPE_STRING)));
            $this->debug_group = Debug\Group::getInstance($uniq);
            
            $this->debug_group->addTool('info', new Debug\Tool\Info($this->mod_name, $this->controller_name));            
          
            if ($this->getParam('generate_by_grid')) { // Если блок сгенерирован через админ 
                $this->debug_group->addTool('block_options', new Debug\Tool\BlockOptions($this->getParam('_block_id')));
            }
        }
    }
    
    /**
    * Обрабатывает результат выполнения действия, возвращает HTML
    * Отправляет подготовленные заголовки в браузер
    * 
    * @param Result\IResult $result
    * @return string;
    */    
    function processResult($result)
    {
        $result_html = parent::processResult($result);
        
        if (($debug_group = $this->getDebugGroup()) && !$this->url->isAjax() && !($this instanceof \Main\Controller\Block\MainContent)) {
            if ($debug_group->getTools('info') && $result instanceof \RS\Controller\Result\ITemplateResult) {
                
                $var_list = $debug_group->getTools('info')->parseVars($result);
                $debug_group->addData('info', 'vars', $var_list);
                $debug_group->getTools('info')->render_template = $result->getTemplate();
                
                if ($this->view->called_hooks) {
                    //Добавляем инструмент сортировки модулей в рамках хука
                    $debug_group->addData('hooksort', 'hooks', $this->view->called_hooks);
                    
                    $sort_dialog_url = $this->router->getAdminUrl(false, array('toolgroup' => $debug_group->getUniq()), 'templates-hooksort');
                    $debug_group->addTool('hooksort', new Debug\Tool\Sorting($sort_dialog_url, t('Порядок модулей')));
                }
            }
            $wrap = new \RS\View\Engine();            
            $wrap->assign(array(
                'group' => $debug_group,
                'result_html' => $result_html
            ));
            $result_html = $wrap->fetch('%system%/debug/mod_wrap.tpl');
        }
        return $result_html;
    }
    
    /**
    * Возвращает объект группы инструментов, для текущего контроллера
    * @return \RS\Debug\Group | null
    */
    function getDebugGroup()
    {
        return $this->debug_group;
    }    
    
    /**
    * Оборачивает HTML секциями body, html добавляет секцию head с мета тегами, заголовком
    * Сперва ищет оборачивающий шаблон html.tpl в папке с темой
    * 
    * @param string $body - HTML, внутри тега body, который нужно обернуть
    * @param string $html_template - имя оборачивающего шаблона
    * @return string
    */
    function wrapHtml($body, $html_template = null)
    {
        if (!$html_template) {
            //Сперва ищем html.tpl в папке с текущей темой
            $html_theme_template = '%THEME%/html.tpl';
            if ($this->view->templateExists($html_theme_template)) {
                return parent::wrapHtml($body, $html_theme_template);
            }
        }
        
        return parent::wrapHtml($body, $html_template);
    }    
}

