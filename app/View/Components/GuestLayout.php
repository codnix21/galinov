<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Макет страниц для гостей (вход, регистрация и т.п.).
 */
class GuestLayout extends Component
{
    /**
     * Подключить шаблон layouts.guest.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
