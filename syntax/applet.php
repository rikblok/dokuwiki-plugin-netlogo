<?php
/**
 * DokuWiki Plugin netlogo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 *
 * ToDo:
 *	* maybe copy $src to temp file with random name?  That might be safer than servefile.php.  See tempnam().
 *	* maybe make .nlogo file parsing a method?
 *
 * Documentation:
 * NetLogo model file format <https://github.com/NetLogo/NetLogo/wiki/Model-file-format>
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
		/*
		 * Copied from DokuWiki media handler in
		 * http://xref.dokuwiki.org/reference/dokuwiki/_functions/doku_handler_parse_media.html
		*/
		// Strip the opening and closing markup
		$link = preg_replace(array('/^\{\{netlogo>/','/\}\}$/u'),'',$match);	
		
		// Split title from URL
		$link = explode('|',$link,2);

		// Check alignment
		$ralign = (bool)preg_match('/^ /',$link[0]);
		$lalign = (bool)preg_match('/ $/',$link[0]);

		// Logic = what's that ;)...
		if ( $lalign & $ralign ) {
			$align = 'center';
		} else if ( $ralign ) {
			$align = 'right';
		} else if ( $lalign ) {
			$align = 'left';
		} else {
			$align = NULL;
		}

		// The title...
		if ( !isset($link[1]) ) {
			$link[1] = NULL;
		}

		//remove aligning spaces
		$link[0] = trim($link[0]);

		//split into src and parameters (using the very last questionmark)
		$pos = strrpos($link[0], '?');
		if($pos !== false){
			$src   = substr($link[0],0,$pos);
			$param = substr($link[0],$pos+1);
		}else{
			$src   = $link[0];
			$param = '';
		}

		//parse width and height
		if(preg_match('#(\d+)(x(\d+))?#i',$param,$size)){
			($size[1]) ? $w = $size[1] : $w = NULL;
			($size[3]) ? $h = $size[3] : $h = NULL;
		} else {
			$w = NULL;
			$h = NULL;
		}

		// default width and height
		if (is_null($w)) $w = '640';
		if (is_null($h)) $h = '480';
		
		$params = array(
			'src'=>$src,
			'title'=>$link[1],
			'align'=>$align,
			'width'=>$w,
			'height'=>$h,
		);

		return $params;
    }

    public function render($mode, &$renderer, $data) {
		global $ID;
		
        if($mode != 'xhtml') return false;
		// debugging: $src not being used yet.  Should pass as parameter to servefile.php [Rik, 2012-09-21]
		$src = $data['src'];
		resolve_mediaid(getNS($ID),$src,$exists);
		$src = mediaFN($src);
		if (!$exists) {
			$renderer->doc .= '<p>NetLogo: File not found: ' . $src . '</p>';
			return true;
		}
		
		// copy src to temp file with unique name (so it can't be guessed)
//		$tmpfname = tempnam(sys_get_temp_dir(), 'dw_nl_'); // debugging [Rik, 2012-10-05] - works but browser can't read files in syst_get_temp_dir
		$tmpfname = tempnam('data/tmp', 'dw_nl_'); // good 
		copy($src, $tmpfname);   // copy NetLogo source into temp file
		echo '<pre>'.file_get_contents($tmpfname).'</pre>';	// debugging [Rik, 2012-10-05]
		if (chmod($tmpfname,0644)) { // grant Java permission to read temp file
			echo 'chmod ok<br />';
		} else {
			echo 'chmod failed<br />';
		}
		// chmod ok but Apache tmp folder not accessible to browser
		
		// get width & height from file
		$data['width']=818; // 844 works
		$data['height']=511; // 690 works
		
		// special handling for center
		$pcenter = false;
		if (!is_null($data['align']) && $data['align']==='center') {
			$pcenter = true;
			$data['align']=null;
		}
		
		if ($pcenter) $renderer->doc .= '<p align="center">';
		$renderer->doc .= '<applet code="org.nlogo.lite.Applet"'
								. '    archive="lib/plugins/netlogo/libraries/5.0.1/NetLogoLite.jar"'
								. '    width="'.$data['width'].'" height="'.$data['height'].'"';
		if (!is_null($data['align']))	$renderer->doc .= ' align="'.$data['align'].'"';
		if (!is_null($data['title']))	$renderer->doc .= ' alt="'.$data['title'].'"';
		$renderer->doc .= '>'
								. '  <param name="DefaultModel"'
//								. '      value="data/media/playground/test.nlogo">' // debugging [Rik, 2012-09-28] - 403 Forbidden, applet gives runtime error
//								. '      value="lib/plugins/netlogo/inc/servefile.php">' // debugging [Rik, 2012-09-28] - works!
//								. '      value="lib/exe/fetch.php?media=playground:test.nlogo">' // debugging [Rik, 2012-10-03] - 403 Forbidden, applet gives runtime error
								. '      value="'.$tmpfname.'">' // debugging [Rik, 2012-10-05]
								. '  <param name="java_arguments"'
								. '      value="-Djnlp.packEnabled=true">'
								. '</applet>';
		if ($pcenter) $renderer->doc .= '</p>';
        return true;
    }
}

// vim:ts=4:sw=4:et:
