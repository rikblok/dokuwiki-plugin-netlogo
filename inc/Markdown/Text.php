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

require_once __DIR__ . '/Filter.php';

/**
 * Represents a piece of text.
 *
 * @todo Make object traverable instead of public $lines
 * @package Markdown
 * @subpackage Text
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class Text
{
    const NOMARKDOWN = 1;
    const CODEBLOCK  = 2;

    public $lines = array();
    public $lineflags = array();

    /**
     *
     * @param string $text
     */
    public function __construct($text = '')
    {
        $this->setText($text);
    }

    public function __toString()
    {
        return implode("\n", $this->lines);
    }

    /**
     * Breaks $str by newlines, multiplatform.
     *
     * @param string $str
     * @return array
     */
    public static function explode($str)
    {
        $str = explode("\n", $str);
        $str = array_map(function($str) { return trim($str, "\r"); }, $str);
        return $str;
    }

    public function insert($lines, $index)
    {
        if (!is_array($lines)) {
            $lines = array($lines);
        }

        $slice = array_splice($this->lines, $index);
        $this->lines = array_merge($this->lines, $lines, $slice);

        $newflags = array();
        $linescount = count($lines);
        foreach ($this->lineflags as $key => $val) {
            if ($key < $index) {
                $newflags[$key] = $val;
            }
            else {
                $newflags[$key + $linescount] = $val;
            }
        }
        $this->lineflags = $newflags;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getText()
    {
        return $this->lines;
    }

    /**
     *
     * @param array|string $text
     * @return Text
     */
    public function setText($text)
    {
        if (is_array($text)) {
            $this->lines = $text;
        }
        else {
            $this->lines = self::explode($text);
        }

        return $this;
    }
}
