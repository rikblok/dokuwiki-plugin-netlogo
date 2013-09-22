<?php
/**
 * DokuWiki Plugin netlogo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Rik Blok <rik.blok@ubc.ca>
 *
 * ToDo:
 *	* automatically add "nlogo    !application/octet-stream" to conf/mime.local.conf? [Rik, 2012-10-19]
 *	* language support [Rik, 2012-10-19]
 *	* better error messages [Rik, 2012-10-19]
 *	* config options (eg. download url) [Rik, 2012-10-19]
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
		$this->Lexer->addSpecialPattern('\{\{[^\}]+\.nlogo\?[^\} ]*do=download[^\} ]* ?\}\}',$mode,'media');		// with do=download parameter
		$this->Lexer->addSpecialPattern('\{\{[^\}]+\.nlogo ?\}\}',$mode,'plugin_netlogo_applet');									// without parameters
		$this->Lexer->addSpecialPattern('\{\{[^\}]+\.nlogo\?[^\} ]+ ?\}\}',$mode,'plugin_netlogo_applet');				// with other parameters
		// here are some test cases [Rik, 2012-10-12]
		/*
			// should work
			{{ugh.nlogo}}
			{{ugh.nlogo }}
			{{ ugh.nlogo }}
			{{ ugh.nlogo}}

			// should work
			{{ugh.nlogo?818x611&version=5.0.1}}
			{{ugh.nlogo?818x611&version=5.0.1 }}
			{{ ugh.nlogo?818x611&version=5.0.1 }}
			{{ ugh.nlogo?818x611&version=5.0.1}}

			// should fail
			{{ugh.nlogo.x}}
			{{ugh.nlogo.x }}
			{{ ugh.nlogo.x }}
			{{ ugh.nlogo.x}}

			// should fail
			{{ugh.nlogo.x?818x611&version=5.0.1}}
			{{ugh.nlogo.x?818x611&version=5.0.1 }}
			{{ ugh.nlogo.x?818x611&version=5.0.1 }}
			{{ ugh.nlogo.x?818x611&version=5.0.1}}
		*/
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
		$link = preg_replace(array('/^\{\{/','/\}\}$/u'),'',$match);	
		
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

		// parse width and height (must be first parameter)
		if (preg_match('#^(\d+)(x(\d+))?#i',$param,$size)){
			($size[1]) ? $w = $size[1] : $w = NULL;
			($size[3]) ? $h = $size[3] : $h = NULL;
		} else {
			$w = NULL;
			$h = NULL;
		}

		// parse version number.  See all versions: http://ccl.northwestern.edu/netlogo/oldversions.shtml
		if (preg_match('#version=(\d+\.\d+(\.?[\w]*)?)#',$param,$version)){
			// specified by user
			$ver = $version[1];
		} else {
			$ver = NULL;
		}
		
		// parse 'do' action
		if (preg_match('#do=([a-z]+)#',$param,$action)){
			// specified by user
			$do = $action[1];
		} else {
			$do = "interface";	// default
		}

		// check for nlogo fileicon
		$nlogoiconsrc = DOKU_PLUGIN.'netlogo/fileicons/nlogo.png';
		$nlogoicondest = DOKU_INC.'lib/images/fileicons/nlogo.png';
		if (!file_exists($nlogoicondest))	copy($nlogoiconsrc, $nlogoicondest);

		$params = array(
			'src'=>$src,
			'title'=>$link[1],
			'align'=>$align,
			'width'=>$w,
			'height'=>$h,
			'version'=>$ver,
			'do'=>$do,
		);

		return $params;
    }

    public function render($mode, &$renderer, $data) {
		global $ID, $conf;
		
        if($mode != 'xhtml') return false;
		
		// check .nlogo file read permission
		$src = $data['src'];
		resolve_mediaid(getNS($ID),$src,$exists);
		if(auth_quickaclcheck(getNS($src).':X') < AUTH_READ){ // auth_quickaclcheck() mimicked from http://xref.dokuwiki.org/reference/dokuwiki/_functions/checkfilestatus.html
			$renderer->doc .= '<div class="error">NetLogo: File not allowed: ' . $src . '</div>';
			return true;
		}
		$src = mediaFN($src);
		if (!$exists) {
			$renderer->doc .= '<div class="error">NetLogo: File not found: ' . $src . '</div>';
			return true;
		}

		// parse file to get contents
		if (is_null($data['version']) || is_null($data['width']) || is_null($data['height']) || $data['do']==='code' || $data['do']==='info' || $data['do']==='mdinfo') {
			$nlogo = file_get_contents($src);
			$nlogoparts = explode('@#$#@#$#@', $nlogo);
			/*
				[0] => code
				[1] => interface
				[2] => info
				[3] => turtle shapes
				[4] => NetLogo version
				[5] => preview commands
				[6] => system dynamics modeler
				[7] => BehaviorSpace
				[8] => HubNet client
				[9] => link shapes
				[10] =>model settings
				[11] =>reserved by Michelle
				[12] => (empty)
			*/

			// show code
			if ($data['do']==='code') {
				$renderer->doc .= p_render('xhtml',p_get_instructions('<code netlogo>' . $nlogoparts[0] . '</code>'),$info);
				return true;
			}
			
			// show info
			if ($data['do']==='info') {
				$renderer->doc .= p_render('xhtml',p_get_instructions($nlogoparts[2]),$info);
				return true;
			}
			// show info wrapped in '<markdown>...</markdown>' tags
			if ($data['do']==='mdinfo') {
				$renderer->doc .= p_render('xhtml',p_get_instructions('<markdown>' . $nlogoparts[2] . '</markdown>'),$info);
				return true;
			}
			
			// version?
			if (is_null($data['version'])) {
				preg_match('/NetLogo (\d+\.\d+(\.?[\w]*)?)/',$nlogoparts[4],$version);
				$data['version'] = $version[1];
			}
			// width & height?
			if (is_null($data['width']) || is_null($data['height'])) {
				// store x,y coordinates of bottom right corner in $rightbottom[2] & $rightbottom[3], respectively
				preg_match_all('/(^|\n)\n[A-Z\-]+\n[0-9]+\n[0-9]+\n([0-9]+)\n([0-9]+)\n/',$nlogoparts[1],$rightbottom);
				if (is_null($data['width']))	$data['width'] = max($rightbottom[2]);
				if (is_null($data['height']))	$data['height'] = max($rightbottom[3]);
			}
		}
		
		// download libraries? Todo: move root url to config option
		$libroot = 'lib/plugins/netlogo/libraries/'.$data['version'];
		$urlroot = 'http://ccl.northwestern.edu/netlogo/'.$data['version'];
		$libjar= $libroot.'/NetLogoLite.jar';
		$libjargz= $libroot.'/NetLogoLite.jar.pack.gz';
		$copyright= $libroot.'/copyright.html';
		$urljar= $urlroot.'/NetLogoLite.jar';
		$urljargz= $urlroot.'/NetLogoLite.jar.pack.gz';
		$urlcopyright= $urlroot.'/docs/copyright.html';
		$dirname = dirname($libjar);
		if (!is_dir($dirname))			mkdir($dirname, 0755, true);
		// fix Fatal error: Allowed memory size of ___ bytes exhausted (tried to allocate ___ bytes) in /___/inc/HTTPClient.php on line ___
		ini_set('memory_limit', '100M');
		if (!file_exists($libjar))		io_download($urljar, $libjar, false, '', 35904890); // max size = 10x latest (v5.0.2)
		if (!file_exists($libjargz))	io_download($urljargz, $libjargz, false, '', 5394750); // max size = 10x latest (v5.0.2)
		if (!file_exists($copyright))	io_download($urlcopyright, $copyright, false, '', 268200); // max size = 10x latest (v5.0.2)

		// check if jar files exist
		if (!file_exists($libjar)) {
			$renderer->doc .= '<div class="error">NetLogo: NetLogoLite.jar version not found: ' . $data['version'] . '</div>';
			return true;
		}

		// $src is currently realpath.  Turn into relative path from DokuWiki media folder
		$src = relativePath(DOKU_INC.'data/media/',$src);
		
		// Will pass token to servefile.php to authorize.  First generate secret uuid if not found.
		$uuidfile = 'data/tmp/plugin_netlogo_uuid';
		if (!file_exists($uuidfile)) {
			if (!$handle = fopen($uuidfile, 'w')) {
				$renderer->doc .= '<div class="error">NetLogo: Cannot create UUID ' . $uuidfile . '</div>';
				return true;
			}
			// Write uuid to our opened file.
			if (fwrite($handle, uuid4()) === FALSE) {
				$renderer->doc .= '<div class="error">NetLogo: Cannot write UUID to ' . $uuidfile . '</div>';
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
		$token=hash('sha256',$uuid.$src.$expires); // replace crypt() for more than first 8 chars [Rik, 2012-10-06]

		// special handling for center
		$pcenter = false;
		if (!is_null($data['align']) && $data['align']==='center') {
			$pcenter = true;
			$data['align']=null;
		}
		$codebase = 'data/tmp/'; // try find path to media folder for includes [Rik, 2013-09-21]
		$cheat = '/~rikblok/wiki/';
		/*
			codebase
			revision 3 - .nlogo file without includes works.  How about with nls?
		*/
		
		if ($pcenter) $renderer->doc .= '<p align="center">';
		$renderer->doc .= '<applet code="org.nlogo.lite.Applet"'
								. '    codebase="'.$cheat.$codebase.'"'						// is it possible to include .nls files in $src folder? [Rik, 2013-09-21]
								. '    archive="'.$cheat.'lib/plugins/netlogo/libraries/'.$data['version'].'/NetLogoLite.jar"'
								. '    width="'.$data['width'].'" height="'.$data['height'].'"';
		if (!is_null($data['align']))	$renderer->doc .= ' align="'.$data['align'].'"';
		if (!is_null($data['title']))	$renderer->doc .= ' alt="'.$data['title'].'"';
		$renderer->doc .= '>'
								. '  <param name="DefaultModel"'
								. '      value="'.$cheat.'lib/plugins/netlogo/inc/servefile.php?src='.urlencode($src).'&expires='.$expires.'&token='.urlencode($token).'">'
								. '  <param name="java_arguments"'
								. '      value="-Djnlp.packEnabled=true">'
								. '</applet>';
		if ($pcenter) $renderer->doc .= '</p>';
        return true;
    }
}

// vim:ts=4:sw=4:et:
