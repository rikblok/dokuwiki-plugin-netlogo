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
 * Translate code blocks and spans.
 *
 * Definitions of code block:
 * <ul>
 *   <li>code block is indicated by indent at least 4 spaces or 1 tab</li>
 *   <li>one level of indentation is removed from each line of the code block</li>
 *   <li>code block continues until it reaches a line that is not indented</li>
 *   <li>within a code block, ampersands (&) and angle brackets (< and >)
 *      are automatically converted into HTML entities</li>
 * </ul>
 *
 * Definitions of code span:
 * <ul>
 *   <li>span of code is indicated by backtick quotes (`)</li>
 *   <li>to include one or more backticks the delimiters must
 *     contain multiple backticks</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage Filter
 * @author Max Tsepkov <max@garygolden.me>
 * @author Igor Gaponov <jiminy96@gmail.com>
 * @version 1.0
 */
class Filter_Code extends Filter
{
    /**
     * Flags lines containing codeblocks.
     * Other filters must avoid parsing markdown on that lines.
     *
     * @see \Markdown\Filter::preFilter()
     */
    public function preFilter(Text $text)
    {
        foreach($text->lines as $no => $line) {
            if (substr($line, 0, 4) === '    ' || @$line[0] == "\t") {
                @$text->lineflags[$no] |= Text::NOMARKDOWN | Text::CODEBLOCK;
            }
        }
    }

    /**
     * Pass given text through the filter and return result.
     *
     * @see Filter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(Text $text)
    {
        $text->setText(preg_replace_callback(
            '/(?:\n\n|\A\n?)(?P<code>(?>( {4}|\t).*\n+)+)((?=^ {0,4}\S)|\Z)/m',
            array($this, 'transformCodeBlock'),
            $text
        ));

        $text->setText(preg_replace_callback(
            '/(?<!\\\)(`+)(?!`)(?P<code>.+?)(?<!`)\1(?!`)/m',
            array($this, 'transformCode'),
            $text
        ));

        return $text;
    }

    /**
     * Takes a single markdown code block and returns its html equivalent.
     *
     * @param array
     * @return string
     */
    protected function transformCodeBlock($values)
    {
        $code = self::outdent($values['code']);
        $code = htmlspecialchars($code, ENT_NOQUOTES);
        $code = ltrim($code, "\n");
        $code = rtrim($code);

        return sprintf("\n\n<pre><code>%s\n</code></pre>\n\n", $code);
    }

    /**
     * Takes a single markdown code span
     * and returns its html equivalent.
     *
     * @param array
     * @return string
     */
    protected function transformCode($values)
    {
        $code = trim($values['code'], " \t");
        $code = htmlspecialchars($code, ENT_NOQUOTES);

        return sprintf("<code>%s</code>", $code);
    }
}
