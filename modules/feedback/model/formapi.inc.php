<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Model;

class FormApi extends \RS\Module\AbstractModel\EntityList
{    
    public 
        $uniq;
    
    function __construct()
    {
        parent::__construct(new \Feedback\Model\Orm\FormItem(),
            array(
                'nameField' => 'title', 
                'multisite' => true,
                'defaultOrder' => 'title'
            ));
    }
    
    /**
    * Возвращает древовидный список элементов, где первый элемент - "Все"
    * @return array or false
    */
    function listWithAll()
    {
        $list = array();
        $res  = $this->getListAsResource();
        while($row = $res->fetchRow()) {
            $list[] = array('fields' => $row, 'child' => null);
        }
        return $list;
    }
    
    
    
    /**
    * Сохраняет результат формы в объект
    * 
    * @param Orm\FormItem $form - ORM объект
    * @param mixed $form_fields - массив значений
    */
    function saveResult(Orm\FormItem $form, array $form_fields)
    {
        $form_result            = new \Feedback\Model\Orm\ResultItem();
        $form_result['site_id'] = \RS\Site\Manager::getSiteId();
        $form_result['dateof']  = date('Y-m-d H:i:s');
        $form_result['form_id'] = $form['id'];
        $form_result['ip']      = $_SERVER['REMOTE_ADDR'];
        $form_result['stext']   = serialize($form_fields);
        $form_result->insert(); 
        
        return $form_result;
    }    
    
    
    
    /**
    * Отправляет ответ пользователю на его обращение в форму обратной связи
    * 
    * @param string $to_email - на чей E-mail отправляется
    * @param string $answer   - тело ответа
    */
    function sendAnswer($to_email,$answer)
    {
       $system_config    = \RS\Config\Loader::getSiteConfig();
       $site             = \RS\Site\Manager::getSite();
       
       $mailer           = new \RS\Helper\Mailer();
       $mailer->Subject  = $site['title'].t('. Ответ на форму обратной связи');
       $mailer->addAddress($to_email);
       $mailer->isHTML(false);
       $mailer->Body     = $answer;
       $mailer->send();
    }         
    
        
    
    /**
    * Отправяет форму на E-mail пользователя по определённому шаблону
    * 
    * @param \Feedback\Model\Orm\FormItem $form - форма для отправки
    * @param \RS\Http\Request $request          - то что пришло из POST для отправки
    * 
    * @return boolean
    */
    function send(Orm\FormItem $form, \RS\Http\Request $request)
    {
        $fields         = $form->getFields();           //Получим поля существующие формы
        $captchaUsed    = false;
        $captcha_config = \RS\Config\Loader::byModule('kaptcha');

        if ($form['use_csrf_protection']) {
            $request->checkCsrf();
        }
        
        foreach($fields as $k => $form_field){
            /**
            * @var \Feedback\Model\Orm\FormFieldItem $form_field
            */
            switch($form_field['show_type']){
                case Orm\FormFieldItem::SHOW_TYPE_EMAIL: //Если E-mail
                      $value = trim($request->post($form_field['alias'],TYPE_STRING));
                      //Проверим условие

                    if (!filter_var($value,FILTER_VALIDATE_EMAIL)&&($form_field['required']==1)){  //Если поле пустое и обязательное
                        $this->addError(t('Поле %0 заполнено не верно', array($form_field['title'])),$form_field['alias']);
                    }



                      
                   break;
                case Orm\FormFieldItem::SHOW_TYPE_FILE: //Если файл
                      $value = $request->files($form_field['alias']);
                      $text_error = "";
                      // Если файл не загружен, и это не обязательное поле, то проскакиваем
                      if (!$value['name'] && !$form_field['required']){ 
                          unset($fields[$k]);
                          break;
                      }
                      if ( ((empty($value) && $form_field['required']) || $text_error = \RS\File\Tools::checkUploadError($value['error']) )){ //Проверим есть ли ошибки при загрузке
                          $this->addError(t('Загрузка файла не удалась. ').$text_error, $form_field['alias']); 
                      }else{
                          $fileinfo = pathinfo($value['name']); //Получим информацию о файле 
                          //Проверим допустимые форматы       
                          if (!$form_field->checkFilekExtension($fileinfo['extension'])){
                             $this->addError(t('Загрузка файла не удалась. Расширение файла должно быть '.$form_field['file_ext']),$form_field['alias']); 
                          }    
                          //Проверим размер максимальный для загрузки
                          if (!$form_field['file_size']>$value['size']){
                             $this->addError(t('Загрузка файла не удалась. Размер файла слишком большой для поля '.$form_field['title']),$form_field['alias']); 
                          }  
                          if (!$this->hasError()){ //Если нет ошибок, то сохраним файл
                             $saved_file = $this->saveAttachedFile($value);
                             $value['real_file_name'] = $saved_file;
                             $value['real_file_name_absolute'] = \RS\Site\Manager::getSite()->getAbsoluteUrl($saved_file);
                          }
                      }
                   
                   break;
                case Orm\FormFieldItem::SHOW_TYPE_CAPTCHA:
                    $captchaUsed = true;
                    $value = trim($request->post($form_field['alias'],TYPE_STRING));
                    $captcha = \RS\Captcha\Manager::currentCaptcha();
                    $captcha_context = 'form_' . $form_field['form_id'];
                    if (!$captcha->check($value, $captcha_context)) {
                        $this->addError($captcha->errorText(), $form_field['alias']);
                    }
                    break;
                case Orm\FormFieldItem::SHOW_TYPE_LIST:
                    $value = $request->post($form_field['alias'], TYPE_MIXED);
                    if (empty($value)&&($form_field['required']==1)){  //Если поле пустое и обязательное
                        $this->addError(t('Поле "'.$form_field['title'].'" не заполнено'), $form_field['alias']);
                    }
                    break;
                default:  //По умолчанию если не файл и не Email
                   $value = trim($request->post($form_field['alias'],TYPE_STRING));
               
                   if (empty($value)&&($form_field['required']==1)){  //Если поле пустое и обязательное
                      $this->addError(t('Поле "'.$form_field['title'].'" не заполнено'),$form_field['alias']);
                   }
                   //Если нужна валидация регулярным выражением
                   if (!empty($form_field['use_mask'])){
                      if (!preg_match('%'.$form_field['mask'].'%',$value)){ //Если не прошло валидацию
                         if ($form_field['error_text']){
                            $this->addError($form_field['error_text'],$form_field['alias']); 
                         }else{
                            $this->addError(t('Поле "'.$form_field['title'].'" не прошло проверку'),$form_field['alias']); 
                         } 
                          
                      } 
                   }
                   
                   break; 
           }
            
            
           $mail[] = array(
                'field' => $form_field,
                'value' => $value
           );

            $form_field->setValue($value);
        }
        
        
        //Если кто-то пытается слать без каптчи, а она требуется
        if (!$captchaUsed && ($form['use_captcha'] == "1")){
           $this->addError(t('Требуется проверка защитного кода'),$form_field['alias']);  
        }
        
        if ($this->hasError())return false;

        $event_result = \RS\Event\Manager::fire('feedback.setvalues', array(
            'values' => $mail
        ));

        list($mail) = $event_result->extract();

        //Сохраним сведения о том, что мы отправляем письмо админку
        $result_item = $this->saveResult($form, $mail);
        
        //Отправим наше письмо
        $mail = new \Feedback\Model\MailItem($form, $mail, $result_item['id']);
        if (!$mail->send()) {
            return $this->addError(t('Не удается отправить письмо'));
        }
        return true;
    } 
    
    /**
    * Сохраняет файл на жёсткий диск, который был загружен
    * 
    * @param array $file_info - массив загруженного файла
    */
    function saveAttachedFile($file_info){
       $dir_to_save = \Setup::$PATH.\Setup::$STORAGE_DIR."/files";
       if (!file_exists($dir_to_save)){ //Если папка е существут, создадим её
          \RS\File\Tools::makePath($dir_to_save);
       } 
       
       $fileinfo  = pathinfo($file_info['name']); //Получим информацию о файле 
       $file_name = md5($file_info['name'].time()).".".$fileinfo['extension'];
       $file      = $dir_to_save."/".$file_name;
       
       //Пересадим файл в нужную папку
       @copy($file_info['tmp_name'],$file);
       return \Setup::$STORAGE_DIR."/files/".$file_name; 
    }   
    
}

