<?php 

return [
    'cloudfront' => [
        'sign' => [
            'key_pair_id' => 'key_pair_id',
            'private_key_file_path' => config_path().DIRECTORY_SEPARATOR.'aws'.DIRECTORY_SEPARATOR.'sign'.DIRECTORY_SEPARATOR.'private_key.pem',
        ],
    ],
];