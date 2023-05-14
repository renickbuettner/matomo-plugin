<?php return [
    'plugin' => [
        'name' => 'Matomo',
        'description' => 'Deep simple Matomo integration for OctoberCMS.',
        'menu_item' => 'Matomo',
    ],
    'permissions' => [
        'tab' => 'Matomo',
        'manage_settings' => [
          'label' => 'Manage Matomo settings',
        ],
        'report_widgets' => [
          'label' => 'Access Matomo report widgets',
        ],
    ],
    'components' => [
        'matomojs' => [
            'name' => 'Matomo JavaScript tag',
            'description' => 'Places a Matomo tracking code for client-slide tracking.',
        ],
        'mtagjs' => [
            'name' => 'Matomo TagManager JavaScript tag',
            'description' => 'Places a Matomo tag manager code for client-side tracking.',
        ],
    ],
    'report_widgets' => [
        'traffic' => [
            'label' => 'Matomo Traffic Overview',
        ],
        'browser' => [
            'label' => 'Matomo Browsers Overview',
        ],
        'screens' => [
            'label' => 'Matomo Screen Sizes Overview',
        ],
    ],
    'backend' => [
      'settings' => [
          'label' => 'Matomo Settings',
          'description' => 'Set your matomo credentials.',
          'tabs' => [
              'general' => 'Your Matomo Settings',
          ],
          'middleware' => [
              'label' => 'Server-side tracking',
              'comment' => 'Use middleware to send the tracking data to matomo. Do not use this combined with ' .
                           'javascript tracking as this will result in double tracking.',
          ],
          'cookies' => [
              'label' => 'Enable cookies',
              'comment' => 'Enable cookies on client-side tracking, will disable the cookie opt-out within the ' .
                           'Matomo client-side script.',
          ],
          'remote_url' => [
              'label' => 'Matomo URL',
              'comment' => 'For example: https://matomo.example.com/ or https://example.com/matomo/',
          ],
          'auth_token' => [
              'label' => 'Auth token',
              'comment' => 'An auth token for accessing the internal matomo Api. You can create one in the ' .
                           'Matomo admin panel.',
          ],
          'matomo_site_id' => [
              'label' => 'Site Id',
              'comment' => 'The id of the site in matomo. You can find this in the URL of the site in matomo.',
          ],
          'container_id' => [
              'label' => 'Container id',
              'comment' => 'Internal id of the tag container. Find it within the Matomo tag manager.',
          ],
      ],
    ],
];
