<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

return [
    'adminUser' => [
        'userId'        => 'admin',
        'userLoginName' => 'admin@myoxideshop.com',
        'userPassword'  => 'admin0303',
        'userName'      => 'John',
        'userLastName'  => 'Doe',
    ],
    'existingUser' => [
        'userId'        => 'testuser',
        'userLoginName' => 'some_test_user@oxid-esales.dev',
        'userPassword'  => 'useruser',
        'userName'      => 'UserNamešÄßüл',
        'userLastName'  => 'UserSurnamešÄßüл',
    ],
    'newUser' => [
        'loginData' => [
            'userLoginNameField' => 'new_test_user@oxid-esales.dev',
            'userPasswordField'  => 'useruser',
        ],
        'address' => [
            'userSalutation' => 'Mr',
            'userFirstName'  => 'New',
            'userLastName'   => 'User',
            'street'         => 'Street',
            'streetNr'       => '55',
            'ZIP'            => '5555',
            'city'           => 'City',
            'countryId'      => 'Germany',
        ],
    ]
];
