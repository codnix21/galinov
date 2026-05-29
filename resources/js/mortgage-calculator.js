/**
 * Интерактивный ипотечный калькулятор: аннуитет / дифференцированный, график, нагрузка на доход.
 */

const BANK_PRESETS = [
    { id: 'sber', name: 'Сбербанк', rate: 7.9, color: '#21a038' },
    { id: 'vtb', name: 'ВТБ', rate: 8.2, color: '#002882' },
    { id: 'domrf', name: 'Дом.РФ', rate: 8.5, color: '#e65100' },
    { id: 'alfa', name: 'Альфа-Банк', rate: 9.1, color: '#ef3124' },
    { id: 'custom', name: 'Своя ставка', rate: null, color: '#64748b' },
];

function formatCurrency(amount) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Math.round(amount));
}

function formatNumber(amount) {
    return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(Math.round(amount));
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function calcAnnuity(loanAmount, monthlyRate, months) {
    if (months <= 0 || loanAmount <= 0) {
        return { monthly: 0, totalPayment: 0, totalInterest: 0, schedule: [] };
    }

    let monthly = 0;
    if (monthlyRate > 0) {
        const pow = Math.pow(1 + monthlyRate, months);
        monthly = (loanAmount * monthlyRate * pow) / (pow - 1);
    } else {
        monthly = loanAmount / months;
    }

    const schedule = [];
    let balance = loanAmount;
    for (let m = 1; m <= months; m++) {
        const interest = balance * monthlyRate;
        const principal = monthly - interest;
        balance = Math.max(0, balance - principal);
        schedule.push({ month: m, payment: monthly, principal, interest, balance });
    }

    const totalPayment = monthly * months;

    return {
        monthly,
        totalPayment,
        totalInterest: totalPayment - loanAmount,
        schedule,
    };
}

function calcDifferentiated(loanAmount, monthlyRate, months) {
    if (months <= 0 || loanAmount <= 0) {
        return { monthlyFrom: 0, monthlyTo: 0, totalPayment: 0, totalInterest: 0, schedule: [] };
    }

    const principalPart = loanAmount / months;
    const schedule = [];
    let balance = loanAmount;
    let totalPayment = 0;

    for (let m = 1; m <= months; m++) {
        const interest = balance * monthlyRate;
        const payment = principalPart + interest;
        balance = Math.max(0, balance - principalPart);
        totalPayment += payment;
        schedule.push({ month: m, payment, principal: principalPart, interest, balance });
    }

    return {
        monthlyFrom: schedule[0]?.payment ?? 0,
        monthlyTo: schedule[schedule.length - 1]?.payment ?? 0,
        totalPayment,
        totalInterest: totalPayment - loanAmount,
        schedule,
    };
}

function incomeLoadPercent(monthlyPayment, income) {
    if (!income || income <= 0) {
        return null;
    }

    return (monthlyPayment / income) * 100;
}

function loadLabel(percent) {
    if (percent === null) {
        return null;
    }
    if (percent <= 30) {
        return { text: 'Комфортная нагрузка', class: 'text-green-700 bg-green-50 border-green-200' };
    }
    if (percent <= 40) {
        return { text: 'Приемлемо для банка', class: 'text-amber-800 bg-amber-50 border-amber-200' };
    }

    return { text: 'Высокая нагрузка — банк может отказать', class: 'text-red-800 bg-red-50 border-red-200' };
}

export function initMortgageCalculator(root) {
    const els = {
        price: root.querySelector('#mc-price'),
        priceRange: root.querySelector('#mc-price-range'),
        downPct: root.querySelector('#mc-down-pct'),
        downPctRange: root.querySelector('#mc-down-pct-range'),
        downAmount: root.querySelector('#mc-down-amount'),
        years: root.querySelector('#mc-years'),
        yearsRange: root.querySelector('#mc-years-range'),
        rate: root.querySelector('#mc-rate'),
        rateRange: root.querySelector('#mc-rate-range'),
        income: root.querySelector('#mc-income'),
        paymentType: root.querySelectorAll('input[name="mc_payment_type"]'),
        bankChips: root.querySelector('#mc-bank-chips'),
        monthlyHero: root.querySelector('#mc-monthly-hero'),
        monthlySub: root.querySelector('#mc-monthly-sub'),
        loanAmount: root.querySelector('#mc-loan-amount'),
        totalInterest: root.querySelector('#mc-total-interest'),
        totalPayment: root.querySelector('#mc-total-payment'),
        overpaymentPct: root.querySelector('#mc-overpayment-pct'),
        incomeLoad: root.querySelector('#mc-income-load'),
        incomeLoadBadge: root.querySelector('#mc-income-load-badge'),
        donutPrincipal: root.querySelector('#mc-donut-principal'),
        donutInterest: root.querySelector('#mc-donut-interest'),
        donutLegendPrincipal: root.querySelector('#mc-legend-principal'),
        donutLegendInterest: root.querySelector('#mc-legend-interest'),
        scheduleBody: root.querySelector('#mc-schedule-body'),
        scheduleCount: root.querySelector('#mc-schedule-count'),
        scheduleToggle: root.querySelector('#mc-schedule-toggle'),
        propertyBanner: root.querySelector('#mc-property-banner'),
        priceLabel: root.querySelector('#mc-price-label'),
        downPctLabel: root.querySelector('#mc-down-pct-label'),
        downAmountLabel: root.querySelector('#mc-down-amount-label'),
        yearsLabel: root.querySelector('#mc-years-label'),
        rateLabel: root.querySelector('#mc-rate-label'),
    };

    let showFullSchedule = false;
    let selectedBank = 'vtb';

    function getPaymentType() {
        const checked = root.querySelector('input[name="mc_payment_type"]:checked');

        return checked?.value === 'differentiated' ? 'differentiated' : 'annuity';
    }

    function getMonths() {
        return Math.round(parseFloat(els.years?.value || '0') * 12);
    }

    function syncLabels() {
        if (els.priceLabel && els.price) {
            els.priceLabel.textContent = `${formatNumber(els.price.value)} ₽`;
        }
        if (els.downPctLabel && els.downPct) {
            els.downPctLabel.textContent = els.downPct.value;
        }
        if (els.downAmountLabel && els.downAmount) {
            els.downAmountLabel.textContent = `${formatNumber(els.downAmount.value)} ₽`;
        }
        if (els.yearsLabel && els.years) {
            const y = parseFloat(els.years.value);
            const word = y === 1 ? 'год' : y >= 2 && y <= 4 ? 'года' : 'лет';
            els.yearsLabel.textContent = `${y} ${word}`;
        }
        if (els.rateLabel && els.rate) {
            els.rateLabel.textContent = `${parseFloat(els.rate.value).toLocaleString('ru-RU')}%`;
        }
    }

    function syncDownFromPct() {
        const price = parseFloat(els.price?.value || '0');
        const pct = parseFloat(els.downPct?.value || '0');
        const amount = Math.round((price * pct) / 100);
        if (els.downAmount) {
            els.downAmount.value = String(amount);
        }
        syncLabels();
    }

    function syncDownFromAmount() {
        const price = parseFloat(els.price?.value || '0');
        const amount = parseFloat(els.downAmount?.value || '0');
        if (price > 0 && els.downPct) {
            els.downPct.value = String(clamp(Math.round((amount / price) * 100), 0, 90));
            if (els.downPctRange) {
                els.downPctRange.value = els.downPct.value;
            }
        }
    }

    function pairInputs(main, range, onChange) {
        if (!main || !range) {
            return;
        }
        const sync = (fromRange) => {
            if (fromRange) {
                main.value = range.value;
            } else {
                range.value = main.value;
            }
            syncLabels();
            onChange?.();
            calculate();
        };
        main.addEventListener('input', () => sync(false));
        range.addEventListener('input', () => sync(true));
    }

    function renderBankChips() {
        if (!els.bankChips) {
            return;
        }
        els.bankChips.innerHTML = '';
        BANK_PRESETS.forEach((bank) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'mc-bank-chip' + (selectedBank === bank.id ? ' mc-bank-chip--active' : '');
            btn.dataset.bankId = bank.id;
            btn.innerHTML = bank.rate !== null
                ? `<span class="font-medium">${bank.name}</span><span class="text-xs opacity-80">${bank.rate.toLocaleString('ru-RU')}%</span>`
                : `<span class="font-medium">${bank.name}</span>`;
            if (bank.color && selectedBank === bank.id) {
                btn.style.borderColor = bank.color;
            }
            btn.addEventListener('click', () => {
                selectedBank = bank.id;
                if (bank.rate !== null && els.rate) {
                    els.rate.value = String(bank.rate);
                    if (els.rateRange) {
                        els.rateRange.value = String(bank.rate);
                    }
                }
                renderBankChips();
                calculate();
            });
            els.bankChips.appendChild(btn);
        });
    }

    function updateDonut(principal, interest) {
        const total = principal + interest;
        const principalPct = total > 0 ? (principal / total) * 100 : 50;
        const circumference = 2 * Math.PI * 42;
        const principalLen = (principalPct / 100) * circumference;

        if (els.donutPrincipal) {
            els.donutPrincipal.setAttribute('stroke-dasharray', `${principalLen} ${circumference}`);
        }
        if (els.donutInterest) {
            els.donutInterest.setAttribute('stroke-dasharray', `${circumference - principalLen} ${circumference}`);
            els.donutInterest.setAttribute('stroke-dashoffset', String(-principalLen));
        }
        if (els.donutLegendPrincipal) {
            els.donutLegendPrincipal.textContent = formatCurrency(principal);
        }
        if (els.donutLegendInterest) {
            els.donutLegendInterest.textContent = formatCurrency(interest);
        }
    }

    function renderSchedule(schedule) {
        if (!els.scheduleBody) {
            return;
        }
        const limit = showFullSchedule ? schedule.length : Math.min(12, schedule.length);
        els.scheduleBody.innerHTML = '';
        for (let i = 0; i < limit; i++) {
            const row = schedule[i];
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50/80';
            tr.innerHTML = `
                <td class="py-2.5 font-medium">${row.month}</td>
                <td class="py-2.5 text-right">${formatCurrency(row.payment)}</td>
                <td class="py-2.5 text-right text-brand-800">${formatCurrency(row.principal)}</td>
                <td class="py-2.5 text-right text-amber-700">${formatCurrency(row.interest)}</td>
                <td class="py-2.5 text-right text-slate-600">${formatCurrency(row.balance)}</td>
            `;
            els.scheduleBody.appendChild(tr);
        }
        if (els.scheduleCount) {
            els.scheduleCount.textContent = showFullSchedule
                ? `Все ${schedule.length} платежей`
                : `Показаны первые ${limit} из ${schedule.length}`;
        }
    }

    function calculate() {
        const price = parseFloat(els.price?.value || '0');
        const downPayment = parseFloat(els.downAmount?.value || '0');
        const years = parseFloat(els.years?.value || '0');
        const rate = parseFloat(els.rate?.value || '0');
        const income = parseFloat(els.income?.value || '0');
        const months = Math.round(years * 12);
        const monthlyRate = rate / 100 / 12;

        if (price <= 0 || months <= 0 || rate <= 0 || downPayment >= price) {
            return;
        }

        const loanAmount = price - downPayment;
        const type = getPaymentType();
        let monthlyDisplay = 0;
        let monthlySub = '';
        let result;

        if (type === 'differentiated') {
            result = calcDifferentiated(loanAmount, monthlyRate, months);
            monthlyDisplay = result.monthlyFrom;
            monthlySub = `с ${formatCurrency(result.monthlyFrom)} до ${formatCurrency(result.monthlyTo)}`;
        } else {
            result = calcAnnuity(loanAmount, monthlyRate, months);
            monthlyDisplay = result.monthly;
            monthlySub = 'фиксированный аннуитетный платёж';
        }

        const loadPct = incomeLoadPercent(monthlyDisplay, income);
        const load = loadLabel(loadPct);

        if (els.monthlyHero) {
            els.monthlyHero.textContent = formatCurrency(monthlyDisplay);
        }
        if (els.monthlySub) {
            els.monthlySub.textContent = monthlySub;
        }
        if (els.loanAmount) {
            els.loanAmount.textContent = formatCurrency(loanAmount);
        }
        if (els.totalInterest) {
            els.totalInterest.textContent = formatCurrency(result.totalInterest);
        }
        if (els.totalPayment) {
            els.totalPayment.textContent = formatCurrency(result.totalPayment);
        }
        if (els.overpaymentPct) {
            const pct = loanAmount > 0 ? ((result.totalInterest / loanAmount) * 100).toFixed(0) : '0';
            els.overpaymentPct.textContent = `+${pct}% к сумме кредита`;
        }

        updateDonut(loanAmount, result.totalInterest);
        renderSchedule(result.schedule);

        if (els.incomeLoad) {
            els.incomeLoad.textContent = loadPct !== null ? `${loadPct.toFixed(1)}% от дохода` : '—';
        }
        if (els.incomeLoadBadge && load) {
            els.incomeLoadBadge.textContent = load.text;
            els.incomeLoadBadge.className = `text-xs font-medium px-2.5 py-1 rounded-full border ${load.class}`;
            els.incomeLoadBadge.classList.remove('hidden');
        } else if (els.incomeLoadBadge) {
            els.incomeLoadBadge.classList.add('hidden');
        }
    }

    pairInputs(els.price, els.priceRange, syncDownFromPct);
    pairInputs(els.downPct, els.downPctRange, () => {
        syncDownFromPct();
    });
    pairInputs(els.years, els.yearsRange);
    pairInputs(els.rate, els.rateRange, () => {
        selectedBank = 'custom';
        renderBankChips();
    });

    els.downAmount?.addEventListener('input', () => {
        syncDownFromAmount();
        syncLabels();
        calculate();
    });

    els.income?.addEventListener('input', calculate);
    els.paymentType.forEach((el) => el.addEventListener('change', calculate));

    els.scheduleToggle?.addEventListener('click', () => {
        showFullSchedule = !showFullSchedule;
        els.scheduleToggle.textContent = showFullSchedule ? 'Свернуть график' : 'Показать весь график';
        calculate();
    });

    renderBankChips();
    syncDownFromPct();
    calculate();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-mortgage-calculator]').forEach(initMortgageCalculator);
});
