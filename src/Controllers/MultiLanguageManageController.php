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
    const EXCEL_BEGIN_COLUMN = 'A';
    const EXCEL_BEGIN_ROW = 2;
    
    public function index(Request $request)
    {
        $locales = $this->getLocaleInformation($request);

        $paginate = $this->getLangsQuery($request)->paginate(20 * count($locales), ['*'], 'page')
            ->appends($request->except('page'));
        $langs = $this->getMakeLangsForView($request, $paginate, $locales);

        return \XePresenter::make('multi_language_manage::views.index', compact('locales', 'langs'));
    }

    public function getImport()
    {
        return api_render('multi_language_manage::views.import_popup');
    }

    public function postImport(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);

        $uploadedFile = $request->file('uploaded_file');
        if ($uploadedFile == null) {
            return;
        }
        $file = \XeStorage::upload($uploadedFile, 'public/multi_language_manage');
        if ($this->importMultiLanguage($request, $file) == false) {
            return 'error';
        }

        return redirect()->route('multi_language_manage::index')
            ->with('alert', ['type' => 'success', 'message' => '성공']);
    }

    private function importMultiLanguage(Request $request, $file)
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load(app_storage_path($file->path . DIRECTORY_SEPARATOR . $file->filename));
        $sheet = $spreadsheet->setActiveSheetIndex(0);

        $heads = [];
        foreach ($sheet->getColumnIterator() as $column) {
            $heads[$column->getColumnIndex()] =
                $sheet->getCell($column->getColumnIndex() . self::EXCEL_BEGIN_ROW)->getValue();
        }

        $isForce = $request->get('is_force', 0);
        foreach ($sheet->getRowIterator(3) as $row) {
            $lang = [];
            
            //head
            foreach ($sheet->getColumnIterator(
                self::EXCEL_BEGIN_COLUMN,
                chr(ord(self::EXCEL_BEGIN_COLUMN) + 1)
            ) as $column) {
                $lang[$heads[$column->getColumnIndex()]] =
                    $sheet->getCell($column->getColumnIndex() . $row->getRowIndex())->getValue();
            }

            foreach ($sheet->getColumnIterator(chr(ord(self::EXCEL_BEGIN_COLUMN) + 2)) as $column) {
                $this->putLang(
                    $lang['namespace'],
                    $lang['item'],
                    $heads[$column->getColumnIndex()],
                    $sheet->getCell($column->getColumnIndex() . $row->getRowIndex())->getValue(),
                    $isForce
                );
            }
        }

        return true;
    }

    private function putLang($namespace, $item, $locale, $value, $force = false, $multiLine = false)
    {
        \Log::info($value);
        $lang = [
            'namespace' => trim($namespace),
            'item' => trim($item),
            'locale' => trim($locale),
            'value' => trim($value),
            'multiline' => $multiLine,
        ];

        $isExist =  \DB::table('translation')
            ->where('namespace', $lang['namespace'])
            ->where('item', $lang['item'])
            ->where('locale', $lang['locale'])
            ->exists();

        if ($isExist == true) {
            if ($force == true) {
                \DB::table('translation')
                    ->where('namespace', $lang['namespace'])
                    ->where('item', $lang['item'])
                    ->where('locale', $lang['locale'])
                    ->update($lang);
            }
        } else {
            \DB::table('translation')->insert($lang);
        }
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

        $localeConfig = config('xe.lang.locales');
        $locales = [];
        foreach ($localeConfig as $idx => $locale) {
            $locales[$locale] = $idx;
        }

        $heads = [
            'namespace',
            'item'
        ];
        foreach ($locales as $locale => $idx) {
            $heads[] = $locale;
        }

        $headLength = self::EXCEL_BEGIN_COLUMN . self::EXCEL_BEGIN_ROW . ':' .
            chr(ord(self::EXCEL_BEGIN_COLUMN) + count($heads) - 1) . self::EXCEL_BEGIN_ROW;
        $sheet->getStyle($headLength)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFABABAB');

        foreach ($heads as $idx => $head) {
            $sheet->setCellValue(chr(ord(self::EXCEL_BEGIN_COLUMN) + $idx) . self::EXCEL_BEGIN_ROW, $head);
        }

        $langs = $this->getLangsQuery($request)
            ->whereIn('locale', $localeConfig)
            ->get();

        $preItem = '';
        $beginRow = self::EXCEL_BEGIN_ROW;
        foreach ($langs as $lang) {
            if ($preItem != $lang->item) {
                $beginRow++;
            }
            if ($preItem == '') {
                $preItem = $lang->item;
            }

            $sheet->setCellValue(chr(ord(self::EXCEL_BEGIN_COLUMN) + 0) . $beginRow, $lang->namespace);
            $sheet->setCellValue(chr(ord(self::EXCEL_BEGIN_COLUMN) + 1) . $beginRow, $preItem);

            $column = chr(ord(self::EXCEL_BEGIN_COLUMN) + 2 + $locales[$lang->locale]) . $beginRow;
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
        if ($value = $request->get('value')) {
            $valueQuery = \XeDB::table('translation');
            $valueQuery->where('value', 'like', '%' . $value . '%');
            $valueIds = $valueQuery->pluck('item')->toArray();

            $query->whereIn('item', $valueIds);
        }

        if ($namespace = $request->get('namespace')) {
            $query->where('namespace', $namespace);
        }

        if ($item = $request->get('item')) {
            $query->where('item', 'like', '%' . $item . '%');
        }

        if ($locale = $request->get('locale')) {
            $query->where('locale', $locale);
        }

        $query->orderBy('namespace', 'asc');
        $query->orderBy('item', 'asc');

        return $query;
    }
}
