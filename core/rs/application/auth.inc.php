<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Application;
use \Users\Model\Orm\User;

/**
* Класс, содржит методы - авторизовать, 
* получить текущего пользователя, сбросить авторизацию, перебросить на авторизацию.
*/
class Auth
{
    const
        COOKIE_AUTH_TICKET = 'user_ticket',
        COOKIE_GUEST_ID = 'guest',
        COOKIE_AUTH_TICKET_LIFETIME = 63072000; //Время жизни cookie с тикетом авторизации в секундах.
    
    protected static $session_var = 'user_id';
    protected static $user;
    protected static $error = '';
    protected static $try_ticket = true;

    /**
    * Инициализирует класс авторизации
    * @return void
    */
    public static function staticInit()
    {
        //Пробуем найти Auth Ticket
        $auth_ticket = \RS\Http\Request::commonInstance()
                            ->cookie(self::COOKIE_AUTH_TICKET, TYPE_STRING);
        if (!empty($auth_ticket) && \Setup::$INSTALLED) {
            self::loginByCookie($auth_ticket);
        }
    }
        
    /**
    * Возвращает true, в случае если у пользователя хватает запрошенных прав, иначе - текст ошибки, если пользователь неавторизован - то пустая строка
    * 
    * @param mixed $needGroup - alias требуемой у пользователя группы
    * @param bool $needAdmin - требуется наличие группы с пометкой "Администратор"
    * @return boolean (true) | string
    */
    public static function checkUserRight($needGroup = null, $needAdmin = false)
    {
        $verdict = true;
        $errMess = '';
        if (self::isAuthorize()) {
            if ($needGroup !== null && !self::getCurrentUser()->inGroup($needGroup)) {
                $verdict = false;
            }
            if ($needAdmin && !self::getCurrentUser()->isAdmin()) {
                $verdict = false;
            }
            
            if ($verdict) {
                return true;
            } else {
                $errMess = t('Недостаточно прав для доступа в этот раздел');
            }
        }
        return $errMess;
    }
    
    /**
    * Авторизовывает пользователя по логину и паролю.
    * 
    * @param string $login - логин
    * @param string $pass - пароль
    * @param boolean $remember - если true, значит будет задействован функция "запомнить меня"
    * @param bool $pass_encrypted - если true, значит аргументом $pass передан hash пароля, иначе ожидается пароль в открытом виде
    * @param bool $no_set_current_user - если установлено true, то метод просто возвращает true или false, при этом не устанавливает пользователя в сессию
    * @return bool - Возвращает true если авторизация пршла успешно
    */
    public static function login($login, $pass, $remember = false, $pass_encrypted = false, $no_set_current_user = false)
    {
        //Защита от подбора паролей
        $ip = \RS\http\Request::commonInstance()->server('REMOTE_ADDR', TYPE_STRING);
        $try = new \Users\Model\Orm\TryAuth($ip);
        $try->load($ip);
        
        if ($try['total'] >= \Setup::$AUTH_TRY_COUNT && time()-strtotime($try['last_try_dateof']) < \Setup::$AUTH_BAN_SECONDS ) {
            self::$error = t('Превышено число попыток авторизации');
            return false;
        } else {
            $try['ip'] = $ip;
            $try['total'] = $try['total'] + 1;
            $try['last_try_dateof'] = date('Y-m-d H:i:s');
            $try['try_login'] = $login;
            $try->replace();
        }
        //Конец защиты от подбора паролей
        
        if (!$pass_encrypted) {
            $pass = User::cryptPass($pass);
        }
        $user = \RS\Orm\Request::make()
                    ->select('*')
                    ->from(new User())
                    ->where(array(
                        'login' => $login,
                    ))
                    ->object();
        
        if ($user && $user['pass'] === $pass) {
            if (self::isUserBanned($user)) {
                self::$error = t('Заблокирован до %0. ', array(date('d.m.Y', strtotime($user['ban_expire'])))).$user['ban_reason'];
                return false;
            } else {
                $try->delete(); //Обнуляем счетчик попыток авторизаций
                if ($user->isAdmin()) {
                    //Если авторизовался администратор, то пишем в лог
                    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                    \Users\Model\LogApi::appendUserLog(new \Users\Model\LogType\AdminAuth(), $user['id'], array('ip' => $ip));
                }
                
                if (!$no_set_current_user) {
                    self::setCurrentUser($user);
                    
                    if ($remember) {
                        \RS\Application\Application::getInstance()->headers->addCookie(self::COOKIE_AUTH_TICKET, self::getAuthTicket($user), time()+self::COOKIE_AUTH_TICKET_LIFETIME, '/');
                    } else {
                        \RS\Application\Application::getInstance()->headers->addCookie(self::COOKIE_AUTH_TICKET, null, -1, '/');
                    }
                    
                    //Генерируем событие успешной авторизации
                    $event_result = \RS\Event\Manager::fire('user.auth', array(
                        'user' => $user
                    ));
                    
                    if ($event_result->getEvent()->isStopped()) {
                        self::$error = implode(', ', $event_result->getEvent()->getErrors());
                        return false;
                    }
                }
                
                return $no_set_current_user ? $user : true;
            }
        } else {
            self::$error = t('Неверный e-mail или пароль');
            return false;
        }
    }
    
    /**
    * Авторизовывает пользователя по Идентификатору в cookie
    * 
    * @param string $auth_ticket - идентификатор, установленны в cookie
    * @return boolean
    */
    public static function loginByCookie($auth_ticket)
    {
        if (!empty($auth_ticket)) {
            @list($id, $uniq) = explode('-', $auth_ticket);
            $id = (int)$id - self::getSecretNumber();
            $user = \RS\Orm\Request::make()
                ->from(new User)
                ->where(array('id' => $id))
                ->object();
                
            if ($user && !self::isUserBanned($user)) {
                if (\Users\Model\Api::getUserUniq($user, \Setup::$SECRET_KEY.'auth') === $uniq) { //Ticket корректный
                    self::setCurrentUser($user);
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
    * Возвращает уникальный идентификатор пользователя, по которому тот сможет авторизоваться
    * 
    * @param \Users\Model\Orm\User $user
    * @return string
    */
    public static function getAuthTicket(User $user)
    {
        return ($user['id'] + self::getSecretNumber()).'-'.\Users\Model\Api::getUserUniq($user, \Setup::$SECRET_KEY.'auth');
    }
    
    /**
    * Возвращает цифру, основанную на секретном ключе, который задан в настройках
    * 
    * @return integer
    */
    protected static function getSecretNumber()
    {
        $sum = 0;
        for($i=0; $i<strlen(\Setup::$SECRET_KEY); $i++ ) {
            $sum += ord(\Setup::$SECRET_KEY[$i]);
        }
        return $sum;
    }

    /**
    * Возвращает причину, по которой не удалось авторизоваться или восстановить пароль
    * @return string
    */
    public static function getError()
    {
        return self::$error;
    }
    
    /**
    * Отменяет авторизацию.
    * @return void
    */
    public static function logout()
    {
        //Генерируем событие выхода пользователя
        \RS\Event\Manager::fire('user.logout', array(
            'user' => self::getCurrentUser()
        ));
        
        unset($_SESSION[self::$session_var]);
        \RS\Application\Application::getInstance()->headers->addCookie(self::COOKIE_AUTH_TICKET, null, -1, '/');
    }
    
    /**
    * Возвращает true, если пользователь авторизован, иначе false
    * @return boolean
    */
    public static function isAuthorize()
    {
        return isset($_SESSION[self::$session_var]);
    }
    
    /**
    * Возвращает объект текущего пользователя
    * @return \Users\Model\Orm\User
    */
    public static function getCurrentUser()
    {
        static $guest_hash;
                
        if (isset(self::$user)) {
            return self::$user;
        }
        
        if ($guest_hash === null) {
            $guest_hash = \RS\Http\Request::commonInstance()->cookie('guest', TYPE_STRING, false);
            if ($guest_hash === false) {
                $guest_hash = self::generateGuestId();
                //2 года помним неавторизованного пользователя
                \RS\Application\Application::getInstance()->headers->addCookie(self::COOKIE_GUEST_ID, $guest_hash, time()+60*60*24*730, '/');
            }
        }
        
        //Если пользователь авторизован, то возвращаем его объект
        if (isset($_SESSION[self::$session_var])) {
            $user = new User($_SESSION[self::$session_var]);
            if (!self::isUserBanned($user)) {
                return self::$user = $user;
            }
        }

        //Если пользователь не авторизован, то присваиваем ему отрицательный id
        $user = new User();        
        $guest_id = -abs(crc32($guest_hash.\Setup::$SECRET_KEY));
        $user['id'] = $guest_id;
        
        //Если сессия не установлена, то не кэшируем результат
        if (session_id() != '') {
            self::$user = $user;
        }
 
        return $user;
    }

    /**
     * Возвращает ID гостя (или ID браузера).
     * Этот ID будет оставаться неизменным у пользователя, если он пользуется одним и тем же браузером.
     *
     * @return string
     */
    public static function getGuestId()
    {
        $guest_id = \RS\Http\Request::commonInstance()->cookie('guest', TYPE_STRING, false);
        if (!$guest_id) {
            $guest_id = self::generateGuestId();
        }
        return $guest_id;
    }

    /**
     * Генерирует уникальный ID браузера один раз за PHP сессию.
     * Этот ID будет сохраняться у пользователя на прояжении 2х лет.
     *
     * @return string
     */
    public static function generateGuestId()
    {
        static $uniq_id;

        if (!$uniq_id) {
            $uniq_id = md5(uniqid());
        }
        return $uniq_id;
    }
    
    /**
    * Обновляет текущего пользователя в сессии.
    * 
    * @param \Users\Model\Orm\User $user
    * @return void
    */
    public static function setCurrentUser(User $user)
    {
        $_SESSION[self::$session_var] = $user->id;
        self::$user = $user;
    }   
    
    /**
    * Возвращает true, если пользователь заблокирован
    * Деавторизует пользователя, если он заблокирован
    * 
    * @param \Users\Model\Orm\User $user
    * @return boolean
    */
    private static function isUserBanned(User $user)
    {
        $is_banned = $user['ban_expire'] !== null && strtotime($user['ban_expire']) > time();
        $session_user_id = isset($_SESSION[self::$session_var]) ? $_SESSION[self::$session_var] : null;
        if ($is_banned && $session_user_id == $user['id']) {
            unset($_SESSION[self::$session_var]);
        }
        return $is_banned;     
    }
    
}

Auth::staticInit();