#!/bin/bash
#### Updating to monitor branch of cDistro
WDIR=$1
CLOUDYDIR=/var/local/cDistro

cd /tmp
TEMP=`mktemp -d cloudytmp.XXX` && {
        cd $TEMP
        curl -k https://raw.githubusercontent.com/Clommunity/cDistro/monitor/web/lang/en.menus.php > en.menus.php
        curl -k https://raw.githubusercontent.com/Clommunity/cDistro/monitor/web/plug/controllers/cloudyupdate.php > cloudyupdate.php
        curl -k https://raw.githubusercontent.com/Clommunity/cDistro/monitor/web/plug/menus/cloudy.menu.php > cloudy.menu.php
        curl -k https://raw.githubusercontent.com/Clommunity/cDistro/monitor/web/plug/controllers/monitor-aas.php > monitor-aas.php

        mv ${CLOUDYDIR}/lang/en.menus.php ${CLOUDYDIR}/lang/en.menus.php.bak
        mv ${CLOUDYDIR}/plug/menus/cloudy.menu.php ${CLOUDYDIR}/plug/menus/cloudy.menu.php.bak
        mv ${CLOUDYDIR}/plug/controllers/cloudyupdate.php ${CLOUDYDIR}/plug/controllers/cloudyupdate.php.bak
        #mv ${CLOUDYDIR}/plug/controllers/monitor-aas.php ${CLOUDYDIR}/plug/controllers/monitor-aas.php.bak #no monitor-aas in master branch

        mv en.menus.php ${CLOUDYDIR}/lang/
        mv cloudy.menu.php ${CLOUDYDIR}/plug/menus/
        mv cloudyupdate.php ${CLOUDYDIR}/plug/controllers/
        mv monitor-aas.php ${CLOUDYDIR}/plug/controllers/

        cd ..
        rm -rf $TEMP
}

echo "Done"
