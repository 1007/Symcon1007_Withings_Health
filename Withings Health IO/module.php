<?php

//******************************************************************************
//	Name		:	Withings Health IO.php
//	Aufruf		:	
//	Info		:	
//
//******************************************************************************

	class WithingsHealthIO extends IPSModule {

		//**************************************************************************
		//
		//**************************************************************************    
		public function Create()
			{
			
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyInteger("Intervall", 3600);
			$this->RegisterTimer("WTH_UpdateTimer", 3600, 'WTH_Update($_IPS["TARGET"]);');

			$this->RegisterPropertyString("AccessToken", "");
			$this->RegisterPropertyString("RefreshToken", "");
			$this->RegisterPropertyString("UserID", "");

			$this->RegisterPropertyString("User", "XXX"); 
			
			$this->RegisterPropertyString("CallbackURL", "");

			$this->RegisterPropertyBoolean("Modulaktiv", true);
			$this->RegisterPropertyBoolean("Notifyaktiv" , false); 
			$this->RegisterPropertyBoolean("Logging", false);
				
		


			

			}

		//**************************************************************************
		//
		//**************************************************************************    
		public function Destroy()
			{

			if (!IPS_InstanceExists($this->InstanceID)) // Instanz wurde eben gelöscht und existiert nicht mehr
				{
				$this->UnregisterHook();
				}
	

			//Never delete this line!
			parent::Destroy();
			
			}

		//**************************************************************************
		//
		//**************************************************************************    
		public function ApplyChanges()
			{

			//Never delete this line!
			parent::ApplyChanges();	

			//Timer stellen
			$interval = $this->ReadPropertyInteger("Intervall") ;
			$this->SetTimerInterval("WTH_UpdateTimer", $interval*1000);
	  

			$runlevel = IPS_GetKernelRunlevel();
			// IPS_LogMessage(__FUNCTION__."[".__LINE__."]"," ".$runlevel);
			if ( $runlevel == KR_READY )
				{
				$this->SubscribeHook();
				}
			else
				{
            	$this->RegisterMessage(0, IPS_KERNELMESSAGE);
				}


			$aktiv = $this->ReadPropertyBoolean("Modulaktiv") ;
			if ( $aktiv == true )	
				$this->SetStatus(102);
			else
				$this->SetStatus(104);	
			
			
			}


		//**************************************************************************
		// Authentifizierung ueber OAuth2 starten
		//**************************************************************************    
		public function Authentifizierung()
			{
			$this->SendDebug(__FUNCTION__."[".__LINE__."]","Starte Webseite zum einloggen bei Withings",0);
			$url = "https://oauth.ipmagic.de/authorize/withings?username=".urlencode(IPS_GetLicensee());
			$this->RegisterOAuth('withingshealth');
			$this->SendDebug(__FUNCTION__."[".__LINE__."]",$url,0);

			return $url;
			}
	
		//******************************************************************************
		//	
		//******************************************************************************
		protected function RegisterOAuth($WebOAuth) 
			{
			// $this->SendDebug(__FUNCTION__,"",0);
			$ids = IPS_GetInstanceListByModuleID("{F99BF07D-CECA-438B-A497-E4B55F139D37}");	// WebOAuth Control
			if(sizeof($ids) > 0) 
				{
				$clientIDs = json_decode(IPS_GetProperty($ids[0], "ClientIDs"), true);
				$found = false;
				foreach($clientIDs as $index => $clientID) 
					{
					if($clientID['ClientID'] == $WebOAuth) 
						{
						if($clientID['TargetID'] == $this->InstanceID)
							return;
						$clientIDs[$index]['TargetID'] = $this->InstanceID;
						$found = true;
						}
					}
				if(!$found) 
					{	
					$clientIDs[] = Array("ClientID" => $WebOAuth, "TargetID" => $this->InstanceID);
					}
				IPS_SetProperty($ids[0], "ClientIDs", json_encode($clientIDs));
				IPS_ApplyChanges($ids[0]);
				}
			}

		//****************************************************************************
		// Wird von OAuth control aufgerufen
		//****************************************************************************
		protected function ProcessOAuthData() 
			{
			if($_SERVER['REQUEST_METHOD'] == "GET") 
				{
				if(!isset($_GET['code']))
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Authorization Code expected", 0);
						die("Authorization Code expected");
					}
				$code = $_GET['code'];  
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "code: ".$code, 0);
				$this->FetchRefreshToken($code);
				} 
			else 
				{	
				echo file_get_contents("php://input");	
				}
			}

		//****************************************************************************
		// Token holen
		//****************************************************************************
		protected function FetchRefreshToken($code) 
			{
   			$this->SendDebug(__FUNCTION__.__LINE__, "Mit Authentication Code Refresh Token holen ! Authentication Code : ".$code, 0);
	   
   			$options = array(
	   						'http' => array(
			   				'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
			   				'method'  => "POST",
			   				'content' => http_build_query(Array("code" => $code))
					   			)
				   			);
   			$context = stream_context_create($options);

   			$result = file_get_contents("https://oauth.ipmagic.de/access_token/withings", false, $context);

   			$this->SendDebug(__FUNCTION__."[".__LINE__."]","Tokens : ".$result,0);
   			$data = json_decode($result);

   			if(!isset($data->token_type) || $data->token_type != "Bearer") 
	   			{
	   			$this->SendDebug(__FUNCTION__."[".__LINE__."]","Bearer Token expected",0 );
	   			return false;
	   			}

   			$token = $data->refresh_token;	
   			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Refresh Token :".$token, 0);
   			IPS_SetProperty($this->InstanceID, "RefreshToken", $token);
   			IPS_ApplyChanges($this->InstanceID);

   			$token = $data->access_token;	
   			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Access Token :".$token, 0);
   			IPS_SetProperty($this->InstanceID, "AccessToken", $token);
   			IPS_ApplyChanges($this->InstanceID);

   			$this->FetchAccessToken($data->access_token, time() + $data->expires_in);
   
   			}

		//******************************************************************************
		//	
		//******************************************************************************
		protected function FetchAccessToken($Token = "", $Expires = 0) 
			{
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Benutze Refresh Token um neuen Access Token zu holen : " . $Token, 0);
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Benutze Refresh Token um neuen Access Token zu holen : " . $this->ReadPropertyString("RefreshToken"),0);
			
			$options = array(
							"http" => array(
							"header" => "Content-Type: application/x-www-form-urlencoded\r\n",
							"method"  => "POST",
							"content" => http_build_query(Array("refresh_token" => $this->ReadPropertyString("RefreshToken")))
									)
							);
							
			$context = stream_context_create($options);

			$result = @file_get_contents("https://oauth.ipmagic.de/access_token/withings", false, $context);
			$data = json_decode($result);

			$this->SendDebug(__FUNCTION__."[".__LINE__."]",$result,0 );

			if(!isset($data->token_type) || $data->token_type != "Bearer") 
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]","Bearer Token expected",0 );
				return false;
				}

			$Expires = time() + $data->expires_in;
				
			//Update Refresh Token wenn vorhanden
			if(isset($data->refresh_token)) 
				{
				$token = $data->refresh_token;		

				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Refresh Token :".$token, 0);
				IPS_SetProperty($this->InstanceID , "RefreshToken", $token);
				IPS_ApplyChanges($this->InstanceID);
				}
				


			$token = $data->access_token;

			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Access Token :".$token, 0);
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Access Token ist gueltig bis ".date("d.m.y H:i:s", $Expires), 0);
		
			IPS_SetProperty($this->InstanceID , "AccessToken", $token);
			IPS_ApplyChanges($this->InstanceID);
		

			$userid = $data->userid;		
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "UserID :".$userid, 0);

			IPS_SetProperty($this->InstanceID , "UserID", $userid);
			IPS_ApplyChanges($this->InstanceID);

			return true;
		
			}


		//**************************************************************************
		// manuelles Refresh der Tokens
		// benutzt gespeichertes RefreshToken um neues Access/Refresh Token zu holen
		//**************************************************************************    
		public function RefreshTokens()
			{
			$status = $this->FetchAccessToken();
			return $status;
			}


	   	//**************************************************************************
		// manuelles Holen der Daten oder ueber Timer
		//**************************************************************************
		public function UpdateIO()
			{

			$this->Update();

			}	



	   	//**************************************************************************
		// manuelles Holen der Daten oder ueber Timer
		//**************************************************************************
		public function Update()
			{
		
			$Text = "Hallo World";

			$this->SendData($Text);

			if ( $this->ReadPropertyBoolean("Modulaktiv") == false )
				{
				return;
				}

			if ( $this->ReadPropertyString("AccessToken") == "" )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]","AccessToken leer" ,0);
				$this->SetStatus(204);	
				return;
				}



			$this->SendDebug(__FUNCTION__."[".__LINE__."]","" ,0);
		

			// $this->GetUser();

			$this->GetDevice();
		
			}	

		//******************************************************************************
		//	Getdevice
		//******************************************************************************
		protected function GetDevice()
			{

			$access_token = $this->ReadPropertyString("AccessToken");

			$url = "https://wbsapi.withings.net/v2/user?action=getdevice&access_token=".$access_token;

			$this->SendDebug(__FUNCTION__."[".__LINE__."]",$url,0);
			//$this->Logging("GetDevice");
			//$this->Logging($url);

			$output = $this->DoCurl($url);

			$this->SendDebug(__FUNCTION__."[".__LINE__."]",$output,0);

			// $this->Logging($output);

			$data = json_decode($output,TRUE); 

			if ( !array_key_exists('status',$data) == TRUE )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]","Status: unbekannt",0);
				return;
				}


			// $id = $this->GetIDForIdent("name");

			// $ModulID = IPS_GetParent($id);

			$DoData = $data['body'];

			// $this->DoDevice($ModulID,$DoData);

			}


		//******************************************************************************
		//	Getuser
		//******************************************************************************
		protected function GetUser()
			{
                $access_token = $this->ReadPropertyString("AccessToken");
				$userid = $this->ReadPropertyString("UserID");

                $url = "https://wbsapi.withings.net/v2/user?action=activate&client_id=".$userid."&access_token=".$access_token;

                $this->SendDebug(__FUNCTION__."[".__LINE__."]", $url, 0);
                //$this->Logging("GetDevice");
                //$this->Logging($url);

                $output = $this->DoCurl($url);

                $this->SendDebug(__FUNCTION__."[".__LINE__."]", $output, 0);
			}
				

		//******************************************************************************
		//	Curl Abfrage ausfuehren
		//******************************************************************************
		function DoCurl($url,$debug=false)
		{

		$debug = true;

		if($debug == true)
			$this->SendDebug(__FUNCTION__."[".__LINE__."]",$url,0);

		$curl = curl_init($url);

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

		$output = curl_exec($curl);

		curl_close($curl);

		if($debug == true)
			$this->SendDebug(__FUNCTION__."[".__LINE__."]",$output,0);
		
		return $output;
				
		}	


		//**************************************************************************
		//
		//**************************************************************************    
		protected function UnregisterTimer($Name)
			{
				return ;
			$id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
			if ($id > 0)
				{
				if (!IPS_EventExists($id)) 
					{
					$this->LogMessage(__FUNCTION__,$this->translate("Timer not exist"));
					// throw new Exception('Timer not present', E_USER_NOTICE);
                	}
				else
					{
					$result = IPS_DeleteEvent($id);
					
					if ( $result == true )
						$this->IPS_LogMessage(__FUNCTION__,$this->translate("Timer deleted"));
					else
						$this->IPS_LogMessage(__FUNCTION__,$this->translate("Timer delete error"));
                    }
				}
			}

	//**************************************************************************
	// Inspired by module SymconTest/HookServe
	//**************************************************************************    
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 
        	{
        	$this->LogMessage("WithingsHealth KR_Ready", KL_MESSAGE);	
            $this->SubscribeHook();
			
        	}
	
	}

	//**************************************************************************
	// Hook Data auswerten
	//**************************************************************************
	protected function ProcessHookData()
		{
            global $_IPS;

            header("HTTP/1.1 200 OK");
            
            http_response_code(200);

            $this->SendDebug(__FUNCTION__."[".__LINE__."]", "Start", 0);
 
		}

	//******************************************************************************
	//	Erstelle Hook
	//******************************************************************************
	protected function SubscribeHook()
		{
		$WebHook = "/hook/WithingsHealth".$this->InstanceID;

		$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
		if (count($ids) > 0) 
			{
			$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
			$found = false;
			foreach ($hooks as $index => $hook) 
				{
				if ($hook['Hook'] == $WebHook) 
					{
					if ($hook['TargetID'] == $this->InstanceID) 
						{
						// $this->SendDebug(__FUNCTION__,"Hook bereits vorhanden : ". $hook['TargetID'], 0);
						return;		// bereits vorhanden
						}
					$hooks[$index]['TargetID'] = $this->InstanceID;
					$found = true;
					}
				}
				
				if (!$found) 
					{
					$hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
					}
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $WebHook ." erstellt" , 0);
				IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}

		//******************************************************************************
		// Hook loeschen
		//******************************************************************************
		protected function UnregisterHook()
			{
			$WebHook = "/hook/WithingsHealth".$this->InstanceID;

			$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
			if (count($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
				$found = false;
				foreach ($hooks as $index => $hook) {
					if ($hook['Hook'] == $WebHook) {
						$found = $index;
						break;
					}
				}
	
				if ($found !== false) {
					array_splice($hooks, $index, 1);
					IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
					IPS_ApplyChanges($ids[0]);
				}
			}
		}


		protected function SendData(string $Text)
			{
			// IPS_LogMessage("Device SEND",$Text);
			$this->SendDataToChildren(json_encode(Array("DataID" => "{33C19E7A-3386-09D7-DF5D-FE75EE51FF09}", "Buffer" => $Text)));

			$Text = '{"type":"Blood Pressure Monitor","battery":"medium","model":"Withings Blood Pressure Monitor V2","model_id":42,"timezone":"Europe\/Berlin","last_session_date":1572807461,"deviceid":"19f734a3ed8be9124ca5ce14a99701c1a7a453c1"},{"type":"Scale","battery":"medium","model":"Body Cardio","model_id":6,"timezone":"Europe\/Berlin","last_session_date":1596946581,"deviceid":"c7f35d19634ecb4f273ec42a4f4915b74315b2a9"},{"type":"Sleep Monitor","battery":"high","model":"Aura Sensor V2","model_id":63,"timezone":"Europe\/Berlin","last_session_date":1596953885,"deviceid":"bb9ce8701afe8d39fe17a8490bd90fc6738db872"},{"type":"Smart Connected Thermometer","battery":"medium","model":"Thermo","model_id":70,"timezone":"Europe\/Berlin","last_session_date":1596947710,"deviceid":"87f6241522588affc3b734842fcf208b24b3fee1"},{"type":"Blood Pressure Monitor","battery":"high","model":"BPM Core","model_id":44,"timezone":"Europe\/Berlin","last_session_date":1596947798,"deviceid":"261dc788ba02c62ddaad86c9c2867246750688cc"}';	
			$this->SendDataToChildren(json_encode(Array("DataID" => "{A2756D8B-6F20-42A3-AD09-795AD631190C}", "Buffer" => $Text)));


			}




		//******************************************************************************
		//	Konfigurationsformular dynamisch erstellen
		//******************************************************************************
		public function GetConfigurationForm() 
			{	
				$userid = $this->ReadPropertyString("UserID");

				$form = '
				
				{
					"elements":
					[
				  
					  { "type": "Label"             , "label":  "Withings Health IO V1#1" },
						  
					  { "type": "CheckBox"          , "name" :  "Modulaktiv",  "caption": "Modul aktiv" },
				   
					  { "type": "Label"             , "label":  "UserID : '.$userid.'"  },
				  

					  { "type": "IntervalBox"       , "name" :  "Intervall", "caption": "Sekunden" },

					  { "type": "CheckBox"      , "name"  : "Notifyaktiv" , "caption": "Benachrichtigungen aktivieren" },

					  { "type": "Label", "caption": "Manuelle Eingabe einer Callback Adresse fuer Benachrichtigungen" },
					  { "type": "ValidationTextBox", "name": "CallbackURL", "caption": "CallbackURL" }

					  
				  
				  
				  
				  
					],
					
					"actions":
					[  
					  { "type": "Button", "label": "Request Authentification", 	"width": "250px", 	"onClick": "echo WTH_Authentifizierung($id);" },
					  { "type": "Button", "label": "Update Data", 				"width": "250px", 	"onClick": "WTH_Update($id);" },
					  { "type": "Button", "label": "Refresh Tokens", 			"width": "250px",	"onClick": "WTH_RefreshTokens($id);" }
				  
					],
				  
				  
					"status":
					  [
						  { "code": 101, "icon": "active", "caption": "Withings Health wird erstellt..." },
						  { "code": 102, "icon": "active", "caption": "Withings Health ist aktiv" },
						  { "code": 104, "icon": "inactive", "caption": "Withings Health ist inaktiv" },
						  { "code": 202, "icon": "error",  "caption": "Userdaten falsch" },
						  { "code": 203, "icon": "error",  "caption": "AuthentificationCode falsch" },
						  { "code": 204, "icon": "error",  "caption": "Token falsch oder nicht vorhanden" },
						  { "code": 293, "icon": "error",  "caption": "Callback URL nicht akzeptiert" }
						  
						  
					  ]
				  
				  
				  
				  }
				
				';

                return $form;
            }




		public function ForwardData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("IO FRWD", utf8_decode($data->Buffer));
			IPS_LogMessage("IO FRWD", $JSONString);

			$Text = "hallo";
			$this->SendDataToChildren(json_encode(Array("DataID" => "{33C19E7A-3386-09D7-DF5D-FE75EE51FF09}", "Buffer" => $Text)));

		}




		public function Send(string $Text)
		{
			$this->SendDataToChildren(json_encode(Array("DataID" => "{33C19E7A-3386-09D7-DF5D-FE75EE51FF09}", "Buffer" => $Text)));
		}

	}