<?php
namespace SeoControl\Model\Orm;
use \RS\Orm\Type;

/**
* Класс объектов одного правила переназначения SEO параметров
*/
class SeoRule extends \RS\Orm\OrmObject
{
    protected static
        $table = 'seocontrol';
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'is_source_regex' => new Type\Integer(array(
                'description' => t('Использовать регулярные выражения'),
                'hint' => t('Если установлен данный флажок, то в поле "Маска URL" можно использовать регулярные выражения.'),
                'default' => 1,
                'checkboxView' => array(1,0)
            )),
            'url_pattern' => new Type\Varchar(array(
                'description' => t('Маска URL'),
                'hint' => t('Регулярное выражение (PCRE) которое сравнивается с текущим URI. Слеш экранируется автоматически, остальные символы(согласно правилам PCRE) необходимо экранировать обратным слешем вручную. Например:<br>'.
                    '<strong>/catalog/holodilnik/</strong> - маска страницы, URI которой содержит этот текст<br>'.
                    '<strong>^/$</strong> - маска главной страницы<br>'.
                    '<strong>^/catalog/klyuchi/</strong> - маска страницы категории товара "Ключи"<br>'.
                    '<strong>^/catalog/.*?/\?p=2</strong> - маска второй страницы любой категории')
            )),
            'domain_list' => new Type\Varchar(array(
                'description' => t('Список доменов (через запятую)'),
                'hint' => t('Если указан - правило будет работать только для указанных доменов,<br>
                            если не указан - правило будет работать всегда'),
                'maxLength' => 255,
            )),
            'h1' => new Type\Varchar(array(
                'maxLength' => 255,
                'description' => t('Заголовок H1'),
                'hint' => t('В шаблоне на месте вывода H1 должна быть использована конструкция<br> &lt;h1&gt;{moduleinsert name=\'\SeoControl\Controller\Block\SeoH1\' default=\'Здесь значение H1 по умолчанию\'}&lt;/h1&gt;')
            )),
            'meta_title' => new Type\Varchar(array(
                'maxLength' => 2000,
                'description' => t('Заголовок')
            )),
            'meta_keywords' => new Type\Varchar(array(
                'maxLength' => 1000,
                'description' => t('Ключевые слова')
            )),
            'meta_description' => new Type\Varchar(array(
                'maxLength' => 1000,
                'viewAsTextarea' => true,
                'description' => t('Описание')
            )),
            'seo_text' => new Type\Richtext(array(
                'description' => t('SEO текст (допустим HTML)')
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядковый номер'),
                'visible' => false
            ))
        ));
    }
    
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG && !$this->isModified('sortn')) {
            
            $q = \RS\Orm\Request::make()
                ->select('MAX(sortn) max_sort')
                ->from($this)
                ->where(array(
                    'site_id' => $this->__site_id->get(),
                ));
                
            $this['sortn'] = $q->exec()->getOneField('max_sort', 0) + 1;
        }
    }
    
    /**
    * Возвращает true, если маска текущего правила соответствует заданному uri
    * 
    * @param string $uri - URI
    * @return boolean
    */
    function match($uri)
    {
        // если для правила указан список доменов - проверяем текущий домен
        $domain_list = $this->getDomainList();
        if ($domain_list && !in_array(\RS\Http\Request::commonInstance()->server('HTTP_HOST'), $domain_list)) {
            return false;
        }
        $pattern = htmlspecialchars_decode($this['url_pattern']);
        if ($this['is_source_regex']){
            $pattern = str_replace('/', '\/', $pattern);
            return preg_match('/'.$pattern.'/u', $uri);
        }
        return ($uri == urldecode($pattern));
    }
    
    /**
    * Возвращает HTML SEO текст
    * 
    * @return string
    */
    function getHtmlSeoText()
    {
        return htmlspecialchars_decode($this['seo_text']);
    }
    
    /**
    * Возвращает список доменов, к которым применяется правило, или false
    * 
    * @return array|false
    */
    function getDomainList()
    { 
        if (!empty($this['domain_list'])) {
            $raw_array = explode(',', $this['domain_list']);
            $list = array();
            foreach ($raw_array as $item) {
                $domain = trim($item);
                if (!empty($domain)) {
                    $list[] = $domain;
                }
            }
            if (!empty($list)) {
                return $list;
            }
        }
        return false;
    }
}