<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Model\Orm;
use \RS\Orm\Type;
 
/**
* Класс ORM-объектов "Формы отправки". Объект результата отправки формы
* Наследуется от объекта \RS\Orm\OrmObject, у которого объявлено свойство id
*/
class ResultItem extends \RS\Orm\OrmObject
{
    protected static
        $table = 'connect_form_result'; //Имя таблицы в БД
    protected
        /**
        * @var \Feedback\Model\Orm\FormItem
        */
        $form;  //Текущая форма
        
    private 
        $hvalues = false,  //Скрытые дополнительные поля
        $values  = false;  //Массив с значениями для существующих полей 
         
    /**
    * Инициализирует свойства ORM объекта
    *
    * @return void
    */
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(), //Создаем поле, которое будет содержать id текущего сайта
            'form_id' => new Type\Integer(array(
                'description' => t('Форма'),
                'List' => array(array('\Feedback\Model\FormApi', 'staticSelectList')),
            )), 
            'title' => new Type\Varchar(array(
                'maxLength' => '150',
                'description' => t('Название'),
                'meVisible' => false
            )), 
            'dateof' => new Type\Datetime(array(
                'maxLength' => '150',
                'description' => t('Дата отправки')
            )), 
            'status' => new Type\Enum(array(
                'new',
                'viewed',
            ), array(
                'maxLength' => '1',
                'allowEmpty' => false,
                'default'   => 'new',
                'listFromArray' => array(array(
                    'new'    => t('Новое'),
                    'viewed' => t('Просморен'),
                )),
                'description' => t('Статус')
            )),            
            'ip' => new Type\Varchar(array(
                'maxLength' => '150',
                'description' => t('IP Пользователя'),
                'meVisible' => false
            )),
            'stext' => new Type\Text(array(
                'description' => t('Содержимое результата формы'),
                'Template' => 'form/field/stext.tpl',
                'meVisible' => false
            )),
            'answer' => new Type\Text(array(
                'description' => t('Ответ'),
                'meVisible' => false
            )), 
            'send_answer' => new Type\Integer(array(
                'maxLength' => '1',
                'meVisible' => false,
                'checkBoxView' => array(1,0),
                'runtime' => true,
                'description' => t('Отправить ответ на E-mail пользователя')
            )), 
        ));
        
    }
    
    /**
    * Возращает масстив сохранённых данных рассериализованными
    * 
    * @param string $field - поле для десеарелизации
    * @return array|mixed
    */
    function tableDataUnserialized($field = 'stext')
    {
       return unserialize($this['stext']); 
    }
    
    /**
    * Событие срабатывает перед записью объекта в БД
    * 
    * @param mixed $flag  - Флаг вставки обновления, либо удаления
    * @return null
    */
    function beforeWrite($flag)
    {
       if ($flag == self::UPDATE_FLAG){ //Флаг обновления
          //Проверим можем ли мы отослать ответ пользователю
          if ($this->hasEmail() && $this['send_answer']){ //Если есть поле с E-mail и стоит галка отсылать на E-mail
             $email = $this->getFirstEmail();
             if (!empty($email) && !empty($this['answer'])){  //Если значение существует, то отправим на E-mail этому пользователю уведомление с ответом
                $form_api = new \Feedback\Model\FormApi();
                $form_api->sendAnswer($email,$this['answer']);
             } 
          }
       } 
    } 
   
    /**
    * Событие срабатывает после записи объекта в БД
    * 
    * @param mixed $flag  - Флаг вставки обновления, либо удаления
    * @return null
    */
    function afterWrite($flag)
    {
       if ($flag == self::INSERT_FLAG){ //Флаг вставки
          $this['title'] = t("Сообщение №").$this['id']; //Обновим название 
          $this->update();
          
          $notice = new \Feedback\Model\Notice\NewResultAdmin();
          $notice->init($this);
          \Alerts\Model\Manager::send($notice);
       }
    }

    /**
    * Получает значение первого поля E-mail
    * 
    * @return string
    */
    function getFirstEmail()
    {
       $data_values = $this->tableDataUnserialized();
       foreach($data_values as $data){
           if ($data['field']['show_type'] == $data['field']::SHOW_TYPE_EMAIL){
              return $data['value']; 
           }
       } 
       return '';
    }

    /**
    * Возращает true, если есть хоть одно поле с E-mail
    * 
    * @return boolean
    */
    function hasEmail()
    {
       if (!isset($this->form)){ //Если форма ещё не загружена
          $this->getFormObject();  
       } 
       $fields = $this->form->getFields();
       if (!empty($fields)){
         foreach($fields as $field){
              /**
              * @var \Feedback\Model\Orm\FormFieldItem
              */
              if ($field['show_type'] == $field::SHOW_TYPE_EMAIL){
                  return true;
              }
         }   
       } 
       return false;
    }
                         
    /**
    * Получает объект формы для текущего результата и возвращает себя
    * 
    * @return \Feedback\Model\Orm\FormItem
    */
    function getFormObject()
    {
        if ($this->form === null) {
            $this->form = new \Feedback\Model\Orm\FormItem($this['form_id']);
        }
        return $this->form;
    }
   
}