<?php
// +----------------------------------------------------------------------+
// | PEAR :: Pager_Sliding                                                |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Lorenzo Alberton <l.alberton at quipo.it>                    |
// +----------------------------------------------------------------------+
//
// $Id: Sliding.php,v 1.19 2003/12/16 20:48:49 quipo Exp $
/**
 * File Sliding.php
 *
 * @package Pager_Sliding
 */
/**
 * Two constants used to guess the path- and file-name of the page
 * when the user doesn't set any pther value
 */
define('CURRENT_FILENAME', basename($_SERVER['PHP_SELF']));
define('CURRENT_PATHNAME', str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])));
/**
 * Error codes
 */
if (!defined('ERROR_PAGER_SLIDING_INVALID')) {
    define('ERROR_PAGER_SLIDING_INVALID', -1);
}
/**
 * Pager_Sliding - Generic data paging class  ("sliding window" style)
 *
 * Usage examples can be found in the doc provided
 *
 * @author  Lorenzo Alberton <l.alberton at quipo.it>
 * @version $Id: Sliding.php,v 1.19 2003/12/16 20:48:49 quipo Exp $
 * @package Pager_Sliding
 */
class Pager_Sliding
{

    // {{{ private class vars

    /**
     * @var integer number of items
     * @access private
     */
    var $_totalItems;

    /**
     * @var integer number of items per page
     * @access private
     */
    var $_perPage     = 10;

    /**
     * @var integer number of page links before and after the current one
     * @access private
     */
    var $_delta       = 2;

    /**
     * @var integer current page number
     * @access private
     */
    var $_currentPage = 1;

    /**
     * @var string CSS class for links
     * @access private
     */
    var $_linkClass   = '';

    /**
     * @var string wrapper for CSS class name
     * @access private
     */
    var $_classString = '';

    /**
     * @var string path name
     * @access private
     */
    var $_path        = CURRENT_PATHNAME;

    /**
     * @var string file name
     * @access private
     */
    var $_fileName    = CURRENT_FILENAME;

    /**
     * @var boolean you have to use FALSE with mod_rewrite
     * @access private
     */
    var $_append      = true;

    /**
     * @var string name of the querystring var for pageID
     * @access private
     */
    var $_urlVar      = 'pageID';

    /**
     * @var string name of the url without the pageID number
     * @access private
     */
    var $_url         = '';

    /**
     * @var string alt text for "previous page"
     * @access private
     */
    var $_altPrev     = 'previous page';

    /**
     * @var string alt text for "next page"
     * @access private
     */
    var $_altNext     = 'next page';

    /**
     * @var string alt text for "page"
     * @access private
     */
    var $_altPage     = 'page';

    /**
     * @var string image/text to use as "prev" link
     * @access private
     */
    var $_prevImg     = '&laquo;';

    /**
     * @var string image/text to use as "next" link
     * @access private
     */
    var $_nextImg     = '&raquo;';

    /**
     * @var boolean TRUE => expanded mode
     * @access private
     */
    var $_expanded    = true;

    /**
     * @var string link separator
     * @access private
     */
    var $_separator   = '|';

    /**
     * @var integer number of spaces before separator
     * @access private
     */
    var $_spacesBeforeSeparator = 3;

    /**
     * @var integer number of spaces after separator
     * @access private
     */
    var $_spacesAfterSeparator  = 3;

    /**
     * @var string CSS class name for current page link
     * @access private
     */
    var $_curPageLinkClassName  = '';

    /**
     * @var string Text before current page link
     * @access private
     */
    var $_curPageSpanPre        = '<b><u>';

    /**
     * @var string Text after current page link
     * @access private
     */
    var $_curPageSpanPost       = '</u></b>';

    /**
     * @var string Text before first page link
     * @access private
     */
    var $_firstPagePre  = '[';

    /**
     * @var string Text to be used for first page link
     * @access private
     */
    var $_firstPageText = '';

    /**
     * @var string Text after first page link
     * @access private
     */
    var $_firstPagePost = ']';

    /**
     * @var string Text before last page link
     * @access private
     */
    var $_lastPagePre   = '[';

    /**
     * @var string Text to be used for last page link
     * @access private
     */
    var $_lastPageText  = '';

    /**
     * @var string Text after last page link
     * @access private
     */
    var $_lastPagePost  = ']';

    /**
     * @var string Will contain the HTML code for the spaces
     * @access private
     */
    var $_spacesBefore  = '';

    /**
     * @var string Will contain the HTML code for the spaces
     * @access private
     */
    var $_spacesAfter   = '';

    /**
     * @var array data to be paged
     * @access private
     */
    var $_itemData      = null;

    /**
     * @var boolean If TRUE and there's only one page, links aren't shown
     * @access private
     */
    var $_clearIfVoid   = true;

    /**
     * @var boolean Use session for storing the number of items per page
     * @access private
     */
    var $_useSessions   = false;

    /**
     * @var boolean Close the session when finished reading/writing data
     * @access private
     */
    var $_closeSession  = false;

    /**
     * @var string name of the session var for number of items per page
     * @access private
     */
    var $_sessionVar    = 'setPerPage';

    /**
     * Pear error mode (when raiseError is called)
     * (see PEAR doc)
     *
     * @var int $_pearErrorMode
     */
    var $_pearErrorMode = null;

    // }}}

    /**
     * @var string Complete set of links
     * @access public
     */
    var $links = '';

    /**
     * @var array Array with a key => value pair representing
     *            page# => bool value (true if key==currentPageNumber).
     *            can be used for extreme customization.
     * @access public
     */
    var $range = array();

    // {{{ Pager_Sliding()

    /**
     * Constructor
     *
     * -------------------------------------------------------------------------
     * VALID options are (default values are set some lines before):
     *  - totalItems (int):    # of items to page.
     *  - perPage    (int):    # of items per page.
     *  - delta      (int):    # of page #s to show before and after the current
     *                         one
     *  - expanded   (bool):   if true, window size is always 2*delta+1
     *  - linkClass  (string): name of CSS class used for link styling.
     *  - append     (bool):   if true pageID is appended as GET value to the
     *                         URL - if false it is embedded in the URL
     *                         according to "fileName" specs
     *  - path       (string): complete path to the page (without the page name)
     *  - fileName   (string): name of the page, with a %d if append=true
     *  - urlVar     (string): name of pageNumber URL var, for example "pageID"
     *  - altPrev    (string): alt text to display for prev page, on prev link.
     *  - altNext    (string): alt text to display for next page, on next link.
     *  - altPage    (string): alt text to display before the page number.
     *  - prevImg    (string): sth (it can be text such as "<< PREV" or an
     *                         <img/> as well...) to display instead of "<<".
     *  - nextImg    (string): same as prevImg, used for NEXT link, instead of
     *                         the default value, which is ">>".
     *  - separator  (string): what to use to separate numbers (can be an
     *                         <img/>, a comma, an hyphen, or whatever.
     *  - spacesBeforeSeparator
     *               (int):    number of spaces before the separator.
     *  - firstPagePre (string):
     *                         string used before first page number (can be an
     *                         <img/>, a "{", an empty string, or whatever.
     *  - firstPageText (string):
     *                         string used in place of first page number
     *  - firstPagePost (string):
     *                         string used after first page number (can be an
     *                         <img/>, a "}", an empty string, or whatever.
     *  - lastPagePre (string):
     *                         similar to firstPagePre.
     *  - lastPageText (string):
     *                         similar to firstPageText.
     *  - lastPagePost (string):
     *                         similar to firstPagePost.
     *  - spacesAfterSeparator
     *               (int):    number of spaces after the separator.
     *  - curPageLinkClassName
     *               (string): name of CSS class used for current page link.
     *  - clearIfVoid(bool):   if there's only one page, don't display pager.
     *  - itemData   (array):  array of items to page.
     *  - useSessions (bool):  if true, number of items to display per page is
     *                         stored in the $_SESSION[$_sessionVar] var.
     *  - closeSession (bool): if true, the session is closed just after R/W.
     *  - sessionVar (string): name of the session var for perPage value.
     *                         A value != from default can be useful when
     *                         using more than one Pager istance in the page.
     *  - pearErrorMode (constant):
     *                         PEAR_ERROR mode for raiseError().
     *                         Default is PEAR_ERROR_RETURN.
     * -------------------------------------------------------------------------
     * REQUIRED options are:
     *  - fileName IF append==false (default is true)
     *  - itemData OR totalItems (if itemData is set, totalItems is overwritten)
     * -------------------------------------------------------------------------
     *
     * @param mixed $options    An associative array of option names and
     *                          their values.
     * @access public
     */
    function __construct($options = array())
    {
        $this->_setOptions($options);
        $this->_generatePageData();
        $this->_setFirstLastText();

        if ($this->_totalPages > (2 * $this->_delta + 1)) {
            $this->links .= $this->_printFirstPage();
        }

        $this->links .= $this->_getBackLink();
        $this->links .= $this->_getPageLinks();
        $this->links .= $this->_getNextLink();

        if ($this->_totalPages > (2 * $this->_delta + 1)) {
            $this->links .= $this->_printLastPage();
        }
    }

    // }}}
    // {{{ getPageData()

    /**
     * Returns an array of current pages data
     *
     * @param $pageID Desired page ID (optional)
     * @return array Page data
     * @access public
     */
    function getPageData($pageID = null)
    {
        if (isset($pageID)) {
            if (!empty($this->_pageData[$pageID])) {
                return $this->_pageData[$pageID];
            } else {
                return false;
            }
        }

        if (!isset($this->_pageData)) {
            $this->_generatePageData();
        }

        return $this->getPageData($this->_currentPage);
    }

    // }}}
    // {{{ getPageIdByOffset()

    /**
     * "Overload" PEAR::Pager method. VOID. Not needed here...
     * @param integer $index Offset to get pageID for
     * @deprecated
     * @access public
     */
    function getPageIdByOffset($index = null) { }

    // }}}
    // {{{ getOffsetByPageId()

    /**
     * Returns offsets for given pageID. Eg, if you
     * pass it pageID one and your perPage limit is 10
     * it will return you 1 and 10. PageID of 2 would
     * give you 11 and 20.
     *
     * @param integer PageID to get offsets for
     * @return array  First and last offsets
     * @access public
     */
    function getOffsetByPageId($pageid = null)
    {
        $pageid = isset($pageid) ? $pageid : $this->_currentPage;
        if (!isset($this->_pageData)) {
            $this->_generatePageData();
        }

        if (isset($this->_pageData[$pageid]) || is_null($this->_itemData)) {
            return array(
                        max(($this->_perPage * ($pageid - 1)) + 1, 1),
                        min($this->_totalItems, $this->_perPage * $pageid)
                   );
        } else {
            return array(0, 0);
        }
    }

    // }}}
    // {{{ getPageRangeByPageId()

    /**
     * Given a PageId, it returns the limits of the range of pages displayed.
     * While getOffsetByPageId() returns the offset of the data within the
     * current page, this method returns the offsets of the page numbers interval.
     * E.g., if you have pageId=5 and delta=2, it will return (3, 7).
     * PageID of 9 would give you (4, 8).
     * If the method is called without parameter, pageID is set to currentPage#.
     *
     * @param integer PageID to get offsets for
     * @return array  First and last offsets
     * @access public
     */
    function getPageRangeByPageId($pageid = null)
    {
        $pageid = isset($pageid) ? (int)$pageid : $this->_currentPage;
        if (!isset($this->_pageData)) {
            $this->_generatePageData();
        }
        if (isset($this->_pageData[$pageid]) || is_null($this->_itemData)) {
            if ($this->_expanded) {
                $min_surplus = ($pageid <= $this->_delta) ? ($this->_delta - $pageid + 1) : 0;
                $max_surplus = ($pageid >= ($this->_totalPages - $this->_delta)) ?
                                ($pageid - ($this->_totalPages - $this->_delta)) : 0;
            } else {
                $min_surplus = 0;
                $max_surplus = 0;
            }
            return array(   max($pageid - $this->_delta - $max_surplus, 1),
                            min($pageid + $this->_delta + $min_surplus, $this->_totalPages));
        } else {
            return array(0, 0);
        }
    }

    // }}}
    // {{{ getCurrentPageID()

    /**
     * Returns ID of current page
     *
     * @return integer ID of current page
     * @access public
     */
    function getCurrentPageID()
    {
        return $this->_currentPage;
    }

    // }}}
    // {{{ getNextPageID()

    /**
     * Returns next page ID. If current page is last page
     * this function returns FALSE
     *
     * @return mixed Next pages' ID
     * @access public
     */
    function getNextPageID()
    {
        return ($this->_currentPage == $this->_totalPages ?
                           false : $this->_currentPage + 1);
    }

    // }}}
    // {{{ getPreviousPageID()

    /**
     * Returns previous page ID. If current page is first page
     * this function returns FALSE
     *
     * @return mixed Previous pages' ID
     * @access public
     */
    function getPreviousPageID()
    {
        return $this->isFirstPage() ? false : $this->getCurrentPageID() - 1;
    }

    // }}}
    // {{{ numItems()

    /**
     * Returns number of items
     *
     * @return int Number of items
     * @access public
     */
    function numItems()
    {
        return $this->_totalItems;
    }

    // }}}
    // {{{ numPages()

    /**
     * Returns number of pages
     *
     * @return int Number of pages
     * @access public
     */
    function numPages()
    {
        return (int)$this->_totalPages;
    }

    // }}}
    // {{{ isFirstPage()

    /**
     * Returns whether current page is first page
     *
     * @return bool First page or not
     * @access public
     */
    function isFirstPage()
    {
        return ($this->_currentPage < 2);
    }

    // }}}
    // {{{ isLastPage()

    /**
     * Returns whether current page is last page
     *
     * @return bool Last page or not
     * @access public
     */
    function isLastPage()
    {
        return ($this->_currentPage == $this->_totalPages);
    }

    // }}}
    // {{{ isLastPageComplete()

    /**
     * Returns whether last page is complete
     *
     * @return bool Last page complete or not
     * @access public
     */
    function isLastPageComplete()
    {
        return !($this->_totalItems % $this->_perPage);
    }

    // }}}
    // {{{ getLinks()

    /**
     * Returns back/next/first/last and page links,
     * both as ordered and associative array.
     *
     * @param integer $pageID Optional pageID. If specified, links
     *                for that page are provided instead of current one.
     * @return array back/pages/next/first/last/all links
     * @access public
     */
    function getLinks($pageID = null)
    {
        if (!is_null($pageID)) {
            $_sav = $this->_currentPage;
            $this->_currentPage = $pageID;

            $this->links = '';
            if ($this->_totalPages > (2 * $this->_delta + 1)) {
                $this->links .= $this->_printFirstPage();
            }
            $this->links .= $this->_getBackLink();
            $this->links .= $this->_getPageLinks();
            $this->links .= $this->_getNextLink();
            if ($this->_totalPages > (2 * $this->_delta + 1)) {
                $this->links .= $this->_printLastPage();
            }
        }

        $back  = str_replace('&nbsp;', '', $this->_getBackLink());
        $next  = str_replace('&nbsp;', '', $this->_getNextLink());
        $pages = $this->_getPageLinks();
        $first = $this->_printFirstPage();
        $last  = $this->_printLastPage();
        $all   = $this->links;

        if ($pageID != null) {
            $this->_currentPage = $_sav;
        }

        return array(
                    $back,
                    $pages,
                    trim($next),
                    $first,
                    $last,
                    $all,
                    'back'  => $back,
                    'pages' => $pages,
                    'next'  => $next,
                    'first' => $first,
                    'last'  => $last,
                    'all'   => $all
                );
    }

    // }}}
    // {{{ getPerPageSelectBox()

    /**
     * Returns a string with a XHTML SELECT menu,
     * useful for letting the user choose how many items per page should be
     * displayed. If parameter useSessions is TRUE, this value is stored in
     * a session var. The string isn't echoed right now so you can use it
     * with template engines.
     *
     * @param integer $start
     * @param integer $end
     * @param integer $step
     * @return string xhtml select box
     * @access public
     */
    function getPerPageSelectBox($start=5, $end=30, $step=5)
    {
        $start = (int)$start;
        $end   = (int)$end;
        $step  = (int)$step;
        if (!empty($_SESSION[$this->_sessionVar])) {
            $selected = (int)$_SESSION[$this->_sessionVar];
        } else {
            $selected = $start;
        }

        $tmp = '<select name="'.$this->_sessionVar.'">';
        for ($i=$start; $i<=$end; $i+=$step) {
            $tmp .= '<option value="'.$i.'"';
            if ($i == $selected) {
                $tmp .= ' selected="selected"';
            }
            $tmp .= '>'.$i.'</option>';
        }
        $tmp .= '</select>';
        return $tmp;
    }

    // }}}
    // {{{ _getPageLinks()

    /**
     * Returns pages link
     *
     * @return string Links
     * @access private
     */
    function _getPageLinks()
    {
        $links = '';
        if ($this->_totalPages > (2 * $this->_delta + 1)) {
            if ($this->_expanded) {
                if (($this->_totalPages - $this->_delta) <= $this->_currentPage) {
                    $_expansion_before = $this->_currentPage - ($this->_totalPages - $this->_delta);
                } else {
                    $_expansion_before = 0;
                }
                for ($i = $this->_currentPage - $this->_delta - $_expansion_before; $_expansion_before; $_expansion_before--, $i++) {
                    if (($i != $this->_currentPage + $this->_delta)){ // && ($i != $this->_totalPages - 1)) {
                        $_print_separator_flag = true;
                    } else {
                        $_print_separator_flag = false;
                    }

                    $this->range[$i] = false;
                    $links .= sprintf('<a href="%s" %s title="%s">%d</a>',
                                        ( $this->_append ? $this->_url.$i : $this->_url.sprintf($this->_fileName, $i) ),
                                        $this->_classString,
                                        $this->_altPage.' '.$i,
                                        $i)
                           . $this->_spacesBefore
                           . ($_print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
                }
            }


            $_expansion_after = 0;
            for ($i = $this->_currentPage - $this->_delta; ($i <= $this->_currentPage + $this->_delta) && ($i <= $this->_totalPages); $i++) {
                if ($i<1) {
                    $_expansion_after++;
                    continue;
                }

                // check when to print separator
                if (($i != $this->_currentPage + $this->_delta) && ($i != $this->_totalPages )) {
                    $_print_separator_flag = true;
                } else {
                    $_print_separator_flag = false;
                }

                if ($i == $this->_currentPage) {
                    $this->range[$i] = true;
                    $links .= $this->_curPageSpanPre . $i . $this->_curPageSpanPost
                                 . $this->_spacesBefore
                                 . ($_print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
                } else {
                    $this->range[$i] = false;
                    $links .= sprintf('<a href="%s" %s title="%s">%d</a>',
                                        ( $this->_append ? $this->_url.$i : $this->_url.sprintf($this->_fileName, $i) ),
                                        $this->_classString,
                                        $this->_altPage.' '.$i,
                                        $i)
                                 . $this->_spacesBefore
                                 . ($_print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
                }
            }

            if ($this->_expanded && $_expansion_after) {
                $links .= $this->_separator . $this->_spacesAfter;
                for ($i = $this->_currentPage + $this->_delta +1; $_expansion_after; $_expansion_after--, $i++) {
                    if (($_expansion_after != 1)) {
                       $_print_separator_flag = true;
                    } else {
                        $_print_separator_flag = false;
                    }

                    $this->range[$i] = false;
                    $links .= sprintf('<a href="%s" %s title="%s">%d</a>',
                                        ( $this->_append ? $this->_url.$i : $this->_url.sprintf($this->_fileName, $i) ),
                                        $this->_classString,
                                        $this->_altPage.' '.$i,
                                        $i)
                           . $this->_spacesBefore
                           . ($_print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
                }
            }

        } else {
            //if $this->_totalPages <= (2*Delta+1) show them all
            for ($i=1; $i<=$this->_totalPages; $i++) {
                if ($i != $this->_currentPage) {
                    $this->range[$i] = false;
                    $links .= sprintf('<a href="%s" %s title="%s">%d</a>',
                                    ( $this->_append ? $this->_url.$i : $this->_url.sprintf($this->_fileName, $i) ),
                                    $this->_classString,
                                    $this->_altPage.' '.$i,
                                    $i);
                } else {
                    $this->range[$i] = true;
                    $links .= $this->_curPageSpanPre . $i . $this->_curPageSpanPost;
                }
                $links .= $this->_spacesBefore
                       . (($i != $this->_totalPages) ? $this->_separator.$this->_spacesAfter : '');
            }
        }

        if ($this->_clearIfVoid) {
            //If there's only one page, don't display links
            if ($this->_totalPages < 2) $links = '';
        }

        return $links;
    }

    // }}}
    // {{{ _getBackLink()

    /**
     * Returns back link
     *
     * @param  string $url  URL to use in the link
     * @param  string $link HTML to use as the link
     * @return string The link
     * @access private
     */
    function _getBackLink()   //function _getBackLink($url, $link = '<< Back')
    {
        if ($this->_currentPage > 1) {
            $back = sprintf('<a href="%s" %s title="%s">%s</a>',
                            ( $this->_append ? $this->_url.$this->getPreviousPageID() :
                                    $this->_url.sprintf($this->_fileName, $this->getPreviousPageID()) ),
                            $this->_classString,
                            $this->_altPrev,
                            $this->_prevImg)
                  . $this->_spacesBefore . $this->_spacesAfter;
        } else {
            $back = '';
        }
        return $back;
    }

    // }}}
    // {{{ _getNextLink()

    /**
     * Returns next link
     *
     * @return string The link
     * @access private
     */
    function _getNextLink()   //function _getNextLink($url, $link = 'Next >>')
    {
        if ($this->_currentPage < $this->_totalPages) {
            $next = $this->_spacesAfter
                . sprintf('<a href="%s" %s title="%s">%s</a>',
                            ( $this->_append ? $this->_url.$this->getNextPageID() :
                                    $this->_url.sprintf($this->_fileName, $this->getNextPageID()) ),
                            $this->_classString,
                            $this->_altNext,
                            $this->_nextImg)
                 . $this->_spacesBefore . $this->_spacesAfter;
        } else {
            $next = '';
        }
        return $next;
    }

    // }}}
    // {{{ _printFirstPage()

    /**
     * Print [1]
     *
     * @return string String with link to 1st page,
     *                or empty string if this is the 1st page.
     * @access private
     */
    function _printFirstPage()
    {
        if ($this->isFirstPage()) {
            return '';
        } else {
            return sprintf('<a href="%s" %s title="%s">%s%s%s</a>',
                            ( $this->_append ? $this->_url.'1' : $this->_url.sprintf($this->_fileName, 1) ),
                            $this->_classString,
                            $this->_altPage.' 1',
                            $this->_firstPagePre,
                            $this->_firstPageText,
                            $this->_firstPagePost)
                 . $this->_spacesBefore . $this->_spacesAfter;

        }
    }

    // }}}
    // {{{ _printLastPage()

    /**
     * Print [numPages()]
     *
     * @return string String with link to last page,
     *                or empty string if this is the 1st page.
     * @access private
     */
    function _printLastPage()
    {
        if ($this->isLastPage()) {
            return '';
        } else {
            return sprintf('<a href="%s" %s title="%s">%s%s%s</a>',
                            ( $this->_append ? $this->_url.$this->_totalPages : $this->_url.sprintf($this->_fileName, $this->_totalPages) ),
                            $this->_classString,
                            $this->_altPage.' '.$this->_totalPages,
                            $this->_lastPagePre,
                            $this->_lastPageText,
                            $this->_lastPagePost);
        }
    }

    // }}}
    // {{{ _generatePageData()

    /**
     * Calculates all page data
     *
     * @access private
     */
    function _generatePageData()
    {
        // Been supplied an array of data?
        if (!is_null($this->_itemData)) {
            $this->_totalItems = count($this->_itemData);
        }
        $this->_totalPages = ceil((float)$this->_totalItems / (float)$this->_perPage);
        $i = 1;
        if (!empty($this->_itemData)) {
            foreach ($this->_itemData as $key => $value) {
                $this->_pageData[$i][$key] = $value;
                if (count($this->_pageData[$i]) >= $this->_perPage) {
                    $i++;
                }
            }
        } else {
            $this->_pageData = array();
        }

        //prevent URL manual modification
        $this->_currentPage = min($this->_currentPage, $this->_totalPages);
    }


    // }}}
    // {{{ _setFirstLastText()

    /**
     * sets the private _firstPageText, _lastPageText variables
     * based on whether they were set in the options
     *
     * @access private
     */
    function _setFirstLastText()
    {
        if ($this->_firstPageText == '') {
            $this->_firstPageText = '1';
        }

        if ($this->_lastPageText == '') {
            $this->_lastPageText = $this->_totalPages;
        }
    }

    // }}}
    // {{{ _getLinksUrl()

    /**
     * Returns the correct link for the back/pages/next links
     *
     * @return string Url
     * @access private
     */
    function _getLinksUrl()
    {
        // Sort out query string to prevent messy urls
        $querystring = array();
        $qs = array();
        if (!empty($_SERVER['QUERY_STRING'])) {
            $qs = explode('&', str_replace('&amp;', '&', $_SERVER['QUERY_STRING']));
            for ($i=0, $cnt=count($qs); $i<$cnt; $i++) {
                list($name, $value) = explode('=', $qs[$i]);
                if ($name != $this->_urlVar) {
                    $qs[$name] = $value;
                }
                unset($qs[$i]);
            }
        }

        foreach ($qs as $name => $value) {
            $querystring[] = $name . '=' . $value;
        }

        return '?' . implode('&amp;', $querystring) . (!empty($querystring) ? '&amp;' : '') . $this->_urlVar .'=';
    }


    // }}}
    // {{{ raiseError()

    /**
     * conditionally includes PEAR base class and raise an error
     *
     * @param string $msg  Error message
     * @param int    $code Error code
     * @access private
     */
    function raiseError($msg, $code)
    {
        include_once 'PEAR.php';
        if (empty($this->_pearErrorMode)) {
            $this->_pearErrorMode = PEAR_ERROR_RETURN;
        }
        PEAR::raiseError($msg, $code, $this->_pearErrorMode);
    }

    // }}}
    // {{{ _setOptions()

    /**
     * Set and sanitize options
     *
     * @param mixed $options    An associative array of option names and
     *                          their values.
     * @access private
     */
    function _setOptions($options)
    {
        $allowed_options = array(
            'totalItems',
            'perPage',
            'delta',
            'linkClass',
            'path',
            'fileName',
            'append',
            'urlVar',
            'altPrev',
            'altNext',
            'altPage',
            'prevImg',
            'nextImg',
            'expanded',
            'separator',
            'spacesBeforeSeparator',
            'spacesAfterSeparator',
            'curPageLinkClassName',
            'firstPagePre',
            'firstPageText',
            'firstPagePost',
            'lastPagePre',
            'lastPageText',
            'lastPagePost',
            'itemData',
            'clearIfVoid',
            'useSessions',
            'closeSession',
            'sessionVar',
            'pearErrorMode'
        );

        foreach ($options as $key => $value) {
            if (in_array($key, $allowed_options) && ($value !== null)) {
                $this->{'_' . $key} = $value;
            }
        }

        $this->_fileName = ltrim($this->_fileName, '/');  //strip leading slash
        $this->_path     = rtrim($this->_path, '/');      //strip trailing slash

        if ($this->_append) {
            $this->_fileName = CURRENT_FILENAME; //avoid easy-verified user error;
            $this->_url = $this->_path.'/'.$this->_fileName.$this->_getLinksUrl();
        } else {
            $this->_url = $this->_path.'/';
            if (!strstr($this->_fileName,'%d')) {
                $msg = '<b>Pager_Sliding Error:</b>'
                      .' "fileName" format not valid. Use "%d" as placeholder.';
                return $this->raiseError($msg, ERROR_PAGER_SLIDING_INVALID);
            }
        }

        if (strlen($this->_linkClass)) {
            $this->_classString = 'class="'.$this->_linkClass.'"';
        } else {
            $this->_classString = '';
        }

        if (strlen($this->_curPageLinkClassName)) {
            $this->_curPageSpanPre  = '<span class="'.$this->_curPageLinkClassName.'">';
            $this->_curPageSpanPost = '</span>';
        }

        if ($this->_perPage < 1) {   //avoid easy-verified user error
            $this->_perPage = 1;
        }

        if ($this->_useSessions && !isset($_SESSION)) {
            session_start();
        }
        if (!empty($_REQUEST[$this->_sessionVar])) {
            $this->_perPage = max(1, (int)$_REQUEST[$this->_sessionVar]);

            if ($this->_useSessions) {
                $_SESSION[$this->_sessionVar] = $this->_perPage;
            }
        }

        if (!empty($_SESSION[$this->_sessionVar])) {
             $this->_perPage = $_SESSION[$this->_sessionVar];
        }

        if ($this->_closeSession) {
            session_write_close();
        }

        for ($i=0; $i<$this->_spacesBeforeSeparator; $i++) {
            $this->_spacesBefore .= '&nbsp;';
        }

        for ($i=0; $i<$this->_spacesAfterSeparator; $i++) {
            $this->_spacesAfter .= '&nbsp;';
        }

        if (isset($_GET[$this->_urlVar])) {
            $this->_currentPage = max((int)@$_GET[$this->_urlVar], 1);
        } else {
            $this->_currentPage = 1;
        }
    }

    // }}}
}
?>