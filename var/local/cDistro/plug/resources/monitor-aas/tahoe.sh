#!/bin/bash
INTRODUCER_NAME=""
INTRODUCER_WEBPORT=""
INTRODUCER_PUBLIC=""
INTRODUCER_GRID_FILE=""
GRID_NAME=""

PACKAGE_FULLNAME="Tahoe-LAFS"
PACKAGE_NAME=tahoe-lafs
TAHOE_COMMAND="/usr/bin/tahoe"

DAEMON_USERNAME=tahoe
DAEMON_GROUP=tahoe
DAEMON_HOMEDIR="/var/lib/tahoe-lafs"
DAEMON_SHELL="/usr/sbin/nologin"

TAHOE_INITD_FILE=tahoe-lafs.init.d
TAHOE_ETC_INITD_FILE=/etc/init.d/tahoe-lafs
TAHOE_ETC_INITD_FILENAME=tahoe-lafs
TAHOE_DEFAULT_FILE=tahoe-lafs.etc.default
TAHOE_ETC_DEFAULT_FILE=/etc/default/tahoe-lafs

INTRODUCER_DIRNAME="introducer"
INTRODUCER_PUBLIC_FILE="introducer.public"
INTRODUCER_GRIDNAME_FILE="grid.name"
INTRODUCER_FURLFILE="introducer.furl"
WEBPORT_FILENAME="web.port"
NODE_DIRNAME="node"
TAHOE_PID_FILE="twistd.pid"
INTRODUCER_ID="my_nodeid"
INTRODUCER_PORTFILE="introducer.port"
TAHOE_CONFIG_FILE=tahoe.cfg

AVAHI_SERVICE_COMMAND=/usr/sbin/avahi-service
AVAHI_SERVICE_TAHOE=tahoe-lafs

HOST_IP=127.0.0.1
## Configuration of tahoe-lafs in Cloudy
## Just call configure INTRODUCER_NAME DAEMON_HOMEDIR INTRODUCER_DIRNAME TAHOE_CONFIG_FILE INTRODUCER_WEBPORT WEBPORT_FILENAME INTRODUCER_PUBLIC INTRODUCER_PUBLIC_FILE GRID_NAME INTRODUCER_GRID_FILE 

random_num() {
	echo $(shuf -i 100-999 -n 1)
}

configure_introducer() {
	### DEFAULTS like in cloudy!
	RANDOM_NUM=$(random_num)
	INTRODUCER_NAME=${1:-"MyIntroducer"}
	INTRODUCER_WEBPORT=${2:-"8228"}
	INTRODUCER_PUBLIC=${3:-"true"}
	INTRODUCER_GRID_FILE=${4:-"grid.name"}
	GRID_NAME=${5:-"Example-Grid-$RANDOM_NUM"}

	sed -i "s/^nickname.*$/nickname = "$INTRODUCER_NAME"/" $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$TAHOE_CONFIG_FILE
	echo $INTRODUCER_WEBPORT >> $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$WEBPORT_FILENAME

	if [[ "$INTRODUCER_PUBLIC" == "true" ]]; then
                touch $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_PUBLIC_FILE
                sed -i "s/^web\.port.*$/web\.port = tcp:"$INTRODUCER_WEBPORT":interface=0.0.0.0/" $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$TAHOE_CONFIG_FILE
        else
                sed -i "s/^web\.port.*$/web\.port = tcp:"$INTRODUCER_WEBPORT":interface=127.0.0.1/" $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$TAHOE_CONFIG_FILE
	fi
	echo $GRID_NAME >> $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_GRIDNAME_FILE

	if [[ -z "grep -q '^AUTOSTART' $TAHOE_ETC_DEFAULT_FILE" ]]; then
                sed -i "s/\" /\"/" $TAHOE_ETC_DEFAULT_FILE
                sed -i "s/\" /\"/" $TAHOE_ETC_DEFAULT_FILE
                sed -i "s/^AUTOSTART=\"[^\"]*/& introducer /" $TAHOE_ETC_DEFAULT_FILE
                sed -i "s/ \"/\"/" $TAHOE_ETC_DEFAULT_FILE
                sed -i "s/ \"/\"/" $TAHOE_ETC_DEFAULT_FILE
        else
                echo "AUTOSTART=\"introducer\"" >> $TAHOE_ETC_DEFAULT_FILE
	fi

	## CHANGING vIP to hIP!
	if [ -f $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_FURLFILE ]; then
		#
		sed -i "s/pb:.*/pb:\/\/"$(cat $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_ID)"@"$HOST_IP":"$(cat $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_PORTFILE)"/introducer" $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_FURLFILE
	else
		echo "pb://"$(cat $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_ID)"@"$HOST_IP":"$(cat $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_PORTFILE)"/introducer" > $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_FURLFILE
	fi

	chown -vR tahoe:tahoe $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME

	## we need to start it so it will create other files
	/etc/init.d/tahoe-lafs start introducer
	/etc/init.d/tahoe-lafs stop introducer

	echo "INTRODUCER_PORT=$(cat $DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$INTRODUCER_PORTFILE)"
}

configure_node() {
	## Configure node
	NODE_INTRODUCER_FURL=${1:-""}
	NODE_NICKNAME=${2:-"MyStorage"}
	# FURL must have the HOST IP not the container IP
	# Do we need more configurations?
	NODE_WEBPORT=${3:-"3456"}
	NODE_SHARES=${4:-""}
	NODE_RESERVED=${5:-"1G"}

	sed -i "s/^nickname.*$/nickname = "$NODE_NICKNAME"/" $DAEMON_HOMEDIR/$NODE_DIRNAME/$TAHOE_CONFIG_FILE
	sed -i "s%^introducer\.furl.*$%introducer\.furl = "$NODE_INTRODUCER_FURL"%" $DAEMON_HOMEDIR/$NODE_DIRNAME/$TAHOE_CONFIG_FILE
	sed -i "s/^web\.port.*$/web\.port = tcp:"$NODE_WEBPORT":interface=0\.0\.0\.0/" $DAEMON_HOMEDIR/$NODE_DIRNAME/$TAHOE_CONFIG_FILE

	if [[ -z "grep -q '^AUTOSTART' $TAHOE_ETC_DEFAULT_FILE" ]]; then
		sed -i "s/\" /\"/" $TAHOE_ETC_DEFAULT_FILE
		sed -i "s/\" /\"/" $TAHOE_ETC_DEFAULT_FILE
		sed -i "s/^AUTOSTART=\"[^\"]*/& node /" $TAHOE_ETC_DEFAULT_FILE
		sed -i "s/ \"/\"/" $TAHOE_ETC_DEFAULT_FILE
		sed -i "s/ \"/\"/" $TAHOE_ETC_DEFAULT_FILE
	else
		echo "AUTOSTART=\"node\"" >> $TAHOE_ETC_DEFAULT_FILE
	fi
	chown -vR tahoe:tahoe $DAEMON_HOMEDIR/$NODE_DIRNAME

	## we need to start it so it will create other files
	/etc/init.d/tahoe-lafs start node
	/etc/init.d/tahoe-lafs stop node

	echo "NODE_PORT=$(cat $DAEMON_HOMEDIR/$NODE_DIRNAME/client.port)"
}

change() {
	WHO=$1
	WHAT=$2
	TO=$3
	FILE_TO_CHANGE=""

	if [[ "$WHO" == "node" ]]; then
	FILE_TO_CHANGE=$DAEMON_HOMEDIR/$NODE_DIRNAME/$TAHOE_CONFIG_FILE
	elif [[ "WHO" == "introducer" ]]; then
	FILE_TO_CHANGE=$DAEMON_HOMEDIR/$INTRODUCER_DIRNAME/$TAHOE_CONFIG_FILE
	fi

	sed -i "s%^"$WHAT".*$%"$WHAT" = "$TO"%" $FILE_TO_CHANGE
}

help() {

	echo "Tahoe-LAFS autoconfigure for Cloudy:\n"
	echo "--> "$0" configure <introducer or node> HOSTIP <ARGS>\n"
	echo "--> "$0" node change WHAT_TO_CHANGE VALUE\n"
}


case "$1" in 
 configure)
	shift
	if [[ "$1" == "introducer" ]]; then
		shift
		HOST_IP=$1
		shift
		configure_introducer "$@"
	elif [[ "$1" == "node" ]]; then
		shift
		HOST_IP=$1
		shift
		configure_node "$@"
	fi
	;;
 node)
	shift
	if [[ "$1" == "change" ]]; then
		shift
		change "node" "$@"
	fi
	;;
 *)
	help
	exit
	;;
esac
