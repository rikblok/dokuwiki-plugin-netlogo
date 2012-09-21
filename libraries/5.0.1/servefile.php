<?php
/**
 * DokuWiki Plugin netlogo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

// can't access file directly because of DokuWiki folder permissions so server it up thru php
$src = DOKU_INC.'data/media/playground/test.nlogo';
//echo file_get_contents($src);
$renderer->doc .= '<code>'.file_get_contents($src).'</code>';
// vim:ts=4:sw=4:et:
