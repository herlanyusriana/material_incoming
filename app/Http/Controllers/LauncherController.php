<?php

namespace App\Http\Controllers;

use App\Support\Menu;
use Illuminate\Http\Request;

class LauncherController extends Controller
{
    /**
     * Home launcher: grid of modules the user has access to.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('launcher.index', [
            'modules' => Menu::visibleModules($user),
        ]);
    }
}
