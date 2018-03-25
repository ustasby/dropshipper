<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Site\Config;
use \RS\Orm\Type;
/**
* Патчи к модулю
*/
class Patches extends \RS\Module\AbstractPatches
{
    /**
    * Возвращает список имен существующих патчей
    */
    function init()
    {
        return array(
            '20027',
            '20021',
            '20025',
            '20034',
            '20037',
            '20038',
            '309'
        );
    }

    function afterUpdate309()
    {
        $site_config = new \Site\Model\Orm\Config();
        $site_config->getPropertyIterator()->append(array(
            t('Организация'),
            'firm_email' => new Type\Varchar(array(
                'description' => t('Официальный Email компании'),
            )),
        ));
        $site_config->dbUpdate();
    }

    function afterUpdate20038()
    {
        $site_config = new \Site\Model\Orm\Config();
        $site_config->getPropertyIterator()->append(array(
            t('Организация'),
                'firm_address' => new Type\Varchar(array(
                    'description' => t('Физический адрес компании'),
                )),
            t('Персональные данные'),
                'policy_personal_data' => new Type\Richtext(array(
                    'description' => t('Политика обработки персональных данных (ссылка /policy/)'),
                )),
                'agreement_personal_data' => new Type\Richtext(array(
                    'description' => t('Соглашение на обработку персональных данных (ссылка /policy-agreement/)'),
                )),
                'enable_agreement_personal_data' => new Type\Integer(array(
                    'description' => t('Включить отображение соглашения на обработку персональных данных в формах'),
                ))
        ));
        $site_config->dbUpdate();
    }

    function beforeUpdate20037()
    {
        $site_config = new \Site\Model\Orm\Config();
        $site_config->getPropertyIterator()->append(array(
            t('Основные'),
            'is_closed' => new Type\Integer(array(
                'description' => t('Закрыть доступ к сайту'),
                'hint' => t('Используйте данный флаг, чтобы закрыть доступ к сайту на время его разработки'),
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
            ))
        ));
        $site_config->dbUpdate();
    }
    
    function beforeUpdate20034()
    {
        $site = new \Site\Model\Orm\Site();
        $site->getPropertyIterator()->append(array(
            'redirect_to_https' => new Type\Integer(array(
                'description' => t('Перенаправлять на Https'),
                'allowEmpty' => false,
                'checkboxView' => array(1,0)
            )),
        ));
        $site->dbUpdate();
    }    
    
    function beforeUpdate20027()
    {
        $site_config = new \Site\Model\Orm\Config();
        $site_config->getPropertyIterator()->append(array(
            t('Основные'),
            'favicon' => new Type\File(array(
                'description' => t('Иконка сайта 16x16 (PNG, ICO)'),
                'hint' => t('Отображается возле заголовка страницы в браузерах'),
                'max_file_size' => 100000, //100 Кб
                'allow_file_types' => array('image/png', 'image/x-icon'),
                'storage' => array(\Setup::$ROOT, \Setup::$FOLDER.'/storage/favicon/')
            ))
        ));
        $site_config->dbUpdate();
    }
    
    function beforeUpdate20021()
    {
        $site = new \Site\Model\Orm\Site();
        $site->getPropertyIterator()->append(array(
            'redirect_to_main_domain' => new Type\Integer(array(
                'description' => t('Перенаправлять на основной домен'),
                'allowEmpty' => false,
                'checkboxView' => array(1,0),
                'hint' => t('Если включено, то при обращении к НЕ основному домену будт происходить 301 редирект на основной домен')
            )),
        ));
        $site->dbUpdate();
    }
    
    function beforeUpdate20025()
    {
        $site_config = new \Site\Model\Orm\Config();
        $site_config->getPropertyIterator()->append(array(
            t('Социальные ссылки'),
            'instagram_group' => new Type\Varchar(array(
                'description' => t('Ссылка на страницу в Instagram')
            ))
        ));        
        $site_config->dbUpdate();
    }
}
