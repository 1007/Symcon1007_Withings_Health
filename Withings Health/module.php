<?php
	class WithingsHealth extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent("{6179ED6A-FC31-413C-BB8E-1204150CF376}");
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

		public function Send(string $Text)
		{
			$this->SendDebug(__FUNCTION__."[".__LINE__."]","Starte Webseite zum einloggen bei Withings",0);
			$this->SendDataToParent(json_encode(Array("DataID" => "{018EF6B5-AB94-40C6-AA53-46943E824ACF}", "Buffer" => $Text)));
			// $this->SendDataToParent(json_encode(Array("DataID" => "{4A550680-80C5-4465-971E-BBF83205A02B}", "Buffer" => $Text,'EventID' => 123)));
			
			
		
		
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("Device RECV", utf8_decode($data->Buffer));
		}

	}