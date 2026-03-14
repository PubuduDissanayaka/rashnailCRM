<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RoutingController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard/index', [
            'user' => Auth::user(),
        ]);
    }

    public function root(Request $request, $first)
    {
        // Check if it's a .well-known path and return 404 to prevent errors
        if (str_starts_with($first, '.well-known')) {
            return response()->json([], 404);
        }

        // Check if user has access to this specific page
        $this->checkUserAccess($first);

        $data = ['user' => Auth::user()];

        // Check if view exists, otherwise try index
        if (view()->exists($first . '.index')) {
            return view($first . '.index', $data);
        }

        return view($first, $data);
    }

    public function secondLevel(Request $request, $first, $second)
    {
        // Check if it's a .well-known path and return 404 to prevent errors
        if (str_starts_with($first, '.well-known')) {
            return response()->json([], 404);
        }

        // Check if user has access to this specific page
        $this->checkUserAccess($first . '.' . $second);

        $data = ['user' => Auth::user()];

        // Check if view exists, otherwise try index
        $viewPath = $first . '.' . $second;
        if (view()->exists($viewPath . '.index')) {
            return view($viewPath . '.index', $data);
        }

        return view($viewPath, $data);
    }

    public function thirdLevel(Request $request, $first, $second, $third)
    {
        // Check if it's a .well-known path and return 404 to prevent errors
        if (str_starts_with($first, '.well-known')) {
            return response()->json([], 404);
        }

        // Check if user has access to this specific page
        $this->checkUserAccess($first . '.' . $second . '.' . $third);

        $data = ['user' => Auth::user()];

        // Check if view exists, otherwise try index
        $viewPath = $first . '.' . $second . '.' . $third;
        if (view()->exists($viewPath . '.index')) {
            return view($viewPath . '.index', $data);
        }

        return view($viewPath, $data);
    }

    private function checkUserAccess($viewName)
    {
        // For pages that should be restricted based on roles, we can add checks here
        // For now, we're just ensuring the user is authenticated via middleware
        // More granular control can be added as needed
    }
}
