<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Основной макет для авторизованных пользователей.
 */
class AppLayout extends Component
{
    /**
     * Подключить шаблон layouts.app.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
