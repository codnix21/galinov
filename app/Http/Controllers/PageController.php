<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Support\LeanWorkflow;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Простые информационные страницы сайта (без сложной бизнес-логики).
 */
class PageController extends Controller
{
    /**
     * Страница «О нас и контакты».
     */
    public function aboutContacts(): View
    {
        return view('pages.about-contacts');
    }

    /**
     * Справка / помощь. Для сотрудников показывается расширенный блок.
     */
    public function help(): View
    {
        $showStaffHelp = Auth::check() && Auth::user()->isStaff();
        $showRealtorTraining = Auth::check() && Auth::user()->isRealtor();

        return view('pages.help', compact('showStaffHelp', 'showRealtorTraining'));
    }

    /**
     * Ипотечный калькулятор (страница с формой на фронте).
     */
    public function mortgageCalculator(Request $request): View
    {
        $property = null;
        if ($request->filled('property_id')) {
            $property = Property::query()
                ->with('cityRelation')
                ->find($request->integer('property_id'));
        }

        return view('pages.mortgage-calculator', [
            'property' => $property,
        ]);
    }

    /** Карта предметной области и Lean-процессов. */
    public function process(): View
    {
        return view('pages.process', [
            'valueStream' => LeanWorkflow::valueStream(),
            'principles' => LeanWorkflow::principles(),
        ]);
    }

    /**
     * Полная страница обучения для риэлторов (middleware realtor.training).
     */
    public function training(): View
    {
        return view('pages.training');
    }

    /**
     * Скачать PDF-памятку по теме обучения (cold, hot, listings).
     */
    public function trainingPdf(string $topic): Response
    {
        $map = [
            'cold' => ['view' => 'training.pdf.cold-calls', 'file' => 'pamyatka-holodnye-zvonki.pdf'],
            'hot' => ['view' => 'training.pdf.hot-calls', 'file' => 'pamyatka-goryachie-zvonki.pdf'],
            'listings' => ['view' => 'training.pdf.listings', 'file' => 'pamyatka-obyavleniya.pdf'],
        ];

        if (!isset($map[$topic])) {
            abort(404);
        }

        $pdf = Pdf::loadView($map[$topic]['view']);

        return $pdf->download($map[$topic]['file']);
    }
}
