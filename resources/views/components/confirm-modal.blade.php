<dialog id="confirm-modal" class="confirm-modal rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/40 max-w-md w-[calc(100%-2rem)]">
    <form method="dialog" class="p-6">
        <h2 id="confirm-modal-title" class="text-lg font-bold text-slate-900">Подтвердите действие</h2>
        <p id="confirm-modal-message" class="mt-2 text-sm text-slate-600"></p>
        <div class="mt-6 flex flex-wrap justify-end gap-2">
            <button type="button" value="cancel" class="btn" id="confirm-modal-cancel">Отмена</button>
            <button type="button" value="ok" class="btn-primary bg-red-600 hover:bg-red-700 border-red-600" id="confirm-modal-ok">Подтвердить</button>
        </div>
    </form>
</dialog>
