<?php   
    
    include __DIR__ . "/../class/AmoCrm.php";
    
    $Amo = new AmoCrm([

        // Данные из настроек интеграции:

            'secret_key' => 'IWYM0V3NnoTitCM4BriNsle2ZlNiTsxBWjvoqsNHM8W7rrsxwnhx7SFsvjWufmry',
            'integration_id' => '4172d6d2-ee21-416e-8254-51c6fc357cf1',
            'auth_code' => 'def502007804f8ba5d9d7bc4d44d8bf888bb8857237974d3adaf2aff69d09c70287293bd2e728ce0352860502658566aed5c64c079bd4bf62b7e4230674c44fe1c9fa99c949a379f13739370a9423636216662e879b985e38a4cd2587fcddf5d48ee438a4d3aeb18546f7e59883da619b34eb087ecf8ae2dc14f00ba447594840066adf866e804a840e9aac30025f0104bb4666d88856bf8bd7a22e95a532b2115211d388edfd878276a44685647445153afb398acb188ffe7ca0142b974c30d7897a93ae3589eb15801842f61b668e28592f308c9660806c57000e4f22d54f16ac9d635ae9dd31de3a58c8d90ae6fb718bc9ba238baaf9cd17d8fc88ed7e8589a032bc15f4ac073c6e8dac59bbb824dd50183c106fb99962b11ffa3793fdcb396c4e30c8484324d25a3cd69c986e19f4e3165fa50a63e7474211495d7a7e51618bed94eac0216ddf3d516101781160b5e1cc60ec7df11d6b70efb7e886bf44d6c0d92f71ba452ae9086953cc587b21a1d9885d975f65ded73076b11789c8672002e7580cdf3a017ea929ee9464348ee972bb1cf07906ead9604e2d05fb09859ee9cf878249a6225609470b66456ea240af5cc533e26c2590f545ed9a6e22e',
            'integration_redirect_uri' => 'https://amo.some.ru/',
            
        
        // Прочие данные:
        
            'user_subdomain' => 'dodatest',  // Ваш поддомен в AMO
            'amo_data_files_dir' => '/../example/amo-auth-files/',  // Место для хранение файлов с данными. Указывается относительно места расположения файла с классом 
            'ssl_crypt_key' => 'somecryptkey12345',  // Необязательный параметр. Ключ шифрования данных, записываемых в файл. По умолчанию (если параметр не задан) берется значение secret_key

    ]);
    
    $AccountData = $Amo->amoCurl([  // Делаем запрос (подробнее - в описании метода amoCurl класса AmoCrm)
    
        'link' => '/api/v2/account',
        'GET' => [

            'with' => 'task_types',

        ],

    ]);

    AmoCrm::prePrint($AccountData);  // Выводим результаты на экран (функция предназначена только для более удобного тестирования)

