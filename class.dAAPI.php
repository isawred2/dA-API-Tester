<?php
	class dAAPI {
	
		// Constructing the basic functions of the class
		function __construct($client_id, $client_secret) {
			define('LBR', "\r\n");
			define('HR', "\r\n========================================================\r\n");
			$this->os = PHP_OS; // The System OS
			$this->client_id = $client_id; // Client ID
			$this->client_secret = $client_secret; // Client Secret
			echo HR . LBR . "Welcome to the dA API Tester!" . LBR . HR;
			$this->menu();
			
		}
		
		// Creating the menu of options
		public function menu() {
			// Listing all the options to choose
			echo LBR . "Please choose an option to run below!" . LBR;
			echo LBR . "oAuth API" . LBR;
				echo "\t1) Grab your oAuth Tokens" . LBR;
				echo "\t2) Grab your dAmntoken" . LBR;
				echo "\t3) Grab your user information" . LBR;
				echo "\t4) Check your Stash space" . LBR;
				echo "\t5) Upload to Stash (Not implemented yet)" . LBR;
			echo LBR . "oEmbed API" . LBR;
				echo "\t6) Show Deviation Info" . LBR;
			// Choosing the option
			echo LBR . "Enter the number of the option:" . LBR;
			$choice = trim(fgets(STDIN));
			echo HR . LBR;
			
			// Checking the option
			switch($choice) {
				case 1: // oAuth
					$this->oauth(1);
					echo "Tokens grabbed!" . LBR . HR;
					break;
				case 2: // dAmntoken
					$this->damntoken();
					echo "Your dAmntoken is " . $this->damntoken->damntoken . "." . LBR . HR;
					break;
				case 3: // Whoami
					$this->whoami();
					echo "You are " . $this->whoami->symbol . $this->whoami->username . "." . LBR . HR;
					break;
				case 4: // Stash Space
					$this->stash_space();
					echo "Your currently left stash space is " . $this->stash_space->available_space/1024/1024 . " megabytes." . LBR . HR;
					break;
				case 5: // Stash Upload
					echo "Not implemented yet.\r\n" . LBR . HR;
					break;
				case 6: // oEmbed
					$this->oEmbed();
					echo "This deviation is " . $this->oembed->title . " by " . $this->oembed->author_name. "." . LBR . HR;
					break;
				default: // Default
					echo "Invalid option, please try again!" . LBR . HR;
					break;
			}
			$this->menu(); // Reset to menu after option chosen
		}
		
		// Function to reuse the curl code.
		private function curler($url) {
			$curl_handle=curl_init();
			curl_setopt($curl_handle,CURLOPT_URL,$url);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1); // Stop curl echoing the info
			$buffer = curl_exec($curl_handle);
			curl_close($curl_handle);
			return $buffer; // Return the buffer of info
		}
		
		// oAuth function, mode sets silent, 0 = silent, 1 = echo
		public function oauth($mode) { 
			if(file_exists("oauth.json")){ // Checking if the file_exists
				echo ($mode == 0) ?: "Grabbing existing oAuth tokens..." . LBR; // Turn off if silent
				
				// Reading config file
				$config_file = "oauth.json";
				$fh = fopen($config_file, 'r') or die("can't open file");
				
				echo ($mode == 0) ?: "Tokens grabbed from file..." . LBR . LBR;
				// Setting to the oauth_tokens variable
				$this->oauth_tokens = json_decode(fread($fh, filesize($config_file)));
				
				echo ($mode == 0) ?: "Checking if tokens have expired..." . LBR;
				$placebo = json_decode($this->curler('https://www.deviantart.com/api/draft15/placebo?access_token='.$this->oauth_tokens->access_token));
				if($placebo->status != "success") { 
					echo ($mode == 0) ?: "Tokens expired, grabbing new ones..." . LBR;
					unlink($config_file);
					$this->oauth(0);
				}
				fclose($fh);
			} else {
				echo ($mode == 0) ?: "Grabbing the oAuth Tokens from deviantART..." . LBR; // Turn off if silent
				
				// Opening browser based on OS
				switch($this->os) {
					case "Darwin": // Mac OSX uses open command
						exec("open 'https://www.deviantart.com/oauth2/draft15/authorize?client_id=".$this->client_id."&redirect_uri=http://damnapp.com/apicode.php&response_type=code'");
						break;
					case "WINNT": // Windows uses start command
						exec('start "" "https://www.deviantart.com/oauth2/draft15/authorize?client_id=".$this->client_id."&redirect_uri=http://damnapp.com/apicode.php&response_type=code"');
						break;
					case "Linux": // Linux uses browser
						exec("xdg-open 'https://www.deviantart.com/oauth2/draft15/authorize?client_id=".$this->client_id."&redirect_uri=http://damnapp.com/apicode.php&response_type=code'");
						break;
		 			default: // No browser command found so echo it out
		 				echo "Could not open your browser to the required URL. Please load the below one!" . LBR;
		 				echo 'https://www.deviantart.com/oauth2/draft15/authorize?client_id=".$this->client_id."&redirect_uri=http://damnapp.com/apicode.php&response_type=code';
		 				break;
		 		}
		 		
				// Retreiving the code
				echo "Enter the code:" . LBR;
				$code = trim(fgets(STDIN)); // STDIN for reading input
				
				// Getting the access token.
				$tokens = $this->curler('https://www.deviantart.com/oauth2/draft15/token?client_id='.$this->client_id.'&redirect_uri=http://damnapp.com//apicode.php&grant_type=authorization_code&client_secret='.$this->client_secret.'&code='.$code);
				
				// Set to oauth_tokens variable
				$this->oauth_tokens = json_decode($tokens);
				
				// Writing to oauth.json
				$config_file = "oauth.json";
				$fh = fopen($config_file, 'w') or die("can't open file");
				fwrite($fh, $tokens);
				fclose($fh);
			}
		}
		
		
		// dAmntoken function
		public function damntoken() {
			// Check if the oauth_tokens variable is set, if not set it.
			if(!isset($this->oauth_tokens)) {
				$this->oauth(0);
			}
			
			// Grab the damntoken and set it to damntoken variable
			$this->damntoken = json_decode($this->curler('https://www.deviantart.com/api/draft15/user/damntoken?access_token='.$this->oauth_tokens->access_token));
		}
		
		
		// whoami function
		public function whoami() {
			// Check if the oauth_tokens variable is set, if not set it.
			if(!isset($this->oauth_tokens)) {
				$this->oauth(0);
			}
			
			// Grab your whoami info and set it to whoami variable
			$this->whoami = json_decode($this->curler('https://www.deviantart.com/api/draft15/user/whoami?access_token='.$this->oauth_tokens->access_token));
		}
		
		// stash_space function
		public function stash_space() {
			// Check if the oauth_tokens variable is set, if not set it.
			if(!isset($this->oauth_tokens)) {
				$this->oauth(0);
			}
			
			// Grabbing your stash_space info and set it to stash_space variable
			$this->stash_space = json_decode($this->curler('https://www.deviantart.com/api/draft15/stash/space?access_token='.$this->oauth_tokens->access_token));
		}
		
		public function oembed() {	
			// Grab your whoami info and set it to whoami variable
			echo "Enter the URL of the deviation:" . LBR;
			$url = trim(fgets(STDIN)); // STDIN for reading input
			echo "" . LBR;
			$this->oembed = json_decode($this->curler('http://backend.deviantart.com/oembed?url='.$url));
		}

	}
?>