{{-- Подменю CRM риэлтора --}}
<nav class="flex flex-wrap gap-2 mb-8 p-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
    <a href="{{ route('realtor.dashboard') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.dashboard') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Дашборд</a>
    <a href="{{ route('realtor.clients.index') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.clients.*') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Клиенты</a>
    <a href="{{ route('realtor.tasks.index') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.tasks.*') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Задачи</a>
    <a href="{{ route('realtor.showings.index') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.showings.*') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Показы</a>
    <a href="{{ route('realtor.collections.index') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.collections.*') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Подборки</a>
    <a href="{{ route('realtor.properties') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.properties') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Мои объекты</a>
    <a href="{{ route('realtor.inquiries.index') }}" class="px-3 py-2 rounded-xl text-sm {{ request()->routeIs('realtor.inquiries.*') ? 'bg-brand-100 text-brand-900 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Заявки</a>
</nav>
