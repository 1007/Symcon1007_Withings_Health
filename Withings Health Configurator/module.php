<?php
	class WithingsHealthKonfigurator extends IPSModule {

		public function Create()
			{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("Devices", "");	

			$this->ConnectParent("{F618B14A-D8D1-1CD1-9B82-B81A547E922E}");		// IO


			}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}


	   	//**************************************************************************
		// manuelles Holen der Devices
		//**************************************************************************
		public function UpdateDevices()
			{
			
			
			}
			
		//******************************************************************************
		// Empfange Daten von Instance 
		//******************************************************************************
		public function ReceiveData($JSONString)
			{
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $JSONString , 0);
			
			$data = json_decode($JSONString);

			$buffer = utf8_decode($data->Buffer);
			$dataid = utf8_decode($data->DataID);	

			if ( $dataid == "{A2756D8B-6F20-42A3-AD09-795AD631190C}" )		// Empfang von IO
				{
					
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $buffer, 0);
				
				$devices = json_decode($buffer,TRUE);

				if ( isset ($devices['status']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "status not found" , 0);
					return;
					}
				if ( $devices['status'] !=0 )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "status not ok" , 0);
					return;
					}
				if ( isset ($devices['body']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "body not found" , 0);
					return;
					}

				$body = $devices['body'];
				$buffer = json_encode($body);	
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $buffer , 0);


				IPS_SetProperty($this->InstanceID , "Devices", $buffer);
				IPS_ApplyChanges($this->InstanceID);

				$this->DecodeDeviceData($buffer);
				
				}
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "" , 0);	
			}

		//******************************************************************************
		//	Docodiere Geraeteliste 
		//******************************************************************************
		protected function DecodeDeviceData($data)
			{
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "" , 0);

			$devices = json_decode($data,TRUE);
			if (isset($devices['devices']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Devices not found", 0);
				return;
				}

			$devices = $devices['devices'];
			
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Geraeteanzahl : " . count($devices), 0);
			
			foreach($devices as $device)
				{

				if(isset($device['type'])) 	$devicetype = $device['type']; else $devicetype = "?"; 
				if(isset($device['model'])) $devicemodel = $device['model']; else $devicemodel = "?"; 
				if(isset($device['model_id'])) $devicemodelid = $device['model_id']; else $devicemodelid = "?"; 
				if(isset($device['timezone'])) $devicetimezone = $device['timezone']; else $devicetimezone = "?"; 
				if(isset($device['last_session_date'])) $devicedate = $device['last_session_date']; else $devicedate = 0; 
				if(isset($device['deviceid'])) $deviceid = $device['deviceid']; else $deviceid = "?"; 

				$devicedatestring = $this->TimestampToString($devicedate);

				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "", 0);	
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicetype, 0);	
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicemodel, 0);	
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicemodelid, 0);	
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicetimezone, 0);	
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicedatestring, 0);
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", $deviceid, 0);	

				}	
				
			}	

		//******************************************************************************
		//	Unixtimestamp wandeln	
		//******************************************************************************
		protected function TimestampToString($timestamp)
			{
			return date('d.m.Y H:i:s',$timestamp);
			}
	

		//******************************************************************************
		//	Sende Daten an IO
		//******************************************************************************
		public function Send(string $command)
			{
			$this->SendDataToParent(json_encode(Array("DataID" => "{72C77EA7-F69C-70CE-30C0-8F33179E7BE6}", "Buffer" => $command)));
			}

		//******************************************************************************
		//	Get Saved Devices
		//******************************************************************************
		protected function GetSavedDevices() 
			{

			$devices = $this->ReadPropertyString("Devices") ;

			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $devices, 0);
			
			return $devices;

            }	




		//******************************************************************************
		//	Konfigurationsformular dynamisch erstellen
		//******************************************************************************
		public function GetConfigurationForm() 
			{	

			$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

			return json_encode($Form);
				
			return;
				$form = '
				
				{
					"elements": 
						[
							{
							"name": "RootId",
							"type": "SelectCategory",
							"caption": "Create under category"
							}
						],

					"actions": 
						[

							{
							"type": "Configurator",
							"name": "Configuration",
							"caption": "Configuration",
							"delete": true,
							"values": 
								[
									{
									"id": 1,
									"name": "Kategorie",
									"address": "W"
									},
									{
									"parent": 1,
									"name": "Rechenmodul - Minimum",
									"address": "2",
									"create": 
										{
										"moduleID": "{A7B0B43B-BEB0-4452-B55E-CD8A9A56B052}",
										"configuration": 
											{
											"Calculation": 2,
											"Variables": "[]"
											}
									}
							},
							{
							"parent": 1,
							"name": "Rechenmodul im Wohnzimmer",
							"address": "2",
							"create": {
								"moduleID": "{A7B0B43B-BEB0-4452-B55E-CD8A9A56B052}",
								"configuration": {
									"Calculation": 2,
									"Variables": "[]"
								},
								"location": [
									"Erdgeschoss",
									"Wohnzimmer"
								]
							}
						},{
							"parent": 1,
							"instanceID": 53398,
							"name": "Fehlerhafte Instanz",
							"address": "4"
						},{
							"parent": 1,
							"name": "Rechenmodul - Auswahl",
							"address": "2",
							"create": {
								"Maximum": {
									"moduleID": "{A7B0B43B-BEB0-4452-B55E-CD8A9A56B052}",
									"configuration": {
										"Calculation": 3,
										"Variables": "[]"
									}
								},
								"Average": {
									"moduleID": "{A7B0B43B-BEB0-4452-B55E-CD8A9A56B052}",
									"configuration": {
										"Calculation": 4,
										"Variables": "[]"
									}
								}
							}
						}, {
							"parent": 1,
							"name": "OZW772 IP-Interface",
							"address": "00:A0:03:FD:14:BB",
							"create": [
								{ 
									"moduleID": "{33765ABB-CFA5-40AA-89C0-A7CEA89CFE7A}",
									"configuration": {}
								},
								{
									"moduleID": "{1C902193-B044-43B8-9433-419F09C641B8}",
									"configuration": {
										"GatewayMode":1
									}
								},
								{
									"moduleID": "{82347F20-F541-41E1-AC5B-A636FD3AE2D8}",
									"configuration": {
										"Host":"172.17.31.95",
										"Port":3671,
										"Open":true
									}
								}
							]
						}
					]
				}
				]
			}
				';

                return $form;
            }






	}