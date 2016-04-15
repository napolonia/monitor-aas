#!/bin/bash

#First we are going to download the gz file
cd /tmp/
TMPDIR=`mktemp -d newcloudy.XXXX` && {
cd $TMPDIR
TMPDIR=/tmp/$TMPDIR

### Args
addr="$1"
echo "Connecting to $addr ..."
scp "$addr" .

errors=$?
if [[ "$errors" > 0 ]]; then
 echo "Error: Could not download updated file, $errors"
 rm -r $TMPDIR
 exit 1
fi

tar -xvf newcloudy.tar.gz -C .
errors=$?
if [[ "$errors" > 0 ]]; then
 echo "Error: Could not unzip file, $errors"
 rm -r $TMPDIR
 exit 2
fi

cd newcloudy/
#Then we are going to copy all files to their respective locations
dirs=($(ls -d */))
mkdir /test
for i in "${dirs[@]}"
do
 echo "Copying directory $i"
 #mkdir -p /test/$i
 #echo "cp -r $i/* /$i"
 cp -r $i/* /$i
done

#Restarting serf with new handler
/etc/init.d/serf stop
/etc/init.d/serf start

# Cleaning up..
cd /
rm -r $TMPDIR
}
echo "I'm done, all things should be working now. Thank you!"
