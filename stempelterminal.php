<?php
/********************************************************************************
* Small Time - Beispiel für ein einfaches Touch - Screen - Stempelterminal
* bei nichtgebrauch Datei löschen
/*******************************************************************************
* Version 0.9.015
* Author:  IT-Master GmbH
* www.it-master.ch / info@it-master.ch
* Copyright (c), IT-Master GmbH, All rights reserved
*******************************************************************************/
$_grpwahl = '2';
$gruppe = 2;
if(isset($_GET['gruppe'])){
	$_grpwahl =  $_GET['gruppe'];
	$gruppe = $_GET['gruppe'];
}
//error_reporting(E_ALL); 
//ini_set("display_errors", 1); 
include_once ('./include/class_absenz.php');
include_once ('./include/class_user.php');
include_once ('./include/class_group.php');
include_once ('./include/class_login.php');   
include_once ('./include/class_template.php');
include_once ('./include/class_time.php');
include_once ('./include/class_month.php');
include_once ('./include/class_jahr.php');
include_once ('./include/class_feiertage.php');
include_once ('./include/class_filehandle.php');
include_once ('./include/class_rapport.php');
include_once ('./include/class_show.php');
include_once ('./include/class_settings.php');
include ("./include/time_funktionen.php");
$_grpwahl = $_grpwahl-1;
$_group = new time_group($_grpwahl);
if(isset($id)) $_grpwahl = $_group->get_usergroup($id);
$anzMA = count($_group->_array[1][$_grpwahl]);	

foreach($_group->_array[0] as $gruppen){
	//echo $gruppen[0];
}									
if(isset($_GET['json'])){
	//-------------------------------------------------------------------------------------------------------------
	// Anwesenheitsliste in ein JSON laden
	//-------------------------------------------------------------------------------------------------------------
	$tmparr = array();
	for($x=0; $x<$anzMA ;$x++){	
		$tmparr[$x]['gruppe'] = trim($_group->_array[0][$_grpwahl][$x]);		
		$tmparr[$x]['mitarbeiterid'] = trim($_group->_array[1][$_grpwahl][$x]);	
		$tmparr[$x]['loginname'] = trim($_group->_array[2][$_grpwahl][$x]);	
		$tmparr[$x]['pfad'] = trim($_group->_array[3][$_grpwahl][$x]);
		// Mitarbeiter - Name
        $name = explode(" ", trim($_group->_array[4][$_grpwahl][$x]));
		$tmparr[$x]['firstname'] = $name[0];
        $tmparr[$x]['lastname'] = @$name[1];
		//Anwesend oder nicht
		$tmparr[$x]['anwesend'] = (count($_group->_array[5][$_grpwahl][$x]))%2;	
		if($tmparr[$x]['anwesend']){
			$tmparr[$x]['status'] = 'Anwesend';
		}else{
			$tmparr[$x]['status'] = 'Abwesend';
		}				
		// Mitarbeiter - Bild anzeigen
		if(file_exists("./Data/".$_group->_array[2][$_grpwahl][$x]."/img/bild.jpg")){		
			$tmparr[$x]['bild'] = "./Data/".$_group->_array[2][$_grpwahl][$x]."/img/bild.jpg";	
		}else{
			$tmparr[$x]['bild'] = "./images/ico/user-icon.png";	
		}	
		if(isset($_group->_array[5][$_grpwahl][$x][count($_group->_array[5][$_grpwahl][$x])-1])){
			$tmparr[$x]['lasttime'] =$_group->_array[5][$_grpwahl][$x][count($_group->_array[5][$_grpwahl][$x])-1];	
			$tmparr[$x]['alltime'] = implode(" - ", $_group->_array[5][$_grpwahl][$x]);	
		}else{
			$tmparr[$x]['lasttime'] = '';
			$tmparr[$x]['alltime'] = '';
		}	
		$tmparr[$x]['passwort'] = trim($_group->_array[7][$_grpwahl][$x]);	
		$idtime_secret = 'CHANGEME';
		// stempeln über idtime
		//http://localhost:88/Kunden/time.repmo.ch/idtime.php?id=1864f9f71f65975b
		$hash = sha1($tmparr[$x]['pfad'].$tmparr[$x]['passwort'].crypt($tmparr[$x]['pfad'], '$2y$04$'.substr($idtime_secret.$tmparr[$x]['passwort'], 0, 22)));
		$tmparr[$x]['idtime'] = substr($hash, 0, 16);
	}	
	echo json_encode($tmparr);
}else{
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
			<!--<meta http-equiv="refresh" content="10">-->
			<title>SmallTime - Touch - Screen - Stempelterminal</title>
            <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
            <link href='templates/smalltime/css/terminal.css' rel='stylesheet' type='text/css'>
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.js"></script>
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css">	
			<script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.js"></script>
            <script src="js/sha1.min.js"></script>
			<script>
                function pad(num, size) {
                    var s = num+"";
                    while (s.length < size) s = "0" + s;
                    return s;
                }

                function num(val) {
                    $('#pin').val($('#pin').val()+val);
                }

                function rem() {
                    $('#pin').val($('#pin').val().slice(0, -1));
                }

                function login() {
                    if($(".mitarbeiter.selected").length == 0) {
                        alert("Bitte erst Namen auswählen!");
                        return;
                    }

                    if($(".selected").data("pin") != sha1($("#pin").val())) {
                        alert("PIN falsch!");
                        $("#pin").val('');
                        return;
                    }

                    hash = $(".selected").data("hash");

                    $("#pin").val('');

                    mastempeln(hash, function () {
                        $.ajax(
                        {
                            url: 'android.php?rfid=' + $(".selected").data("path") + '&action=getvar&class=_jahr&var=_summe_t',
                            type: 'get',
                            dataType: 'text',
                            async: true,
                            success: function(saldo)
                            {
                                if(saldo > 0) {
                                    stunden = Math.floor(saldo)
                                    $("#saldo").removeClass("red");
                                    $("#saldo").addClass("green");
                                } else {
                                    stunden = Math.ceil(saldo)
                                    $("#saldo").removeClass("green");
                                    $("#saldo").addClass("red")
                                }

                                minuten = saldo - stunden;
                                minuten = Math.round(minuten * 60);

                                if(minuten < 0) minuten *= -1

                                $("#saldo").html(stunden + "h " + minuten + "min");

                                $("#shadow").show();
                                $("#details").show();
                            }
                        });
                    });
                }

                function logoff() {
                    $(".selected").removeClass("selected");
                    $("#name").html("");
                    $("#shadow").hide();
                    $("#details").hide();
                }

                $(function() {
                    setInterval(function () {
                        const heute = new Date();
                        $("#currentTime").html(pad(heute.getDate(), 2) + "." + pad(heute.getMonth()+1, 2) + "." + heute.getFullYear() +
                            " " + pad(heute.getHours(), 2) + ":" + pad(heute.getMinutes(), 2) + ":" + pad(heute.getSeconds(), 2) + " Uhr");
                    }, 1000);

                    $(document).on('click', '.mitarbeiter', function() {
                        $(".mitarbeiter").removeClass("selected");
                        $(this).addClass("selected");

                        $("#name").html(" " + $(this).data('name'));
                    });
                });

				function start(){
					uebersicht('?gruppe=<?php echo $gruppe; ?>&json');
				}

				function mastempeln(str, cb=null){
                    $.ajax(
                    {
                        url: 'idtime.php?id=' + str + '&w=no',
                        type: 'get',
                        dataType: 'text',
                        async: true,
                        success: function(response)
                        {
                            uebersicht('?gruppe=<?php echo $gruppe; ?>&json');
                            if(cb!=null) cb();
                        }
                    });
				}

				function uebersicht(url)
				{
					$.ajax(
						{
							//url: 'idtime.php?id=' + id + '&w=no',
							url: url,
							type: 'get',
							dataType: 'json',
							async: true,
							success: function(response)
							{
								console.log(response);
								$('#maanzeige').html('');
								//$('#matemplate').show();
								var panel = $('#matemplate').clone();
								$('#matemplate').hide();
								for (i = 0; i < response.length; i++) {
									var new_panel = panel.clone();
									// Tabelle farblich unterscheiden
									if (response[i].anwesend == 1) {
										new_panel.find('.mitarbeiter').addClass('green');
									} else {
										new_panel.find('.mitarbeiter').addClass('red');
									}
									//<img src="{{bild}}" alt="{{username}}" />
									new_panel.find('#img').html('<img src="'+ response[i].bild+'" alt="'+ response[i].username+'" />');
									var html_for_mustache = new_panel.html();
									var html = Mustache.to_html(html_for_mustache, response[i]);
									$('#maanzeige').append(html);
								};
							}
						});
				}
			</script>
		</head>	
		<body onload="start();">
			<!--  HEADER !-->
			<header>
                <a href="?"><img src="images/logo_sit.png" height="40"/></a>
			</header>

			<!--  CONTENT  !-->
			<div class="content">
                <div class="container-ma" id="maanzeige">
                </div>

                <div class="container-pin">
                    <h1>Guten Tag<span id="name"></span>,</h1>
                    <hr />
                    <p>
                        <strong>Aktuelles Datum und Uhrzeit:</strong><br />
                        <span id="currentTime"></span>
                    </p>

                    <p>
                        <strong>Bitte geben Sie Ihre PIN ein:</strong><br>
                        <input type="password" id="pin" disabled>
                    </p>

                    <div class="pinpad">
                        <div class="number" onclick="num('1');">1</div> <div class="number" onclick="num('2');">2</div> <div class="number" onclick="num('3');">3</div>
                        <div class="number" onclick="num('4');">4</div> <div class="number" onclick="num('5');">5</div> <div class="number" onclick="num('6');">6</div>
                        <div class="number" onclick="num('7');">7</div> <div class="number" onclick="num('8');">8</div> <div class="number" onclick="num('9');">9</div>
                        <div class="number"></div> <div class="number" onclick="num('0');">0</div> <div class="number" onclick="rem();"><i class="fa fa-caret-square-o-left" aria-hidden="true"></i></div>
                    </div>

                    <div class="login" onclick="login();">OK</div>
                </div>
            </div>

            <!-- DETAILS -->
            <div id="shadow"></div>
            <div id="details">
                <p>Stundenkonto:</p>
                <p id="saldo" class="green">50</p>
                <div class="btn" onclick="logoff();">OK</div>
            </div>

            <!--  TEMPLATES  !-->
            <div id="matemplate"  style="visibility: hidden">
                <div class="mitarbeiter" data-hash="{{idtime}}" data-pin="{{passwort}}" data-name="{{firstname}} {{lastname}}" data-path="{{pfad}}">
                    <div class="bild"><img src="{{bild}}" alt="{{username}}" height="50" /></div>
                    <div class="name">
                        <div class="firstname">{{firstname}}</div>
                        <div class="lastname">{{lastname}}</div>
                    </div>
                </div>
            </div>
		</body>
	</html> 
	<?php
}
?>