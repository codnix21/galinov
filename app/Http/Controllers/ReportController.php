<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Отчёты для администратора: сводка за период, экспорт в PDF, CSV и Excel.
 */
class ReportController extends Controller
{
    /**
     * Только администратор может смотреть и выгружать отчёты.
     */
    private function checkAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }
    }

    /**
     * Собирает все данные отчёта за выбранный период и фильтры (общая логика для экрана и экспорта).
     *
     * @return array{
     *   dateFrom: string,
     *   dateTo: string,
     *   dateFromCarbon: Carbon,
     *   dateToCarbon: Carbon,
     *   filterPropertyStatus: ?string,
     *   filterContractStatus: ?string,
     *   filterPropertyTip: ?string,
     *   filterUserRole: ?string,
     *   sortTop: string,
     *   sortDir: string,
     *   propertiesStats: array,
     *   contractsStats: array,
     *   usersStats: array,
     *   dailyProperties: \Illuminate\Support\Collection,
     *   propertiesByType: \Illuminate\Support\Collection,
     *   topUsersByProperties: \Illuminate\Support\Collection
     * }
     */
    private function sformirovatDannyeOtchetov(Request $request): array
    {
        // По умолчанию — последняя неделя до сегодня
        $dateFrom = $request->input('date_from', Carbon::now()->subWeek()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'filter_property_status' => 'nullable|in:draft,active,pending_review,sold,rented,inactive',
            'filter_contract_status' => 'nullable|in:draft,pending,active,completed,cancelled',
            'filter_property_tip' => 'nullable|in:apartment,house,commercial,land',
            'filter_user_role' => 'nullable|in:client,realtor,admin,guest',
            'sort_top' => 'nullable|in:properties_count,familia,email_polzovatela',
            'sort_dir' => 'nullable|in:asc,desc',
        ]);

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        $filterPropertyStatus = $request->input('filter_property_status');
        $filterContractStatus = $request->input('filter_contract_status');
        $filterPropertyTip = $request->input('filter_property_tip');
        $filterUserRole = $request->input('filter_user_role');
        $sortTop = $request->input('sort_top', 'properties_count');
        $sortDir = $request->input('sort_dir', 'desc');

        // Базовые запросы по дате создания; дальше клонируем для разных срезов (clone не меняет исходный builder)
        $propertyBase = Property::query()->whereBetween('sozdano_at', [$dateFromCarbon, $dateToCarbon]);
        if ($filterPropertyStatus) {
            $sid = PropertyStatus::idFor($filterPropertyStatus);
            if ($sid !== null) {
                $propertyBase->where('status_obyavleniya_id', $sid);
            }
        }
        if ($filterPropertyTip) {
            $propertyBase->where('tip', $filterPropertyTip);
        }

        $contractBase = Contract::query()->whereBetween('sozdano_at', [$dateFromCarbon, $dateToCarbon]);
        if ($filterContractStatus) {
            $cid = ContractStatus::idFor($filterContractStatus);
            if ($cid !== null) {
                $contractBase->where('status_dogovora_id', $cid);
            }
        }

        $userBase = User::query()->whereBetween('sozdano_at', [$dateFromCarbon, $dateToCarbon]);
        if ($filterUserRole) {
            $userBase->whereHas('roleRelation', fn ($q) => $q->where('kod', $filterUserRole));
        }

        $propertiesStats = [
            'total' => (clone $propertyBase)->count(),
            'active' => ($sid = PropertyStatus::idFor('active')) !== null
                ? (clone $propertyBase)->where('status_obyavleniya_id', $sid)->count() : 0,
            'pending_review' => ($sid = PropertyStatus::idFor('pending_review')) !== null
                ? (clone $propertyBase)->where('status_obyavleniya_id', $sid)->count() : 0,
            'draft' => ($sid = PropertyStatus::idFor('draft')) !== null
                ? (clone $propertyBase)->where('status_obyavleniya_id', $sid)->count() : 0,
            'sold' => ($sid = PropertyStatus::idFor('sold')) !== null
                ? (clone $propertyBase)->where('status_obyavleniya_id', $sid)->count() : 0,
            'inactive' => ($sid = PropertyStatus::idFor('inactive')) !== null
                ? (clone $propertyBase)->where('status_obyavleniya_id', $sid)->count() : 0,
        ];

        $contractsStats = [
            'total' => (clone $contractBase)->count(),
            'active' => ($cid = ContractStatus::idFor('active')) !== null
                ? (clone $contractBase)->where('status_dogovora_id', $cid)->count() : 0,
            'pending' => ($cid = ContractStatus::idFor('pending')) !== null
                ? (clone $contractBase)->where('status_dogovora_id', $cid)->count() : 0,
            'rejected' => ($cid = ContractStatus::idFor('cancelled')) !== null
                ? (clone $contractBase)->where('status_dogovora_id', $cid)->count() : 0,
        ];

        $usersStats = [
            'total' => (clone $userBase)->count(),
            'clients' => (clone $userBase)->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->count(),
            'realtors' => (clone $userBase)->whereHas('roleRelation', fn ($q) => $q->where('kod', 'realtor'))->count(),
        ];

        // Динамика: сколько объявлений создано в каждый день периода
        $dailyProperties = (clone $propertyBase)
            ->select(DB::raw('DATE(sozdano_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d.m.Y'),
                    'count' => $item->count,
                ];
            });

        $propertiesByType = (clone $propertyBase)
            ->select('tip', DB::raw('COUNT(*) as count'))
            ->groupBy('tip')
            ->get()
            ->map(function ($item) {
                $typeNames = Property::tipNazvaniya();

                return [
                    'type' => $typeNames[$item->tip] ?? $item->tip,
                    'count' => $item->count,
                ];
            });

        // Топ-10 авторов объявлений за период (с учётом сортировки из формы)
        $topQuery = User::whereHas('properties', function ($query) use ($propertyBase) {
            $query->whereIn('id', (clone $propertyBase)->select('id'));
        })
            ->withCount(['properties' => function ($query) use ($propertyBase) {
                $query->whereIn('id', (clone $propertyBase)->select('id'));
            }]);

        if ($sortTop === 'properties_count') {
            $topQuery->orderBy('properties_count', $sortDir === 'asc' ? 'asc' : 'desc');
        } elseif ($sortTop === 'familia') {
            $topQuery->orderBy('familia', $sortDir === 'desc' ? 'desc' : 'asc')
                ->orderBy('imya', $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $topQuery->orderBy('email_polzovatela', $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $topUsersByProperties = $topQuery->take(10)->get();

        return compact(
            'dateFrom',
            'dateTo',
            'dateFromCarbon',
            'dateToCarbon',
            'filterPropertyStatus',
            'filterContractStatus',
            'filterPropertyTip',
            'filterUserRole',
            'sortTop',
            'sortDir',
            'propertiesStats',
            'contractsStats',
            'usersStats',
            'dailyProperties',
            'propertiesByType',
            'topUsersByProperties'
        );
    }

    /** Метаданные: кто и когда сформировал отчёт */
    private function metaGeneratorOtchet(): array
    {
        $user = Auth::user();
        $fio = trim(($user->familia ?? '').' '.($user->imya ?? '').' '.($user->otchestvo ?? ''));
        if ($fio === '') {
            $fio = $user->name ?? 'Администратор';
        }

        return [
            'generatedAt' => Carbon::now(),
            'generatedByFio' => $fio,
            'generatedByEmail' => $user->email_polzovatela ?? $user->email ?? '',
            'generatedById' => $user->id,
        ];
    }

    private function dannyeDlyaEksporta(Request $request): array
    {
        return array_merge(
            $this->sformirovatDannyeOtchetov($request),
            $this->metaGeneratorOtchet()
        );
    }

    /**
     * Страница отчётов с фильтрами и графиками на основе sformirovatDannyeOtchetov.
     */
    public function index(Request $request): View
    {
        $this->checkAdmin();

        $data = $this->sformirovatDannyeOtchetov($request);
        $data = array_merge($data, $this->metaGeneratorOtchet());

        return view('admin.reports.index', $data);
    }

    /**
     * Тот же отчёт, что на экране, в виде PDF-файла для скачивания.
     */
    public function exportPdf(Request $request)
    {
        $this->checkAdmin();

        $data = $this->dannyeDlyaEksporta($request);

        $pdf = Pdf::loadView('admin.reports.pdf', $data);

        $filename = 'otchet_'.$data['dateFrom'].'_'.$data['dateTo'].'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Выгрузка сводки в CSV (разделитель «;», UTF-8 с BOM для Excel).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->checkAdmin();

        $d = $this->dannyeDlyaEksporta($request);

        $filename = 'otchet_'.$d['dateFrom'].'_'.$d['dateTo'].'.csv';

        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            // BOM — чтобы кириллица в Excel открывалась без кракозябр
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Отчёт', 'Период '.$d['dateFrom'].' — '.$d['dateTo']], ';');
            fputcsv($out, ['Сформировал', $d['generatedByFio'].' ('.$d['generatedByEmail'].')'], ';');
            fputcsv($out, ['Дата формирования', $d['generatedAt']->format('d.m.Y H:i:s')], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['Объявления — всего', $d['propertiesStats']['total']], ';');
            fputcsv($out, ['Объявления — активные', $d['propertiesStats']['active']], ';');
            fputcsv($out, ['Объявления — черновики', $d['propertiesStats']['draft']], ';');
            fputcsv($out, ['Объявления — продано', $d['propertiesStats']['sold']], ';');
            fputcsv($out, ['Объявления — неактивные', $d['propertiesStats']['inactive']], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['Договоры — всего', $d['contractsStats']['total']], ';');
            fputcsv($out, ['Договоры — активные', $d['contractsStats']['active']], ';');
            fputcsv($out, ['Договоры — на подтверждении', $d['contractsStats']['pending']], ';');
            fputcsv($out, ['Договоры — отклонённые', $d['contractsStats']['rejected']], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['Пользователи — всего', $d['usersStats']['total']], ';');
            fputcsv($out, ['Пользователи — клиенты', $d['usersStats']['clients']], ';');
            fputcsv($out, ['Пользователи — риэлторы', $d['usersStats']['realtors']], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['Объявления по дням', 'Дата', 'Количество'], ';');
            foreach ($d['dailyProperties'] as $row) {
                fputcsv($out, ['', $row['date'], $row['count']], ';');
            }
            fputcsv($out, [], ';');

            fputcsv($out, ['По типам недвижимости', 'Тип', 'Количество'], ';');
            foreach ($d['propertiesByType'] as $row) {
                fputcsv($out, ['', $row['type'], $row['count']], ';');
            }
            fputcsv($out, [], ';');

            fputcsv($out, ['Топ пользователей', 'ФИО', 'Email', 'Объявлений'], ';');
            foreach ($d['topUsersByProperties'] as $u) {
                $fio = trim($u->familia.' '.$u->imya.' '.$u->otchestvo);
                fputcsv($out, ['', $fio, $u->email_polzovatela, $u->properties_count], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Выгрузка в Excel (.xlsx): пишем во временный файл, отдаём потоком, файл удаляем.
     */
    public function exportXlsx(Request $request): StreamedResponse
    {
        $this->checkAdmin();

        $d = $this->dannyeDlyaEksporta($request);
        $filename = 'otchet_'.$d['dateFrom'].'_'.$d['dateTo'].'.xlsx';

        return response()->streamDownload(function () use ($d) {
            $path = tempnam(sys_get_temp_dir(), 'lsx');
            if ($path === false) {
                return;
            }

            $writer = new Writer;
            $writer->openToFile($path);
            $writer->getCurrentSheet()->setName('Сводка');

            // Вспомогательная функция: массив значений → строка листа OpenSpout
            $row = function (array $values): Row {
                $cells = [];
                foreach (array_values($values) as $i => $v) {
                    $cells[$i] = Cell::fromValue($v);
                }

                return new Row($cells);
            };

            $writer->addRow($row(['Отчёт', 'Период '.$d['dateFrom'].' — '.$d['dateTo']]));
            $writer->addRow($row(['Сформировал', $d['generatedByFio'].' ('.$d['generatedByEmail'].')']));
            $writer->addRow($row(['Дата формирования', $d['generatedAt']->format('d.m.Y H:i:s')]));
            $writer->addRow(new Row([]));

            $writer->addRow($row(['Объявления', '']));
            $writer->addRow($row(['Всего', $d['propertiesStats']['total']]));
            $writer->addRow($row(['Активные', $d['propertiesStats']['active']]));
            $writer->addRow($row(['Черновики', $d['propertiesStats']['draft']]));
            $writer->addRow($row(['Продано', $d['propertiesStats']['sold']]));
            $writer->addRow($row(['Неактивные', $d['propertiesStats']['inactive']]));
            $writer->addRow(new Row([]));

            $writer->addRow($row(['Договоры', '']));
            $writer->addRow($row(['Всего', $d['contractsStats']['total']]));
            $writer->addRow($row(['Активные', $d['contractsStats']['active']]));
            $writer->addRow($row(['На подтверждении', $d['contractsStats']['pending']]));
            $writer->addRow($row(['Отклонённые / отменены', $d['contractsStats']['rejected']]));
            $writer->addRow(new Row([]));

            $writer->addRow($row(['Пользователи (за период)', '']));
            $writer->addRow($row(['Всего', $d['usersStats']['total']]));
            $writer->addRow($row(['Клиенты', $d['usersStats']['clients']]));
            $writer->addRow($row(['Риэлторы', $d['usersStats']['realtors']]));
            $writer->addRow(new Row([]));

            $writer->addRow($row(['Объявления по дням', 'Дата', 'Количество']));
            foreach ($d['dailyProperties'] as $item) {
                $writer->addRow($row(['', $item['date'], $item['count']]));
            }
            $writer->addRow(new Row([]));

            $writer->addRow($row(['По типам недвижимости', 'Тип', 'Количество']));
            foreach ($d['propertiesByType'] as $item) {
                $writer->addRow($row(['', $item['type'], $item['count']]));
            }
            $writer->addRow(new Row([]));

            $writer->addRow($row(['Топ пользователей', 'ФИО', 'Email', 'Объявлений']));
            foreach ($d['topUsersByProperties'] as $u) {
                $fio = trim($u->familia.' '.$u->imya.' '.$u->otchestvo);
                $writer->addRow($row(['', $fio, $u->email_polzovatela, $u->properties_count]));
            }

            $writer->close();
            readfile($path);
            unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
