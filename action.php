<?php
/**
 * DokuWiki Plugin rst (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris Green chris@isbd.co.uk>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_rst extends DokuWiki_Action_Plugin {

   function register(&$controller) {
      $controller->register_hook('PARSER_WIKITEXT_PREPROCESS',
'BEFORE', $this, 'handle_parser_wikitext_preprocess');
   }

   function handle_parser_wikitext_preprocess(&$event, $param) {
       global $ACT;
       global $ID;
       global $TEXT;
       // Check if file is a .md page:
       if(substr($ID,-4) != '.rst') return true;
       // Check for default view (in this case there is only 1 parsed text)
       // or check that the text parsed is the text being edited
       // (see: http://www.dokuwiki.org/devel:environment#text):
       if($ACT != 'show' && $event->data != $TEXT) return true;
       {
           $event->data = "<rst>\n".$event->data."\n</rst>";
       }
   }
}