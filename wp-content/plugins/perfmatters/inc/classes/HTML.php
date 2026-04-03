<?php

namespace Perfmatters;

class HTML
{
    private static $offsets = [];

    public static function get_selector_elements($html, $selectors)
    {
        //convert selector array to regex string and escape for use inside regex (delimiter # so / in selectors is safe)
        if(is_array($selectors)) {
            $selectors = implode('|', array_map(function($s) { return preg_quote($s, '#'); }, $selectors));
        } else {
            $selectors = preg_quote($selectors, '#');
        }

        //find tags with selector
        if(!preg_match_all("#<(?:header|nav|div|section|figure|footer)([^>]*({$selectors})[^>]*)>#s", $html, $selector_tags, PREG_OFFSET_CAPTURE)) {
            return;
        }

        $elements = array();

        //get all tags
        preg_match_all('/<[^>]*>/', $html, $dom_tags, PREG_OFFSET_CAPTURE);

        //loop through selector tags
        foreach($selector_tags[0] as $key => $selector_tag) {

            //skip if selector tag is inside an element we already matched
            if(!empty(self::$offsets)) {
                foreach(self::$offsets as $offset) {
                    if($selector_tag[1] > $offset[0] && $selector_tag[1] < $offset[1]) {
                        continue 2;
                    }
                }
            }

            $stack = array();

            //loop through dom tags
            foreach($dom_tags[0] as $dom_tag) {

                //wait until current tag position is after the selector tag
                if($dom_tag[1] < $selector_tag[1]) {
                    continue;
                }

                //skip some tags by default, self-closing, style, script, etc...
                if(preg_match('/^<\/?(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr|script|style|circle|rect|ellipse|line|path|poly|use|view|stop|set|image|animate|!--|!DOCTYPE)/i', $dom_tag[0])) {
                    continue;
                }

                //make sure it's not a closing tag
                if(strpos($dom_tag[0], '</') === false) {

                    //add current tag to stack
                    array_push($stack, $dom_tag[0]);
                }
                else {

                    //remove last tag from stack
                    array_pop($stack);
                }

                //end of element
                if(empty($stack)) {

                    //get the length of the entire element we need
                    $length = $dom_tag[1] - $selector_tag[1] + strlen($dom_tag[0]);

                    //add element details to array
                    $elements[] = [
                        'html' => substr($html, $selector_tag[1], $length),
                        'selector' => $selector_tags[2][$key][0],
                        'selector_tag' => $selector_tag[0],
                        'selector_tag_atts' => $selector_tags[1][$key][0]
                    ];

                    //save the start and end positions of the element
                    self::$offsets[] = array($selector_tag[1], $selector_tag[1] + $length);

                    break;
                }
            }
        }

        //empty stored offsets
        self::$offsets = [];

        return $elements;
    }
}