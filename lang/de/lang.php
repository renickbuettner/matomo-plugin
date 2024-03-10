<?php return [
    'plugin' => [
        'name' => 'Matomo',
        'description' => 'Einfache Matomo Integration für OctoberCMS.',
        'menu_item' => 'Matomo',
    ],
    "period" => "Zeitraum",
    "periods" => [
        "day" => "Tag",
        "week" => "Woche",
        "month" => "Monat",
        "year" => "Jahr",
    ],
    "visitssummary" => "Besucherübersicht",
    'permissions' => [
        'tab' => 'Matomo',
        'manage_settings' => [
            'label' => 'Matomo Einstellungen verwalten',
        ],
        'report_widgets' => [
            'label' => 'Matomo Widgets benutzen',
        ],
    ],
    'components' => [
        'matomojs' => [
            'name' => 'Matomo JavaScript Tag',
            'description' => 'Fügt den Matomo JavaScript Tag hinzu.',
        ],
        'mtagjs' => [
            'name' => 'Matomo TagManager JavaScript Tag',
            'description' => 'Fügt den Matomo Tag Manager hinzu.',
        ],
    ],
    'report_widgets' => [
        'traffic' => [
            'label' => 'Matomo Traffic Übersicht',
        ],
        'browser' => [
            'label' => 'Matomo Browser Übersicht',
        ],
        'campaign' => [
            'label' => 'Matomo Kampagne Übersicht',
        ],
        'pages' => [
            'label' => 'Matomo Meistbesuchte Seiten',
        ],
    ],
    'backend' => [
        'settings' => [
            'label' => 'Matomo Einstellungen',
            'description' => 'Matomo Einstellungen verwalten.',
            'tabs' => [
                'general' => 'Deine Matomo Einstellungen',
            ],
            'middleware' => [
                'label' => 'Server-side Tracking',
                'comment' => 'Nutzt eine Middleware um alle Abfragen Server-seitig zu tracken. Bitte nicht gleichzeitig  ' .
                    'mit JavaScript-Tracking nutzen, da sonst doppelte Daten erfasst werden.',
            ],
            'cookies' => [
                'label' => 'Cookies zulassen',
                'comment' => 'Wenn Cookies zugelassen werden, wird im Script-Tag das Cookie Opt-out entfernt.',
            ],
            'remote_url' => [
                'label' => 'Matomo URL',
                'comment' => 'Zum Beispiel: https://matomo.example.com/ oder https://example.com/matomo/',
            ],
            'auth_token' => [
                'label' => 'Auth token',
                'comment' => 'Der Zugriffstoken für Matomo. Kann im Matomo Adminbereich angelegt werden.',
            ],
            'matomo_site_id' => [
                'label' => 'Seitennummer',
                'comment' => 'Die interne Seitennummer von Matomo. Kann in Matomo aus dem Link abgelesen werden.',
            ],
            'container_id' => [
                'label' => 'Containernummer',
                'comment' => 'Die interne Containernummer von Matomo. Kann im Tag Manager eingesehen werden.',
            ],
        ],
    ],
];
