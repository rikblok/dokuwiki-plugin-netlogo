<?php
/**
 * Helper for DokuWiki Plugin netlogo
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 *
 * ToDo:
 *	* don't allow access to any folder above data/media.  Use relativePath() to compare? [Rik, 2012-10-06]
 *	* assume filetype is .nlogo and append to $src.  Prevents from accessing other filetypes. [Rik, 2012-10-06]
 */

// get url parameters
$src = $_GET['src'];
$expires = $_GET['expires'];
$token = $_GET['token'];

// relative path to DokuWiki root
if (!defined('DOKU_INC')) define('DOKU_INC', "../../../../"); // assumes servefile.php nested four levels beneath root, in DOKU_INC.'lib/plugins/netlogo/inc/'

// check token
$uuidfile = DOKU_INC.'data/tmp/plugin_netlogo_uuid';
$uuid = file_get_contents($uuidfile);
//$expectedtoken=crypt($src.$expires,$uuid); // error: can change expires=... in url  (eg. increment by 1) with no problem.  Why? Maybe crypt() has max length for $str? Or am I misusing crypt()? [Rik, 2012-10-06]
$expectedtoken=hash('sha256',$uuid.$src.$expires); // debugging [Rik, 2012-10-06] - replace crypt() for more than first 8 chars
if ($token != $expectedtoken) die();

// check expiration
if (time() > $expires) die();

// check file exists and is readable
$src = DOKU_INC . 'data/media/' . $src;
if (!is_readable($src)) die();

// all ok, serve file
echo file_get_contents($src);

// vim:ts=4:sw=4:et:
