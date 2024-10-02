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
        if (isset($validated['second_period']))
        {
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
            $object = Report::where('date_from' , Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
            if($object)
            {
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
                    'pages' =>$pages,
                    'labelsPages' => $labelsPages,
                    'prefs' => $prefs,
                    'labelsPrefs' => $labelsPrefs
                ];
                #region Conversion
                $conv = ReportConversion::where('date_from' , Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
                if ($conv)
                {
                    $conversions = json_decode($conv->json, true);
                }
            }
            #endregion

        } else
        {
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
        }else
        {
            $second = [Carbon::yesterday()->startOfMonth()->format('d/m/Y'), Carbon::yesterday()->format('d/m/Y')];

        }


        $postsales = collect(json_decode('{"all":{"01":0.19198312236286919,"02":0.20496894409937888,"03":0.22888283378746593,"04":0.24,"05":0.3064327485380117,"06":0.33900226757369617,"07":0.3611859838274933,"08":0.2923076923076923},"sales":{"02":{"count":231,"price":2359920,"Month":"February","clients":1127},"01":{"count":182,"price":1918733,"Month":"January","clients":948},"03":{"count":252,"price":2726160,"Month":"March","clients":1101},"04":{"count":246,"price":2711900,"Month":"April","clients":1025},"05":{"count":262,"price":2870825,"Month":"May","clients":855},"06":{"count":299,"price":3212530,"Month":"June","clients":882},"07":{"count":268,"price":2642698,"Month":"July","clients":742},"08":{"count":266,"price":3183770,"Month":"August","clients":910}}}', true));

//        dd(collect($postsales['sales'])->pluck('Month'));
        error_reporting(E_ERROR);
        return view('home', compact('first', 'second', 'compare', 'objects', 'objects2', 'conversions', 'postsales'));
    }
    public function managers($first = null, $second = null)
    {
        if (!isset($_GET['token']) or ($_GET['token'] != 'fgEvcEWkc'  and time() - $_GET['token'] > 100 ) )
        {
            abort(419);
        }
        $objects = $objects2 = $conversions = $conversions2 = $compare = null;
        if ($first) {
            $first = explode('|', $first);
            $from_first = str_replace('/', '-', trim($first[0], ' +'));
            $to_first = str_replace('/', '-', trim($first[1], ' +'));

            //region Common
            $object = Report::where('date_from' , Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
            if($object)
            {
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
                    'pages' =>$pages,
                    'labelsPages' => $labelsPages,
                    'prefs' => $prefs,
                    'labelsPrefs' => $labelsPrefs
                ];
                #region Conversion
                $conv = ReportConversion::where('date_from' , Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
                if ($conv)
                {
                    $conversions = json_decode($conv->json, true);
                }
            }
            #endregion

        } else
        {
            $first = [Carbon::yesterday()->startOfMonth()->format('d/m/Y'), Carbon::yesterday()->format('d/m/Y')];
        }


        error_reporting(E_ERROR);
        return view('managers', compact('first', 'second', 'compare', 'objects', 'objects2', 'conversions'));
    }
}
