<div id="toast-stack" class="pointer-events-none fixed bottom-4 right-4 z-[100] flex max-w-sm flex-col gap-2 sm:bottom-6 sm:right-6" aria-live="polite" aria-atomic="true"></div>

@if(session('success'))
    <div data-flash-toast data-type="success" data-message="{{ e(session('success')) }}" hidden></div>
@endif
@if(session('error'))
    <div data-flash-toast data-type="error" data-message="{{ e(session('error')) }}" hidden></div>
@endif
@if(session('warning'))
    <div data-flash-toast data-type="warning" data-message="{{ e(session('warning')) }}" hidden></div>
@endif
@if(session('info'))
    <div data-flash-toast data-type="info" data-message="{{ e(session('info')) }}" hidden></div>
@endif
