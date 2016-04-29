<?php
//For Docker
$urlpath="$staticFile/docker";
$docker_pkg = "docker.io";
$dev = "docker0";
$dockerexec = "/usr/bin/docker";
$containerdev = "eth0";
$dockerpsfile = "/var/local/cDistro/plug/resources/monitor-aas/ps_image.dockerfile";
$dockerpsimagename = "ps_test";
$dockertahoefile = "/var/local/cDistro/plug/resources/monitor-aas/tahoe_image.dockerfile";
$dockertahoeimagename = "tahoe_test";
$dockertahoeiname = "tahoe_introducer";
$dockertahoenname = "tahoe_storage";

//peerstreamer
$pspath="/opt/peerstreamer/";
$psprogram="streamer-udp-grapes-static";
define("pspath", "/opt/peerstreamer/");
define("psprogram", "streamer-udp-grapes-static");
$title="Peer Streamer";

//VLC
$vlcpath="/usr/bin/";
$vlcprogram="cvlc";
$vlcuser="nobody";

//Avahi type
$avahi_ps_type="peerstreamer";
$avahi_tahoe_type="tahoe-lafs";

//psutils
$psutils="/../resources/peerstreamer/pscontroller";

//curl
$curlprogram="/usr/bin/curl";

// Aquest paquest no existeix encar   i per tant pot donar algun problema.
$pspackages="peer_web_gui";

// Mapping between service name and docker image
$service_map = array('peerstreamer'=>'ps_test', 'tahoe-lafs'=>'tahoe_test', 'tahoe'=>'tahoe_test', 'tahoe-introducer'=>'tahoe_test','tahoe-node'=>'tahoe_test');

//Tahoe-lafs service
$TAHOE_RESOURCES_PATH=$_SERVER['DOCUMENT_ROOT'].'/plug/resources/tahoe-lafs';
$TAHOELAFS_CONF="tahoe-lafs.conf";
$TAHOE_VARS=load_conffile($TAHOE_RESOURCES_PATH.'/'.$TAHOELAFS_CONF);

function index() {
	global $title, $urlpath, $docker_pkg, $staticFile, $dockerpsimagename, $dockertahoeimagename;

	$page = hlc(t("docker_title"));
	$page .= hl(t("docker_desc"), 4);

	if (!isPackageInstall($docker_pkg)) {
		$page .= "<div class='alert alert-error text-center'>".t("docker_not_installed")."</div>\n";
		$page .= addButton(array('label'=>t("docker_install"),'class'=>'btn btn-success', 'href'=>"$urlpath/install"));
		return array('type'=>'render','page'=>$page);
	} elseif (!isRunning()) {
		$page .= "<div class='alert alert-error text-center'>".t("docker_not_running")."</div>\n";
		$page .= addButton(array('label'=>t("docker_start"),'class'=>'btn btn-success', 'href'=>"$urlpath/start"));
		$page .= addButton(array('label'=>t('docker_remove'),'class'=>'btn btn-danger', 'href'=>$staticFile.'/default/uninstall/'.$docker_pkg));
		return array('type'=>'render','page'=>$page);
	} else {
		$page .= ptxt(info_docker());
		$page .= "<div class='alert alert-success text-center'>".t("docker_running")."</div>\n";
		if (!isPSCreated()) {
		 $page .= "<div class='alert alert-error text-center'>".t("Peerstreamer Container")."</div>\n";
		 $page .= addButton(array('label'=>t("Create PeerStreamer Image"), 'class'=>'btn btn-success', 'href'=>"$urlpath/create_peerstreamer"));
		} else {
	 	 $page .= "<div class='alert alert-success text-center'>".t("Peerstreamer Image: <br>(".getImageName($dockerpsimagename)." - ".getImageID($dockerpsimagename).")")."</div>\n";
		 $page .= addButton(array('label'=>t("Publish a Video Stream"), 'class'=>'btn btn-success', 'href'=>"$urlpath/ps_form?ps=source"));
		 $page .= addButton(array('label'=>t("Connect to Peer"), 'class'=>'btn btn-success', 'href'=>"$urlpath/ps_form?ps=peer"));
	//	 $page .= addButton(array('label'=>t("TEST"), 'class'=>'btn btn-success', 'href'=>"$urlpath/publish_serv"));
		 $page .= "<p><div><pre>".info_peerstreamer()."</pre></div></p>";
		 $page .= "<br>";
		}

		if(!isTahoeCreated()) {
		 $page .= "<div class='alert alert-error text-center'>".t("Tahoe-Lafs Container")."</div>\n";
		 $page .= addButton(array('label'=>t("Create Tahoe-Lafs Image"), 'class'=>'btn btn-success', 'href'=>"$urlpath/create_tahoe"));
		} else {
	 	 $page .= "<div class='alert alert-success text-center'>".t("Tahoe-Lafs Image: <br>(".getImageName($dockertahoeimagename)." - ".getImageID($dockertahoeimagename).")")."</div>\n";
		 $page .= addButton(array('label'=>t("Launch Tahoe Introducer"), 'class'=>'btn btn-success', 'href'=>"$urlpath/start_tahoe_introducer"));
		 $page .= addButton(array('label'=>t("Launch Tahoe Storage"), 'class'=>'btn btn-success', 'href'=>"$urlpath/start_tahoe_node"));
		 $page .= "<p><div><pre>".info_tahoe()."</pre></div></p>";
		 $page .= "<br>";
		}
		$page .= "<div class='alert alert-error text-center'>".t("Other Services Containers")."</div>\n";


		$page .= "<p></p>";
		$page .= addButton(array('label'=>t("docker_stop"),'class'=>'btn btn-danger', 'href'=>"$urlpath/stop"));

		return array('type' => 'render','page' => $page);
	}
}

function publish_serv() {
//FOR TESTING: TO BE REMOVED AFTER
	$page = publish_service("peerstreamer", "test", "6411");
	return array('type' => 'render','page' => $page);
}

function unpublish_serv() {
//FOR TESTING: TO BE REMOVED AFTER
	$page = unpublish_service("peerstreamer","6411");
	return array('type' => 'render','page' => $page);

}

function getImageId($str) {
	global $dockerexec;
	$cmd = $dockerexec." images | grep ".$str." | awk '{print $3}'";
	$id = execute_program_shell($cmd)['output'];
	return trim($id);
}

function getImageName($str) {
	global $dockerexec;
	$cmd = $dockerexec." images | grep ".$str."| awk '{print $1}'";
	$name = execute_program_shell($cmd)['output'];
	return trim($name);
}

function isPSCreated() {
	global $dockerexec,$dockerpsimagename;
	$cmd = $dockerexec." images | grep ".$dockerpsimagename;
	$ret=execute_program_shell($cmd);
	if(!empty($ret['output']))
		return true;

	return false;
}

function isTahoeCreated() {
	global $dockerexec, $dockertahoeimagename;
	$cmd = $dockerexec." images | grep ".$dockertahoeimagename;
	$ret=execute_program_shell($cmd);
	if(!empty($ret['output']))
		return true;

	return false;
}

function isRunning(){
	$cmd = "/usr/bin/docker ps";
	$ret = execute_program($cmd);
  return ( $ret['return'] ==  0 );
}
function install(){
  global $title, $urlpath, $docker_pkg;

  $page = package_not_install($docker_pkg,t("docker_desc"));
  return array('type' => 'render','page' => $page);
}
function start() {
	global $urlpath;

	execute_program_detached("service docker start");
	setFlash(t('docker_start_message'),"success");
	return(array('type'=> 'redirect', 'url' => $urlpath));
}
function stop() {
	global $urlpath;

	execute_program_detached("service docker stop");
	setFlash(t('docker_stop_message'),"success");
	return(array('type'=> 'redirect', 'url' => $urlpath));
}

function info_docker(){
	global $dev;

	$cmd = "/sbin/ip addr show dev $dev";
	$ret = execute_program($cmd);
  return ( implode("\n", $ret['output']) );

}

function info_peerstreamer($trunc=""){
	global $dev, $staticFile,$dockerpsimagename, $avahi_ps_type;
	$total = "";
	//It will now get json docker ps and show as a html table

	$cmd = "python3 /var/local/cDistro/plug/resources/monitor-aas/dockerpsjson.py";
	$json = execute_program_shell($cmd);

	$total .= json_to_table(json_decode(trim($json['output']),true), $dockerpsimagename);

	return $total;
}

function info_tahoe($trunc="") {
	global $staticFile, $dockertahoeimagename, $avahi_tahoe_type;
	$total = "";
	//It will now get json docker ps and show as a html table

	$cmd = "python3 /var/local/cDistro/plug/resources/monitor-aas/dockerpsjson.py";
	$json = execute_program_shell($cmd);

	$total .= json_to_table(json_decode(trim($json['output']),true), $dockertahoeimagename);
	return $total;
}

function json_to_table($json, $service) {
	$arr = array("CONTAINER ID","IMAGE","COMMAND","CREATED","STATUS","PORTS","NAMES");

	$table = "<table width=100%>";
	$table .= "<tr>";
	foreach ($arr as $p) $table .= "<td>".$p."</td>";
	$table .= "</tr>";

	foreach ($json as $line){
	  if(strpos($line["IMAGE"], $service) !== false) {
		$table .= "<tr>";
		foreach ($arr as $p) $table .="<td>".str_replace(", ",",<br>",$line[$p])."</td>";
		//For Peerstreamer we should add Peer View button
		if(strpos($line["NAMES"],"_peer_") !== false)
		$table .= "<td>".addButton(array('label'=>t("Watch"), 'class'=>'btn btn-success', 'href'=>"${staticFile}/docker/psviewer?u=".getPeerStream($line["CONTAINER ID"])))."</td>\n";
		//For Tahoe maybe we need to add other buttons

		//now we are going to add the stop button
		$table .= "<td>".addButton(array('label'=>t("Stop"), 'class'=>'btn btn-success', 'href'=>"${staticFile}/docker/stop_service?sid=".$line["CONTAINER ID"]))."</td>\n";
		$table .= "</tr>";
	  }
	}
	$table .= "</table>";

	return $table;
}

function getPeerStream($sid) {
	global $_SERVER;

	//We are going to get the stream url for this container how?
	$ip = $_SERVER['SERVER_ADDR'];
	if (empty($ip))
		$ip = trim(execute_program_shell("ip r | grep 10.| awk '{print $9}'")['output']);
	$port = getServicePort($sid);
	//Assuming for now its rtsp
	$type = "rtsp";

	$url = $type."://".$ip.":".$port."/";

	return urlencode($url);
}

function create_peerstreamer(){
	global $dev, $urlpath, $staticFile, $dockerpsfile, $dockerpsimagename;
	if (!file_exists($dockerpsfile)) {
		$page = "<pre>The dockerfile could not be located, your Cloudy version may need to be updated.</pre>";
		$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
		return array('type' => 'render','page' => $page);
	}

	//Needs to be run as root most probably
	$cmd = "docker build -t ${dockerpsimagename} - < ".$dockerpsfile;
	execute_program_detached($cmd);

	$page = "<pre>Building of Peerstreamer Image has begun in background, it may take some time to finish.</pre>";
	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type' => 'render','page' => $page);
}

function create_tahoe(){
	global $dev, $urlpath, $staticFile, $dockertahoefile, $dockertahoeimagename;
	if (!file_exists($dockertahoefile)) {
		$page = "<pre>The dockerfile could not be located, your Cloudy version may need to be updated.</pre>";
		$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
		return array('type' => 'render','page' => $page);
	}

	//Needs to be run as root most probably
	$cmd = "docker build -t ${dockertahoeimagename} - < ".$dockertahoefile;
	execute_program_detached($cmd);

	$page = "<pre>Building of Tahoe-lafs Image has begun in background, it may take some time to finish.</pre>";
	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type' => 'render','page' => $page);


}

function ps_form() {
	global $urlpath;
	global $paspath,$title;
        global $staticFile;
	$page = "";
	$ps=$_GET['ps'];

	if ($ps == "source") {
	$page = hlc(t($title));
	$page .= hlc(t('Publish a video stream'),2);
	$page .= par(t("Please write a stream source"));
        $page .= par(t("If the URL is a rtmp, please make sure to introduce all the requiered parameters separated ONLY by a simple comma."));
        $page .= createForm(array('class'=>'form-horizontal','action'=>$urlpath.'/ps_publish_post'));
	$page .= "<input type='hidden' name='ps' value='source'>";
        $page .= addInput('url',t('URL Source'),'',array('class'=>'input-xxlarge'));
        $page .= addInput('port',t('Port Address'));
        $page .= addInput('description',t('Describe this channel'));
        $page .= addSubmit(array('label'=>t('Publish'),'class'=>'btn btn-primary'));
        $page .= addButton(array('label'=>t('Cancel'),'href'=>$staticFile.'/docker'));

	} else if ($ps == "peer") {
	$page = hlc(t($title));
        $page .= hlc(t('Connect to a Peer'),2);
        $page .= par(t("You can join a stream through a Peer in the network, or you can find channels in the avahi menu option."));
        $page .= createForm(array('class'=>'form-horizontal','action'=>$urlpath.'/ps_publish_post'));
	$page .= "<input type='hidden' name='ps' value='peer'>";
        $page .= t('Peer:');
        $page .= addInput('ip',t('IP Address'),$peerip);
        $page .= addInput('port',t('Port Address'),$peerport);
        $page .= t('You:');
        $page .= addCheckbox('type', t('Server Type'), array('RTSP'=>t('Create RTSP Server'),'UDP'=>t('Send to UDP Server')));
        $page .= addInput('myport',t('Port'));
        $page .= addSubmit(array('label'=>t('Connect'),'class'=>'btn btn-primary'));
        $page .= addButton(array('label'=>t('Cancel'),'href'=>$staticFile.'/docker'));

	 $page .= "";
	}
	return(array('type'=>'render','page'=> $page));
}

function ps_publish_post() {
	global $urlpath;
	$url = $_POST['url'];
        $port = $_POST['port'];
        $description = $_POST['description'];
        $ip = $_POST['ip'];
	$ps = $_POST['ps'];

        $page = "<pre>";
        $page .= start_peerstreamer($url,$ip,$port,$description,$ps);
        $page .= "</pre>";

        return(array('type' => 'render','page' => $page));
}


function start_peerstreamer($url,$ip,$port,$description,$ps){
	global $urlpath,$staticFile,$containerdev,$dockerexec,$dockerpsimagename,$avahi_ps_type;
	//$myip = "127.0.0.1"; //THIS NEEDS TO CHANGE!!!! TO THE CONTAINER ADDRESS!!!!!
	$myip = "'\\\"'$(ip r | grep 172. | awk '\''{print $9}'\'')'\\\"'";
	$endcmd = "&& /bin/bash"; //The end command has to stay up in foreground for docker to continue the container

	if($ps == "source")
	$cmds = "publish ".$url." ".$port." ".$containerdev." ".$description; //device hardcoded!!!

	$type = $_POST['type'];
	$iport = $_POST['myport'];
	if($ps == "peer" && $type == "RTSP")
	$cmds = "connectrtsp ${ip} ${port} ${iport} ${myip} ${containerdev}"; //to see if its correct

	if($ps == "peer" && $type == "UDP")
	$cmds = "connectudp ${ip} ${port} ${myip} ${iport} ${containerdev}"; //to see if its correct

	//Exporting ports either the source or the peer iport
	if(isset($iport)) $port = $iport; //$expPorts = "-p ${iport}:${iport}";
	$expPorts = "-p ${port}:${port}/udp";
	$expPorts .= " -p ${port}:${port}";
	
	//IF /var/run/pspeers.conf is not there than we need to create otherwise it will bug
	$startcmd = "touch /var/run/pspeers.conf &&";

	$cmd = $dockerexec." run --name ${avahi_ps_type}_${ps}_${port} -tid ${expPorts} ${dockerpsimagename} /bin/bash -c '".$startcmd." /bin/bash /var/local/cDistro/plug/resources/peerstreamer/pscontroller ".$cmds." ".$endcmd."'";
	$ret = execute_program_shell($cmd);
	setFlash(t('Docker Peerstreamer Container'),"success");

	$page = "CONTAINERID: ".trim($ret['output'])."<br>";

	//Need to account for errors and not publish!!!!!
	if(empty($description)) $description = "Republishing";

	$page .= publish_service($avahi_ps_type,$description,$port)."<br>";

	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
	return $page;
}

function test_pub() {
	$page = "";

	$page .= publish_service("tahoe-lafs","nothing", 40024);
	return(array('type' => 'render','page' => $page));
}

function publish_service($service, $description, $port, $opts=array()) {
	global $dev,$dockerexec;
	//here we should publish services as was before
	$temp="";
	$einfo="";
	if(!empty($opts)) {
	 foreach($opts as $val)
	 	$einfo.=$val.",";
	}
	//now we get the extra information from service
	// two things, internal and external:

	//Internal:
	$sid=trim(getContainerId($port));
	//Because we need to wait a bit till tahoe is executed in the container
	sleep(2);
	$cmd = $dockerexec." exec ".$sid." ".getExtraCmd($service,$port,$opts);
	$iobj = execute_program_shell($cmd)['output'];

	//External:
	$cmd = $dockerexec." inspect ".$sid." | jq -c ."; //From here we can take out information
	$cmd = trim(getExtraCmd("docker_".$service, $sid));
	$eobj = execute_program_shell($cmd)['output'];

	//Need to merge both arrays as is, array_merge does not do that properly
	if($iobj[strlen($iobj)-1]=='}')
	 $ret = substr($iobj, 0, -1).",".substr($eobj,1);
	else
	 $ret = substr($iobj, 0, -2).",".substr($eobj,1);

	$ret = trim(strtr($ret, array(','=>';')));
	$einfo .= "einfo=".addslashes(addslashes($ret));

	$temp=avahi_publish($service, $description, $port, $einfo);

	//$temp .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
	return ptxt($temp);
}

function unpublish_service($service, $port) {
	global $dev, $dockerexec;

	$temp="";
//	$sid=trim(getContainerId($port));
	//May need necessary update to container information on Monitor-aAS
	//instead of just unpublishing
	$temp .= avahi_unpublish($service,$port);
//	$temp .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
	return ptxt($temp);
}

function getContainerId($port) {
	global $dockerexec;
	//each container is different by the ports they export
	$cmd=$dockerexec." ps | grep ".$port." |awk '{print $1}'";
	$id = execute_program_shell($cmd)['output'];

	return trim($id);
}

function getExtraCmd($service, $port="", $opts="") {
	//Each service has its own way to gather extra information
	switch ($service) {
	case 'peerstreamer':
	//getting extra info using the common.sh file?
	return "/bin/bash /var/local/cDistro/plug/resources/monitor-aas/common.sh gather_information peerstreamer ".$port;
	case 'tahoe-lafs':
	//Not sure yet, either common.sh or tahoe-lafs.service
	return "/bin/bash /var/local/cDistro/plug/resources/monitor-aas/common.sh gather_information tahoe-lafs";
	case 'synchthing':
 	///bin/bash /var/local/cDistro/plug/resources/monitor-aas/common.sh gather_information synchthing xml_config_file_inside_container
	return "";

	//In case of docker extra info
	case 'docker_peerstreamer':
	return "/bin/bash /var/local/cDistro/plug/resources/monitor-aas/common.sh gather_information ".$service." ".$port;
	case 'docker_tahoe-lafs':
	return "/bin/bash /var/local/cDistro/plug/resources/monitor-aas/common.sh gather_information ".$service." ".$port;

	default:
	//maybe has default it should be starting time?
	return "";
	}

}

function getServicePort($sid) {
	$cmd = "docker inspect ".$sid." | jq .[].NetworkSettings.Ports | grep tcp | cut -d'/' -f1|cut -d'\"' -f2 ";

	$ret = execute_program_shell($cmd)['output'];

	return trim($ret);
}

function getServiceBySID($sid) {
	global $service_map;
	$service = trim(execute_program_shell("docker ps|grep ".$sid." | awk '{print $2}'|cut -d':' -f1")['output']);
	// this gets the docker image name, we need to associate image name to service name
	foreach ($service_map as $k => $v) {
		if($v == $service)
		return $k;
	}

	return null;
}

function stop_service() {
	global $dev, $staticFile;

	$sid = $_GET['sid'];
	$service = getServiceBySID($sid);
	$port = getServicePort($sid);

	//IF service == null than there is no service available!

	$page = "";
	//Now we need to stop service by docker stop $sid / docker rm $sid (dont wont the container to stay in drive)
	//unpublish the service as before avahi_unpublish(...); 
	//the service itself may need some command to stop so.. docker exec $sid /bin/bash -c 'stop service from inside container'
	//before stopping the container
	$page .= "<pre>Service ${service} with SID ${sid} on Port ${port} has been stopped and unpublished</pre>";

	$cmd = "docker stop ".$sid;
	$cmd1 = "docker rm ".$sid;
//	$page .= "<p> For now will just: docker stop <containerid>, but this should be changed in future</p>";
	
	//Ports if there are more than one
	$ports = explode("\n",$port);
	if (count($ports) > 1) $port = $ports[1]; //should be the second one
	//to be sure 8228 is not the one!

	//Unpublishing service from avahi/serf
	$page .= unpublish_service($service, $port);
	$page .= ptxt(execute_program_shell($cmd)['output']);
	//For now we do not remove containers, should be changed because of harddisk space
	$page .= ptxt(execute_program_shell($cmd1)['output']);

	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type'=>'render','page'=>$page);

}

function vlcobject($url){

        $o = "";
        $o .= '<div id="vlc-plugin" >';
        $o .= '<!-- <object classid="clsid:9BE31822-FDAD-461B-AD51-BE1D1C159921" codebase="http://download.videolan.org/pub/videolan/vlc/last/win32/axvlc.cab"></object> -->';
        $o .= '<embed pluginspage="http://www.videolan.org"';
        $o .= 'type="application/x-vlc-plugin"';
        $o .= 'version="VideoLAN.VLCPlugin.2"';
        $o .= 'width="720" volume="50"';
        $o .= 'height="480"';
        $o .= 'name="vlc" id="vlc"';
        $o .= 'autoplay="true" allowfullscreen="true" windowless="true" loop="true" toolbar="false"';
        $o .= ' target="'.$url.'">';
        $o .= '</embed>';
        $o .= '</div>';

        return($o);
}

function psviewer(){

        global $staticFile,$title;

	$url = urldecode($_GET['u']);

        $page = hlc(t($title));
        $page .= par(t("PeerStreamer s'est   executant en segon pla, si tens el connector de vlc podr  s veure el video al teu navegador."));
        $page .= vlcobject($url);
        $page .= par(t("Alternativament pots accedir al video usant el seg  ent enlla   al teu player preferit."));
        $page .= ptxt($url );

	$page .= addButton(array('label'=>t('List'),'href'=>$staticFile.'/docker'));
        return(array('type' => 'render','page' => $page));
}


function start_tahoe_introducer() {
	global $webpage, $staticFile, $urlpath, $dockertahoeimagename, $dockerexec, $TAHOE_VARS, $dockertahoeiname, $avahi_tahoe_type;
	$description = "Tahoe-LAFS-Grid";
	$endcmd = "&& /bin/bash"; //The end command has to stay up in foreground for docker to continue the container

	$page = "";

	$page .= "<p>Tahoe-Lafs Introducer (For tests, Running with default values)</p>";
	
	//CONFIGURATION SHOULD NOT BE STATIC!!!
	$startcmd = $TAHOE_VARS['TAHOE_ETC_INITD_FILE']." start ".$TAHOE_VARS['INTRODUCER_DIRNAME'];
	$expPorts = "-p 8228:8228";
	$internal = trim(execute_program_shell("docker run -i ${dockertahoeimagename} cat ".$TAHOE_VARS['DAEMON_HOMEDIR']."/introducer/introducer.port")['output']);
	$expPorts .= " -p ${internal}:${internal}";
	$expPorts .= " -p ${internal}:${internal}/udp";
	
	$cmd = $dockerexec." run --name ${dockertahoeiname}_${internal} -tid ${expPorts} ${dockertahoeimagename} /bin/bash -c '".$startcmd." ".$endcmd."'";
	$ret = execute_program_shell($cmd)['output'];
	$page .= ptxt($ret);

	//Publishing introducer
	$page .= publish_service($avahi_tahoe_type,$description,$internal)."<br>";
	//Publishing as before (with avahi-service may not be possible)
	//we need to get information from inside and outside of container

	$page .= addButton(array('label'=>t("Tahoe Introducer"), 'class'=>'btn btn-success', 'href'=>"$urlpath/start_tahoe_introducer"));
	$page .= addButton(array('label'=>t("Tahoe Storage"), 'class'=>'btn btn-success', 'href'=>"$urlpath/start_tahoe_node"));

	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type'=>'render','page'=>$page);

}

function start_tahoe_node() {
	global $webpage, $staticFile, $urlpath, $dockertahoeimagename, $dockerexec, $TAHOE_VARS, $dockertahoenname;
	$endcmd = "&& /bin/bash"; //The end command has to stay up in foreground for docker to continue the container

	$page = "";

	$page .= "<p>Tahoe-Lafs Storage (For tests, Running with default values)</p>";

	//SHOULD NOT BE STATIC!!!!!!
	$introducer_pb="pb://mpaishrzgopngzdsct4aobfdkzdnjys4@172.17.0.175:40024,127.0.0.1:40024,10.139.40.91:40024/introducer"; //static for now
	//Change introducer!

//	$r = trim(execute_program_shell("docker run -i ${dockertahoeimagename} /bin/bash /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh node change introducer.furl ".$introducer_pb)['output']);
	
	$startcmd = "/bin/bash /var/local/cDistro/plug/resources/monitor-aas/tahoe.sh node change introducer.furl ".$introducer_pb." && ";
	$startcmd .= $TAHOE_VARS['TAHOE_ETC_INITD_FILE']." start ".$TAHOE_VARS['NODE_DIRNAME'];
	$expPorts = "-p 3456:3456";
	$internal = trim(execute_program_shell("docker run -i ${dockertahoeimagename} cat ".$TAHOE_VARS['DAEMON_HOMEDIR']."/node/client.port")['output']);
	$expPorts .= " -p ${internal}:${internal}";
	$expPorts .= " -p ${internal}:${internal}/udp";

	$cmd = $dockerexec." run --name ${dockertahoenname}_node_${internal} -tid ${expPorts} ${dockertahoeimagename} /bin/bash -c '".$startcmd." ".$endcmd."'";
//$page .= $cmd;	
	$ret = execute_program_shell($cmd)['output'];

	//Node is not published but updated in serf
	$pubcmd = "/bin/bash /usr/share/avahi-service/files/tahoe-lafs.service nodeStart docker ${ret}";
//	$page .= ptxt(execute_program_shell($pubcmd)['output']);

	$page .= ptxt($ret);

	$page .= addButton(array('label'=>t("Tahoe Introducer"), 'class'=>'btn btn-success', 'href'=>"$urlpath/start_tahoe_introducer"));
	$page .= addButton(array('label'=>t("Tahoe Storage"), 'class'=>'btn btn-success', 'href'=>"$urlpath/start_tahoe_node"));

	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type'=>'render','page'=>$page);

}
