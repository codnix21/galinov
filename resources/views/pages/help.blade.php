{{-- Страница «Помощь». --}}
@extends('layouts.app')

@section('title', 'Помощь')

@section('content')
<div class="max-w-4xl mx-auto" id="help-page">
    <div class="mb-6">
        <h1 class="text-4xl font-bold mb-2">Помощь</h1>
        <p class="text-gray-600">Справка по разделам сайта. Введите слово в поле ниже, чтобы оставить только подходящие блоки.</p>
    </div>

    <div class="sticky top-16 z-40 -mx-1 px-1 py-3 mb-6 bg-white/95 backdrop-blur-sm border-b border-gray-200">
        <label for="help-search" class="sr-only">Поиск по странице помощи</label>
        <input
            type="search"
            id="help-search"
            class="form-input w-full"
            placeholder="Например: модерация, договор, избранное, черновик…"
            autocomplete="off"
        >
        <p id="help-search-meta" class="text-sm text-gray-500 mt-2 hidden" aria-live="polite"></p>
        <p id="help-search-empty" class="text-sm text-amber-800 mt-2 hidden">По запросу ничего не найдено — попробуйте другие слова или сбросьте поиск.</p>
    </div>

    <div class="space-y-6" id="help-sections">
        <section
            class="help-section card p-8"
            data-keywords="роли гость клиент риэлтор администратор авторизация регистрация вход"
        >
            <h2 class="text-2xl font-bold mb-4">Роли и доступ</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li><strong>Гость</strong> — просмотр каталога объявлений, ипотечного калькулятора и этой справки без входа.</li>
                <li><strong>Клиент</strong> — личный кабинет, избранное, договоры, создание своих объявлений (как правило продажа/сдача своего жилья). При регистрации можно выбрать этот тип аккаунта.</li>
                <li><strong>Риэлтор</strong> — можно выбрать при регистрации или назначить администратор; доступ к модерации, обучению и расширенной работе с объявлениями.</li>
                @auth
                    @if($showStaffHelp)
                        <li>Для риэлтора дополнительно: очередь <strong>модерации</strong> (кроме одобрения/отклонения <em>собственных</em> объявлений), раздел <a href="{{ route('pages.training') }}" class="underline text-brand-700">«Обучение»</a> с программой и PDF-памятками.</li>
                        <li><strong>Администратор</strong> — полная админ-панель: пользователи, все объявления и договоры, отчёты, модерация (включая свои объявления при необходимости).</li>
                    @endif
                @endauth
            </ul>
            <p class="text-gray-700 mt-4">Заблокированный аккаунт не может пользоваться закрытыми разделами до разблокировки администратором.</p>
            @if($showStaffHelp)
                <p class="text-gray-700 mt-4 border-t border-gray-200 pt-4">
                    <strong>Сотрудникам:</strong> раздел
                    <a href="{{ route('moderation.index') }}" class="underline hover:no-underline">«Модерация»</a>
                    в шапке — очередь объявлений на проверке.
                    @if(auth()->user()->isAdmin())
                        <span class="ml-1"><a href="{{ route('admin.dashboard') }}" class="underline hover:no-underline">Админ-панель</a> — пользователи, все объявления и договоры, отчёты.</span>
                    @endif
                </p>
            @endif
        </section>

        <section
            class="help-section card p-8"
            data-keywords="lean бережливое производство процесс выход logout выйти аккаунт кабинет"
        >
            <h2 class="text-2xl font-bold mb-4">Как устроена система и выход</h2>
            <p class="text-gray-700 mb-4">
                Для преподавателя и сотрудников: <a href="{{ route('pages.process') }}" class="underline text-brand-700">схема процессов агентства</a> (описание для диплома).
                В кабинете и у риэлтора блок <strong>«Следующий шаг»</strong> подсказывает, что сделать дальше.
            </p>
            @auth
                <p class="text-gray-700">
                    <strong>Выйти из аккаунта:</strong> красная кнопка «Выход» в шапке справа (на компьютере) или «Выйти из аккаунта» внизу меню ☰ на телефоне; также кнопка в <a href="{{ route('cabinet.index') }}" class="underline">личном кабинете</a>.
                </p>
            @else
                <p class="text-gray-700">Вход и регистрация — в шапке сайта.</p>
            @endauth
        </section>

        <section
            class="help-section card p-8"
            data-keywords="каталог объявления поиск фильтр тип операция цена квартира дом аренда продажа"
        >
            <h2 class="text-2xl font-bold mb-4">Каталог объявлений</h2>
            <p class="text-gray-700 mb-3">На странице <a href="{{ route('properties.index') }}" class="underline hover:no-underline">«Объявления»</a> доступны поиск по тексту и фильтры: тип недвижимости, операция (продажа/аренда), диапазон цены.</p>
            <p class="text-gray-700">В общем списке показываются только <strong>опубликованные</strong> (прошедшие модерацию и активные) объявления. Карточки со статусами «черновик», «на модерации» или «отклонено» для чужих пользователей недоступны.</p>
        </section>

        <section
            class="help-section card p-8"
            data-keywords="избранное звезда сохранить"
        >
            <h2 class="text-2xl font-bold mb-4">Избранное</h2>
            <p class="text-gray-700 mb-3">После входа в шапке доступна кнопка со звездой — раздел избранного. Туда можно добавить только <strong>активные</strong> объявления из каталога.</p>
            <p class="text-gray-700">Удаление из избранного — на той же странице объявления или в списке избранного.</p>
        </section>

        @if($showRealtorTraining ?? false)
        <section
            class="help-section"
            data-keywords="обучение риэлтор холодные горячие звонки объявления тренинг pdf памятка"
            id="help-training"
        >
            @include('partials.realtor-training')
            <p class="text-sm text-gray-600 mt-4">
                <a href="{{ route('pages.training') }}" class="btn-primary inline-flex">Открыть полную программу обучения</a>
            </p>
        </section>
        @endif

        <section
            class="help-section card p-8"
            id="help-listings"
            data-keywords="создать объявление черновик фото загрузка редактировать публикация модерация статус работа с объявлениями"
        >
            <h2 class="text-2xl font-bold mb-4">Создание и публикация объявления</h2>
            <ol class="list-decimal list-inside space-y-2 text-gray-700 mb-4">
                <li>Войдите в систему и откройте <a href="{{ route('cabinet.index') }}" class="underline hover:no-underline">личный кабинет</a> или сразу <a href="{{ route('properties.create') }}" class="underline hover:no-underline">«Создать объявление»</a>.</li>
                <li>Заполните поля формы, при необходимости добавьте до 10 фотографий.</li>
                <li>Статус <strong>«Черновик»</strong> — объявление сохранено, в каталоге не отображается.</li>
                <li>Вариант отправки на публикацию (в форме это может отображаться как отправка на проверку) переводит объявление в статус <strong>«На модерации»</strong> — его проверяет сотрудник.</li>
                <li>После <strong>одобрения</strong> объявление становится активным и появляется в каталоге.</li>
            </ol>
            <p class="text-gray-700 mb-2">Черновики собраны на странице <a href="{{ route('properties.drafts') }}" class="underline hover:no-underline">«Мои черновики»</a>; оттуда можно снова отправить объект на модерацию.</p>
            <p class="text-gray-700">При <strong>отклонении</strong> модератором объявление возвращается в черновик, а на карточке объекта отображается <strong>причина отказа</strong> — исправьте текст или данные и отправьте снова.</p>
        </section>

        <section
            class="help-section card p-8"
            data-keywords="цензура мат ненормативная лексика запрещённые слова текст название описание отклонено"
        >
            <h2 class="text-2xl font-bold mb-4">Текст объявления и правила площадки</h2>
            <p class="text-gray-700 mb-3">В <strong>названии</strong> и <strong>описании</strong> недвижимости нельзя использовать ненормативную лексику и слова из словаря запрета системы (<code class="text-sm bg-gray-100 px-1 rounded">config/censor.php</code>). Такой текст <strong>не будет сохранён</strong>: форма покажет сообщение — уберите запрещённые выражения и отправьте снова.</p>
            @auth
                <p class="text-gray-700 mb-3">То же правило действует для поля <strong>«О себе»</strong> в профиле, <strong>примечаний к договору</strong>@if($showStaffHelp) и текста <strong>причины отказа</strong> при модерации@endif: при совпадении со словарём сохранение не выполняется.</p>
                @if($showStaffHelp)
                    <p class="text-gray-700">Сотрудник не сможет одобрить объявление, если запрещённые слова всё ещё присутствуют в названии или описании.</p>
                @endif
            @endauth
        </section>

        @if($showStaffHelp)
        <section
            class="help-section card p-8"
            data-keywords="модерация очередь одобрить отклонить причина сотрудник"
        >
            <h2 class="text-2xl font-bold mb-4">Модерация объявлений</h2>
            <p class="text-gray-700 mb-3">Раздел «Модерация» в шапке доступен администраторам и риэлторам — <a href="{{ route('moderation.index') }}" class="underline hover:no-underline">перейти к очереди</a>. В очереди — объявления со статусом «на модерации».</p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li><strong>Одобрить</strong> — объявление публикуется в каталоге.</li>
                <li><strong>Отклонить</strong> — нужно указать причину (её увидит автор на странице объявления).</li>
                <li>Риэлтор не может одобрять или отклонять <em>свои</em> объявления; администратор может.</li>
            </ul>
        </section>
        @endif

        <section
            class="help-section card p-8"
            data-keywords="договор контракт клиент риэлтор подпись скан аренда купля"
        >
            <h2 class="text-2xl font-bold mb-4">Договоры</h2>
            <p class="text-gray-700 mb-3">В разделе «Договоры» авторизованные пользователи создают и просматривают договоры по объектам. Участники — клиент и риэлтор; статусы включают черновик, ожидание подтверждения, активный, завершённый и отменённый.</p>
            <p class="text-gray-700 mb-3">Риэлтору доступен список <strong>всех</strong> договоров, ожидающих подтверждения риэлтора — любой риэлтор может подтвердить или отклонить, не только назначенный по договору. Договоры, созданные риэлтором, подтверждает клиент. Для аренды может потребоваться дата окончания, печатная форма и скан подписанного документа.</p>
            <p class="text-gray-700">История изменений по связанным объектам может отображаться в карточке объявления для владельца и администратора.</p>
        </section>

        <section
            class="help-section card p-8"
            data-keywords="кабинет профиль пароль аватар биография телефон"
        >
            <h2 class="text-2xl font-bold mb-4">Личный кабинет и профиль</h2>
            <p class="text-gray-700 mb-3"><a href="{{ route('cabinet.index') }}" class="underline hover:no-underline">Личный кабинет</a> показывает краткую сводку: объявления, в том числе на модерации, и ссылки на действия.</p>
            <p class="text-gray-700">Профиль редактируется в разделе <a href="{{ route('profile.edit') }}" class="underline hover:no-underline">настроек профиля</a>: ФИО, контакты, фото, текст «о себе». Для «о себе» действуют те же ограничения по словарю, что и для остальных проверяемых полей: при нарушении профиль не сохранится.</p>
        </section>

        <section
            class="help-section card p-8"
            data-keywords="ипотека калькулятор кредит взнос ставка срок платёж"
        >
            <h2 class="text-2xl font-bold mb-4">Ипотечный калькулятор</h2>
            <p class="text-gray-700 mb-3">На странице <a href="{{ route('pages.mortgage-calculator') }}" class="underline hover:no-underline">«Ипотечный калькулятор»</a> укажите стоимость жилья, первоначальный взнос, срок и ставку — система рассчитает ориентировочный платёж.</p>
            <p class="text-gray-700 text-sm">Результат носит ознакомительный характер и не заменяет условия банка.</p>
        </section>

        @if($showStaffHelp)
        <section
            class="help-section card p-8"
            data-keywords="админ панель пользователи блокировка отчёт csv xlsx pdf дашборд"
        >
            <h2 class="text-2xl font-bold mb-4">Администратору и риэлтору</h2>
            <p class="text-gray-700 mb-3">В админ-панели ведутся пользователи (в том числе блокировка; свою учётную запись нельзя заблокировать или удалить), все объявления и договоры, экспорт отчётов (в том числе PDF, CSV, Excel — по настройкам проекта). Риэлторам для работы доступны модерация и договоры; полный доступ к панели — у роли администратор.@if(auth()->user()->isAdmin()) <a href="{{ route('admin.dashboard') }}" class="underline hover:no-underline">Открыть админ-панель</a>. @endif</p>
            <p class="text-gray-700">Файлы изображений объявлений хранятся в хранилище; при проблемах с ярлыком <code class="text-sm bg-gray-100 px-1">public/storage</code> на сервере доступна раздача через префикс <code class="text-sm bg-gray-100 px-1">/media/…</code>.</p>
        </section>
        @endif

        <section
            class="help-section card p-8"
            data-keywords="контакты телефон email адрес поддержка связь"
        >
            <h2 class="text-2xl font-bold mb-4">Контакты агентства</h2>
            <p class="text-gray-700 mb-4">Подробнее об агентстве — на странице <a href="{{ route('pages.about-contacts') }}" class="underline hover:no-underline">«О нас и контакты»</a>.</p>
            <div class="space-y-2 text-gray-700">
                <p><strong>Телефон:</strong> <a href="tel:+79991234567" class="hover:underline">+7 (999) 123-45-67</a></p>
                <p><strong>Email:</strong> <a href="mailto:info@realestate.ru" class="hover:underline">info@realestate.ru</a></p>
                <p><strong>Адрес:</strong> г. Иркутск, ул. Ленина, д. 5а</p>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var input = document.getElementById('help-search');
    var sections = document.querySelectorAll('#help-sections .help-section');
    var emptyMsg = document.getElementById('help-search-empty');
    var meta = document.getElementById('help-search-meta');
    if (!input || !sections.length) return;

    function run() {
        var q = input.value.trim().toLowerCase().replace(/\s+/g, ' ');
        var visible = 0;
        sections.forEach(function (sec) {
            var keywords = (sec.getAttribute('data-keywords') || '') + ' ' + sec.textContent;
            var match = !q || keywords.toLowerCase().indexOf(q) !== -1;
            sec.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        emptyMsg.classList.toggle('hidden', visible !== 0 || !q);
        if (!q) {
            meta.classList.add('hidden');
            meta.textContent = '';
        } else {
            meta.textContent = 'Показано разделов: ' + visible + ' из ' + sections.length;
            meta.classList.remove('hidden');
        }
    }

    input.addEventListener('input', run);
    input.addEventListener('search', run);
})();
</script>
@endpush
@endsection
