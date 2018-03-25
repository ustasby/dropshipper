<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Model;

class LangApi extends \RS\Module\AbstractModel\BaseModel
{
    const
        FILTER_CORE = '@core';
    
    /**
    * Метод, создающий файлы локализации по всему проекту
    * 
    * @param string $lang - язык для которого подготовить файлы
    * @param string $filter - фильтрует модули, для которых нужно создать языковый файл.
    * Может содержать "имя папки модуля" или "@core" или "#имя шаблона"
    */
    static public function createLangFiles($lang, $filter = null)
    {
        // Создаем файлы локализациия для всех модулей
        foreach(glob(\Setup::$PATH.'/modules/*', GLOB_ONLYDIR) as $one){
            if (!$filter || $filter == basename($one)) {
                self::createLangFilesForDirectory($one, $one . '/view/lang/' . $lang . '/messages.lng.php', $one . '/view/lang/' . $lang . '/messages.js.php');
            }
        }

        // Создаем файлы локализациия для всех тем оформления
        foreach(glob(\Setup::$PATH.'/templates/*', GLOB_ONLYDIR) as $one){
            if (!$filter || $filter == '#'.basename($one)) {
                self::createLangFilesForDirectory($one, $one . '/resource/lang/' . $lang . '/messages.lng.php', $one . '/resource/lang/' . $lang . '/messages.js.php');
            }
        }

        if (!$filter || $filter == self::FILTER_CORE) {
            $sys_lang_dir = \Setup::$PATH . '/resource/lang';
            // Создаем файлы локализации для кода, находящегося в папке [core]
            self::createLangFilesForDirectory(\Setup::$PATH . '/core', $sys_lang_dir . '/' . $lang . '/messages.lng.php', $sys_lang_dir . '/' . $lang . '/messages.js.php');
            // Создаем файлы локализации для кода, находящегося в папке [resources]
            self::createLangFilesForDirectory(\Setup::$PATH . '/resource', $sys_lang_dir . '/' . $lang . '/messages.lng.php', $sys_lang_dir . '/' . $lang . '/messages.js.php');
            // Создаем файлы локализации для кода, находящегося в папке [templates/system]
            self::createLangFilesForDirectory(\Setup::$PATH . '/templates/system', $sys_lang_dir . '/' . $lang . '/messages.lng.php', $sys_lang_dir . '/' . $lang . '/messages.js.php');
        }
    }

    /**
     * Создает языковые файлы для определенной директории
     *
     * @param string $directory_path - путь к папке
     * @param string $php_strings_output_file - путь к файлу php для хранить языковые фразы
     * @param string $js_strings_output_file - путь к файлу js для хранить языковые фразы
     */
    static public function createLangFilesForDirectory($directory_path, $php_strings_output_file, $js_strings_output_file)
    {
        // Поиск функции t()
        //$php_strings = \Main\Model\LangApi::getStringsFromDirByTokenizer($directory_path, array('php'));

        // Регулярное выражение для поиска tpl тэга {t} {/t}
        $patterns = array(
            '/\{t(?:\s+?.*?)?}([^{}]+?)\{\/t\}/s',
        );
        
        $tpl_strings = \Main\Model\LangApi::getStringsFromDir($directory_path, array('tpl'), $patterns);
        //$php_strings = array_merge($php_strings, $tpl_strings);
        $php_strings = $tpl_strings;

        // Регулярное выражение для поиска JS функции lang.t()
        $patterns = array(
            '/\Wlang\.t\s*?\(\s*?\'(.*?)\'\s*?[,\)]/s',
            '/\Wlang\.t\s*?\(\s*?\"(.*?)\"\s*?[,\)]/s',
        );
        $js_strings = \Main\Model\LangApi::getStringsFromDir($directory_path, array('js', 'tpl'), $patterns);

        \RS\File\Tools::makePath($php_strings_output_file, true);
        \RS\File\Tools::makePath($js_strings_output_file, true);
        array_unique($php_strings);
        array_unique($js_strings);
        
        // Сохраняем старые значения переведенных строк, котрые уже были сделаны в файле перевода
        if(file_exists($php_strings_output_file)){
            $old_strings = include($php_strings_output_file);
            if(is_array($old_strings)){
                $php_strings = array_merge($php_strings, $old_strings);
            }
        }

        // Сохраняем старые значения переведенных строк, котрые уже были сделаны в файле перевода
        if(file_exists($js_strings_output_file)){
            $old_strings = include($js_strings_output_file);
            if(is_array($old_strings)){
                $js_strings = array_merge($js_strings, $old_strings);
            }
        }
        
        file_put_contents($php_strings_output_file, '<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/ return '.var_export($php_strings, true).';');
        file_put_contents($js_strings_output_file, '<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/ return '.var_export($js_strings, true).';');
    }


    /**
     * Получить все строки из директории
     *
     * @param string $directory_path - путь к папке
     * @param array $extensions - расширения файлов для поиска
     * @param array $patterns - массив парсинга
     * @return array
     */
    static public function getStringsFromDir($directory_path, array $extensions, array $patterns)
    {
        $ret = array();

        if(mb_stripos($directory_path,'node_modules') !== false){ //Пропускаем папку node_modules, чтобы она не учитывась
            return $ret;
        }
        
        foreach($extensions as $ex){
            foreach(glob($directory_path.'/*.'.$ex) as $one){
                if(preg_match('/tpl\.php$/', $one)){ //Пропускаем автоматически сгенерированные файлы кэша шаблонов
                    continue;
                }
                $ret = array_merge($ret, self::getStringsFromFile($one, $patterns));
            }
        }
        
        $dirs = glob($directory_path.'/*', GLOB_ONLYDIR);
        foreach($dirs as $one){
            if(is_dir($one)){
                $substrings = self::getStringsFromDir($one, $extensions, $patterns);
                $ret = array_merge($ret, $substrings);
            }
        }
        
        return $ret;
    }

    /**
     * Получить все строки из директории используя токинайзер PHP
     *
     * @param string $directory_path - путь к папке
     * @param array $extensions - расширения файлов для поиска
     * @return array
     */
    static public function getStringsFromDirByTokenizer($directory_path, array $extensions)
    {
        $ret = array();

        foreach($extensions as $ex){
            foreach(glob($directory_path.'/*.'.$ex) as $one){
                if(preg_match('/tpl\.php$/', $one)){ //Пропускаем автоматически сгенерированные файлы кэша шаблонов
                    continue;
                }
                $ret = array_merge($ret, self::getStringsFromFileByTokenizer($one));
            }
        }

        $dirs = glob($directory_path.'/*', GLOB_ONLYDIR);
        foreach($dirs as $one){
            if(is_dir($one)){
                $substrings = self::getStringsFromDirByTokenizer($one, $extensions);
                $ret = array_merge($ret, $substrings);
            }
        }

        return $ret;
    }

    /**
     * Получить все строки из конкретного файлы
     *
     * @param string $file_path - путь к файлу
     * @param array $patterns - правила для парсинга
     * @return array
     */
    static public function getStringsFromFile($file_path, array $patterns)
    {
        $file_content = @file_get_contents($file_path);
        $ret = array();
        foreach($patterns as $one){
            preg_match_all($one, $file_content, $matches);
            $messages = array();
            if (!empty($matches[1])){
                foreach ($matches[0] as $key=>$match){
                    if (preg_match('/alias\s*?=\s*?[\'|"]([^\'"]+|)[\'|"]/i', $match, $aliases)){
                        $alias = "!".$aliases[1];
                        $messages[$alias] = $matches[1][$key];
                    }else{
                        $message = $matches[1][$key];
                        $messages[$message] = $message;
                    }
                }

                $ret = array_merge($ret, $messages);
            }
        }
        return $ret;
    }

    /**
     * Получить все строки из конкретного файла используя лексер для обхода
     *
     * @param string $file_path - путь к файлу
     * @return array
     */
    static public function getStringsFromFileByTokenizer($file_path)
    {
        $tokens = token_get_all(file_get_contents($file_path));

        $ret = array();
        $t_function_token_start = false; //Началась ли конструкция обертки функцией t
        $t_array_token_start = false; //Началась ли конструкция обертки функцией t с параметрами
        $message_stack = array(); //Стэк сообщений
        $brackets = 0; //Стэк скобок фукции t
        $brackets_array = 0; //Стэк скобок фукции t в массиве параметров
        $commas = 0; //Стэк запятых
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $token_name  = token_name($token[0]); //Наименование токена
                $token_value = stripslashes(trim($token[1],"\"'"));
                if (!$t_function_token_start && ($token_name == "T_STRING") && ($token_value == "t")){
                    $t_function_token_start = true;
                }
                if ($t_function_token_start && ($token_name == "T_ARRAY")){ //Если начался массив параметров
                    $t_array_token_start = true;
                }
                if ($t_function_token_start && ($token_name == "T_CONSTANT_ENCAPSED_STRING")){
                    $message_stack[] = $token_value;
                }
            }else{
                if ($t_function_token_start && ($token == "(")){ //Увеличим стек скобок
                    $brackets++;
                }
                if ($t_function_token_start && ($token == ")")){ //Уменьшим стек скобок
                    $brackets--;
                }
                if ($t_array_token_start && ($token == "(")){ //Увеличим стек скобок
                    $brackets_array++;
                }
                if ($t_array_token_start && ($token == ")")){ //Уменьшим стек скобок
                    $brackets_array--;
                }
                if ($t_array_token_start && $brackets_array == 0){ //Исключим параметры массива
                    $t_array_token_start = false;
                }
                if ($t_function_token_start && !$t_array_token_start && ($token == ",")){ //Уменьшим стек скобок
                    $commas++;
                }
                if ($t_function_token_start && ($brackets == 0)){ //Если это конец обёртки функции t
                    $message = reset($message_stack);
                    if ((count($message_stack) > 1) && $commas > 1){ //Если присутствует alias
                        $alias = $message_stack[count($message_stack)-1];
                        $ret["!".$alias] = $message;
                    }else{ //Если, есть только фраза без псевдонима
                        $ret[$message] = $message;
                    }

                    //Сбросим на начало
                    $t_function_token_start = false;
                    $message_stack = array();
                    $brackets = 0;
                    $commas = 0;
                }
            }
        }

        return $ret;
    }

    /**
     * Возвращает ассоциативный массив со списком модулей и тем оформления, которые можно перевести
     *
     * @return array
     */
    static function getTranslateModuleList()
    {
        $module_manager = new \RS\Module\Manager();
        $modules = $module_manager->getList();

        $theme_manager = new \RS\Theme\Manager();
        $themes = $theme_manager->getList();

        $result = array(
            '' => t('Все'),
            '@core' => t('Ядро')
        );
        foreach($modules as $name => $module) {
            $result[$name] = t('Модуль: %0', array('('.$name.') '.$module->getConfig()->name));
        }

        foreach($themes as $name => $theme) {
            $result['#'.$name] = t('Тема оформления: %0', array( '('.$name.') '.(string)$theme->getThemeXml()->general->name));
        }

        return $result;
    }

    /**
     * Возвращает список языков, для которых созданы языковые файлы в системе
     *
     * @return array
     */
    static function getPossibleLang()
    {
        $result = array();
        $list = glob(\Setup::$PATH.'/modules/*/view/lang/*');
        $list = array_merge($list, glob(\Setup::$PATH.'/templates/*/resource/lang/*'));
        $list = array_merge($list, glob(\Setup::$PATH.'/resource/lang/*'));

        foreach($list as $item) {
            if ($item !== false) {
                $lang = strtolower(basename($item));
                $result[$lang] = $lang;
            }
        }

        return $result;
    }

    /**
     * Возвращает ссылку на созданный zip архив
     *
     * @param string $lang
     * @return string
     */
    static function makeLangArchive($lang, $file = null)
    {
        //Дополнительно валидируем
        $lang = preg_replace('/[^a-zA-Z]/', '', $lang);

        if (!$file) {
            $filename = \Setup::$TMP_REL_DIR.'/lang/RS_lang_'.strtolower($lang).'.zip';
            $file = \Setup::$ROOT.$filename;
            \RS\File\Tools::makePath($file, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($file, \ZipArchive::CREATE) !== true) {
            throw new \RS\Exception(t('Не удалось создать zip архив'));
        }

        //Ядро
        $core = '/resource/lang/'.$lang;
        self::addLangFolderToArchive($zip,  $core);

        //Модули и темы оформления
        $list = glob(\Setup::$PATH.'/modules/*/view/lang/'.$lang);
        $list = array_merge($list, glob(\Setup::$PATH.'/templates/*/resource/lang/'.$lang));

        foreach($list as $item) {
            $module_lang = str_replace(\Setup::$PATH, '', $item);
            self::addLangFolderToArchive($zip,  $module_lang);
        }

        $ok = $zip->numFiles > 0;

        $zip->close();
        return $ok ? $filename : false;
    }

    /**
     * Добавляет файлы одного модуля в архив
     *
     * @param $zip
     * @param $folder
     */
    protected static function addLangFolderToArchive(\ZipArchive $zip, $relative_folder)
    {
        $absolute = \Setup::$PATH.$relative_folder;
        if (is_dir($absolute)) {
            $lng_php = '/messages.lng.php';
            if (file_exists($absolute.$lng_php)) {
                $zip->addFile($absolute.$lng_php, ltrim($relative_folder.$lng_php,'/'));
            }

            $lng_js = '/messages.js.php';
            if (file_exists($absolute.$lng_js)) {
                $zip->addFile($absolute.$lng_js, ltrim($relative_folder.$lng_js, '/'));
            }
        }
    }
}