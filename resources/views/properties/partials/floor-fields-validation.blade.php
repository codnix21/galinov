@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const floor = document.getElementById('etazh');
    const total = document.getElementById('vsego_etazhey');
    const tip = document.getElementById('tip');
    if (!floor || !total) {
        return;
    }

    function syncFloorMax() {
        const t = parseInt(total.value, 10);
        if (!isNaN(t) && t >= 1) {
            floor.max = String(t);
        } else {
            floor.removeAttribute('max');
        }
    }

    function validateOnSubmit(e) {
        if (tip && tip.value === 'land') {
            return;
        }
        const f = parseInt(floor.value, 10);
        const t = parseInt(total.value, 10);
        if (!isNaN(f) && !isNaN(t) && f > t) {
            e.preventDefault();
            alert('Этаж (' + f + ') не может быть больше общего количества этажей (' + t + ').');
            floor.focus();
        }
    }

    total.addEventListener('input', syncFloorMax);
    floor.addEventListener('input', syncFloorMax);
    syncFloorMax();

    const form = floor.closest('form');
    if (form) {
        form.addEventListener('submit', validateOnSubmit);
    }
});
</script>
@endpush
