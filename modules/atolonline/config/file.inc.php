<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace AtolOnline\Config;
use \RS\Orm\Type;


/**
* Описание файла конфига модуля
*/
class File extends \RS\Orm\ConfigObject
{ 
    function _init()
    {
        parent::_init()->append(array(
            'service_url' => new Type\Varchar(array(
                'description' => t('URL API'),
                'hint' => t('Допустимо пустое поле. Пустое поле означает, что будет использоваться стандартный URL для обмена с сервисом АТОЛ.ОНЛАЙН')
            )),
            '_load_settings' => new Type\Varchar(array(
                'description' => '',
                'template' => '%atolonline%/load_settings.tpl'
            )),
            'login' => new Type\Varchar(array(
                'description' => t('Логин'),
                'hint' => t('Выдаётся АТОЛ')
            )),
            'pass' => new Type\Varchar(array(
                'description' => t('Пароль'),
                'hint' => t('Выдаётся АТОЛ')
            )),
            'group_code' => new Type\Varchar(array(
                'description' => t('Группа'),
                'hint' => t('Выдаётся АТОЛ')
            )),
            'inn' => new Type\Varchar(array(
                'description' => t('ИНН организации'),
                
            )),
            'sno' => new Type\Varchar(array(
                'description' => t('Система налогообложения'),
                'hint' => t('Не обязательно, если у организации один тип налогооблажения'),
                'listFromArray' => array(array(
                    0 => t('-Не выбрано-'),
                    'Osn' => t('Общая СН'),
                    'usn_income' => t('УСН доходы'),
                    'usn_income_outcome' => t('УСН доходы минус расходы'),
                    'envd' => t('Единый налог на вменённый доход'),
                    'esn' => t('Единый сельскохозяйственный налог'),
                    'patent' => t('Патентная СН')
                ))
            )),
            'domain' => new Type\Varchar(array(
                'description' => t('Доменное имя вашего магазина (как оно указано в АТОЛ)'),
                'hint' => t('Если не указано, то будет использоваться доменное имя без протокола, например: yourstore.com'),
            )),
            'api_version' => new Type\Varchar(array(
                'description' => t('Версия протокола АТОЛ'),
                'hint' => t('Выберите версию протокола исходя из формата фискальных данных, указанных в кабинете АТОЛ'),
                'listFromArray' => array(array(
                    '3' => 'Версия 3 (ФФД 1.0)',
                    '4' => 'Версия 4 (ФФД 1.05)'
                ))
            ))
        ));
    }
    
    /**
    * Возвращает список действий для панели конфига
    * 
    * @return array
    */
    public static function getDefaultValues()
    {
        return parent::getDefaultValues() + array(           
            'tools' => array(
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('checkAuth', array(), 'atolonline-tools'),
                    'title' => t('Проверить авторизацию'),
                    'description' => t('Делает запрос на авторизацию и возвращает результат'),
                )
            )
        );
    }  
}