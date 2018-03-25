<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Sitemap\Model;

class Api
{
    const
        ELEMENT_NAME_KEY = 'element_name',
        ELEMENT_MAP_TYPE_KEY = 'element_map_type';
    
    protected
        $site_id,
        $map_type,
        $allowed_map_types = array('google'),
        $folder = '/storage/sitemap',
        $full_filename;
    
    function __construct($site_id = null, $map_type = null)
    {
        if ($site_id) {
            \RS\Site\Manager::setCurrentSite(new \Site\Model\Orm\Site($site_id));
        }
        $this->site_id = \RS\Site\Manager::getSiteId();
        // имя файла "sitemap[_{map_type}]-{site_id}.xml"
        $filename = 'sitemap';
        if ($map_type && in_array($map_type, $this->allowed_map_types)) {
            $this->map_type = $map_type;
            $filename .= "_{$this->map_type}";
        }
        $filename .= "-{$this->site_id}.xml";
        $this->full_filename = \Setup::$PATH.$this->folder.'/'.$filename;
    }
        
    /**
    * Отдает актуальный файл sitemap.xml на вывод
    * @return void
    */
    function sitemapToOutput()
    {
        if (!$this->checkActual()) {
            $this->generateSitemap();
        }
        $app = \RS\Application\Application::getInstance();        
        if (file_exists($this->full_filename)) {
            $app->headers
                ->addHeader('Content-Type', 'text/xml')
                ->sendHeaders();
            readfile($this->full_filename);
        } else {
            $app->showException(404, t('Файл не найден'));
        }
    }
    
    /**
    * Возвращает true, если файл sitemap существует и он актуальный
    * 
    * @return bool
    */
    function checkActual()
    {
        $config = \RS\Config\Loader::byModule($this);
        $expire_time = time() - $config['lifetime']*60;
        return $config['lifetime'] != 0 && (file_exists($this->full_filename) && filemtime($this->full_filename) > $expire_time);
    }
    
    /**
    * Создает файл sitemap.xml
    * 
    */
    function generateSitemap()
    {
        $event_result = \RS\Event\Manager::fire('getpages', array());
        $pagelist = $event_result->getResult();
        $config = \RS\Config\Loader::byModule($this);
        
        $additional_urls = $config['add_urls'];
        foreach(explode("\n", $additional_urls) as $url) {
            if ($url = trim($url)) {
                $pagelist[] = array(
                    'loc' => $url
                );
            }
        }
        
        $default_page = array(
            'priority' => $config['priority'],
        );
        if ($config['set_generate_time_as_lastmod']) {
            $default_page['lastmod'] = date('c');
        }
        if ($config['changefreq'] != 'disabled') {
            $default_page['changefreq'] = $config['changefreq'];
        }
        
        \RS\File\Tools::makePath($this->full_filename, true);
        
        $this->xml = new \XMLWriter();
        $this->xml->openURI($this->full_filename);
        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->startElement('urlset');
        $this->xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->xml->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        
        $exclude_urls = !empty($config['exclude_urls']) ? explode("\r", $config['exclude_urls']) : array(); //url которые надо исключить
        
        $base_url = rtrim(\RS\Site\Manager::getSite()->getRootUrl(true), '/');
        foreach($pagelist as $page_data) {
            $page = array_diff_key($page_data, array_flip(array('custom_data'))) + $default_page;
            
            if (substr($page['loc'], 0, 4) != 'http') {
                $page['loc'] = $base_url.$page['loc'];
            }
            
            foreach ($exclude_urls as $exclude_url) {
                if (preg_match("/".trim(str_replace('/', '\/', $exclude_url))."/ui", $page['loc'])){   
                    continue 2;
                }
            } 
            
            if (isset($page['loc'])) {
                $this->xml->startElement('url');
                $this->writeItemContent($page);
                $this->xml->endElement();
            }
        }
        $this->xml->endDocument();
        $this->xml->flush();
        unset($this->xml);
    }
    
    /**
    * Рекурсивно записывает переданный массив элементов карты в xml
    * 
    * @param array $content - содержимое элемента 
    */
    protected function writeItemContent(array $content = array())
    {
        foreach ($content as $key=>$item) {
            if (is_array($item)) {
                // не записываем элементы, предназначенные для типов sitemap, отличных от текущего
                if (!empty($item[self::ELEMENT_MAP_TYPE_KEY]) && $item[self::ELEMENT_MAP_TYPE_KEY] != $this->map_type) {                    
                    continue;
                }
                $this->xml->startElement( (isset($item[self::ELEMENT_NAME_KEY])) ? $item[self::ELEMENT_NAME_KEY] : $key);
                $this->writeItemContent($item);
                $this->xml->endElement();
            } elseif (!in_array($key, array(self::ELEMENT_NAME_KEY, self::ELEMENT_MAP_TYPE_KEY))) {
                $this->xml->writeElement($key, $item);
            }
        }
    }
}