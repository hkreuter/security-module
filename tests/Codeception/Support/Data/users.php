<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

return [
    'existingUser' => [
        'userId'        => 'testuser',
        'userLoginName' => 'some_test_user@oxid-esales.dev',
        'userPassword'  => 'useruser',
        'userName'      => 'UserNamešÄßüл',
        'userLastName'  => 'UserSurnamešÄßüл',
    ],
    'adminUser' => [
        'userId'        => 'oxadmintest',
        'userLoginName' => 'admin_test@oxid-esales.dev',
        'userPassword'  => 'useruser',
        'userName'      => 'John',
        'userLastName'  => 'Doe',
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
