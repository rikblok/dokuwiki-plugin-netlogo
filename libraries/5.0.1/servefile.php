<?php
/**
 * DokuWiki Plugin netlogo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

// can't access file directly because of DokuWiki folder permissions so serve it up thru php
// debugging: $src should be passed from applet.php [Rik, 2012-09-21]
$src = DOKU_PLUGIN.'netlogo/libraries/5.0.1/test.nlogo';
echo file_get_contents($src);
//$renderer->doc .= '<code>'.file_get_contents($src).'</code>';
// vim:ts=4:sw=4:et:
