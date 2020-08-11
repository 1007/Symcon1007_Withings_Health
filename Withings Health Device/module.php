<?php
	class WithingsHealthDevice extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyBoolean("Modulaktiv", true);
			$this->RegisterPropertyBoolean("Logging", false);
			$this->RegisterPropertyString("Meas", "");

			$this->RegisterPropertyBoolean("ShowMoreDebug", false);
			
			
			$this->RegisterPropertyString("devicetype", "");
			$this->RegisterPropertyString("devicemodel", "");
			$this->RegisterPropertyInteger("devicemodelid", 0);
			$this->RegisterPropertyString("devicetimezone", "");
			$this->RegisterPropertyString("devicedatestring", "");
			$this->RegisterPropertyString("deviceid", "");

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

			$this->RegisterAllProfile();

		}




	//******************************************************************************
	// Register alle Profile
	//******************************************************************************
	protected function RegisterAllProfile()
		{
		
		$this->RegisterProfile(1,"WITHINGS_M_Groesse"  ,"Gauge"  ,""," cm");
		$this->RegisterProfile(1,"WITHINGS_M_Puls"     ,"Graph"  ,""," bpm");
		$this->RegisterProfile(1,"WITHINGS_M_Atmung"   ,"Graph"  ,""," Atemzuege/Minute");
		$this->RegisterProfile(2,"WITHINGS_M_Kilo"     ,""       ,""," kg",false,false,false,1);
		$this->RegisterProfile(2,"WITHINGS_M_Prozent"  ,""       ,""," %",false,false,false,1);
		$this->RegisterProfile(2,"WITHINGS_M_BMI"      ,""       ,""," kg/m²",false,false,false,1);
		$this->RegisterProfile(1,"WITHINGS_M_Blutdruck","",""," mmHg");
		$this->RegisterProfile(2,"WITHINGS_M_VO2","",""," ml/min/kg");

		

		$this->RegisterProfileGender("WITHINGS_M_Gender", "", "", "", Array(
									Array(0, "maennlich",  "", 0x0000FF),
									Array(1, "weiblich",   "", 0xFF0000)
									));

		$this->RegisterProfileBatterie("WITHINGS_M_Batterie", "", "", "", Array(
									Array(0, "Schwach < 30%",  "", 0xFF0000),
									Array(1, "Mittel > 30%",   "", 0xFFFF00),
									Array(2, "Gut > 75%",      "", 0x00FF00)
									));

		$this->RegisterProfile(1,"WITHINGS_M_Minuten","",""," Minuten");

		$this->RegisterProfile(1,"WITHINGS_M_Schritte","",""," Schritte");
		$this->RegisterProfile(1,"WITHINGS_M_Anzahl","","","");
	
		$this->RegisterProfile(2,"WITHINGS_M_Kalorien","",""," kcal",false,false,false,2);
		$this->RegisterProfile(2,"WITHINGS_M_Meter","",""," Meter",false,false,false,2);
							 
		}

	//**************************************************************************
	//  0 - Bool
	//  1 - Integer
	//  2 - Float
	//  3 - String
	//**************************************************************************    
	protected function RegisterProfile($Typ, $Name, $Icon, $Prefix, $Suffix, $MinValue=false, $MaxValue=false, $StepSize=false, $Digits=0) 
		{
		if(!IPS_VariableProfileExists($Name)) 
			{
			IPS_CreateVariableProfile($Name, $Typ);  
			} 
		else 
			{
			$profile = IPS_GetVariableProfile($Name);
			if($profile['ProfileType'] != $Typ)
				{
				IPS_Logmessage("Withingsmodul","Profil falsch : " . $Name);
				//throw new Exception("Variable profile type does not match for profile ".$Name);

				}
			}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
 
		if ( $Typ == 2 )
			IPS_SetVariableProfileDigits($Name, $Digits);
		}

	//**************************************************************************
	//
	//**************************************************************************    
	protected function RegisterProfileGender($Name, $Icon, $Prefix, $Suffix, $Associations) 
		{
		if ( sizeof($Associations) === 0 )
			{
			$MinValue = 0;
			$MaxValue = 0;
			}
		else 
			{
			$MinValue = $Associations[0][0];
			$MaxValue = $Associations[sizeof($Associations)-1][0];
			}

		$this->RegisterProfile(1,$Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

		foreach($Associations as $Association) 
			{
			IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}

		}

	//**************************************************************************
	// 
	//**************************************************************************    
	protected function RegisterProfileBatterie($Name, $Icon, $Prefix, $Suffix, $Associations) 
		{
		if ( sizeof($Associations) === 0 )
			{
			$MinValue = 0;
			$MaxValue = 0;
			}
		else 
			{
			$MinValue = $Associations[0][0];
			$MaxValue = $Associations[sizeof($Associations)-1][0];
			}

		$this->RegisterProfile(1,$Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

		foreach($Associations as $Association) 
			{
			IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}

		}



		//******************************************************************************
		// Sende Daten zu Instance 
		//******************************************************************************
		public function Send(string $Text)
			{
			$this->SendDataToParent(json_encode(Array("DataID" => "{72C77EA7-F69C-70CE-30C0-8F33179E7BE6}", "Buffer" => $Text)));
			}

		//******************************************************************************
		// Empfange Daten von Instance 
		//******************************************************************************
		public function ReceiveData($JSONString)
			{

			$moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");
	
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $JSONString , 0);
			
			$data = json_decode($JSONString);

			$buffer = utf8_decode($data->Buffer);
			$dataid = utf8_decode($data->DataID);	

			if ( $dataid == "{33C19E7A-3386-09D7-DF5D-FE75EE51FF09}")		// Empfang von IO
				{
				if ( $moreDebug == TRUE )	
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", $buffer, 0);
				
				
				$buffer = json_decode($buffer,TRUE);

				if ( isset ($buffer['status']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "status not found" , 0);
					return;
					}
				if ( $buffer['status'] !=0 )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "status not ok" , 0);
					return;
					}
				if ( isset ($buffer['body']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "body not found" , 0);
					return;
					}

				$body = $buffer['body'];
				$buffer = json_encode($body);
				if ( $moreDebug == TRUE )	
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", $buffer , 0);


				// Devices erhalten
				if ( isset($body['devices']) == true )			
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Get Devices" , 0);
					$this->DecodeDeviceData($buffer);	
					}

				// Meas erhalten	
				if ( isset($body['updatetime']) == true )		
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Get Meas" , 0);
					//IPS_SetProperty($this->InstanceID , "Meas", $buffer);
					//IPS_ApplyChanges($this->InstanceID);
					$this->DecodeMeasData($buffer);	
			
					}

				// Sleep erhalten ?	
				if ( isset($body['series']) == true )		
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Get Sleep ?" , 0);
					
					$this->DecodeSleepData($buffer);	
			
					}

					

				}
				
				
	
			}

		//******************************************************************************
		//	Docodiere Sleep Messungenliste 
		//******************************************************************************
		protected function DecodeSleepData($data)
			{
            $moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");
    
            $this->SendDebug(__FUNCTION__."[".__LINE__."]", $data, 0);

			$meas = json_decode($data,TRUE);
			if (isset($meas['series']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "series not found", 0);
				return;
				}
	
			$series = $meas['series'];
			
			if (isset($series[0]) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "series empty - no sleep monitor", 0);
				return;
				}
	
			if (isset($series[0]['model_id']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "model_id not found - no sleep monitor", 0);
				return;
				}
	
			$this->DecodeSleepSeries($series);		


			}
			
		//******************************************************************************
		//	Docodiere Sleep Series 
		//******************************************************************************
		protected function DecodeSleepSeries($series)
			{
			
			$moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");

			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "", 0);


			foreach ( $series as $serie )
				{

				if ( isset($serie['model']) == FALSE )
					continue;	

				$model = $serie['model'];
				
				if ( $model == 32 or $model == 16 )
					{
					// $this->SendDebug(__FUNCTION__."[".__LINE__."]", "Korrekt Model ID : ".$model , 0);


					}
				else
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Model ID NOK : ".$model , 0);

                    continue;
                    }	

				$startdate = 0;
				$enddate = 0;	
				if ( isset($serie['startdate']))
					$startdate = $serie['startdate'];	
				if ( isset($serie['enddate']))
					$enddate = $serie['enddate'];	

				$startdatestring = $this->TimestampToString($startdate);	
				$enddatestring = $this->TimestampToString($enddate);	

				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "[".$startdatestring."][".$enddatestring."]" , 0);

				if ( isset($serie['data']))
					$data = $serie['data'];	
				
				if ( isset($data) )
					$this->DecodeSingleSleepData($data,$enddatestring,$enddate);	
					

				}


			}

		//******************************************************************************
		//	Docodiere SingleSleepData 
		//******************************************************************************
		protected function DecodeSingleSleepData($data,$enddatestring,$enddate)
			{
			
			$moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");
			
			$this->SendDebug(__FUNCTION__."[".__LINE__."]","", 0);

			if ( isset($data['wakeupduration']) ) $wakeupduration = $data['wakeupduration']; else $wakeupduration = false;   	
			if ( isset($data['lightsleepduration']) ) $lightsleepduration = $data['lightsleepduration']; else $lightsleepduration = false;   	
			if ( isset($data['deepsleepduration']) ) $deepsleepduration = $data['deepsleepduration']; else $deepsleepduration = false;   	
			if ( isset($data['wakeupcount']) ) $wakeupcount = $data['wakeupcount']; else $wakeupcount = false;   	
			if ( isset($data['durationtosleep']) ) $durationtosleep = $data['durationtosleep']; else $durationtosleep = false;   	
			if ( isset($data['remsleepduration']) ) $remsleepduration = $data['remsleepduration']; else $remsleepduration = false;   	
			if ( isset($data['durationtowakeup']) ) $durationtowakeup = $data['durationtowakeup']; else $durationtowakeup = false;   	
			if ( isset($data['hr_average']) ) $hr_average = $data['hr_average']; else $hr_average = false;   	
			if ( isset($data['hr_min']) ) $hr_min = $data['hr_min']; else $hr_min = false;   	
			if ( isset($data['hr_max']) ) $hr_max = $data['hr_max']; else $hr_max = false;   	
			if ( isset($data['rr_average']) ) $rr_average = $data['rr_average']; else $rr_average = false;   	
			if ( isset($data['rr_min']) ) $rr_min = $data['rr_min']; else $rr_min = false;   	
			if ( isset($data['rr_max']) ) $rr_max = $data['rr_max']; else $rr_max = false;   	
			
			if ($moreDebug == true) 
				{
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "wakeupduration : ". $wakeupduration, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "lightsleepduration : ". $lightsleepduration, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "deepsleepduration : ". $deepsleepduration, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "wakeupcount : ". $wakeupcount, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "durationtosleep : ". $durationtosleep, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "remsleepduration : ". $remsleepduration, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "durationtowakeup : ". $durationtowakeup, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "hr_average : ". $hr_average, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "hr_min : ". $hr_min, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "hr_max : ". $hr_max, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "rr_average : ". $rr_average, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "rr_min : ". $rr_min, 0);
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", "rr_max : ". $rr_max, 0);
            	}	

			
			// $typearray = $this->DecodeMeasureTypeSleepName($wakeupduration);	

			$this->HandleSingleSleep($enddatestring,$wakeupduration/60,"wakeupduration",$enddate);
			$this->HandleSingleSleep($enddatestring,$lightsleepduration/60,"lightsleepduration",$enddate);
			$this->HandleSingleSleep($enddatestring,$deepsleepduration/60,"deepsleepduration",$enddate);
			$this->HandleSingleSleep($enddatestring,$wakeupcount,"wakeupcount",$enddate);
			$this->HandleSingleSleep($enddatestring,$durationtosleep/60,"durationtosleep",$enddate);
			$this->HandleSingleSleep($enddatestring,$remsleepduration/60,"remsleepduration",$enddate);
			$this->HandleSingleSleep($enddatestring,$durationtowakeup/60,"durationtowakeup",$enddate);
			$this->HandleSingleSleep($enddatestring,$hr_average,"hr_average",$enddate);
			$this->HandleSingleSleep($enddatestring,$hr_min,"hr_min",$enddate);
			$this->HandleSingleSleep($enddatestring,$hr_max,"hr_max",$enddate);
			$this->HandleSingleSleep($enddatestring,$rr_average,"rr_average",$enddate);
			$this->HandleSingleSleep($enddatestring,$rr_min,"rr_min",$enddate);
			$this->HandleSingleSleep($enddatestring,$rr_max,"rr_max",$enddate);
			
			}
			



		//******************************************************************************
		//	Docodiere Messungenliste 
		//******************************************************************************
		protected function DecodeMeasData($data)
			{

			$moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");
	
            $this->SendDebug(__FUNCTION__."[".__LINE__."]", $data, 0);

			$meas = json_decode($data,TRUE);
			if (isset($meas['updatetime']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Meas not found", 0);
				return;
				}

			$updatetime =$this->TimestampToString($meas['updatetime']);	
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Updatetime : ".$updatetime, 0);	

			if (isset($meas['timezone']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Meas timezone not found", 0);
				return;
				}

			$timezone = $meas['timezone'];	
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Timezone : ".$timezone, 0);	
			
			if (isset($meas['measuregrps']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Meas measuregrps not found", 0);
				return;
				}

			$count = count($meas['measuregrps']);
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Anzahl measuregrps :" . $count, 0);
			
			

			$measuregrps = $meas['measuregrps'];
			// Neueste nach hinten
			$measuregrps = array_reverse ( $measuregrps  );


			foreach($measuregrps as $grp )
				{
			
				if (isset($grp['deviceid']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "DeviceID not found in Measures", 0);
					continue;
					}
						
				$localDeviceID = $this->GetDeviceID();	
				$deviceid = $grp['deviceid'];	

				if ( $localDeviceID != $deviceid )
					continue;

				if (isset($grp['date']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Date not found in Measures", 0);
					continue;
					}

				$date = $this->TimestampToString($grp['date']);	
				$timestamp = $grp['date'];

				if (isset($grp['created']) == false )
					{
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Created not found in Measures", 0);
					continue;
					}

				$created = $this->TimestampToString($grp['created']);	
			

				// $this->SendDebug(__FUNCTION__."[".__LINE__."]", "Deviceid : [" . $date . "][" . $created . "]", 0);


				if (isset($grp['attrib']) == false )
					{	
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Attribute not found in Measures", 0);
					continue;
					}

				$attribute = $grp['attrib'];	
				$AttributeString = $this->DecodeAttributeData($attribute);
				if ( $moreDebug == true )
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Attribute :" . $AttributeString, 0);


				if (isset($grp['measures']) == false )
					{	
					$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Measures not found in Measures", 0);
					continue;
					}

				$measures = $grp['measures'];	

				$this->DecodeMeasuresData($date,$measures,$timestamp);

				}	



			}

		//******************************************************************************
		//	Docodiere Messungen 
		//******************************************************************************
		protected function DecodeMeasuresData($date,$measures,$timestamp)
			{

			$moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");

			// Alle Messungen durchgehen
			foreach ( $measures as $measure )
				{

				$value 	= false;
				$type  	= false;
				$unit	= false; 	
				$algo	= false;	// veraltet
				$fm 	= false;	// veraltet
				
				if ( isset($measure['value']) == TRUE )
					$value = $measure['value'];
				if ( isset($measure['type']) == TRUE )
					$type = $measure['type'];
				if ( isset($measure['unit']) == TRUE )
					$unit = $measure['unit'];
				if ( isset($measure['algo']) == TRUE )
					$algo = $measure['algo'];
				if ( isset($measure['fm']) == TRUE )
					$fm = $measure['fm'];

				$this->HandleSingleMeasure($date,$value,$type,$unit,$timestamp);	
				
				}

			}
		
		//******************************************************************************
		//	Handle einzelne Messung 
		//******************************************************************************
		protected function HandleSingleMeasure($date,$value,$type,$unit,$timestamp)
			{

			$value = $this->DecodeMeasureUnit($value,$unit);
			$typearray = $this->DecodeMeasureTypeSleepName($type);	
			$s = $date . " - " . $value . " - " . $type ." - " . $unit . " - " .$typearray['desc'] ;
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $s, 0);

			if ($typearray['vartyp'] == false )
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Vartype ist FALSE", 0);
			else
				$this->SetValueToVariable($date,$value,$typearray,$timestamp);	


			}

		//******************************************************************************
		//	Handle einzelne Sleep 
		//******************************************************************************
		protected function HandleSingleSleep($date,$value,$type,$timestamp)
			{

			$typearray = $this->DecodeMeasureTypeSleepName($type);	
			$s = $date . " - " . $value . " - " . $type ."  - " .$typearray['desc'] ;
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $s, 0);

			if ($typearray['vartyp'] == false )
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Vartype ist FALSE", 0);
			else
				$this->SetValueToVariable($date,$value,$typearray,$timestamp);	


			}


		//******************************************************************************
		//
		//******************************************************************************
		protected function SetValueToVariable($date,$value,$typearray,$timestamp)
			{

			$VariableIdent = $typearray['ident'];
			
			// Teste ob Variable bereits vorhanden
			$id = @$this->GetIDForIdent($VariableIdent);

			if ( $id == TRUE )
				{
				// $this->SendDebug(__FUNCTION__."[".__LINE__."]", "Variable vorhanden : " .$VariableIdent, 0);

				}
			else
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Variable nicht vorhanden", 0);


				// Variable erstellen
				$VariableTyp = $typearray['vartyp'];
				$VariableProfil = $typearray['profil'];
				$VariableName = $typearray['name'];

				if ( $VariableTyp == 0 )
					$VariableID = $this->RegisterVariableBoolean( $VariableIdent, $VariableName,$VariableProfil,0);
				if ( $VariableTyp == 1 )
					$VariableID = $this->RegisterVariableInteger( $VariableIdent, $VariableName,$VariableProfil,0);
				if ( $VariableTyp == 2 )
					$VariableID = $this->RegisterVariableFloat( $VariableIdent, $VariableName,$VariableProfil,0);
				if ( $VariableTyp == 3 )
					$VariableID = $this->RegisterVariableString( $VariableIdent, $VariableName,$VariableProfil,0);


				}


			// Teste ob Variable jetzt vorhanden
			$id = $this->GetIDForIdent($VariableIdent);
	
			if ( $id == true )
				{


				// letzte Aktualisierung
				$array = IPS_GetVariable ($id);	
				$varUpdate = $array['VariableUpdated'];	

				if ($timestamp > $varUpdate ) 
					{
                    $this->SetValue($VariableIdent, $value);
					}
				else
					{

					// $this->SendDebug(__FUNCTION__."[".__LINE__."]", "Variablenupdate nicht noetig" .$date . " - " , 0);
		
					}
				
				}	


			}	
				

		//******************************************************************************
		//	Docodiere Messtype 
		// 
		// 	Variabletypen	:
		// 						0		-	Boolean
		// 						1		-	Integer
		// 						2		-	Float
		// 						3		-	String
		//						name 	-	Sleep
		//******************************************************************************
		protected function DecodeMeasureTypeSleepName($type)
			{
            $result =array();

			switch ($type) 
				{

                case 1:
                            $result['ident'] 	= "weight";
							$result['desc']		= "Gewicht (kg)";
							$result['name']		= $this->translate("Weight") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Kilo";
                            break;

				case 4:
							$result['ident'] 	= "height";
							$result['desc']		= "Groesse (meter)";
							$result['name']		= $this->translate("height") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Groesse";
							break;
				case 5:
                            $result['ident'] 	= "fatfree";
							$result['desc']		= "Fettfrei Anteil (kg)";
							$result['name']		= $this->translate("Fat Free Mass") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Kilo";
                            break;

				case 6:
                            $result['ident'] 	= "fatradio";
							$result['desc']		= "Fett Prozent (%)";
							$result['name']		= $this->translate("Fat Ratio") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Prozent";
                            break;
							
				case 8:
							$result['ident'] 	= "fatmassweight";
							$result['desc']		= "Fett Anteil (kg)";
							$result['name']		= $this->translate("Fat Mass Weight") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Kilo";
							break;

				case 9:
							$result['ident'] 	= "diastolicblood";
							$result['desc']		= "Diastolic Blutdruck (mmHG)";
							$result['name']		= $this->translate("Diastolic Blood Pressure") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Blutdruck";
							break;

				case 10:
							$result['ident'] 	= "systolicblood";
							$result['desc']		= "Systolic Blutdruck (mmHG)";
							$result['name']		= $this->translate("Systolic Blood Pressure") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Blutdruck";
							break;
					
				case 11:
							$result['ident'] 	= "heartpulse";
							$result['desc']		= "Puls (bpm)";
							$result['name']		= $this->translate("Heart Pulse") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Puls";
							break;

				case 12:
							$result['ident'] 	= "temperatur";
							$result['desc']		= "Temperatur (Celsius)";
							$result['name']		= $this->translate("Temperature") ;
							$result['vartyp']	= 2;
							$result['profil']	= "~Temperature";
							break;

				case 54:
							$result['ident'] 	= "spo2";
							$result['desc']		= "Sauerstoffsaettigung (%)";
							$result['name']		= $this->translate("SPO2") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Prozent";
							break;
			
				case 71:
							$result['ident'] 	= "koerpertemperatur";
							$result['desc']		= "Koerpertemperatur (Celsius)";
							$result['name']		= $this->translate("Body Temperature") ;
							$result['vartyp']	= 2;
							$result['profil']	= "~Temperature";
							break;
								
				case 73:
							$result['ident'] 	= "hauttemperatur";
							$result['desc']		= "Hauttemperatur (Celsius)";
							$result['name']		= $this->translate("Skin Temperature") ;
							$result['vartyp']	= 2;
							$result['profil']	= "~Temperature";
							break;
	
				case 76:
							$result['ident'] 	= "muskelmasse";
							$result['desc']		= "Muskelmasse (kg)";
							$result['name']		= $this->translate("Muscle Mass") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Kilo";
							break;

				case 77:
							$result['ident'] 	= "wasseranteil";
							$result['desc']		= "Wasseranteil (kg)";
							$result['name']		= $this->translate("Hydration") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Kilo";
							break;

				case 88:
							$result['ident'] 	= "bonemass";
							$result['desc']		= "Knochenanteil (kg)";
							$result['name']		= $this->translate("Bone Mass") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_Kilo";
							break;

				case 91:
							$result['ident'] 	= "pulsewave";
							$result['desc']		= "Pulswellengeschwindigkeit (m/s)";
							$result['name']		= $this->translate("Pulse Wave Velocity") ;
							$result['vartyp']	= 2;
							$result['profil']	= "~WindSpeed.ms";
							break;

				case 123:
							$result['ident'] 	= "vo2max";
							$result['desc']		= "Maximale Sauerstoffaufnahme (ml/min/kg)";
							$result['name']		= $this->translate("VO2max") ;
							$result['vartyp']	= 2;
							$result['profil']	= "WITHINGS_M_VO2";
							break;
								

				case "wakeupduration":
							$result['ident'] 	= "wachphasen";
							$result['desc']		= "Time spent awake";
							$result['name']		= $this->translate("Time spent awake") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Minuten";
							break;

				case "lightsleepduration":
							$result['ident'] 	= "leichschlafphasen";
							$result['desc']		= "Duration in state light sleep";
							$result['name']		= $this->translate("Duration in state light sleep") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Minuten";
							break;
								
				case "deepsleepduration":
							$result['ident'] 	= "tiefschlafphasen";
							$result['desc']		= "Duration in state deep sleep";
							$result['name']		= $this->translate("Duration in state deep sleep") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Minuten";
							break;

				case "wakeupcount":
							$result['ident'] 	= "schlafunterbrechungen";
							$result['desc']		= "Number of times the user woke up";
							$result['name']		= $this->translate("Number of times the user woke up") ;
							$result['vartyp']	= 1;
							$result['profil']	= "";
							break;

				case "durationtosleep":
							$result['ident'] 	= "einschlafzeit";
							$result['desc']		= "Time to sleep";
							$result['name']		= $this->translate("Time to sleep") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Minuten";
							break;
								
				case "remsleepduration":
							$result['ident'] 	= "remschlafphasen";
							$result['desc']		= "Duration in state REM sleep";
							$result['name']		= $this->translate("Duration in state REM sleep") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Minuten";
							break;

				case "durationtowakeup":
							$result['ident'] 	= "aufstehzeit";
							$result['desc']		= "Time to wake up";
							$result['name']		= $this->translate("Time to wake up") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Minuten";
							break;


				case "hr_average":
							$result['ident'] 	= "herzschlagdurchschnitt";
							$result['desc']		= "Average heart rate";
							$result['name']		= $this->translate("Average heart rate") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Puls";
							break;
								
				case "hr_min":
							$result['ident'] 	= "herzschlagminimal";
							$result['desc']		= "Minimal heart rate";
							$result['name']		= $this->translate("Minimal heart rate") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Puls";
							break;
								
				case "hr_max":
							$result['ident'] 	= "herzschlagmaximal";
							$result['desc']		= "Maximal heart rate";
							$result['name']		= $this->translate("Maximal heart rate") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Puls";
							break;

				case "rr_average":
							$result['ident'] 	= "atemzuegedurchschnitt";
							$result['desc']		= "Average respiration rate";
							$result['name']		= $this->translate("Average respiration rate") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Atmung";
							break;
									
				case "rr_min":
							$result['ident'] 	= "atemzuegeminimal";
							$result['desc']		= "Minimal respiration rate";
							$result['name']		= $this->translate("Minimal respiration rate") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Atmung";
							break;
									
				case "rr_max":
							$result['ident'] 	= "atemzuegemaximal";
							$result['desc']		= "Maximal respiration rate";
							$result['name']		= $this->translate("Maximal respiration rate") ;
							$result['vartyp']	= 1;
							$result['profil']	= "WITHINGS_M_Atmung";
							break;
								

                default:
                            $result['ident'] 	= "";
                            $result['desc']		= "";
							$result['name']		= $this->translate("") ;
							$result['vartyp']	= false;
							$result['profil']	= "";
                            break;

				}
				
			return $result;

            }	
			

		//******************************************************************************
		//	Docodiere Masseinheit 
		//******************************************************************************
		protected function DecodeMeasureUnit($value,$unit)
			{
				
			$val = floatval ( $value ) * floatval ( "1e".$unit );

			return $val;	

            }	

		//******************************************************************************
		//	Docodiere Attribute 
		//******************************************************************************
		protected function DecodeAttributeData($attribute)
			{

			$string = "";

			switch ($attribute) 
					{
                    case 0:
                        $string =  "The measuregroup has been captured by a device and is known to belong to this user (and is not ambiguous)";
                        break;
					case 1:
						$string =  "The measuregroup has been captured by a device but may belong to other users as well as this one (it is ambiguous)";
						break;
					case 2:
						$string =  "The measuregroup has been entered manually for this particular user";
						break;
					case 3:
						$string =  "3";
						break;
					case 4:
						$string =  "The measuregroup has been entered manually during user creation (and may not be accurate)";
						break;
					case 5:
						$string =  "Measure auto, it's only for the Blood Pressure Monitor. This device can make many measures and computed the best value";
						break;
					case 6:
						$string =  "6";
						break;
					case 7:
						$string =  "Measure confirmed. You can get this value if the user confirmed a detected activity";
						break;
					case 8:
						$string =  "The measuregroup has been captured by a device and is known to belong to this user (and is not ambiguous)";
						break;
								

					default:
                       $string = "Unkown Attribute";

					}
					
			return $string;		

            }	

		//******************************************************************************
		//	Docodiere Geraeteliste 
		//******************************************************************************
		protected function DecodeDeviceData($data)
			{

			$moreDebug = $this->ReadPropertyBoolean("ShowMoreDebug");

			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $data , 0);

			$devices = json_decode($data,TRUE);
			if (isset($devices['devices']) == false )
				{
				$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Devices not found", 0);
				return;
				}

			$devices = $devices['devices'];
			
			if ($moreDebug == true) 
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


				$InstanceDeviceID = $this->GetDeviceID();

				if ( $InstanceDeviceID == $deviceid )
					{
					if ($moreDebug == true) 
						{
                        $this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicetype, 0);
                        $this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicemodel, 0);
                        $this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicemodelid, 0);
                        $this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicetimezone, 0);
                        $this->SendDebug(__FUNCTION__."[".__LINE__."]", $devicedatestring, 0);
                        $this->SendDebug(__FUNCTION__."[".__LINE__."]", $deviceid, 0);
                    	}

					$changed = false;

					$old = $this->ReadPropertyString("devicetype");
					if ($old != $devicetype) 
						{	
                        IPS_SetProperty($this->InstanceID, "devicetype", $devicetype);
                        $changed = true;
						}
					$old = $this->ReadPropertyString("devicemodel");
					if ($old != $devicemodel) 
						{
						IPS_SetProperty($this->InstanceID, "devicemodel", $devicemodel);
						$changed = true;
						}
					$old = $this->ReadPropertyInteger("devicemodelid");
					if ($old != $devicemodelid) 
						{
						IPS_SetProperty($this->InstanceID, "devicemodelid", $devicemodelid);
						$changed = true;
						}
					$old = $this->ReadPropertyString("devicetimezone");
					if ($old != $devicetimezone) 
						{
						IPS_SetProperty($this->InstanceID, "devicetimezone", $devicetimezone);
						$changed = true;
						}
					$old = $this->ReadPropertyString("devicedatestring");
					if ($old != $devicedatestring) 
						{
						IPS_SetProperty($this->InstanceID, "devicedatestring", $devicedatestring);
						$changed = true;
						}
					$old = $this->ReadPropertyString("deviceid");
					if ($old != $deviceid) 
						{
						IPS_SetProperty($this->InstanceID, "deviceid", $deviceid);
						$changed = true;
						}
																					
						
					if ($changed == true) 
						{
						$this->SendDebug(__FUNCTION__."[".__LINE__."]", "Devices Changed" , 0);

                        IPS_ApplyChanges($this->InstanceID);
                    	}	

					}
					
				}	
				
			}	


		//******************************************************************************
		//	Get DeviceID der Instance	
		//******************************************************************************
		protected function GetDeviceID()
			{

			$guid = "87f6241522588affc3b734842fcf208b24b3fee1";	// Thermo
			$guid =	"c7f35d19634ecb4f273ec42a4f4915b74315b2a9";	// Body Cardio
			$guid =	"261dc788ba02c62ddaad86c9c2867246750688cc";	// BPM
			$guid =	"19f734a3ed8be9124ca5ce14a99701c1a7a453c1";	// Withings Blood Pressure Monitor V2
			$guid =	"bb9ce8701afe8d39fe17a8490bd90fc6738db872";	// Aura

			return $guid; 
			}


		//******************************************************************************
		//	Unixtimestamp wandeln	
		//******************************************************************************
		protected function TimestampToString($timestamp)
			{
			return date('d.m.Y H:i:s',$timestamp);
			}


		//******************************************************************************
		//	Konfigurationsformular dynamisch erstellen
		//******************************************************************************
		public function GetConfigurationForm() 
			{	

				$devicetyp = $this->ReadPropertyString("devicetype");
				$devicemodel = $this->ReadPropertyString("devicemodel");
				$devicemodelid = $this->ReadPropertyInteger("devicemodelid");
				$devicetimezone = $this->ReadPropertyString("devicetimezone");
				$devicedatestring = $this->ReadPropertyString("devicedatestring");
				$deviceid = $this->ReadPropertyString("deviceid");

				$form = '
				
				{
					"elements":
					[
						{ "type": "Label"             , "label":  "Withings Health Device V1#1" },

						{ 
							"type": "RowLayout",
							"items": [
										{
										"type": "Label",
										"width": "150px",
										"caption": "Device Typ"
										},
										{
										"type": "Label",
										
										"caption": ": '.$devicetyp.'"
									
										}
									]
						},

						{ 
							"type": "RowLayout",
							"items": [
										{
										"type": "Label",
										"width": "150px",
										"caption": "Device Model"
										},
										{
										"type": "Label",
										"caption": ": '.$devicemodel.'"
									
										}
									]
						},

						{ 
							"type": "RowLayout",
							"items": [
										{
										"type": "Label",
										"width": "150px",
										"caption": "Device Model ID"
										},
										{
										"type": "Label",
										"caption": ": '.$devicemodelid.'"
									
										}
									]
						},
						  
						{ 
							"type": "RowLayout",
							"items": [
										{
										"type": "Label",
										"width": "150px",
										"caption": "Device Timezone"
										},
										{
										"type": "Label",
										"caption": ": '.$devicetimezone.'"
									
										}
									]
						},

						{ 
							"type": "RowLayout",
							"items": [
										{
										"type": "Label",
										"width": "150px",
										"caption": "Device Date"
										},
										{
										"type": "Label",
										"caption": ": '.$devicedatestring.'"
									
										}
									]
						},

						{ 
							"type": "RowLayout",
							"items": [
										{
										"type": "Label",
										"width": "150px",
										"caption": "Device ID"
										},
										{
										"type": "Label",
										"caption": ": '.$deviceid.'"
									
										}
									]
						},

						

						{
							"type":  "ExpansionPanel", "caption": "Expert Parameters",
							"items": 	[
							  			{"type": "CheckBox", "name": "ShowMoreDebug", "caption": "Aktivate more Debug"}
										]
						  }
					  

	
				  
				  
					],
					
				  
					"status":
					  [
						  { "code": 101, "icon": "active", "caption": "Withings Health Device wird erstellt..." },
						  { "code": 102, "icon": "active", "caption": "Withings Health Device ist aktiv" },
						  { "code": 104, "icon": "inactive", "caption": "Withings Health Device ist inaktiv" }
						  	  
					  ]
				  
				  }
				
				';

                return $form;
            }






	}