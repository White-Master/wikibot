<?php
set_time_limit(0);
ini_set('display_errors', 'on');

$config = array( 
        'server' 	=> 'asimov.freenode.net',  // Servidor IRC
        'port'   	=> 6667,  // Puerto
        'channel' 	=> '#canal', // Canal
        'name'   	=> 'Wiki bot',  // GECOS del bot 
        'nick'   	=> 'WikiBot',  // Nick del bot
        'pass'  	=> '', // Contraseña del servidor
		'nsuser'	=> 'WhiteBot', // Usuario de nickserv del bot (usualmente es el mismo nick)
		'nspass'	=> 'masbot', // Contrasña de nickserv
		'wiki'		=> 'http://ocioxtreme.skn1.com/wiki/', // Wiki
);
                                 
class IRCBot {
        var $socket;
        var $ex = array();
		private $kk;

        function __construct($config){
                $this->socket = fsockopen($config['server'], $config['port']);
                if(!$this->socket){ die("No se pudo conectar al servidor"); }
                $this->login($config);
                $this->main($config);
        }

        function login($config){
                $this->send_data('USER', $config['nick'].' * '.$config['nick'].' :'.$config['name']);
                $this->send_data('NICK', $config['nick']);
        }

        function main($config){             
				if($this->kk==1){ sleep(1);}
                $data = fgets($this->socket, 256);
                
                echo nl2br($data);

                $this->ex = explode(' ', $data);

                if($this->ex[0] == 'PING'){ $this->send_data('PONG', $this->ex[1]); }

                $command = str_replace(array(chr(10), chr(13)), '', $this->ex[3]);
				preg_match('@^(?:\:.*? )?(.*?) @', $data, $coi);
				@$com = $coi[1];
                
				switch($com){
					case "001":
						$this->send_data('PRIVMSG NickServ :IDENTIFY '.$config['nsuser'].' '.$config['nspass']);
						$this->send_data('JOIN', $config['channel']);
						break;
					case "376":	$this->kk=1;
				}
				
				if($this->kk==1){
					$fl=file_get_contents("$config[wiki]api.php?action=query&list=recentchanges&format=json&rcprop=user|comment|flags|title|timestamp|loginfo");
					$fg=json_decode($fl);
					@$tsfile = fopen (dirname(__FILE__). "/le.txt", "r");
					@$tscfile= fread($tsfile, 21);
					$s="";
					if(trim($tscfile)!=trim($fg->query->recentchanges[0]->timestamp)){
						
						switch($fg->query->recentchanges[0]->type){
							case "edit":
								$s="03".$fg->query->recentchanges[0]->user." ha 08editado el articulo [[".$fg->query->recentchanges[0]->title."]] con el siguiente comentario: 07".$fg->query->recentchanges[0]->comment;
								if(@isset($fg->query->recentchanges[0]->minor)){ $s.="11 Esta es una edición menor."; } 
								break;
							case "new":
								$s="03".$fg->query->recentchanges[0]->user." ha 03creado el articulo [[".$fg->query->recentchanges[0]->title."]] con el siguiente comentario: 07".$fg->query->recentchanges[0]->comment;
								break;
							case "log":
								if($fg->query->recentchanges[0]->logtype=="newusers"){
									$s="Se ha registrado el usuario ".$fg->query->recentchanges[0]->user."";
								}elseif($fg->query->recentchanges[0]->logtype=="rights"){
									if($fg->query->recentchanges[0]->rights->old==""){$old="(ninguno)";}else{$old=$fg->query->recentchanges[0]->rights->old;}
									if($fg->query->recentchanges[0]->rights->new==""){$new="(ninguno)";}else{$new=$fg->query->recentchanges[0]->rights->new;}
									$s="Se han cambiado los privilegios de [[".$fg->query->recentchanges[0]->title."]] de ".$old." a ".$new;
								}
						}
						fclose($tsfile);
						$tsfile = fopen (dirname(__FILE__). "/le.txt", "w");
						fwrite($tsfile, $fg->query->recentchanges[0]->timestamp);
						fclose($tsfile);
						$this->send_data("PRIVMSG $config[channel] :$s");
					}
				}
                $this->main($config);
        }

        function send_data($cmd, $msg = ""){
			fputs($this->socket, $cmd.' '.$msg."\r\n");
        }
}

$bot = new IRCBot($config);
?>
