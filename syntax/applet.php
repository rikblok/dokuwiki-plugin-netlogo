<?php
/**
 * DokuWiki Plugin netlogo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 * 
 * Acknowledgements:
 * Thanks to Stylianos Dritsas for the applet plugin 
 *   <https://www.dokuwiki.org/plugin:applet>.
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_netlogo_applet extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

/* // don't override getPType()
    public function getPType() {
        return 'FIXME: normal|block|stack';
    }
*/

    public function getSort() {
		/* Should be less than 320 as defined in
		 * /inc/parser/parser.php:class Doku_Parser_Mode_media
		 * http://xref.dokuwiki.org/reference/dokuwiki/_classes/doku_parser_mode_media.html
		*/
        return 317; // after plugin:applet
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{netlogo>[^\}]+\}\}',$mode,'plugin_netlogo_applet');
//        $this->Lexer->addEntryPattern('<FIXME>',$mode,'plugin_netlogo_applet');
    }

//    public function postConnect() {
//        $this->Lexer->addExitPattern('</FIXME>','plugin_netlogo_applet');
//    }

    public function handle($match, $state, $pos, &$handler){
        $data = array();
		/*
    preg_match( '/width=([0-9]+)/i',            substr( $match, 6, -1 ), $match_width   );
    preg_match( '/height=([0-9]+)/i',           substr( $match, 6, -1 ), $match_height  );
    preg_match( '/code=([a-zA-Z_0-9].+)/i',      substr( $match, 6, -1 ), $match_code    );
    preg_match( '/archive=([a-zA-Z_0-9:.]+)/i', substr( $match, 6, -1 ), $match_archive );
    return array( 
      $match_code[1], 
      $match_width[1], 
      $match_height[1], 
      $match_archive[1] 
    );
	*/

        return $data;
    }

    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
		$renderer->doc .= "<p>NetLogo placeholder</p>";   
        return true;
    }
}

// vim:ts=4:sw=4:et:
