<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UtilitiesHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:user view|role & permission view|activity log view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'users',
                'label' => 'Users',
                'permission' => 'user view',
                'src' => route('users.index', ['embed' => 1]),
            ],
            [
                'key' => 'roles',
                'label' => 'Roles & permissions',
                'permission' => 'role & permission view',
                'src' => route('roles.index', ['embed' => 1]),
            ],
            [
                'key' => 'logs',
                'label' => 'Activity Logs',
                'permission' => 'activity log view',
                'src' => route('activity-logs.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'users');
        return view('hub.tabs', [
            'title' => 'Utilities',
            'subtitle' => 'Users, roles, dan activity logs.',
            'routeName' => 'utilities-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

