<?php

    /*

        Пример подключения интеграции AMO:

            $Amo = new AmoCrm([  // Создаем подключение
            
                // Данные из настроек интеграции:
            
                    'secret_key' => 'ITYM0V6NneyitCM4BriNsle2ZlNiTsxBWjvoqsNHM8W7rrsxwnhx7SFsvjWufmry',
                    'integration_id' => '4277d6d2-ee65-416e-8254-54c6bc357cf1',
                    'auth_code' => 'def5020071517911149acc1d39dd287ad4486db2dd5b56550e26877226843a0beba2e58557150f53f78544b6049b057e5a31683a102009fbc53ea4bf1ca4caee088d2e87d2301a4d17230190d63c97e648813f2d50c2580ea209c1ba88a977737e62851df97a3a850c6b2a5823813dd6a30c36bb6827f81b14eacd15c643fceee931c116be1c5860bf7b0bf0a81c5dfa284d77019140a5d15b283a2633ba13cd6ac69750c6f17789525c547ee141026a408b0d70dd3c1027d9c8e9e69c90cd2502347352fb8f689ea559174cec0c0ba2a41b8ae435bab685b6ae325bc3cdaf4623a36b983fd3f7c248e4f21408818d5ccf74e77f7f07e5469ee21560a833435097140397a5c7a3bf03b5c04e1fd2fdfa8f1f3b83eed9d9f494be50174e4dc127a4dc799aad31aebfb7a476801b20b06fc697f65ace239a34e179494cc94ef2de0421aae5cc3c159d34f2ca98b8a4ea0b79cfac934e886bf3f4688b0151987e470dcfa41bccc9128885bf2f68defa64b2db9a0f21c3d32389fb19d042daff0ae05527678e170e5408fa9e76a8855a4ab8964a6b23a19c9f00771d2f9cfb37a55abf8e0d7f2431ce49c9a2f68ffac6744fa6679ef40fb1850b63e9d091fd1b36',
                    'integration_redirect_uri' => 'https://amoint.some.ru/',
                
                // Прочие данные:
                
                    'user_subdomain' => 'sydoda',
                    'amo_data_files_dir' => '/../../amo-auth-files/',  // Относительно места расположения файла с классом 
                    'ssl_crypt_key' => 'sdDd32ds32Fdfds',  // Необязательный параметр. Ключ шифрования данных, записываемых в файл. По умолчанию (если параметр не задан) берется значение secret_key
            
            ]);
            
        Пример запроса к API AMO:
            
            $AccountData = $Amo->amoCurl([  // Делаем запрос (подробнее - в описании метода amoCurl класса AmoCrm)
            
                'link' => '/api/v2/account',
                'GET' => [

                    'with' => 'task_types',

                ],

            ]);
            
        Пример вывода полученных (для тестов):

           AmoCrm::prePrint($AccountData);  // Выводим результаты на экран (функция предназначена только для более удобного тестирования) 

    */

    class AmoCrm {

		protected $amo_data = "";
		protected $amo_tokens_data = "";
        
        private static $StdHeadCounter = 0;
        
		function __construct($AMOData)
        {

            $this->amo_data = $AMOData;
            $this->amo_tokens_data = $this->getAmoTokens();

		}
        
        private function getAmoTokens()
        {
            
            $AMOData = $this->amo_data;
            
            $AmoTokensData = false;
            $Error = false;

            $AmoTokensData = $this->readAmoTokensFromFile();

            if (is_array($AmoTokensData)) {

                if ($AmoTokensData['access_token_end_time'] < time()) {

                    // Старый access-токен, необходимо его переполучение
                    
                    $AmoTokensData = $this->refreshAmoTokens();
                    
                }

            }
            else $AmoTokensData = $this->refreshAmoTokens();
            
            if ($Error) {
                
                echo "AMO get tokens error";
                exit();

            }
            else return $AmoTokensData;

        }
        
        private function refreshAmoTokens()
        {
            
            $AMOData = $this->amo_data;
            $AccessTokens = [];
            
            $AmoTokensData = false;
            $Error = false;
            
            $AmoTokensData = $this->readAmoTokensFromFile();

            if (is_array($AmoTokensData)) {

                $AccessTokens = $this->amoCurl([
                
                    'link' => '/oauth2/access_token',
                    'add_header_access_token' => false,
                    'POST' => [

                        'client_id' => $AMOData['integration_id'],
                        'client_secret' => $AMOData['secret_key'],
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $AmoTokensData['refresh_token'],
                        'redirect_uri' => $AMOData['integration_redirect_uri'],

                    ],

                ]);
                
            }
            else {  // Первое получение токенов по коду авторизации

                $AccessTokens = $this->amoCurl([
                
                    'link' => '/oauth2/access_token',
                    'add_header_access_token' => false,
                    'POST' => [

                        'client_id' => $AMOData['integration_id'],
                        'client_secret' => $AMOData['secret_key'],
                        'grant_type' => 'authorization_code',
                        'code' => $AMOData['auth_code'],
                        'redirect_uri' => $AMOData['integration_redirect_uri'],                     

                    ],

                ]);
                
            }

            if ($AccessTokens['error'] || !$AccessTokens['access_token']) $Error = true;
            else {
                
                $AmoTokensData = [

                    'access_token' => $AccessTokens['access_token'],
                    'access_token_end_time' => time()+72000,
                    'refresh_token' => $AccessTokens['refresh_token'],
                    'refresh_token_end_time' => time()+5184000,
                
                ];
             
                $this->writeAmoTokensToFile($AmoTokensData);

            }                    

  
            if ($Error) {
                
                echo "AMO refresh tokens error";
                exit();

            }
            else return $AmoTokensData;

        }
        
        private function readAmoTokensFromFile()
        {

            $Result = false;
            
            $AMOData = $this->amo_data;
            
            if ($AMOData['amo_data_files_dir']) $AmoFilesDir = $AMOData['amo_data_files_dir'];
            else $AmoFilesDir = '/';            
            
            $AmoTokensDataFile = __DIR__ .$AmoFilesDir.$AMOData['user_subdomain'].'_'.md5($AMOData['integration_id']).'.json';

            if (file_exists($AmoTokensDataFile)) {
                
                if ($AMOData['ssl_crypt_key']) $Key = $AMOData['ssl_crypt_key'];
                else $Key = $AMOData['secret_key'];

                $AmoTokensData = json_decode(self::sslCrypt(file_get_contents($AmoTokensDataFile), 'decrypt', $Key), true);

                if (is_array($AmoTokensData)) {
                 
                    $Result = $AmoTokensData;
                    
                }

            }
            
            return $Result;

        }
        
        private function writeAmoTokensToFile($AmoTokensData)
        {
            
            $this->amo_tokens_data = $AmoTokensData;
            
            $AMOData = $this->amo_data;
            
            if ($AMOData['amo_data_files_dir']) $AmoFilesDir = $AMOData['amo_data_files_dir'];
            else $AmoFilesDir = '/';
            
            $AmoTokensDataFile = __DIR__ .$AmoFilesDir.$AMOData['user_subdomain'].'_'.md5($AMOData['integration_id']).'.json';

            if ($AMOData['ssl_crypt_key']) $Key = $AMOData['ssl_crypt_key'];
            else $Key = $AMOData['secret_key'];

            file_put_contents($AmoTokensDataFile, self::sslCrypt(json_encode($AmoTokensData), 'encrypt', $Key));
            
            return $this;

        }
        
        private static function sslCrypt($Str, $Mode, $Key)
        {
            
            $Result = '';
            
            if(trim($Str)){

                if ($Mode === 'encrypt') {

                    $IvLen = openssl_cipher_iv_length($cipher="AES-128-CBC");
                    $IV = openssl_random_pseudo_bytes($IvLen);
                    $CiphertextRaw = openssl_encrypt($Str, $cipher, $Key, $Options=OPENSSL_RAW_DATA, $IV);
                    $HMac = hash_hmac('sha256', $CiphertextRaw, $Key, $AsBinary=true);
                    $Result = base64_encode( $IV.$HMac.$CiphertextRaw );
                    
                }
                else if ($Mode === 'decrypt') {

                    $c = base64_decode($Str);
                    $IvLen = openssl_cipher_iv_length($Cipher="AES-128-CBC");
                    $IV = substr($c, 0, $IvLen);
                    $HMac = substr($c, $IvLen, $Sha2len=32);
                    $CiphertextRaw = substr($c, $IvLen+$Sha2len);
                    $Result = openssl_decrypt($CiphertextRaw, $Cipher, $Key, $Options=OPENSSL_RAW_DATA, $IV);
                    $Calcmac = hash_hmac('sha256', $CiphertextRaw, $Key, $AsBinary=true);
                    if (!hash_equals($HMac, $Calcmac)) $Result = '';
                    
                }
            
            }
            
            return $Result;
            
        }        
        
        public function amoCurl($CurlData)
        {

            /*
            
                Функция для выполнения запросов к AMO через CURL
            
                Описание входного массива $CurlData:
                
                $CurlData = [
                
                    'link' => '/api/v2/account',  // Обязательный параметр, адрес запроса (без https://SUBDOMAIN.amocrm.ru)
                    
                    'response_json_decode' => true, // Необязательный к указанию параметр. По умолчанию - true. Если указан как false, то ответ на запрос не будет декодирован как json
                    'add_header_access_token' => true, // Необязательный к указанию параметр. По умолчанию - true. Вставлять в заголовок access_token
                    
                    'GET' => [  // Доп. параметр. Массив параметров, передаваемых с GET-запросом

                        'some' => 1,
                        'else' => 'some data',

                    ],
                    'POST' => [  // Доп. параметр. Массив параметров, передаваемых с POST-запросом

                        'some' => 2,
                        'else' => 'some data',

                    ],
                    'PUT' => [  // Доп. параметр. Массив параметров, передаваемых с PUT-запросом

                        'some' => 3,
                        'else' => 'some data',

                    ],
                
                ];
            
            */

            $AMOData = $this->amo_data;
            
            $DefaultCurlData = [
            
                'link' => '',
                'response_out_key' => '',
                'response_json_decode' => true,
                'add_header_access_token' => true,
            
            ];
            
            $CurlData = array_merge($DefaultCurlData, $CurlData);

            $CurlData['link'] = 'https://'.$AMOData['user_subdomain'].'.amocrm.ru'.$CurlData['link'];
            
            $Curl = curl_init();
            
            if(is_array($CurlData['GET'])) $CurlData['link'] .= '?'.http_build_query($CurlData['GET']);
            
            curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($Curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
            curl_setopt($Curl, CURLOPT_URL, $CurlData['link']);

            $AmoTokensData = $this->amo_tokens_data;
            
            if (is_array($AmoTokensData) || $CurlData['add_header_access_token'] === false) {
                
                if ($CurlData['add_header_access_token'] && $AmoTokensData['access_token_end_time'] < time()) {

                    // Старый access-токен, необходимо его переполучение
                    
                    $AmoTokensData = $this->refreshAmoTokens();
                    
                }            
                
                $CurlHeader = ['Content-Type:application/json'];
                
                if ($CurlData['add_header_access_token'] && is_array($AmoTokensData)) {

                    $CurlHeader[] = 'Authorization: Bearer '.$AmoTokensData['access_token'];
                
                }

                curl_setopt($Curl, CURLOPT_HTTPHEADER, $CurlHeader);

                if(is_array($CurlData['POST'])) {
                    
                    curl_setopt($Curl, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($Curl, CURLOPT_POSTFIELDS, json_encode($CurlData['POST']));

                }
                
                if(is_array($CurlData['PUT'])) {
                    
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($Curl, CURLOPT_POSTFIELDS, json_encode($CurlData['PUT']));

                }
                
                curl_setopt($Curl, CURLOPT_HEADER, false);
                curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($Curl, CURLOPT_SSL_VERIFYHOST, 0);
                
                $Response = curl_exec($Curl);
                $ResponseCode = intval(curl_getinfo($Curl, CURLINFO_HTTP_CODE));

                if ($ResponseCode!=200 && $ResponseCode!=204) {

                    $Errors = [
                    
                        301=>'Moved permanently',
                        400=>'Bad request',
                        401=>'Unauthorized',
                        403=>'Forbidden',
                        404=>'Not found',
                        500=>'Internal server error',
                        502=>'Bad gateway',
                        503=>'Service unavailable'
                      
                    ];

                    $OriginalResponse = $Response;

                    $Response = [
                    
                        'error' => true,
                        'error_code' => $ResponseCode,
                        'error_text' => $Errors[$ResponseCode],
                        'original_response' => $OriginalResponse,
                    
                    ];
                    
                }
                else {
                
                    if($CurlData['response_json_decode']){
                        
                        if($Response) $Response = json_decode($Response, true);
                        else $Response = [];
                        
                    }
                
                }
                
                return $Response;
            
            }
            else {
                
                echo "AMO tokens error";
                exit();
             
            }
            
        }

        public static function prePrint($DataToPrint, $HeadName = 'Результат')
        {
            self::$StdHeadCounter++;
            
            if ($HeadName) {

                $HeadName = self::$StdHeadCounter.". ".$HeadName;
                $HeadStr = "== ".$HeadName.": =".str_repeat("=", 44-mb_strlen($HeadName));
                
            }
            else $HeadStr = str_repeat("=", 50);
            
            $PreStyle = "style=\"font-family:monospace;font-size:13px;text-align:left;\"";
            
            echo "<pre $PreStyle>$HeadStr</pre>";
            echo "<pre $PreStyle>";
            print_r($DataToPrint);
            echo "</pre>";
            echo "<pre $PreStyle>".str_repeat("~", 50)."</pre>";
            echo "</div>";

        }        
        
    }
