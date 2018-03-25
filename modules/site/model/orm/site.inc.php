<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Site\Model\Orm;
use \RS\Orm\Type,
    \RS\Cache\Cleaner as CacheCleaner;

class Site extends \RS\Orm\OrmObject
{
    protected static
        $table = 'sites';
        
    function _init()
    {
        parent::_init();
        
        $this->getPropertyIterator()->append(array(
            'title' => new Type\Varchar(array(
                'checker' => array(
                    'chkEmpty',
                    t('Укажите название')
                ),
                'description' => t('Краткое название сайта')
            )),
            'full_title' => new Type\Varchar(array(
                'description' => t('Полное название сайта'),
                'hint' => t('Будет использовано в подписи почтовых уведомлений. Например: "Интернет-магазин модной женской одежды"')
            )),
            'domains' => new Type\Text(array(
                'description' => t('Доменные имена (через запятую)'),
                'hint' => t('Первый домен в списке - является основным. Именно он используется для построения абсолютных ссылок')
            )),
            
            'folder' => new Type\Varchar(array(
                'description' => t('Папка сайта'),
                'hint' => t('Например: en или version/english'),
                'attr' => array(array(
                    'size' => 30
                ))                
            )),            
            'language' => new Type\Varchar(array(
                'description' => t('Язык'),
                'hint' => t('2 английские буквы. Например: en или ru'),
                'attr' => array(array(
                    'size' => 2
                ))
            )),
            'default' => new Type\Integer(array(
                'description' => t('По умолчанию'),
                'hint' => t('Этот сайт будет открыт, даже если домен не соответствует заявленным'),
                'checkboxview' => array(1,0)
            )),
            'update_robots_txt' => new Type\Integer(array(
                'description' => t('Обновить robots.txt'),
                'hint' => t('При установке данного флага, файл robots.txt будет перезаписан, а в .htaccess будут внесены необходимые изменения (при использовании мультисайтовости)'),
                'runtime' => true,
                'checkboxView' => array(1,0)
            )),
            'redirect_to_main_domain' => new Type\Integer(array(
                'description' => t('Перенаправлять на основной домен'),
                'allowEmpty' => false,
                'checkboxView' => array(1,0),
                'hint' => t('Если включено, то при обращении к НЕ основному домену будт происходить 301 редирект на основной домен')
            )),
            'redirect_to_https' => new Type\Integer(array(
                'description' => t('Перенаправлять на https'),
                'hint' => t('При изменении данной опции, необходимо очистить кэш браузера'),
                'allowEmpty' => false,
                'checkboxView' => array(1,0)
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Сортировка'),
                'visible' => false
            )),
            'theme' => new Type\Varchar(array(
                'attr' => array(array(
                    'readonly' => 'readonly'
                )),
                'description' => t('Тема'),
                'runtime' => true,
                'template' => '%site%/form/site/theme.tpl'
            )),
            'is_closed' => new Type\Integer(array(
                'description' => t('Закрыть доступ к сайту'),
                'hint' => t('Используйте данный флаг, чтобы закрыть доступ к сайту на время его разработки. Администраторы будут иметь доступ как на сайт, так и в административную панель.'),
                'template' => '%site%/form/site/is_closed.tpl',
                'checkboxView' => array(1,0)
            )),
            'close_message' => new Type\Varchar(array(
                'description' => t('Причина закрытия сайта'),
                'hint' => t('Будет отображена пользователям'),
                'attr' => array(array(
                    'placeholder' => t('Причина закрытия сайта')
                )),
                'visible' => false
            )),
            'rating' => new Type\Decimal(array(
                'maxLength' => '3',
                'decimal' => '1',
                'default' => 0,
                'visible' => false,
                'description' => t('Средний балл(рейтинг)'),
                'hint' => t('Расчитывается автоматически, исходя из поставленных оценок')
            )),
            'comments' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Кол-во комментариев к сайту'),
                'default' => 0,
                'visible' => false,
            ))
        ));
    }
    
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as max')
                ->from($this)
                ->exec()->getOneField('max', 0) + 1;
        }        
        if ($this['default'] == 1) {
            //Сайт по-умолчанию может быть только один
            \RS\Orm\Request::make()
                ->update($this)
                ->set(array(
                    'default' => 0
                ))
                ->exec();
        }

    }
    
    function afterWrite($flag)
    {
        if (\Setup::$INSTALLED && $flag == self::INSERT_FLAG) {
            $theme = new \RS\Theme\Item($this['theme']);
            $theme->setThisTheme(null, $this['id']);                        
        }


        if ($this['update_robots_txt']) {
            $robotsTxtApi = new \Site\Model\RobotsTxtApi($this);
            $robotsTxtApi->AutoCreateSiteRobotsTxt();
        }

        //Очищаем кэш
        CacheCleaner::obj()->clean(CacheCleaner::CACHE_TYPE_COMMON);
    }
    
    /**
    * Возвращает список доменов, которые относятся к текущему сайту
    * 
    * @return array
    */
    function getDomainsList()
    {
        return preg_split('/[,\n\s]/', $this['domains'], null, PREG_SPLIT_NO_EMPTY);
    }
    
    /**
    * Возвращает главное доменное имя сайта
    * 
    * @return string
    */
    function getMainDomain()
    {
        $domains = $this->getDomainsList();    
        return $domains ? trim($domains[0]) : '';
    }
    
    /**
    * Возвращает уникальный идентификатор сайта
    * 
    * @return string
    */
    function getSiteHash()
    {
        return sha1($this->getMainDomain().$this['folder']);
    }
    
    /**
    * Возвращает ссылку на корень сайта
    * 
    * @param bool $absolute - Если true, то будет возвращена абсолютная ссылка, иначе относительная
    * @param bool $add_root_folder - Если true, то приписывает в конце папку, в которой находится скрипт
    * @param bool $force_https - Если true, то всегда возвращается с https, иначе в зависимости от текущего протокола
    * 
    * @return string
    */
    function getRootUrl($absolute = false, $add_root_folder = true, $force_https = false)
    {
        $domain = $this->getMainDomain();
        
        $folder = !empty($this['folder']) ? '/'.trim($this['folder'],'/').'/' : '/';
        if ($add_root_folder) {
            $folder = \Setup::$FOLDER.$folder;
        }

        if ($force_https) {
            $protocol = 'https';
        } else {
            $protocol = \RS\Http\Request::commonInstance()->getProtocol();
        }
        
        return $absolute ? $protocol.'://'.$domain.$folder : $folder;
    }
    
    /**
    * Формирует абсолютный URL из относительного применительно к текущему сайту
    * 
    * @param string $relative_uri
    * @return string
    */
    function getAbsoluteUrl($relative_uri)
    {
        return $this->getRootUrl(true, false).ltrim($relative_uri, '/');
    }
    
    function delete()
    {
        $remain = \RS\Orm\Request::make()->from($this)->count();
        if ($remain == 1) {
            $this->addError(t('Необходимо наличие хотя бы одного сайта'));
            return false;
        }
        
        $result = parent::delete();
        if ($result) {
            $api = new \Site\Model\Api();
            //Удалим всю информацию касающуюся этого сайта 
            $api->deleteSiteNoNeedInfo($this['id']); 
            
            $robots_txt_api = new \Site\Model\RobotsTxtApi($this);
            $robots_txt_api->deleteRobotsTxt();
        }
        return true;
    }
}

