<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Comments\Model\Orm;
use \RS\Orm\Type;

/**
* ORM объект одного комментария
*/
class Comment extends \RS\Orm\OrmObject
{
	protected static
		$table = 'comments';
    
    protected
        $cache_user = null,
        $cache_type; //Объект пользователя
		
	function _init()
	{
        if (\RS\Debug\Mode::isEnabled()) {        
            $this->addDebugActions(array(
                new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'comments-ctrl')),
                new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'comments-ctrl'))
            ));
        }        
        
        $api = new \Comments\Model\Api();
        
		parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'type' => new Type\Varchar(array(
                'maxLength' => '150',
                'index' => true,
                'description' => t('Класс комментария'),
                'list' => array(array($api, 'getTypeList'))
            )),
            'aid' => new Type\Integer(array(
                'maxLength' => '12',
                'description' => t('Идентификатор объект'),
            )),
            '__url__' => new Type\Mixed(array(
                'visible' => true,
                'description' => t('Ссылка на объект'),
                'template' => '%comments%/form/comment/url.tpl'
            )),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата'),
            )),
            'user_id' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Пользователь'),
            )),
            '__url_user__' => new Type\Mixed(array(
                'visible' => true,
                'description' => t('Ссылка на пользователя'),
                'template' => '%comments%/form/comment/user.tpl'
            )),
            'user_name' => new Type\Varchar(array(
                'maxLength' => '100',
                'Checker' => array('chkEmpty', t('Напишите, пожалуйста, Ваше имя')),
                'description' => t('Имя пользователя'),
            )),
            'message' => new Type\Text(array(
                'description' => t('Сообщение'),
                'Checker' => array('chkEmpty', t('Напишите, пожалуйста, отзыв')),                
            )),
            'moderated' => new Type\Integer(array(
                'maxLength' => '1',
                'description' => t('Проверено'),
                'CheckboxView' => array(1,0),
            )),
            'rate' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Оценка (от 1 до 5)'),
            )),
            'help_yes' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Ответ помог'),
                'allowEmpty' => false
            )),
            'help_no' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Ответ не помог'),
                'allowEmpty' => false
            )),
            'ip' => new Type\Varchar(array(
                'maxLength' => '15',
                'description' => t('IP адрес'),
            )),
            'useful' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Полезность'),
                'allowEmpty' => false
            )),
            'help' => new Type\Mixed(array(
                'visible' => false
            )),
            'captcha' => new Type\Captcha(array(
                'visible' => false,
                'enable' => false,
                'context' => '',
            ))
        ));
	}

    /**
     * Выполняет действие до записи объекта
     */
	public function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['ip'] = $_SERVER['REMOTE_ADDR'];

            if (!$this->isModified('dateof')) {
                $this['dateof'] = date('Y-m-d H:i:s');
            }
        }

        if ($this['rate'] > 5) $this['rate'] = 5;
    }

    /**
     * Выполняет действие после записи объекта
     */
    public function afterWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $notice = new \Comments\Model\Notice\NewCommentAdmin();
            $notice->init($this);
            \Alerts\Model\Manager::send($notice);
        }

        if ($type_class = $this->getTypeObject()) {
            $type_class->onAdd();
        }

        if ($this['replace_by_ip']) {
            $this->deleteOtherComments(); //Удаляем другие комменты с голосами
        }
    }

    /**
     * Удаляет другие комментарии с текущего IP для текущего объекта
     *
     * @return void
     */
    public function deleteOtherComments()
    {
        \RS\Orm\Request::make()
            ->delete('A, B')
            ->from($this)->asAlias('A')
            ->leftjoin(new Vote(), 'A.id = B.comment_id', 'B')
            ->where("A.aid = '#aid' AND A.type = '#type' AND A.ip = '#ip'", array(
                'aid' => $this['aid'],
                'type' => $this['type'],
                'ip' => $this['ip']
            ))
            ->where("A.id != '#id'", array('id' => $this['id']))
            ->exec();
    }

    /**
    * Возвращает отладочные действия, которые можно произвести с объектом
    * 
    * @return RS\Debug\Action[]
    */
    public function getDebugActions()
    {
        return array(
            new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'comments-ctrl')),
            new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'comments-ctrl'))
        );
    }    
    
    /**
    * Получает тип объекта комментария
    * 
    * @return string
    */
    function getTypeObject()
    {
        if ($this->cache_type === null && class_exists($this['type'])) {
            $this->cache_type = new $this['type']($this);
        }
        return $this->cache_type;
    }
    
    
    /**
    * Получает ссылку на пользователя в админ панели.
    * 
    * @return false|string
    */
    function getUserAdminHref()
    {
       if ($this['user_id']) {
          return \RS\Router\Manager::obj()->getAdminUrl('edit', array('id' => $this['user_id']), 'users-ctrl'); 
       } 
       return false; 
    }     
    
    /**
    * Возвращает пользователя оставившего комментарий
    * 
    * @return \Users\Model\Orm\User
    */
    function getUser() 
    {
        if ($this->cache_user === null){
            $this->cache_user = new \Users\Model\Orm\User($this['user_id']);
        }  
        return $this->cache_user;
    }
    
    /**
    * Возвращает оценку комментария в виде текста
    * 
    * @return string
    */
    function getRateText()
    {
        $rates = self::getRates();
        return $rates[$this['rate']];
    }
    
    /**
    * Возвращает список возможных значений для комментариев
    * 
    * @return array
    */
    public static function getRates()
    {
        return array(
            t('нет оценки'),
            t('ужасно'),
            t('плохо'),
            t('нормально'),
            t('хорошо'),
            t('отлично')
        );
    }

    /**
     * Возвращает текстовое отображение оценки
     *
     * @return string
     */
    function getRateStr()
    {
        $rates = self::getRates();
        return $rates[(int)$this['rate']];
    }
    
    function delete()
    {
        if ($result = parent::delete()) {
            if ($type_class = $this->getTypeObject()) {
                $type_class->onDelete();
            }
        }
        return $result;
    }
}

