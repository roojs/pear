<?php
/*********************************************************************************/
/**
 * iCalcreator v2.10.15
 * copyright (c) 2007-2011 Kjell-Inge Gustafsson kigkonsult
 * kigkonsult.se/iCalcreator/index.php
 * ical@kigkonsult.se
 *
 * Description:
 * This file is a PHP implementation of RFC 2445.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/*********************************************************************************/
/*********************************************************************************/
/*         A little setup                                                        */
/*********************************************************************************/
            /* your local language code */
// define( 'ICAL_LANG', 'sv' );
            // alt. autosetting
/*
$langstr     = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$pos         = strpos( $langstr, ';' );
if ($pos   !== false) {
  $langstr   = substr( $langstr, 0, $pos );
  $pos       = strpos( $langstr, ',' );
  if ($pos !== false) {
    $pos     = strpos( $langstr, ',' );
    $langstr = substr( $langstr, 0, $pos );
  }
  define( 'ICAL_LANG', $langstr );
}
*/
/*********************************************************************************/
/*         only for phpversion 5.1 and later,                                    */
/*         date management, default timezone setting                             */
/*         since 2.6.36 - 2010-12-31 */
if( substr( phpversion(), 0, 3 ) >= '5.1' )
  // && ( 'UTC' == date_default_timezone_get()))
  date_default_timezone_set( 'Asia/Hong_Kong' );
/*********************************************************************************/
/*         since 2.6.22 - 2010-09-25, do NOT remove!!                            */
require_once 'UtilityFunctions.php';
require_once 'calendarComponent.php';
require_once 'valarm.php';
require_once 'vevent.php';
require_once 'vfreebusy.php';
require_once 'vjournal.php';
require_once 'vtimezone.php';
require_once 'vtodo.php';



/*********************************************************************************/
/*         version, do NOT remove!!                                              */
define( 'ICALCREATOR_VERSION', 'iCalcreator 2.10.15' );
/*********************************************************************************/
/*********************************************************************************/
/**
 * vcalendar class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 */
class iCal_Vcalendar {
            //  calendar property variables
  var $calscale;
  var $method;
  var $prodid;
  var $version;
  var $xprop;
            //  container for calendar components
  var $components;
            //  component config variables
  var $allowEmpty;
  var $unique_id;
  var $language;
  var $directory;
  var $filename;
  var $url;
  var $delimiter;
  var $nl;
  var $format;
  var $dtzid;
            //  component internal variables
  var $attributeDelimiter;
  var $valueInit;
            //  component xCal declaration container
  var $xcaldecl;
/**
 * constructor for calendar object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 * @param array $config
 * @return void
 */
  function iCal_Vcalendar ( $config = array()) {
    $this->_makeVersion();
    $this->calscale   = null;
    $this->method     = null;
    $this->_makeUnique_id();
    $this->prodid     = null;
    $this->xprop      = array();
    $this->language   = null;
    $this->directory  = null;
    $this->filename   = null;
    $this->url        = null;
    $this->dtzid      = null;
/**
 *   language = <Text identifying a language, as defined in [RFC 1766]>
 */
    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

    $this->xcaldecl   = array();
    $this->components = array();
  }
/*********************************************************************************/
/**
 * Property Name: CALSCALE
 */
/**
 * creates formatted output for calendar property calscale
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @return string
 */
  function createCalscale() {
    if( empty( $this->calscale )) return FALSE;
    switch( $this->format ) {
      case 'xcal':
        return ' calscale="'.$this->calscale.'"'.$this->nl;
        break;
      default:
        return 'CALSCALE:'.$this->calscale.$this->nl;
        break;
    }
  }
/**
 * set calendar property calscale
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @param string $value
 * @return void
 */
  function setCalscale( $value ) {
    if( empty( $value )) return FALSE;
    $this->calscale = $value;
  }
/*********************************************************************************/
/**
 * Property Name: METHOD
 */
/**
 * creates formatted output for calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.9.7 - 2006-11-20
 * @return string
 */
  function createMethod() {
    if( empty( $this->method )) return FALSE;
    switch( $this->format ) {
      case 'xcal':
        return ' method="'.$this->method.'"'.$this->nl;
        break;
      default:
        return 'METHOD:'.$this->method.$this->nl;
        break;
    }
  }
/**
 * set calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-20-23
 * @param string $value
 * @return bool
 */
  function setMethod( $value ) {
    if( empty( $value )) return FALSE;
    $this->method = $value;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: PRODID
 *
 *  The identifier is RECOMMENDED to be the identical syntax to the
 * [RFC 822] addr-spec. A good method to assure uniqueness is to put the
 * domain name or a domain literal IP address of the host on which.. .
 */
/**
 * creates formatted output for calendar property prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.9.7 - 2006-11-20
 * @return string
 */
  function createProdid() {
    if( !isset( $this->prodid ))
      $this->_makeProdid();
    switch( $this->format ) {
      case 'xcal':
        return ' prodid="'.$this->prodid.'"'.$this->nl;
        break;
      default:
        return 'PRODID:'.$this->prodid.$this->nl;
        break;
    }
  }
/**
 * make default value for calendar prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.8 - 2009-12-30
 * @return void
 */
  function _makeProdid() {
    $this->prodid  = '-//'.$this->unique_id.'//NONSGML kigkonsult.se '.ICALCREATOR_VERSION.'//'.strtoupper( $this->language );
  }
/**
 * Conformance: The property MUST be specified once in an iCalendar object.
 * Description: The vendor of the implementation SHOULD assure that this
 * is a globally unique identifier; using some technique such as an FPI
 * value, as defined in [ISO 9070].
 */
/**
 * make default unique_id for calendar prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-10
 * @return void
 */
  function _makeUnique_id() {
    $this->unique_id  = ( isset( $_SERVER['SERVER_NAME'] )) ? gethostbyname( $_SERVER['SERVER_NAME'] ) : 'localhost';
  }
/*********************************************************************************/
/**
 * Property Name: VERSION
 *
 * Description: A value of "2.0" corresponds to this memo.
 */
/**
 * creates formatted output for calendar property version

 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.9.7 - 2006-11-20
 * @return string
 */
  function createVersion() {
    if( empty( $this->version ))
      $this->_makeVersion();
    switch( $this->format ) {
      case 'xcal':
        return ' version="'.$this->version.'"'.$this->nl;
        break;
      default:
        return 'VERSION:'.$this->version.$this->nl;
        break;
    }
  }
/**
 * set default calendar version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-10
 * @return void
 */
  function _makeVersion() {
    $this->version = '2.0';
  }
/**
 * set calendar version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-23
 * @param string $value
 * @return void
 */
  function setVersion( $value ) {
    if( empty( $value )) return FALSE;
    $this->version = $value;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: x-prop
 */
/**
 * creates formatted output for calendar property x-prop, iCal format only
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.3 - 2011-05-14
 * @return string
 */
  function createXprop() {
    if( 'xcal' == $this->format )
      return false;
    if( empty( $this->xprop ) || !is_array( $this->xprop )) return FALSE;
    $output = null;
    $toolbox = new calendarComponent();
    $toolbox->setConfig( $this->getConfig());
    foreach( $this->xprop as $label => $xpropPart ) {
      if( !isset($xpropPart['value']) || ( empty( $xpropPart['value'] ) && !is_numeric( $xpropPart['value'] ))) {
        $output  .= $toolbox->_createElement( $label );
        continue;
      }
      $attributes = $toolbox->_createParams( $xpropPart['params'], array( 'LANGUAGE' ));
      if( is_array( $xpropPart['value'] )) {
        foreach( $xpropPart['value'] as $pix => $theXpart )
          $xpropPart['value'][$pix] = $toolbox->_strrep( $theXpart );
        $xpropPart['value']  = implode( ',', $xpropPart['value'] );
      }
      else
        $xpropPart['value'] = $toolbox->_strrep( $xpropPart['value'] );
      $output    .= $toolbox->_createElement( $label, $attributes, $xpropPart['value'] );
    }
    return $output;
  }
/**
 * set calendar property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.3 - 2011-05-14
 * @param string $label
 * @param string $value
 * @param array $params optional
 * @return bool
 */
  function setXprop( $label, $value, $params=FALSE ) {
    if( empty( $label )) return FALSE;
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = null; else return FALSE;
    $xprop           = array( 'value' => $value );
    $xprop['params'] = iCal_UtilityFunctions::_setParams( $params );
    if( !is_array( $this->xprop )) $this->xprop = array();
    $this->xprop[strtoupper( $label )] = $xprop;
    return TRUE;
  }
/*********************************************************************************/
/**
 * delete calendar property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $propName, bool FALSE => X-property
 * @param int $propix, optional, if specific property is wanted in case of multiply occurences
 * @return bool, if successfull delete
 */
  function deleteProperty( $propName=FALSE, $propix=FALSE ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( !$propix )
      $propix = ( isset( $this->propdelix[$propName] ) && ( 'X-PROP' != $propName )) ? $this->propdelix[$propName] + 2 : 1;
    $this->propdelix[$propName] = --$propix;
    $return = FALSE;
    switch( $propName ) {
      case 'CALSCALE':
        if( isset( $this->calscale )) {
          $this->calscale = null;
          $return = TRUE;
        }
        break;
      case 'METHOD':
        if( isset( $this->method )) {
          $this->method   = null;
          $return = TRUE;
        }
        break;
      default:
        $reduced = array();
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) { unset( $this->propdelix[$propName] ); return FALSE; }
          foreach( $this->xprop as $k => $a ) {
            if(( $k != $propName ) && !empty( $a ))
              $reduced[$k] = $a;
          }
        }
        else {
          if( count( $this->xprop ) <= $propix )  return FALSE;
          $xpropno = 0;
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix != $xpropno )
              $reduced[$xpropkey] = $xpropvalue;
            $xpropno++;
          }
        }
        $this->xprop = $reduced;
        if( empty( $this->xprop )) {
          unset( $this->propdelix[$propName] );
          return FALSE;
        }
        return TRUE;
    }
    return $return;
  }
/**
 * get calendar property value/params
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-04-16
 * @param string $propName, optional
 * @param int $propix, optional, if specific property is wanted in case of multiply occurences
 * @param bool $inclParam=FALSE
 * @return mixed
 */
  function getProperty( $propName=FALSE, $propix=FALSE, $inclParam=FALSE ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( 'X-PROP' == $propName ) {
      if( !$propix )
        $propix = ( isset( $this->propix[$propName] )) ? $this->propix[$propName] + 2 : 1;
      $this->propix[$propName] = --$propix;
    }
    switch( $propName ) {
      case 'ATTENDEE':
      case 'CATEGORIES':
      case 'DTSTART':
      case 'LOCATION':
      case 'ORGANIZER':
      case 'PRIORITY':
      case 'RESOURCES':
      case 'STATUS':
      case 'SUMMARY':
      case 'RECURRENCE-ID-UID':
      case 'R-UID':
      case 'UID':
        $output = array();
        foreach ( $this->components as $cix => $component) {
          if( !in_array( $component->objName, array('vevent', 'vtodo', 'vjournal', 'vfreebusy' )))
            continue;
          if(( 'ATTENDEE' == $propName ) || ( 'CATEGORIES' == $propName ) || ( 'RESOURCES' == $propName )) {
            $component->_getProperties( $propName, $output );
            continue;
          }
          elseif(( 3 < strlen( $propName )) && ( 'UID' == substr( $propName, -3 ))) {
            if( FALSE !== ( $content = $component->getProperty( 'RECURRENCE-ID' )))
              $content = $component->getProperty( 'UID' );
          }
          elseif( FALSE === ( $content = $component->getProperty( $propName )))
            continue;
          if( FALSE === $content )
            continue;
          elseif( is_array( $content )) {
            if( isset( $content['year'] )) {
              $key  = sprintf( '%04d%02d%02d', $content['year'], $content['month'], $content['day'] );
              if( !isset( $output[$key] ))
                $output[$key] = 1;
              else
                $output[$key] += 1;
            }
            else {
              foreach( $content as $partValue => $partCount ) {
                if( !isset( $output[$partValue] ))
                  $output[$partValue] = $partCount;
                else
                  $output[$partValue] += $partCount;
              }
            }
          } // end elseif( is_array( $content )) {
          elseif( !isset( $output[$content] ))
            $output[$content] = 1;
          else
            $output[$content] += 1;
        } // end foreach ( $this->components as $cix => $component)
        if( !empty( $output ))
          ksort( $output );
        return $output;
        break;

      case 'CALSCALE':
        return ( !empty( $this->calscale )) ? $this->calscale : FALSE;
        break;
      case 'METHOD':
        return ( !empty( $this->method )) ? $this->method : FALSE;
        break;
      case 'PRODID':
        if( empty( $this->prodid ))
          $this->_makeProdid();
        return $this->prodid;
        break;
      case 'VERSION':
        return ( !empty( $this->version )) ? $this->version : FALSE;
        break;
      default:
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) return FALSE;
          return ( $inclParam ) ? array( $propName, $this->xprop[$propName] )
                                : array( $propName, $this->xprop[$propName]['value'] );
        }
        else {
          if( empty( $this->xprop )) return FALSE;
          $xpropno = 0;
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix == $xpropno )
              return ( $inclParam ) ? array( $xpropkey, $this->xprop[$xpropkey] )
                                    : array( $xpropkey, $this->xprop[$xpropkey]['value'] );
            else
              $xpropno++;
          }
          unset( $this->propix[$propName] );
          return FALSE; // not found ??
        }
    }
    return FALSE;
  }
/**
 * general iCal_Vcalendar property setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.13 - 2007-11-04
 * @param mixed $args variable number of function arguments,
 *                    first argument is ALWAYS component name,
 *                    second ALWAYS component value!
 * @return bool
 */
  function setProperty () {
    $numargs    = func_num_args();
    if( 1 > $numargs )
      return FALSE;
    $arglist    = func_get_args();
    $arglist[0] = strtoupper( $arglist[0] );
    switch( $arglist[0] ) {
      case 'CALSCALE':
        return $this->setCalscale( $arglist[1] );
      case 'METHOD':
        return $this->setMethod( $arglist[1] );
      case 'VERSION':
        return $this->setVersion( $arglist[1] );
      default:
        if( !isset( $arglist[1] )) $arglist[1] = null;
        if( !isset( $arglist[2] )) $arglist[2] = null;
        return $this->setXprop( $arglist[0], $arglist[1], $arglist[2] );
    }
    return FALSE;
  }
/*********************************************************************************/
/**
 * get iCal_Vcalendar config values or * calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 * @param mixed $config
 * @return value
 */
  function getConfig( $config = FALSE ) {
    if( !$config ) {
      $return = array();
      $return['ALLOWEMPTY']  = $this->getConfig( 'ALLOWEMPTY' );
      $return['DELIMITER']   = $this->getConfig( 'DELIMITER' );
      $return['DIRECTORY']   = $this->getConfig( 'DIRECTORY' );
      $return['FILENAME']    = $this->getConfig( 'FILENAME' );
      $return['DIRFILE']     = $this->getConfig( 'DIRFILE' );
      $return['FILESIZE']    = $this->getConfig( 'FILESIZE' );
      $return['FORMAT']      = $this->getConfig( 'FORMAT' );
      if( FALSE !== ( $lang  = $this->getConfig( 'LANGUAGE' )))
        $return['LANGUAGE']  = $lang;
      $return['NEWLINECHAR'] = $this->getConfig( 'NEWLINECHAR' );
      $return['UNIQUE_ID']   = $this->getConfig( 'UNIQUE_ID' );
      if( FALSE !== ( $url   = $this->getConfig( 'URL' )))
        $return['URL']       = $url;
      $return['TZID']        = $this->getConfig( 'TZID' );
      return $return;
    }
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        return $this->allowEmpty;
        break;
      case 'COMPSINFO':
        unset( $this->compix );
        $info = array();
        foreach( $this->components as $cix => $component ) {
          if( empty( $component )) continue;
          $info[$cix]['ordno'] = $cix + 1;
          $info[$cix]['type']  = $component->objName;
          $info[$cix]['uid']   = $component->getProperty( 'uid' );
          $info[$cix]['props'] = $component->getConfig( 'propinfo' );
          $info[$cix]['sub']   = $component->getConfig( 'compsinfo' );
        }
        return $info;
        break;
      case 'DELIMITER':
        return $this->delimiter;
        break;
      case 'DIRECTORY':
        if( empty( $this->directory ))
          $this->directory = '.';
        return $this->directory;
        break;
      case 'DIRFILE':
        return $this->getConfig( 'directory' ).$this->getConfig( 'delimiter' ).$this->getConfig( 'filename' );
        break;
      case 'FILEINFO':
        return array( $this->getConfig( 'directory' )
                    , $this->getConfig( 'filename' )
                    , $this->getConfig( 'filesize' ));
        break;
      case 'FILENAME':
        if( empty( $this->filename )) {
          if( 'xcal' == $this->format )
            $this->filename = date( 'YmdHis' ).'.xml'; // recommended xcs.. .
          else
            $this->filename = date( 'YmdHis' ).'.ics';
        }
        return $this->filename;
        break;
      case 'FILESIZE':
        $size    = 0;
        if( empty( $this->url )) {
          $dirfile = $this->getConfig( 'dirfile' );
          if( !is_file( $dirfile ) || ( FALSE === ( $size = filesize( $dirfile ))))
            $size = 0;
          clearstatcache();
        }
        return $size;
        break;
      case 'FORMAT':
        return ( $this->format == 'xcal' ) ? 'xCal' : 'iCal';
        break;
      case 'LANGUAGE':
         /* get language for calendar component as defined in [RFC 1766] */
        return $this->language;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        return $this->nl;
        break;
      case 'TZID':
        return $this->dtzid;
        break;
      case 'UNIQUE_ID':
        return $this->unique_id;
        break;
      case 'URL':
        if( !empty( $this->url ))
          return $this->url;
        else
          return FALSE;
        break;
    }
  }
/**
 * general iCal_Vcalendar config setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.10 - 2011-09-19
 * @param mixed  $config
 * @param string $value
 * @return void
 */
  function setConfig( $config, $value = FALSE) {
    if( is_array( $config )) {
      $ak = array_keys( $config );
      foreach( $ak as $k ) {
        if( 'DIRECTORY' == strtoupper( $k )) {
          if( FALSE === $this->setConfig( 'DIRECTORY', $config[$k] ))
            return FALSE;
          unset( $config[$k] );
          break;
        }
      }
      foreach( $config as $cKey => $cValue ) {
        if( FALSE === $this->setConfig( $cKey, $cValue ))
          return FALSE;
      }
      return TRUE;
    }
    $res = FALSE;
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        $this->allowEmpty = $value;
        $subcfg  = array( 'ALLOWEMPTY' => $value );
        $res = TRUE;
        break;
      case 'DELIMITER':
        $this->delimiter = $value;
        return TRUE;
        break;
      case 'DIRECTORY':
        $value   = trim( $value );
        $del     = $this->getConfig('delimiter');
        if( $del == substr( $value, ( 0 - strlen( $del ))))
          $value = substr( $value, 0, ( strlen( $value ) - strlen( $del )));
        if( is_dir( $value )) {
            /* local directory */
          clearstatcache();
          $this->directory = $value;
          $this->url       = null;
          return TRUE;
        }
        else
          return FALSE;
        break;
      case 'FILENAME':
        $value   = trim( $value );
        if( !empty( $this->url )) {
            /* remote directory+file -> URL */
          $this->filename = $value;
          return TRUE;
        }
        $dirfile = $this->getConfig( 'directory' ).$this->getConfig( 'delimiter' ).$value;
        if( file_exists( $dirfile )) {
            /* local file exists */
          if( is_readable( $dirfile ) || is_writable( $dirfile )) {
            clearstatcache();
            $this->filename = $value;
            return TRUE;
          }
          else
            return FALSE;
        }
        elseif( is_readable($this->getConfig( 'directory' ) ) || is_writable( $this->getConfig( 'directory' ) )) {
            /* read- or writable directory */
          $this->filename = $value;
          return TRUE;
        }
        else
          return FALSE;
        break;
      case 'FORMAT':
        $value   = trim( strtolower( $value ));
        if( 'xcal' == $value ) {
          $this->format             = 'xcal';
          $this->attributeDelimiter = $this->nl;
          $this->valueInit          = null;
        }
        else {
          $this->format             = null;
          $this->attributeDelimiter = ';';
          $this->valueInit          = ':';
        }
        $subcfg  = array( 'FORMAT' => $value );
        $res = TRUE;
        break;
      case 'LANGUAGE':
         // set language for calendar component as defined in [RFC 1766]
        $value   = trim( $value );
        $this->language = $value;
        $subcfg  = array( 'LANGUAGE' => $value );
        $res = TRUE;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        $this->nl = $value;
        $subcfg  = array( 'NL' => $value );
        $res = TRUE;
        break;
      case 'TZID':
        $this->dtzid = $value;
        $subcfg  = array( 'TZID' => $value );
        $res = TRUE;
        break;
      case 'UNIQUE_ID':
        $value   = trim( $value );
        $this->unique_id = $value;
        $subcfg  = array( 'UNIQUE_ID' => $value );
        $res = TRUE;
        break;
      case 'URL':
            /* remote file - URL */
        $value     = trim( $value );
        $value     = str_replace( 'HTTP://',   'http://', $value );
        $value     = str_replace( 'WEBCAL://', 'http://', $value );
        $value     = str_replace( 'webcal://', 'http://', $value );
        $this->url = $value;
        $this->directory = null;
        $parts     = pathinfo( $value );
        return $this->setConfig( 'filename',  $parts['basename'] );
        break;
      default:  // any unvalid config key.. .
        return TRUE;
    }
    if( !$res ) return FALSE;
    if( isset( $subcfg ) && !empty( $this->components )) {
      foreach( $subcfg as $cfgkey => $cfgvalue ) {
        foreach( $this->components as $cix => $component ) {
          $res = $component->setConfig( $cfgkey, $cfgvalue, TRUE );
          if( !$res )
            break 2;
          $this->components[$cix] = $component->copy(); // PHP4 compliant
        }
      }
    }
    return $res;
  }
/*********************************************************************************/
/**
 * add calendar component to container
 *
 * alias to setComponent
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 1.x.x - 2007-04-24
 * @param object $component calendar component
 * @return void
 */
  function addComponent( $component ) {
    $this->setComponent( $component );
  }
/**
 * delete calendar component from container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $arg1 ordno / component type / component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return void
 */
  function deleteComponent( $arg1, $arg2=FALSE  ) {
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) {
      $argType = 'INDEX';
      $index   = (int) $arg1 - 1;
    }
    elseif(( strlen( $arg1 ) <= strlen( 'vfreebusy' )) && ( FALSE === strpos( $arg1, '@' ))) {
      $argType = strtolower( $arg1 );
      $index   = ( !empty( $arg2 ) && ctype_digit( (string) $arg2 )) ? (( int ) $arg2 - 1 ) : 0;
    }
    $cix1dC = 0;
    foreach ( $this->components as $cix => $component) {
      if( empty( $component )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix )) {
        unset( $this->components[$cix] );
        return TRUE;
      }
      elseif( $argType == $component->objName ) {
        if( $index == $cix1dC ) {
          unset( $this->components[$cix] );
          return TRUE;
        }
        $cix1dC++;
      }
      elseif( !$argType && ($arg1 == $component->getProperty( 'uid' ))) {
        unset( $this->components[$cix] );
        return TRUE;
      }
    }
    return FALSE;
  }
/**
 * get calendar component from container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.1 - 2011-04-16
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return object
 */
  function getComponent( $arg1=FALSE, $arg2=FALSE ) {
    $index = $argType = null;
    if ( !$arg1 ) { // first or next in component chain
      $argType = 'INDEX';
      $index   = $this->compix['INDEX'] = ( isset( $this->compix['INDEX'] )) ? $this->compix['INDEX'] + 1 : 1;
    }
    elseif ( ctype_digit( (string) $arg1 )) { // specific component in chain
      $argType = 'INDEX';
      $index   = (int) $arg1;
      unset( $this->compix );
    }
    elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
      $arg2  = implode( '-', array_keys( $arg1 ));
      $index = $this->compix[$arg2] = ( isset( $this->compix[$arg2] )) ? $this->compix[$arg2] + 1 : 1;
      $dateProps  = array( 'DTSTART', 'DTEND', 'DUE', 'CREATED', 'COMPLETED', 'DTSTAMP', 'LAST-MODIFIED', 'RECURRENCE-ID' );
      $otherProps = array( 'ATTENDEE', 'CATEGORIES', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID' );
    }
    elseif(( strlen( $arg1 ) <= strlen( 'vfreebusy' )) && ( FALSE === strpos( $arg1, '@' ))) { // object class name
      unset( $this->compix['INDEX'] );
      $argType = strtolower( $arg1 );
      if( !$arg2 )
        $index = $this->compix[$argType] = ( isset( $this->compix[$argType] )) ? $this->compix[$argType] + 1 : 1;
      elseif( isset( $arg2 ) && ctype_digit( (string) $arg2 ))
        $index = (int) $arg2;
    }
    elseif(( strlen( $arg1 ) > strlen( 'vfreebusy' )) && ( FALSE !== strpos( $arg1, '@' ))) { // UID as 1st argument
      if( !$arg2 )
        $index = $this->compix[$arg1] = ( isset( $this->compix[$arg1] )) ? $this->compix[$arg1] + 1 : 1;
      elseif( isset( $arg2 ) && ctype_digit( (string) $arg2 ))
        $index = (int) $arg2;
    }
    if( isset( $index ))
      $index  -= 1;
    $ckeys = array_keys( $this->components );
    if( !empty( $index) && ( $index > end(  $ckeys )))
      return FALSE;
    $cix1gC = 0;
    foreach ( $this->components as $cix => $component) {
      if( empty( $component )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix ))
        return $component->copy();
      elseif( $argType == $component->objName ) {
        if( $index == $cix1gC )
          return $component->copy();
        $cix1gC++;
      }
      elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
        $hit = FALSE;
        foreach( $arg1 as $pName => $pValue ) {
          $pName = strtoupper( $pName );
          if( !in_array( $pName, $dateProps ) && !in_array( $pName, $otherProps ))
            continue;
          if(( 'ATTENDEE' == $pName ) || ( 'CATEGORIES' == $pName ) || ( 'RESOURCES' == $pName )) { // multiple ocurrence may occur
            $propValues = array();
            $component->_getProperties( $pName, $propValues );
            $propValues = array_keys( $propValues );
            $hit = ( in_array( $pValue, $propValues )) ? TRUE : FALSE;
            continue;
          } // end   if(( 'CATEGORIES' == $propName ) || ( 'RESOURCES' == $propName )) { // multiple ocurrence may occur
          if( FALSE === ( $value = $component->getProperty( $pName ))) { // single ocurrency
            $hit = FALSE; // missing property
            continue;
          }
          if( 'SUMMARY' == $pName ) { // exists within (any case)
            $hit = ( FALSE !== stripos( $d, $pValue )) ? TRUE : FALSE;
            continue;
          }
          if( in_array( strtoupper( $pName ), $dateProps )) {
            $valuedate = sprintf( '%04d%02d%02d', $value['year'], $value['month'], $value['day'] );
            if( 8 < strlen( $pValue )) {
              if( isset( $value['hour'] )) {
                if( 'T' == substr( $pValue, 8, 1 ))
                  $pValue = str_replace( 'T', '', $pValue );
                $valuedate .= sprintf( '%02d%02d%02d', $value['hour'], $value['min'], $value['sec'] );
              }
              else
                $pValue = substr( $pValue, 0, 8 );
            }
            $hit = ( $pValue == $valuedate ) ? TRUE : FALSE;
            continue;
          }
          elseif( !is_array( $value ))
            $value = array( $value );
          foreach( $value as $part ) {
            $part = ( FALSE !== strpos( $part, ',' )) ? explode( ',', $part ) : array( $part );
            foreach( $part as $subPart ) {
              if( $pValue == $subPart ) {
                $hit = TRUE;
                continue 2;
              }
            }
          }
          $hit = FALSE; // no hit in property
        } // end  foreach( $arg1 as $pName => $pValue )
        if( $hit ) {
          if( $index == $cix1gC )
            return $component->copy();
          $cix1gC++;
        }
      } // end elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
      elseif( !$argType && ($arg1 == $component->getProperty( 'uid' ))) { // UID
        if( $index == $cix1gC )
          return $component->copy();
        $cix1gC++;
      }
    } // end foreach ( $this->components.. .
            /* not found.. . */
    unset( $this->compix );
    return FALSE;
  }
/**
 * create new calendar component, already included within calendar
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.33 - 2011-01-03
 * @param string $compType component type
 * @return object (reference)
 */
  function & newComponent( $compType ) {
    $config = $this->getConfig();
    $keys   = array_keys( $this->components );
    $ix     = end( $keys) + 1;
    switch( strtoupper( $compType )) {
      case 'EVENT':
      case 'VEVENT':
        $this->components[$ix] = new vevent( $config );
        break;
      case 'TODO':
      case 'VTODO':
        $this->components[$ix] = new vtodo( $config );
        break;
      case 'JOURNAL':
      case 'VJOURNAL':
        $this->components[$ix] = new vjournal( $config );
        break;
      case 'FREEBUSY':
      case 'VFREEBUSY':
        $this->components[$ix] = new vfreebusy( $config );
        break;
      case 'TIMEZONE':
      case 'VTIMEZONE':
        array_unshift( $this->components, new vtimezone( $config ));
        $ix = 0;
        break;
      default:
        return FALSE;
    }
    return $this->components[$ix];
  }
/**
 * select components from calendar on date or selectOption basis
 *
 * Ensure DTSTART is set for every component.
 * No date controls occurs.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.13 - 2011-09-23
 * @param mixed $startY optional, start Year, default current Year ALT. array selecOptions
 * @param int $startM optional,   start Month, default current Month
 * @param int $startD optional,   start Day, default current Day
 * @param int $endY optional,     end Year, default $startY
 * @param int $endY optional,     end Month, default $startM
 * @param int $endY optional,     end Day, default $startD
 * @param mixed $cType optional,  calendar component type(-s), default FALSE=all else string/array type(-s)
 * @param bool $flat optional,    FALSE (default) => output : array[Year][Month][Day][]
 *                                TRUE => output : array[] (ignores split)
 * @param bool $any optional,     TRUE (default) - select component that take place within period
 *                                FALSE - only components that starts within period
 * @param bool $split optional,   TRUE (default) - one component copy every day it take place during the
 *                                       period (implies flat=FALSE)
 *                                FALSE - one occurance of component only in output array
 * @return array or FALSE
 */
  function selectComponents(
            $startY=FALSE,
            $startM=FALSE,
            $startD=FALSE,
            $endY=FALSE,
            $endM=FALSE,
            $endD=FALSE,
            $cType=FALSE,
            $flat=FALSE,
            $any=TRUE,
            $split=TRUE ) {
            /* check  if empty calendar */
    if( 0 >= count( $this->components )) {
        return FALSE;
    }
    
    if( is_array( $startY )) {
         return $this->selectComponents2( $startY );
    }

    /* check default dates */
    if( !$startY ) $startY = date( 'Y' );
    if( !$startM ) $startM = date( 'm' );
    if( !$startD ) $startD = date( 'd' );
    $startDate = mktime( 0, 0, 0, $startM, $startD, $startY );
    if( !$endY )   $endY   = $startY;
    if( !$endM )   $endM   = $startM;
    if( !$endD )   $endD   = $startD;
    $endDate   = mktime( 23, 59, 59, $endM, $endD, $endY );
    
    
    
    //echo 'selectComp arg='.date( 'Y-m-d H:i:s', $startDate).' -- '.date( 'Y-m-d H:i:s', $endDate)."<br />\n"; $tcnt = 0;// test ###
    /* check component types */
    $validTypes = array('vevent', 'vtodo', 'vjournal', 'vfreebusy' );
    if( is_array( $cType )) {
        foreach( $cType as $cix => $theType ) {
            $cType[$cix] = $theType = strtolower( $theType );
            if( !in_array( $theType, $validTypes )) {
                $cType[$cix] = 'vevent';
            }
        }
        $cType = array_unique( $cType );
        
    } else if( !empty( $cType )) {
        $cType = strtolower( $cType );
        
        if( !in_array( $cType, $validTypes )) {
            $cType = array( 'vevent' );
        } else {
            $cType = array( $cType );
        }
        
    } else {
        $cType = $validTypes;
    }
    if( 0 >= count( $cType )) {
      $cType = $validTypes;
    }
    
    if(( TRUE === $flat ) && ( TRUE === $split )) {// invalid combination
         $split = FALSE;
    }
      
    // end validate and reset arguments..
      
      
            /* iterate components */
    $result = array();
    foreach ( $this->components as $cix => $component ) {
        //print_r($component);
        if( empty( $component )) {
            continue;
        }
        unset( $start );
        
        /* deselect unvalid type components */
        if( !in_array( $component->objName, $cType )) {
            continue;
        }
        
        $start = $component->getProperty( 'dtstart' );
              /* select due when dtstart is missing */
        if( empty( $start ) &&
            ( $component->objName == 'vtodo' ) &&
            ( FALSE === ( $start = $component->getProperty( 'due' )))) {
            continue;
        }
        
        $dtendExist = $dueExist = $durationExist = $endAllDayEvent = FALSE;
        unset( $end, $startWdate, $endWdate, $rdurWsecs, $rdur, $exdatelist, $workstart, $workend, $endDateFormat ); // clean up
        
        $startWdate = iCal_UtilityFunctions::_date2timestamp( $start );
        $startDateFormat = ( isset( $start['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
        
        /* get end date from dtend/due/duration properties */
        $end = $component->getProperty( 'dtend' );
        if( !empty( $end )) {
            $dtendExist = TRUE;
            $endDateFormat = ( isset( $end['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
        }
        if( empty( $end ) && ( $component->objName == 'vtodo' )) {
            $end = $component->getProperty( 'due' );
            if( !empty( $end )) {
                $dueExist = TRUE;
                $endDateFormat = ( isset( $end['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
            }
        }
        if( !empty( $end ) && !isset( $end['hour'] )) {
            /* a DTEND without time part regards an event that ends the day before,
               for an all-day event DTSTART=20071201 DTEND=20071202 (taking place 20071201!!! */
            $endAllDayEvent = TRUE;
            $endWdate = mktime( 23, 59, 59, $end['month'], ($end['day'] - 1), $end['year'] );
            $end['year']  = date( 'Y', $endWdate );
            $end['month'] = date( 'm', $endWdate );
            $end['day']   = date( 'd', $endWdate );
            $end['hour']  = 23;
            $end['min']   = $end['sec'] = 59;
        }
        if( empty( $end )) {
            $end = $component->getProperty( 'duration', FALSE, FALSE, TRUE );// in dtend (array) format
            if( !empty( $end )) {
              $durationExist = TRUE;
            }
        
            $endDateFormat = ( isset( $start['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
            // if( !empty($end))  echo 'selectComp 4 start='.implode('-',$start).' end='.implode('-',$end)."<br />\n"; // test ###
        }
        
        if( empty( $end )) { // assume one day duration if missing end date
              $end = array(
                'year' => $start['year'],
                'month' => $start['month'],
                'day' => $start['day'],
                'hour' => 23,
                'min' => 59,
                'sec' => 59
            );
        }
  // if( isset($end))  echo 'selectComp 5 start='.implode('-',$start).' end='.implode('-',$end)."<br />\n"; // test ###
        $endWdate = iCal_UtilityFunctions::_date2timestamp( $end );
        if( $endWdate < $startWdate ) { // MUST be after start date!!
            $end = array(
                'year' => $start['year'],
                'month' => $start['month'],
                'day' => $start['day'],
                'hour' => 23,
                'min' => 59,
                'sec' => 59
            );
            $endWdate = iCal_UtilityFunctions::_date2timestamp( $end );
        }
        
        
        
        $rdurWsecs  = $endWdate - $startWdate; // compute event (component) duration in seconds
              /* make a list of optional exclude dates for component occurence from exrule and exdate */
        $exdatelist = array();
        $workstart  = iCal_UtilityFunctions::_timestamp2date(( $startDate - $rdurWsecs ), 6);
        $workend    = iCal_UtilityFunctions::_timestamp2date(( $endDate + $rdurWsecs ), 6);
        
        while( FALSE !== ( $exrule = $component->getProperty( 'exrule' ))) {    // check exrule
            iCal_UtilityFunctions::_recur2date( $exdatelist, $exrule, $start, $workstart, $workend );
        }
        
        while( FALSE !== ( $exdate = $component->getProperty( 'exdate' ))) {  // check exdate
            foreach( $exdate as $theExdate ) {
                $exWdate = iCal_UtilityFunctions::_date2timestamp( $theExdate );
                $exWdate = mktime( 0, 0, 0, date( 'm', $exWdate ), date( 'd', $exWdate ), date( 'Y', $exWdate ) ); // on a day-basis !!!
                if((( $startDate - $rdurWsecs ) <= $exWdate ) && ( $endDate >= $exWdate )) {
                      $exdatelist[$exWdate] = TRUE;
                }
            } // end - foreach( $exdate as $theExdate )
        }  // end - check exdate
        
        //var_dump(array($startWdate ,$startDate , $startWdate , $endDate ));exit;
         /* select only components with startdate within period */
        if(( $startWdate >= $startDate ) && ( $startWdate <= $endDate )) {
              /* add the selected component (WITHIN valid dates) to output array */
            if( $flat ) {
                $result[$component->getProperty( 'UID' )] = $component->copy(); // copy original to output;
                
            } elseif( $split ) { // split the original component
                if( $endWdate > $endDate ) {
                    $endWdate = $endDate;     // use period end date
                }
                
                $rstart = $startWdate;
                if( $rstart < $startDate ){
                    $rstart = $startDate; // use period start date
                }
                $startYMD = date( 'Ymd', $rstart );
                $endYMD   = date( 'Ymd', $endWdate );
                $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                
                while( date( 'Ymd', $rstart ) <= $endYMD ) { // iterate
                    $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                    if( isset( $exdatelist[$checkDate] )) { // exclude any recurrence date, found in exdatelist
                      $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
                      continue;
                    }
                    if( date( 'Ymd', $rstart ) > $startYMD ) // date after dtstart
                      $datestring = date( $startDateFormat, mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart )));
                    else
                      $datestring = date( $startDateFormat, $rstart );
                    if( isset( $start['tz'] ))
                      $datestring .= ' '.$start['tz'];
                    // echo "X-CURRENT-DTSTART 3 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component->setProperty( 'X-CNT', $tcnt ); // test ###
                    $component->setProperty( 'X-CURRENT-DTSTART', $datestring );
                    if( $dtendExist || $dueExist || $durationExist ) {
                      if( date( 'Ymd', $rstart ) < $endYMD ) // not the last day
                        $tend = mktime( 23, 59, 59, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ));
                      else
                        $tend = mktime( date( 'H', $endWdate ), date( 'i', $endWdate ), date( 's', $endWdate ), date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                      $datestring = date( $endDateFormat, $tend );
                      if( isset( $end['tz'] ))
                        $datestring .= ' '.$end['tz'];
                      $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                      $component->setProperty( $propName, $datestring );
                    } // end if( $dtendExist || $dueExist || $durationExist )
                    $wd = getdate( $rstart );
                    if (!isset($result[$wd['year']][$wd['mon']][$wd['mday']][$component->getProperty( 'UID' )] )) {
                        $result[$wd['year']][$wd['mon']][$wd['mday']][$component->getProperty( 'UID' )] = $component->copy(); // copy to output
                    }
                    $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
                } // end while( $rstart <= $endWdate )
                
                //print_R($result);exit;
                
          } // end if( $split )   -  else use component date
        } // end if(( $startWdate >= $startDate ) && ( $startWdate <= $endDate ))
              /* if 'any' components, check components with reccurrence rules, removing all excluding dates */
        if( TRUE === $any ) {
              /* make a list of optional repeating dates for component occurence, rrule, rdate */
          $recurlist = array();
          while( FALSE !== ( $rrule = $component->getProperty( 'rrule' )))    // check rrule
            iCal_UtilityFunctions::_recur2date( $recurlist, $rrule, $start, $workstart, $workend );
          foreach( $recurlist as $recurkey => $recurvalue ) // key=match date as timestamp
            $recurlist[$recurkey] = $rdurWsecs; // add duration in seconds
          while( FALSE !== ( $rdate = $component->getProperty( 'rdate' ))) {  // check rdate
            foreach( $rdate as $theRdate ) {
              if( is_array( $theRdate ) && ( 2 == count( $theRdate )) &&  // all days within PERIOD
                     array_key_exists( '0', $theRdate ) &&  array_key_exists( '1', $theRdate )) {
                $rstart = iCal_UtilityFunctions::_date2timestamp( $theRdate[0] );
                if(( $rstart < ( $startDate - $rdurWsecs )) || ( $rstart > $endDate ))
                  continue;
                if( isset( $theRdate[1]['year'] )) // date-date period
                  $rend = iCal_UtilityFunctions::_date2timestamp( $theRdate[1] );
                else {                             // date-duration period
                  $rend = iCal_UtilityFunctions::_duration2date( $theRdate[0], $theRdate[1] );
                  $rend = iCal_UtilityFunctions::_date2timestamp( $rend );
                }
                while( $rstart < $rend ) {
                  $recurlist[$rstart] = $rdurWsecs; // set start date for recurrence instance + rdate duration in seconds
                  $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
                }
              } // PERIOD end
              else { // single date
                $theRdate = iCal_UtilityFunctions::_date2timestamp( $theRdate );
                if((( $startDate - $rdurWsecs ) <= $theRdate ) && ( $endDate >= $theRdate ))
                  $recurlist[$theRdate] = $rdurWsecs; // set start date for recurrence instance + event duration in seconds
              }
            }
          }  // end - check rdate
          if( 0 < count( $recurlist )) {
            ksort( $recurlist );
            $xRecurrence = 1;
            foreach( $recurlist as $recurkey => $durvalue ) {
  // echo "recurKey=".date( 'Y-m-d H:i:s', $recurkey ).' dur='.iCal_UtilityFunctions::offsetSec2His( $durvalue )."<br />\n"; // test ###;
              if((( $startDate - $rdurWsecs ) > $recurkey ) || ( $endDate < $recurkey )) // not within period
                continue;
              $checkDate = mktime( 0, 0, 0, date( 'm', $recurkey ), date( 'd', $recurkey ), date( 'Y', $recurkey ) ); // on a day-basis !!!
              if( isset( $exdatelist[$checkDate] )) // check excluded dates
                continue;
              if( $startWdate >= $recurkey ) // exclude component start date
                continue;
              $component2   = $component->copy();
              $rstart = $recurkey;
              $rend   = $recurkey + $durvalue;
             /* add repeating components within valid dates to output array, only start date set */
              if( $flat ) {
                $datestring = date( $startDateFormat, $recurkey );
                if( isset( $start['tz'] ))
                  $datestring .= ' '.$start['tz'];
  // echo "X-CURRENT-DTSTART 0 =$datestring tcnt =".++$tcnt."<br />";$component2->setProperty( 'X-CNT', $tcnt ); // test ###
                $component2->setProperty( 'X-CURRENT-DTSTART', $datestring );
                if( $dtendExist || $dueExist || $durationExist ) {
                  $datestring = date( $endDateFormat, $recurkey + $durvalue );   // fixa korrekt sluttid
                  if( isset( $end['tz'] ))
                    $datestring .= ' '.$end['tz'];
                  $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                  $component2->setProperty( $propName, $datestring );
                } // end if( $dtendExist || $dueExist || $durationExist )
                $component2->setProperty( 'X-RECURRENCE', ++$xRecurrence );
                $result[$component2->getProperty( 'UID' )] = $component2->copy(); // copy to output
              }
             /* add repeating components within valid dates to output array, one each day */
              elseif( $split ) {
                if( $rend > $endDate )
                  $rend = $endDate;
                $startYMD = date( 'Ymd', $rstart );
                $endYMD   = date( 'Ymd', $rend );
  // echo "splitStart=".date( 'Y-m-d H:i:s', $rstart ).' end='.date( 'Y-m-d H:i:s', $rend )."<br />\n"; // test ###;
                while( $rstart <= $rend ) { // iterate.. .
                  $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                  if( isset( $exdatelist[$checkDate] ))  // exclude any recurrence START date, found in exdatelist
                    break;
  // echo "checking date after startdate=".date( 'Y-m-d H:i:s', $rstart ).' mot '.date( 'Y-m-d H:i:s', $startDate )."<br />"; // test ###;
                  if( $rstart >= $startDate ) {    // date after dtstart
                    if( date( 'Ymd', $rstart ) > $startYMD ) // date after dtstart
                      $datestring = date( $startDateFormat, $checkDate );
                    else
                      $datestring = date( $startDateFormat, $rstart );
                    if( isset( $start['tz'] ))
                      $datestring .= ' '.$start['tz'];
  //echo "X-CURRENT-DTSTART 1 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component2->setProperty( 'X-CNT', $tcnt ); // test ###
                    $component2->setProperty( 'X-CURRENT-DTSTART', $datestring );
                    if( $dtendExist || $dueExist || $durationExist ) {
                      if( date( 'Ymd', $rstart ) < $endYMD ) // not the last day
                        $tend = mktime( 23, 59, 59, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ));
                      else
                        $tend = mktime( date( 'H', $endWdate ), date( 'i', $endWdate ), date( 's', $endWdate ), date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                      $datestring = date( $endDateFormat, $tend );
                      if( isset( $end['tz'] ))
                        $datestring .= ' '.$end['tz'];
                      $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                      $component2->setProperty( $propName, $datestring );
                    } // end if( $dtendExist || $dueExist || $durationExist )
                    $component2->setProperty( 'X-RECURRENCE', $xRecurrence );
                    $wd = getdate( $rstart );
                    if (!isset($result[$wd['year']][$wd['mon']][$wd['mday']][$component2->getProperty( 'UID' )] )) {
                        $result[$wd['year']][$wd['mon']][$wd['mday']][$component2->getProperty( 'UID' )] = $component2->copy(); // copy to output
                    }
                    
                  } // end if( $checkDate > $startYMD ) {    // date after dtstart
                  $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
                } // end while( $rstart <= $rend )
                $xRecurrence += 1;
              } // end elseif( $split )
              elseif( $rstart >= $startDate ) {     // date within period   //* flat=FALSE && split=FALSE *//
                $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                if( !isset( $exdatelist[$checkDate] )) { // exclude any recurrence START date, found in exdatelist
                  $xRecurrence += 1;
                  $datestring = date( $startDateFormat, $rstart );
                  if( isset( $start['tz'] ))
                    $datestring .= ' '.$start['tz'];
  //echo "X-CURRENT-DTSTART 2 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br />";$component2->setProperty( 'X-CNT', $tcnt ); // test ###
                  $component2->setProperty( 'X-CURRENT-DTSTART', $datestring );
                  if( $dtendExist || $dueExist || $durationExist ) {
                    $rstart += $rdurWsecs;
                    if( date( 'Ymd', $rstart ) < date( 'Ymd', $endWdate ))
                      $tend = mktime( 23, 59, 59, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ));
                    else
                      $tend = mktime( date( 'H', $endWdate ), date( 'i', $endWdate ), date( 's', $endWdate ), date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                    $datestring = date( $endDateFormat, $tend );
                    if( isset( $end['tz'] ))
                      $datestring .= ' '.$end['tz'];
                    $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                    $component2->setProperty( $propName, $datestring );
                  } // end if( $dtendExist || $dueExist || $durationExist )
                  $component2->setProperty( 'X-RECURRENCE', $xRecurrence );
                  $wd = getdate( $rstart );
                  if (!isset($result[$wd['year']][$wd['mon']][$wd['mday']][$component2->getProperty( 'UID' )] )) {
                     $result[$wd['year']][$wd['mon']][$wd['mday']][$component2->getProperty( 'UID' )] = $component2->copy(); // copy to output
                  }
                } // end if( !isset( $exdatelist[$checkDate] ))
              } // end elseif( $rstart >= $startDate )
            } // end foreach( $recurlist as $recurkey => $durvalue )
          } // end if( 0 < count( $recurlist ))
              /* deselect components with startdate/enddate not within period */
          if(( $endWdate < $startDate ) || ( $startWdate > $endDate ))
            continue;
        } // end if( TRUE === $any )
    } // end foreach ( $this->components as $cix => $component )
    if( 0 >= count( $result )) {
        return FALSE;
    }
    
    if( !$flat ) {
        foreach( $result as $y => $yeararr ) {
          foreach( $yeararr as $m => $montharr ) {
            foreach( $montharr as $d => $dayarr )
              $result[$y][$m][$d] = array_values( $dayarr ); // skip tricky UID-index, hoping they are in hour order.. .
            ksort( $result[$y][$m] );
          }
          ksort( $result[$y] );
        }
        ksort( $result );
    } // end elseif( !$flat )
    //print_R($result);
    return $result;
  }
/**
 * select components from calendar on based on Categories, Location, Resources and/or Summary
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-05-03
 * @param array $selectOptions, (string) key => (mixed) value, (key=propertyName)
 * @return array
 */
  function selectComponents2( $selectOptions ) {
    $output = array();
    $allowedProperties = array( 'ATTENDEE', 'CATEGORIES', 'LOCATION', 'ORGANIZER', 'RESOURCES', 'PRIORITY', 'STATUS', 'SUMMARY', 'UID' );
    foreach( $this->components as $cix => $component3 ) {
      if( !in_array( $component3->objName, array('vevent', 'vtodo', 'vjournal', 'vfreebusy' )))
        continue;
      $uid = $component3->getProperty( 'UID' );
      foreach( $selectOptions as $propName => $pvalue ) {
        $propName = strtoupper( $propName );
        if( !in_array( $propName, $allowedProperties ))
          continue;
        if( !is_array( $pvalue ))
          $pvalue = array( $pvalue );
        if(( 'UID' == $propName ) && in_array( $uid, $pvalue )) {
          $output[] = $component3->copy();
          continue;
        }
        elseif(( 'ATTENDEE' == $propName ) || ( 'CATEGORIES' == $propName ) || ( 'RESOURCES' == $propName )) {
          $propValues = array();
          $component3->_getProperties( $propName, $propValues );
          $propValues = array_keys( $propValues );
          foreach( $pvalue as $theValue ) {
            if( in_array( $theValue, $propValues ) && !isset( $output[$uid] )) {
              $output[$uid] = $component3->copy();
              break;
            }
          }
          continue;
        } // end   elseif(( 'ATTENDEE' == $propName ) || ( 'CATEGORIES' == $propName ) || ( 'RESOURCES' == $propName ))
        elseif( FALSE === ( $d = $component3->getProperty( $propName ))) // single ocurrence
          continue;
        if( is_array( $d )) {
          foreach( $d as $part ) {
            if( in_array( $part, $pvalue ) && !isset( $output[$uid] ))
              $output[$uid] = $component3->copy();
          }
        }
        elseif(( 'SUMMARY' == $propName ) && !isset( $output[$uid] )) {
          foreach( $pvalue as $pval ) {
            if( FALSE !== stripos( $d, $pval )) {
              $output[$uid] = $component3->copy();
              break;
            }
          }
        }
        elseif( in_array( $d, $pvalue ) && !isset( $output[$uid] ))
          $output[$uid] = $component3->copy();
      } // end foreach( $selectOptions as $propName => $pvalue ) {
    } // end foreach( $this->components as $cix => $component3 ) {
    if( !empty( $output )) {
      ksort( $output );
      $output = array_values( $output );
    }
    return $output;
  }
/**
 * add calendar component to container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param object $component calendar component
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return void
 */
  function setComponent( $component, $arg1=FALSE, $arg2=FALSE  ) {
    $component->setConfig( $this->getConfig(), FALSE, TRUE );
    if( !in_array( $component->objName, array( 'valarm', 'vtimezone' ))) {
            /* make sure dtstamp and uid is set */
      $dummy1 = $component->getProperty( 'dtstamp' );
      $dummy2 = $component->getProperty( 'uid' );
    }
    if( !$arg1 ) { // plain insert, last in chain
      $this->components[] = $component->copy();
      return TRUE;
    }
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) { // index insert/replace
      $argType = 'INDEX';
      $index   = (int) $arg1 - 1;
    }
    elseif( in_array( strtolower( $arg1 ), array( 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone' ))) {
      $argType = strtolower( $arg1 );
      $index = ( ctype_digit( (string) $arg2 )) ? ((int) $arg2) - 1 : 0;
    }
    // else if arg1 is set, arg1 must be an UID
    $cix1sC = 0;
    foreach ( $this->components as $cix => $component2) {
      if( empty( $component2 )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix )) { // index insert/replace
        $this->components[$cix] = $component->copy();
        return TRUE;
      }
      elseif( $argType == $component2->objName ) { // component Type index insert/replace
        if( $index == $cix1sC ) {
          $this->components[$cix] = $component->copy();
          return TRUE;
        }
        $cix1sC++;
      }
      elseif( !$argType && ( $arg1 == $component2->getProperty( 'uid' ))) { // UID insert/replace
        $this->components[$cix] = $component->copy();
        return TRUE;
      }
    }
            /* arg1=index and not found.. . insert at index .. .*/
    if( 'INDEX' == $argType ) {
      $this->components[$index] = $component->copy();
      ksort( $this->components, SORT_NUMERIC );
    }
    else    /* not found.. . insert last in chain anyway .. .*/
      $this->components[] = $component->copy();
    return TRUE;
  }
/**
 * sort iCal compoments
 *
 * ascending sort on properties (if exist) x-current-dtstart, dtstart,
 * x-current-dtend, dtend, x-current-due, due, duration, created, dtstamp, uid
 * if no arguments, otherwise sorting on argument CATEGORIES, LOCATION, SUMMARY or RESOURCES
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.4 - 2011-06-02
 * @param string $sortArg, optional
 * @return void
 *
 */
  function sort( $sortArg=FALSE ) {
    if( is_array( $this->components )) {
      if( $sortArg ) {
        $sortArg = strtoupper( $sortArg );
        if( !in_array( $sortArg, array( 'ATTENDEE', 'CATEGORIES', 'DTSTAMP', 'LOCATION', 'ORGANIZER', 'RESOURCES', 'PRIORITY', 'STATUS', 'SUMMARY' )))
          $sortArg = FALSE;
      }
            /* set sort parameters for each component */
      foreach( $this->components as $cix => & $c ) {
        $c->srtk = array( '0', '0', '0', '0' );
        if( 'vtimezone' == $c->objName ) {
          if( FALSE === ( $c->srtk[0] = $c->getProperty( 'tzid' )))
            $c->srtk[0] = 0;
          continue;
        }
        elseif( $sortArg ) {
          if(( 'ATTENDEE' == $sortArg ) || ( 'CATEGORIES' == $sortArg ) || ( 'RESOURCES'  == $sortArg )) {
            $propValues = array();
            $c->_getProperties( $sortArg, $propValues );
            $c->srtk[0] = reset( array_keys( $propValues ));
          }
          elseif( FALSE !== ( $d = $c->getProperty( $sortArg )))
            $c->srtk[0] = $d;
          continue;
        }
        if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTSTART' )))
          $c->srtk[0] = iCal_UtilityFunctions::_date_time_string( $d[1] );
        elseif( FALSE === ( $c->srtk[0] = $c->getProperty( 'dtstart' )))
          $c->srtk[1] = 0;                                                  // sortkey 0 : dtstart
        if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTEND' )))
          $c->srtk[1] = iCal_UtilityFunctions::_date_time_string( $d[1] );   // sortkey 1 : dtend/due(/dtstart+duration)
        elseif( FALSE === ( $c->srtk[1] = $c->getProperty( 'dtend' ))) {
          if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DUE' )))
            $c->srtk[1] = iCal_UtilityFunctions::_date_time_string( $d[1] );
          elseif( FALSE === ( $c->srtk[1] = $c->getProperty( 'due' )))
            if( FALSE === ( $c->srtk[1] = $c->getProperty( 'duration', FALSE, FALSE, TRUE )))
              $c->srtk[1] = 0;
        }
        if( FALSE === ( $c->srtk[2] = $c->getProperty( 'created' )))      // sortkey 2 : created/dtstamp
          if( FALSE === ( $c->srtk[2] = $c->getProperty( 'dtstamp' )))
            $c->srtk[2] = 0;
        if( FALSE === ( $c->srtk[3] = $c->getProperty( 'uid' )))          // sortkey 3 : uid
          $c->srtk[3] = 0;
      } // end foreach( $this->components as & $c
            /* sort */
      usort( $this->components, array( $this, '_cmpfcn' ));
    }
  }
  function _cmpfcn( $a, $b ) {
    if(        empty( $a ))                       return -1;
    if(        empty( $b ))                       return  1;
    if( 'vtimezone' == $a->objName ) {
      if( 'vtimezone' != $b->objName )            return -1;
      elseif( $a->srtk[0] <= $b->srtk[0] )        return -1;
      else                                        return  1;
    }
    elseif( 'vtimezone' == $b->objName )          return  1;
    $sortkeys = array( 'year', 'month', 'day', 'hour', 'min', 'sec' );
    for( $k = 0; $k < 4 ; $k++ ) {
      if(        empty( $a->srtk[$k] ))           return -1;
      elseif(    empty( $b->srtk[$k] ))           return  1;
      if( is_array( $a->srtk[$k] )) {
        if( is_array( $b->srtk[$k] )) {
          foreach( $sortkeys as $key ) {
            if    (  empty( $a->srtk[$k][$key] )) return -1;
            elseif(  empty( $b->srtk[$k][$key] )) return  1;
            if    (         $a->srtk[$k][$key] == $b->srtk[$k][$key])
                                                  continue;
            if    ((  (int) $a->srtk[$k][$key] ) < ((int) $b->srtk[$k][$key] ))
                                                  return -1;
            elseif((  (int) $a->srtk[$k][$key] ) > ((int) $b->srtk[$k][$key] ))
                                                  return  1;
          }
        }
        else                                      return -1;
      }
      elseif( is_array( $b->srtk[$k] ))           return  1;
      elseif( $a->srtk[$k] < $b->srtk[$k] )       return -1;
      elseif( $a->srtk[$k] > $b->srtk[$k] )       return  1;
    }
    return 0;
  }
/**
 * parse iCal text/file into iCal_Vcalendar, components, properties and parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-21
 * @param mixed $unparsedtext, optional, strict rfc2445 formatted, single property string or array of property strings
 * @return bool FALSE if error occurs during parsing
 *
 */
  function parse( $unparsedtext=FALSE ) {
    $nl = $this->getConfig( 'nl' );
    if(( FALSE === $unparsedtext ) || empty( $unparsedtext )) {
            /* directory+filename is set previously via setConfig directory+filename or url */
      if( FALSE === ( $filename = $this->getConfig( 'url' )))
        $filename = $this->getConfig( 'dirfile' );
            /* READ FILE */
      if( FALSE === ( $rows = file_get_contents( $filename )))
        return FALSE;                 /* err 1 */
    }
    elseif( is_array( $unparsedtext ))
      $rows =  implode( '\n'.$nl, $unparsedtext );
    else
      $rows = & $unparsedtext;
            /* identify BEGIN:VCALENDAR, MUST be first row */
    if( 'BEGIN:VCALENDAR' != strtoupper( substr( $rows, 0, 15 )))
      return FALSE;                   /* err 8 */
            /* fix line folding */
    $eolchars = array( "\r\n", "\n\r", "\n", "\r" ); // check all line endings
    $EOLmark = FALSE;
    foreach( $eolchars as $eolchar ) {
      if( !$EOLmark  && ( FALSE !== strpos( $rows, $eolchar ))) {
        $rows = str_replace( $eolchar." ",  '',  $rows );
        $rows = str_replace( $eolchar."\t", '',  $rows );
        if( $eolchar != $nl )
          $rows = str_replace( $eolchar,    $nl, $rows );
        $EOLmark = TRUE;
      }
    }
    $tmp = explode( $nl, $rows );
    $rows = array();
    foreach( $tmp as $tmpr )
      if( !empty( $tmpr ))
        $rows[] = $tmpr;
            /* skip trailing empty lines */
    $lix = count( $rows ) - 1;
    while( empty( $rows[$lix] ) && ( 0 < $lix ))
      $lix -= 1;
            /* identify ending END:VCALENDAR row, MUST  be last row */
    if( 'END:VCALENDAR'   != strtoupper( substr( $rows[$lix], 0, 13 )))
      return FALSE;                   /* err 9 */
    if( 3 > count( $rows ))
      return FALSE;                   /* err 10 */
    $comp    = & $this;
    $calsync = 0;
            /* identify components and update unparsed data within component */
    $config = $this->getConfig();
    foreach( $rows as $line ) {
      if( '' == trim( $line ))
        continue;
      if(     'BEGIN:VCALENDAR' == strtoupper( substr( $line, 0, 15 ))) {
        $calsync++;
        continue;
      }
      elseif( 'END:VCALENDAR'   == strtoupper( substr( $line, 0, 13 ))) {
        $calsync--;
        break;
      }
      elseif( 1 != $calsync )
        return FALSE;                 /* err 20 */
      elseif( in_array( strtoupper( substr( $line, 0, 6 )), array( 'END:VE', 'END:VF', 'END:VJ', 'END:VT' ))) {
        $this->components[] = $comp->copy();
        continue;
      }

      if(     'BEGIN:VEVENT'    == strtoupper( substr( $line, 0, 12 )))
        $comp = new vevent( $config );
      elseif( 'BEGIN:VFREEBUSY' == strtoupper( substr( $line, 0, 15 )))
        $comp = new vfreebusy( $config );
      elseif( 'BEGIN:VJOURNAL'  == strtoupper( substr( $line, 0, 14 )))
        $comp = new vjournal( $config );
      elseif( 'BEGIN:VTODO'     == strtoupper( substr( $line, 0, 11 )))
        $comp = new vtodo( $config );
      elseif( 'BEGIN:VTIMEZONE' == strtoupper( substr( $line, 0, 15 )))
        $comp = new vtimezone( $config );
      else  /* update component with unparsed data */
        $comp->unparsed[] = $line;
    } // end - foreach( rows.. .
    unset( $config );
            /* parse data for calendar (this) object */
    if( isset( $this->unparsed ) && is_array( $this->unparsed ) && ( 0 < count( $this->unparsed ))) {
            /* concatenate property values spread over several lines */
      $lastix    = -1;
      $propnames = array( 'calscale','method','prodid','version','x-' );
      $proprows  = array();
      foreach( $this->unparsed as $line ) {
        if( '' == trim( $line ))
          continue;
        $newProp = FALSE;
        foreach ( $propnames as $propname ) {
          if( $propname == strtolower( substr( $line, 0, strlen( $propname )))) {
            $newProp = TRUE;
            break;
          }
        }
        if( $newProp ) {
          $newProp = FALSE;
          $lastix++;
          $proprows[$lastix]  = $line;
        }
        else
          $proprows[$lastix] .= '!"#¤%&/()=?'.$line;
      }
      foreach( $proprows as $line ) {
        $line = str_replace( '!"#¤%&/()=? ', '', $line );
        $line = str_replace( '!"#¤%&/()=?', '', $line );
        if( '\n' == substr( $line, -2 ))
          $line = substr( $line, 0, strlen( $line ) - 2 );
            /* get property name */
        $cix = $propname = null;
        for( $cix=0, $clen = strlen( $line ); $cix < $clen; $cix++ ) {
          if( in_array( $line[$cix], array( ':', ';' )))
            break;
          else
            $propname .= $line[$cix];
        }
            /* ignore version/prodid properties */
        if( in_array( strtoupper( $propname ), array( 'VERSION', 'PRODID' )))
          continue;
        $line = substr( $line, $cix);
            /* separate attributes from value */
        $attr   = array();
        $attrix = -1;
        $strlen = strlen( $line );
        for( $cix=0; $cix < $strlen; $cix++ ) {
          if((       ':'   == $line[$cix] )             &&
                   ( '://' != substr( $line, $cix, 3 )) &&
             ( !in_array( strtolower( substr( $line, $cix - 3, 4 )), array( 'fax:', 'cid:', 'sms:', 'tel:', 'urn:' ))) &&
             ( !in_array( strtolower( substr( $line, $cix - 4, 5 )), array( 'crid:', 'news:', 'pres:' ))) &&
             ( 'mailto:'   != strtolower( substr( $line, $cix - 6, 7 )))) {
            $attrEnd = TRUE;
            if(( $cix < ( $strlen - 4 )) &&
                 ctype_digit( substr( $line, $cix+1, 4 ))) { // an URI with a (4pos) portnr??
              for( $c2ix = $cix; 3 < $c2ix; $c2ix-- ) {
                if( '://' == substr( $line, $c2ix - 2, 3 )) {
                  $attrEnd = FALSE;
                  break; // an URI with a portnr!!
                }
              }
            }
            if( $attrEnd) {
              $line = substr( $line, $cix + 1 );
              break;
            }
          }
          if( ';' == $line[$cix] )
            $attr[++$attrix] = null;
          else
            $attr[$attrix] .= $line[$cix];
        }

            /* make attributes in array format */
        $propattr = array();
        foreach( $attr as $attribute ) {
          $attrsplit = explode( '=', $attribute, 2 );
          if( 1 < count( $attrsplit ))
            $propattr[$attrsplit[0]] = $attrsplit[1];
          else
            $propattr[] = $attribute;
        }
            /* update Property */
        if( FALSE !== strpos( $line, ',' )) {
          $content  = explode( ',', $line );
          $clen     = count( $content );
          for( $cix = 0; $cix < $clen; $cix++ ) {
            if( "\\" == substr( $content[$cix], -1 )) {
              $content[$cix] .= ','.$content[$cix + 1];
              unset( $content[$cix + 1] );
              $cix++;
            }
          }
          if( 1 < count( $content )) {
            foreach( $content as $cix => $contentPart )
              $content[$cix] = calendarComponent::_strunrep( $contentPart );
            $this->setProperty( $propname, $content, $propattr );
            continue;
          }
          else
            $line = reset( $content );
          $line = calendarComponent::_strunrep( $line );
        }
        $this->setProperty( $propname, trim( $line ), $propattr );
      } // end - foreach( $this->unparsed.. .
    } // end - if( is_array( $this->unparsed.. .
    unset( $unparsedtext, $rows, $this->unparsed, $proprows );
            /* parse Components */
    if( is_array( $this->components ) && ( 0 < count( $this->components ))) {
      $ckeys = array_keys( $this->components );
      foreach( $ckeys as $ckey ) {
        if( !empty( $this->components[$ckey] ) && !empty( $this->components[$ckey]->unparsed )) {
          $this->components[$ckey]->parse();
        }
      }
    }
    else
      return FALSE;                   /* err 91 or something.. . */
    return TRUE;
  }
/*********************************************************************************/
/**
 * creates formatted output for calendar object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.1 - 2011-03-12
 * @return string
 */
  function createCalendar() {
    $calendarInit1 = $calendarInit2 = $calendarxCaldecl = $calendarStart = $calendar = null;
    switch( $this->format ) {
      case 'xcal':
        $calendarInit1 = '<?xml version="1.0" encoding="UTF-8"?>'.$this->nl.
                         '<!DOCTYPE iCalendar PUBLIC "-//IETF//DTD XCAL/iCalendar XML//EN"'.$this->nl.
                         '"http://www.ietf.org/internet-drafts/draft-ietf-calsch-many-xcal-01.txt"';
        $calendarInit2 = '>'.$this->nl;
        $calendarStart = '<vcalendar';
        break;
      default:
        $calendarStart = 'BEGIN:VCALENDAR'.$this->nl;
        break;
    }
    $calendarStart .= $this->createVersion();
    $calendarStart .= $this->createProdid();
    $calendarStart .= $this->createCalscale();
    $calendarStart .= $this->createMethod();
    switch( $this->format ) {
      case 'xcal':
        $nlstrlen = strlen( $this->nl );
        if( $this->nl == substr( $calendarStart, ( 0 - $nlstrlen )))
          $calendarStart = substr( $calendarStart, 0, ( strlen( $calendarStart ) - $nlstrlen ));
        $calendarStart .= '>'.$this->nl;
        break;
      default:
        break;
    }
    $calendar .= $this->createXprop();
    foreach( $this->components as $component ) {
      if( empty( $component )) continue;
      $component->setConfig( $this->getConfig(), FALSE, TRUE );
      $calendar .= $component->createComponent( $this->xcaldecl );
    }
    if(( 0 < count( $this->xcaldecl )) && ( 'xcal' == $this->format )) { // xCal only
      $calendarInit1 .= $this->nl.'['.$this->nl;
      $old_xcaldecl = array();
      foreach( $this->xcaldecl as $declix => $declPart ) {
        if(( 0 < count( $old_xcaldecl)) &&
           ( in_array( $declPart['uri'],      $old_xcaldecl['uri'] )) &&
           ( in_array( $declPart['external'], $old_xcaldecl['external'] )))
          continue; // no duplicate uri and ext. references
        $calendarxCaldecl .= '<!';
        foreach( $declPart as $declKey => $declValue ) {
          switch( $declKey ) {                    // index
            case 'xmldecl':                       // no 1
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'uri':                           // no 2
              $calendarxCaldecl .= $declValue.' ';
              $old_xcaldecl['uri'][] = $declValue;
              break;
            case 'ref':                           // no 3
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'external':                      // no 4
              $calendarxCaldecl .= '"'.$declValue.'" ';
              $old_xcaldecl['external'][] = $declValue;
              break;
            case 'type':                          // no 5
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'type2':                         // no 6
              $calendarxCaldecl .= $declValue;
              break;
          }
        }
        $calendarxCaldecl .= '>'.$this->nl;
      }
      $calendarInit2 = ']'.$calendarInit2;
    }
    switch( $this->format ) {
      case 'xcal':
        $calendar .= '</vcalendar>'.$this->nl;
        break;
      default:
        $calendar .= 'END:VCALENDAR'.$this->nl;
        break;
    }
    return $calendarInit1.$calendarxCaldecl.$calendarInit2.$calendarStart.$calendar;
  }
/**
 * a HTTP redirect header is sent with created, updated and/or parsed calendar
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.12 - 2011-07-13
 * @param bool $utf8Encode
 * @param bool $gzip
 * @return redirect
 */
  function returnCalendar( $utf8Encode=FALSE, $gzip=FALSE ) {
    $filename = $this->getConfig( 'filename' );
    $output   = $this->createCalendar();
    if( $utf8Encode )
      $output = utf8_encode( $output );
    if( $gzip ) {
      $output = gzencode( $output, 9 );
      header( 'Content-Encoding: gzip');
      header( 'Vary: *');
    }
    $filesize = strlen( $output );
    if( 'xcal' == $this->format )
      header( 'Content-Type: application/calendar+xml; charset=utf-8' );
    else
      header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Length: '.$filesize );
    header( 'Content-Disposition: attachment; filename="'.$filename.'"' );
    header( 'Cache-Control: max-age=10' );
    echo $output;
    die();
  }
/**
 * save content in a file
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.12 - 2007-12-30
 * @param string $directory optional
 * @param string $filename optional
 * @param string $delimiter optional
 * @return bool
 */
  function saveCalendar( $directory=FALSE, $filename=FALSE, $delimiter=FALSE ) {
    if( $directory )
      $this->setConfig( 'directory', $directory );
    if( $filename )
      $this->setConfig( 'filename',  $filename );
    if( $delimiter && ($delimiter != DIRECTORY_SEPARATOR ))
      $this->setConfig( 'delimiter', $delimiter );
    if( FALSE === ( $dirfile = $this->getConfig( 'url' )))
      $dirfile = $this->getConfig( 'dirfile' );
    $iCalFile = @fopen( $dirfile, 'w' );
    if( $iCalFile ) {
      if( FALSE === fwrite( $iCalFile, $this->createCalendar() ))
        return FALSE;
      fclose( $iCalFile );
      return TRUE;
    }
    else
      return FALSE;
  }
/**
 * if recent version of calendar file exists (default one hour), an HTTP redirect header is sent
 * else FALSE is returned
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.12 - 2007-10-28
 * @param string $directory optional alt. int timeout
 * @param string $filename optional
 * @param string $delimiter optional
 * @param int timeout optional, default 3600 sec
 * @return redirect/FALSE
 */
  function useCachedCalendar( $directory=FALSE, $filename=FALSE, $delimiter=FALSE, $timeout=3600) {
    if ( $directory && ctype_digit( (string) $directory ) && !$filename ) {
      $timeout   = (int) $directory;
      $directory = FALSE;
    }
    if( $directory )
      $this->setConfig( 'directory', $directory );
    if( $filename )
      $this->setConfig( 'filename',  $filename );
    if( $delimiter && ( $delimiter != DIRECTORY_SEPARATOR ))
      $this->setConfig( 'delimiter', $delimiter );
    $filesize    = $this->getConfig( 'filesize' );
    if( 0 >= $filesize )
      return FALSE;
    $dirfile     = $this->getConfig( 'dirfile' );
    if( time() - filemtime( $dirfile ) < $timeout) {
      clearstatcache();
      $dirfile   = $this->getConfig( 'dirfile' );
      $filename  = $this->getConfig( 'filename' );
//    if( headers_sent( $filename, $linenum ))
//      die( "Headers already sent in $filename on line $linenum\n" );
      if( 'xcal' == $this->format )
        header( 'Content-Type: application/calendar+xml; charset=utf-8' );
      else
        header( 'Content-Type: text/calendar; charset=utf-8' );
      header( 'Content-Length: '.$filesize );
      header( 'Content-Disposition: attachment; filename="'.$filename.'"' );
      header( 'Cache-Control: max-age=10' );
      $fp = @fopen( $dirfile, 'r' );
      if( $fp ) {
        fpassthru( $fp );
        fclose( $fp );
      }
      die();
    }
    else
      return FALSE;
  }
}
