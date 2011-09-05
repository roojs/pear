<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 *
 * PHP versions 5
 *
 * LICENSE: This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; If not, see <http://www.gnu.org/licenses/>.
 *
 * @todo      Refactor sentinel conditions to show flow
 * @todo      Document EBNF of what each major block is actually doing
 * @todo      Document getToken/pushBack assumptions of each major block
 * @todo      Refactor into Expression classes, keeping the Tokenizer the same,
 *            outputting the same parse tree
 * @todo      we need to remember spaces, this is esential to determine whether
 *            it is a function call: "function("
 *            or just some expression: "ident ("
 * @category  Database
 * @package   SQL_Parser
 * @author    Erich Enke <erich.Enke@gmail.com>
 * @author    Brent Cook <busterbcook@yahoo.com>
 * @author    Jason Pell <jasonpell@hotmail.com>
 * @author    Lauren Matheson <inan@canada.com>
 * @author    John Griffin <jgriffin316@netscape.net>
 * @copyright 2002-2004 Brent Cook
 *            2005 Erich Enke
 * @license   http://www.gnu.org/licenses/lgpl.html GNU Lesser GPL 3
 * @version   CVS: $Id: Parser.php 313967 2011-07-30 15:46:36Z alan_k $
 * @link      http://pear.php.net/package/SQL_Parser
 * @since     File available since Release 0.1.0
 */

/**
 *
 */
require_once 'SQL/Parser/Lexer.php';

/**
 * A sql parser
 * 
 * 
 * @example
 * 
require_once 'SQL/Parser.php';
$parser = new SQL_Parser();
$struct = $parser->parse("SELECT a,b,c FROM Foo");
print_r($struct);
 *
 * @see       http://www.sjhannah.com/blog/?p=16
 * @category  Database
 * @package   SQL_Parser
 * @author    Brent Cook <busterbcook@yahoo.com>
 * @copyright 2002-2004 Brent Cook
 *            2005 Erich Enke
 * @license   http://www.gnu.org/licenses/lgpl.html GNU Lesser GPL 3
 * @version   Devel: 0.5
 * @link      http://pear.php.net/package/SQL_Parser
 * @since     File available since Release 0.1.0
 */
class SQL_Parser
{
    /**
     * @var    SQL_Parser_Lexer
     * @access public
     */
    public $lexer;

    /**
     * @var    string
     * @access public
     */
    public $token;

    /**
     * @var    array
     * @access public
     */
    public $functions = array();

    /**
     * @var    array
     * @access public
     */
    public $types = array();

    /**
     * @var    array
     * @access public
     */
    public $symbols = array();

    /**
     * @var    array
     * @access public
     */
    public $operators = array();

    /**
     * @var    array
     * @access public
     */
    public $synonyms = array();

    /**
     * @var    array
     * @access public
     */
    public $lexeropts = array();

    /**
     * @var    array
     * @access public
     */
    public $parseropts = array();

    /**
     * @var    array
     * @access public
     */
    public $comments = array();

    /**
     * @var    array
     * @access public
     */
    public $quotes = array();

    /**
     * @var    array
     * @access public
     */
    static public $dialects = array(
        'ANSI',
        'MySQL',
    );

    public $notes = array();

    /**
     *
     */
    const DIALECT_ANSI = 'ANSI';

    /**
     *
     */
    const DIALECT_MYSQL = 'MySQL';

    // {{{ function SQL_Parser($string = null, $dialect = 'ANSI')
    /**
     * Constructor
     *
     * @param string $string the SQL query to parse
     * @param string $dialect the SQL dialect
     * @uses  SQL_Parser::setDialect()
     * @uses  SQL_Parser::$lexer      W to create it
     * @uses  SQL_Parser::$symbols    R
     * @uses  SQL_Parser::$lexeropts  R
     * @uses  SQL_Parser_Lexer        to create an Object
     * @uses  SQL_Parser_Lexer::$symbols W to set it
     * @uses  is_string()
     */
    public function __construct($string = null, $dialect = 'ANSI')
    {
        $this->setDialect($dialect);

        if (is_string($string)) {
            $this->initLexer($string);
        }
    }
    // }}}

    function initLexer($string)
    {
        // Initialize the Lexer with a 3-level look-back buffer
        $this->lexer = new SQL_Parser_Lexer($string, 3, $this->lexeropts);
        $this->lexer->symbols  =& $this->symbols;
        $this->lexer->comments =& $this->comments;
        $this->lexer->quotes   =& $this->quotes;
    }

    // {{{ function setDialect($dialect)
    /**
     * loads SQL dialect specific data
     *
     * @param string $dialect the SQL dialect to use
     * @return mixed true on success, otherwise Error
     * @uses  in_array()
     * @uses  SQL_Parser::$dialects   R
     * @uses  SQL_Parser::$types      W to set it
     * @uses  SQL_Parser::$functions  W to set it
     * @uses  SQL_Parser::$operators  W to set it
     * @uses  SQL_Parser::$commands   W to set it
     * @uses  SQL_Parser::$synonyms   W to set it
     * @uses  SQL_Parser::$symbols    W to set it
     * @uses  SQL_Parser::$lexeropts  W to set it
     * @uses  SQL_Parser::$parseropts W to set it
     * @uses  SQL_Parser::raiseError()
     */
    public function setDialect($dialect)
    {
        if (! in_array($dialect, SQL_Parser::$dialects)) {
            throw new Exception('Unknown SQL dialect:' . $dialect);
        }

        include 'SQL/Parser/Dialect/' . $dialect . '.php';
        $this->types      = array_flip($dialect['types']);
        $this->functions  = array_flip($dialect['functions']);
        $this->operators  = array_flip($dialect['operators']);
        $this->commands   = array_flip($dialect['commands']);
        $this->synonyms   = $dialect['synonyms'];
        $this->symbols    = array_merge(
            $this->types,
            $this->functions,
            $this->operators,
            $this->commands,
            array_flip($dialect['reserved']),
            array_flip($dialect['conjunctions']));
        $this->lexeropts  = $dialect['lexeropts'];
        $this->parseropts = $dialect['parseropts'];
        $this->comments   = $dialect['comments'];
        $this->quotes     = $dialect['quotes'];

        return true;
    }
    // }}}

    // {{{ getParams(&$values, &$types)
    /**
     * extracts parameters from a function call
     *
     * this function should be called if an opening brace is found,
     * so the first call to $this->getTok() will return first param
     * or the closing )
     *
     * @param array   &$values to set it
     * @param array   &$types  to set it
     * @param integer $i       position
     * @return mixed true on success, otherwise Error
     * @uses  SQL_Parser::$token  R
     * @uses  SQL_Parser::$lexer  R
     * @uses  SQL_Parser::getTok()
     * @uses  SQL_Parser::isVal()
     * @uses  SQL_Parser::raiseError()
     * @uses  SQL_Parser_Lexer::$tokText R
     */
    public function getParams(&$values, &$types, $i = 0)
    {
        $values = array();
        $types  = array();

        // the first opening brace is already fetched
        // function(
        $open_braces = 1;

        while ($open_braces > 0) {
            $this->getTok();

            if ($this->token === ')') {
                $open_braces--;
            } elseif ($this->token === '(') {
                $open_braces++;
            } elseif ($this->token === ',') {
                $i++;
            } elseif (isset($values[$i])) {
                $values[$i] .= '' . $this->lexer->tokText;
                $types[$i]  .= $this->token;
            } else {
                $values[$i] = $this->lexer->tokText;
                $types[$i]  = $this->token;
            }
        }

        return true;
    }
    // }}}

    // {{{ raiseError($message)
    /**
     *
     * @param string $message error message
     * @return Error
     * @uses  is_null()
     * @uses  substr()
     * @uses  strlen()
     * @uses  str_repeat()
     * @uses  abs()
     * @uses  SQL_Parser::$lexer      R
     * @uses  SQL_Parser::$token      R
     * @uses  SQL_Parser_Lexer::$string   R
     * @uses  SQL_Parser_Lexer::$lineBegin R
     * @uses  SQL_Parser_Lexer::$stringLen R
     * @uses  SQL_Parser_Lexer::$lineNo   R
     * @uses  SQL_Parser_Lexer::$tokText  R
     * @uses  SQL_Parser_Lexer::$tokPtr   R
     */
    public function raiseError($message)
    {
        $end = 0;
        if ($this->lexer->string != '') {
            while ($this->lexer->lineBegin + $end < $this->lexer->stringLen
             && $this->lexer->string{$this->lexer->lineBegin + $end} != "\n") {
                $end++;
            }
        }

        $message = 'Parse error: ' . $message . ' on line ' .
            ($this->lexer->lineNo + 1) . "\n";
        $message .= substr($this->lexer->string, $this->lexer->lineBegin, $end);
        $message .= "\n";
        $length   = is_null($this->token) ? 0 : strlen($this->lexer->tokText);
        $message .= str_repeat(' ', abs($this->lexer->tokPtr -
            $this->lexer->lineBegin - $length)) . "^";
        $message .= ' found: "' . $this->lexer->tokText . '"';

        throw new Exception($message);
    }
    // }}}

    // {{{ isType()
    /**
     * Returns true if current token is a variable type name, otherwise false
     *
     * @uses  SQL_Parser::$types  R
     * @uses  SQL_Parser::$token  R
     * @return  boolean  true if current token is a variable type name
     */
    public function isType()
    {
        return isset($this->types[$this->token]);
    }
    // }}}

    // {{{ isVal()
    /**
     * Returns true if current token is a value, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @return  boolean  true if current token is a value
     */
    public function isVal()
    {
        return ($this->token == 'real_val' ||
        $this->token == 'int_val' ||
        $this->token == 'text_val' ||
        $this->token == 'null');
    }
    // }}}

    // {{{ isFunc()
    /**
     * Returns true if current token is a function, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @uses  SQL_Parser::$functions R
     * @return  boolean  true if current token is a function
     */
    public function isFunc()
    {
        return isset($this->functions[$this->token]);
    }
    // }}}

    // {{{ isCommand()
    /**
     * Returns true if current token is a command, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @uses  SQL_Parser::$commands R
     * @return  boolean  true if current token is a command
     */
    public function isCommand()
    {
        return isset($this->commands[$this->token]);
    }
    // }}}

    // {{{ isReserved()
    /**
     * Returns true if current token is a reserved word, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @uses  SQL_Parser::$symbols R
     * @return  boolean  true if current token is a reserved word
     */
    public function isReserved()
    {
        return isset($this->symbols[$this->token]);
    }
    // }}}

    // {{{ isOperator()
    /**
     * Returns true if current token is an operator, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @uses  SQL_Parser::$operators R
     * @return  boolean  true if current token is an operator
     */
    public function isOperator()
    {
        return isset($this->operators[$this->token]);
    }
    // }}}

    // {{{ isEngineName()
    /**
     * Returns true if current token has a engine name for value, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @return  boolean  true if current token is a value
     */
    public function isEngineName()
    {
        $engines = array(
            'myisam',
            'innodb',
            'ibmdb2i',
            'merge',
            'memory',
            'example',
            'federated',
            'archive',
            'csv',
            'blackhole',
        );
        return in_array(strtolower($this->lexer->tokText), $engines);
    }
    // }}}

    // {{{ isCharsetName()
    /**
     * Returns true if current token has a charset name for value, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @return  boolean  true if current token has a charset name for  value
     */
    public function isCharsetName()
    {
        $charsets = array(
            'armscii8',
            'ascii',
            'big5',
            'binary',
            'cp1250',
            'cp1251',
            'cp1256',
            'cp1257',
            'cp850',
            'cp852',
            'cp866',
            'cp932',
            'dec8',
            'eucjpms',
            'euckr',
            'gb2312',
            'gbk',
            'geostd8',
            'greek',
            'hebrew',
            'hp8',
            'keybcs2',
            'koi8r',
            'koi8u',
            'latin1',
            'latin2',
            'latin5',
            'latin7',
            'macce',
            'macroman',
            'sjis',
            'swe7',
            'tis620',
            'ucs2',
            'ujis',
            'utf8',
        );
        return in_array(strtolower($this->lexer->tokText), $charsets);
    }
    // }}}

    // {{{ isCollationName()
    /**
     * Returns true if current token has a collation name for value, otherwise false
     *
     * @uses  SQL_Parser::$token  R
     * @return  boolean  true if current token has a collation name for value
     */
    public function isCollationName()
    {
        $collations = array(
            'armscii8_bin',
            'armscii8_general_ci',
            'ascii_bin',
            'ascii_general_ci',
            'big5_bin',
            'big5_chinese_ci',
            'binary',
            'cp1250_bin',
            'cp1250_croatian_ci',
            'cp1250_czech_cs',
            'cp1250_general_ci',
            'cp1251_bin',
            'cp1251_bulgarian_ci',
            'cp1251_general_ci',
            'cp1251_general_cs',
            'cp1251_ukrainian_ci',
            'cp1256_bin',
            'cp1256_general_ci',
            'cp1257_bin',
            'cp1257_general_ci',
            'cp1257_lithuanian_ci',
            'cp850_bin',
            'cp850_general_ci',
            'cp852_bin',
            'cp852_general_ci',
            'cp866_bin',
            'cp866_general_ci',
            'cp932_bin',
            'cp932_japanese_ci',
            'dec8_bin',
            'dec8_swedish_ci',
            'eucjpms_bin',
            'eucjpms_japanese_ci',
            'euckr_bin',
            'euckr_korean_ci',
            'gb2312_bin',
            'gb2312_chinese_ci',
            'gbk_bin',
            'gbk_chinese_ci',
            'geostd8_bin',
            'geostd8_general_ci',
            'greek_bin',
            'greek_general_ci',
            'hebrew_bin',
            'hebrew_general_ci',
            'hp8_bin',
            'hp8_english_ci',
            'keybcs2_bin',
            'keybcs2_general_ci',
            'koi8r_bin',
            'koi8r_general_ci',
            'koi8u_bin',
            'koi8u_general_ci',
            'latin1_bin',
            'latin1_danish_ci',
            'latin1_general_ci',
            'latin1_general_cs',
            'latin1_german1_ci',
            'latin1_german2_ci',
            'latin1_spanish_ci',
            'latin1_swedish_ci',
            'latin2_bin',
            'latin2_croatian_ci',
            'latin2_czech_cs',
            'latin2_general_ci',
            'latin2_hungarian_ci',
            'latin5_bin',
            'latin5_turkish_ci',
            'latin7_bin',
            'latin7_estonian_cs',
            'latin7_general_ci',
            'latin7_general_cs',
            'macce_bin',
            'macce_general_ci',
            'macroman_bin',
            'macroman_general_ci',
            'sjis_bin',
            'sjis_japanese_ci',
            'swe7_bin',
            'swe7_swedish_ci',
            'tis620_bin',
            'tis620_thai_ci',
            'ucs2_bin',
            'ucs2_czech_ci',
            'ucs2_danish_ci',
            'ucs2_esperanto_ci',
            'ucs2_estonian_ci',
            'ucs2_general_ci',
            'ucs2_hungarian_ci',
            'ucs2_icelandic_ci',
            'ucs2_latvian_ci',
            'ucs2_lithuanian_ci',
            'ucs2_persian_ci',
            'ucs2_polish_ci',
            'ucs2_roman_ci',
            'ucs2_romanian_ci',
            'ucs2_slovak_ci',
            'ucs2_slovenian_ci',
            'ucs2_spanish2_ci',
            'ucs2_spanish_ci',
            'ucs2_swedish_ci',
            'ucs2_turkish_ci',
            'ucs2_unicode_ci',
            'ujis_bin',
            'ujis_japanese_ci',
            'utf8_bin',
            'utf8_czech_ci',
            'utf8_danish_ci',
            'utf8_esperanto_ci',
            'utf8_estonian_ci',
            'utf8_general_ci',
            'utf8_hungarian_ci',
            'utf8_icelandic_ci',
            'utf8_latvian_ci',
            'utf8_lithuanian_ci',
            'utf8_persian_ci',
            'utf8_polish_ci',
            'utf8_roman_ci',
            'utf8_romanian_ci',
            'utf8_slovak_ci',
            'utf8_slovenian_ci',
            'utf8_spanish2_ci',
            'utf8_spanish_ci',
            'utf8_swedish_ci',
            'utf8_turkish_ci',
            'utf8_unicode_ci',
        );
        return in_array(strtolower($this->lexer->tokText), $collations);
    }
    // }}}

    // {{{ getTok()
    /**
     * retrieves next token
     *
     * @uses  SQL_Parser::$token  W to set it
     * @uses  SQL_Parser::$lexer  R
     * @uses  SQL_Parser_Lexer::lex()
     * @return void
     */
    public function getTok()
    {
        $this->token = $this->lexer->lex();
        //echo $this->token . "\t" . $this->lexer->tokText . "\n";
    }
    // }}}

    // {{{ &parseFieldOptions()
    /**
     * Parses field/column options, usually  for an CREATE or ALTER TABLE statement
     *
     * @uses  SQL_Parser::$token
     * @uses  SQL_Parser::getTok()
     * @uses  SQL_Parser::raiseError()
     * @uses  SQL_Parser::$lexer
     * @uses  SQL_Parser_Lexer::$tokText
     * @uses  SQL_Parser_Lexer::unget()
     * @uses  SQL_Parser::isVal()
     * @uses  SQL_Parser::isFunc()
     * @uses  SQL_Parser::parseFunctionOpts()
     * @uses  SQL_Parser::parseCondition()
     * @return  array   parsed field options
     */
    public function parseFieldOptions()
    {
        // parse field options
        $namedConstraint = false;
        $options         = array();
        while ($this->token != ',' && $this->token != ';' && $this->token != ')' && $this->token != null ) {
            $option    = $this->token;
            $haveValue = true;
            switch ($option) {
                case 'constraint':
                    $this->getTok();
                    if ($this->token != 'ident') {
                        $this->raiseError('Expected a constraint name');
                    }
                    $constraintName = $this->lexer->tokText;
                    $namedConstraint = true;
                    $haveValue = false;
                    $this->getTok();
                    break;
                case 'default':
                    $this->getTok();
                    if ($this->isVal()) {
                        $constraintOpts = array(
                            'type' => 'default_value',
                            'value' => $this->lexer->tokText,
                        );
                        $this->getTok();
                    } elseif ($this->isFunc()) {
                        $results = $this->parseFunctionOpts();
                        if (false === $results) {
                            return $results;
                        }
                        $results['type'] = 'default_function';
                        $constraintOpts  = $results;
                    } else {
                        $this->raiseError('Expected default value');
                    }

                    break;
                case 'primary':
                    $this->getTok();
                    if ($this->token != 'key') {
                        $this->raiseError('Expected "key"');
                    }
                    $constraintOpts = array(
                        'type'  => 'primary_key',
                        'value' => true,
                    );
                    $this->getTok();
                    break;
                case 'not':
                    $this->getTok();
                    if ($this->token != 'null') {
                        $this->raiseError('Expected "null"');
                    }
                    $constraintOpts = array(
                        'type'  => 'not_null',
                        'value' => true,
                    );
                    $this->getTok();
                    break;
                case 'check':
                    $this->getTok();
                    if ($this->token != '(') {
                        $this->raiseError('Expected (');
                    }

                    $this->getTok();
                    $results = $this->parseCondition();
                    if (false === $results) {
                        return $results;
                    }

                    $results['type'] = 'check';
                    $constraintOpts  = $results;
                    if ($this->token != ')') {
                        $this->raiseError('Expected )');
                    }
                    $this->getTok();
                    break;
                case 'unique':
                    $this->getTok();
                    if ($this->token != '(') {
                        $this->raiseError('Expected (');
                    }

                    $constraintOpts = array('type'=>'unique');
                    $this->getTok();
                    while ($this->token != ')') {
                        if ($this->token != 'ident') {
                            $this->raiseError('Expected an identifier');
                        }
                        $constraintOpts['column_names'][] = $this->lexer->tokText;
                        $this->getTok();
                        if ($this->token != ')' && $this->token != ',') {
                            $this->raiseError('Expected ) or ,');
                        }
                    }

                    if ($this->token != ')') {
                        $this->raiseError('Expected )');
                    }
                    $this->getTok();
                    break;
                case 'month':
                case 'year':
                case 'day':
                case 'hour':
                case 'minute':
                case 'second':
                    $intervals = array(
                        array(
                            'month' => 0,
                            'year'  => 1,
                        ),
                        array(
                            'second' => 0,
                            'minute' => 1,
                            'hour'   => 2,
                            'day'    => 3,
                        )
                    );

                    foreach ($intervals as $class) {
                        if (isset($class[$option])) {
                            $constraintOpts = array(
                                'quantum_1' => $this->token,
                            );
                            $this->getTok();
                            if ($this->token == 'to') {
                                $this->getTok();
                                if (! isset($class[$this->token])) {
                                    $this->raiseError(
                                        'Expected interval quanta');
                                }

                                if ($class[$this->token] >=
                                    $class[$constraintOpts['quantum_1']]
                                ) {
                                    $this->raiseError($this->token
                                        . ' is not smaller than ' .
                                        $constraintOpts['quantum_1']);
                                }
                                $constraintOpts['quantum_2'] = $this->token;
                            } else {
                                $this->lexer->unget();
                            }
                            $this->getTok();
                            break;
                        }
                    }
                    if (!isset($constraintOpts['quantum_1'])) {
                        $this->raiseError('Expected interval quanta');
                    }
                    $constraintOpts['type'] = 'values';
                    $this->getTok();
                    break;
                case 'null':
                    $haveValue = false;
                    $this->getTok();
                    break;
                case 'auto_increment':
                    $constraintOpts = array(
                        'type'  => 'auto_increment',
                        'value' => true,
                    );
                    $this->getTok();
                    break;
                default:
                    $this->raiseError('Unexpected token '
                            . $this->token . ': "' . $this->lexer->tokText . '"');
            }

            if ($haveValue) {
                if ($namedConstraint) {
                    $options['constraints'][$constraintName] = $constraintOpts;
                    $namedConstraint = false;
                } else {
                    $options['constraints'][] = $constraintOpts;
                }
            }

        }
        return $options;
    }
    // }}}

    // {{{ parseCondition()
    /**
     * parses conditions usually used in WHERE or ON
     *
     * @return  array   parsed condition
     * @uses  SQL_Parser::$token
     * @uses  SQL_Parser::$lexer
     * @uses  SQL_Parser::getTok()
     * @uses  SQL_Parser::raiseError()
     * @uses  SQL_Parser::getParams()
     * @uses  SQL_Parser::isFunc()
     * @uses  SQL_Parser::parseFunctionOpts()
     * @uses  SQL_Parser::parseCondition()
     * @uses  SQL_Parser::isReserved()
     * @uses  SQL_Parser::isOperator()
     * @uses  SQL_Parser::parseSelect()
     * @uses  SQL_Parser_Lexer::$tokText
     * @uses  SQL_Parser_Lexer::unget()
     * @uses  SQL_Parser_Lexer::pushBack()
     */
    public function parseCondition()
    {
        $clause = array();

        while (true) {
            // parse the first argument
            if ($this->token == 'not') {
                $clause['neg'] = true;
                $this->getTok();
            }

            if ($this->token == '(') {
                $this->getTok();
                $clause['args'][] = $this->parseCondition();
                if ($this->token != ')') {
                    $this->raiseError('Expected ")"');
                }
                $this->getTok();
            } elseif ($this->isFunc()) {
                $result = $this->parseFunctionOpts();
                if (false === $result) {
                    return $result;
                }
                $clause['args'][] = $result;
            } elseif ($this->token == 'ident') {
                $clause['args'][] = $this->parseIdentifier();
            } else {
                $arg = $this->lexer->tokText;
                $argtype = $this->token;
                $clause['args'][] = array(
                    'value' => $arg,
                    'type'  => $argtype,
                );
                $this->getTok();
            }

            if (! $this->isOperator()) {
                // no operator, return
                return $clause;
            }

            // parse the operator
            $op = $this->token;
            if ($op == 'not') {
                $this->getTok();
                $not = 'not ';
                $op = $this->token;
            } else {
                $not = '';
            }

            $this->getTok();
            switch ($op) {
                case 'is':
                    // parse for 'is' operator
                    if ($this->token == 'not') {
                        $op .= ' not';
                        $this->getTok();
                    }
                    $clause['ops'][] = $op;
                    break;
                case 'like':
                    $clause['ops'][] = $not . $op;
                    break;
                case 'between':
                    // @todo
                    //$clause['ops'][] = $not . $op;
                    //$this->getTok();
                    break;
                case 'in':
                    // parse for 'in' operator
                    if ($this->token != '(') {
                        $this->raiseError('Expected "("');
                    }

                    // read the subset
                    $this->getTok();
                    // is this a subselect?
                    if ($this->token == 'select') {
                        $clause['args'][] = $this->parseSelect(true);
                    } else {
                        $this->lexer->pushBack();
                        // parse the set
                        $result = $this->getParams($values, $types);
                        if (false === $result) {
                            return $result;
                        }
                        $clause['args'][] = array(
                            'values' => $values,
                            'types'  => $types,
                        );
                    }
                    if ($this->token != ')') {
                        $this->raiseError('Expected ")"');
                    }
                    break;
                case 'and':
                case 'or':
                    $clause['ops'][] = $not . $op;
                    continue;
                    break;
                default:
                    $clause['ops'][] = $not . $op;
            }
            // next argument [with operator]
        }

        return $clause;
    }
    // }}}

    public function parseSelectExpression()
    {
        $clause = array();

        $this->getTok();
        while (true) {
            // parse the first argument
            if ($this->token == 'not') {
                $clause['neg'] = true;
                $this->getTok();
            }

            if ($this->token == '(') {
                $this->getTok();
                $clause['args'][] = $this->parseCondition();
                if ($this->token != ')') {
                    $this->raiseError('Expected ")"');
                }
                $this->getTok();
            } elseif ($this->isFunc()) {
                $result = $this->parseFunctionOpts();
                if (false === $result) {
                    return $result;
                }
                $clause['args'][] = $result;
            } elseif ($this->token == 'ident') {
                $clause['args'][] = $this->parseIdentifier();
            } else {
                $arg = $this->lexer->tokText;
                $argtype = $this->token;
                $clause['args'][] = array(
                    'value' => $arg,
                    'type'  => $argtype,
                );
                $this->getTok();
            }

            if (! $this->isOperator()) {
                // no operator, return
                return $clause;
            }

            // parse the operator
            $op = $this->token;
            if ($op == 'not') {
                $this->getTok();
                $not = 'not ';
                $op = $this->token;
            } else {
                $not = '';
            }

            $this->getTok();
            switch ($op) {
                case 'is':
                    // parse for 'is' operator
                    if ($this->token == 'not') {
                        $op .= ' not';
                        $this->getTok();
                    }
                    $clause['ops'][] = $op;
                    break;
                case 'like':
                    $clause['ops'][] = $not . $op;
                    break;
                case 'between':
                    // @todo
                    //$clause['ops'][] = $not . $op;
                    //$this->getTok();
                    break;
                case 'in':
                    // parse for 'in' operator
                    if ($this->token != '(') {
                        $this->raiseError('Expected "("');
                    }

                    // read the subset
                    $this->getTok();
                    // is this a subselect?
                    if ($this->token == 'select') {
                        $clause['args'][] = $this->parseSelect(true);
                    } else {
                        $this->lexer->pushBack();
                        // parse the set
                        $result = $this->getParams($values, $types);
                        if (false === $result) {
                            return $result;
                        }
                        $clause['args'][] = array(
                            'values' => $values,
                            'types'  => $types,
                        );
                    }
                    if ($this->token != ')') {
                        $this->raiseError('Expected ")"');
                    }
                    break;
                case 'and':
                case 'or':
                    $clause['ops'][] = $not . $op;
                    continue;
                    break;
                default:
                    $clause['ops'][] = $not . $op;
            }
            // next argument [with operator]
        }

        return $clause;
    }

    // {{{ parseFieldList()
    /**
     * @access  public
     * @return mixed array parsed field list on success, otherwise Error
     */
    public function parseFieldList($allow_multiple = true, $expect = '(')
    {
        $fields = array();
        if ($expect !== false) {
            $this->getTok();
            if ($this->token != $expect) {
                $this->raiseError('Expected (');
            }
            $this->getTok();
        }
        while (1) {
            $new_data_fields = $this->parseField();
            $fields = array_merge_recursive($fields, $new_data_fields);

            if ($this->token == ',') {
                $this->getTok();
                if ($allow_multiple) {
                    continue;
                }
                // parsing alter field list - have to break on ','
                return $fields;
            }
            if ($this->token == ')') {
                $this->getTok();
                return $fields;
            } 
            if ($this->token == ';' || is_null($this->token)) {
                $this->getTok();
                return $fields;
            }
        }

        return $fields;
    }
    // }}}

    public function parseField()
    {
        $tree = array();
        $startTokenPtr = $this->lexer->tokPtr;
        /*
        try {
            $tree['constraint_defs'] = $this->parseConstraint();
            return $tree; // optimization
        } catch (Exception $err) {
            if ($startTokenPtr != $this->lexer->tokPtr) {
                throw $err;
            }
        }
         * 
         */
        try {
            $tree['index_defs'] = $this->parseIndex();
            return $tree; // optimization
        } catch (Exception $err) {
            if ($startTokenPtr != $this->lexer->tokPtr) {
                throw $err;
            }
        }
        try {
            $tree['column_defs'] = $this->parseColumn();
            return $tree; // optimization
        } catch (Exception $err) {
            // it was the right rule, but there was a mistake
            if ($startTokenPtr != $this->lexer->tokPtr) {
                throw $err;
            }
        }
        // /!\ all field data have to be merged recursively in
        // the calling environment to merge data of all fields
        return $tree;
    }

    public function parseColumn()
    {
        $fields = array();

        // In this context, field names can be reserved words or function names
        if ($this->token == 'primary') {
            $this->getTok();
            if ($this->token != 'key') {
                $this->raiseError('Expected key');
            }
            $this->getTok();
            if ($this->token != '(') {
                $this->raiseError('Expected (');
            }
            while (1) {
                $this->getTok();
                if ($this->token != 'ident') {
                    $this->raiseError('Expected identifier');
                }
                $name = $this->lexer->tokText;
                $this->getTok();

                if ($this->token == ')') {
                    $fields[$name]['constraints'][] = array(
                        'type'  => 'primary_key',
                        'value' => true,
                    );
                    // break;
                    return $fields;
                }
                if ($this->token == ',') {
                    $fields[$name]['constraints'][] = array(
                        'type'  => 'primary_key',
                        'value' => true,
                    );
                    // continue;
                    return $fields;
                }
                $this->raiseError('Expected )');

            }
            // continue;
            return $fields;
        }


        if ($this->token == 'key') {
            $this->getTok();
            if ($this->token != 'ident') {
                $this->raiseError('Expected identifier');
            }
            $key = $this->lexer->tokText;
            $this->getTok();
            if ($this->token != '(') {
                $this->raiseError('Expected (');
            }
            $this->getTok();
            $rows = array();
            while ($this->token != ')') {
                if ($this->token != 'ident') {
                    $this->raiseError('Expected identifier');
                }
                $name= $this->lexer->tokText;
                $fields[$name]['constraints'][] = array(
                    'type'  => 'key',
                    'value' => $key,
                );
                $this->getTok();
            }
            // continue;
            return $fields;
        }

        if ($this->token == ')') {
            return $fields;
        }

        if ($this->token == 'ident' || $this->isFunc() || $this->isReserved()) {
            $name = $this->lexer->tokText;
        }
        // else ??    //$this->raiseError('Expected identifier');

        // parse field type
        $this->getTok();
        if (! $this->isType($this->token)) {
            $this->raiseError('Expected a valid type');
        }
        $type = $this->token;

        $this->getTok();
        // handle special case two-word types
        if ($this->token == 'precision') {
            // double precision == double
            if ($type == 'double') {
                $this->raiseError('Unexpected token');
            }
            $this->getTok();
        } elseif ($this->token == 'varying') {
            // character varying() == varchar()
            if ($type != 'character' && $type != 'varchar') {
                $this->raiseError('Unexpected token');
            }
            $this->getTok();
        }


        $fields[$name]['type'] = $this->synonyms[$type];

        // parse type parameters
        if ($this->token == '(') {
            $results = $this->getParams($values, $types);
            if (false === $results) {
                return $results;
            }
            switch ($fields[$name]['type']) {
                case 'numeric':
                    if (isset($values[1])) {
                        if ($types[1] != 'int_val') {
                            $this->raiseError('Expected an integer');
                        }
                        $fields[$name]['decimals'] = $values[1];
                    }
                case 'float':
                    if ($types[0] != 'int_val') {
                        $this->raiseError('Expected an integer');
                    }
                    $fields[$name]['length'] = $values[0];
                    break;
                case 'char':
                case 'varchar':
                case 'integer':
                case 'int':
                case 'tinyint' :
                    if (sizeof($values) != 1) {
                        $this->raiseError('Expected 1 parameter');
                    }
                    if ($types[0] != 'int_val') {
                        $this->raiseError('Expected an integer');
                    }
                    $fields[$name]['length'] = $values[0];
                    break;
                case 'set':
                case 'enum':
                    if (! sizeof($values)) {
                        $this->raiseError('Expected a domain');
                    }
                    $fields[$name]['domain'] = $values;
                    break;
                default:
                    if (sizeof($values)) {
                        $this->raiseError('Unexpected )');
                    }
            }
            $this->getTok();
        }
        // parse field options..
        $options = $this->parseFieldOptions();
        if (false === $options) {
            return $options;
        }

        $fields[$name] += $options;

        return $fields;
    }

    public function parseIndex()
    {
        if ($this->token != 'index') {
            $this->raiseError('Expected index');
        }

        $this->getTok();
        if ($this->token != 'ident') {
            $this->raiseError('Expected identifier');
        }
        $name = $this->lexer->tokText;

        $this->getTok();
        // @todo type should be preceed with using and not directly.
        // 'ident' might be replaced with 'using'
        if ($this->token != '(' && $this->token != 'ident') {
            $this->raiseError('Expected ( or the index type');
        }
        if ($this->token == 'iden') {
            $type = $this->lexer->tokText;
            $this->getTok();
        }

        if ($this->token != '(') {
            $this->raiseError('Expected (');
        }
        $this->getTok();
        $cols = array();
        while ($this->token != ')') {
            if ($this->token != 'ident') {
                $this->raiseError('Expected identifier');
            }
            $cols [] = $this->lexer->tokText;
            $this->getTok();
        }
        $this->getTok();

        $index[$name] = array('columns' => $cols);
        $index[$name]['type'] = isset($type) ? $type : 'default';
        return $index;
    }


    public function parseTableOptions()
    {
        $options = array();
        $this->getTok();
        while (';' !== $this->token) {
            $option = $this->parseTableOption();
            $options [] = $option;
            if (',' === $this->token) {
                $this->getTok();
            }
        }
        return empty($options) ? false : $options;
    }


    public function parseTableOption($is_default = false)
    {
        $option = array();
        switch ($this->token) {
            case 'engine':
                // ENGINE [=] engine_name
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isEngineName()) {
                    $this->raiseError("Unknown engine name {$this->lexer->tokText}");
                }
                $option['option_name'] = 'engine';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'auto_increment':
                // AUTO_INCREMENT [=] value
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isVal()) {
                    $this->raiseError('Value expected');
                }
                $option['option_name'] = 'auto_increment';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'avg_row_length':
                // AVG_ROW_LENGTH [=] value
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isVal()) {
                    $this->raiseError('Value expected');
                }
                $option['option_name'] = 'avg_row_length';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'default':
                // [DEFAULT] CHARACTER SET [=] charset_name
                // [DEFAULT] COLLATE [=] collation_name
                $this->getTok();
                $option = $this->parseTableOption(true);
                break;
            case 'character':
                // [DEFAULT] CHARACTER SET [=] charset_name
                $this->getTok();
                if ('set' !== $this->token) {
                    $this->raiseError("Expected 'set' instead of {$this->lexer->tokText}");
                }
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isCharsetName()) {
                    $this->raiseError("Unknown charset name {$this->lexer->tokText}");
                }
                $option['option_name'] = 'character set';
                $option['value'] = $this->lexer->tokText;
                $option['is_default'] = $is_default;
                $this->getTok();
                break;
            case 'collate':
                // [DEFAULT] COLLATE [=] collation_name
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isCollationName()) {
                    $this->raiseError("Unknown collation name {$this->lexer->tokText}");
                }
                $option['option_name'] = 'collate';
                $option['value'] = $this->lexer->tokText;
                $option['is_default'] = $is_default;
                $this->getTok();
                break;
            case 'checksum':
                // CHECKSUM [=] {0 | 1}
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ('0' !== $this->lexer->tokText && '1' !== $this->lexer->tokText) {
                    $this->raiseError("Expected 0 or 1 instead of {$this->lexer->tokText}");
                }
                $option['option_name'] = 'checksum';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
/*
            @todo find the right algorithm to get comments, paths or
            connect_strings in all cases (escaped chars, same quote, etc)
            case 'comment':
                // COMMENT [=] 'string'
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                // @todo check that it doesn't run into an infinite loop
                // @todo manage escaped quote
                if ( ! in_array($this->token, $dialect['quotes'])) {
                    $this->raiseError("Expected a quote instead of {$this->token}");
                }
                $quote_type = $this->token;
                $this->getTok();
                $comment = '';
                while ($quote_type !== $this->token) {
                    $comment .= $this->token . ' ';
                    $this->getTok();
                }
                if (strlen($comment) > 0) {
                    $comment = substr($comment, 0, -1);
                }
                $option['option_name'] = 'comment';
                $option['value'] = $comment;
                break;
            case 'connection':
                // CONNECTION [=] 'connect_string'
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                break;
            case 'data':
                // DATA DIRECTORY [=] 'absolute path to directory'
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                break;
*/
            case 'delay_key_write':
                // DELAY_KEY_WRITE [=] {0 | 1}
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ('0' !== $this->lexer->tokText && '1' !== $this->lexer->tokText) {
                    $this->raiseError("Expected 0 or 1 instead of {$this->lexer->tokText}");
                }
                $option['option_name'] = 'delay_key_write';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
/*
            @todo same as above
            case 'index':
                // INDEX DIRECTORY [=] 'absolute path to directory'
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                break;
*/
            case 'insert_method':
                // INSERT_METHOD [=] { NO | FIRST | LAST }
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                $insert_methods = array('no', 'first', 'last');
                if ( ! in_array(strtolower($this->lexer->tokText), $insert_methods)) {
                    $this->raiseError("Expected no, first or last instead of {$this->lexer->tokText}");
                }
                $option['option_name'] = 'insert_method';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'key_block_size':
                // KEY_BLOCK_SIZE [=] value
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isVal()) {
                    $this->raiseError('Value expected');
                }
                $option['option_name'] = 'key_block_size';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'max_rows':
                // MAX_ROWS [=] value
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isVal()) {
                    $this->raiseError('Value expected');
                }
                $option['option_name'] = 'max_rows';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'min_rows':
                // MIN_ROWS [=] value
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                if ( ! $this->isVal()) {
                    $this->raiseError('Value expected');
                }
                $option['option_name'] = 'min_rows';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'pack_keys':
                // PACK_KEYS [=] {0 | 1 | DEFAULT}
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                $pack_keys_values = array('0', '1', 'default');
                if ( ! in_array(strtolower($this->lexer->tokText), $pack_keys_values)) {
                    $this->raiseError("Expected 0, 1 or default instead of {$this->lexer->tokText}");
                }
                $option['option_name'] = 'pack_keys';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
/*
            @todo same as above
            case 'password':
                // PASSWORD [=] 'string'
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                $option['option_name'] = '';
                $option['value'] = $this->token;
                break;
*/
            case 'row_format':
                // ROW_FORMAT [=] {DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT}
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                $row_formats = array('default','dynamic','fixed','compressed','redundant','compact');
                if ( ! in_array(strtolower($this->lexer->tokText), $row_formats)) {
                    $this->raiseError("Unexpected row format {$this->lexer->tokText}");
                }
                $option['option_name'] = 'row_format';
                $option['value'] = $this->lexer->tokText;
                $this->getTok();
                break;
            case 'tablespace':
                // TABLESPACE tablespace_name [STORAGE {DISK|MEMORY|DEFAULT}]
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                $option['option_name'] = 'tablespace';
                $option['value'] = $this->token;
                $this->getTok();
                if ('storage' === $this->token) {
                    $this->getTok();
                    $storage_modes = array('disk', 'memory', 'default');
                    if ( ! in_array(strtolower($this->lexer->tokText), $storage_modes)) {
                        $this->raiseError("Unexpected storage {$this->lexer->tokText}");
                    }
                    $option['storage'] = $this->lexer->tokText;
                    $this->getTok();
                }
                break;
/*
            @todo manage the delimiter ',' which is like the delimiter for options...
            case 'union':
                // UNION [=] (tbl_name[,tbl_name]...)
                $this->getTok();
                if ('=' === $this->token) {
                    $this->getTok();
                }
                $option['option_name'] = '';
                $option['value'] = $this->token;
                break;
*/
            default:
                $this->raiseError('Unknown table option');
        }
        return $option;
    }

    // {{{ parseFunctionOpts()
    /**
     * Parses parameters in a function call
     *
     * @access  public
     * @return mixed array parsed function options on success, otherwise Error
     */
    public function parseFunctionOpts()
    {
        $function     = $this->token;
        $opts['name'] = $function;
        $this->getTok();
        if ($this->token != '(') {
            $this->raiseError('Expected "("');
        }

        $this->getParams($opts['arg'], $opts['type']);
        if ($this->token != ')') {
            $this->raiseError('Expected ")"');
        }
        $this->getTok();

        return $opts;
    }
    // }}}

    // {{{ parseCreate()
    /**
     * @access  public
     * @return mixed array parsed create on success, otherwise Error
     */
    public function parseCreate()
    {
        $tree = array();

        $this->getTok();
        switch ($this->token) {
            case 'table':
                $tree['command'] = 'create_table';
                $this->getTok();
                if ($this->token != 'ident') {
                    $this->raiseError('Expected table name');
                }
                $tree['table_names'][] = $this->lexer->tokText;
                $fields = $this->parseFieldList();
                if (false === $fields) {
                    return $fields;
                }
                $tree = array_merge($tree, $fields);
                $options = $this->parseTableOptions();
                if (false !== $options) {
                    $tree['table_options'] = $options;
                }
                return $tree;
            case 'index':
                $tree['command'] = 'create_index';
                break;
            case 'constraint':
                $tree['command'] = 'create_constraint';
                break;
            case 'sequence':
                $tree['command'] = 'create_sequence';
                break;
            default:
                $this->raiseError('Unknown object to create');
        }

        $this->getTok();
        return $tree;
    }
    // }}}


    public function parseAlter()
    {
        $tree = array();

        $this->getTok();
        switch ($this->token) {
            case 'table':
                $tree['command'] = 'alter_table';
                $this->getTok();
                if ($this->token != 'ident') {
                    $this->raiseError('Expected table name');
                }
                $tree['table_names'] = array( $this->lexer->tokText );
                $tree['table_actions'] = array();

                $action = array();


                while (1) {

                    if ($this->token == ';' || $this->token == ',') {
                        $tree['table_actions'][] = $action;
                        $action = array();
                    }

                    if ($this->token == ';') {
                        return $tree;
                    }

                    $this->getTok();
                    // alter table ADD|CHANGE|..
                    $action['action'] = $this->token;
                    $this->getTok();
                    // alter table ADD COLUMN....
                    $action['what'] = $this->token;
                   // var_dump($action['what']);
                    switch ($action['what']) {

                        case 'column': // add / remove / 

                            if ($action['action'] == 'drop') {
                                $this->getTok();
                                $action['name'] = $this->lexer->tokText;
                                // fixmen check...
                                $this->getTok(); // comma or ;
                                if ($this->token != ';' && $this->token != ',') {
                                    $this->raiseError("expection ', or ;' got ".  $this->token);
                                }
                                break;
                            }

                            if ($action['action'] == 'change') {
                                $this->getTok();
                                $action['from'] = $this->lexer->tokText;
                            }

                            $fields = $this->parseFieldList(false, false);
                            foreach($fields as $k=>$v) {
                                $action['name'] = $k;
                                $action['field'] = $v;
                            }
                            //var_dump($this->token);
                            if ($this->token != ';' && $this->token != ',') {
                                $this->raiseError("expection ', or ;' got " . $this->token);
                            }

                            break;



                        case 'index':
                            // alter table xxx add index indexname(a,b,c);
                            $this->getTok();
                            $action['name'] = $this->lexer->tokText;

                            $this->getTok();
                            if ($this->token != '(') {
                                $this->raiseError("Expecting '(', got : ". $this->token);
                            }
                            // this needs more work.. let's start with just handling a lit of toeksn..
                            $action['indexes'] = array();
                            while(1) {
                                $this->getTok();
                                $action['indexes'][] = $this->lexer->tokText;
                                $this->getTok();
                                if ($this->token ==',') {
                                    continue;
                                }
                                if ($this->token ==')') {
                                    break;
                                }
                                $this->raiseError("Expecting ', or )' got " . $this->token);
                            }

                            // @ ), or ;

                            $this->getTok();
                            if ($this->token != ';' && $this->token != ',') {
                                $this->raiseError("expection ', or ;' got " . $this->token);
                            }

                            break;


                        default: 
                            $this->raiseError("do not know how to handle: " . $action['what']);
                    }// end switch..




                }
                // we never get here.. it should return from insind the loop.
                break;
            case 'index':
                $tree['command'] = 'alter_index';



                break;
            case 'constraint':
                $tree['command'] = 'alter_constraint';
                break;
            case 'sequence':
                $tree['command'] = 'alter_sequence';
                break;
            default:
                $this->raiseError('Unknown object to create');
        }


        throw new Exception("Can not handle ". $tree['command'] . " yet");
    }

    // {{{ parseInsert()
    // INSERT INTO tablename
    /**
     * @access  public
     * @return mixed array parsed insert on success, otherwise Error
     */
    public function parseInsert()
    {
        $this->getTok();
        if ($this->token != 'into') {
            $this->raiseError('Expected "into"');
        }
        $tree = array('command' => 'insert');

        $this->getTok();
        if ($this->token != 'ident') {
            $this->raiseError('Expected table name');
        }
        $tree['table_names'][] = $this->lexer->tokText;

        $this->getTok();
        if ($this->token == '(') {
            $results = $this->getParams($values, $types);
            if (false === $results) {
                return $results;
            } elseif (sizeof($values)) {
                $tree['column_names'] = $values;
            }
            $this->getTok();
        }

        if ($this->token != 'values') {
            $this->raiseError('Expected "values"');
        }

        // loop over all (value[, ...])[,(value[, ...]), ...]
        while (1) {
            // get opening brace '('
            $this->getTok();
            if ($this->token != '(') {
                $this->raiseError('Expected "("');
            }
            $results = $this->getParams($values, $types);
            if (false === $results) {
                return $results;
            }
            if (isset($tree['column_defs'])
             && sizeof($tree['column_defs']) != sizeof($values)) {
                $this->raiseError('field/value mismatch');
            }
            if (! sizeof($values)) {
                $this->raiseError('No fields to insert');
            }
            foreach ($values as $key => $value) {
                $values[$key] = array(
                    'value' => $value,
                    'type'  => $types[$key],
                );
            }
            $tree['values'][] = $values;

            $this->getTok();
            if ($this->token != ',') {
                return $tree;
            }
        }
    }
    // }}}

    // {{{ parseUpdate()
    /**
     * UPDATE tablename SET (colname = (value|colname) (,|WHERE searchclause))+
     *
     * @todo This is incorrect.  multiple where clauses would parse
     * @access  public
     * @return mixed array parsed update on success, otherwise Error
     */
    public function parseUpdate()
    {
        $tree = array('command' => 'update');
        $this->getTok();
        $tree['tables'][] = $this->parseIdentifier('table');

        if ($this->token != 'set') {
            $this->raiseError('Expected "set"');
        }

        while (true) {
            $this->getTok();
            $set['name'] = $this->parseIdentifier();

            if ($this->token != '=') {
                $this->raiseError('Expected =');
            }

            $this->getTok();
            $set['value'] = $this->parseCondition();

            $tree['sets'][] = $set;

            if ($this->token != ',') {
                break;
            }
        }

        if ($this->token == 'from') {
            $this->getTok();
            $tree['from'] = $this->parseFrom();
        }

        if ($this->token == 'where') {
            $this->getTok();
            $clause = $this->parseCondition();
            if (false === $clause) {
                return $clause;
            }
            $tree['where_clause'] = $clause;
        }

        return $tree;
    }
    // }}}

    public function parseTableFactor()
    {
        if ($this->token == '(') {
            $this->getTok();
            $tree = $this->parseTableReference();
            // closing )
            $this->getTok();
            return $tree;
        } elseif ($this->token == 'select') {
            return $this->parseSelect();
        } else {
            return $this->parseIdentifier('table');
        }
    }

    public function parseTableReference()
    {
        $tree = array();

        while (true) {
            $tree['table_factors'][] = $this->parseTableFactor();

            // join condition
            if ($this->token == 'on') {
                $this->getTok();
                $clause = $this->parseCondition();
                if (false === $clause) {
                    return $clause;
                }
                $tree['table_join_clause'][] = $clause;
            }

            // joins LEFT|RIGHT|INNER|OUTER|NATURAL|CROSS|STRAIGHT_JOIN
            if ($this->token == ',') {
                $tree['table_join'][] = $this->token;
                $this->getTok();
            } elseif ($this->token == 'straight_join') {
                $tree['table_join'][] = $this->token;
                $this->getTok();
            } elseif ($this->token == 'join') {
                $tree['table_join'][] = $this->token;
                $this->getTok();
            } elseif ($this->token == 'cross'
             || $this->token == 'inner') {
                // (CROSS|INNER) JOIN
                $join = $this->token;
                $this->getTok();
                if ($this->token != 'join') {
                    $this->raiseError('Expected token "join"');
                }
                $tree['table_join'][] = $join.' join';
                $this->getTok();
            } elseif ($this->token == 'left'
             || $this->token == 'right') {
                // {LEFT|RIGHT} [OUTER] JOIN
                $join = $this->token;

                $this->getTok();
                if ($this->token == 'outer') {
                    $join .= ' outer';
                    $this->getTok();
                }

                if ($this->token != 'join') {
                    $this->raiseError('Expected token "join"');
                }
                $tree['table_join'][] = $join.' join';

                $this->getTok();
            } elseif ($this->token == 'natural') {
                // NATURAL [{LEFT|RIGHT} [OUTER]] JOIN
                $join = $this->token;

                $this->getTok();
                if (($this->token == 'left')
                 || ($this->token == 'right')) {
                    $join .= ' ' . $this->token;
                    $this->getTok();
                }

                if ($this->token == 'outer') {
                    $join .= ' ' . $this->token;
                    $this->getTok();
                }

                if ($this->token == 'join') {
                    $tree['table_join'][] = $join.' join';
                } else {
                    $this->raiseError('Expected token "join"');
                }
                $this->getTok();
            } else {
                break;
            }
        }

        return $tree;
    }

    public function parseFrom()
    {
        $tree = array();

        $tree['table_references'] = $this->parseTableReference();

        return $tree;
    }

    // {{{ parseDelete()
    /**
     * DELETE FROM tablename WHERE searchclause
     *
     * @access  public
     * @return mixed array parsed delete on success, otherwise Error
     */
    public function parseDelete()
    {
        $tree = array('command' => 'delete');

        $this->getTok();
        if ($this->token == 'from') {
            // FROM is not required
            $this->getTok();
        }

        if ($this->token != 'ident') {
            $this->raiseError('Expected a table name');
        }
        $tree['table_names'][] = $this->lexer->tokText;

        $this->getTok();
        if ($this->token == 'where') {
            // WHERE is not required
            $this->getTok();
            $clause = $this->parseCondition();
            if (false === $clause) {
                return $clause;
            }
            $tree['where_clause'] = $clause;
        }

        return $tree;
    }
    // }}}

    // {{{ parseDrop()
    /**
     * @access  public
     * @return mixed array parsed drop on success, otherwise Error
     */
    public function parseDrop()
    {
        $this->getTok();

        switch ($this->token) {
            case 'table':
                $tree = array('command' => 'drop_table');
                $this->parseDropTable($tree);
                break;

            case 'index':
                $tree = array('command' => 'drop_index');
                $this->raiseError('DROP ' . $this->token . ' not supported yet');
                break;

            case 'constraint':
                $tree = array('command' => 'drop_constraint');
                $this->raiseError('DROP ' . $this->token . ' not supported yet');
                break;

            case 'sequence':
                $tree = array('command' => 'drop_sequence');
                $this->raiseError('DROP ' . $this->token . ' not supported yet');
                break;

            case 'function':
                $tree = array('command' => 'drop_function');
                $this->raiseError('DROP ' . $this->token . ' not supported yet');
                break;


            default:
                $this->raiseError('Unknown object to drop');
        }
        return $tree;
    }
    // }}}
    /**
     * parses SQL from right after DROP TABLE
     * 
     * @uses    SQL_Parser::getTok()
     * @uses    SQL_Parser::$token
     * @uses    SQL_Parser::$drop_table_options
     * @uses    SQL_Parser::$lexer
     * @uses    Lexer::$tokText
     * @param   array   $tree   the array to be filled with SQL information
     */
    public function parseDropTable(&$tree)
    {
        $this->getTok();
        $table_names_valid = true;
        while (null !== $this->token && $this->token != ';') {
            switch ($this->token) {
                case ',':
                    break;
                case 'restrict':
                    $tree['behaviors'][] = $this->token;
                    $table_names_valid = false;
                    break;
                case 'cascade':
                    $tree['behaviors'][] = $this->token;
                    $table_names_valid = false;
                    break;
                default:
                    $token_text = $this->lexer->tokText;
                    if ($option = $this->parseOption($this->drop_table_options)) {
                        $tree['command_options'][] = $option;
                    } elseif ($table_names_valid) {
                        $tree['table_names'][] = $token_text;
                    } else {
                        $this->raiseError('Unknown drop syntax '. $token_text);
                        $tree['unknown'][] = $token_text;
                    }
            }
            $this->getTok();
        }
    }

    /**
     * checks if the current token leads to a valid option (keyword)
     * supports multi word option like IF EXISTS
     * 
     * @recursiv
     * @uses    SQL_Parser::getTok()
     * @uses    SQL_Parser::_parseOption()
     * @uses    SQL_Parser::$lexer
     * @uses    Lexer::pushBack()
     * @uses    Lexer::$tokText
     * @uses    is_array()
     * @param   array   $valid_options  valid options to check $this->lexer->tokText against
     * @return  mixed   option name or false if not found
     */
    public function _parseOption($valid_options)
    {
        if (isset($valid_options[$this->lexer->tokText])) {
            if (is_array($valid_options[$this->lexer->tokText])) {
                $valid_options = $valid_options[$this->lexer->tokText];
                $this->getTok();
                $option = $this->_parseOption($valid_options);
                if (false === $option) {
                    $this->lexer->pushBack();
                }
                return $option;
            } else {
                return $valid_options[$this->lexer->tokText];
            }
        } else {
            return false;
        }
    }


    /**
     * [[db.].table].column [[AS] alias]
     */
    public function parseIdentifier($type = 'column')
    {
        $ident = array(
            'database' => '',
            'table'    => '',
            'column'   => '',
            'alias'    => '',
        );

        $ident['column'] = $this->lexer->tokText;
        $prevTok = $this->token;

        $this->getTok();
        if ($this->token == '.') {
            $this->getTok();
            $prevTok = $this->token;
            $ident['table']  = $ident['column'];
            $ident['column'] = $this->lexer->tokText;
            $this->getTok();
            if ($this->token == '.') {
                $this->getTok();
                $prevTok = $this->token;
                $ident['database'] = $ident['table'];
                $ident['table']    = $ident['column'];
                $ident['column']   = $this->lexer->tokText;
                $this->getTok();
            }
        }

        if ($prevTok != 'ident' && $prevTok != '*') {
            $this->raiseError('Expected name');
        }

        if ($type === 'table') {
            $ident['database'] = $ident['table'];
            $ident['table']    = $ident['column'];
            unset($ident['column']);
        }

        if ($this->token == 'as') {
            $this->getTok();
            if ($this->token != 'ident' ) {
                $this->raiseError('Expected column alias');
            }
            $ident['alias'] = $this->lexer->tokText;
            $this->getTok();
        } elseif ($this->token == 'ident') {
            $ident['alias'] = $this->lexer->tokText;
            $this->getTok();
        }

        return $ident;
    }

    // {{{ parseSelect()
    /**
     * @access  public
     * @return mixed array parsed select on success, otherwise Error
     */
    public function parseSelect($subSelect = false)
    {
        $tree = array('command' => 'select');
        $this->getTok();
        if ($this->token == 'distinct' || $this->token == 'all') {
            $tree['set_quantifier'] = $this->token;
            $this->getTok();
        }

        while (1) {
            $tree['select_expressions'][] = $this->parseCondition();
            if ($this->token != ',') {
                break;
            }
            $this->getTok();
        }

        // FROM
        if ($this->token != 'from') {
            return $tree;
        }

        $this->getTok();
        $tree['from'] = $this->parseFrom();

        // WHERE

        // GROUP BY

        // HAVING

        // ORDER BY

        // LIMIT

        // UNION
        while ($this->token != ';' && ! is_null($this->token) && (!$subSelect || $this->token != ')')
         && $this->token != ')') {
            switch ($this->token) {
                case 'where':
                    $this->getTok();
                    $clause = $this->parseCondition();
                    if (false === $clause) {
                        return $clause;
                    }
                    $tree['where_clause'] = $clause;
                    break;
                case 'order':
                    $this->getTok();
                    if ($this->token != 'by') {
                        $this->raiseError('Expected "by"');
                    }
                    $this->getTok();
                    while ($this->token == 'ident') {
                        $arg = $this->lexer->tokText;
                        $this->getTok();
                        if ($this->token == '.') {
                            $this->getTok();
                            if ($this->token == 'ident') {
                                $arg .= '.'.$this->lexer->tokText;
                            } else {
                                $this->raiseError('Expected a column name');
                            }
                        } else {
                            $this->lexer->pushBack();
                        }
                        $col = $arg;
                        //$col = $this->lexer->tokText;
                        $this->getTok();
                        if (isset($this->synonyms[$this->token])) {
                            $order = $this->synonyms[$this->token];
                            if (($order != 'asc') && ($order != 'desc')) {
                                $this->raiseError('Unexpected token');
                            }
                            $this->getTok();
                        } else {
                            $order = 'asc';
                        }
                        if ($this->token == ',') {
                            $this->getTok();
                        }
                        $tree['sort_order'][$col] = $order;
                    }
                    break;
                case 'limit':
                    $this->getTok();
                    if ($this->token != 'int_val') {
                        $this->raiseError('Expected an integer value');
                    }
                    $length = $this->lexer->tokText;
                    $start = 0;
                    $this->getTok();
                    if ($this->token == ',') {
                        $this->getTok();
                        if ($this->token != 'int_val') {
                            $this->raiseError('Expected an integer value');
                        }
                        $start  = $length;
                        $length = $this->lexer->tokText;
                        $this->getTok();
                    }
                    $tree['limit_clause'] = array('start'=>$start,
                    'length'=>$length);
                    break;
                case 'group':
                    $this->getTok();
                    if ($this->token != 'by') {
                        $this->raiseError('Expected "by"');
                    }
                    $this->getTok();
                    while ($this->token == 'ident') {
                        $arg = $this->lexer->tokText;
                        $this->getTok();
                        if ($this->token == '.') {
                            $this->getTok();
                            if ($this->token == 'ident') {
                                $arg .= '.'.$this->lexer->tokText;
                            } else {
                                $this->raiseError('Expected a column name');
                            }
                        } else {
                            $this->lexer->pushBack();
                        }
                        $col = $arg;
                        //$col = $this->lexer->tokText;
                        $this->getTok();
                        if ($this->token == ',') {
                            $this->getTok();
                        }
                        $tree['group_by'][] = $col;
                    }
                    break;
                default:
                    $this->raiseError('Unexpected clause');
            }
        }
        return $tree;
    }
    // }}}

    /**
     * tbl_name [[AS] alias] lock_type[, ...]
     */
    public function parseLock()
    {
        $tree = array('command' => 'lock tables');

        $this->getTok();
        if ($this->token != 'tables') {
            $this->raiseError('Expected tables');
        }

        while(1) {
            $this->getTok();
            $table = $this->parseIdentifier('table');
            if (false === $table) {
                return $table;
            }

            $lock = $this->parseLockType();
            if (false === $lock) {
                return $lock;
            }

            $lock['table'] = $table;
            $tree['locks'][] = $lock;

            if ($this->token != ',') {
                return $tree;
            }
        }
    }

    /**
     * READ [LOCAL] | [LOW_PRIORITY] WRITE
     */
    public function parseLockType()
    {
        $tree = array();

        if ($this->token == 'read') {
            $tree['type'] = $this->token;
            $this->getTok();
            if ($this->token == 'local') {
                $tree['option'] = $this->token;
                $this->getTok();
            }
            return $tree;
        }

        if ($this->token == 'low_priority') {
            $tree['option'] = $this->token;
            $this->getTok();
        }

        if ($this->token == 'write') {
            $tree['type'] = $this->token;
            $this->getTok();
        } else {
            $this->raiseError('Expected READ [LOCAL] | [LOW_PRIORITY] WRITE');
        }

        return $tree;
    }

    // {{{ parse($string)
    /**
     *
     * @return  array   parsed data
     * @uses  SQL_Parser::$lexeropts
     * @uses  SQL_Parser::$lexer
     * @uses  SQL_Parser::$symbols
     * @uses  SQL_Parser::$token
     * @uses  SQL_Parser::raiseError()
     * @uses  SQL_Parser::getTok()
     * @uses  SQL_Parser::parseSelect()
     * @uses  SQL_Parser::parseUpdate()
     * @uses  SQL_Parser::parseInsert()
     * @uses  SQL_Parser::parseDelete()
     * @uses  SQL_Parser::parseCreate()
     * @uses  SQL_Parser::parseDrop()
     * @uses  SQL_Parser_Lexer
     * @uses  SQL_Parser_Lexer::$symbols
     * @access  public
     */
    public function parseQuery()
    {
        $tree = array();

        // get query action
        $this->getTok();
        //var_Dump($this->token);
        while (1) {
            $branch = array();
            switch ($this->token) {
                case null:
                    // null == end of string
                    break;
                case 'select':
                    $branch = $this->parseSelect();
                    break;
                case 'update':
                    $branch = $this->parseUpdate();
                    break;
                case 'insert':
                    $branch = $this->parseInsert();
                    break;
                case 'delete':
                    $branch = $this->parseDelete();
                    break;
                case 'create':
                    $branch = $this->parseCreate();
                    break;
                case 'alter':
                    $branch = $this->parseAlter();
                    break;
                case 'drop':
                    $branch = $this->parseDrop();
                    break;
                case 'unlock':
                    $this->getTok();
                    if ($this->token != 'tables') {
                        $this->raiseError('Expected tables');
                    }

                    $this->getTok();
                    $branch = array('command' => 'unlock tables');
                    break;
                case 'lock':
                    $branch = $this->parseLock();
                    break;
                case '(':
                    $branch[] = $this->parseQuery();
                    if ($this->token != ')') {
                        $this->raiseError('Expected )');
                    }
                    $this->getTok();
                    break;
                default:
                    $this->raiseError('Unknown action: ' . $this->token);
            }
            $tree[] = $branch;

            // another command separated with ; or a UNION
            if ($this->token == ';') {
                $tree[] = ';';
                $this->getTok();
                if (! is_null($this->token)) {
                    continue;
                }
            }

            // another command separated with ; or a UNION
            if ($this->token == 'UNION') {
                $tree[] = 'UNION';
                $this->getTok();
                continue;
            }

            // end? unknown?
            break;
        }

        return $tree;
    }

    public function parse($string = null)
    {
        try {
            if (is_string($string)) {
                $this->initLexer($string);
            } elseif (! $this->lexer instanceof SQL_Parser_Lexer) {
                throw new Exception('No initial string specified');
                return array('empty' => true);
            }
        } catch (Exception $e) {
            return 'Caught exception on init: ' . $e->getMessage() . "\n";
        }

        try {
            $tree = $this->parseQuery();
            if (! is_null($this->token)) {
                $this->raiseError('Expected EOQ');
            }
        } catch (Exception $e) {
            $tree = "\n";
            $tree .= 'Caught exception: ' . $e->getMessage() . "\n";
            $tree .= 'in: ' . $e->getFile() . '#' . $e->getLine() . "\n";
            $tree .= 'from: ' . "\n" . $e->getTraceAsString();
            $tree .= "\n";
        }

        return $tree;
    }
    // }}}
}
