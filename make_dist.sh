#!/bin/sh

# short little script to make a releaseable pear ball.... - for use with flexyframework projects.

TARGET=/tmp/pear 
rm -rf ${TARGET}
rm -rf /tmp/crap

PEAR="pear -C ${TARGET}/pear.conf  -c ${TARGET}/user.pear.conf -D php_dir=${TARGET} "
PEAR="${PEAR} -D bin_dir=/tmp/crap  -D doc_dir=/tmp/crap -D test_dir=/tmp/crap"
PEAR="${PEAR} -D data_dir=/tmp/crap"
 
 
#be carefull, order is important (although we could use --fixdeps or whatever now)
 
#needed for pear-1.4.*
${PEAR} channel-update pear.php.net

${PEAR} install \
	Archive_Tar \
	Console_Getopt \
	XML_RPC 

${PEAR} install --force  PEAR

#now some of the basic stuff..
${PEAR} install \
	DB \
	Date \
	Validate-beta \
	DB_DataObject \
	Auth \
	Auth_SASL \
	Net_Socket \
	Net_SMTP \
	Mail-beta \
	Mail_Mime  \
	Mail_mimeDecode  \
	XML_Parser \
	HTML_Javascript \
	File_Gettext-beta \
	Translation2 \
	HTML_Template_Flexy \
	File_Passwd \
	Text_Password \
	Log \
	XML_Util \
	Config  \
	Benchmark \
	Pager_Sliding  \
	I18N-beta \
	Image_Transform-alpha \
	I18Nv2-beta \
	Text_CAPTCHA-alpha

${PEAR} install  --force Image_Text-beta

${PEAR} install --force MDB2 MDB2#mysql  MDB2#mysqli  MDB2#pgsql MDB2#sqlite

${PEAR} install -o \
	Spreadsheet_Excel_Writer-beta \
	XML_Serializer-beta \
	File_Find \
	File \
	Text_Password


	
	
#${PEAR} install PHPUnit

 
${PEAR} install \
	Net_URL \
	Net_Socket \
	HTTP_Request \
	Image_Graph-alpha \
	Image_Canvas-alpha \
	Image_Color \
	Numbers_Words-beta \
	Numbers_Roman \
	Services_JSON


${PEAR} install -o Cache
   
 
#// Kludge - unreleased dataobject!

#curl http://svn.php.net/repository/pear/packages/DB_DataObject/trunk/DataObject.php > /tmp/pear/DB/DataObject.php



#zip it up for a pearball..
cd /tmp
rm -rf /tmp/pear/.channels
rm -rf /tmp/pear/.registry
rm -f /tmp/pear/pearcmd.php
rm -f /tmp/pear/peclcmd.php
rm -f /tmp/pear/user.pear.conf
rm -f /tmp/pear/.filemap
rm -f /tmp/pear/.depdb
rm -f /tmp/pear/.lock
rm -f /tmp/pear/.depdblock
rm -rf /tmp/pear/cache 
rm -rf /tmp/pear/tests
rm -rf /tmp/pear/download

echo 'rsync -a /tmp/pear /your/location'
 
  