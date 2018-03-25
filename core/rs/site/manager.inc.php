<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Site;

/**
* Класс содержит инструменты для работы с сайтами внутри данной CMS
* Зависит от модуля Site
*/
class Manager
{
    protected static 
        $adminSite,
        $defaultSite,
        $site,
        $siteId;
    
    /**
    * Возвращает id текущего сайта
    * 
    * @return integer
    */
    public static function getSiteId()
    {
        if (!self::$siteId) {
            $site = self::getSite();
            self::$siteId = $site['id'];
        }
        return self::$siteId;
    }
    
    /**
    * Возвращает объект текущего сайта, если вы находитесь в клиентской зоне
    * или возвращает редактируемый в настоящее время сайт, если вы находитесь в администратовной панели
    * 
    * @return \Site\Model\Orm\Site | false
    */
    public static function getSite()
    {        
        if (!self::$site) {
            if (!\Setup::$INSTALLED) {
                self::$site = new \Site\Model\Orm\Site();
                self::$site['id'] = 1;
            } else {
                if (\RS\Router\Manager::obj()->isAdminZone()) {
                    self::$site = self::getAdminCurrentSite();
                } else {
                    $host = \RS\Http\Request::commonInstance()->server('HTTP_HOST', TYPE_STRING);
                    $uri = \RS\Http\Request::commonInstance()->server('REQUEST_URI', TYPE_STRING);
                    self::$site = self::getSiteByUrl($host, $uri);
                }
            }
        }
        return self::$site;
    }
    
    
    /**
    * Возвращает объект сайта по доменному имени и URI
    * Если ни один сайт не определен для данного домена, возвращается сайт по-умолчанию
    * 
    * @param mixed $domain
    * @return \Site\Model\Orm\Site
    */
    public static function getSiteByUrl($host, $uri, $cache = true)
    {
        $idnaconvert = new \RS\Helper\IdnaConvert();
        $sites = self::getSiteList();
        $default = false;
        $haystack = strtolower(ltrim($uri, '/'));
        foreach($sites as $site) {
            $domains = explode(',', strtolower($site['domains']));
            $domains = array_map('trim', $domains);
            
            if ( in_array(strtolower($host), $domains)
               || in_array(strtolower($idnaconvert->decode($host)),  $domains)) { //Проверяем соответствие домену
                //Проверяем соответствие папки
                $needle = strtolower(trim($site['folder'],'/')).'/';
                
                if ($needle != '/') {
                    if (substr($haystack, 0, strlen($needle)) === $needle) {
                        //Вырезаем эту секцию из URL
                        $new_uri = '/'.substr(ltrim($uri, '/'), strlen($needle));
                        \RS\Http\Request::commonInstance()->set('REQUEST_URI', $new_uri, SERVER);
                        return $site;
                    }
                } else {
                    return $site;
                }
            }
            if ($site['default']) {
                $default = $site;
            }
        }
        return $default;
    }
    
    /**
    * Возвращает список сайтов
    * 
    * @param bool $cache - возвращать кэшированное значение
    * @return array
    */
    public static function getSiteList($cache = true)
    {
        if ($cache) {
            return \RS\Cache\Manager::obj()->tags(CACHE_TAG_SITE)->request(array(__CLASS__, 'getSiteList'), false);
        } else {        
            $result = array();
            
            try {
                $result = \RS\Orm\Request::make()
                    ->from(new \Site\Model\Orm\Site())
                    ->orderby('folder DESC')
                    ->objects();
            } catch(\RS\Db\Exception $e) {
                if (\Setup::$INSTALLED) throw $e;
            }
            
            return $result;
        }
    }
    
    
    /**
    * Возвращает объект активного сайта в админке
    * 
    * @return \Site\Model\Orm\Site | false
    */
    public static function getAdminCurrentSite()
    {
        if (!self::$adminSite) {
            $current_user = \RS\Application\Auth::getCurrentUser();
            $user_site_id = \RS\HashStore\Api::get('USER_SITE_'.$current_user['id']);
            $site = new \Site\Model\Orm\Site($user_site_id);
            
            if (!$site['id'] || !$current_user->checkSiteRights($site['id']) ) {
                $allow_site_list = $current_user->getAllowSites();
                if (count($allow_site_list)) {
                    self::$adminSite = reset($allow_site_list);
                } else {
                    self::$adminSite = false;
                }
            } else {
                self::$adminSite = $site;
            }
        }
        return self::$adminSite;
    }
    
    /**
    * Устанавливает текущий сайт для администрирования в администраторской панели
    * 
    * @param integer $site_id - ID сайта
    * @return boolean
    */
    public static function setAdminCurrentSite($site_id)
    {
        $current_user = \RS\Application\Auth::getCurrentUser();
        $sites = $current_user->getAllowSites();
        
        if (count($sites)>1 && isset($sites[$site_id])) {
            \RS\HashStore\Api::set('USER_SITE_'.$current_user['id'], $site_id);
            return true;
        }
        return false;
    }
    
    /**
    * Устанавливает текущий сайт, обходя механизмы определения сайта
    * 
    * @param \Site\Model\Orm\Site $site
    */
    public static function setCurrentSite(\Site\Model\Orm\Site $site)
    {
        self::$site = $site;
        self::$siteId = $site["id"];
    }
    
}

