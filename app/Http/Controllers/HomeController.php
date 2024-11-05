<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportConversion;
use App\UseCases\dict\ReportTrait;
use App\UseCases\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->middleware('auth');
    }

    public function load(Request $request)
    {
        $validated = $request->validate([
            'first_period' => 'required|string',
            'second_period' => 'nullable|string',
            'hard_refresh' => 'nullable|string'
        ]);
        $hard = isset($validated['hard_refresh']);
        $first_period = $validated['first_period'];
        $first = explode('-', $first_period);
        $from_first = str_replace('/', '-', trim($first[0], ' +'));
        $to_first = str_replace('/', '-', trim($first[1], ' +'));
        $result = ReportService::report($from_first, $to_first, $hard);
        if (isset($validated['second_period'])) {
            $second_period = $validated['second_period'];
            $second = explode('-', $second_period);
            $from_second = str_replace('/', '-', trim($second[0], ' +'));
            $to_second = str_replace('/', '-', trim($second[1], ' +'));
            $result2 = ReportService::report($from_second, $to_second, $hard);
            return redirect("home/{$from_first}|{$to_first}/{$from_second}|{$to_second}");

        }
        return redirect("home/{$from_first}|{$to_first}");
    }

    public function managers_load(Request $request)
    {

        $validated = $request->validate([
            'first_period' => 'required|string',
            'second_period' => 'nullable|string',
        ]);
        $first_period = $validated['first_period'];
        $first = explode('-', $first_period);
        $from_first = str_replace('/', '-', trim($first[0], ' +'));
        $to_first = str_replace('/', '-', trim($first[1], ' +'));
        $result = ReportService::report($from_first, $to_first);
        $time = time();
        return redirect("managers/{$from_first}|{$to_first}?token={$time}");
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($first = null, $second = null)
    {
        $objects = $objects2 = $conversions = $conversions2 = $compare = null;
        if ($first) {
            $first = explode('|', $first);
            $from_first = str_replace('/', '-', trim($first[0], ' +'));
            $to_first = str_replace('/', '-', trim($first[1], ' +'));

            //region Common
            $object = Report::where('date_from', Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
            if ($object) {
                $objects = json_decode($object->json, true);
                $closed = $success = $bill = $spam = $work = $korp = [];

                foreach ($objects['mans'] as $key => $man) {
                    $closed[$key] = $man['closed'];
                    $success[$key] = $man['success'];
                    $bill[$key] = $man['bill'];
                    $spam[$key] = $man['spam'];
                    $work[$key] = $man['work'];
                    $korp[$key] = $man['korp'];
                }


                $pages_ = $objects['pages'];
                arsort($pages_);
                $pages = array_slice($pages_, 0, 15);
                $labelsPages = array_keys($pages);


                $prefs_ = $objects['prefs'];
                arsort($prefs_);
                $prefs = array_slice($prefs_, 0, 15);
                $labelsPrefs = array_keys($prefs);
                //endregion

                $objects['additional'] = [
                    'success' => $success,
                    'closed' => $closed,
                    'bill' => $bill,
                    'spam' => $spam,
                    'work' => $work,
                    'korp' => $korp,
                    'pages' => $pages,
                    'labelsPages' => $labelsPages,
                    'prefs' => $prefs,
                    'labelsPrefs' => $labelsPrefs
                ];
                #region Conversion
                $conv = ReportConversion::where('date_from', Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
                if ($conv) {
                    $conversions = json_decode($conv->json, true);
                }
            }
            #endregion

        } else {
            $first = [Carbon::yesterday()->startOfMonth()->format('d/m/Y'), Carbon::yesterday()->format('d/m/Y')];
        }

        $compare = false;
        if ($second) {
            $compare = true;
            $second = explode('|', $second);
            $from_second = str_replace('/', '-', trim($second[0], ' +'));
            $to_second = str_replace('/', '-', trim($second[1], ' +'));
            $object2 = Report::where('date_from', Carbon::parse($from_second))->where('date_to', Carbon::parse($to_second))->get()->first();
            if ($object2) {
                $objects2 = json_decode($object2->json, true);
                $closed = $success = $bill = $spam = $work = $korp = [];

                foreach ($objects2['mans'] as $key => $man) {
                    $closed[$key] = $man['closed'];
                    $success[$key] = $man['success'];
                    $bill[$key] = $man['bill'];
                    $spam[$key] = $man['spam'];
                    $work[$key] = $man['work'];
                    $korp[$key] = $man['korp'];
                }


                $pages_ = $objects2['pages'];
                arsort($pages_);
                $pages = array_slice($pages_, 0, 15);
                $labelsPages = array_keys($pages);


                $prefs_ = $objects2['prefs'];
                arsort($prefs_);
                $prefs = array_slice($prefs_, 0, 15);
                $labelsPrefs = array_keys($prefs);
                //endregion

                $objects2['additional'] = [
                    'success' => $success,
                    'closed' => $closed,
                    'bill' => $bill,
                    'spam' => $spam,
                    'work' => $work,
                    'korp' => $korp,
                    'pages' => $pages,
                    'labelsPages' => $labelsPages,
                    'prefs' => $prefs,
                    'labelsPrefs' => $labelsPrefs
                ];
                #region Conversion
                $conv2 = ReportConversion::where('date_from', Carbon::parse($from_second))->where('date_to', Carbon::parse($to_second))->get()->first();
                if ($conv2) {
                    $conversions2 = json_decode($conv2->json, true);
                }
            }
        } else {
            $second = [Carbon::yesterday()->startOfMonth()->format('d/m/Y'), Carbon::yesterday()->format('d/m/Y')];

        }


        $postsales = collect(json_decode('{"all":{"01":0.22257383966244726,"02":0.22803904170363798,"03":0.23251589464123523,"04":0.2653658536585366,"05":0.3064327485380117,"06":0.2891156462585034,"07":0.4056603773584906,"08":0.22417582417582418,"09":0.2638580931263858,"10":0.1761612620508326},"sales":{"01":{"count":211,"price":2329820,"Month":"January","clients":948},"02":{"count":257,"price":2608510,"Month":"February","clients":1127},"03":{"count":256,"price":2839555,"Month":"March","clients":1101},"04":{"count":272,"price":2863425,"Month":"April","clients":1025},"05":{"count":262,"price":2624185,"Month":"May","clients":855},"06":{"count":255,"price":2986070,"Month":"June","clients":882},"07":{"count":301,"price":3220453,"Month":"July","clients":742},"08":{"count":204,"price":2490115,"Month":"August","clients":910},"09":{"count":238,"price":2972910,"Month":"September","clients":902},"10":{"count":201,"price":2424970,"Month":"October","clients":1141}}}', true));

//        dd(collect($postsales['sales'])->pluck('Month'));
        error_reporting(E_ERROR);
        $postsales['sales']["10"] = $postsales['sales'][10];
        return view('home', compact('first', 'second', 'compare', 'objects', 'objects2', 'conversions', 'postsales'));
    }

    public function managers($first = null, $second = null)
    {
        if (!isset($_GET['token']) or ($_GET['token'] != 'fgEvcEWkc' and time() - $_GET['token'] > 100)) {
            abort(419);
        }
        $objects = $objects2 = $conversions = $conversions2 = $compare = null;
        if ($first) {
            $first = explode('|', $first);
            $from_first = str_replace('/', '-', trim($first[0], ' +'));
            $to_first = str_replace('/', '-', trim($first[1], ' +'));

            //region Common
            $object = Report::where('date_from', Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
            if ($object) {
                $objects = json_decode($object->json, true);
                $closed = $success = $bill = $spam = $work = $korp = [];

                foreach ($objects['mans'] as $key => $man) {
                    $closed[$key] = $man['closed'];
                    $success[$key] = $man['success'];
                    $bill[$key] = $man['bill'];
                    $spam[$key] = $man['spam'];
                    $work[$key] = $man['work'];
                    $korp[$key] = $man['korp'];
                }


                $pages_ = $objects['pages'];
                arsort($pages_);
                $pages = array_slice($pages_, 0, 15);
                $labelsPages = array_keys($pages);


                $prefs_ = $objects['prefs'];
                arsort($prefs_);
                $prefs = array_slice($prefs_, 0, 15);
                $labelsPrefs = array_keys($prefs);
                //endregion

                $objects['additional'] = [
                    'success' => $success,
                    'closed' => $closed,
                    'bill' => $bill,
                    'spam' => $spam,
                    'work' => $work,
                    'korp' => $korp,
                    'pages' => $pages,
                    'labelsPages' => $labelsPages,
                    'prefs' => $prefs,
                    'labelsPrefs' => $labelsPrefs
                ];
                #region Conversion
                $conv = ReportConversion::where('date_from', Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
                if ($conv) {
                    $conversions = json_decode($conv->json, true);
                }
            }
            #endregion

        } else {
            $first = [Carbon::yesterday()->startOfMonth()->format('d/m/Y'), Carbon::yesterday()->format('d/m/Y')];
        }


        error_reporting(E_ERROR);
        return view('managers', compact('first', 'second', 'compare', 'objects', 'objects2', 'conversions'));
    }
}
