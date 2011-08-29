<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains the Translation2_Container base class
 *
 * PHP versions 4 and 5
 *
 * LICENSE: Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Internationalization
 * @package   Translation2
 * @author    Lorenzo Alberton <l.alberton@quipo.it>
 * @copyright 2004-2005 Lorenzo Alberton
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   CVS: $Id: Container.php 305985 2010-12-05 22:55:33Z clockwerx $
 * @link      http://pear.php.net/package/Translation2
 */

/**
 * Base class for Translation2 drivers/containers
 *
 * Extend this class to provide custom containers.
 * Some containers are already bundled with the package.
 *
 * @category  Internationalization
 * @package   Translation2
 * @author    Lorenzo Alberton <l.alberton@quipo.it>
 * @copyright 2004-2005 Lorenzo Alberton
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link      http://pear.php.net/package/Translation2
 */
class Translation2_Container
{
    // {{{ Class vars

    /**
     * Additional options for the storage container
     * @var array
     */
    var $options = array();

    /**
     * @var array
     * @access private
     */
    var $currentLang = array();

    /**
     * @var array
     * @access private
     */
    var $langs = array();

    // }}}
    // {{{ Constructor

    /**
     * Constructor
     * Has to be overwritten by each storage class
     *
     * @access public
     */
    function Translation2_Container()
    {
    }

    // }}}
    // {{{ _parseOptions()

    /**
     * Parse options passed to the container class
     *
     * @param array $array options
     *
     * @return void
     * @access protected
     */
    function _parseOptions($array)
    {
        if (!is_array($array)) {
            return;
        }
        foreach ($array as $key => $value) {
            if (isset($this->options[$key])) {
                $this->options[$key] = $value;
            }
        }
    }

    // }}}
    // {{{ _getLangID()

    /**
     * Get a valid langID or raise an error when no valid language is set
     *
     * @param string $langID language ID
     *
     * @return string language ID or PEAR_Error on error
     * @access private
     */
    function _getLangID($langID)
    {
        if (!empty($langID) || (0 === $langID)) {
            return $langID;
        }
        if (!empty($this->currentLang['id']) || (0 === $this->currentLang['id'])) {
            return $this->currentLang['id'];
        }
        $msg = 'No valid language set. Use Translation2::setLang().';
        return $this->raiseError($msg, TRANSLATION2_ERROR_UNKNOWN_LANG);
    }

    // }}}
    // {{{ setCharset()

    /**
     * Set charset used to read/store the translations
     *
     * @param string $charset character set (encoding)
     *
     * @return PEAR_Error on error
     */
    function setCharset($charset)
    {
        if (method_exists($this->storage, 'setCharset')) {
            return $this->storage->setCharset($charset);
        }
        return $this->raiseError(TRANSLATION2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ setLang()

    /**
     * Sets the current language
     *
     * @param string $langID language ID
     *
     * @return array|PEAR_Error language information
     */
    function setLang($langID)
    {
        $res = $this->getLangs(); //load available languages, if not loaded yet
        if (PEAR::isError($res)) {
            return $res;
        }
        if (!array_key_exists($langID, $this->langs)) {
            return $this->raiseError('unknown language: "'.$langID.'"',
                                    TRANSLATION2_ERROR_UNKNOWN_LANG,
                                    PEAR_ERROR_RETURN,
                                    E_USER_WARNING);
        }
        $this->currentLang = $this->langs[$langID];
        return $this->langs[$langID];
    }

    // }}}
    // {{{ getLang()

    /**
     * Gets the current lang
     *
     * @param string $format what must be returned
     *
     * @return mixed array with current lang data or null if not set yet
     */
    function getLang($format = 'id')
    {
        return isset($this->currentLang['id']) ? $this->currentLang : null;
    }

    // }}}
    // {{{ getLangData()

    /**
     * Gets the array data for the lang
     *
     * @param string $langID language ID
     * @param string $format what must be returned
     *
     * @return mixed array with lang data or null if not available
     */
    function getLangData($langID, $format = 'id')
    {
        $langs = $this->getLangs('array');
        return isset($langs[$langID]) ? $langs[$langID] : null;
    }

    // }}}
    // {{{ getLangs()

    /**
     * Gets the available languages
     *
     * @param string $format ['array' | 'ids' | 'names' | 'encodings']
     *
     * @return array|PEAR_Error
     */
    function getLangs($format = 'array')
    {
        //if not cached yet, fetch langs data from the container
        if (empty($this->langs) || !count($this->langs)) {
            $res = $this->fetchLangs(); //container-specific method
            if (PEAR::isError($res)) {
                return $res;
            }
        }

        $tmp = array();
        switch ($format) {
        case 'array':
            foreach ($this->langs as $aLang) {
                $aLang['lang_id']  = $aLang['id'];
                $tmp[$aLang['id']] = $aLang;
            }
            break;
        case 'id':
        case 'ids':
            foreach ($this->langs as $aLang) {
                $tmp[] = $aLang['id'];
            }
            break;
        case 'encoding':
        case 'encodings':
            foreach ($this->langs as $aLang) {
                $tmp[] = $aLang['encoding'];
            }
            break;
        case 'name':
        case 'names':
        default:
            foreach ($this->langs as $aLang) {
                $tmp[$aLang['id']] = $aLang['name'];
            }
        }
        return $tmp;
    }

    // }}}
    // {{{ fetchLangs()

    /**
     * Fetch the available langs if they're not cached yet.
     * Containers should implement this method.
     *
     * @return PEAR_Error on error
     */
    function fetchLangs()
    {
        return $this->raiseError('method "fetchLangs" not supported',
                                 TRANSLATION_ERROR_METHOD_NOT_SUPPORTED);
    }

    // }}}
    // {{{ getPage()

    /**
     * Returns an array of the strings in the selected page
     * Containers should implement this method.
     *
     * @param string $pageID page/group ID
     * @param string $langID language ID
     *
     * @return array
     */
    function getPage($pageID = null, $langID = null)
    {
        return $this->raiseError('method "getPage" not supported',
                                 TRANSLATION_ERROR_METHOD_NOT_SUPPORTED);
    }

    // }}}
    // {{{ getOne()

    /**
     * Get a single item from the container, without caching the whole page
     * Containers should implement this method.
     *
     * @param string $stringID string ID
     * @param string $pageID   page/group ID
     * @param string $langID   language ID
     *
     * @return string
     */
    function getOne($stringID, $pageID = null, $langID = null)
    {
        return $this->raiseError('method "getOne" not supported',
                                 TRANSLATION_ERROR_METHOD_NOT_SUPPORTED);
    }

    // }}}
    // {{{ getStringID()

    /**
     * Get the stringID for the given string
     *
     * @param string $string string
     * @param string $pageID page/group ID
     *
     * @return string
     */
    function getStringID($string, $pageID = null)
    {
        return $this->raiseError('method "getStringID" not supported',
                                 TRANSLATION_ERROR_METHOD_NOT_SUPPORTED);
    }

    // }}}
    // {{{ raiseError()

    /**
     * Trigger a PEAR error
     *
     * @param string $msg    error message
     * @param int    $code   error code
     * @param int    $mode   PEAR error mode
     * @param int    $option error severity
     *
     * @return void|PEAR_Error
     * @access public
     */
    function raiseError($msg, $code, $mode = PEAR_ERROR_TRIGGER, $option = E_USER_WARNING)
    {
        if (isset($GLOBALS['_PEAR_default_error_mode'])) {
            $mode = $GLOBALS['_PEAR_default_error_mode'];
        }
        if (isset($GLOBALS['_PEAR_default_error_options'])) {
            $option = $GLOBALS['_PEAR_default_error_options'];
        }
        if ($mode == PEAR_ERROR_RETURN) {
            return PEAR::raiseError($msg, $code, $mode, $option);
        } else {
            PEAR::raiseError($msg, $code, $mode, $option);
        }
    }

    // }}}
}
?>