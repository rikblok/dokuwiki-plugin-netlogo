<?php
/**
 * Helper for DokuWiki Plugin netlogo
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 *
 * ToDo:
 *	* $src should be passed from applet.php [Rik, 2012-09-21]
 *	* check permissions of $src to make sure user is allowed to view. [Rik, 2012-09-28]
 */

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
