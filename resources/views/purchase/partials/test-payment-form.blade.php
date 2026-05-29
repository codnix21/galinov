<form method="POST" action="{{ route('purchase.pay', $contract) }}" id="paymentForm" class="space-y-4">
    @csrf
    <p class="text-xs text-gray-500">Имитация оплаты для разработки. Деньги не списываются.</p>
    <div>
        <p class="form-label mb-3">Способ</p>
        <div class="flex flex-col sm:flex-row gap-3">
            <label class="flex-1 flex items-center gap-2 p-3 border rounded-xl cursor-pointer has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                <input type="radio" name="payment_method" value="card" checked class="payment-method">
                <span>Карта</span>
            </label>
            <label class="flex-1 flex items-center gap-2 p-3 border rounded-xl cursor-pointer has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                <input type="radio" name="payment_method" value="sbp" class="payment-method">
                <span>СБП</span>
            </label>
        </div>
    </div>
    <div id="cardFields" class="space-y-3">
        <p class="text-xs text-gray-500">4111…1111 — успех; 4000…0002 — отказ</p>
        <input type="text" name="card_number" class="form-input" placeholder="Номер карты" value="4111 1111 1111 1111">
        <div class="grid grid-cols-2 gap-3">
            <input type="text" name="card_expiry" class="form-input" placeholder="MM/YY" value="12/28">
            <input type="text" name="card_cvc" class="form-input" placeholder="CVC" value="123">
        </div>
        <input type="text" name="card_holder" class="form-input" value="{{ Auth::user()->name }}">
    </div>
    <div id="sbpFields" class="hidden">
        <input type="tel" name="sbp_phone" class="form-input" placeholder="+7 900 000-00-00">
    </div>
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="accept_offer" value="1" required class="mt-1">
        <span>Согласен с условиями тестовой оплаты</span>
    </label>
    <button type="submit" class="btn w-full">Тестовая оплата</button>
</form>
@once
    @push('scripts')
        <script>
        document.querySelectorAll('.payment-method').forEach(r => {
            r.addEventListener('change', () => {
                const sbp = document.querySelector('input[value="sbp"]')?.checked;
                document.getElementById('cardFields')?.classList.toggle('hidden', sbp);
                document.getElementById('sbpFields')?.classList.toggle('hidden', !sbp);
            });
        });
        </script>
    @endpush
@endonce
