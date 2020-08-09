<?php
	class WithingsHealthDevice extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

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
			$this->SendDebug(__FUNCTION__."[".__LINE__."]", $JSONString , 0);
			
			$data = json_decode($JSONString);

			$buffer = utf8_decode($data->Buffer);
			$dataid = utf8_decode($data->DataID);	

			if ( $dataid == "{33C19E7A-3386-09D7-DF5D-FE75EE51FF09}")		// Empfang von IO
				{
                $this->SendDebug(__FUNCTION__."[".__LINE__."]", $buffer, 0);
                // $this->SendDebug(__FUNCTION__."[".__LINE__."]", $dataid, 0);
				}
				
				

			// IPS_LogMessage("Device RECV", utf8_decode($data->Buffer));
	
	
			}




	}