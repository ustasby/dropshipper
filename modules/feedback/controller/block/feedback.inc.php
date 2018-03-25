<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Controller\Block;
use \RS\Orm\Type;

/**
* Блок-контроллер Статья
*/
class Feedback extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Форма отправки на E-mail',
        $controller_description = 'Отображает форму связи для пользователя с уведомлением, на E-mail';

    protected
        $default_params = array(
            'indexTemplate'        => 'blocks/feedback/feedback.tpl' //Шаблон отображения произвольной формы
        );
        
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * @return \RS\Orm\ControllerParamObject | false
    */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
                'form_id' => new Type\Varchar(array(
                    'description' => t('Выберите форму из списка'),
                    'list' => array(array('\Feedback\Model\FormApi', 'staticSelectList'))
                )),
                'hvalues' => new Type\ArrayList(array(
                    'description' => t('Массив для передачи скрытых полей key=>value'),
                    'visible' => false
                )),
                'values' => new Type\ArrayList(array(
                    'description' => t('Массив для передачи в уже существующие поля key=>value'),
                    'visible' => false
                ))
            ));
    }        

    /**
    * Показ формы отправки на e-mail и её обработка
    * 
    */
    function actionIndex()
    {
        $api     = new \Feedback\Model\FormApi();
        $form    = $api->getOneItem($this->getParam('form_id'));
        $errors  = array();
        $request = null;


        if ($form->id>0){ //Установим готовые для подстановки значения полей, если задан массив в шаблоне
            $form->setValues($this->getParam('values'));
        }
        
        if ($this->isMyPost()&&($form->id>0)) {//Если результат пришёл и форма такая существует
            /**
            * @var \Feedback\Model\Orm\FormItem $form
            */
            $form->setHiddenValues($this->getParam('hvalues'));//Получим массив скрытых полей
            if ($api->send($form, $this->url)) { //OK
                $this->view->assign('success', true);
            } else { //Если есть ошибки
                $errors  = $api->getErrors();
                $request = $this->url;
            }
        }
        
        $this->view->assign(array(
            'form'         => $form,
            'error_fields' => $errors,
            'request'      => $request
        ));
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}