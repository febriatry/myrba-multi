<?php

return [
    /**
     * If any input file(image) as default will used options below.
     */
    'image' => [
        /**
         * Path for store the image.
         *
         * avaiable options:
         * 1. public
         * 2. storage
         */
        'path' => 'storage',

        /**
         * Will used if image is nullable and default value is null.
         */
        'default' => 'https://via.placeholder.com/350?text=No+Image+Avaiable',

        /**
         * Crop the uploaded image using intervention image.
         */
        'crop' => true,

        /**
         * When set to true the uploaded image aspect ratio will still original.
         */
        'aspect_ratio' => true,

        /**
         * Crop image size.
         */
        'width' => 500,
        'height' => 500,
    ],

    'format' => [
        /**
         * Will used to first year on select, if any column type year.
         */
        'first_year' => 1900,

        /**
         * If any date column type will cast and display used this format, but for input date still will used Y-m-d format.
         *
         * another most common format:
         * - M d Y
         * - d F Y
         * - Y m d
         */
        'date' => 'd/m/Y',

        /**
         * If any input type month will cast and display used this format.
         */
        'month' => 'm/Y',

        /**
         * If any input type time will cast and display used this format.
         */
        'time' => 'H:i',

        /**
         * If any datetime column type or datetime-local on input, will cast and display used this format.
         */
        'datetime' => 'd/m/Y H:i',

        /**
         * Limit string on index view for any column type text or longtext.
         */
        'limit_text' => 100,
    ],

    /**
     * It will used for generator to manage and showing menus on sidebar views.
     *
     * Example:
     * [
     *   'header' => 'Main',
     *
     *   // All permissions in menus[] and submenus[]
     *   'permissions' => ['test view'],
     *
     *   menus' => [
     *       [
     *          'title' => 'Main Data',
     *          'icon' => '<i class="bi bi-collection-fill"></i>',
     *          'route' => null,
     *
     *          // permission always null when isset submenus
     *          'permission' => null,
     *
     *          // All permissions on submenus[] and will empty[] when submenus equals to []
     *          'permissions' => ['test view'],
     *
     *          'submenus' => [
     *                 [
     *                     'title' => 'Tests',
     *                     'route' => '/tests',
     *                     'permission' => 'test view'
     *                  ]
     *               ],
     *           ],
     *       ],
     *  ],
     *
     * This code below always changes when you use a generator and maybe you must lint or format the code.
     */
    'sidebars' => [
        [
            'header' => 'Menu',
            'permissions' => [
                'pemasukan view',
                'pengeluaran view',
                'tagihan view',
                'audit keuangan view',
                'laporan view',
                'category pemasukan view',
                'category pengeluaran view',
                'bank account view',
                'bank view',
                'pelanggan view',
                'balance history view',
                'withdraw view',
                'withdraw create',
                'withdraw edit',
                'withdraw delete',
                'withdraw approval',
                'topup view',
                'area coverage view',
                'package view',
                'package category view',
                'odc view',
                'odp view',
                'attendance view',
                'attendance manage',
                'attendance payroll',
                'unit satuan view',
                'kategori barang view',
                'barang view',
                'transaksi stock in view',
                'transaksi stock out view',
                'laporan barang view',
                'investor view',
                'investor rule manage',
                'investor payout approve',
                'investor payout request',
                'log view',
                'dhcp view',
                'interface view',
                'settingmikrotik view',
                'statusrouter view',
                'mikrotik automation view',
                'audit pelanggan view',
                'profile pppoe view',
                'active ppp view',
                'non active ppp view',
                'secret ppp view',
                'static view',
                'active static view',
                'non active static view',
                'hotspotactive view',
                'hotspotuser view',
                'voucher view',
                'hotspotprofile view',
                'sendnotif view',
                'olt view',
                'tiket aduan view',
                'banner management view',
                'informasi management view',
                'setting web view',
                'user view',
                'role & permission view',
                'activity log view',
            ],
            'menus' => [
                [
                    'title' => 'Keuangan',
                    'icon' => '<i class="bi bi-cash-stack"></i>',
                    'route' => '/finance-hub',
                    'permission' => null,
                    'permissions' => [
                        'pemasukan view',
                        'pengeluaran view',
                        'tagihan view',
                        'audit keuangan view',
                        'laporan view',
                        'category pemasukan view',
                        'category pengeluaran view',
                        'bank account view',
                        'bank view',
                        'setor view',
                        'setor create',
                        'setor approve',
                        'setor export pdf',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'Pelanggan',
                    'icon' => '<i class="bi bi-people"></i>',
                    'route' => '/pelanggan-hub',
                    'permission' => null,
                    'permissions' => [
                        'pelanggan view',
                        'balance history view',
                        'withdraw view',
                        'withdraw create',
                        'withdraw edit',
                        'withdraw delete',
                        'withdraw approval',
                        'topup view',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'Kelola Layanan',
                    'icon' => '<i class="bi bi-boxes"></i>',
                    'route' => '/layanan-hub',
                    'permission' => null,
                    'permissions' => [
                        'area coverage view',
                        'package view',
                        'package category view',
                        'odc view',
                        'odp view',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'HR & Absensi',
                    'icon' => '<i class="bi bi-person-badge"></i>',
                    'route' => '/hr-hub',
                    'permission' => null,
                    'permissions' => [
                        'attendance view',
                        'attendance manage',
                        'attendance payroll',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'Inventory',
                    'icon' => '<i class="bi bi-box-seam"></i>',
                    'route' => '/inventory-hub',
                    'permission' => null,
                    'permissions' => [
                        'unit satuan view',
                        'kategori barang view',
                        'barang view',
                        'transaksi stock in view',
                        'transaksi stock out view',
                        'laporan barang view',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'Network Ops',
                    'icon' => '<i class="bi bi-router-fill"></i>',
                    'route' => '/network-hub',
                    'permission' => null,
                    'permissions' => [
                        'log view',
                        'dhcp view',
                        'interface view',
                        'settingmikrotik view',
                        'statusrouter view',
                        'mikrotik automation view',
                        'audit pelanggan view',
                        'profile pppoe view',
                        'active ppp view',
                        'non active ppp view',
                        'secret ppp view',
                        'static view',
                        'active static view',
                        'non active static view',
                        'hotspotactive view',
                        'hotspotuser view',
                        'voucher view',
                        'hotspotprofile view',
                        'sendnotif view',
                        'olt view',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'Investor & Mitra',
                    'icon' => '<i class="bi bi-briefcase"></i>',
                    'route' => '/investor-hub',
                    'permission' => null,
                    'permissions' => [
                        'investor view',
                        'investor rule manage',
                        'investor payout approve',
                        'investor payout request',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'CMS',
                    'icon' => '<i class="bi bi-newspaper"></i>',
                    'route' => '/cms-hub',
                    'permission' => null,
                    'permissions' => [
                        'tiket aduan view',
                        'banner management view',
                        'informasi management view',
                    ],
                    'submenus' => [],
                ],
                [
                    'title' => 'Settings',
                    'icon' => '<i class="bi bi-gear-wide-connected"></i>',
                    'route' => '/settings-hub',
                    'permission' => null,
                    'permissions' => [
                        'setting web view',
                        'user view',
                        'role & permission view',
                        'activity log view',
                    ],
                    'submenus' => [],
                ],
            ],
        ],
    ],
];
