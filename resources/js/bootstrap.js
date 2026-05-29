/**
 * Базовая настройка HTTP-клиента для AJAX-запросов к Laravel.
 */
import axios from 'axios';
window.axios = axios;

// Помечаем запросы как XMLHttpRequest — Laravel так распознаёт AJAX
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        let message = 'Ошибка сети или сервера. Проверьте подключение и повторите попытку.';

        if (!error.response) {
            message = 'Нет соединения с сервером. Проверьте интернет.';
        } else if (status === 422 && error.response.data?.errors) {
            const first = Object.values(error.response.data.errors).flat()[0];
            message = first || 'Проверьте введённые данные.';
        } else if (status === 403) {
            message = 'Доступ запрещён.';
        } else if (status === 404) {
            message = 'Данные не найдены.';
        } else if (status >= 500) {
            message = 'Ошибка на сервере. Попробуйте позже.';
        } else if (error.response.data?.message) {
            message = error.response.data.message;
        }

        if (typeof window.showToast === 'function') {
            window.showToast(message, 'error', 8000);
        }

        return Promise.reject(error);
    },
);

window.addEventListener('offline', () => {
    if (typeof window.showToast === 'function') {
        window.showToast('Нет соединения с интернетом.', 'warning', 0);
    }
});
