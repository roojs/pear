<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001, 2002, 2003 The PHP Group       |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Wolfram Kriesing <wk@visionp.de>                            |
// |                                                                      |
// +----------------------------------------------------------------------+//
// $Id: DateTime.php 136857 2003-08-07 07:46:23Z cain $

require_once 'I18N/Format.php';

define( 'I18N_DATETIME_SHORT' ,         1 );
define( 'I18N_DATETIME_DEFAULT' ,       2 );
define( 'I18N_DATETIME_MEDIUM' ,        3 );
define( 'I18N_DATETIME_LONG' ,          4 );
define( 'I18N_DATETIME_FULL' ,          5 );


  
/**
*
*   @package    I18N 
*
*/
class I18N_DateTime extends I18N_Format
{

    // for ZE2 :-)
/*
    const SHORT =   1;
    const DEFAULT = 2;
    const MEDIUM =  3;
    const LONG =    4;
    const FULL =    5;

    const CUSTOM_FORMATS_OFFSET = 100;
*/

    var $days = array( 'Sunday' , 'Monday' , 'Tuesday' , 'Wednesday' , 'Thursday' , 'Friday' , 'Saturday' );

    var $daysAbbreviated = array( 'Sun','Mon','Tue','Wed','Thu','Fri','Sat');

    var $monthsAbbreviated = array( 'Jan' , 'Feb' , 'Mar' , 'Apr' , 'May' , 'Jun' ,'Jul' , 'Aug' , 'Sep' , 'Oct' , 'Nov' , 'Dec' );

    var $months = array(
                            'January',
                            'February',
                            'March',
                            'April',
                            'May',
                            'June',
                            'July',
                            'August',
                            'September',
                            'October',
                            'November',
                            'December'
                         );

    /**
    *   this var contains the current locale this instace works with
    *
    *   @access     protected
    *   @var        string  this is a string like 'de_DE' or 'en_US', etc.
    */
    var $_locale;

    /**
    *   the locale object which contains all the formatting specs
    *
    *   @access     protected
    *   @var        object
    */
    var $_localeObj = null;

    
    var $_currentFormat = I18N_DATETIME_DEFAULT;
    var $_currentDateFormat = I18N_DATETIME_DEFAULT;
    var $_currentTimeFormat = I18N_DATETIME_DEFAULT;

    var $_customFormats = array();

    /**
    *   Use this method to setup and to retreive the static instance of the I18N_DateTime.
    *   <code>
    *   // setup the object with the proper locale
    *   I18N_DateTime::singleton($locale);
    *   // and anywhere in your code you can call the following
    *   // and you get the instance for this very locale, you specified via singleton()
    *   $dateTime = I18N_DateTime::singleton();
    *   </code>
    *
    *   @static 
    *   @access public
    *   @param  string  the locale to use, i.e. 'de_DE'
    *   @return object  an instance of this class
    */
    function &singleton($locale=null)
    {
        return I18N_Format::_singleton($locale,__CLASS__);
    }
    
    /**
    *   returns the timestamp formatted according to the locale and the format-mode
    *   use this method to format a date and time timestamp
    *
    *   @see        setFormat()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @param      int     the formatting mode, using setFormat you can add custom formats
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function format($timestamp=null,$format=null)
    {
        return $this->_format($timestamp,$format);
    }

    /**
    *   returns the timestamp formatted according to the locale and the format-mode
    *   use this method to get a formatted date only
    *
    *   @see        setDateFormat()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @param      int     the formatting mode, use setDateFormat to add custom formats
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDate( $timestamp=null , $format=null )
    {
        return $this->_formatDateTime($timestamp,$format,'date');
    }

    /**
    *   returns the timestamp formatted according to the locale and the format-mode
    *   use this method to get a formatted time only
    *
    *   @see        setTimeFormat()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @param      int     the formatting mode, use setTimeFormat to add custom formats
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatTime( $timestamp=null , $format=null )
    {
        return $this->_formatDateTime($timestamp,$format,'time');
    }

    /**
    *   formats a timestamp consisting of date and time
    *   or a custom timestamp, which was set using setFormat
    *
    *   @see        setFormat()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @param      int     the format
    *   @return     string  the formatted timestamp
    *   @access     private
    */
    function _format( $timestamp , $format )
    {
        if ($format == null){
            $format = $this->getFormat();
        }
        if ($timestamp == null){
            $timestamp = time();
        }

        if ($format >= I18N_CUSTOM_FORMATS_OFFSET) {
            if (isset($this->_customFormats[$format])) {
                return $this->_translate(date($this->_customFormats[$format],$timestamp));
            } else {
                $format = I18N_DATETIME_DEFAULT;
            }
        }
        return  $this->_formatDateTime($timestamp,$format,'date').' '.
                $this->_formatDateTime($timestamp,$format,'time');
    }

    /**
    *   this method formats the given timestamp into the given format
    *
    *   @see        setFormat()
    *   @see        setDateFormat()
    *   @see        setTimeFormat()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @param      int     the formatting mode, use setTimeFormat to add custom formats
    *   @param      string  either 'date' or 'time' so this method knows what it is currently formatting
    *   @return     string  the formatted timestamp
    *   @access     private
    */
    function _formatDateTime($timestamp,$format,$what)
    {
        $getFormatMethod = 'get'.ucfirst($what).'Format';
        if ($format == null){
            $format = $this->$getFormatMethod();
        }
        if ($timestamp == null){
            $timestamp = time();
        }
                 
        $curFormat = I18N_DATETIME_DEFAULT;// just in case the if's below dont find nothing
        $formatArray = $what.'Formats';
        if (isset($this->_localeObj->{$formatArray}[$format])) {
            $curFormat = $this->_localeObj->{$formatArray}[$format];
        } elseif(isset($this->_customFormats[$format])) {
            $curFormat = $this->_customFormats[$format];
        }
        return $this->_translate(date($curFormat,$timestamp));
    }

    /**
    *   this simply translates the formatted timestamp into the locale-language
    *
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      string  a human readable timestamp, such as 'Monday, August 7 2002'
    *   @return     string  the formatted timestamp
    *   @access     private
    */
    function _translate( $string )
    {
//FIXXME optimize this array, use only those that are in the format string, i.e. if no abbreviated formats are used
// dont put the abbreviated's in this array ....
        $translateArrays = array('days','months','daysAbbreviated','monthsAbbreviated');

        // this seems a bit difficult i guess,
        // but i had problems with the way i did it before, which way simply str_replace the
        // src-lang array and the dest-lang array, this caused stuff like
        // translating 'Monday' => 'Montag' and then 'Montag' => 'Motag', since 'Mon'
        // is the abbreviated weekday for Monday. 
        // if i would turn it around and translate the abbreviated words first it would screw up worse
        
        // so what i do now is searching for the position of words which can be translated and
        // remember the position (using strpos) and dont translate a word at this position a second
        // time. this at least prevents the case described above. i hope it covers everything else too
        // for me it works quite well now
        $translateSrc =  array();
        $translateDest = array();
        $prevPositions = array();
        foreach ($translateArrays as $aArray) {
            if (isset($this->_localeObj->{$aArray})) {
                foreach ($this->{$aArray} as $index=>$aWord) {
                    if (($pos=strpos($string,$aWord))!==false && !in_array($pos,$prevPositions)) {
                        $translateSrc[] = $aWord;
                        $translateDest[] = $this->_localeObj->{$aArray}[$index];
                        $prevPositions[] = $pos;
                    }
                }
            }
        }
        // here we actually replace the strings (translate:-)) that we found, when checking for their position
        $string = str_replace($translateSrc,$translateDest,$string);
        return $string;
    }

    /**
    *   define a custom format given by $format and return the $format-id
    *   the format-id can be used to call format( x , format-id ) to
    *   tell the method you want to use the format with that id
    *
    *   @see        format()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      string  defines a custom format
    *   @return     int     the format-id, to be used with the format-method
    */
    function setFormat( $format=I18N_DATETIME_DEFAULT )
    {
        return parent::setFormat( $format );
    }

    /**
    *   define a custom format given by $format and return the $format-id
    *   the format-id can be used to call formatDate( x , format-id ) to
    *   tell the method you want to use the format with that id
    *
    *   @see        formatDate()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      string  defines a custom format
    *   @return     int     the format-id, to be used with the format-method
    */
    function setDateFormat( $format=I18N_DATETIME_DEFAULT )
    {
        return $this->_setFormat( $format , 'date' );
    }

    /**
    *   define a custom format given by $format and return the $format-id
    *   the format-id can be used to call formatTime( x , format-id ) to
    *   tell the method you want to use the format with that id
    *
    *   @see        formatTime()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      string  defines a custom format
    *   @return     int     the format-id, to be used with the format-method
    */
    function setTimeFormat( $format=I18N_DATETIME_DEFAULT )
    {
        return $this->_setFormat( $format , 'time' );
    }

    /**
    *
    */
    function getDateFormat()
    {
        return $this->_currentDateFormat;
    }
    function getTimeFormat()
    {
        return $this->_currentTimeFormat;
    }  
                          
    /**
    *   get either the current or the given month name
    *
    */
    function getMonthName( $which=null , $abbreviated=false )
    {
        if ($which==null) {
            $which = $date('n')-1;
        }
        $monthNames = $this->getMonthNames( $abbreviated );
        return $monthNames[$which];
    }

    /**
    *   get all month names for the current locale
    *
    *   get all month names for the current locale,
    *   fallback to english if not defined
    *
    */
    function getMonthNames($abbreviated=false)
    {                                          
        $propName = 'months'.($abbreviated ? 'Abbreviated' : '' );
        return isset($this->_localeObj->$propName) ? $this->_localeObj->$propName : $this->$propName;
    }

    function getDayNames($abbreviated=false)
    {
        $propName = 'days'.($abbreviated ? 'Abbreviated' : '' );
        return isset($this->_localeObj->$propName) ? $this->_localeObj->$propName : $this->$propName;
    }

    //
    //  all the following are simply convienence methods
    //  which make it shorter to call the format methods with the default
    //  formats,
    //  FIXXME we should use overloading here, well with ZE2 we will!!!!
    //
    //  i am not really happy with the following, since it only bloats the code,
    //  but the methods make sense :-)
    //

    /**
    *   convinience method, same as format( $timestamp , I18N_DATETIME_SHORT )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_SHORT
    *
    *   @see        format()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatShort( $timestamp=null )
    {
        return $this->format( $timestamp , I18N_DATETIME_SHORT );
    }

    /**
    *   convinience method, same as format( $timestamp , I18N_DATETIME_DEFAULT )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_DEFAULT
    *
    *   @see        format()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDefault( $timestamp=null )
    {
        return $this->format( $timestamp , I18N_DATETIME_DEFAULT );
    }

    /**
    *   convinience method, same as format( $timestamp , I18N_DATETIME_MEDIUM )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_MEDIUM
    *
    *   @see        format()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatMedium( $timestamp=null )
    {
        return $this->format( $timestamp , I18N_DATETIME_MEDIUM );
    }

    /**
    *   convinience method, same as format( $timestamp , I18N_DATETIME_LONG )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_LONG
    *
    *   @see        format()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatLong( $timestamp=null )
    {
        return $this->format( $timestamp , I18N_DATETIME_LONG );
    }

    /**
    *   convinience method, same as format( $timestamp , I18N_DATETIME_FULL )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_FULL
    *
    *   @see        format()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatFull( $timestamp=null )
    {
        return $this->format( $timestamp , I18N_DATETIME_FULL );
    }




    /**
    *   convinience method, same as formatDate( $timestamp , I18N_DATETIME_SHORT )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_SHORT
    *
    *   @see        formatDate()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDateShort( $timestamp=null )
    {
        return $this->formatDate( $timestamp , I18N_DATETIME_SHORT );
    }

    /**
    *   convinience method, same as formatDate( $timestamp , I18N_DATETIME_DEFAULT )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_DEFAULT
    *
    *   @see        formatDate()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDateDefault( $timestamp=null )
    {
        return $this->formatDate( $timestamp , I18N_DATETIME_DEFAULT );
    }

    /**
    *   convinience method, same as formatDate( $timestamp , I18N_DATETIME_MEDIUM )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_MEDIUM
    *
    *   @see        formatDate()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDateMedium( $timestamp=null )
    {
        return $this->formatDate( $timestamp , I18N_DATETIME_MEDIUM );
    }

    /**
    *   convinience method, same as formatDate( $timestamp , I18N_DATETIME_LONG )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_LONG
    *
    *   @see        formatDate()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDateLong( $timestamp=null )
    {
        return $this->formatDate( $timestamp , I18N_DATETIME_LONG );
    }

    /**
    *   convinience method, same as formatDate( $timestamp , I18N_DATETIME_FULL )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_FULL
    *
    *   @see        formatDate()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatDateFull( $timestamp=null )
    {
        return $this->formatDate( $timestamp , I18N_DATETIME_FULL );
    }





    /**
    *   convinience method, same as formatTime( $timestamp , I18N_DATETIME_SHORT )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_SHORT
    *
    *   @see        formatTime()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatTimeShort( $timestamp=null )
    {
        return $this->formatTime( $timestamp , I18N_DATETIME_SHORT );
    }

    /**
    *   convinience method, same as formatTime( $timestamp , I18N_DATETIME_DEFAULT )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_DEFAULT
    *
    *   @see        formatTime()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatTimeDefault( $timestamp=null )
    {
        return $this->formatTime( $timestamp , I18N_DATETIME_DEFAULT );
    }

    /**
    *   convinience method, same as formatTime( $timestamp , I18N_DATETIME_MEDIUM )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_MEDIUM
    *
    *   @see        formatTime()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatTimeMedium( $timestamp=null )
    {
        return $this->formatTime( $timestamp , I18N_DATETIME_MEDIUM );
    }

    /**
    *   convinience method, same as formatTime( $timestamp , I18N_DATETIME_LONG )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_LONG
    *
    *   @see        formatTime()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatTimeLong( $timestamp=null )
    {
        return $this->formatTime( $timestamp , I18N_DATETIME_LONG );
    }

    /**
    *   convinience method, same as formatTime( $timestamp , I18N_DATETIME_FULL )
    *
    *   this method exists, to have a shorter call to the method
    *   with a default format I18N_DATETIME_FULL
    *
    *   @see        formatTime()
    *   @version    02/11/28
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      int     a timestamp
    *   @return     string  the formatted timestamp
    *   @access     public
    */
    function formatTimeFull( $timestamp=null )
    {
        return $this->formatTime( $timestamp , I18N_DATETIME_FULL );
    }


}

?>
