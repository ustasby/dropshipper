<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm;
use \RS\Orm\Type;
 
/**
* Класс ORM-объектов "Добавить в 1 клик". Объект добавить в 1 клик
* Наследуется от объекта \RS\Orm\OrmObject, у которого объявлено свойство id
*/
class OneClickItem extends \RS\Orm\OrmObject
{
    const
        STATUS_NEW = 'new',
        STATUS_VIEWED = 'viewed',
        STATUS_CANCELLED = 'cancelled';
        
    protected static
        $table = 'one_click'; //Имя таблицы в БД
        
    protected
        /**
        * @var \Feedback\Model\Orm\FormItem
        */
        $form;  //Текущая форма
         
    /**
    * Инициализирует свойства ORM объекта
    *
    * @return void
    */
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(), //Создаем поле, которое будет содержать id текущего сайта
            'user_id' => new Type\Bigint(array(
                'description' => t('Пользователь'),
                'visible' => false
            )),
            'user_fio' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Ф.И.О. пользователя')
            )),
            'user_phone' => new Type\Varchar(array(
                'maxLength' => '50',
                'description' => t('Телефон пользователя')
            )),
            'title' => new Type\Varchar(array(
                'maxLength' => '150',
                'description' => t('Номер сообщения')
            )), 
            'dateof' => new Type\Datetime(array(
                'maxLength' => '150',
                'description' => t('Дата отправки')
            )), 
            'status' => new Type\Enum(array(
                self::STATUS_NEW,
                self::STATUS_VIEWED,
                self::STATUS_CANCELLED
            ), array(
                'maxLength' => '1',
                'allowEmpty' => false,
                'default'   => self::STATUS_NEW,
                'listFromArray' => array(array(
                    self::STATUS_NEW    => t('Новое'),
                    self::STATUS_VIEWED => t('Закрыт'),
                    self::STATUS_CANCELLED => t('Отменен')
                )),
                'description' => t('Статус')
            )),
            'ip' => new Type\Varchar(array(
                'maxLength' => '150',
                'description' => t('IP Пользователя')
            )),
            'currency' => new Type\Varchar(array(
                'maxLength' => '5',
                'description' => t('Трехсимвольный идентификатор валюты на момент покупки')
            )),
            'sext_fields' => new Type\Text(array(
                'description' => t('Дополнительными сведения'),
                'Template' => 'form/field/sext_fields.tpl'
            )),
            'stext' => new Type\Text(array(
                'description' => t('Cведения о товарах'),
                'Template' => 'form/field/stext.tpl'
            )),
            'kaptcha' => new Type\Captcha(array(
                'enable' => false,
                'context' => '',
            )),
        ));
    }
    
    /**
    * Возращает масстив сохранённых данных рассериализованными
    * 
    * @param string $field - поле для десеарелизации
    * @return mixed
    */
    function tableDataUnserialized($field = 'stext')
    {
       return @unserialize($this[$field]);
    }

    /**
     * Возвращает пользователя, оформившего заказ
     *
     * @return \Users\Model\Orm\User
     */
    function getUser()
    {
        if ($this['user_id']>0){
            return new \Users\Model\Orm\User($this['user_id']);
        }
        $user = new \Users\Model\Orm\User();
        $fio = explode(" ", $this['user_fio']);
        if (isset($fio[0])){
            $user['surname'] = $fio[0];
        }
        if (isset($fio[1])){
            $user['name'] = $fio[1];
        }
        if (isset($fio[2])){
            $user['midname'] = $fio[2];
        }
        $user['phone']  = $this['user_phone'];
        return $user;
    }


    /**
    * Событие срабатывает перед записью объекта в БД
    * 
    * @param string $flag  - Флаг вставки обновления, либо удаления insert или update
    * @return null
    */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG){ //Флаг вставки
            if (empty($this['stext']) && !empty($this['products'])){
                $api = new \Catalog\Model\OneClickItemApi();
                $this['stext'] = $api->prepareSerializeTextFromProducts($this['products']);
            }
            $this['user_id'] = \RS\Application\Auth::getCurrentUser()->id;
        }
        
        if (empty($this['currency'])){
           //Если Валюта не задана, то укажем базовую
           $default_currency = \Catalog\Model\CurrencyApi::getDefaultCurrency(); 
           $this['currency'] = $default_currency['title'];
        }
    }
    
   
    /**
    * Событие срабатывает после записи объекта в БД
    * 
    * @param string $flag  - Флаг вставки обновления, либо удаления
    * @return null
    */
    function afterWrite($flag)
    {
       if ($flag == self::INSERT_FLAG){ //Флаг вставки           
          
          $notice = new \Catalog\Model\Notice\OneClickAdmin();
          $notice->init($this);
          //Отсылаем письмо администратору
          \Alerts\Model\Manager::send($notice);
          
          $this['title'] = t("Покупка №").$this['id']." ".$this['user_fio']." (".$this['user_phone'].")"; //Обновим название 
          $this->update();
       }
    }
   
}