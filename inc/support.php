<?php
/**
 * Helper for DokuWiki Plugin netlogo
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 *
 * ToDo:
 */

function relativePath($from, $to, $ps = '/')
// Returns the relative path from $from to $to. Note: On Windows it does not work when $from and $to are on different drives.
// From http://www.php.net/manual/en/function.realpath.php#105876
{
  $arFrom = explode($ps, rtrim($from, $ps));
  $arTo = explode($ps, rtrim($to, $ps));
  while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
  {
    array_shift($arFrom);
    array_shift($arTo);
  }
  return str_pad("", count($arFrom) * 3, '..'.$ps).implode($ps, $arTo);
}

function uuid4()
// Returns valid version 4 UUID.
// From http://www.php.net/manual/en/function.com-create-guid.php#99425
// See https://en.wikipedia.org/wiki/Uuid#Version_4_.28random.29
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


// vim:ts=4:sw=4:et:
