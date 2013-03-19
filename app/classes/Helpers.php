<?php
/**
 * Date: 19.2.13
 * Time: 18:08
 * Author: Michal Májský
 */

namespace SRS;

class Helpers
{
    const DATE_PATTERN = '([0-9]){4}-([0-9]){2}-([0-9]){2}';


    public static function renderBoolean($bool)
    {
        if ($bool) return 'ANO';
        return 'NE';
    }


}