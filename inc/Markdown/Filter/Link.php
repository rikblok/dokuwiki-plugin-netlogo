<?php
/**
 * Copyright (C) 2011, Maxim S. Tsepkov
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Markdown;

require_once __DIR__ . '/../Filter.php';

/**
 * Translates links.
 *
 * Definitions:
 * <ul>
 *   <li>link text is delimited by [square brackets]</li>
 *   <li>inline-style URL is inside the parentheses with an optional title in quotes</li>
 *   <li>reference-style links use a second set of square brackets with link label</li>
 *   <li>link definitions can be placed anywhere in document</li>
 *   <li>link definition names may consist of letters, numbers, spaces, and punctuation
 *      — but they are not case sensitive</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage Filter
 * @author Igor Gaponov <jiminy96@gmail.com>
 * @version 1.0
 */
class Filter_Link extends Filter
{
    /**
     * Array with link definitions
     *
     * @var array
     */
    protected $_urls = array();

    /**
     * Array with titles of link definitions
     *
     * @var array
     */
    protected $_titles = array();

    /**
     * Mark, that placed before brackets of link text,
     * null by default
     *
     * @var null
     */
    protected $_mark = null;

    /**
     * Format of the returned html code
     *
     * @var string
     */
    protected $_format = '<a href="%s"%s>%s</a>';

    /**
     * Pass given text through the filter and return result.
     *
     * @see Filter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(Text $text)
    {
        $result = (string) $text;

        $result = preg_replace_callback(
            '/^[ ]{0,3}\[(?P<id>.+)\]:[ \t]*\n?[ \t]*<?(?P<url>.+?)>?[ \t]*(?:\n?[ \t]*(?<=\s)[\'"(](?P<title>[^\n]*)[\'")][ \t]*)?(?:\n+|\Z)/m',
            array($this, 'extractLinkDefinitions'),
            $result
        );

        $result = preg_replace_callback(
            sprintf(
                '/%s\[(?P<text>(?>[^\[\]]+|\[(?>[^\[\]]+)*\])*)\][ ]?(?:\n[ ]*)?\[(?P<id>.*?)\]/xs',
                $this->_mark
            ),
            array($this, 'transformReference'),
            $result
        );

        $result = preg_replace_callback(
            sprintf(
                '/%s\[(?P<text>(?>[^\[\]]+|\[(?>[^\[\]]+)*\])*)\]\([ \t\n]*(?P<url><.+?>|.+?)[ \t\n]*(([\'"])(?P<title>.*?)\4[ \t\n]*)?\)/s',
                $this->_mark
            ),
            array($this, 'transformInline'),
            $result
        );

        $text->setText($result);

        return $text;
    }

    /**
     * Extract all link definitions from text
     * and place them in {@link $_urls}
     *
     * @param array
     * @return null
     */
    protected function extractLinkDefinitions($values) {
        $id = strtolower($values['id']);
        $url = trim($values['url'], '<>');
        $this->_urls[$id] = $this->encodeAttribute($url);
        if(isset($values['title'])) {
            $this->_titles[$id] = $this->encodeAttribute($values['title']);
        }

        return null;
    }

    /**
     * Takes a single markdown reference-style link
     * and returns its html equivalent.
     *
     * @param array
     * @return string
     */
    protected function transformReference($values) {
        $text = $values['text'];
        $id = $values['id'];
        if(empty($id)) {
            $id = $text;
        }
        $id = preg_replace('/[ ]?\n/', ' ', strtolower($id));
        if(isset($this->_urls[$id])) {
            $url = $this->_urls[$id];
            if(isset( $this->_titles[$id])) {
                $title = " title=\"{$this->_titles[$id]}\"";
            } else {
                $title = null;
            }
        } else {
            return $values[0];
        }

        return sprintf($this->_format, $url, $title, $text);
    }

    /**
     * Takes a single markdown inline-style link
     * and returns its html equivalent.
     *
     * @param array
     * @return string
     */
    protected function transformInline($values) {
        $text = $values['text'];
        $url = trim($values['url'], '<>');
        $url = $this->encodeAttribute($url);
        if(isset($values['title'])) {
            $title = sprintf(' title="%s"',
                $this->encodeAttribute($values['title']));
        } else {
            $title = null;
        }

        return sprintf($this->_format, $url, $title, $text);
    }

    /**
     * Encode text for a double-quoted HTML attribute
     *
     * @param string
     * @return string
     */
    protected function encodeAttribute($text) {
        return str_replace('"', '&quot;', $text);
    }
}
