<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserPersonalData;
use App\Services\TextCensor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Управление профилем пользователя: просмотр, редактирование и удаление аккаунта.
 */
class ProfileController extends Controller
{
    /**
     * Показать форму редактирования профиля.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'profileDocs' => \App\Support\UserProfileDocuments::summary($user),
        ]);
    }

    /**
     * Сохранить изменения профиля (имя, email, биография, аватар).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $bioErrors = TextCensor::fieldError('biografiya', $validated['biografiya'] ?? null);
            if ($bioErrors !== []) {
                return Redirect::route('profile.edit')->withErrors($bioErrors)->withInput();
            }

            // Обработка загрузки аватара
            if ($request->hasFile('avatar_polzovatela')) {
                // Удаляем старый аватар, если есть
                $oldAvatar = $user->avatar_polzovatela ?? $user->avatar;
                if ($oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
                    Storage::disk('public')->delete($oldAvatar);
                }
                // Сохраняем новый аватар
                $validated['avatar_polzovatela'] = $request->file('avatar_polzovatela')->store('avatars', 'public');
            } else {
                // Если аватар не загружен, не обновляем поле
                unset($validated['avatar_polzovatela']);
            }

            if (array_key_exists('pol', $validated) && $validated['pol'] === '') {
                $validated['pol'] = null;
            }

            $user->fill($validated);

            if ($user->isDirty('email_polzovatela')) {
                $user->email_podtverzhden_at = null;
            }

            $user->save();

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } catch (\Exception $e) {
            return Redirect::route('profile.edit')
                ->withInput()
                ->with('error', 'Не удалось сохранить профиль: '.$e->getMessage());
        }
    }

    public function updatePersonalData(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pasport_seriya_nomer' => ['nullable', 'string', 'max:11', 'regex:/^\d{4}\s?\d{6}$/u'],
            'pasport_kem_vydan' => ['nullable', 'string', 'max:255'],
            'pasport_data_vydachi' => ['nullable', 'date', 'before_or_equal:today', 'after:1990-01-01'],
            'inn' => ['nullable', 'string', 'max:12', 'regex:/^\d{10,12}$/u'],
            'snils' => ['nullable', 'string', 'max:14', 'regex:/^\d{3}-?\d{3}-?\d{3}\s?\d{2}$/u'],
        ], [
            'pasport_seriya_nomer.regex' => 'Паспорт: 4 цифры серии и 6 цифр номера (например 1234 567890).',
            'pasport_seriya_nomer.max' => 'Серия и номер — не более 11 символов.',
            'inn.regex' => 'ИНН: только цифры, 10 или 12 знаков.',
            'inn.max' => 'ИНН — не более 12 цифр.',
            'snils.regex' => 'СНИЛС: формат XXX-XXX-XXX XX или 11 цифр подряд.',
            'snils.max' => 'СНИЛС — не более 14 символов.',
            'pasport_data_vydachi.before_or_equal' => 'Дата выдачи не может быть в будущем.',
        ]);

        $user = $request->user();

        if (!empty($validated['pasport_seriya_nomer'])) {
            $digits = preg_replace('/\D/u', '', $validated['pasport_seriya_nomer']);
            $validated['pasport_seriya_nomer'] = substr($digits, 0, 4) . ' ' . substr($digits, 4, 6);
        }
        if (!empty($validated['inn'])) {
            $validated['inn'] = preg_replace('/\D/u', '', $validated['inn']);
        }
        if (!empty($validated['snils'])) {
            $d = preg_replace('/\D/u', '', $validated['snils']);
            if (strlen($d) === 11) {
                $validated['snils'] = substr($d, 0, 3) . '-' . substr($d, 3, 3) . '-' . substr($d, 6, 3) . ' ' . substr($d, 9, 2);
            }
        }

        UserPersonalData::updateOrCreate(
            ['polzovatel_id' => (int) $user->id],
            $validated
        );

        return Redirect::back()->with('status', 'personal-updated');
    }

    /**
     * Удалить аккаунт пользователя после подтверждения пароля.
     */
    public function updateTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_chat_id' => ['nullable', 'string', 'max:32'],
        ]);

        $request->user()->update($validated);

        return Redirect::route('profile.edit')->with('status', 'telegram-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
