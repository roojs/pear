<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Wolfram Kriesing <wolfram@kriesing.de>                      |
// +----------------------------------------------------------------------+
//
//  $Id: Translate.php 113824 2003-01-28 19:20:04Z cain $

// we have to move this to some more common place in PEAR
// this is just a quick hack here :-)
require_once( 'Tree/OptionsDB.php' );    // this contains all the methods like setOption, getOption, etc.

/**
*   Description
*
*   @package  Language
*   @access   public
*   @author   Wolfram Kriesing <wolfram@kriesing.de>
*   @version  2001/12/29
*/
class I18N_Messages_Translate extends Tree_OptionsDB
{

    var $options = array(   'tablePrefix' =>    'translate_'    // the DB-table name prefix, at the end we add the lang-string passed to the method
                            ,'sourceLanguage'=>  'en'           // the source language, the language used to retrieve the strings to translate from
                                                                // its also the table which is used to retreive the source string
                            
                            // be case senstivie by default since not all languages write nouns and verbs etc. 
                            // in lower case translating from german might fail if the case is not considered
                            ,'caseSensitive'=> true

                            ,'translatorUrl' =>  ''              // the url to a translator tool, only used if given
                         );

    /**
    *   Those are the delimiters surrounding translatable text.
    *   This way we prevent from translating each HTML-tag, which definetly wouldnt work :-)
    *   those delimiters might also work on any other markup language, like xml - but not tested
    *
    *   NOTE: if you have php inside such a tag then you have to use an extra filter
    *   since <a href="">< ?=$var? ></a> would find the php-tags and see them as delimiters
    *   which results in < ?=$var? > can not be translated, see sf.net/projects/simpletpl, there
    *   is a filter in 'SimpleTemplate/Filter/Basic::applyTranslateFunction' which solves this
    *   it wraps a given function/method around it so that it finally will be:
    *       <a href="">< ?=translateThis($var)? ></a>
    *
    *   @var    array   $possibleMarkUpDelimiters
    */
    var $possibleMarkUpDelimiters = array(
                                    // cant use this
                                    // '>[^<]*'                        ,'[^>]*<', // this mostly applies, that a text is inbetween '>' and '<'
                                    // because it would translate 'class="..."' too, if we have the word 'as' to be translated :-(
                                    // but this also means we have to handle stuff like &nbsp; of others specials chars, that dont start
                                    // and end with a < or > somehow ... i dont know how yet :-(

                                    // this mostly applies, that a text is inbetween '>' and '<'
                                    array('>\s*',   '\s*<')

                                    // same as above,
                                    // only that this leaves &nbsp; before and after the text outside html-tags as they are ...
                                    //
                                    // actually we need a more common thing here, which also takes care of other chars which
                                    // we dont want to bother the translation with
                                  //  ,array('>[\s*&nbsp;]*', '[\s*&nbsp;]*<')
                                  //the above is not really secure, since [] means any char inside
                                  // so '>nbsp translate this<' would be translated too


                                    // this is for input button's values    
//FIXXXME this fails if the order of type and value attrbs is switched :-(
                                    ,array('<input type=["\']?(submit|button)["\']? [^>]*value=["\']?\s*' , '\s*["\']?[^>]*>' ,  1)
//                                    ,array('<\s*input .*type=submit .*value=["\']?\s*','\s*["\']?.*>')
//                                    array('<\s*input.*type=[\'"]?(button|submit)[\'"]?.*value=["\']?\s*','\s*["\']?.*>',1)
//                                    ,array('<\s*input.*value=["\']?\s*','\s*["\']?.*>')

// all the reg-exps here are limited, the ones below would work perfect on:
//    <a title="translate this" ...>  or <img alt="translate this" ....>
// but with the following they would have problems:
//    <a href="< ?php echo §foor ? >" title="translate this">
// since there is a < before the title-attribute, and if we build the regexp
// with .* instead of [^>]* before the attribute it might return ambigious results

// SOLUTION for now: put the attrbutes you want translated first inside the tag, like in the example above

                                    ,array('<img [^>]*alt=["\']?\s*' ,  '\s*["\']?.*>')
                                    ,array('<a [^>]*title=["\']?\s*' ,  '\s*["\']?.*>')
                                );

    /**
    *   @var    array   this contains the content from the DB, to prevent from multiple DB accesses
    */
    var $_translated = array('destLanguage'=>'','strings'=>array());

    /**
    *   @var    array   this array contains the translated strings but with the difference to $_translated
    *                   that the source strings is the index, so a lookup if a translation exists is much faster
    */
    var $_sourceStringIndexed = array();

    /**
    *
    *
    *   @access     public
    *   @author
    *   @version
    */
    function __construct( $dsn , $options )
    {
        parent::Tree_OptionsDB( $dsn , $options );
// FIXXME pass a resource to the constructor which can be used to translate the
// string, it should be possible to use XML, DB, or whatever
// currently, as you can see there is only a DB interface hardcoded in here
// this will be removed soon
    }

    /**
    *   for pre-ZE2 compatibility
    *
    *   @access     public
    *   @author
    *   @version
    */
    function I18N_Messages_Translate( $dsn , $options=array() )
    {
        return $this->__construct( $dsn , $options );
    }

    /**
    *   tries to translate a given string, but only exactly the string as it is in the DB
    *
    *   @access     public
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @version    01/12/29
    *   @param      string  $string     the string that shall be translated
    *   @param      string  $lang       iso-string for the destination language
    *   @return     string  the translated string
    */
    function simpleTranslate( $string , $lang )
    {
        if( $lang == $this->getOption('sourceLanguage') )   // we dont need to translate a string from the source language to the source language
            return $string;

        if( sizeof($this->_translated['strings'])>0 &&      // this checks if the DB content had been read already
            $this->_translated['destLanguage'] == $lang )   // for this language
        {
            if( sizeof($this->_sourceStringIndexed) == 0 )
            {
                foreach( $this->_translated['strings'] as $aSet)
                    $this->_sourceStringIndexed[$aSet['string']] = $aSet['translated'];
            }
            if( isset($this->_sourceStringIndexed[$string]) )
                return $this->_sourceStringIndexed[$string];
            return $string;
        }
// FIXXME may be it would be better just reading the entire DB-content once
// and using this array then ??? this uses up a lot of RAM and that for every user ... so i guess not OR?
// or use PEAR::Cache
        $query = sprintf(   "SELECT d.string FROM %s%s s,%s%s d WHERE s.string=%s AND s.id=d.id",
                            $this->getOption('tablePrefix'),$this->getOption('sourceLanguage'), // build the source language name
                            $this->getOption('tablePrefix'),$lang,
                            $this->dbh->quote($string) );    // build the destination language name
        $res = $this->dbh->getOne( $query );
        if( DB::isError($res) )
        {
//            return $this->raiseError('...');
            return $string; // return the actual string on failure
        }

        if( !$res )                                 // if no translation was found return the source string
            return $string;

        return $res;
    }

    /**
    *   tries to translate a given string, it also tries using the regexp's which might be in the DB
    *
    *   @access     public
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @version    01/12/29
    *   @param      string  $string     the string that shall be translated
    *   @param      string  $lang       iso-string for the destination language
    *   @return     string  the translated string
    */
    function translate( $string , $lang )
    {
//FIXXME extract the reg-exp thingy from the translateMarkup method and call 
// it explicitly here, and once we have found a translation stop the process,
// so we dont translate stuff that is already translated again, 
// i.e. 'Data saved' is translated into 'Daten gespeichert' and if we continue
// as we do now it will translate 'Date' into 'Datum' which results in 'Datumn gespeichert'
// which is big bullshit
// and the second thing: do only translate full words, then the above wouldnt happen neither

        $res = $this->simpleTranslate( $string , $lang );
        // if the select didnt translate the string we need to go thru all the strings
        // which contain parts of regular expressions, so we can leave out all
        // the pure strings, this should save some time
        if( $res == $string )
        {
            $temp = $this->possibleMarkUpDelimiters;    // remember the delimiters
            $this->possibleMarkUpDelimiters = array(''=>'');    // dont use any delimiters
// may be better using a property like 'useMarkupDelimiters'
            $res = $this->translateMarkUpString( $string , $lang ); // translate
            $this->possibleMarkUpDelimiters = $temp;    // set delimiters properly again
        }

        return $res;
    }

    /**
    *   returns the DB content for the source and the destination language given as paramter $lang
    *
    *   @access     public
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @version    02/01/08
    *   @param      string  $lang       iso-string for the destination language
    *   @return     array
    */
    function getAll( $lang )
    {
        if( sizeof($this->_translated['strings'])==0 ||      // this checks if the DB content had been read already
            $this->_translated['destLanguage'] != $lang )    // for this language
        {
//print "read again<br>";
            $this->_translated['destLanguage'] = $lang;
        }
        else
        {
            return $this->_translated['strings'];
        }

		// for the translation API, we need to have the long sentences at first, since translating a single word
		// might screw up the entire content, like translating 'i move to germany' and starting to tranlate the 
		// word 'move' makes it impossible to properly translate the entire phrase
		// even though this problem can not really happen, since we check for delimiters around the string that
		// shall be translated see $posDelimiters
        $query = sprintf(   'SELECT d.string as translated,d.*,s.* '.   // d.string shall be named 'translated'
                                                                        // but we still need all the rest from the destination language table
                                                                        // and s.* overwrites d.string but we dont need it we have it in 'translated'
                            'FROM %s%s s,%s%s d WHERE s.id=d.id '.
                            'ORDER BY LENGTH(s.string) DESC',   // sort the results by the length of the strings, so we translate
                                                                // sentences first and single words at last
                            $this->getOption('tablePrefix'),$this->getOption('sourceLanguage'), // build the source language name
                            $this->getOption('tablePrefix'),$lang );    // build the destination language name
                            
        $res = $this->dbh->getAll( $query );
        if( DB::isError($res) )
        {
//            return $this->raiseError('...');
            echo sprintf('ERROR - Translate::getAll<br>QUERY:%s<br>%s<br><br>',$query,$res->message);
            return false;
        }
        $this->_translated['destLanguage'] = $lang;
        $this->_translated['strings'] = $res;

        return $this->_translated['strings'];
    }

    /**
    *   translates all the strings that match any of the source language-string
    *   the input is mostly an HTML-file, and it is filtered so only real text
    *   is translated, at least i try it as good as i can :-)
    *
    *   @access     public
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @version    02/01/08
    *   @param      string  $input      the string that shall be translated, mostly an entire HTML-page
    *   @param      string  $lang       iso-string for the destination language
    *   @return     string  iso-string for the language
    *
    */
    function translateMarkUpString( $input , $lang )
    {
        if( $lang == $this->getOption('sourceLanguage') )   // we dont need to translate a string from the source language to the source language
        {
        	// this would be a cool feature, if i get it done :-)
            $url=$this->getOption('translatorUrl');
            if( $url )
            {
                $this->getAll( $lang );
                return $this->addTranslatorLinks( $input , $url );
            }
            return $input;
        }

        $this->getAll( $lang );          // get all the possible strings from the DB
                             
        $addModifier = $this->getOption('caseSensitive') ? '' : 'i';

// FIXXME replace all spaces by something like this: (\s*|<br>|<br/>|<font.*>|</font>|<i>|</i>|<b>|</b>|&nbsp;)
// simply all those formatting tags which dont really cancel the phrase that should be translated
// and put them back in the translated string
// by filling $x in the right place and updating $lastSubpattern
// then it will be really cool and the text to translate will be recognized with any kind of space inbetween
// *NO* 
// we dont want to do the above thing, since the formatting inside a text needs to be taken care of
// by the translator, this method here has no chance to know that 'this is a <b>user</b> from germany'
// has to become 'dies ist ein <b>Benutzer</b> aus Deutschland', the grammar of languages might be so different,
// that the marked part can be in a totally different place in the destination language string!

        if(is_array($this->_translated['strings']) && sizeof($this->_translated['strings']))
        foreach( $this->_translated['strings'] as $aString )             // search for each single string and try to translate it
        {
            $lastSubpattern = 2;
            // we use 2 strings that we search for, one is the real text as from the db
            // the second $htmlSourceString is the source string but with all non html characters
            // translated using htmlentities, in case someone has been programming proper html :-)
            
            // shall the translated string be converted to HTML, or does it may be contain HTML?
            if( isset($aString['convertToHtml']) && $aString['convertToHtml'] )
                $translated = htmlentities($aString['translated']);
            else
                $translated = $aString['translated'];

            if( $aString['numSubPattern'] )// if the string is a regExp, we need to update $lastSubpattern
            {
            	// we dont preg_quote the strings which contain regExps's
            	// if chars like '.' or alike which also appear in regExps they have to be 
            	// escaped by the person who enters the source-string (may be we do that better one day)
                $sourceString = $aString['string'];
                $htmlSourceString = htmlentities($aString['string']);// we should not preg_quote the string

                $lastSubpattern = 2 + $aString['numSubPattern'];    // set $lastSubpattern properly

                // in the DB the spaceholders start with $1, but here we need it
                // to start with $2, that's what the following does
                preg_match_all ( '/\$(\d)/' , $translated , $res );
                $res[0] = array_reverse($res[0]);   // reverse the arrays, since we replace $1 by $2 and then $2 by $3 ...
                $res[1] = array_reverse($res[1]);   // ... if we wouldnt reverse all would become $<lastNumber>
                foreach( $res[0] as $index=>$aRes )
                {
                    $aRes = preg_quote($aRes);
                    $translated = preg_replace( '/'.$aRes.'/' , '\$'.($res[1][$index]+1) , $translated );
            	}
            }
            else
            {
                // in none regExp's source strings we better quote the chars which could be
                // mistakenly seen as regExp chars
                $sourceString = preg_quote(trim($aString['string']));
                $htmlSourceString = preg_quote(htmlentities(trim($aString['string'])));
                // escape all slashes, since preg_quote doenst do that :-(
                $sourceString = str_replace('/','\/',$sourceString);
                $htmlSourceString = str_replace('/','\/',$htmlSourceString);
            }

            foreach( $this->possibleMarkUpDelimiters as $delimiters )  // go thru all the delimiters and try to translate the strings
            {
// FIXXME there might be a major problem:
//   <td
//       {if($currentPageIndex==$key)}
//           class="naviItemSelected"    this line will also be tried to translated, since the line before and the one after
//                                       will start/end with php tags, which also start/end with a < or > which are possible delimtier :-(
//       {else}
//           class="naviItem"
//   nowrap>
//
//              
                $numSubPatterns = array(0,0);
                $begin = $delimiters[0];
                $end = $delimiters[1];
                if( isset($delimiters[2]) )     $numSubPatterns[0] = $delimiters[2];
                if( isset($delimiters[3]) )     $numSubPatterns[1] = $delimiters[3];

                // replace all spaces in the source string by \s* so that there can be spaces
                // as many as one wants 
                // and even newlines (the modifier s in the preg_replace takes care of that)
                $sourceString = preg_replace('/\s+/','\\s*',$sourceString);
                $htmlSourceString = preg_replace('/\s+/s','\\s*',$htmlSourceString);

                $_hashCode = md5($input);  
                
                $input = preg_replace(  '/('.$begin.')'.$sourceString.'('.$end.')/sU'.$addModifier ,
                                        '$1'.$translated.'$'.($lastSubpattern+$numSubPatterns[0]) ,
                                        $input );

                // if the regExp above didnt have no effect try this one with all html characters translated
                // if we wouldnt check this i had the effect that something was translated twice ...
                // dont know exactly why but it did :-)
                if( $_hashCode == md5($input) )
                {
                    // try also to translate the string with all non-HTML-characters translated using htmlentities
                    // may be someone was creating proper html :-)
                    $input = preg_replace(  '/('.$begin.')'.$htmlSourceString.'('.$end.')/sU'.$addModifier ,
                                            '$1'.$translated.'$'.($lastSubpattern+$numSubPatterns[0]) ,
                                            $input );
                }

            }
        }
        return $input;
    }

    /**
    *
    *
    *   @access     public
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @version    02/04/14
    *   @param      string  the url to a translation tool
    *   @return
    */
/*    function addTranslatorLinks( $input , $url )
    {
        $linkBegin = '<a href="#" onClick="javascript:window.open(\''.$url.'?string=';
        $linkEnd =  '\',\'translate\',\'left=100,top=100,width=400,height=200\')" '.
                    'style="background-color:red; color:white; font-style:Courier; font-size:12px;">&nbsp;T&nbsp;</a>';

        foreach( $this->_translated['strings'] as $aString )             // search for each single string and try to translate it
        {
            $englishString = preg_quote($aString['string']);

            if( $aString['numSubPattern'] )         // if the string is a regExp, we need to update $lastSubpattern
            {
                $englishString = $aString['string'];// we should not preg_quote the string
                $lastSubpattern = '$'.( 2 + $aString['numSubPattern'] );    // set $lastSubpattern properly
            }

            $link = $linkBegin.urlencode($englishString).$linkEnd;
            $input = preg_replace( '/(\s*>\s*)('.$englishString.')(\s*<\/a>)/isU' , '$1$2$3'.$link , $input );
            $input = preg_replace( '/(<option.*>\s*)('.$englishString.')(.*<\/select>)/isU' , '$1$2$3'.$link , $input );
            $input = preg_replace(  '/(<input[^>]*type=.?(button|submit|reset)[^>]*value=.?\s*)'.
                                    '('.$englishString.')([^>]*>)/isU' , '$1$3$4'.$link , $input );
        }
        return $input;
    }

#        '>\s*'                          =>  '\s*<', // this mostly applies, that a text is inbetween '>' and '<'
#        '<\s*input .*value=["\']?\s*'   =>  '\s*["\']?.*>'  // this is for input button's values
*/

} // end of class
?>
