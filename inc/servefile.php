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

$src = '../../../../data/media/playground/test.nlogo'; // debugging [Rik, 2012-09-28] - works!
//$src = 'data/media/playground/test.nlogo'; // debugging [Rik, 2012-09-28] - doesn't work, returns blank file

echo file_get_contents($src);

// vim:ts=4:sw=4:et:
