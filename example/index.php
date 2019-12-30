<?php   
    
    include __DIR__ . "/../class/AmoCrm.php";
    
    $Amo = new AmoCrm([  // Подключаемся к AMO
    
        // Данные из настроек интеграции:
    
            'secret_key' => 'IWYM0V6NnoTitCM4BriNsle2ZlNiTsxBWjvoqsNHM8W7rrsxwnhx7SFsvjWufmry',
            'integration_id' => '4177d6d2-ee21-416e-8254-54c6bc357cf1',
            'auth_code' => 'def50200e80b334bbb6010877de0da6564d3be4a12c6cdd562e398762f8ebdd54f026168b4d69ff0c03af88fb645983fa347f80042657531beed12c31a7fb0810cf2a30aa92392e7f54b14223c501b35fd6678b0e5361df43d6f9449ccee8548e68d2aeeb45cbb8ab4a1923c4414dab7afcae3ce8f0f0a7bb274a21df641965de1d9867bd691a7b7d358d80d99363af03b5f5643acfdf4a944ed91633870c9920b47ec403bfeac629ea602bdfe02e6849d028b8fdcf1d40410d7bfb07d3734090bda584c591bf66d1c13b8ec89ead08601da70ac7a0c3600a5eedd8cf1d233fd22116eff5d24ccd407f25f11ab89dcfafb2f47c66f0d3e8ad7962e216b94e668ea8c86eff76c176af63f142c6e5508c77ee72b21ea68493ea30f8f0c0ddf86f68af80f879ac09352501db7a0e918c8897aef492329441829485c21bc16734ad3c8e713a47d092dd654a9a0c084d89a42287d2dc9ac4aa90b7bc3cb8d4433b03b126cc56b3f27b4d155d7eecbb0fe9b6ac00654fa07f3c110d0b3b42a3c36fbb6dcfb85471339371b436ecba2efa4050116055fe48c7bd8879205b59a5491184cd1ec4fd0e2ab0a80f86d4657d386c741224b0cd58c4371c99a6daf14be0479',
            'integration_redirect_uri' => 'https://cw1.urgh.ru/',
            
        
        // Прочие данные:
        
            'user_subdomain' => 'sydoda',  // Ваш поддомен в AMO
            'amo_data_files_dir' => '/../example/amo-auth-files/',  // Относительно места расположения файла с классом 
            'ssl_crypt_key' => 'somecryptkey12345',  // Необязательный параметр. Ключ шифрования данных, записываемых в файл. По умолчанию (если параметр не задан) берется значение secret_key
    
    ]);
    
    $AccountData = $Amo->amoCurl([  // Делаем запрос (подробнее - в описании метода amoCurl класса AmoCrm)
    
        'link' => '/api/v2/account',
        'GET' => [

            'with' => 'task_types',

        ],

    ]);

    AmoCrm::prePrint($AccountData);  // Выводим результаты на экран (функция предназначена только для более удобного тестирования)

