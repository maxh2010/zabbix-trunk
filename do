#!/bin/sh

#
# Description:
#	ZABBIX compilateion script
# Author:
#	Eugene Grigorjev
#

win2nix="no"
premake="no"
copy="no"
configure="no"
domake="no"
doinstall="no"
config_param=""
dotest="no"
cleanwarnings="no"
docat="yes"
help="no"
noparam=0
#def="--enable-agent --enable-server --with-mysql --prefix=`echo $HOME`/local/zabbix --with-ldap --with-net-snmp"
def="--enable-agent --enable-server --with-mysql --prefix=`echo $HOME`/local/zabbix --with-ldap --with-net-snmp --with-libcurl"

for cmd
do
  case "$cmd" in
    win2nix )	win2nix="yes";		noparam=1;;
    copy )	copy="yes";		noparam=1;;
    cpy )	copy="yes";		noparam=1;;
    pre )	premake="yes";		noparam=1;;
    premake )	premake="yes";		noparam=1;;
    conf )	configure="yes";	noparam=1;;
    config )	configure="yes";	noparam=1;;
    configure )	configure="yes";	noparam=1;;
    mk )	domake="yes";		noparam=1;;
    make )	domake="yes";		noparam=1;;
    inst )	doinstall="yes";	noparam=1;;
    install )	doinstall="yes";	noparam=1;;
    test )	dotest="yes";		noparam=1;;
    nocat )	docat="no";		noparam=1;;
    cat )	docat="yes";		noparam=1;;
    def )		config_param="$config_param $def";;
    --enable-* )	config_param="$config_param $cmd";; 
    --with-* )		config_param="$config_param $cmd";;
    --prefix=* )	config_param="$config_param $cmd";;
    help )	help="yes";;
    h )		help="yes";;
    * ) 
        echo "$0: ERROR: unknown parameter \"$cmd\""; 
	help="yes";
  esac
done
if [ "$help" = "yes" ] || [ $noparam = 0 ]
then
        echo
        echo "Usage:"
        echo "  $0 [commands] [options]"
	echo
	echo " Commands:"
	echo "   [win2nix]                - convers win EOL [\\\\r\\\\n] to nix EOL [\\\\n]"
	echo "   [copy|cpy]               - copy automake files"
	echo "   [premake|pre]            - make configuration file"
	echo "   [configure|config|conf]  - configure make files"
	echo "   [make]                   - make applications"
	echo "   [test]                   - test applications"
	echo "   [inst|install]           - install applications"
	echo
	echo " Options:"
	echo "   [def]            - default configuration \"$def\""
	echo "   [cat]            - cat WARRNING file at the end (defaut - ON)"
	echo "   [nocat]          - do not cat WARRNING file"
	echo "   [--enable-*]     - option for configuration"
	echo "   [--with-*]       - option for configuration"
        echo
        echo "Examples:"
        echo "  $0 conf def make test        - compyle, test, and sow report"
        echo "  $0 cat                       - cat last REPORT"
        echo "  $0                           - show this help"
        exit 1;
fi

if [ "$copy" = "yes" ] || [ $premake = "yes" ] || 
  [ $configure = "yes" ] || [ $domake = "yes" ] || 
  [ $dotest = "yes" ] || 
  [ "$win2nix" = "yes" ] || [ $doinstall = "yes" ]
then
  cleanwarnings="yes"
fi

if [ "$cleanwarnings" = "yes" ] 
then
  rm -f WARNINGS
fi

if [ "$win2nix" = "yes" ]
then
  echo "Replacing..."
  echo "Replacing..." >> WARNINGS
  find ./ -name "configure.in" -exec vi "+%s/\\r$//" "+wq" "-es" {} ';' -print 2>> WARNINGS
  find ./ -name "*.[hc]" -exec vi "+%s/\\r$//" "+wq" "-es" {} ';' -print 2>> WARNINGS
fi

premake_is_ok=1
if [ "$premake" = "yes" ] 
then
  premake_is_ok=0
  echo "Pre-making..."
  echo "Pre-making..." >> WARNINGS
#  aclocal 2>> WARNINGS
  echo -n " 1"
  aclocal -I m4 2>> WARNINGS
  if [ "x$?" = "x0" ] 
  then
    echo -n "2"
    autoconf 2>> WARNINGS
    if [ "x$?" = "x0" ] 
    then
      echo -n "3"
      autoheader 2>> WARNINGS
      if [ "x$?" = "x0" ] 
      then
        echo -n "4"
        automake -a 2>> WARNINGS
        if [ "x$?" = "x0" ] 
        then
          premake_is_ok=1
        fi
      fi
    fi
  fi
  echo
fi

if [ "$copy" = "yes" ] 
then
  echo "Copyng..."
  echo "Copyng..." >> WARNINGS
  rm -f config.guess config.sub depcomp install-sh missing 2>> WARNINGS

  cp /usr/share/automake-1.9/config.guess config.guess 2>> WARNINGS
  cp /usr/share/automake-1.9/config.sub   config.sub 2>> WARNINGS
  cp /usr/share/automake-1.9/depcomp      depcomp 2>> WARNINGS
  cp /usr/share/automake-1.9/install-sh   install-sh 2>> WARNINGS
  cp /usr/share/automake-1.9/missing      missing 2>> WARNINGS
fi

configure_is_ok=1
if [ "x$premake_is_ok$configure" = "x1yes" ] 
then
  configure_is_ok=0
  echo "Configuring..."
  echo "Configuring..." >> WARNINGS
  export CFLAGS="-Wall -DDEBUG"
  #export CFLAGS="-Wall -pedantic"
  ./configure $config_param 2>> WARNINGS 
  if [ "x$?" = "x0" ]
  then
    ./create/schema/gen.pl c 2>> WARNINGS > ./include/dbsync.h
    if [ "x$?" = "x0" ]
    then
      configure_is_ok=1
    fi
  fi
fi

make_is_ok=1
if [ "x$configure_is_ok$domake" = "x1yes" ] 
then
  make_is_ok=0
  echo "Cleaning..."
  echo "Cleaning..." >> WARNINGS
  make clean 2>> WARNINGS 
  echo "Making..."
  echo "Making..." >> WARNINGS
  make 2>>WARNINGS 
  if [ "x$?" = "x0" ] 
  then
    make_is_ok=1
  fi
fi

if [ "x$make_is_ok$dotest" = "x1yes" ] 
then
  echo "Testing..."
  echo "Testing..." >> WARNINGS
  ./src/zabbix_agent/zabbix_agent -h >> WARNINGS
  echo "------------------------" >> WARNINGS
  ./src/zabbix_agent/zabbix_agentd -h >> WARNINGS
  echo "------------------------" >> WARNINGS
  ./src/zabbix_get/zabbix_get -h >> WARNINGS
  echo "------------------------" >> WARNINGS
  ./src/zabbix_sender/zabbix_sender -h >> WARNINGS
  echo "------------------------" >> WARNINGS
  ./src/zabbix_server/zabbix_server -h >> WARNINGS
  echo "------------------------" >> WARNINGS 
  echo "   Agent TEST RESULTS   " >> WARNINGS 
  echo "------------------------" >> WARNINGS 
  ./src/zabbix_agent/zabbix_agentd -p >> WARNINGS
fi

if [ "x$make_is_ok$doinstall" = "x1yes" ] 
then
  echo "Instalation..."
  echo "Instalation..." >> WARNINGS
  make install 2>> WARNINGS 
fi

if [ "$docat" = "yes" ] 
then
  echo
  echo WARNINGS
  echo "-----------------------------------"
  cat WARNINGS
  echo "-----------------------------------"
fi

