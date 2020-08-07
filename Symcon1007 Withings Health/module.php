<?php


//******************************************************************************
//	Name		:	Withings Health Modul.php
//	Aufruf		:	
//	Info		:	
//
//******************************************************************************

	class WithingsHealth extends IPSModule {

		public function Create()
		{
			
			$this->RegisterPropertyInteger("Intervall", 3600);
			$this->RegisterTimer("WTH_UpdateTimer", 3600000, 'WTH_Update($_IPS["TARGET"]);');

			$this->RegisterPropertyString("AccessToken", "");
			$this->RegisterPropertyString("RefreshToken", "");
			$this->RegisterPropertyString("UserID", "");

			$this->RegisterPropertyString("User", "XXX"); 
			
			$this->RegisterPropertyString("CallbackURL", "");

			//Never delete this line!
			parent::Create();

		}

		public function Destroy()
		{

			$this->UnregisterTimer("WTH_UpdateTimer");

			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{

			  	//Timer stellen
				  $interval = $this->ReadPropertyInteger("Intervall") ;
				  $this->SetTimerInterval("WTH_UpdateTimer", $interval*1000);
	  
				  $this->SetStatus(102);

			//Never delete this line!
			parent::ApplyChanges();
		}


	   	//**************************************************************************
		// manuelles Holen der Daten oder ueber Timer
		//**************************************************************************
		public function Update()
			{
		
			$this->SendDebug(__FUNCTION__,"" ,0);
		
		
		
			}	

	}