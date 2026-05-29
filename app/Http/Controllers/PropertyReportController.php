<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertyReportController extends Controller
{
    public function show(Property $property): View
    {
        $viewer = Auth::user();

        if (!PropertyReportService::canView($property, $viewer)) {
            abort(404);
        }

        $report = PropertyReportService::build($property, $viewer);

        return view('properties.report', $report);
    }
}
