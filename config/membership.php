<?php

use Vimatech\Membership\Models\Membership;

return [
    'models' => [
        'membership' => Membership::class,
    ],

    'tables' => [
        'memberships' => 'memberships',
    ],

    'roles' => [
        'owner' => 100,
        'admin' => 50,
        'member' => 10,
    ],

    'owner_roles' => ['owner'],

    'admin_roles' => ['owner', 'admin'],

    'guards' => [
        'prevent_removing_last_owner' => true,
        'prevent_removing_last_admin' => false,
        'prevent_self_demotion' => false,
        'prevent_role_escalation' => false,
    ],

    'soft_deletes' => false,
];
