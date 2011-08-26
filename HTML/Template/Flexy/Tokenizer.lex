<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors:  Alan Knowles <alan@akbkhome.com>                           |
// +----------------------------------------------------------------------+
//
// $Id: Tokenizer.lex 293237 2010-01-08 03:00:40Z alan_k $
//
//  The Source Lex file. (Tokenizer.lex) and the Generated one (Tokenizer.php)
// You should always work with the .lex file and generate by
//
// #mono phpLex/phpLex.exe Tokenizer.lex
// The lexer is available at http://sourceforge.net/projects/php-sharp/
// 
// or the equivialant .NET runtime on windows...
//
//  Note need to change a few of these defines, and work out
// how to modifiy the lexer to handle the changes..
//



define('HTML_TEMPLATE_FLEXY_TOKEN_NONE',1);
define('HTML_TEMPLATE_FLEXY_TOKEN_OK',2);
define('HTML_TEMPLATE_FLEXY_TOKEN_ERROR',3);

 

define("YYINITIAL"     ,0);
define("IN_SINGLEQUOTE"     ,   1) ;
define("IN_TAG"     ,           2)  ;
define("IN_ATTR"     ,          3);
define("IN_ATTRVAL"     ,       4) ;
define("IN_NETDATA"     ,       5);
define("IN_ENDTAG"     ,        6);
define("IN_DOUBLEQUOTE"     ,   7);
define("IN_MD"     ,            8);
define("IN_COM"     ,           9);
define("IN_DS",                 10);
define("IN_FLEXYMETHOD"     ,   11);
define("IN_FLEXYMETHODQUOTED"  ,12);
define("IN_FLEXYMETHODQUOTED_END" ,13);
define("IN_SCRIPT",             14);
define("IN_CDATA"     ,         15);
define("IN_DSCOM",              16);
define("IN_PHP",                17);
define("IN_COMSTYLE"     ,      18);

define('YY_E_INTERNAL', 0);
define('YY_E_MATCH',  1);
define('YY_BUFFER_SIZE', 4096);
define('YY_F' , -1);
define('YY_NO_STATE', -1);
define('YY_NOT_ACCEPT' ,  0);
define('YY_START' , 1);
define('YY_END' , 2);
define('YY_NO_ANCHOR' , 4);
define('YY_BOL' , 257);
define('YY_EOF' , 258);
   
%%
%namespace HTML_Template_Flexy_Tokenizer
%public
%class HTML_Template_Flexy_Tokenizer
%implements yyParser.yyInput 
%type int
%ignore_token  HTML_TEMPLATE_FLEXY_TOKEN_NONE
%eofval{
	return TOKEN_EOF;
%eofval}


%{
        
     
    /**
    * options array : meanings:
    *    ignore_html - return all tags as  text tokens
    *
    *
    * @var      boolean  public
    * @access   public
    */
    
    
    var $options = array(
        'ignore_html' => false,
        'token_factory'  => array('HTML_Template_Flexy_Token','factory'),
    );
     
    
    /**
    * flag if inside a style tag. (so comments are ignored.. )
    *
    * @var boolean
    * @access private
    */
    
    
    var $inStyle = false;
    
    /**
    * the start position of a cdata block
    *
    * @var int
    * @access private
    */
    
    var $yyCdataBegin = 0;
     /**
    * the start position of a comment block
    *
    * @var int
    * @access private
    */
    
    var $yyCommentBegin = 0;
    /**
    * the name of the file being parsed (used by error messages)
    *
    * @var string
    * @access public
    */
    
    
    var $fileName;
    
    /**
    * the string containing an error if it occurs..
    *
    * @var string
    * @access public
    */
    
    
    var $error;
    
    /**
    * Flexible constructor
    *
    * @param   string       string to tokenize
    * @param   array        options array (see options above)       
    * 
    *
    * @return   HTML_Template_Flexy_Tokenizer
    * @access   public
    */
  
    
    function &construct($data,$options= array()) 
    {
        $t = new HTML_Template_Flexy_Tokenizer($data);
        foreach($options as $k=>$v) {
            if (is_object($v) || is_array($v)) {
                $t->options[$k] = &$v;
                continue;
            }
            $t->options[$k] = $v;
        }
        return $t;
    }
    
    
    /**
    * raise an error: = return an error token and set the error variable.
    *
    * 
    * @param   string           Error type
    * @param   string           Full Error message
    * @param   boolean          is it fatal..
    *
    * @return   int the error token.
    * @access   public
    */
  
    
    function raiseError($s,$n='',$isFatal=false) 
    {
        $this->error = "ERROR $n in File {$this->fileName} on Line {$this->yyline} Position:{$this->yy_buffer_end}: $s\n";
        return HTML_TEMPLATE_FLEXY_TOKEN_ERROR;
    }
    
    /**
    * return text
    *
    * Used mostly by the ignore HTML code. - really a macro :)
    *
    * @return   int   token ok.
    * @access   public
    */
  
    function returnSimple() 
    {
        $this->value = $this->createToken('TextSimple');
        return HTML_TEMPLATE_FLEXY_TOKEN_OK;
    }
    
    /**
    * Create a token based on the value of $this->options['token_call']
    *
    *
    * @return   Object   some kind of token..
    * @access   public
    */
    function createToken($token, $value = false, $line = false, $charPos = false) 
    {
        if ($value === false) {
            $value = $this->yytext();
        }
        if ($line === false) {
            $line = $this->yyline;
        }
        if ($charPos === false) {
            $charPos = $this->yy_buffer_start;
        }
        return call_user_func_array($this->options['token_factory'],array($token,$value,$line,$charPos));
    }
   

%}

%line
%full
%char
%state IN_SINGLEQUOTE IN_TAG IN_ATTR IN_ATTRVAL IN_NETDATA IN_ENDTAG IN_DOUBLEQUOTE IN_MD IN_COM IN_DS IN_FLEXYMETHOD IN_FLEXYMETHODQUOTED IN_FLEXYMETHODQUOTED_END IN_SCRIPT IN_CDATA IN_DSCOM IN_PHP IN_COMSTYLE

 





DIGIT   =		[0-9]
LCLETTER =	[a-z]

UCLETTER =	[A-Z]
NONASCII =  [\200-\377]

LCNMCHAR	= [\._-]
UCNMCHAR	= [\._-]
RE		 = \n
RS		 = \r
SEPCHAR	 = \011
SPACECHAR	=	\040


COM 	="--"
CRO 	="&#"
DSC	    ="]"
DSO	    ="["
ERO 	="&"
ETAGO	="</"
LIT	    = \"
LITA    = "'"

/* ' hack comment to make syntax highlighting to work in scintilla*/

MDO	    = "<!"
MSC	    = "]]"
NET     = "/"
PERO    = "%"
PIC_PHP = "?>"
PIO	    = "<?"
REFC    = ";"
STAGO   = "<"
TAGC    = ">"


PIO_XML                 = ({PIO}[Xx][Mm][Ll])

NAME_START_CHARACTER    = ({LCLETTER}|{UCLETTER}|{NONASCII})
NAME_CHARACTER          = ({NAME_START_CHARACTER}|{DIGIT}|{LCNMCHAR}|{UCNMCHAR})
NAME_CHARACTER_WITH_NAMESPACE  = ({NAME_START_CHARACTER}|{DIGIT}|{LCNMCHAR}|{UCNMCHAR}|":")

NAME                    = ({NAME_START_CHARACTER}{NAME_CHARACTER}*)
NSNAME                  = ({NAME_START_CHARACTER}{NAME_CHARACTER_WITH_NAMESPACE}*)
NUMBER                  = {DIGIT}+
NUMBER_TOKEN            = {DIGIT}+{NAME_CHARACTER}*
NAME_TOKEN              = {NAME_CHARACTER}+

SPACE                   = ({SPACECHAR}|{RE}|{RS}|{SEPCHAR})
SPACES                  = ({SPACECHAR}|{RE}|{RS}|{SEPCHAR})+

WHITESPACE              = ({SPACECHAR}|{RE}|{RS}|{SEPCHAR})*

REFERENCE_END           = ({REFC}|{RE})
LITERAL                 = ({LIT}[^\"]*{LIT})|({LITA}[^\']*{LITA})


 
FLEXY_GTSTART       = "{_("
FLEXY_GTEND         = ")_}"

FLEXY_START         = ("%7B"|"%7b"|"{")
FLEXY_NEGATE        = "!"
FLEXY_SIMPLEVAR     = (({NAME_START_CHARACTER}|"_")({LCLETTER}|{UCLETTER}|"_"|{DIGIT}|{NONASCII})*)
FLEXY_ARRAY         = (("["|"%5B"|"%5b")({DIGIT}|{NAME_START_CHARACTER}|"_"|"-")+("]"|"%5D"|"%5d"))
FLEXY_VAR           = ({FLEXY_SIMPLEVAR}{FLEXY_ARRAY}*("."{FLEXY_SIMPLEVAR}{FLEXY_ARRAY}*)*)
FLEXY_METHOD        = ({FLEXY_SIMPLEVAR}|{FLEXY_SIMPLEVAR}{FLEXY_ARRAY}*("."{FLEXY_SIMPLEVAR}{FLEXY_ARRAY}*)*"."{FLEXY_SIMPLEVAR})
FLEXY_END           = ("%7D"|"%7d"|"}")
FLEXY_LITERAL       = [^#]*
FLEXY_MODIFIER      = ({NAME_START_CHARACTER}+)


END_SCRIPT          = {ETAGO}(S|s)(C|c)(r|R)(I|i)(P|p)(T|t){TAGC}
%%

// note (for above) - this is rather cool - it actually prevents calling quazi private 
// methods by not accepting _first methods or variables..   

// "


<YYINITIAL>{CRO}{NUMBER}{REFERENCE_END}?	 {
    // &#123;
    $this->value = $this->createToken('Text');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}


<YYINITIAL>{CRO}{NAME}{REFERENCE_END}?		{
    // &#abc;
    $this->value = $this->createToken('Text');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

  
<YYINITIAL>{ERO}{NAME}{REFERENCE_END}?	{
    // &abc;
    $this->value = $this->createToken('Text');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

  
<YYINITIAL>{ETAGO}{NSNAME}?{WHITESPACE}"/"{STAGO} {
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
   
    /* </name <  -- unclosed end tag */
    return $this->raiseError("Unclosed  end tag");
}

  
<YYINITIAL>{ETAGO}{NSNAME}{WHITESPACE} {
    /* </title> -- end tag */
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    if ($this->inStyle) {
        $this->inStyle = false;
    }
    $this->tagName = trim(substr($this->yytext(),1));
    $this->tokenName = 'EndTag';
    $this->yybegin(IN_ENDTAG);
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

 

<YYINITIAL>{ETAGO}{TAGC}        {
    /* </> -- empty end tag */  
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    return $this->raiseError("empty end tag not handled");

}
            
<YYINITIAL>{MDO}{NAME}{WHITESPACE}      {
    /* <!DOCTYPE -- markup declaration */
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    $this->value = $this->createToken('Doctype');
    $this->yybegin(IN_MD);
    
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

  
<YYINITIAL>{MDO}{TAGC}      {
    /* <!> */
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    return $this->raiseError("empty markup tag not handled"); 
}



<YYINITIAL>{MDO}{COM}           {
    /* <!--  -- comment declaration */
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    
    if ($this->inStyle) {
        $this->value = $this->createToken('Comment');
        $this->yybegin(IN_COMSTYLE);
        return HTML_TEMPLATE_FLEXY_TOKEN_OK;
    }
    
    $this->yyCommentBegin = $this->yy_buffer_end;
    //$this->value = $this->createToken('Comment',$this->yytext(),$this->yyline);
    $this->yybegin(IN_COM);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


<YYINITIAL>{MDO}{DSO}{WHITESPACE}   {
    /* <![ -- marked section */
    return $this->returnSimple();

}

<YYINITIAL>{MDO}{DSO}"CDATA"{DSO}     {
    /* <![ -- marked section */
    $this->value = $this->createToken('Cdata',$this->yytext(), $this->yyline);
    $this->yybegin(IN_CDATA);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<IN_CDATA>([^{DSC}"{"]+|{MSC}|{DSC}|"{") { 
    $this->value = $this->createToken('Cdata',$this->yytext(), $this->yyline);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
 
<IN_CDATA>{MSC}{TAGC}      { 
    /* ]]> -- marked section end */
    $this->value = $this->createToken('Cdata',$this->yytext(), $this->yyline);
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 

}


<YYINITIAL>{MSC}{TAGC}      { 
    /* ]]> -- marked section end */
    
    return $this->returnSimple();
}
  
<YYINITIAL>{PIO}{NAME}{WHITESPACE}		{ 
    /* eg. <?xml-stylesheet, <?php ... */
    
    $t = $this->yytext();
    
    $tagname = trim(strtoupper(substr($t,2)));
   // echo "STARTING XML? $t:$tagname\n";
    if ($tagname == 'PHP') {
        $this->yyPhpBegin = $this->yy_buffer_start;
        $this->yybegin(IN_PHP);
        return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    }
    // not php - it's xlm or something...
    // we treat this like a tag???
    // we are going to have to escape it eventually...!!!
    $this->tagName = trim(substr($t,1));
    $this->tokenName = 'Tag';
    $this->value = '';
    $this->attributes = array();
    $this->yybegin(IN_ATTR);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
     
}
   

<YYINITIAL>{PIO}{WHITESPACE} { 
    /* <? php start.. */
    //echo "STARTING PHP?\n";
    $this->yyPhpBegin = $this->yy_buffer_start;
    
    $this->yybegin(IN_PHP);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;

}

<IN_ATTR>{PIC_PHP}	{
    // <em^/ -- NET tag */
    $this->attributes["?"] = true;
    $this->value = $this->createToken($this->tokenName, array($this->tagName,$this->attributes));
        
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<IN_PHP>{PIC_PHP} { 
    /* php end */
    
    $this->value = $this->createToken('Php',
        substr($this->yy_buffer,$this->yyPhpBegin ,$this->yy_buffer_end - $this->yyPhpBegin ),
        $this->yyline,$this->yyPhpBegin);
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}


<IN_PHP>([^\?]*|"?"[^>]) {     
    /* anything inside of php tags */
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


<YYINITIAL>{STAGO}{NSNAME}{WHITESPACE}		{
    //<name -- start tag */
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    $this->tagName = trim(substr($this->yytext(),1));
    $this->tokenName = 'Tag';
    $this->value = '';
    $this->attributes = array();
    $this->yybegin(IN_ATTR);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


  
<YYINITIAL>{STAGO}{TAGC}			{  
    // <> -- empty start tag */
    if ($this->options['ignore_html']) {
        return $this->returnSimple();
    }
    return $this->raiseError("empty tag"); 
}

  
<YYINITIAL>([^\<\&\{\)]|(<[^<&a-zA-Z!->?])|(&[^<&#a-zA-Z]))+|"{"|")"     {
    //abcd -- data characters  
    // { and ) added for flexy
    $this->value = $this->createToken('Text');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}




  
<IN_NETDATA>{SPACES} { 
    // whitespace switch back to IN_ATTR MODE.
    $this->value = '';
    $this->yybegin(IN_ATTR);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


  
<IN_ATTR>{NSNAME}{SPACE}*={WHITESPACE}		{
   // <a ^href = "xxx"> -- attribute name 
    $this->attrKey = substr(trim($this->yytext()),0,-1);
    $this->yybegin(IN_ATTRVAL);
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

  
<IN_ATTR>{NSNAME}{WHITESPACE}		{
    // <img src="xxx" ...ismap...> the ismap */
    
    $this->attributes[trim($this->yytext())] = true;
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


<IN_ATTRVAL>{NAME_TOKEN}{WHITESPACE}	{
    // <a name = ^12pt> -- number token */
    $this->attributes[$this->attrKey] = trim($this->yytext());
    $this->yybegin(IN_ATTR);
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

  
<IN_ATTRVAL>{NUMBER_TOKEN}{WHITESPACE}	{
    // <a name = ^xyz> -- name token */
    $this->attributes[$this->attrKey] = trim($this->yytext());
    $this->yybegin(IN_ATTR);
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}
                 


//<IN_ATTRVAL>{LITERAL}{WHITESPACE}		{
    // <a href = ^"a b c"> -- literal */
    // TODO add flexy parsing in here!!!
//    $this->attributes[$this->attrKey] = trim($this->yytext());
//    $this->yybegin(IN_ATTR);
//    $this->value = '';
//    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
//}
 
<IN_ATTRVAL> \'    {
	//echo "STARTING SINGLEQUOTE";
    $this->attrVal = array( "'");
    $this->yybegin(IN_SINGLEQUOTE);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

<IN_ATTRVAL>\"    {
    //echo "START QUOTE";
    $this->attrVal =array("\"");
    
    $this->yybegin(IN_DOUBLEQUOTE);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


 

<IN_SINGLEQUOTE>([^\{\%\'\\]+|\\[^\'])+|"%"|"{"|{FLEXY_GTSTART}|{FLEXY_GTEND}	{
    
    $this->attrVal[] = $this->yytext();
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

<IN_SINGLEQUOTE> \' {
    $this->attrVal[] = "'";
     //var_dump($this->attrVal);
    $s = "";
    foreach($this->attrVal as $v) {
        if (!is_string($v)) {
            $this->attributes[$this->attrKey] = $this->attrVal;
            $this->yybegin(IN_ATTR);
            return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
        }
        $s .= $v;
    }
    $this->attributes[$this->attrKey] = $s;
    $this->yybegin(IN_ATTR);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

<IN_DOUBLEQUOTE>([^\{\%\"\\]|\\[^\"\\])+|"%"|"{"|{FLEXY_GTSTART}|{FLEXY_GTEND} {
    //echo "GOT DATA:".$this->yytext();
    $this->attrVal[] = $this->yytext();
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
} 

<IN_DOUBLEQUOTE>\" {
    //echo "GOT END DATA:".$this->yytext();
    $this->attrVal[] = "\"";
    
    $s = "";
    foreach($this->attrVal as $v) {
        if (!is_string($v)) {
            $this->attributes[$this->attrKey] = $this->attrVal;
            $this->yybegin(IN_ATTR);
            return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
        }
        $s .= $v;
    }
    $this->attributes[$this->attrKey] = $s;
    $this->yybegin(IN_ATTR);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    
}





 
<IN_SCRIPT>{END_SCRIPT} {
    // </script>
    $this->value = $this->createToken('EndTag', array('/script'));

    $this->yybegin(YYINITIAL);
  
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<IN_SCRIPT>([^<]+|{STAGO}) {
    // general text in script..
    $this->value = $this->createToken('Text');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}











  // <a name= ^> -- illegal tag close */
<IN_ATTRVAL>{TAGC}			{ 
    return $this->raiseError("Tag close found where attribute value expected"); 
}

  // <a name=foo ^>,</foo^> -- tag close */
<IN_ATTR,IN_TAG>{TAGC}		{
    $this->value = $this->createToken($this->tokenName, array($this->tagName,$this->attributes));
    
    
    if (strtoupper($this->tagName) == 'SCRIPT') {
        $this->yybegin(IN_SCRIPT);
        return HTML_TEMPLATE_FLEXY_TOKEN_OK;
    }
    if (strtoupper($this->tagName) == 'STYLE') {
        $this->inStyle = true;
    } else {
        $this->inStyle = false;
    }
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}


<IN_ATTRVAL>{NET}	{
    // <em^/ -- NET tag */
    return $this->raiseError("attribute value missing"); 
}


<IN_ATTR>{NET}	{
    // <em^/ -- NET tag */
    $this->yybegin(IN_NETDATA);
    $this->attributes["/"] = true;
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
} 

<IN_ATTR>{NET}{WHITESPACE}{TAGC}	{
    // <em^/ -- NET tag */
    $this->attributes["/"] = true;
    $this->value = $this->createToken($this->tokenName, array($this->tagName,$this->attributes));
        
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
 
  
<IN_ATTR,IN_ATTRVAL,IN_TAG> {STAGO}	{
    // <foo^<bar> -- unclosed start tag */
    return $this->raiseError("Unclosed tags not supported"); 

}

  
  
<IN_ATTRVAL> ([^ \'\"\t\n\r>]+){WHITESPACE}	{
    // <a href = ^http://foo/> -- unquoted literal HACK */                          
    
    $this->attributes[$this->attrKey] = trim($this->yytext());
    $this->yybegin(IN_ATTR);
    //   $this->raiseError("attribute value needs quotes");
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


// ' (put here for scintilla color render mess :)

<IN_TAG,IN_ATTR> {WHITESPACE}	{
    $this->value = '';
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}



  // end tag -- non-permissive */
<IN_ENDTAG>{TAGC} { 
    $this->value = $this->createToken($this->tokenName, array($this->tagName));
        array($this->tagName);
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<IN_ENDTAG>. { 
    return $this->raiseError("extraneous character in end tag"); 
}

 

 // 10 Markup Declarations: General */

 
<IN_COM>([^-]|-[^-])*{WHITESPACE}	{
    // inside a comment (not - or not --
    // <!^--...-->   -- comment */   
       
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}
<IN_COM>{COM}[^>]	{
	// inside comment -- without a >
	return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

 
<IN_COMSTYLE>([^"{"][^-]|-[^-])*{WHITESPACE}	{
    // inside a style comment (not - or not --
    // <!^--...-->   -- comment */   
    $this->value = $this->createToken('Comment');
	return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
<IN_COMSTYLE>. {
    // we allow anything inside of comstyle!!!
    $this->value = $this->createToken('Comment');
	return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
<IN_COMSTYLE>{COM}[^>]	{
	// inside style comment -- without a >
    $this->value = $this->createToken('Comment');
	return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}





<IN_DSCOM>([^-]|-[^-])*{WHITESPACE}	{
    // inside a comment (not - or not --
    // <!^--...-->   -- comment */   
    $this->value = $this->createToken('DSComment');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

 
<IN_MD>{PERO}{NAME}{REFERENCE_END}?{WHITESPACE}		{
    // <!doctype ^%foo;> -- parameter entity reference */
    $this->value = $this->createToken('EntityRef');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

 
<IN_MD>{PERO}{SPACES}			{
    // <!entity ^% foo system "..." ...> -- parameter entity definition */
    $this->value = $this->createToken('EntityPar');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
 
<IN_MD>{NUMBER}{WHITESPACE} 	{   
    $this->value = $this->createToken('Number');
    
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}
<IN_MD>{NAME}{WHITESPACE}			{ 
    $this->value = $this->createToken('Name');
    
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}
<IN_MD>{NUMBER_TOKEN}{WHITESPACE}		{ 
    $this->value = $this->createToken('NumberT');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}
<IN_MD>{NAME_TOKEN}{WHITESPACE}	{ 
    $this->value = $this->createToken('NameT');
    
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}
<IN_MD>{LITERAL}{WHITESPACE}      { 
    $this->value = $this->createToken('Literal');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}

<IN_MD>{WHITESPACE} { 
    $this->value = $this->createToken('WhiteSpace');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}


<IN_COM>[-]*{COM}{TAGC}			{   
    
    $this->value = $this->createToken('Comment',
        '<!--'. substr($this->yy_buffer,$this->yyCommentBegin ,$this->yy_buffer_end - $this->yyCommentBegin),
        $this->yyline,$this->yyCommentBegin
    );
    $this->yybegin(YYINITIAL); 
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}

<IN_COMSTYLE>[-]*{COM}{TAGC}			{   
    // --> inside a style tag.
    $this->value = $this->createToken('Comment');
    $this->yybegin(YYINITIAL); 
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}

<IN_COMSTYLE>("{"{FLEXY_VAR}":"{FLEXY_MODIFIER}"}")|("{"{FLEXY_VAR}"}") {
    // var in commented out style bit..
    $t =  $this->yytext();
    $t = substr($t,1,-1);

    $this->value = $this->createToken('Var', $t);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}


<IN_DSCOM>{COM}{TAGC}			{   
    $this->value = $this->createToken('DSEnd');
    $this->yybegin(YYINITIAL); 
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}

<IN_MD>{TAGC}			{   
    $this->value = $this->createToken('CloseTag');
    $this->yybegin(YYINITIAL); 
    return HTML_TEMPLATE_FLEXY_TOKEN_OK; 
}


//other constructs are errors. 
  
<IN_MD>{DSO}			{
    // <!doctype foo ^[  -- declaration subset */
    $this->value = $this->createToken('BeginDS');
    $this->yybegin(IN_DS);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<IN_MD,IN_COM>.  {
    return $this->raiseError("illegal character in markup declaration (0x".dechex(ord($this->yytext())).')');
}

 


<IN_DS>{MSC}{TAGC}			{
    // ]]> -- marked section end */
     $this->value = $this->createToken('DSEnd');
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
  
<IN_DS>{DSC}			{ 
    // ] -- declaration subset close */
    $this->value = $this->createToken('DSEndSubset');
    $this->yybegin(IN_DSCOM); 
    
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<IN_DS>[^\]]+			{ 
    $this->value = $this->createToken('Declaration');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

 // EXCERPT ACTIONS: STOP */

   
   
   

<YYINITIAL>{FLEXY_GTSTART} {
    $this->value = $this->createToken('GetTextStart','');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
 
<YYINITIAL>{FLEXY_GTEND} {
    $this->value = $this->createToken('GetTextEnd','');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

   
   
   
   
   
   
   
   
   
   
   
 
<YYINITIAL>"{if:"{FLEXY_NEGATE}?{FLEXY_VAR}"}" {
    $this->value = $this->createToken('If',substr($this->yytext(),4,-1));
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<YYINITIAL>"{if:"{FLEXY_NEGATE}?{FLEXY_VAR}"(" {
    $this->value =  '';
    $this->flexyMethod = substr($this->yytext(),1,-1);
    $this->flexyArgs = array();
     
    $this->yybegin(IN_FLEXYMETHOD);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}
 


// foreach (borks on incorrect syntax...


<YYINITIAL,IN_CDATA>"{foreach:"{FLEXY_VAR}"}" {
    return $this->raiseError('invalid syntax for Foreach','',true);
}
<YYINITIAL,IN_CDATA>"{foreach:"{FLEXY_VAR}","{FLEXY_SIMPLEVAR}"}" {
    $this->value = $this->createToken('Foreach', explode(',',substr($this->yytext(),9,-1)));
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
<YYINITIAL,IN_CDATA>"{foreach:"{FLEXY_VAR}","{FLEXY_SIMPLEVAR}","{FLEXY_SIMPLEVAR}"}" {
    $this->value = $this->createToken('Foreach',  explode(',',substr($this->yytext(),9,-1)));
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}
<YYINITIAL,IN_CDATA>"{end:}" {
    $this->value = $this->createToken('End', '');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

<YYINITIAL,IN_CDATA>"{else:}" {
    $this->value = $this->createToken('Else', '');
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}


// variables
// need to work out how to do this with attribute values..

 
 
<IN_DOUBLEQUOTE,IN_SINGLEQUOTE> ({FLEXY_START}{FLEXY_VAR}":"{FLEXY_MODIFIER}{FLEXY_END})|({FLEXY_START}{FLEXY_VAR}{FLEXY_END}) {

    $n = $this->yytext();
    if ($n{0} != '{') {
        $n = substr($n,3);
    } else {
        $n = substr($n,1);
    }
    if ($n{strlen($n)-1} != '}') {
        $n = substr($n,0,-3);
    } else {
        $n = substr($n,0,-1);
    }
    $this->attrVal[] = $this->createToken('Var'  , $n);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


<YYINITIAL,IN_CDATA>("{"{FLEXY_VAR}":"{FLEXY_MODIFIER}"}")|("{"{FLEXY_VAR}"}") {
    $t =  $this->yytext();
    $t = substr($t,1,-1);

    $this->value = $this->createToken('Var'  , $t);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}







// methods

<YYINITIAL>"{"{FLEXY_METHOD}"(" {
    $this->value =  '';
    $this->flexyMethod = substr($this->yytext(),1,-1);
    $this->flexyArgs = array();
    $this->yybegin(IN_FLEXYMETHOD);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

<IN_FLEXYMETHOD>(")}"|"):"{FLEXY_MODIFIER}"}") {
    
    $t = $this->yytext();
    if ($t{1} == ':') {
        $this->flexyMethod .= substr($t,1,-1);
    }
        
    $this->value = $this->createToken('Method'  , array($this->flexyMethod,$this->flexyArgs));
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}



<IN_FLEXYMETHOD>{FLEXY_VAR}(","|")}"|"):"{FLEXY_MODIFIER}"}") {
    
    $t = $this->yytext();
    if ($t{strlen($t)-1} == ",") {
        // add argument
        $this->flexyArgs[] = substr($t,0,-1);
        return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    }
    if ($c = strpos($t,':')) {
        $this->flexyMethod .= substr($t,$c,-1);
        $t = substr($t,0,$c-1);
    } else {
        $t = substr($t,0,-2);
    }
    
    $this->flexyArgs[] = $t;
    $this->value = $this->createToken('Method'  , array($this->flexyMethod,$this->flexyArgs));
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}

// let the previous method handle closing, with the modifier.

<IN_FLEXYMETHOD>"#"{FLEXY_LITERAL}("#,"|"#") {
     
    $t = $this->yytext();
    if ($t{strlen($t)-1} == ",") {
        // add argument
        $this->flexyArgs[] = substr($t,0,-1);
        return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    }
    
    $this->flexyArgs[] = $t;
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    
}
 
<IN_FLEXYMETHOD>{DIGIT}+(","|")}"|"):"{FLEXY_MODIFIER}"}") {
    
    $t = $this->yytext();
    if ($t{strlen($t)-1} == ",") {
        // add argument
        $this->flexyArgs[] = '#' . substr($t,0,-1) . '#';
        return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    }
    if ($c = strpos($t,':')) {
        $this->flexyMethod .= substr($t,$c,-1);
        $t = '#' . substr($t,0,$c-1) . '#';
    } else {
        $t = '#' . substr($t,0,-2) . '#';
    }
    
    $this->flexyArgs[] = $t;
    $this->value = $this->createToken('Method', array($this->flexyMethod,$this->flexyArgs));
    $this->yybegin(YYINITIAL);
    return HTML_TEMPLATE_FLEXY_TOKEN_OK;
}


// methods inside quotes..


<IN_DOUBLEQUOTE,IN_SINGLEQUOTE>{FLEXY_START}{FLEXY_METHOD}"(" {
    $this->value =  '';
    $n = $this->yytext();
    if ($n{0} != "{") {
        $n = substr($n,2);
    }
    
    $this->flexyMethod = substr($n,1,-1);
    $this->flexyArgs = array();
    $this->flexyMethodState = $this->yy_lexical_state;
    $this->yybegin(IN_FLEXYMETHODQUOTED);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

// no values in method.

<IN_FLEXYMETHODQUOTED,IN_FLEXYMETHODQUOTED_END>(")"|"):"{FLEXY_MODIFIER}){FLEXY_END} {
    
    $t = $this->yytext();
    if ($p = strpos($t,':')) {
        $this->flexyMethod .= substr($t,$p,-1);
    }
    $this->attrVal[] = $this->createToken('Method'  , array($this->flexyMethod,$this->flexyArgs));
    $this->yybegin($this->flexyMethodState);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}

<IN_FLEXYMETHODQUOTED_END>"," {

    $this->yybegin(IN_FLEXYMETHODQUOTED);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
}


<IN_FLEXYMETHODQUOTED>{FLEXY_VAR} {
    
     
    $t = $this->yytext();
    // add argument
    $this->flexyArgs[] = $t;
    $this->yybegin(IN_FLEXYMETHODQUOTED_END);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
  
    
}

 

<IN_FLEXYMETHODQUOTED>"#"{FLEXY_LITERAL}"#" {
    $t = $this->yytext();
    $this->flexyArgs[] =$t;
    $this->yybegin(IN_FLEXYMETHODQUOTED_END);
    return HTML_TEMPLATE_FLEXY_TOKEN_NONE;
    
    
}


<YYINITIAL,IN_SINGLEQUOTE,IN_TAG,IN_ATTR,IN_ATTRVAL,IN_NETDATA,IN_DOUBLEQUOTE,IN_DS,IN_FLEXYMETHOD,IN_FLEXYMETHODQUOTED,IN_FLEXYMETHODQUOTED_END,IN_DSCOM> . {
    return $this->raiseError("unexpected something: (".$this->yytext() .") character: 0x" . dechex(ord($this->yytext())));
    
} 
