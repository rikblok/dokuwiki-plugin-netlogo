<?php
/**
 * DokuWiki Plugin netlogo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 *
 * ToDo:
 *	* if 'conf/local.php' touched since uuidfile created then recreate.  See http://www.jandecaluwe.com/testwiki/doku.php/navigation:sidebar_details#generating_the_sidebar_xhtml for demo. [Rik, 2012-10-05]
 *	* maybe make .nlogo file parsing a method? [Rik, 2012-09-28]
 *	* read size from .nlogo file if not passed as parameter [Rik, 2012-10-12]
 *	* read version from .nlogo file if not passed as parameter [Rik, 2012-10-12]
 *	* download NetLogo jars if version not present [Rik, 2012-10-12]
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
require_once DOKU_PLUGIN.'netlogo/inc/support.php';

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
		 * After plugin:applet (316), before media (320).  See https://www.dokuwiki.org/devel:parser:getsort_list
		*/
        return 317;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{netlogo>[^\}]+\}\}',$mode,'plugin_netlogo_applet');
		// should look for {{*.nlogo}} instead of {{netlogo>*}} but none of the addSpecialPattern's below work.  Why?  http://www.pagecolumn.com/tool/pregtest.htm and other regex testers don't show any problems. [Rik, 2012-10-12]
		// here are some test cases [Rik, 2012-10-12]
		/*
			{{ugh.nlogo}}
			{{ugh.nlogo }}
			{{ ugh.nlogo }}
			{{ ugh.nlogo}}

			{{ugh.nlogo?818x611&version=5.0.1}}
			{{ugh.nlogo?818x611&version=5.0.1 }}
			{{ ugh.nlogo?818x611&version=5.0.1 }}
			{{ ugh.nlogo?818x611&version=5.0.1}}

			{{ugh.nlogo.x}}
			{{ugh.nlogo.x }}
			{{ ugh.nlogo.x }}
			{{ ugh.nlogo.x}}

			{{ugh.nlogo.x?818x611&version=5.0.1}}
			{{ugh.nlogo.x?818x611&version=5.0.1 }}
			{{ ugh.nlogo.x?818x611&version=5.0.1 }}
			{{ ugh.nlogo.x?818x611&version=5.0.1}}
		*/
		// $this->Lexer->addSpecialPattern('\{\{[^\}\{]*?\.nlogo(\?.*)? ?\}\}',$mode,'plugin_netlogo_applet');
		// $this->Lexer->addSpecialPattern('\{\{[^\}]+\.nlogo(\?[^\}]+)?\s?\}\}',$mode,'plugin_netlogo_applet');
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

		//parse width and height (must be first parameter)
		if (preg_match('#^(\d+)(x(\d+))?#i',$param,$size)){
			($size[1]) ? $w = $size[1] : $w = NULL;
			($size[3]) ? $h = $size[3] : $h = NULL;
		} else {
			$w = NULL;
			$h = NULL;
		}

		// default width and height (from Untitled.nlogo). Todo: extract from .nlogo file.
		if (is_null($w)) $w = '644';
		if (is_null($h)) $h = '470';
		
		// parse version number.  See all versions: http://ccl.northwestern.edu/netlogo/oldversions.shtml
		if (preg_match('#version=(\d+\.\d+(\.?[\w]*)?)#',$param,$version)){
			$ver = $version[1];
		} else {
			$ver = NULL;
		}
		
		// default version
		if (is_null($ver)) $ver = '5.0.1';
		
		// download libraries?
		$libjar='lib/plugins/netlogo/libraries/'.$ver.'/NetLogoLite.jar';
		$libjargz='lib/plugins/netlogo/libraries/'.$ver.'/NetLogoLite.jar.pack.gz';
		$copyright='lib/plugins/netlogo/libraries/'.$ver.'/copyright.html';
		$urljar='http://ccl.northwestern.edu/netlogo/'.$ver.'/NetLogoLite.jar';
		$urljargz='http://ccl.northwestern.edu/netlogo/'.$ver.'/NetLogoLite.jar.pack.gz';
		$urlcopyright='http://ccl.northwestern.edu/netlogo/'.$ver.'/docs/copyright.html';
		$dirname = dirname($libjar);
		if (!is_dir($dirname))			mkdir($dirname, 0755, true);
		if (!file_exists($libjar))		io_download($urljar,    $libjar, false, '', 35904890); // max size = 10x latest (v5.0.2)
		if (!file_exists($libjargz))	io_download($urljargz, $libjargz, false, '', 5394750); // max size = 10x latest (v5.0.2)
		if (!file_exists($copyright))	io_download($urlcopyright, $copyright, false, '', 268200); // max size = 10x latest (v5.0.2)
		
		$params = array(
			'src'=>$src,
			'title'=>$link[1],
			'align'=>$align,
			'width'=>$w,
			'height'=>$h,
			'version'=>$ver,
		);

		return $params;
    }

    public function render($mode, &$renderer, $data) {
		global $ID, $conf;
		
        if($mode != 'xhtml') return false;
		
		// check if jar files exist
		if (!file_exists('lib/plugins/netlogo/libraries/'.$data['version'].'/NetLogoLite.jar')) {
			$renderer->doc .= '<p>NetLogo: NetLogoLite.jar version not found: ' . $data['version'] . '</p>';
			return true;
		}
		
		// check .nlogo file read permission
		$src = $data['src'];
		resolve_mediaid(getNS($ID),$src,$exists);
		if(auth_quickaclcheck(getNS($src).':X') < AUTH_READ){ // auth_quickaclcheck() mimicked from http://xref.dokuwiki.org/reference/dokuwiki/_functions/checkfilestatus.html
			$renderer->doc .= '<p>NetLogo: File not allowed: ' . $src . '</p>';
			return true;
		}
		$src = mediaFN($src);
		if (!$exists) {
			$renderer->doc .= '<p>NetLogo: File not found: ' . $src . '</p>';
			return true;
		}
		// $src is currently realpath.  Turn into relative path from DokuWiki media folder
		$src = relativePath(DOKU_INC.'data/media/',$src);
		
		// Will pass token to servefile.php to authorize.  First generate secret uuid if not found.
		$uuidfile = 'data/tmp/plugin_netlogo_uuid';
		if (!file_exists($uuidfile)) {
			if (!$handle = fopen($uuidfile, 'w')) {
				$renderer->doc .= '<p>NetLogo: Cannot create UUID ' . $uuidfile . '</p>';
				return true;
			}
			// Write uuid to our opened file.
			if (fwrite($handle, uuid4()) === FALSE) {
				$renderer->doc .= '<p>NetLogo: Cannot write UUID to ' . $uuidfile . '</p>';
				return true;
			}
			fclose($handle);		
		}
		// read uuid from file
		$uuid = file_get_contents($uuidfile);
		
		// when should the servefile.php link expire?
		$expires = time()+min(max($conf['cachetime'],60), 3600); // expires in cachetime, but no less than 1 minute or more than 1 hour
		
		// disable caching of this page to ensure parameters passed to servefile.php are always fresh [Rik, 2012-10-06]
        $renderer->info['cache'] = false;

		// generate token for servefile.php to authorize, use $uuid as salt.  servefile.php must be able to generate same token or it won't serve file.
		// $token=crypt($src.$expires,$uuid); // debugging [Rik, 2012-10-06] - only uses first 8 chars of $src
		$token=hash('sha256',$uuid.$src.$expires); // debugging [Rik, 2012-10-06] - replace crypt() for more than first 8 chars

		// special handling for center
		$pcenter = false;
		if (!is_null($data['align']) && $data['align']==='center') {
			$pcenter = true;
			$data['align']=null;
		}
		
		if ($pcenter) $renderer->doc .= '<p align="center">';
		$renderer->doc .= '<applet code="org.nlogo.lite.Applet"'
								. '    archive="lib/plugins/netlogo/libraries/'.$data['version'].'/NetLogoLite.jar"'
								. '    width="'.$data['width'].'" height="'.$data['height'].'"';
		if (!is_null($data['align']))	$renderer->doc .= ' align="'.$data['align'].'"';
		if (!is_null($data['title']))	$renderer->doc .= ' alt="'.$data['title'].'"';
		$renderer->doc .= '>'
								. '  <param name="DefaultModel"'
//								. '      value="data/media/playground/test.nlogo">' // debugging [Rik, 2012-09-28] - 403 Forbidden, applet gives runtime error
//								. '      value="lib/plugins/netlogo/inc/servefile.php">' // debugging [Rik, 2012-09-28] - works!
//								. '      value="lib/exe/fetch.php?media=playground:test.nlogo">' // debugging [Rik, 2012-10-03] - 403 Forbidden, applet gives runtime error
//								. '      value="'.$tmpfname.'">' // debugging [Rik, 2012-10-05] - temp file exists but not read not permitted for either sys_get_temp_dir or 'data/tmp'
								. '      value="lib/plugins/netlogo/inc/servefile.php?src='.urlencode($src).'&expires='.$expires.'&token='.urlencode($token).'">' // debugging [Rik, 2012-10-05] - works!
								. '  <param name="java_arguments"'
								. '      value="-Djnlp.packEnabled=true">'
								. '</applet>';
		if ($pcenter) $renderer->doc .= '</p>';
        return true;
    }
}

// vim:ts=4:sw=4:et:
