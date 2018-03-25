<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Db;
use \RS\Helper\Log,
    \RS\Cache\Manager as CacheManager;

/**
* Класс работы с базой данных
*/
class Adapter
{
    protected static 
        /**
        * Указатель на соединение с базой
        */
        $connection,
        /**
        * Инстанс логирования
        * 
        * @var Log
        */
        $log;
    
    public static function init()
    {
        if (\Setup::$LOG_SQLQUERY_TIME) {
            $str = "\n-------- Page: ".@$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI']." \n";
            self::$log = Log::file(\Setup::$PATH.\Setup::$LOG_SQLQUERY_FILE)->append($str);
        }
        
        if (self::connect()) {
            //Устанавливаем часовой пояс для Mysql 
            $time = new \DateTime('now', new \DateTimeZone(\Setup::$TIMEZONE));
            self::sqlExec("SET time_zone = '#time_zone'", array(
                'time_zone' => $time->format('P')
            ));
            
            //Устанавливаем не строгий режим MYSQL
            self::sqlExec("SET sql_mode = ''");
        }
    }
    
    /**
    * Открывает соединение с базой данных
    */
    public static function connect()
    {
        self::$connection = @mysqli_connect(\Setup::$DB_HOST,
            \Setup::$DB_USER,
            \Setup::$DB_PASS,
            \Setup::$DB_NAME,
            (int)\Setup::$DB_PORT,
            \Setup::$DB_SOCKET);

        if(self::$connection){
            self::sqlExec("SET names utf8");
        }

        return self::$connection;
    }
    
    /**
    * Закрывает соединение с базой данных
    */
    public static function disconnect()
    {
        return mysqli_close(self::$connection);        
    }
        
    /**
    * Выполняет sql запрос
    * 
    * @param mixed $sql - SQL запрос.
    * @param array | null $values - массив со знчениями для замены.
    * @return Result
    */
    public static function sqlExec($sql, $values = null)
    {
        if(!self::$connection){
            if (\Setup::$INSTALLED) {
                throw new Exception(t('Не установлено соединение с базой данных'));
            }
            return new Result(false);
        }

        if ($values !== null) {
            foreach($values as $key => $val) {
                $sql = str_replace("#$key",  self::escape($val) , $sql);
            }
        }
        
        $start_time = microtime(true);
        $resource = mysqli_query(self::$connection, $sql);
        
        if (\Setup::$LOG_SQLQUERY_TIME) {
            $str = (microtime(true)-$start_time)." - {$sql}";
            self::$log->append($str);
        }
        
        // Блок необходим для определения неактуальности кэша
        if (\Setup::$CACHE_ENABLED && \Setup::$CACHE_USE_WATCHING_TABLE) { //Если кэш включен, то пишем информацию о модификации таблиц
            self::invalidateTables($sql);
        }

        $error_no = mysqli_errno(self::$connection);
        if ($error_no != 0){
            //Не бросаем исключения об ошибках - неверено указана БД, не существует таблицы, не выбрана база данных, когда включен режим DB_INSTALL_MODE
            if ( !\Setup::$DB_INSTALL_MODE || !($error_no == 1102 || $error_no == 1146 || $error_no == 1046 || $error_no == 1142) ) {
                throw new Exception($sql.mysqli_error(self::$connection), $error_no);
            }
        }
        return new Result($resource);
    }
    
    /**
    * Парсит sql запрос и делает отметку о изменении таблиц, присутствующих в запросе
    * @param string $sql
    */
    private static function invalidateTables($sql)
    {
        //Находим имя таблицы и базы данных у SQL запроса
        $sql = preg_replace(array('/\s\s+/','/\n/'), ' ', $sql); //Удаляем лишние пробелы и переносы строк
        
        if (preg_match('/^(INSERT|UPDATE|DELETE)/ui', $sql)) {
            if (preg_match('/^INSERT.*?INTO([a-zA-Z0-9\-_`,\.\s]*?)(\(|VALUE|SET|SELECT)/ui', $sql, $match)) {
                $tables_str = $match[1];
            }
            if (preg_match('/^UPDATE (LOW_PRIORITY|IGNORE|\s)*([a-zA-Z0-9\-_`,\.]*).*?SET/ui', $sql, $match)) {
                $tables_str = $match[2];
            }
            if (preg_match('/^DELETE(LOW_PRIORITY|QUICK|IGNORE|\s)*(.*?)FROM(.*?)((WHERE|ORDER\sBY|LIMIT).*)?$/ui', $sql, $match)) {
                $tables_str = $match[3];
            }
            
            //Исключительно на время разработки
            if (\Setup::$LOG_SQLQUERY_TIME && !isset($tables_str)) {
                Log::file(\Setup::$PATH.'/logs/parse_query.txt')->append($sql);
            }
        
            $tables_arr = preg_split('/[,]+/', $tables_str, -1, PREG_SPLIT_NO_EMPTY);
            foreach($tables_arr as $table) {
                //Очищаем имя базы данных и имя таблицы
                if (preg_match('/(?:(.*?)\.)?(.*?)( (as)?.*?)?$/ui', $table, $match)) {
                    $db = trim($match[1],"` ");
                    $table = trim($match[2],"` ");
                    CacheManager::obj()->tableIsChanged($table, $db);
                }
            }
        }
    }
    
    /**
    * Экранирует sql запрос.
    * 
    * @param string $str SQL запрос
    * @return string экранированный запрос
    */
    public static function escape($str)
    {
        if(!self::$connection) {
            return $str;
        }
        
        return mysqli_real_escape_string(self::$connection, $str);
    }
    
    /**
    * Возвращает значение инкремента для последнего INSERT запроса
    * 
    * @return integer | bool(false)
    */
    public static function lastInsertId()
    {
        return mysqli_insert_id(self::$connection);
    }    

    /**
    * Возвращает число затронутых в результате запросов строк
    * 
    * @return integer
    */    
    public static function affectedRows()
    {
        return mysqli_affected_rows(self::$connection);
    }    
    
    /**
    * Возвращает версию Mysql сервера
    * 
    * @return string | bool(false)
    */
    public static function getVersion()
    {
        return mysqli_get_server_info(self::$connection);
    }
    
}

if (\Setup::$DB_AUTOINIT) {
    Adapter::init();
}