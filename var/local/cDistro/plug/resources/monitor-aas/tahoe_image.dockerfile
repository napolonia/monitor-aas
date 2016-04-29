#### DOCKER FILE
##  Test Image building for Peerstreamer service
##  :: docker build -t <name> - < ps_image.dockerfile
##  will build the image without working dir, from the dockerfile
####

FROM philcryer/min-wheezy:latest
MAINTAINER cloudy@ac.upc.edu

ENV PACKAGE_NAME tahoe-lafs
RUN apt-get update
RUN apt-get install -y net-tools
RUN apt-get install -y rsyslog

## Adding files to container
RUN mkdir -p /var/local/cDistro/plug/resources/monitor-aas/
ADD http://10.139.40.91:7000/plug/resources/monitor-aas/common.sh /var/local/cDistro/plug/resources/monitor-aas/common.sh

RUN mkdir -p /var/local/cDistro/plug/resources/tahoe-lafs/
ADD http://10.139.40.91:7000/plug/resources/tahoe-lafs/tahoe-lafs.init.d /var/local/cDistro/plug/resources/tahoe-lafs/tahoe-lafs.init.d
ADD http://10.139.40.91:7000/plug/resources/tahoe-lafs/tahoe-lafs.etc.default /var/local/cDistro/plug/resources/tahoe-lafs/tahoe-lafs.etc.default
## Added to configure tahoe-lafs
#ADD http://10.139.40.91:7000/plug/resources/monitor-aas/tahoe.sh /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh


RUN apt-get install -y tahoe-lafs
RUN groupadd --system tahoe
RUN useradd --system -g tahoe --home-dir "/var/lib/tahoe-lafs" --shell "/usr/sbin/nologin" "tahoe"
RUN mkdir "/var/lib/tahoe-lafs/"
RUN chown -vR tahoe:tahoe "/var/lib/tahoe-lafs"
RUN cp -fv /var/local/cDistro/plug/resources/tahoe-lafs/tahoe-lafs.init.d /etc/init.d/tahoe-lafs
RUN cp -fv /var/local/cDistro/plug/resources/tahoe-lafs/tahoe-lafs.etc.default /etc/default/tahoe-lafs
RUN chmod -v +x /etc/init.d/tahoe-lafs
RUN update-rc.d tahoe-lafs defaults

## We should have a IF clause for introducer or node
#RUN if [ $TAHOE_IN -eq "introducer" ]; then /usr/bin/tahoe create-"$TAHOE_IN" /var/lib/tahoe-lafs/introducer; else /usr/bin/tahoe create-node? /var/lib/tahoe-lafs/node; fi
RUN mkdir /var/lib/tahoe-lafs/introducer
RUN mkdir /var/lib/tahoe-lafs/node
RUN "/usr/bin/tahoe" create-introducer "/var/lib/tahoe-lafs/introducer"
RUN "/usr/bin/tahoe" create-node "/var/lib/tahoe-lafs/node"

ADD http://10.139.40.91:7000/plug/resources/monitor-aas/tahoe.sh /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh

## Since from here we do not know which will be thefor introducer or node
# Ill configure for both and just run one of them each time
RUN /bin/bash /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh configure introducer 10.1.26.2
RUN /bin/bash /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh configure node 10.1.26.2

# THIS IS EXECd WHEN CREATING CONTAINER!! along with "&& /bin/bash" for container not to stop running
# /etc/init.d/tahoe-lafs start introducer # for introducer
# /etc/init.d/tahoe-lafs start node # for storage node

# IF it does not start we can call it with both options worked:
# tahoe start /var/lib/tahoe-lafs/introducer or node ## --syslog may not be working
# or twistd -ny /var/lib/tahoe-lafs/introducer/tahoe-introducer.tac
### ADDING after so IT WILL update the image
#ADD http://10.1.26.2:7000/plug/resources/monitor-aas/tahoe.sh /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh

## ADDing JQ and PS
RUN echo "deb http://http.debian.net/debian wheezy-backports main" >> /etc/apt/sources.list
RUN apt-get update
RUN apt-get -t wheezy-backports install -y --force-yes jq
RUN apt-get install -y --force-yes procps
