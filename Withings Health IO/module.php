<?php
	class WithingsHealthIO extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();
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


		public function ReceiveData($JSONString)
		{

			$this->SendDebug(__FUNCTION__."[".__LINE__."]","Starte Webseite zum einloggen bei Withings",0);
			$data = json_decode($JSONString);
			$this->LogMessage("IO FRWD", utf8_decode($data->Buffer));
		}


		public function ForwardData($JSONString)
		{

			$this->SendDebug(__FUNCTION__."[".__LINE__."]","Starte Webseite zum einloggen bei Withings",0);
			$data = json_decode($JSONString);
			$this->LogMessage("IO FRWD", utf8_decode($data->Buffer));
		}

		public function Send(string $Text)
		{
			$this->SendDataToChildren(json_encode(Array("DataID" => "{178052EF-1D7D-19A3-8C1B-FCE922D2A3EA}", "Buffer" => $Text)));
		}

	}