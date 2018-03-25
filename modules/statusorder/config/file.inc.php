<?php
namespace StatusOrder\Config;
use RS\Orm\Type;

/**
* Класс конфигурации модуля
*/
class File extends \RS\Orm\ConfigObject
{

	/**
    * Метод инициализации свойств ORM объекта
    * 
    * @return void
    */
    function _init()
    {
        //Дополняем свойства, определенные у родительского класса
        parent::_init()->append(array(
            //У модуля будет один параметр для настройки
            'buttonText' => new Type\Varchar(array(
                'description' => t('Текст кнопки'),
                'hint' => t('Текст кнопки "Проверить заказ"'),
                'default' => 'Проверить заказ',
            )),
            'authFrom' => new Type\Integer(array(
                'description' => t('Поле идентификации клиента'),
                'hint' => t('Выберите признак, по которому будет идентифицироваться клиент'),
                'listFromArray' => array(array(0 => t("Нет"), 1 => t("Электронная почта"), 2 => t("Телефон"))),
                'default' => 0,
            )),
            'useServicePochta' => new Type\Integer(array(
                'description' => t('Для трекинга Почты России использовать'),
                'hint' => t('Выберите сервис, который нужно использовать для трекинга посылок'),
                'listFromArray' => array(array(1 => t("API Почта России"), 2 => t("Сервис ГдеПосылка"))),
            )),
            'useServiceCDEK' => new Type\Integer(array(
                'description' => t('Для трекинга СДЕК использовать'),
                'hint' => t('Выберите сервис, который нужно использовать для трекинга посылок'),
                'listFromArray' => array(array(1 => t("API СДЕК"), 2 => t("Сервис ГдеПосылка"))),
            )),
            t('Почта России'),
                'pochtaTpl' => new Type\Varchar(array(
                    'description' => t('Укажите шаблон для Почта России'),
                    'hint' => t('Подробнее о шаблонах смотрите в Истории изменений'),
                    'default' => '^\d{14}$',
                )),
                'pochtaURL' => new Type\Varchar(array(
                    'description' => t('Адрес для Единичного доступа'),
                    'hint' => t('Адрес для Единичного доступа, посмотреть можно в ЛК трекинга Почты России'),
                )),
                'pochtaLogin' => new Type\Varchar(array(
                    'description' => t('Логин для Почты России'),
                    'hint' => t('Логин, посмотреть можно в ЛК трекинга Почты России'),
                )),
                'pochtaPassword' => new Type\Varchar(array(
                    'description' => t('Пароль для Почты России'),
                    'hint' => t('Пароль, посмотреть можно в ЛК трекинга Почты России'),
                )),
            t('BoxBerry'),
                'boxberryTpl' => new Type\Varchar(array(
                    'description' => t('Укажите шаблон для BoxBerry'),
                    'hint' => t('Подробнее о шаблонах смотрите в Истории изменений'),
                    'default' => '',
                )),
                'boxberryKey' => new Type\Varchar(array(
                    'description' => t('API-токен BoxBerry'),
                    'hint' => t('Введите API-токен BoxBerry, получить можно в панели администратора сервиса'),
                )),
            t('СДЕК'),
                'cdekTpl' => new Type\Varchar(array(
                    'description' => t('Укажите шаблон для СДЕК'),
                    'default' => '^\d{6,10}$',
                    'hint' => t('Подробнее о шаблонах смотрите в Истории изменений'),
                )),
                'cdekLogin' => new Type\Varchar(array(
                    'description' => t('Логин для СДЕК'),
                    'hint' => t('Про получение Логина и Пароля читайте на сайте cdek.ru'),
                )),
                'cdekPass' => new Type\Varchar(array(
                    'description' => t('Пароль для СДЕК'),
                    'hint' => t('Про получение Логина и Пароля читайте на сайте cdek.ru'),
                )),
        ));
    }

    public static function getDefaultValues()
    {
        return parent::getDefaultValues() + array(           
            'core_version' => '>= 3.0.0.0'
        );
    }    
}