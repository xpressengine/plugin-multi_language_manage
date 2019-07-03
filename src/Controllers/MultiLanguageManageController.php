<?php

namespace Xpressengine\Plugins\MultiLanguageManage\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Xpressengine\Http\Request;

class MultiLanguageManageController extends Controller
{
    public function index(Request $request)
    {
        $locales = $this->getLocaleInformation($request);

        $paginate = $this->getLangsQuery($request)->paginate(20 * count($locales), ['*'], 'page')
            ->appends($request->except('page'));
        $langs = $this->getMakeLangsForView($request, $paginate, $locales);

        return \XePresenter::make('multi_language_manage::views.index', compact('locales', 'langs'));
    }

    private function getLocaleInformation(Request $request)
    {
        $localeConfig = config('xe.lang.locales');
        $locales = [];

        if ($searchLocale = $request->get('locale')) {
            $locales[$searchLocale] = array_search($searchLocale, $localeConfig);
        } else {
            foreach ($localeConfig as $idx => $locale) {
                $locales[$locale] = $idx;
            }
        }

        return $locales;
    }

    private function getMakeLangsForView($request, $paginate, $configLocales)
    {
        $itemArray = [];
        foreach ($paginate->items() as $item) {
            $itemArray[$item->namespace][$item->item][$item->locale] = $item->value;
        }

        $allItems = [];
        foreach ($itemArray as $namespace => $items) {
            foreach ($items as $item => $locales) {
                $item = [
                    'namespace' => $namespace,
                    'item' => $item
                ];

                foreach ($configLocales as $locale => $idx) {
                    if (array_key_exists($locale, $locales)) {
                        $item[$locale] = $locales[$locale];
                    } else {
                        $item[$locale] = '';
                    }
                }

                $allItems[] = $item;
            }
        }

        $originalPagination = $paginate->toArray();

        $paginate = new LengthAwarePaginator(
            Collection::make($allItems),
            $originalPagination['total'],
            $originalPagination['per_page'],
            $originalPagination['current_page']
        );
        $paginate->setPath(route('multi_language_manage::index'));
        $paginate->appends($request->except('page'));

        return $paginate;
    }

    public function export(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);

        $beginColumn = 'A';
        $beginRow = 2;

        $localeConfig = config('xe.lang.locales');
        $locales = [];
        foreach ($localeConfig as $idx => $locale) {
            $locales[$locale] = $idx;
        }

        $heads = [
            'namespace',
            'key'
        ];
        foreach ($locales as $locale => $idx) {
            $heads[] = $locale;
        }

        $headLength = $beginColumn . $beginRow . ':' . chr(ord($beginColumn) + count($heads) - 1) . $beginRow;
        $sheet->getStyle($headLength)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFABABAB');

        foreach ($heads as $idx => $head) {
            $sheet->setCellValue(chr(ord($beginColumn) + $idx) . $beginRow, $head);
        }

        $langs = $this->getLangsQuery($request)->get();

        $beginColumn = 'A';
        $preItem = '';
        foreach ($langs as $lang) {
            if ($preItem != $lang->item) {
                $beginRow++;
            }

            $sheet->setCellValue(chr(ord($beginColumn) + 0) . $beginRow, $lang->namespace);
            $sheet->setCellValue(chr(ord($beginColumn) + 1) . $beginRow, $preItem);

            $column = chr(ord($beginColumn) + 2 + $locales[$lang->locale]) . $beginRow;
            $sheet->setCellValue($column, $lang->value);

            $preItem = $lang->item;
        }

        $folderPath = 'app/public/multi_language_manage';
        if (file_exists(storage_path($folderPath)) == false) {
            mkdir(storage_path($folderPath), 0777, true);
        }

        $fileName = 'multi_language_' . now()->format('Y-m-d H:i:s');
        $path = storage_path($folderPath . '/' . $fileName . '.xlsx');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);

        $response = app(ResponseFactory::class)->download($path);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function getLangsQuery(Request $request)
    {
        $query = \XeDB::table('translation');

        if ($namespace = $request->get('namespace')) {
            $query->where('namespace', $namespace);
        }

        if ($item = $request->get('item')) {
            $query->where('item', 'like', '%' . $item . '%');
        }

        if ($locale = $request->get('locale')) {
            $query->where('locale', $locale);
        }

        if ($value = $request->get('value')) {
            $query->where('value', 'like', '%' . $value . '%');
        }

        $query->orderBy('namespace', 'asc');
        $query->orderBy('item', 'asc');

        return $query;
    }
}
