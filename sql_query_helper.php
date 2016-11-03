<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('query')) {
    /**
     * принимает SQL-выражение ($sql), заменяет символы подстановки на 
     * соответсвующие значения ($values) и возвращает результат по указанному 
     * типу возврата ($type)
     * 
     * @package EasyUtils\SQL-helper
     * @param    string       $sql      SQL-выражение с символами подстановки (см.
     *                                  Символы подстановки)
     * @param    mixed|null   $values   массив подстановки
     * @param    int|string   $type     тип возвращаемого значения
     *
     * @return   mixed                  зависит от `$type`
     */
    function query(string $sql, $values = null, $type = null)
    {
        if (!isset($CI)) {
            $CI = &get_instance();
        }

        $CI->load->database();
        try {
            $sql = prepare($sql, $values);
            if($type == 'sql'){
               return $sql;
            }
        } catch (Exception $e) {
            log_message('error', "SQL Parser Error:\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
            show_error(__METHOD__' said: See log');
            exit();
        }

        if (empty($sql)) {return;}

        $query = $CI->db->query($sql);
        if (empty($query)) {
            return;
        }
        if ($type === 1) {
            return $CI->db->insert_id();
        }
        if (empty($type)) {
            return $CI->db->affected_rows();
        }

        switch ($type) {

            case 'one':
            case '?':
                // [ru Возвратит одно значение] 
                // Returns one value
                return getOne($query->row_array());

            case 'row':
            case '#':
            case ':#':

                // [ru Возвратит первую строку как массив с числовыми индексами] 
                // Returns first row as an array with numeric keys
                return getOneLine($query->row_array());

            case 'col':
            case '#:':
            case '#:?':

                // [ru Возвратит массив значений первой колонки запроса] 
                // Returns an array of values of first column of query
                return getCol($query->result_array());

            case 'line':
            case '@':
            case ':@':
                // [ru Возвратит первую строку как массив с хэш индексами из имен в запросе] 
                // Returns first row as an array with hash keys made from names of fields in query
                return $query->row_array();

            case 'num':
            case '#:#':

                // [ru Возвратит массив нумерованых массивов с числовыми индексами] 
                // Returns an array of numeric arrays with numeric keys
                return getArray($query->result_array());

            case 'hash':
            case '#:@':
                // [ru Возвратит массив нумерованых массивов с хэш индексами из имен в запросе] 
                // Returns an array of numeric arrays with hash keys made from names of fields in query
                return $query->result_array();

            case 'key-num':
            case '$:#':
                // [ru Возвратит массив, его ключами будут значения первой колонки запроса, значениями - массивы с числовыми индексами] 
                // Returns an array. Its keys will be values of first column of query, while values will be numeric arrays
                return getArray($query->result_array(), 0, 1);

            case 'key-hash':
            case '$:@':
                // [ru Возвратит массив, его ключами будут значения первой колонки запроса, значениями - массивы с хэш индексами из имен в запросе] 
                // Returns an array.Its keys will be values of first column of query, while values will be hash arrays with keys made from names in query
                return getArray($query->result_array(), 1, 1);

            case 'key-col':
            case '$:':
            case '$:?':
                // [ru Возвратит массив с индексами из значений первой колонки запроса и значениями из второй колонки] 
                // Returns an array with keys from first query's column and values from the second
                return getKeyCol($query->result_array());

            case 'key-array-array':
            case '$:##':
                // [ru Возвратит многомерный массив с индексами из значений первой колонки запроса и значениями из числовых массивов, где каждое значение - нумерованный массив уникальных значений для колонки]
                // Returns a mult-dimensional array with keys from first query's column and values a numeric arrays, which values are numeric arrays of unique values for columns
                return getKeyArrayArray($query->result_array());

            case 'key-array-hash':
            case '$:#@':
                // [ru Возвратит многомерный массив с индексами из значений первой колонки запроса и значениями из числовых массивов, где каждое значение - хэш-массив уникальных значений для колонки с индексами из имен в запросе]
                // Returns a mult-dimensional array with keys from first query's column and values a numeric arrays, which values are hash-arrays of unique values for columns with keys masde from names in query
                return getKeyArrayArray($query->result_array(), 1);

            case 'key-array':
            case '$:#~':
                // [ru Возвратит многомерный массив с индексами из значений первой колонки запроса и значениями из остальных колонок в виде хэш-массива.] 
                // Returns a multi - dimensional array with keys from first query 's column and values from the second column as a numeric array
                // [ru Это нужно, если значения из первой колонки запроса имеют повторяющиеся значения во второй] 
                // It is needed when values from the first column may have a repeating value in second
                return getKeyArrayCol($query->result_array());

            case 'multi#':
            case '#:#:':
                // [ru Все multi вернут массив(кол - во равно кол - ву полей запроса) значения--колонки запроса с ключем или авто] 
                // All "multis" return an array(quantity of elements equals to quantity fields in query).Values - query 's columns with key or auto
                // [ru Оптимальный синтаксис в последней строке Tолько в мульти есть два двоеточия] 
                // Optimal syntax is in a last case. Only multis have two semicolumns
                return getMulti($query->result_array());

            case 'multi@':
            case '@:#:':
                return getMulti($query->result_array(), 1);

            case '#multi#':
            case '#:$:':
                return getMulti($query->result_array(), 0, 1);

            case '#multi@':
            case '@:$:':
                return getMulti($query->result_array(), 1, 1);
        }

        return false;
    }
}

if (!function_exists('getOne')) {
    /**
     * возвращает первое значение входящего массива
     *
     * используется в query() с параметром $type="one"
     *
     * @package EasyUtils\SQL-helper
     * @param    array   $row_array   одномерный массив (хэш или ключевой), обычно строка результата запроса к БД
     *
     * @return   string                значение первого элемента массива `$row_array`
     */
    function getOne($row_array)
    {
        if (!empty($row_array)) {
            return array_values($row_array)[0];
        }
        return '';
    }
}
if (!function_exists('getOneLine')) {
    /**
     * принимает массив и возвращает его значения в виде числового массива
     *
     * используется в query() с параметром $type="num"
     *
     * @package EasyUtils\SQL-helper
     * @param    array   $row_array   одномерный массив (хэш или ключевой), обычно строка результата запроса к БД
     *
     * @return   array                числовой массив
     */
    function getOneLine($row_array)
    {
        if (empty($row_array)) {
            return [];
        }
        return array_values($row_array);
    }
}
if (!function_exists('getCol')) {
    /**
     * принимает двумерный массив и возвращает первую колонку (значения первых элементов
     * массивов второго уровня) в виде _нумерованного_ массива
     *
     * используется в query() с параметром $type="col"
     *
     * @package EasyUtils\SQL-helper
     * @param    array   $result_array   двумерный массив, обычно массив результатов запроса к БД
     *
     * @return   array                   нумерованный одномерный массив
     */
    function getCol($result_array)
    {
        $tmp = [];
        foreach ($result_array as $value) {
            if (!isset($name)) {
                $name = array_keys($value)[0];
            }
            $tmp[] = $value[$name];
        }
        return $tmp;
    }
}
if (!function_exists('getKeyCol')) {
    /**
     * принимает двумерный массив и возвращает одномерный массив в котором индексами служат значения первой колонки (значения первого элемента каждого из массивов второго уровня), а значениями - второй колонки (значения второго элемента каждого из массивов второго уровня)
     *
     * используется в query() с параметром $type="keycol"
     *
     * @package EasyUtils\SQL-helper
     * @param    array   $result_array   двумерный массив, обычно массив результатов запроса к БД
     *
     * @return   array                      одномерный массив, где значения - вторая колонка результата, а ключи - первая
     */
    function getKeyCol($result_array)
    {
        $tmp = [];
        foreach ($result_array as $value) {
            if (!isset($name)) {
                list($key, $name) = array_keys($value);
            }
            $tmp[$value[$key]] = $value[$name];
        }
        return $tmp;
    }
}
if (!function_exists('getKeyArrayCol')) {
    /**
     * принимает двумерный массив и возвращает двумерный массив в котором ключи первого
     * уровня являются значения первой колонки (первый элемент каждого массива второго уровня),
     * а значениями - _нумерованный_ массив со всеми уникальными вариантами значений второй колонки (второго элемента каждого массива второго уровня)
     *
     * используется в query() с параметром $type="keyarray"
     *
     * @package EasyUtils\SQL-helper
     * @param    array   $result_array   двумерный массив, обычно массив результатов запроса к БД
     *
     * @return   array                   двумерный массив
     */
    function getKeyArrayCol($result_array)
    {
        $tmp = [];
        foreach ($result_array as $value) {
            if (!isset($name)) {
                list($key, $name) = array_keys($value);
            }
            $tmp[$value[$key]][] = $value[$name];
        }
        return $tmp;
    }
}
if (!function_exists('getKeyArrayArray')) {
    /**
     * принимает двумерный массив и возвращает трехмерный массив в котором ключи первого уровня
     * соответствуют значениям первой колонки, второй уровень представлен _нумерованным_ массивом,
     * а массив третьего уровня может быть либо _нумерованным_ массивом (при $type
     * = 0), либо имена ключей исходного массива второго уровня (имена колонок) (при $type
     * = 1)
     *
     * используется в query() с параметрами $type="keyarrayhash" или $type="keyarrayarray"
     *
     * @package EasyUtils\SQL-helper
     * @param    array      $result_array   двумерный массив, обычно массив результатов запроса к БД
     * @param    int|bool      $type        тип возврата массива, по-умолчанию - 0 (нумерованный), 1 - (хэш)
     *
     * @return   array                  3-мерный массив
     */
    function getKeyArrayArray($result_array, $type = 0)
    {
        $tmp = [];
        foreach ($result_array as $value) {
            if (empty($type)) {
                $value = array_values($value);
            }
            $index         = array_shift($value);
            $tmp[$index][] = $value;
        }
        return $tmp;
    }
}
if (!function_exists('getArray')) {
    /**
     * Возвращает двумерный массив с автоинкрементными ($type = 0) или _именоваными_
     * ($type = 1) индексами второго уровня и индексами первого уровня, которые могут
     * быть автоинкрементами ($setkey = 0) или значениями первой колонки ($setkey = 1)
     *
     * используется в query() с параметрами $type="num" или $type="keynum"
     *  или $type="keyhash"
     *
     * @package EasyUtils\SQL-helper
     * @param    array         $result_array   двумерный массив, обычно массив результатов запроса к БД
     * @param    int|bool      $type           тип возврата массива, по-умолчанию -  0 (нумерованный), 1 - (хэш)
     * @param    int|bool      $setkey         выставлять ли первую колонку, как ключ массива первого уровня (по-умолчанию: нет)
     *
     * @return   array
     */
    function getArray($result_array, $type = 0, $setkey = 0)
    {
        $tmp = [];
        foreach ($result_array as $value) {
            if (empty($type)) {
                $value = array_values($value);
            }
            if (!empty($setkey)) {
                $index       = array_shift($value);
                $tmp[$index] = $value;
            } else {
                $tmp[] = $value;
            }
        }
        return $tmp;
    }
}
if (!function_exists('getMulti')) {
    /**
     * принимает двумерный массив и возвращает двумерный массив в котором ключи первого
     * уровня либо соответствуют именам колонок (ключей второго уровня исходного массива) (
     * при $type = 1), либо автоикрементны ($type = 0); ключи второго уровня, либо
     * автоинкрементны (при $setkey = 0), либо представляют собой первую колонку (первый
     * элемент каждого исходного массива второго уровня) (при $setkey = 1)
     *
     * используется в query() с параметрами $type="multi#" или $type="multi@" или $type="#multi#" или $type="#multi@"
     *
     * @package EasyUtils\SQL-helper
     * @param    array    $result_array   [description]
     * @param    int      $type           [description]
     * @param    int      $setkey         [description]
     *
     * @return   array                    [description]
     */
    function getMulti($result_array, $type = 0, $setkey = 0)
    {
        $tmp = [];
        foreach ($result_array as $value) {
            if (empty($type)) {
                $value = array_values($value);
            }
            if (!empty($setkey)) {
                $index = array_shift($value);
            }
            foreach ($value as $i => $v) {
                if (!empty($setkey)) {
                    if ($v !== null and $v !== '') {
                        $tmp[$i][$index] = $v;
                    }
                } else {
                    $tmp[$i][] = $v;
                }
                if (!empty($tmp[$i])) {
                    if (!is_array($tmp[$i])) {
                        $tmp[$i] = [];
                    }
                }

            }
        }
        return $tmp;
    }
}
if (!function_exists('prepare')) {
    /**
     * функция выполняющая подстановки в исходной SQL-строке и возвращающая SQL строку
     * готовую к передаче БД MySQL. В случае передачи
     * некорректных параметров генерирует стандартное исключение.
     *
     * @see query()
     * @package EasyUtils\SQL-helper
     * @param    string         $text     строка с SQL-выражением и символами подстановки
     * @param    array|null     $values   массив значений для подстановки
     *
     * @return   string|bool             SQL-строку или false
     */
    function prepare($text, $values = null)
    {
        if (empty($text)) {
            throw new Exception("SQL string is empty");
            return false;
        }

        if (!isset($values)) {
            return $text;
        }

        if (!is_array($values)) {
            throw new Exception("Second parameter should be array, ".gettype($values)." given");
        }

        preg_match_all('/([~!@#?|])/', $text, $P, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE);
        if (empty($P)) {
            return $text;
        }

        if (count($P) != count($values)) {
            throw new Exception('Quantity of substitutions in text does not equals to quantity of arguments ' . count($P) . '!=' . count($values));
            return false;
        }
        $prepare = str_split($text);
        $values  = array_values($values);
        foreach ($P as $i => $v) {
            $key = $v[0][1];
            switch ($v[0][0]) {
                case '~':
                    // [ru Заменить псевдонимом поля текстом с приставкой в обратных кавычках] 
                    // replace with field's alias with text in back-quotes ( ~ == AS `text`)
                    $values[$i]    = ' AS `' . str_replace('`', '', $values[$i]) . '`';
                    $replace[$key] = $i;
                    break;

                case '|':
                    // [ru Заменить текстом без изменений] 
                    // replace with text with no changes
                    $replace[$key] = $i;
                    break;

                case '?':
                    // [ru Заменить экранированой текстовой строкой в кавычках] 
                    // replace with a screened text string in doublequotes
                    if (!isset($CI)) {
                        $CI = &get_instance();
                    }

                    $values[$i]    = "'" . $CI->db->escape_str($values[$i]) . "'";
                    $replace[$key] = $i;
                    break;

                case '!':

                    // [ru Заменить числом] 
                    // replace with an integer
                    $values[$i]    = (float) $values[$i];
                    $replace[$key] = $i;
                    break;

                case '#':

                    // [ru Заменить числовой строкой через запятую из значений масива] 
                    // replace with a string of numbers from an array separated by coma
                    if (!is_array($values[$i])) {
                        $values[$i] = (array) $values[$i];
                    }

                    $values[$i]    = array_values(array_map('floatval', $values[$i]));
                    $replace[$key] = $i;
                    break;

                case '@':

                    // [ru Заменить цепочкой эранированного текста в каычках через запятую] 
                    // replace with a chain of text strings screened by doublequotes and separated by coma
                    if (!isset($CI)) {
                        $CI = &get_instance();
                    }

                    if (!is_array($values[$i])) {
                        $values[$i] = (array) $values[$i];
                    }

                    $escape = array();
                    foreach ($values[$i] as $string) {
                        $escape[] = "'" . $CI->db->escape_str($string) . "'";
                    }
                    $values[$i]    = $escape;
                    $replace[$key] = $i;
                    break;
            }
        }
        preg_match_all('/&(\d{1,2})/', $text, $P, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE);
        if (!empty($P)) {

            // [ru Найдены ссылки] 
            // links found
            foreach ($P as $v) {
                if (isset($v[1][0]) and $v[1][0] >= count($values)) {
                    throw new Exception('A link "&' . $v[1][1] . '" is found that appears to be missing in arguments');
                    return false;
                }
                $replace[$v[0][1]] = $v[1][0];
                $prepare[$v[1][1]] = null;
            }
        }
        preg_match_all('/{(.*)}/s', $text, $P, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE);
        if (!empty($P)) {

            // [ru Была найдена группировка] 
            // A grouping is found
            if (count($P) > 1) {
                throw new Exception('More than one grouping is found: "{..}"');
                return false;
            }
            $start = $P[0][0][1];

            // [ru Позиция для '{'] 
            // Position for '{'
            $end = ($start + strlen($P[0][0][0]) - 1);

            // [ru Позиция для '{'] 
            // Position for '}'
            $prepare[$start] = null;

            // [ru Убираем из текста '{'] 
            // Removing '{ 'from text
            $prepare[$end] = null;

            // [ru Убираем из текста '}'] 
            // Removing '}' from text
            for ($i = $start; $i <= $end; $i++) {
                if (isset($replace[$i])) {

                    // [ru Установлена замена для этой позиции копируем и обнуляем] 
                    // A substitute for this position is found. Copy and remove
                    $Q[$i]       = $replace[$i];
                    $replace[$i] = null;
                }
                $S[$i] = $prepare[$i];

                // [ru Копируем символ из позиции] 
                // Copying a symbol from the position
                $prepare[$i] = null;

                // [ru и обнуляем в тексте] 
                // and zero out in the text

            }
            if (!empty($Q)) {

                // [ru Была найдена группировка с подстановками] 
                // A grouping with substitutions was found
                $M = 0;

                //max count
                foreach ($Q as $vID) {
                    if (is_array($values[$vID])) {

                        // [ru Поулучаем размер первого встреченного масива] 
                        // Getting a length of first found array
                        if (empty($M)) {
                            $M = count($values[$vID]);
                        } elseif ($M != count($values[$vID])) {
                            throw new Exception('Arrays with different quantity of elements are given for a grouping block: "' . $M . '" and "' . count($values[$vID]) . '"');
                            return false;
                        }
                    }
                }
                if (empty($M)) {
                    $M = 1;
                }

                // [ru В группировке нет массивов выполняем один раз] 
                // No arrays in grouping.Execute one time
                foreach ($Q as $index => $vID) {

                    // [ru Приводим все значения к массиву одного размера] 
                    // Bring all values to an array of one size
                    if (is_array($values[$vID])) {
                        $Q[$index] = $values[$vID];
                    } else {
                        $Q[$index] = array_fill(0, $M, $values[$vID]);
                    }
                }
                for ($i = 0; $i < $M; $i++) {

                    // [ru Фромируем блоки замены типа(раз, два)] 
                    // constructing substitution blocks like(one, two)
                    foreach ($Q as $index => $val) {

                        // [ru В копии строки S заменяем символ в позиции подстановки] 
                        // replace a symbol in replacement position in S string's copy
                        $S[$index] = $val[$i];
                    }
                    $tmp[] = '( ' . implode('', $S) . ' )';
                }
                if (!empty($tmp)) {
                    $prepare[$start] = implode(',', $tmp);
                }
            } else {

                // [ru Подстановок в группировке не найдено] 
                // No substitutions in group are found
                if (!empty($S)) {

                    // [ru Найден текст группировки возврвщаем его в точку начала группировки] 
                    // text of grouping is found. return it in a point of beggining of grouping
                    $prepare[$start] = '{' . implode(',', $S) . '}';
                }
            }
        }
        if (!empty($replace)) {

            // [ru Найдены прдстановки устанавливаем замену в точки находок] 
            // Substitutions are found. Placing replacement in a point where found
            foreach ($replace as $key => $vID) {
                if (isset($values[$vID])) {
                    if (is_array($values[$vID])) {
                        $prepare[$key] = implode(',', $values[$vID]);
                    } else {
                        $prepare[$key] = $values[$vID];
                    }
                }
            }
            return implode('', $prepare);
        }
        return false;
    }
}
