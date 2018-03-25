<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Users\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - пользователь системы.
*/
class User extends \RS\Orm\OrmObject
{
    const 
        SESSION_LAST_VISIT_VAR = 'last_visit',
        PASSWORD_LEN = 4,
        SUPERVISOR_GROUP = 'supervisor';
        
    protected static
        $table = "users";
    
    protected 
        $default_group = 'guest', //Группа, к которой определять пользователя по умолчанию
        $authorized_user_group = 'clients', //Группа, к которой относятся все авторизованные пользователи 
        $access_menu_table,
        $access_module_table,
        
        $cache_cost_id,
        $cache_mod_access,
        $cache_menu_access,
        $cache_admin_menu_access,
        $cache_allow_sites;
        
    protected $groups;
    
    function __construct($id = null, $cache = true)
    {
        $this->access_menu_table = \Setup::$DB_TABLE_PREFIX.'access_menu';
        $this->access_module_table = \Setup::$DB_TABLE_PREFIX.'access_module';        
        parent::__construct($id, $cache);
    }

    protected function _init()
    {
        parent :: _init();
        
        $for_company = array('is_company' => 1);
        $chk_depend = array(get_class($this), 'chkDepend');        
        
        $this->getPropertyIterator()->append(array(
            t('Основные'),
                    'name' => new Type\Varchar(array(
                        'maxLength' => '100',
                        'description' => t('Имя'),
                        'Checker' => array('chkEmpty', t('Укажите, пожалуйста, имя')),
                        'meVisible' => false,
                    )),
                    'surname' => new Type\Varchar(array(
                        'maxLength' => '100',
                        'description' => t('Фамилия'),
                        'Checker' => array('chkEmpty', t('Укажите, пожалуйста, фамилию')),
                        'meVisible' => false,
                    )),
                    'midname' => new Type\Varchar(array(
                        'maxLength' => '100',
                        'description' => t('Отчество'),
                        'meVisible' => false,
                    )),
                    'e_mail' => new Type\Varchar(array(
                        'maxLength' => '150',
                        'description' => 'E-mail',
                        'unique' => true,
                        'Checker' => array('chkEmail', t('Неверно заполнен e-mail')),
                        'meVisible' => false,
                    )),
                    'login' => new Type\Varchar(array(
                        'maxLength' => '64',
                        'unique' => true,
                        'description' => t('Логин'),
                        'meVisible' => false,
                    )),
                    'openpass' => new Type\Varchar(array(
                        'template' => '%users%/form/user/chpass.tpl',
                        'maxLength' => '100',
                        'description' => t('Новый пароль'),
                        'runtime' => true,
                        'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete'=>'off')),
                        'Checker' => array(array(__CLASS__, 'checkOpenPassword'), ''),
                        'appVisible' => false,
                        'meVisible' => false,
                    )),
                    'pass' => new Type\Varchar(array(
                        'maxLength' => '32',
                        'description' => t('Пароль'),
                        'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete'=>'off')),
                        'listenPost' => false,
                        'visible' => false,
                        'meVisible' => false,
                    )),
                    'phone' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'description' => t('Телефон'),
                        'Checker' => array('chkPattern', t('Неверно указан телефон'), '/^[0-9()\-\s+,]+$/'),
                        'meVisible' => false,
                    )),
                    'sex' => new Type\Varchar(array(
                        'maxLength' => '1',
                        'description' => t('Пол'),
                        'ListFromArray' => array(array('' => t('Не выбрано'), 'M' => t('Мужской'), 'F' => t('Женский'))),
                        'Attr' => array(array()),
                        'meVisible' => false,
                    )),
                    'hash' => new Type\Varchar(array(
                        'maxLength' => '64',
                        'index' => true,
                        'description' => t('Ключ'),
                        'visible' => false,
                        'meVisible' => false,
                    )),
                    'subscribe_on' => new Type\Integer(array(
                        'maxLength' => '1',
                        'description' => t('Получать рассылку'),
                        'CheckBoxView' => array(1,0),
                        'meVisible' => false,
                    )),
                    'dateofreg' => new Type\Datetime(array(
                        'description' => t('Дата регистрации'),
                        'meVisible' => false,
                    )),
                    'balance' => new Type\Decimal(array(
                        'allowEmpty' => false,
                        'readOnly' => true,
                        'listenPost' => false,
                        'maxLength' => '15',
                        'decimal' => 2,
                        'description' => t('Баланс'),
                        'appVisible' => false,
                        'meVisible' => false,
                    )),
                    'balance_sign' => new Type\Varchar(array(
                        'visible' => false,
                        'listenPost' => false,
                        'description' => t('Подпись баланса'),
                        'meVisible' => false,
                    )),
                    'ban_expire' => new Type\Datetime(array(
                        'description' => t('Заблокировать до ...'),
                        'template' => '%users%/form/user/ban_expire.tpl',
                        'meVisible' => false,
                    )),
                    'ban_reason' => new Type\Varchar(array(
                        'description' => t('Причина блокировки'),
                        'visible' => false,
                        'meVisible' => false,
                    )),
                    'last_visit' => new Type\Datetime(array(
                        'description' => t('Последний визит'),
                        'meVisible' => false,
                    )),
                    'last_ip' => new  Type\Varchar(array(
                        'description' => t('Последний IP, который использовался'),
                        'maxLength' => 100,
                        'meVisible' => false,
                    )),
                    'registration_ip' => new Type\Varchar(array(
                        'description' => t('IP пользователя при регистрации'),
                        'maxLength' => 100,
                        'meVisible' => false,
                    )),
            t('Организация'),
                    'is_company' => new Type\Integer(array(
                        'maxLength' => '1',
                        'description' => t('Это юридическое лицо?'),
                        'ListFromArray' => array(array('0' => t('Нет'), '1' => t('Да'))),
                        'meVisible' => false,
                    )),
                    'company' => new Type\Varchar(array(
                        'maxLength' => '255',
                        'description' => t('Название организации'),
                        'condition' => $for_company,
                        'Checker' => array($chk_depend, t('Не указано название организации'), 'chkEmpty', $for_company),
                        'meVisible' => false,
                    )),
                    'company_inn' => new Type\Varchar(array(
                        'maxLength' => '12',
                        'description' => t('ИНН организации'),
                        'condition' => array('is_company' => 1),
                        'Checker' => array($chk_depend, t('ИНН должен состоять из 10 или 12 цифр'), 'chkPattern', $for_company, array('/^(\d{10}|\d{12})$/')),
                        'attr' => array(array(
                            'size' => 20
                        )),
                        'meVisible' => false,
                    )),
            t('Группы'),
                '__groups__' => new Type\UserTemplate('%users%/form/user/groups.tpl', '%users%/form/user/megroups.tpl', array(
                    'meVisible' => true,
                )),
                'groups' => new Type\ArrayList(array(
                    'description' => t('Группы'),
                    'appVisible' => false
                )),
            t('Дополнительные сведения'),
                    'data' => new Type\ArrayList(array(
                        'description' => '',
                        'template' => '%users%/form/user/userfields.tpl',
                        'meVisible' => false,
                    )),
                    'changepass' => new Type\Integer(array(
                        'description' => t('Сменить пароль'),
                        'runtime' => true,
                        'CheckBoxView' => array(1,0),
                        'visible' => false,
                        'Attr' => array(array('id' => 'chpass')),
                    )),
                    '_serialized' => new Type\Text(array(
                        'visible' => false,
                    )),
                    'captcha' => new Type\Captcha(array(
                        'enable' => false
                    ))
        ));
    }
    
    /**
    * Проверяет пароль на соответствие требованиям безопасности
    * 
    * @param User $_this - проверяемый ORM - объект
    * @param mixed $value - проверяемое значение
    * @param string $error - текст ошибки
    * @return bool(true) | string возвращает true в случае успеха, иначе текст ошибки
    */
    public static function checkOpenPassword($_this, $value, $error)
    {
        if ($_this['changepass']) {
            if (mb_strlen($value) < self::PASSWORD_LEN) {
                return t('Пароль должен содержать не менее %len знаков', array('len' => self::PASSWORD_LEN));
            }
        }
        return true;
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $flag - insert или update
    */
    function beforeWrite($flag)
    {
        if ($this['id']<0) {
           $this['_tmpid'] = $this['id']; 
           unset($this['id']);
        }
        
        $ret = true;
    	//Создаем новый произвольный ключ при каждом сохранении
        $this->updateHash(false);
        $this['_serialized'] = serialize($this['data']);
        
        if ($flag == self::INSERT_FLAG) {
            $this['dateofreg'] = date('Y-m-d H:i:s');
            $this['registration_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        
        if (!$this->isModified('login')) {
            $this['login'] = $this['e_mail']; //Если логин не задан, то устанавливаем его таким же как email
        }
        
        if (!empty($this['openpass'])) {
            $this['pass'] = self::cryptPass($this['openpass']);
        }
        
        if ($this['ban_expire'] == '') {
            $this['ban_expire'] = null;
        }
        
        //Проверяем, не занят ли E-mail
        $q = \RS\Orm\Request::make()->from($this)
            ->where(array(
                'e_mail' => $this['e_mail']
            ));
        if ($flag == self::UPDATE_FLAG) {
            $q->where("id != '#this_id'", array( 'this_id' => $this['id'] ));
        }
        
        if ($q->count()) {
            $this->addError(t('такой E-mail уже занят'), 'e_mail');
            $ret = false;
        }
        
        //Сохраняем дополнительные сведения о пользователе
        $conf_userfields = $this->getUserFieldsManager();
        $uf_err = $conf_userfields->check($this['data']);
        if (!$uf_err && !$this['no_validate_userfields']) {
            foreach($conf_userfields->getErrors() as $form=>$errortext) {
                $this->addError($errortext, $form);
            }
            return false;
        }

        return $ret;
	}
    
    /**
    * Действия после записи объекта
    * 
    * @param string $flag - insert или update
    */
    function afterWrite($flag)
    {
        if ($flag == self::INSERT_FLAG && \Setup::$INSTALLED && !$this['no_send_notice']) {
            
            // Уведомление пользователю
            $notice = new \Users\Model\Notice\UserRegisterUser;
            $notice->init($this, $this['openpass']);
            \Alerts\Model\Manager::send($notice); 
            
            $site_config = \RS\Config\Loader::getSiteConfig();
            if ($site_config['admin_email']) {
                // Уведомление администратору
                $notice = new \Users\Model\Notice\UserRegisterAdmin;
                $notice->init($this, $this['openpass']);
                \Alerts\Model\Manager::send($notice); 
            }
        }
        
        if ($this->isModified('groups')) {
            $this->linkGroup($this['groups']);
        }
        
    }
    
    /**
    * После того как подгружен объект
    * 
    */
    function afterObjectLoad()
    {
        $this['data'] = (array)@unserialize($this['_serialized']);
    }
    

    /**
    * Возвращает группы, в которых состоит пользователь
    * В ReadyScript предусмотрены системные группы пользователей, к которым 
    * пользователь автоматически причисляется при следующих условиях:
    * Гость  - присваивается всем пользователям, находящимся в клиентской части
    * Клиент - присваивается всем авторизованным пользователям, находящимся в клиентской части
    * 
    * @param boolean $returnAliases - Если true, то возвращает массив с alias'ами групп, иначе array
    * @return array
    */
    function getUserGroups($returnAliases = true)
    {        
        if (!isset($this->groups)) //Не запрашиваем дважды группы у юзера
        {
            $this->groups = array();
            $is_admin_zone = \RS\Router\Manager::obj()->isAdminZone();
            
            if (!$is_admin_zone) {
                $this->groups[$this->default_group] = new \Users\Model\Orm\UserGroup($this->default_group);
            }
            if ($this['id']>0)
            {
                if (!$is_admin_zone) {
                    $this->groups[$this->authorized_user_group] = new \Users\Model\Orm\UserGroup($this->authorized_user_group);                
                }
                $this->groups += \RS\Orm\Request::make()->select('G.*')
                          ->from(new \Users\Model\Orm\UserGroup())->asAlias('G')
                          ->from(new \Users\Model\Orm\UserInGroup())->asAlias('I')
                          ->where("I.group = G.alias AND I.user='#user'", array('user' => $this['id']))
                          ->objects('\Users\Model\Orm\UserGroup', 'alias');
                
            }
        
            if ($is_admin_zone) {
                unset($this->groups[$this->default_group]);
                unset($this->groups[$this->authorized_user_group]);
            }            
        }

        return $returnAliases ? array_keys($this->groups) : $this->groups;
    }
    
    /**
    * Проверяет состоит ли пользователь в группе
    * 
    * @return bool
    */
    function inGroup($groupAlias)
    {
        $groups = $this->getUserGroups(false);
        return isset($groups[$groupAlias]);
    }
    
    /**
    * Возвращает True, если пользователь состоит в группе с отметкой "Администратор" 
    * Пользователи такой группы могут входить в административную панель.
    * 
    * @return bool
    */
    function isAdmin()
    {
        $groups = $this->getUserGroups(false);
        $is_admin = false;
        foreach($groups as $group) {
            $is_admin = $is_admin || $group['is_admin'];
        }
        return $is_admin;
    }
    
    /**
    * Помещает пользователя в группу
    * 
    * @param array $groups - массив групп в которые нужно добавить 
    * @return void
    */
    function linkGroup(array $groups)
    {
        $uig = new \Users\Model\Orm\UserInGroup();
        $uig->linkUserToGroup($this['id'], $groups);
    }
    
    /**
    * Добавляет группу к уже существующим и пользователя группам
    * 
    * @param string $groupid - алиас группы
    * @return void
    */
    function addGroup($groupid){
       $q           = new \RS\Orm\Request();
       $user_groups = $q->from(new \Users\Model\Orm\UserInGroup())
                        ->where(array(
                           'user'=>$this['id']
                        ))
                        ->objects();
       if ($user_groups){ //Если состоит в группах
           foreach ($user_groups as $group){
               if ($groupid == $group['group']){ //Если такая группа уже существует
                   return;
               }
           }
       }
       
       $user_group          = new \Users\Model\Orm\UserInGroup();
       $user_group['user']  = $this['id'];
       $user_group['group'] = $groupid;
       $user_group->insert();
       
    }
    
    /**
    * Удаляет пользователя из группы $group_id или из всех групп
    * 
    * @param $group_id - ID группы
    * @return void
    */
    function unlinkGroup($group_id = null)
    {
        $q = new \RS\Orm\Request();
        $q->delete()
            ->from(new \Users\Model\Orm\UserInGroup())
            ->where(array('user' => $this['id'])
                    + ($group_id ? array('group' => $group_id) : array())
            )->exec();
    }
    
    /**
    * Возвращает права доступа ко всем модулям с учетом группы, 
    * к которой принадлежит пользователь. 
    * 
    * @return array
    */
    function getModuleAccess()
    {
        if (isset($this->cache_mod_access)) return $this->cache_mod_access;
        
        $groups = $this->getUserGroups();                    
        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            $mod_right[AccessModule::FULL_MODULE_ACCESS] =  AccessModule::MAX_ACCESS_RIGHTS;
        } else {
            $q = \RS\Orm\Request::make()
                ->select('module, BIT_OR(access) as access')
                ->from($this->access_module_table)
                ->where(empty($this['id']) ? null : "user_id={$this['id']}")                
                ->groupby('module');
            
            if ($groups) {
                $q->whereIn('group_alias', $groups, "OR");
            }
                
            $mod_right = $q->exec()->fetchSelected('module', 'access');
        }
        
        $this->cache_mod_access = $mod_right;
        return $this->cache_mod_access;
    }
    
    /**
    * Возвращет права оступа к конкретному модулю, с учетом группы к кторой принадлежит пользователь
    * 
    * @param string $module - название модуля
    * @return int 0 - нет прав, 255 - полный права
    */
    function getRight($module)
    {
        $module = strtolower($module);
        $mod_access = $this->getModuleAccess();
        if (isset($mod_access['all'])) return $mod_access['all'];
        return isset($mod_access[$module]) ? $mod_access[$module] : 0;
    }
    
    /**
    * Проверяет, включен ли нужный бит в правах доступа к модулю.
    * 
    * @param integer $bit - от 0 до 7.
    * @return bool;
    */
    function checkModuleRight($module, $bit)
    {
        $rights = $this->getRight($module);
        return ((1 << $bit) & $rights) >0;
    }
    
    /**
    * Возвращает права доступа к пунктам меню пользовательской части с учетом группы, 
    * к которой принадлежит пользователь
    * 
    * @return array
    */
    function getMenuAccess()
    {
        if (isset($this->cache_menu_access)) return $this->cache_menu_access;
        $groups = $this->getUserGroups();

        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            $menu_access =  array(
                AccessMenu::FULL_USER_ACCESS
            );
        } else {
            $q = \RS\Orm\Request::make()
            ->select('menu_id')
            ->from($this->access_menu_table)
            ->where(array('menu_type' => 'user'))
            ->openWGroup()
                ->where(empty($this['id']) ? null : "user_id={$this['id']}");
            
            if ($groups) {
                $q->whereIn('group_alias', $groups, "OR");
            }
            
            $q->closeWGroup()
            ->groupby('menu_id');
            
            $menu_access = $q->exec()->fetchSelected(null, 'menu_id');
        }
            
        $this->cache_menu_access = $menu_access;
        
        return $this->cache_menu_access;
    }
    
    /**
    * Возвращает права доступа к пунктам меню административной панели с учетом группы, 
    * к которой принадлежит пользователь
    * 
    * @return array
    */    
    function getAdminMenuAccess()
    {
        if (isset($this->cache_admin_menu_access)) return $this->cache_admin_menu_access;
        $groups = $this->getUserGroups();

        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            $menu_access =  array(
                AccessMenu::FULL_ADMIN_ACCESS
            );
        } else {
            $q = \RS\Orm\Request::make()
            ->select('menu_id')
            ->from($this->access_menu_table)
            ->where(array('menu_type' => 'admin'))
            ->openWGroup()
                ->where(empty($this['id']) ? null : "user_id={$this['id']}");
            if ($groups) {
                $q->whereIn('group_alias', $groups, "OR");
            }
            $q->closeWGroup()
            ->groupby('menu_id');
            
            $menu_access = $q->exec()
            ->fetchSelected(null, 'menu_id');
        }
            
        $this->cache_admin_menu_access = $menu_access;
        
        return $this->cache_admin_menu_access;        
    }
    
    /**
    * Удаляет пользователя
    * 
    * @return bool
    */
    function delete()
    {
        if ($ret = parent::delete()) {
            $this->unlinkGroup();
        }
        return $ret;
    }
    
    /**
    * Возвращает хэш от пароля.
    * 
    * @param string $password - пароль в открытом виде
    * @return string
    */
    public static function cryptPass($password)
    {
        //Разрешаем задать другой способ расчета хеша в классе конфигурации
        if (is_callable(array('\Setup', 'cryptPassword'))) {
            return \Setup::cryptPassword($password);
        }
        return md5($password.sha1(\Setup::$SECRET_SALT));
    }
    
    /**
    * Вернет true, если пользователь будет создан, 
    * В случае ошибки - false. Вызовите $this->getLastError(), чтобы увидеть текст ошибки
    * Объект должен быть заполнен даными перед вызовом данного метода
    */
    function create()
    {
        if (!$this->checkCreateData()) return false;
        return $this->insert();
    }
    
    
    /**
    * Возвращает клонированный объект пользователя
    * @return User
    */
    function cloneSelf()
    {
        $this['usergroup'] = $this->getUserGroups(); //заполним группы 
        /**
        * @var \Users\Model\Orm\User
        */
        $clone = parent::cloneSelf();
        $clone->setTemporaryId();
        
        unset($clone['e_mail']);
        unset($clone['login']);
        unset($clone['openpass']);
        unset($clone['pass']);
        
        return $clone;
    }
    
    /**
    * Проверяет, можно ли создать пользователя с текущими данными.
    * (проверяется уникальность логина, уникальность e-mail'а)
    */
    function checkCreateData()
    {
        $q = new \RS\Orm\Request();
        $res = $q->select('*')->from($this)
            ->where( array(
                'login' => $this['login'],
                'e_mail' => $this['e_mail']
            ), null, 'OR')
            ->exec();
        
        if ($res->rowCount()) {
            $user_row = $res->fetchRow();
            
            if (!empty($this['login']) && stristr($user_row['login'], $this['login']) !== false)
                $this->addError(t('Пользователь с таким логином уже существует'), 'login');
                
            if (!empty($this['e_mail']) && stristr($user_row['e_mail'], $this['e_mail']) !== false)
                $this->addError(t('Пользователь с таким E-mail`ом уже существует'), 'e_mail');
            
            return false;
        }
        return true;
    }
    
    /**
    * Возвращает массив с данными пользовательских полей
    * 
    * @return array
    */
    function getUserFields()
    {
        $struct = $this->getUserFieldsManager()->getStructure();
        
        foreach($struct as &$field) {
            $field['current_val'] = isset($this['data'][$field['alias']]) ? $this['data'][$field['alias']] : $field['val'];
        }
        
        return $struct;
    }
    
    /**
    * Возвращает объект - менеджер произвольных полей
    * @return \RS\Config\UserFieldsManager
    */
    function getUserFieldsManager()
    {
        return \RS\Config\Loader::byModule($this)->getUserFieldsManager()
                ->setErrorPrefix('userfield_')
                ->setArrayWrapper('data')
                ->setValues((array)$this['data']);
    }
    
    /**
    * Возвращает Фамилию, имя, отчество в одну строку
    * 
    * @return string
    */
    function getFio()
    {
        return trim($this['surname'].' '.$this['name'].' '.$this['midname']);
    }
    
    /**
    * Обновляет секретный хэш пользователя. Хэш используется для восстановления пароля
    * 
    * @param bool $commit - если true, то изменения будут сразу сохранены в базе.
    * @return void
    */
    function updateHash($commit = true)
    {
        $newhash =  md5(uniqid(mt_rand(), true)).md5(uniqid(mt_rand(), true));
        $this['hash'] = $newhash;
        if ($commit) {
            $q = new \RS\Orm\Request();
            $q->update($this)->set(array(
                'hash' => $this['hash']
            ))->where("id='{$this['id']}'")
            ->exec();
        }
    }
    
    /**
    * Возвращает true, если пароль соответствует требованиям, иначе текст ошибки
    * 
    * @return bool(true) | string
    */
    public static function checkPassword($password)
    {
        if (mb_strlen($password) < self::PASSWORD_LEN) {
            return t('Пароль должен содержать не менее %len знаков', array('len' => self::PASSWORD_LEN));
        }
        return true;
    }
    
    /**
    * Возвращает true, если пользователь является супервизором
    * 
    * @return bool
    */
    public function isSupervisor()
    {
        return $this->inGroup(self::SUPERVISOR_GROUP);
    }
    
    /**
    * Возвращает true, если у пользователя есть права на данный сайт
    * @param integer $site_id - ID сайта
    * 
    * @return bool
    */
    public function checkSiteRights($site_id)
    {
        $allow_sites = $this->getAllowSites();
        return in_array($site_id, array_keys($allow_sites));
    }
    
    /**
    * Возвращает список сайтов, доступных пользователю
    * 
    * @return array of \Site\Model\Orm\Site
    */
    public function getAllowSites()
    {
        if (!isset($this->cache_allow_sites)) {
            $groups = $this->getUserGroups();

            if ($this->isSupervisor()) {
                $q = \RS\Orm\Request::make()
                    ->from(new \Site\Model\Orm\Site());
            } else {
                $q = \RS\Orm\Request::make()
                    ->select('S.*')
                    ->from(new \Users\Model\Orm\AccessSite())->asAlias('A')
                    ->join(new \Site\Model\Orm\Site(),'A.site_id = S.id', 'S')
                    ->where(empty($this['id']) ? null : "user_id={$this['id']}");
                    if ($groups) {
                        $q->whereIn('group_alias', $groups, "OR");                
                    }
            }
                
            $this->cache_allow_sites = $q->objects('\Site\Model\Orm\Site', 'id');
        }
        return $this->cache_allow_sites;
    }
    
    /**
    * Возвращает текущий остаток на лицевом счете пользователя
    * 
    * @param bool $use_currency - если true, то значение будет возвращено в текущей валюте, иначе в базовой
    * @param bool $format - если true, то форматировать возвращаемое значение, приписывать символ валюты
    * @return string
    */
    public function getBalance($use_currency = false, $format = false)
    {
        if($this->checkBalanceSign()){
            $balance = ($use_currency) ? \Catalog\Model\CurrencyApi::applyCurrency($this['balance']) : $this['balance'];
        } else {
            $balance = 0;
        }
        return $format ? \RS\Helper\CustomView::cost($balance, \Catalog\Model\CurrencyApi::getCurrecyLiter()) : $balance;        
    }
    
    /**
    * Возвращает true, если подпись к балансу является корректной
    * 
    * @return bool
    */
    public function checkBalanceSign()
    {
        if($this->balance == 0) return true;
        $transApi = new \Shop\Model\TransactionApi();
        $balance_sign = $transApi->getBalanceSign($this->balance, $this->id);
        return $balance_sign == $this->balance_sign;
    }
    
    /**
    * Возвращает идентификатор группы пользователей, которая присваивается обязательно всем 
    * пользователям в клиентской части сайта.
    * 
    * @return string
    */
    public function getDefaultGroup()
    {
        return $this->default_group;
    }
    
    /**
    * Возвращает идентификатор группы пользователей, которая присваивается всем 
    * авторизованным пользователям в клиентской части сайта.
    * 
    * @return string
    */
    public function getAuthorizedGroup()
    {
        return $this->authorized_user_group;
    }
    
    /**
    * Обновляет дату последнего посещения в БД не чаще, чем 1 раз в 4 часа
    * 
    * @return bool
    */
    public function saveVisitDate()
    {
        $delay = 60*60*4;
        $datetime = time();
        
        if ($this['id']>0 
            && (!isset($_SESSION[self::SESSION_LAST_VISIT_VAR]) 
            || $datetime - $_SESSION[self::SESSION_LAST_VISIT_VAR] > $delay)) 
        {
            $_SESSION[self::SESSION_LAST_VISIT_VAR] = $datetime;
            $this['last_visit'] = date('Y-m-d H:i:s', $datetime);
            $this['last_ip'] = $_SERVER['REMOTE_ADDR'];

            \RS\Orm\Request::make()
                ->update($this)
                ->set(array(
                    'last_visit' => $this['last_visit'],
                    'last_ip' => $this['last_ip']
                ))->where(array(
                    'id' => $this['id']
                ))
                ->exec();
        }
    }
}