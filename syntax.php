<?php
/**
 * RestructuredText plugin for DokuWiki.
 *
 * @license GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @version 1.0 - 2015-10-07
 * @author Chris Green <chris@isbd.co.uk>
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_rst extends DokuWiki_Syntax_Plugin {

    function getType() {
        return 'protected';
    }

    function getPType() {
        return 'block';
    }

    function getSort() {
        return 69;
    }

    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<rst>(?=.*</rst>)', $mode, 'plugin_rst');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</rst>', 'plugin_rst');
    }

    function handle($match, $state, $pos, &$handler) {
        switch ($state) {
            case DOKU_LEXER_ENTER :      return array($state, '');
            case DOKU_LEXER_UNMATCHED :  return array($match);
            case DOKU_LEXER_EXIT :       return array($state, '');
        }
        return array($state,'');
    }

    function render($mode, &$renderer, $data) {
        dbglog("mode is $mode, data[0] is $data[0]");
        if ($mode == 'xhtml' && $data[0] != DOKU_LEXER_ENTER && $data[0] != DOKU_LEXER_EXIT)
        {
            $descriptorspec = array(
               0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
               2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
            );

            $cwd = '/tmp';
            $env = NULL;

            $process = proc_open('rst2html --no-doc-title', $descriptorspec, $pipes, $cwd, $env);

            if (is_resource($process)) {
                // $pipes now looks like this:
                // 0 => writeable handle connected to child stdin
                // 1 => readable handle connected to child stdout
                // Any error output will be appended to /tmp/error-output.txt

                fwrite($pipes[0], $data[0]);
                fclose($pipes[0]);

                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                // It is important that you close any pipes before calling
                // proc_close in order to avoid a deadlock
                $return_value = proc_close($process);

            }
            $renderer->doc .= $output;
            return true;
        } else {
            return false;
        }
    }

    /*
    function render($mode, &$renderer, $data) {
        //dbg('function render($mode, &$renderer, $data)-->'.' mode = '.$mode.' data = '.$data);
        //dbg($data);
        if ($mode == 'xhtml') {
            list($state,$match) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :      break;    
                case DOKU_LEXER_UNMATCHED :
                    dbglog("In render, $match is:" . $match);                    
                    $match = $this->_toc($renderer, $match);
                    $renderer->doc .= $match;
                    break;
                case DOKU_LEXER_EXIT :       break;
            }
            return true;
        }else if ($mode == 'metadata') {
            //dbg('function render($mode, &$renderer, $data)-->'.' mode = '.$mode.' data = '.$data);
            //dbg($data);
            list($state,$match) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :      break;    
                case DOKU_LEXER_UNMATCHED :
                    if (!$renderer->meta['title']){
                        $renderer->meta['title'] = $this->_rst_header($match);
                    }
                    $this->_toc($renderer, $match);
                    $internallinks = $this->_internallinks($match);
                    #dbg($internallinks);
                    if (count($internallinks)>0){
                        foreach($internallinks as $internallink)
                        {
                            $renderer->internallink($internallink);
                        }
                    }
                    break;
                case DOKU_LEXER_EXIT :       break;
            }
            return true;
        } else {
            return false;
        }
    }
    */

    function _rst_header($text)
    {   
        $doc = new DOMDocument('1.0','UTF-8');
        //dbg($doc);
        $meta = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
        $doc->loadHTML($meta.$text);
        //dbg($doc->saveHTML());
        if ($nodes = $doc->getElementsByTagName('h1')){
            return $nodes->item(0)->nodeValue;
        }
        return false;
    }
    
    function _internallinks($text)
    {
        $links = array();
        if ( ! $text ) return $links;
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($text);
        if ($nodes = $doc->getElementsByTagName('a')){
            foreach($nodes as $atag)
            {
                $href = $atag->getAttribute('href');
                if (!preg_match('/^(https{0,1}:\/\/|ftp:\/\/|mailto:)/i',$href)){
                    $links[] = $href;
                }
            }
        }
        return $links;
    }
    
    function _toc(&$renderer, $text)
    {
        $doc = new DOMDocument('1.0','UTF-8');
        //dbg($doc);
        $meta = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
        $doc->loadHTML($meta.$text);
        if ($nodes = $doc->getElementsByTagName("*")){
            foreach($nodes as $node)
            {
                if (preg_match('/h([1-7])/',$node->tagName,$match))
                {
                    #dbg($node);
                    $node->setAttribute('class', 'sectionedit'.$match[1]);
                    $hid = $renderer->_headerToLink($node->nodeValue,'true');
                    $node->setAttribute('id',$hid);
                    $renderer->toc_additem($hid, $node->nodeValue, $match[1]);
                }
                
            }
        }
        //remove outer tags of content
        $html = $doc->saveHTML();
        $html = str_replace('<!DOCTYPE html>','',$html);
        $html = preg_replace('/.+<body>/', '', $html);
        $html = preg_replace('@</body>.*</html>@','', $html);
        return $html;
    }

}