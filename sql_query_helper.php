<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
if (!function_exists('query')) {
    /**
     * Функция выполняет умную замену _символов подстановки_ в SQL-выражении (переданном в `$sql`)
     * на значения массива `$values` в порядке вхождения и возвращает полученный результат в
     * виде определенном переменной `$type`
     *
     * ##<a name="head0"></a>Содержание:
     * + [Символы подстановки](#head1)
     * + [Варианты значений `$type` для запросов возвращающих результат запроса](#head2)
     * + [Варианты значений `$type` для запросов НЕ возвращающих результат запроса](#head3)
     * + [Примеры](#head4)
     * + [Параметры](#head5)
     *
     * ##[__^__](#head0) <a name="head1"></a>Символы подстановки:
     *
     * (`$value` в рамках данного описания соответсвует значению из массива `$values`)
     *
     * + **~** - превращается в алиас поля MySQL (``'zxc~' == 'zxc AS `$value`'``)
     * + **!** - превращается в число с плавающей точкой (``'zxc = !' == 'zxc = (float) $value'``)
     * + **@** - превращается в цепочку текстовых значений через запятую (``'IN(@)' == 'IN("'.implode('","',$value).'")'``) (здесь `$value` должен быть массивом) (строки экранируются посредством функции Code_Igniter->db->escape_str())
     * + **#** - превращается в цепочку числовых значений через запятую (``'IN(@)' == 'IN(implode(',',$value))'``) (здесь `$value` должен быть массивом)
     * + **?** - превращается в текстовое значение (``'zxc = ?' == 'zxc = "$value"'``) (строки экранируются посредством функции Code_Igniter->db->escape_str())
     * + **|** - помещает значение в строку без изменений (``'zxc = |' == 'zxc = $value'``, при `$value == 'NULL'` это будет означать, что ``'zxc = |' == 'zxc = NULL'``)
     * + **&** - нумерованная ссылка на значение из `$values`, `&0` будет ссылаться на `$values[0]`, `&1` на `$values[1]` и так далее. Таким образом одно и тоже значение может быть использовано *многократно* в одном SQL-выражении
     * + **{}** - специальная конструкция для работы с массивами (чаще всего в `INSERT ... VALUES`), которая подменяется блоками скобок `(...)`, количество которых определяется  длиной массива из подстановки. В случае если передается более одного массива, количество элементов в них должно совпадать или будет выброшено исключение. Допускаются как нумерованные (`#`), так и именованные (`@`) массивы. Если вместе с массивами будет передан строчный или числовой параметр (`?` или `!`), то его значение будет использовано во всех блоках скобок. [(пример)](#ex17)
     *
     * * * *
     *
     * ##[__^__](#head0) <a name="head2"></a>Варианты значений `$type` для запросов возвращающих результат запроса:
     *
     * + **one**|**?** - возвратит значение первой колонки первого найденного ряда в виде строки или числа [(пример)](#ex1)
     * + **row**|**#**|**:#** - возвратит первый ряд результата в виде массива с числовыми индексами [(пример)](#ex2)
     * + **col**|**#:**|**#:?** - возвратит первую колонку результата в виде массива с числовыми индексами [(пример)](#ex3)
     * + **line**|**@**|**:@** - возвратит первый ряд результата в виде хэш-массива, где имена  ключей соответствуют именам колонок результата [(пример)](#ex4)
     * + **num**|**#:#** - возвратит числовой массив, где каждое значение будет *числовым массивом* полей строки результата [(пример)](#ex5)
     * + **hash**|**#:@** - возвратит числовой массив, где каждое значение будет *хэш-массивом* полей одной строки результата, где ключи нижнего массива равны именам колонок результата [(пример)](#ex6)
     * + **keynum**|**$:#** - возвратит массив, ключами которого станут значения первой колонки результата, а значениями *числовые массивы* с значениями остальных колонок результата [(пример)](#ex7)
     * + **keyhash**|**$:@** - возвратит массив, ключами которого станут значения первой колонки результата, а значениями *хэш-массивы* с значениями остальных колонок результата, где ключи нижнего массива будут равны именам колонок результата [(пример)](#ex8)
     * + **keycol**|**$:** - возвратит массив ключами которого станут значения первой колонки результата, а значениями - значения второй колонки [(пример)](#ex9)
     * + **keyarrayarray**|**$:##** - возвратит массив, ключами которого будут значения первой колонки, а значениями - числовой массив *числовых массивов* со значениями остальных колонок [(пример)](#ex10)
     * + **keyarrayhash**|**$:#@** - возвратит массив , ключами которого будут значения первой колонки, а значениями - числовой массив *хэш-массивов* со значениями остальных колонок, где ключами нижнего массива будут имена колонок [(пример)](#ex11)
     * + **keyarray**|**$:#~** - возвратит массив, где ключами будут значения первой колонки результата, а значениями массив со всеми вариантами второй колонки результата (предполагается использование в тех случаях, когда первая колонка может иметь не уникальные значения) [(пример)](#ex12)
     * + **multi#**|**#:#:** - возвратит 2-мерный массив, где для 1D: ключи - автоинкрементны; 2D: ключи - автоинкрементны, значения соответствует полям колонок результата [(пример)](#ex13)
     * + **multi@**|**@:#:** - возвратит 2-мерный массив, где для 1D: ключи - имена колонок; 2D: ключи - автоинкрементны, значения соответствует полям колонок результата [(пример)](#ex14)
     * + **#multi#**|**#:$:** - возвратит 2-мерный массив, где для 1D: ключи - автоинкрементны; 2D: ключи - значения первой колонки результата, значения соответствует полям колонок результата [(пример)](#ex15)
     * + **#multi@**|**@:$:** - возвратит 3-мерный массив, где для 1D: ключи - имена колонок; 2D: ключи - значения первой колонки результата; 3D: ключи - автоинкрементны, значения соответствует полям колонок результата [(пример)](#ex16)
     *
     * ##[__^__](#head0) <a name="head3"></a>Варианты значений `$type` для запросов НЕ возвращающих результат запроса:
     *
     * + `0` - вернет кол-во измененных строк;
     * + `1` - вернет ID последней вставленной записи.
     *
     * * * *
     *
     * ##[__^__](#head0) <a name="head4"></a>Примеры:
     *
     * Для всех примеров используется таблица следующего вида:
     *
     * ```mysql
     * > SHOW CREATE TABLE `tmp_tbl`;
     *
     * CREATE TABLE `tmp_tbl` (
     * `col_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     * `col_char` char(5) NOT NULL,
     * `col_date` date NOT NULL,
     * `col_text` text,
     * PRIMARY KEY (`col_id`),
     * KEY `col_date` (`col_date`)
     * ) ENGINE=MyISAM CHARSET=utf8 COMMENT='test table'
     *
     * > SELECT * FROM `tmp_tbl`;
     *
     * +--------+----------+------------+---------------------+
     * | col_id | col_char | col_date   | col_text            |
     * +--------+----------+------------+---------------------+
     * |      1 | aaaaa    | 2016-09-12 | some text           |
     * |      2 | bbbbb    | 2016-09-11 | NULL                |
     * |      3 | ccccc    | 2016-09-10 | NULL                |
     * |      4 | ddddd    | 2016-09-12 | not null, same date |
     * +--------+----------+------------+---------------------+
     *
     * ```
     *
     *
     * ###<a name="ex1"></a> _Пример 1_, `type=one`
     *
     * ```php
     * SELECT `col_char`
     * FROM `tmp_tbl`
     * WHERE `col_id` = !
     *
     * print_r($sql,[1],"one");
     *
     *
     * aaaaa
     *
     * ```
     *
     *
     * ###<a name="ex2"></a> _Пример 2_, `type=#`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     * WHERE `col_id` = !
     *
     * print_r($sql,[1],"#");
     *
     *
     * Array
     *
     * (
     *
     *     [0] => 1
     *
     *     [1] => aaaaa
     *
     *     [2] => 2016-09-12
     *
     *     [3] => some text
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex3"></a> _Пример 3_, `type=#:`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,,"#:");
     *
     *
     * Array
     *
     * (
     *
     *     [0] => 1
     *
     *     [1] => 2
     *
     *     [2] => 3
     *
     *     [3] => 4
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex4"></a> _Пример 4_, `type=@`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     * WHERE `col_id` = !
     *
     * print_r($sql,[2],"@");
     *
     *
     * Array
     *
     * (
     *
     *     [col_id] => 2
     *
     *     [col_char] => bbbbb
     *
     *     [col_date] => 2016-09-11
     *
     *     [col_text] =>
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex5"></a> _Пример 5_, `type=#:#`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"#:#");
     *
     *
     * Array
     *
     * (
     *
     *     [0] => Array
     *
     *         (
     *
     *             [0] => 1
     *
     *             [1] => aaaaa
     *
     *             [2] => 2016-09-12
     *
     *             [3] => some text
     *
     *         )
     *
     *
     *
     *     [1] => Array
     *
     *         (
     *
     *             [0] => 2
     *
     *             [1] => bbbbb
     *
     *             [2] => 2016-09-11
     *
     *             [3] =>
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [0] => 3
     *
     *             [1] => ccccc
     *
     *             [2] => 2016-09-10
     *
     *             [3] =>
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [0] => 4
     *
     *             [1] => ddddd
     *
     *             [2] => 2016-09-12
     *
     *             [3] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex6"></a> _Пример 6_, `type=#:@`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"#:@");
     *
     *
     * Array
     *
     * (
     *
     *     [0] => Array
     *
     *         (
     *
     *             [col_id] => 1
     *
     *             [col_char] => aaaaa
     *
     *             [col_date] => 2016-09-12
     *
     *             [col_text] => some text
     *
     *         )
     *
     *
     *
     *     [1] => Array
     *
     *         (
     *
     *             [col_id] => 2
     *
     *             [col_char] => bbbbb
     *
     *             [col_date] => 2016-09-11
     *
     *             [col_text] =>
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [col_id] => 3
     *
     *             [col_char] => ccccc
     *
     *             [col_date] => 2016-09-10
     *
     *             [col_text] =>
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [col_id] => 4
     *
     *             [col_char] => ddddd
     *
     *             [col_date] => 2016-09-12
     *
     *             [col_text] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex7"></a> _Пример 7_, `type=$:#`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"$:#");
     *
     *
     * Array
     *
     * (
     *
     *     [1] => Array
     *
     *         (
     *
     *             [0] => aaaaa
     *
     *             [1] => 2016-09-12
     *
     *             [2] => some text
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [0] => bbbbb
     *
     *             [1] => 2016-09-11
     *
     *             [2] =>
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [0] => ccccc
     *
     *             [1] => 2016-09-10
     *
     *             [2] =>
     *
     *         )
     *
     *
     *
     *     [4] => Array
     *
     *         (
     *
     *             [0] => ddddd
     *
     *             [1] => 2016-09-12
     *
     *             [2] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex8"></a> _Пример 8_, `type=$:@`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"$:@");
     *
     *
     * Array
     *
     * (
     *
     *     [1] => Array
     *
     *         (
     *
     *             [col_char] => aaaaa
     *
     *             [col_date] => 2016-09-12
     *
     *             [col_text] => some text
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [col_char] => bbbbb
     *
     *             [col_date] => 2016-09-11
     *
     *             [col_text] =>
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [col_char] => ccccc
     *
     *             [col_date] => 2016-09-10
     *
     *             [col_text] =>
     *
     *         )
     *
     *
     *
     *     [4] => Array
     *
     *         (
     *
     *             [col_char] => ddddd
     *
     *             [col_date] => 2016-09-12
     *
     *             [col_text] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex9"></a> _Пример 9_, `type=$:`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"$:");
     *
     *
     * Array
     *
     * (
     *
     *     [1] => aaaaa
     *
     *     [2] => bbbbb
     *
     *     [3] => ccccc
     *
     *     [4] => ddddd
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex10"></a> _Пример 10_, `type=$:##`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"$:##");
     *
     *
     * Array
     *
     * (
     *
     *     [1] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [0] => aaaaa
     *
     *                     [1] => 2016-09-12
     *
     *                     [2] => some text
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [0] => bbbbb
     *
     *                     [1] => 2016-09-11
     *
     *                     [2] =>
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [0] => ccccc
     *
     *                     [1] => 2016-09-10
     *
     *                     [2] =>
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     *     [4] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [0] => ddddd
     *
     *                     [1] => 2016-09-12
     *
     *                     [2] => not null, same date
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex11"></a> _Пример 11_, `type=$:#@`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"$:#@");
     *
     *
     * Array
     *
     * (
     *
     *     [1] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [col_char] => aaaaa
     *
     *                     [col_date] => 2016-09-12
     *
     *                     [col_text] => some text
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [col_char] => bbbbb
     *
     *                     [col_date] => 2016-09-11
     *
     *                     [col_text] =>
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [col_char] => ccccc
     *
     *                     [col_date] => 2016-09-10
     *
     *                     [col_text] =>
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     *     [4] => Array
     *
     *         (
     *
     *             [0] => Array
     *
     *                 (
     *
     *                     [col_char] => ddddd
     *
     *                     [col_date] => 2016-09-12
     *
     *                     [col_text] => not null, same date
     *
     *                 )
     *
     *
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex12"></a> _Пример 12_, `type=$:#~`
     *
     * ```php
     * SELECT `col_date`,`col_id`
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"$:#~");
     *
     *
     * Array
     *
     * (
     *
     *     [2016-09-12] => Array
     *
     *         (
     *
     *             [0] => 1
     *
     *             [1] => 4
     *
     *         )
     *
     *
     *
     *     [2016-09-11] => Array
     *
     *         (
     *
     *             [0] => 2
     *
     *         )
     *
     *
     *
     *     [2016-09-10] => Array
     *
     *         (
     *
     *             [0] => 3
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex13"></a> _Пример 13_, `type=#:#:`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"#:#:");
     *
     *
     * Array
     *
     * (
     *
     *     [0] => Array
     *
     *         (
     *
     *             [0] => 1
     *
     *             [1] => 2
     *
     *             [2] => 3
     *
     *             [3] => 4
     *
     *         )
     *
     *
     *
     *     [1] => Array
     *
     *         (
     *
     *             [0] => aaaaa
     *
     *             [1] => bbbbb
     *
     *             [2] => ccccc
     *
     *             [3] => ddddd
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [0] => 2016-09-12
     *
     *             [1] => 2016-09-11
     *
     *             [2] => 2016-09-10
     *
     *             [3] => 2016-09-12
     *
     *         )
     *
     *
     *
     *     [3] => Array
     *
     *         (
     *
     *             [0] => some text
     *
     *             [1] =>
     *
     *             [2] =>
     *
     *             [3] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex14"></a> _Пример 14_, `type=@:#:`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"@:#:");
     *
     *
     * Array
     *
     * (
     *
     *     [col_id] => Array
     *
     *         (
     *
     *             [0] => 1
     *
     *             [1] => 2
     *
     *             [2] => 3
     *
     *             [3] => 4
     *
     *         )
     *
     *
     *
     *     [col_char] => Array
     *
     *         (
     *
     *             [0] => aaaaa
     *
     *             [1] => bbbbb
     *
     *             [2] => ccccc
     *
     *             [3] => ddddd
     *
     *         )
     *
     *
     *
     *     [col_date] => Array
     *
     *         (
     *
     *             [0] => 2016-09-12
     *
     *             [1] => 2016-09-11
     *
     *             [2] => 2016-09-10
     *
     *             [3] => 2016-09-12
     *
     *         )
     *
     *
     *
     *     [col_text] => Array
     *
     *         (
     *
     *             [0] => some text
     *
     *             [1] =>
     *
     *             [2] =>
     *
     *             [3] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex15"></a> _Пример 15_, `type=#:$:`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"#:$:");
     *
     *
     * Array
     *
     * (
     *
     *     [0] => Array
     *
     *         (
     *
     *             [1] => aaaaa
     *
     *             [2] => bbbbb
     *
     *             [3] => ccccc
     *
     *             [4] => ddddd
     *
     *         )
     *
     *
     *
     *     [1] => Array
     *
     *         (
     *
     *             [1] => 2016-09-12
     *
     *             [2] => 2016-09-11
     *
     *             [3] => 2016-09-10
     *
     *             [4] => 2016-09-12
     *
     *         )
     *
     *
     *
     *     [2] => Array
     *
     *         (
     *
     *             [1] => some text
     *
     *             [4] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     *
     * ###<a name="ex16"></a> _Пример 16_, `type=@:$:`
     *
     * ```php
     * SELECT *
     * FROM `tmp_tbl`
     *
     * print_r($sql,[],"@:$:");
     *
     *
     * Array
     *
     * (
     *
     *     [col_char] => Array
     *
     *         (
     *
     *             [1] => aaaaa
     *
     *             [2] => bbbbb
     *
     *             [3] => ccccc
     *
     *             [4] => ddddd
     *
     *         )
     *
     *
     *
     *     [col_date] => Array
     *
     *         (
     *
     *             [1] => 2016-09-12
     *
     *             [2] => 2016-09-11
     *
     *             [3] => 2016-09-10
     *
     *             [4] => 2016-09-12
     *
     *         )
     *
     *
     *
     *     [col_text] => Array
     *
     *         (
     *
     *             [1] => some text
     *
     *             [4] => not null, same date
     *
     *         )
     *
     *
     *
     * )
     *
     *
     *
     * ```
     *
     * ###<a name="ex17"></a> _Пример 17_, Символ подстановки `{}`
     *
     * ```php
     *
     *  $sql = 'INSERT
     *
     *  `tmp_tbl`
     *
     *  (`col_char`,`col_date`,`col_text`)
     *
     *  VALUES
     *
     *  {@,?,@}';
     *
     *
     * print_r(query($sql, [['a','b','c','d'], '2010-10-10',
     * ['something','something else',null,'something new']], 'sql'));
     *
     *  INSERT
     *
     *  `tmp_tbl`
     *
     *  (`col_char`,`col_date`,`col_text`)
     *
     *  VALUES
     *
     *  ( 'a','2010-10-10','something' ),( 'b','2010-10-10','something else' ),
     * ( 'c','2010-10-10','' ),( 'd','2010-10-10','something new' )
     *
     *
     *
     * ```
     *
     * <a name="head5"></a>
     * @package EasyUtils\DB-helper
     * @param    string       $sql      SQL-выражение с символами подстановки (см.
     *                                  Символы подстановки)
     * @param    mixed|null   $values   массив подстановки
     * @param    int|string   $type     тип возвращаемого значения
     *
     * @return   mixed                  зависит от `$type`
     */
    function query(string $sql, $values = null, $type = '')
    {
        if (!isset($CI)) {
            $CI = &get_instance();
        }

        $CI->load->database();
        try {
            $sql = prepare($sql, $values);
            // log_message('error',$sql);
        } catch (Exception $e) {
            log_message('error', "SQL Parser Error:\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
            show_error('See log');
            exit();
        }

        if (empty($sql)) {return;}

        // var_dump($CI->db);
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

                //[ru Возвратит одно значение] Returns one value
                return getOne($query->row_array());
            case 'row':
            case '#':
            case ':#':

                //[ru Возвратит первую строку как массив с числовыми индексами] Returns first row as an array with numeric keys
                return getOneLine($query->row_array());
            case 'col':
            case '#:':
            case '#:?':

                //[ru Возвратит массив значений первой колонки запроса] Returns an array of values of first column of query
                return getCol($query->result_array());
            case 'line':
            case '@':
            case ':@':
                //[ru Возвратит первую строку как массив с хэш индексами из имен в запросе] Returns first row as an array with hash keys from names of fields in query
                return $query->row_array();
            case 'num':
            case '#:#':

                //[ru Возвратит массив нумерованый массивов с числовыми индексами] Returns an array of numeric arrays with numeric keys
                return getArray($query->result_array());
            case 'hash':
            case '#:@':

                //[ru Возвратит массив нумерованый массивов с хэш индексами из имен в запросе] Returns an array of numeric arrays with hash keys from names of fields in query
                return $query->result_array();
            case 'keynum':
            case '$:#':

                //[ru Возвратит массив, его ключами будут значения первой колонки запроса, массивов с числовыми индексами] Returns an array.Its keys will be values of first column of query,              while values will be numeric arrays
                return getArray($query->result_array(), 0, 1);
            case 'keyhash':
            case '$:@':

                //[ru Возвратит массив, его ключами будут значения первой колонки запроса, массивов с хэш индексами из имен в запросе] Returns an array.Its keys will be values of first column of query,
                //while values will be hash arrays from names in query
                return getArray($query->result_array(), 1, 1);
            case 'keycol':
            case '$:':

                //[ru Возвратит массив с индексами из значений первой колонки запроса и значениями из второй колонки] Returns an array with keys from first query 's column and values from the second
                return getKeyCol($query->result_array());
            case 'keyarrayarray':
            case '$:##':

                // array('first field' => array(0 => array(other fields)[
                //if duplicate first field 1 => array(other fields)...]))
                return getKeyArrayArray($query->result_array());
            case 'keyarrayhash':
            case '$:#@':

                // array('first field' => array(0 => array(other fields)[
                //if duplicate first field 1 => array(other fields)...]))
                return getKeyArrayArray($query->result_array(), 1);
            case 'keyarray':
            case '$:#~':

                //[ru Возвратит многомерный массив с индексами из значений первой колонки запроса и(значениями из второй колонки в виде массива).] Returns a multi - dimensional array with keys from first query 's column and values from the second column as a numeric array
                //[ru Это нужно, если значения из первой колонки запроса имеют повторяющиеся значения] It is needed when values from the first column may have a repeating value
                return getKeyArrayCol($query->result_array());
            case 'multi#':
            case '#:#:':

                //[ru Все multi вернут массив(кол - во равно кол - ву полей запроса) значения--колонки запроса с ключем или авто] All "multis"
                //return an array(quantity of elements equals to quantity fields in query).Values - query 's columns with key or auto
                //[ru Оптимальный синтаксис в последней строке
                //case Tолько в мульти есть два двоеточия] Optimal syntax is in a last

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
            case 'sql':
                return $sql;
        }

        return false;
    }
}

if (!function_exists('getOne')) {
    /**
     * возвращает первое значение входящего массива
     *
     * используется в {@link query()} с параметром `$type="one"` [(пример)](#ex1)
     *
     * @package EasyUtils\DB-helper
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
     * используется в {@link query()} с параметром `$type="num"` [(пример)](#ex5)
     *
     * @package EasyUtils\DB-helper
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
     * используется в {@link query()} с параметром `$type="col"` [(пример)](#ex3)
     *
     * @package EasyUtils\DB-helper
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
     * используется в {@link query()} с параметром `$type="keycol"` [(пример)](#ex9)
     *
     * @package EasyUtils\DB-helper
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
     * используется в {@link query()} с параметром `$type="keyarray"` [(пример)](#ex12)
     *
     * @package EasyUtils\DB-helper
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
     * а массив третьего уровня может быть либо _нумерованным_ массивом (при `$type
     * = 0`), либо имена ключей исходного массива второго уровня (имена колонок) (при `$type
     * = 1`)
     *
     * используется в {@link query()} с параметрами `$type="keyarrayhash"` или `$type="keyarrayarray"` [(пример 1)](#ex10) [(пример 2)](#ex11)
     *
     * @package EasyUtils\DB-helper
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
     * Возвращает двумерный массив с автоинкрементными (`$type = 0`) или _именоваными_
     * (`$type = 1`) индексами второго уровня и индексами первого уровня, которые могут
     * быть автоинкрементами (`$setkey = 0`) или значениями первой колонки (`$setkey = 1`)
     *
     * используется в {@link query()} с параметрами `$type="num"` или `$type="keynum"`
     *  или `$type="keyhash"` [(пример 1)](#ex5), [(пример 2)](#ex7), [(пример 3)](#ex8)
     *
     * @package EasyUtils\DB-helper
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
     * при `$type = 1`), либо автоикрементны (`$type = 0`); ключи второго уровня, либо
     * автоинкрементны (при `$setkey = 0`), либо представляют собой первую колонку (первый
     * элемент каждого исходного массива второго уровня) (при `$setkey = 1`)
     *
     * используется в {@link query()} с параметрами `$type="multi#"` или `$type="multi@"` или `$type="#multi#"` или `$type="#multi@"` [(пример 1)](#ex13), [(пример 2)](#ex14), [(пример 3)](#ex15), [(пример 4)](#ex16)
     *
     * @package EasyUtils\DB-helper
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
     * готовую к передаче БД MySQL. Символы подстановки детально расписаны в документации
     * функции {@link query()}, разделе "Символы подстановки". В случае передачи
     * некорректных параметров генерирует стандартное исключение.
     *
     * @see query()
     * @package EasyUtils\DB-helper
     * @param    string         $text     строка с SQL-выражением и символами подстановки
     * @param    array|null     $values   массив значений для подстановки
     *
     * @return   string|bool             SQL-строку или false
     */
    function prepare($text, $values = null)
    {
        if (empty($text)) {

            //E::getSelf()->msg('SQL text is missing', 'DB::prepare()');
            throw new Exception("SQL string is empty");

            return false;
        }
        if (!isset($values)) {
            return $text;
        }
        if (!is_array($values)) {
            $values = (array) $values;
        }

        preg_match_all('/([~!@#?|])/', $text, $P, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE);
        if (empty($P)) {
            return $text;
        }

        if (count($P) != count($values)) {

            //E::getSelf()->trace();
            //E::getSelf()->msg('Quantity of substitutions in text does not equals to quantity of arguments ' . count($P) . '!=' . count($values), 'DB::prepare()');
            throw new Exception('Quantity of substitutions in text does not equals to quantity of arguments ' . count($P) . '!=' . count($values));
            return false;
        }
        $prepare = str_split($text);
        $values  = array_values($values);
        foreach ($P as $i => $v) {
            $key = $v[0][1];
            switch ($v[0][0]) {
                case '~':

                    //[ru Заменить псевдонимом поля текстом с приставкой в обратных кавычках] replace with field 's alias with text in backward quotes ( ~ == AS `text`)
                    $values[$i]    = ' AS `' . str_replace('`', '', $values[$i]) . '`';
                    $replace[$key] = $i;
                    break;

                case '|':

                    //[ru Заменить текстом без изменений] replace with text with no changes
                    $replace[$key] = $i;
                    break;

                case '?':

                    //[ru Заменить экранированой текстовой строкой в кавычках] replace with a screened text string in doublequotes
                    if (!isset($CI)) {
                        $CI = &get_instance();
                    }

                    $values[$i]    = "'" . $CI->db->escape_str($values[$i]) . "'";
                    $replace[$key] = $i;
                    break;

                case '!':

                    //[ru Заменить числом] replace with an integer
                    $values[$i]    = (float) $values[$i];
                    $replace[$key] = $i;
                    break;

                case '#':

                    //[ru Заменить числовой строкой через запятую из значений масива] replace with a string of numbers from an array separated by coma
                    if (!is_array($values[$i])) {
                        $values[$i] = (array) $values[$i];
                    }

                    $values[$i]    = array_values(array_map('intval', $values[$i]));
                    $replace[$key] = $i;
                    break;

                case '@':

                    //[ru Заменить цепочкой эранированного текста в каычках через запятую] replace with a chain of text strings screened by doublequotes and separated by coma
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
        preg_match_all('/&(\d)/', $text, $P, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE);
        if (!empty($P)) {

            //[ru Найдены ссылки] links found
            foreach ($P as $v) {
                if (isset($v[1][0]) and $v[1][0] >= count($values)) {

                    //E::getSelf()->msg('A link "&' . $v[1][1] . '" is found that appears to be missing in arguments', 'DB::prepare()');
                    throw new Exception('A link "&' . $v[1][1] . '" is found that appears to be missing in arguments');
                    return false;
                }
                $replace[$v[0][1]] = $v[1][0];
                $prepare[$v[1][1]] = null;
            }
        }
        preg_match_all('/{(.*)}/s', $text, $P, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE);
        if (!empty($P)) {

            //[ru Была найдена группировка] A grouping is found
            if (count($P) > 1) {

                //E::getSelf()->msg('More than one grouping is found: "{..}"', 'DB::prepare()');
                throw new Exception('More than one grouping is found: "{..}"');
                return false;
            }
            $start = $P[0][0][1];

            //[ru Позиция для '{'] Position for '{'
            $end = ($start + strlen($P[0][0][0]) - 1);

            //[ru Позиция для '{'] Position for '}'
            $prepare[$start] = null;

            //[ru Убираем из текста '{'] Removing '{ 'from text
            $prepare[$end] = null;

            //[ru Убираем из текста '}'] Removing '}' from text
            for ($i = $start; $i <= $end; $i++) {
                if (isset($replace[$i])) {

                    //[ru Установлена замена для этой позиции копируем и обнуляем] A substitute for this position is found. Copy and remove
                    $Q[$i]       = $replace[$i];
                    $replace[$i] = null;
                }
                $S[$i] = $prepare[$i];

                //[ru Копируем символ из позиции] Copying a symbol from the position
                $prepare[$i] = null;

                //[ru и обнуляем в тексте] and zero out in the text

            }
            if (!empty($Q)) {

                //[ru Была найдена группировка с подстановками] A grouping with substitutions was found
                $M = 0;

                //max count
                foreach ($Q as $vID) {
                    if (is_array($values[$vID])) {

                        //[ru Поулучаем размер первого встреченног масива] Getting a length of first found array
                        if (empty($M)) {
                            $M = count($values[$vID]);
                        } elseif ($M != count($values[$vID])) {

                            //E::getSelf()->msg('Arrays with different quantity of elements are given for a grouping block: "' . $M . '" and "' . count($values[$vID]) . '"', 'DB::prepare()');
                            throw new Exception('Arrays with different quantity of elements are given for a grouping block: "' . $M . '" and "' . count($values[$vID]) . '"');
                            return false;
                        }
                    }
                }
                if (empty($M)) {
                    $M = 1;
                }

                //[ru В группировке нет массивов выполняем один раз] No arrays in grouping.Execute one time
                foreach ($Q as $index => $vID) {

                    //[ru Приводим все значения к массиву одного размера] Bring all values to an array of one size
                    if (is_array($values[$vID])) {
                        $Q[$index] = $values[$vID];
                    } else {
                        $Q[$index] = array_fill(0, $M, $values[$vID]);
                    }
                }
                for ($i = 0; $i < $M; $i++) {

                    //[ru Фромируем блоки замены типа(раз, два)] constructing substitution blocks like(one, two)
                    foreach ($Q as $index => $val) {

                        //[ru В копии строки S заменяем символ в позиции подстановки] replace a symbol in replacement position in S string 's copy
                        $S[$index] = $val[$i];
                    }
                    $tmp[] = '( ' . implode('', $S) . ' )';
                }
                if (!empty($tmp)) {
                    $prepare[$start] = implode(',', $tmp);
                }
            } else {

                //[ru Подстановок в группировке не найдено] No substitutions in group are found
                if (!empty($S)) {

                    //[ru Найден текст группировки возврвщаем его в точку начала группировки] text of grouping is found.return it in a point of beggining of grouping
                    $prepare[$start] = '{' . implode(',', $S) . '}';
                }
            }
        }
        if (!empty($replace)) {

            //[ru Найдены прдстановки устанавливаем замену в точки находок] Substitutions are found.Placing replacement in a point of found
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
