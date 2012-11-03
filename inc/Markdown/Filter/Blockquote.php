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
 * Translate email-style blockquotes.
 *
 * Definitions:
 * <ul>
 *   <li>blockquote is indicated by < at the start of line</li>
 *   <li>blockquotes can be nested</li>
 *   <li>lazy blockquotes are allowed</li>
 *   <li>Blockquote ends with \n\n</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage Filter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class Filter_Blockquote extends Filter
{
    /**
     * Pass given text through the filter and return result.
     *
     * @see Filter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(Text $text)
    {
        foreach($this->searchQuotes((string) $text) as $quote) {
            $text->setText(str_replace($quote, $this->transformQuote($quote), (string) $text));
        }

        return $text;
    }

    /**
     * Search markdown for quotes and returns it untouched.
     *
     * @param string $text
     * @return array $quotes
     */
    protected function searchQuotes($text)
    {
        $quotes = array();

        $inQuote = false;
        $len = strlen($text);
        for ($pos = 0; $pos < $len; $pos++) {
            if (!$inQuote) {
                if ($text[$pos] == '>' && ($pos == 0 || $text[$pos-1] == "\n")) {
                    $inQuote  = true;
                    $quotes[] = '';
                    $quote    =& $quotes[count($quotes) - 1];
                }
            }

            if ($inQuote) {
                if ($text[$pos] == "\n" && $text[$pos-1] == "\n") {
                    $inQuote = false;
                }
                else {
                    $quote .= $text[$pos];
                }
            }
        }

        return $quotes;
    }

    /**
     * Recursive function takes a single markdown quote
     * and returns its html equivalent.
     *
     * @param string
     * @return string
     */
    protected function transformQuote($text)
    {
        $text = preg_replace('/^\s*>\s*/m', '', $text);

        foreach ($this->searchQuotes($text) as $quote) {
            $text = str_replace($quote, $this->transformQuote($quote), $text);
        }

        return "<blockquote>\n" . $text . "</blockquote>\n";
    }
}
