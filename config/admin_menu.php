<?php

return [
    'groups' => [
        [
            'key' => 'keuangan',
            'label' => 'Keuangan',
            'icon' => 'account_balance_wallet',
            'items' => [
                [
                    'key' => 'daftar_tagihan',
                    'label' => 'Daftar Tagihan',
                    'icon' => 'receipt_long',
                    'required_permissions' => ['tagihan view'],
                    'endpoint' => '/api/admin/tagihans',
                ],
                [
                    'key' => 'pemasukans',
                    'label' => 'Pemasukan',
                    'icon' => 'trending_up',
                    'required_permissions' => ['pemasukan view'],
                    'endpoint' => '/api/admin/pemasukans',
                ],
                [
                    'key' => 'pengeluarans',
                    'label' => 'Pengeluaran',
                    'icon' => 'trending_down',
                    'required_permissions' => ['pengeluaran view'],
                    'endpoint' => '/api/admin/pengeluarans',
                ],
                [
                    'key' => 'topups',
                    'label' => 'Topup',
                    'icon' => 'add_card',
                    'required_permissions' => ['topup view'],
                    'endpoint' => '/api/admin/topups',
                ],
                [
                    'key' => 'withdraws',
                    'label' => 'Withdraw',
                    'icon' => 'price_check',
                    'required_permissions' => ['withdraw view'],
                    'endpoint' => '/api/admin/withdraws',
                ],
            ],
        ],
        [
            'key' => 'data_pelanggan',
            'label' => 'Data Pelanggan',
            'icon' => 'folder_shared',
            'items' => [
                [
                    'key' => 'request_pelanggan',
                    'label' => 'Request Data Pelanggan',
                    'icon' => 'person_add',
                    'required_permissions' => ['pelanggan view'],
                    'endpoint' => '/api/admin/request-pelanggan',
                ],
                [
                    'key' => 'daftar_pelanggan',
                    'label' => 'Daftar Pelanggan',
                    'icon' => 'groups',
                    'required_permissions' => ['pelanggan view'],
                    'endpoint' => '/api/admin/pelanggans',
                ],
            ],
        ],
        [
            'key' => 'tiket_aduan',
            'label' => 'Tiket Aduan',
            'icon' => 'support_agent',
            'items' => [
                [
                    'key' => 'daftar_tiket',
                    'label' => 'Daftar Tiket',
                    'icon' => 'confirmation_number',
                    'required_permissions' => ['tiket aduan view'],
                    'endpoint' => '/api/admin/tiket-aduans',
                ],
            ],
        ],
        [
            'key' => 'informasi_management',
            'label' => 'Informasi Management',
            'icon' => 'campaign',
            'items' => [
                [
                    'key' => 'daftar_informasi',
                    'label' => 'Daftar Informasi',
                    'icon' => 'feed',
                    'required_permissions' => ['informasi management view'],
                    'endpoint' => '/api/admin/informasi-management',
                ],
            ],
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventory',
            'icon' => 'inventory_2',
            'items' => [
                [
                    'key' => 'barangs',
                    'label' => 'Barang',
                    'icon' => 'inventory',
                    'required_permissions' => ['barang view'],
                    'endpoint' => '/api/admin/barangs',
                ],
                [
                    'key' => 'transaksi_stock_in',
                    'label' => 'Transaksi Stock Masuk',
                    'icon' => 'south_west',
                    'required_permissions' => ['transaksi stock in view'],
                    'endpoint' => '/api/admin/transaksi/stock-in',
                ],
                [
                    'key' => 'transaksi_stock_out',
                    'label' => 'Transaksi Stock Keluar',
                    'icon' => 'north_east',
                    'required_permissions' => ['transaksi stock out view'],
                    'endpoint' => '/api/admin/transaksi/stock-out',
                ],
                [
                    'key' => 'kategori_barangs',
                    'label' => 'Kategori Barang',
                    'icon' => 'category',
                    'required_permissions' => ['kategori barang view'],
                    'endpoint' => '/api/admin/kategori-barangs',
                ],
            ],
        ],
        [
            'key' => 'investor',
            'label' => 'Investor',
            'icon' => 'handshake',
            'items' => [
                [
                    'key' => 'investor_dashboard',
                    'label' => 'Dashboard Investor',
                    'icon' => 'grid_view',
                    'required_permissions' => ['investor view'],
                    'endpoint' => '/api/admin/investor/dashboard',
                ],
                [
                    'key' => 'investor_payout_requests',
                    'label' => 'Request Payout',
                    'icon' => 'request_quote',
                    'required_permissions' => ['investor payout approve', 'investor payout request'],
                    'endpoint' => '/api/admin/investor-payout-requests',
                ],
                [
                    'key' => 'investor_payout_accounts',
                    'label' => 'Rekening / E-Wallet',
                    'icon' => 'account_balance',
                    'required_permissions' => ['investor payout approve', 'investor payout request'],
                    'endpoint' => '/api/admin/investor/payout-accounts',
                ],
                [
                    'key' => 'investor_inventory',
                    'label' => 'Inventory Investor',
                    'icon' => 'inventory',
                    'required_permissions' => ['investor view'],
                    'endpoint' => '/api/admin/investor/inventory',
                ],
            ],
        ],
        [
            'key' => 'pppoe',
            'label' => 'PPOE',
            'icon' => 'router',
            'items' => [
                [
                    'key' => 'ppp_profiles',
                    'label' => 'Profile PPP',
                    'icon' => 'badge',
                    'required_permissions' => ['profile pppoe view'],
                    'endpoint' => '/api/admin/ppp/profiles',
                ],
                [
                    'key' => 'ppp_secrets',
                    'label' => 'Secret PPP',
                    'icon' => 'vpn_key',
                    'required_permissions' => ['secret ppp view'],
                    'endpoint' => '/api/admin/ppp/secrets',
                ],
                [
                    'key' => 'ppp_active',
                    'label' => 'Active PPP',
                    'icon' => 'wifi_tethering',
                    'required_permissions' => ['active ppp view'],
                    'endpoint' => '/api/admin/ppp/active',
                ],
                [
                    'key' => 'ppp_non_active',
                    'label' => 'Non Active PPP',
                    'icon' => 'signal_wifi_off',
                    'required_permissions' => ['non active ppp view'],
                    'endpoint' => '/api/admin/ppp/non-active',
                ],
            ],
        ],
    ],
];
