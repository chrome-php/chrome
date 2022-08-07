<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\PageUtils;

abstract class PagePdfOptions
{
    /**
     * bool.
     *
     * Paper orientation. Defaults to false
     */
    public const landscape = 'landscape';

    /**
     * bool.
     *
     * Display header and footer. Defaults to false
     */
    public const displayHeaderFooter = 'displayHeaderFooter';

    /**
     * bool.
     *
     * Print background graphics. Defaults to false
     */
    public const printBackground = 'printBackground';

    /**
     * float.
     *
     * Scale of the webpage rendering. Defaults to 1
     */
    public const scale = 'scale';

    /**
     * float.
     *
     * Paper width in inches. Defaults to 8.5 inches. Defaults to 8.5
     */
    public const paperWidth = 'paperWidth';

    /**
     * float.
     *
     * Paper height in inches. Defaults to 11
     */
    public const paperHeight = 'paperHeight';

    /**
     * float.
     *
     * Top margin in inches. Defaults to 1cm (~0.4 inches)
     */
    public const marginTop = 'marginTop';

    /**
     * float.
     *
     * Bottom margin in inches. Defaults to 1cm (~0.4 inches)
     */
    public const marginBottom = 'marginBottom';

    /**
     * float.
     *
     * Left margin in inches. Defaults to 1cm (~0.4 inches)
     */
    public const marginLeft = 'marginLeft';

    /**
     * float.
     *
     * Right margin in inches. Defaults to 1cm (~0.4 inches)
     */
    public const marginRight = 'marginRight';

    /**
     * string.
     *
     * Paper ranges to print, one based, e.g., '1-5, 8, 11-13'.
     * Pages are printed in the document order, not in the order specified, and no more than once.
     * The page numbers are quietly capped to actual page count of the document, and ranges beyond the end of the document are ignored.
     * If this results in no pages to print, an error is reported. It is an error to specify a range with start greater than end.
     * Defaults to empty string, which implies the entire document is printed.
     */
    public const pageRanges = 'pageRanges';

    /**
     * bool.
     *
     * Whether to silently ignore invalid but successfully parsed page ranges, such as ‘3-2’. Defaults to false.
     */
    public const ignoreInvalidPageRanges = 'ignoreInvalidPageRanges';

    /**
     * string.
     *
     * HTML template for the print header.
     * Should be valid HTML markup with following classes used to inject printing values into them:
     *
     * - date: formatted print date
     * - title: document title
     * - url: document location
     * - pageNumber: current page number
     * - totalPages: total pages in the document
     *
     * For example, <span class=title></span> would generate span containing the title.
     */
    public const headerTemplate = 'headerTemplate';

    /**
     * string.
     *
     * HTML template for the print footer. Should use the same format as the headerTemplate.
     */
    public const footerTemplate = 'footerTemplate';

    /**
     * bool.
     *
     * Whether or not to prefer page size as defined by css.
     * Defaults to false, in which case the content will be scaled to fit the paper size.
     */
    public const preferCSSPageSize = 'preferCSSPageSize';
}
