<?php
/**
 * The Mail_mimePart class is used to create MIME E-mail messages
 *
 * This class enables you to manipulate and build a mime email
 * from the ground up. The Mail_Mime class is a userfriendly api
 * to this class for people who aren't interested in the internals
 * of mime mail.
 * This class however allows full control over the email.
 *
 * Compatible with PHP versions 4 and 5
 *
 * LICENSE: This LICENSE is in the BSD license style.
 * Copyright (c) 2002-2003, Richard Heyes <richard@phpguru.org>
 * Copyright (c) 2003-2006, PEAR <pear-group@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met:
 *
 * - Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * - Neither the name of the authors, nor the names of its contributors 
 *   may be used to endorse or promote products derived from this 
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF
 * THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Mail
 * @package   Mail_Mime
 * @author    Richard Heyes  <richard@phpguru.org>
 * @author    Cipriano Groenendal <cipri@php.net>
 * @author    Sean Coates <sean@php.net>
 * @author    Aleksander Machniak <alec@php.net>
 * @copyright 2003-2006 PEAR <pear-group@php.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id: mimePart.php 292738 2009-12-29 11:08:28Z alec $
 * @link      http://pear.php.net/package/Mail_mime
 */


/**
 * The Mail_mimePart class is used to create MIME E-mail messages
 *
 * This class enables you to manipulate and build a mime email
 * from the ground up. The Mail_Mime class is a userfriendly api
 * to this class for people who aren't interested in the internals
 * of mime mail.
 * This class however allows full control over the email.
 *
 * @category  Mail
 * @package   Mail_Mime
 * @author    Richard Heyes  <richard@phpguru.org>
 * @author    Cipriano Groenendal <cipri@php.net>
 * @author    Sean Coates <sean@php.net>
 * @author    Aleksander Machniak <alec@php.net>
 * @copyright 2003-2006 PEAR <pear-group@php.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Mail_mime
 */
class Mail_mimePart
{
    /**
    * The encoding type of this part
    *
    * @var string
    * @access private
    */
    var $_encoding;

    /**
    * An array of subparts
    *
    * @var array
    * @access private
    */
    var $_subparts;

    /**
    * The output of this part after being built
    *
    * @var string
    * @access private
    */
    var $_encoded;

    /**
    * Headers for this part
    *
    * @var array
    * @access private
    */
    var $_headers;

    /**
    * The body of this part (not encoded)
    *
    * @var string
    * @access private
    */
    var $_body;

    /**
    * Constructor.
    *
    * Sets up the object.
    *
    * @param string $body   The body of the mime part if any.
    * @param array  $params An associative array of parameters:
    *                content_type - The content type for this part eg multipart/mixed
    *                encoding     - The encoding to use, 7bit, 8bit,
    *                               base64, or quoted-printable
    *                cid          - Content ID to apply
    *                disposition  - Content disposition, inline or attachment
    *                dfilename    - Optional filename parameter for content disposition
    *                description  - Content description
    *                charset      - Character set to use
    *
    * @access public
    */
    function Mail_mimePart($body = '', $params = array())
    {
        if (!defined('MAIL_MIMEPART_CRLF')) {
            define(
                'MAIL_MIMEPART_CRLF',
                defined('MAIL_MIME_CRLF') ? MAIL_MIME_CRLF : "\r\n", true
            );
        }

        $contentType = array();
        $contentDisp = array();
        foreach ($params as $key => $value) {
            switch ($key) {
            case 'content_type':
                $contentType['type'] = $value;
                break;

            case 'encoding':
                $this->_encoding = $value;
                $headers['Content-Transfer-Encoding'] = $value;
                break;

            case 'cid':
                $headers['Content-ID'] = '<' . $value . '>';
                break;

            case 'disposition':
                $contentDisp['disp'] = $value;
                break;

            case 'dfilename':
                $contentDisp['filename'] = $value;
                $contentType['name'] = $value;
                break;

            case 'description':
                $headers['Content-Description'] = $value;
                break;

            case 'charset':
                $contentType['charset'] = $value;
                $contentDisp['charset'] = $value;
                break;

            case 'language':
                $contentType['language'] = $value;
                $contentDisp['language'] = $value;
                break;

            case 'location':
                $headers['Content-Location'] = $value;
                break;

            }
        }

        if (isset($contentType['type'])) {
            $headers['Content-Type'] = $contentType['type'];
            if (isset($contentType['name'])) {
                $headers['Content-Type'] .= ';' . MAIL_MIMEPART_CRLF;
                $headers['Content-Type'] .= $this->_buildHeaderParam(
                    'name', $contentType['name'], 
                    isset($contentType['charset']) ? $contentType['charset'] : 'US-ASCII', 
                    isset($contentType['language']) ? $contentType['language'] : null
                );
            }
            if (isset($contentType['charset'])) {
                $headers['Content-Type']
                    .= ';' . MAIL_MIMEPART_CRLF . " charset={$contentType['charset']}";
            }
        }

        if (isset($contentDisp['disp'])) {
            $headers['Content-Disposition'] = $contentDisp['disp'];
            if (isset($contentDisp['filename'])) {
                $headers['Content-Disposition'] .= ';' . MAIL_MIMEPART_CRLF;
                $headers['Content-Disposition'] .= $this->_buildHeaderParam(
                    'filename', $contentDisp['filename'], 
                    isset($contentDisp['charset']) ? $contentDisp['charset'] : 'US-ASCII', 
                    isset($contentDisp['language']) ? $contentDisp['language'] : null
                );
            }
        }


        // Default content-type
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/plain';
        }

        // Default encoding
        if (!isset($this->_encoding)) {
            $this->_encoding = '7bit';
        }

        // Assign stuff to member variables
        $this->_encoded  = array();
        $this->_headers  = $headers;
        $this->_body     = $body;
    }

    /**
     * encode()
     *
     * Encodes and returns the email. Also stores
     * it in the encoded member variable
     *
     * @return An associative array containing two elements,
     *         body and headers. The headers element is itself
     *         an indexed array.
     * @access public
     */
    function encode()
    {
        $encoded =& $this->_encoded;

        if (count($this->_subparts)) {
            $boundary = '=_' . md5(rand() . microtime());
            $crlf = MAIL_MIMEPART_CRLF;

            $this->_headers['Content-Type'] .= ";$crlf boundary=\"$boundary\"";

            $encoded['body'] = ''; 

            for ($i = 0; $i < count($this->_subparts); $i++) {
                $encoded['body'] .= '--' . $boundary . $crlf;
                $tmp = $this->_subparts[$i]->encode();
                foreach ($tmp['headers'] as $key => $value) {
                    $encoded['body'] .= $key . ': ' . $value . $crlf;
                }
                $encoded['body'] .= $crlf . $tmp['body'] . $crlf;
            }

            $encoded['body'] .= '--' . $boundary . '--' . $crlf;

        } else {
            $encoded['body'] = $this->_getEncodedData($this->_body, $this->_encoding);
        }

        // Add headers to $encoded
        $encoded['headers'] =& $this->_headers;

        return $encoded;
    }

    /**
     * &addSubPart()
     *
     * Adds a subpart to current mime part and returns
     * a reference to it
     *
     * @param string $body   The body of the subpart, if any.
     * @param array  $params The parameters for the subpart, same
     *                       as the $params argument for constructor.
     *
     * @return Mail_mimePart A reference to the part you just added. It is
     *                       crucial if using multipart/* in your subparts that
     *                       you use =& in your script when calling this function,
     *                       otherwise you will not be able to add further subparts.
     * @access public
     */
    function &addSubPart($body, $params)
    {
        $this->_subparts[] = new Mail_mimePart($body, $params);
        return $this->_subparts[count($this->_subparts) - 1];
    }

    /**
     * _getEncodedData()
     *
     * Returns encoded data based upon encoding passed to it
     *
     * @param string $data     The data to encode.
     * @param string $encoding The encoding type to use, 7bit, base64,
     *                         or quoted-printable.
     *
     * @return string
     * @access private
     */
    function _getEncodedData($data, $encoding)
    {
        switch ($encoding) {
        case '8bit':
        case '7bit':
            return $data;
            break;

        case 'quoted-printable':
            return $this->_quotedPrintableEncode($data);
            break;

        case 'base64':
            return rtrim(chunk_split(base64_encode($data), 76, MAIL_MIMEPART_CRLF));
            break;

        default:
            return $data;
        }
    }

    /**
     * quotedPrintableEncode()
     *
     * Encodes data to quoted-printable standard.
     *
     * @param string $input    The data to encode
     * @param int    $line_max Optional max line length. Should
     *                         not be more than 76 chars
     *
     * @return string Encoded data
     *
     * @access private
     */
    function _quotedPrintableEncode($input , $line_max = 76)
    {
        $lines  = preg_split("/\r?\n/", $input);
        $eol    = MAIL_MIMEPART_CRLF;
        $escape = '=';
        $output = '';

        while (list(, $line) = each($lines)) {

            $line    = preg_split('||', $line, -1, PREG_SPLIT_NO_EMPTY);
            $linlen     = count($line);
            $newline = '';

            for ($i = 0; $i < $linlen; $i++) {
                $char = $line[$i];
                $dec  = ord($char);

                if (($dec == 32) AND ($i == ($linlen - 1))) {
                    // convert space at eol only
                    $char = '=20';
                } elseif (($dec == 9) AND ($i == ($linlen - 1))) {
                    // convert tab at eol only
                    $char = '=09';
                } elseif ($dec == 9) {
                    ; // Do nothing if a tab.
                } elseif (($dec == 61) OR ($dec < 32 ) OR ($dec > 126)) {
                    $char = $escape . sprintf('%0X', $dec);
                } elseif (($dec == 46) AND (($newline == '')
                    || ((strlen($newline) + strlen("=2E")) >= $line_max))
                ) {
                    // Bug #9722: convert full-stop at bol,
                    // some Windows servers need this, won't break anything (cipri)
                    // Bug #11731: full-stop at bol also needs to be encoded
                    // if this line would push us over the line_max limit.
                    $char = '=2E';
                }

                // Note, when changing this line, also change the ($dec == 46)
                // check line, as it mimics this line due to Bug #11731
                // MAIL_MIMEPART_CRLF is not counted
                if ((strlen($newline) + strlen($char)) >= $line_max) {
                    // soft line break; " =\r\n" is okay
                    $output  .= $newline . $escape . $eol;
                    $newline  = '';
                }
                $newline .= $char;
            } // end of for
            $output .= $newline . $eol;
        }
        // Don't want last crlf
        $output = substr($output, 0, -1 * strlen($eol));
        return $output;
    }

    /**
     * _buildHeaderParam()
     *
     * Encodes the paramater of a header.
     *
     * @param string $name      The name of the header-parameter
     * @param string $value     The value of the paramter
     * @param string $charset   The characterset of $value
     * @param string $language  The language used in $value
     * @param int    $maxLength The maximum length of a line. Defauls to 75
     *
     * @return string
     *
     * @access private
     */
    function _buildHeaderParam($name, $value, $charset=null, $language=null, $maxLength=75)
    {
        // RFC 2045:
        // value needs encoding if contains non-ASCII chars or is longer than 78 chars
        if (!preg_match('#[^\x20-\x7E]#', $value) // ASCII characters
            && (!$charset || strtolower($charset) == 'us-ascii') // charset
            && (!$language || preg_match('/^(en|en-us)$/i', $language)) // language
        ) {
            $token_regexp = '#([^\x21,\x23-\x27,\x2A,\x2B,\x2D'
                . ',\x2E,\x30-\x39,\x41-\x5A,\x5E-\x7E])#';
            if (!preg_match($token_regexp, $value)) {
                // token
                if (strlen($name) + strlen($value) + 3 <= $maxLength) {
                    return " {$name}={$value}";
                }
            } else {
                // quoted-string
                $quoted = addcslashes($value, '\\"');
                if (strlen($name) + strlen($quoted) + 5 <= $maxLength) {
                    return " {$name}=\"{$quoted}\"";
                }
            }
        }

        // RFC2231:
        $encValue = preg_replace_callback(
            '/([^\x21,\x23,\x24,\x26,\x2B,\x2D,\x2E,\x30-\x39,\x41-\x5A,\x5E-\x7E])/',
            array($this, '_encodeReplaceCallback'), $value
        );
        $value = "$charset'$language'$encValue";

        $header = " {$name}*={$value}";
        if (strlen($header) <= $maxLength) {
            return $header;
        }

        $preLength = strlen(" {$name}*0*=");
        $maxLength = max(16, $maxLength - $preLength - 3);
        $maxLengthReg = "|(.{0,$maxLength}[^\%][^\%])|";

        $headers = array();
        $headCount = 0;
        while ($value) {
            $matches = array();
            $found = preg_match($maxLengthReg, $value, $matches);
            if ($found) {
                $headers[] = " {$name}*{$headCount}*={$matches[0]}";
                $value = substr($value, strlen($matches[0]));
            } else {
                $headers[] = " {$name}*{$headCount}*={$value}";
                $value = '';
            }
            $headCount++;
        }

        $headers = implode(';' . MAIL_MIMEPART_CRLF, $headers);
        return $headers;
    }

    /**
     * Callback function to replace extended characters (\x80-xFF) with their
     * ASCII values (RFC2231)
     *
     * @param array $matches Preg_replace's matches array
     *
     * @return string        Encoded character string
     * @access private
     */
    function _encodeReplaceCallback($matches)
    {
        return sprintf('%%%0X', ord($matches[1]));
    }

} // End of class
