<?php

namespace App\UseCases;


use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Filters\NotesFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\Factories\NoteFactory;
use AmoCRM\Models\LeadModel;
use App\Models\Report;
use App\Models\ReportConversion;
use App\UseCases\dict\CustomFields;
use App\UseCases\dict\Pipelines;
use App\UseCases\dict\ReportTrait;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Reverb\Loggers\Log;



//TODO: Запрет на большие периоды
class ReportService
{

    public const PROGRESS_KEY = 'progress_task';

    public static function conversion($from_first, $to_first, $socket = true)
    {
        \Illuminate\Support\Facades\Log::info('started');
        $report = ReportConversion::where('date_from', Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
        if ($report) {
            $one_day = 2 * 60 * 60; //часы * мин * сек = 86400 c
            if (time() - $report->created_at->timestamp < $one_day and $report->created_at->timestamp < \Carbon\Carbon::parse($to_first . ' 23:59')->timestamp) { // || true
                die(json_encode(["status" => 1]));
            }
        }
        $start = new DateTime($from_first);
        $end = new DateTime($to_first);
        $end = $end->modify('+1 day'); // чтобы включить конечную дату

        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end);

        $dates = array();

        $salesT = [];
        $users = ReportTrait::USERS;
        foreach ($dateRange as $key => $value) {
            foreach ($users as $user) {
                $salesT[$user][$value->format('d M')] = [
                    'success' => 0,
                    'closed' => 0,
                    'summary' => 0,
                    'price' => 0,
                    'new' => 0,
                ];
            }
        }

        $apiClient = AmoService::getClient();
        $lf = new \AmoCRM\Filters\LeadsFilter();
        $lf->setCreatedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse($from_first)->timestamp)->setTo(\Carbon\Carbon::parse($to_first . ' 23:59')->timestamp));
        $lf->setLimit(200);
        $i = 1;

//    $users = [];
        $pipelines = [];


        $pipelines = ReportTrait::PIPES;

        $response = [];
        $pipes = [];
        $pipes['retail_success'] = 0;
        $pipes['retail_closed'] = 0;
        $pages = [];
        $managers = [];
        $courses = [];
        $prefs = [];
        $common = [
            'leads' => 0,
            'closed_period' => 0,
            'success_period' => 0,
            'deffered' => 0,
            'closed' => 0,
            'reasons' => [],
            'success' => 0,
            'sell' => 0,
            'time' => 0,
            'time_clean' => 0,
            'deffered_clean' => 0,
            'types' => [],
            'overload' => 0,
            'time_calls' => 0,
            'time_call_count' => 0,
            'time_tasks' => 0,
            'time_tasks_count' => 0,
        ];

        $cats = [
            'mas' => 0,
            'kos' => 0
        ];
        $man = [];
        $increment = 0.01;

        $int = Carbon::parse($to_first)->diff(Carbon::parse($from_first))->d + 1;
        $increment = round(40 / ($int * 200), 2);
        $pr = 0;
        $o = 0;
        $gap = 10;
        $key_cost = 0;
        $code = $from_first;
        PusherProgress::sendProgress($pr, $code);
        while (true) {
            $tmp = $apiClient->leads()->get($lf, [LeadModel::CONTACTS]);
            foreach ($tmp as $key => $lead) {
                if ($key - $key_cost == $gap) {
                    $key_cost = $key;
                    PusherProgress::sendProgress($pr += $increment * $gap, $code);
                }


                $pid = $lead->getPipelineId();

                if (in_array($pid, [Pipelines::RETAIL, Pipelines::DEFFERED])) {

                    $uid = $lead->getResponsibleUserId();
                    $sid = $lead->getStatusId();
                    if (isset($users[$uid])) {

                        $user = $users[$uid];
                        if (!isset($man[$user]['leads'])) {
                            $man[$user] = [
                                'leads' => 0,
                                'id' => $uid,
                                'success_period' => 0,
                                'closed_period' => 0,
                                'deffered' => 0,
                                'closed' => 0,
                                'reasons' => [],
                                'success' => 0,
                                'sell' => 0,
                                'time' => 0,
                                'time_clean' => 0,
                                'deffered_clean' => 0,
                                'overload' => 0,
                                'time_calls' => 0,
                                'time_call_count' => 0,
                                'time_tasks' => 0,
                                'time_tasks_count' => 0,
                            ];
                        }
                        $man[$user]['leads']++;
                        $common['leads']++;
                        if ($pid == Pipelines::DEFFERED) {
                            $man[$user]['deffered']++;
                            $common['deffered']++;
                        };
                        if ($sid == 143) {
                            $man[$user]['closed_period']++;
                            $common['closed_period']++;
                        }
                        if ($sid == 142) {
                            if (Carbon::parse($lead->getClosedAt())->between(Carbon::parse($from_first.' 00:00'), Carbon::parse($to_first.' 23:59')))
                            {
                                $man[$user]['success_period']++;
                                $common['success_period']++;
                            }
                        }
                        if ($sid == 32533201 or $sid == 32533204)
                        {
                            $leadNotesService = $apiClient->events();

                            $ef = new \AmoCRM\Filters\EventsFilter();
                            $ef->setEntity([EntityTypesInterface::LEADS])->setEntityIds([$lead->getId()])->setTypes(['lead_status_changed']);
                            try {
                                $events = $apiClient->events()->get($ef);
                                foreach ($events as $e) {
                                    /** @var \AmoCRM\Models\EventModel $e */
                                    if (in_array($e->getValueAfter()[0]['lead_status']['id'], [32533201, 32533204])) {
                                        if (Carbon::parse($e->getCreatedAt())->between(Carbon::parse($from_first.' 00:00'), Carbon::parse($to_first. '23:59')))
                                        {
                                            $man[$user]['success_period']++;
                                            $common['success_period']++;
                                            ReportTrait::incrementOrSet($man[$user]['partly_payed']);
                                            ReportTrait::incrementOrSet($common['partly_payed']);
                                            break;
                                        }
                                    }
                                }
                            } catch (Exception $exception) {
                            }
                        }


                        #region Звонки
                        $csfv = $lead->getCustomFieldsValues();
                        try {

                            if ($c = $csfv->getBy("fieldid", CustomFields::RESULT[0])) {

                                if ($c = $c->getValues()->first()->getValue()) {
                                    if ($c == "Заявка с сайта") {
                                        $cont = $lead->getMainContact();
                                        if ($cont) {
                                            $leadNotesService = $apiClient->notes(EntityTypesInterface::CONTACTS);
                                            $nf = (new NotesFilter())->setNoteTypes([NoteFactory::NOTE_TYPE_CODE_CALL_OUT]);
                                            $nf->setEntityIds([$cont->getId()]);
                                            try {
                                                $task_time = null;
                                                $tasks = $apiClient->tasks()->get((new \AmoCRM\Filters\TasksFilter())->setEntityIds([$lead->getId()])->setEntityType(EntityTypesInterface::LEADS));
                                                $tasks = $tasks->toArray();
                                                usort($tasks, function ($a, $b) {
                                                    return $a['created_at'] <=> $b['created_at'];
                                                });

                                                foreach ($tasks as $task)
                                                {
                                                    $task = (object)$task;
                                                    if ($task->responsible_user_id == $lead->getResponsibleUserId())
                                                    {
                                                        $task_time = $task->updated_at - $task->created_at;
                                                        $man[$user]['time_tasks'] += $task_time;
                                                        $common['time_tasks'] += $task_time;
                                                        $common['time_tasks_count']++;
                                                        $man[$user]['time_tasks_count']++;

                                                        break;
                                                    }
                                                }


                                                $notesCollection = $leadNotesService->get($nf);
                                                $notesCollection = $notesCollection->toArray();
                                                usort($notesCollection, function ($a, $b) {
                                                    return $a['created_at'] <=> $b['created_at'];
                                                });
                                                foreach ($notesCollection as $note) {
                                                    $note = (object)$note;
                                                    if ($note->created_at > $lead->getCreatedAt()) {
                                                        if ($note->responsible_user_id == $lead->getResponsibleUserId())
                                                        {
                                                            $timer = $note->created_at - $lead->createdAt;
                                                            $man[$user]['time_calls'] += $timer;
                                                            $common['time_calls'] += $timer;
                                                            $common['time_call_count']++;
                                                            $man[$user]['time_call_count']++;
                                                            break;
                                                        }
                                                    }
                                                }
                                            } catch (AmoCRMApiException $e) {

                                            }
                                        }
                                    }
                                }
                            }


                        } catch (\League\Flysystem\Exception $exception) {
                        }

                        #endregion


                        $salesT[$user][date('d M', $lead->createdAt)]['new']++;

                    }

                }
            }
            $i++;
            $lf->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }

        }
        $lf2 = new LeadsFilter();
        $lf2->setClosedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse($from_first.' 00:00')->timestamp)->setTo(\Carbon\Carbon::parse($to_first . ' 23:59')->timestamp));
        $lf2->setLimit(200);
        $i = 1;
        $pr = 40;
        PusherProgress::sendProgress($pr, $code);


        while (true) {
            $tmp = $apiClient->leads()->get($lf2);
            foreach ($tmp as $lead) {
                if ($pr < 99) {
                    PusherProgress::sendProgress($pr += $increment, $code);
                }

                $pid = $lead->getPipelineId();
                $uid = $lead->getResponsibleUserId();
                if (isset($users[$uid])) {
                    $user = $users[$uid];
                    if (!isset($man[$user]['closed'])) {
                        $man[$user] = [
                            'leads' => 0,
                            'id' => $uid,
                            'success_period' => 0,
                            'closed_period' => 0,
                            'deffered' => 0,
                            'closed' => 0,
                            'reasons' => [],
                            'success' => 0,
                            'sell' => 0,
                            'time' => 0,
                            'time_clean' => 0,
                            'deffered_clean' => 0,
                            'overload' => 0,

                        ];
                    }
                    if (in_array($pid, [Pipelines::RETAIL, Pipelines::DEFFERED])) {
                        if ($lead->getStatusId() == 142) {
                            $salesT[$user][date('d M', $lead->closedAt)]['success']++;
                            $salesT[$user][date('d M', $lead->closedAt)]['price'] += $lead->getPrice();
                            $man[$user]['success']++;
                            $common['success']++;
                            $man[$user]['sell'] += $lead->getPrice();
                            $common['sell'] += $lead->getPrice();
                            $man[$user]['time'] += $lead->closedAt - $lead->createdAt;
                            $common['time'] += $lead->closedAt - $lead->createdAt;
                            $wasInef = false;

                            $leadNotesService = $apiClient->events();

                            $ef = new \AmoCRM\Filters\EventsFilter();
                            $ef->setEntity([EntityTypesInterface::LEADS])->setEntityIds([$lead->getId()])->setTypes(['lead_status_changed']);
                            try {
                                $events = $apiClient->events()->get($ef);
                                foreach ($events as $e) {
                                    /** @var \AmoCRM\Models\EventModel $e */
                                    if ($e->getValueAfter()[0]['lead_status']['pipeline_id'] == Pipelines::DEFFERED) {
                                        $wasInef = true;
                                        break;
                                    }
                                }
                            } catch (Exception $exception) {
                            }

                            if (!$wasInef) {
                                $man[$user]['time_clean'] += $lead->closedAt - $lead->createdAt;
                                $common['time_clean'] += $lead->closedAt - $lead->createdAt;
                                $common['deffered_clean']++;
                                $man[$user]['deffered_clean']++;
                            }


                            $csfv = $lead->getCustomFieldsValues();
                            if ($c = $csfv->getBy("fieldid", CustomFields::STUDY_FORM_RET[0])) {
                                try {
                                    if ($c = $c->getValues()->first()->getValue()) {
                                        if (!isset($man[$user]['types'])) {
                                            $man[$user]['types'] = [];
                                        }
                                        ReportTrait::incrementOrSet($man[$user]['types'][$c]);
                                        if (!isset($man[$user]['types_price'])) {
                                            $man[$user]['types_price'] = [];
                                        }
                                        $man[$user]['types_price'][$c] = isset($man[$user]['types_price'][$c]) ? $man[$user]['types_price'][$c] + $lead->getPrice() : $lead->getPrice();
                                        ReportTrait::incrementOrSet($common['types'][$c]);
                                        $common['types_price'][$c] = isset($common['types_price'][$c]) ? $common['types_price'][$c] + $lead->getPrice() : $lead->getPrice();
                                    }
                                } catch (Exception $e) {
                                }

                            }
                        } elseif ($lead->getStatusId() == 143) {
                            $csfv = $lead->getCustomFieldsValues();
                            $po_vopr_obuch = false;

                            if (!$csfv) {
                                $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                $man[$user]['closed']++;
                                $common['closed']++;
                            } else {
                                $cn = $csfv->getBy("fieldid", 644641);
                                if ($cn) {
                                    try {
                                        $cn = $cn->getValues()->first()->getValue();
                                        ReportTrait::incrementOrSet($man[$user]['necelevoy'][$cn]);
                                        ReportTrait::incrementOrSet($common['necelevoy'][$cn]);
                                        if (trim($cn) == 'Слушатель по вопросу обучения') {

                                            $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                            $man[$user]['closed']++;
                                            $common['closed']++;
                                            $po_vopr_obuch = true;
                                            $man[$user]['overload']++;
                                            $common['overload']++;
                                            $man[$user]['reasons']["Слушатель по вопросу обучения"] = isset($man[$user]['reasons']["Слушатель по вопросу обучения"]) ? $man[$user]['reasons']["Слушатель по вопросу обучения"] + 1 : 0;
                                            $common['reasons']["Слушатель по вопросу обучения"] = isset($common['reasons']["Слушатель по вопросу обучения"]) ? $common['reasons']["Слушатель по вопросу обучения"] + 1 : 0;

                                        }
                                    } catch (Exception $e) {
                                    }
                                }
                                if (!$po_vopr_obuch) {
                                    if ($c = $csfv->getBy("fieldid", 644039)) {
                                        try {
                                            if ($c = $c->getValues()->first()->getValue()) {
                                                ReportTrait::incrementOrSet( $man[$user]['reasons'][$c]);
                                                ReportTrait::incrementOrSet( $common['reasons'][$c]);

                                                if (!in_array($c, ['Дубль', 'Ошиблись номером', 'Уже обучается'])) {
                                                    $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                                    $man[$user]['closed']++;
                                                    $common['closed']++;
                                                } else {

                                                    $man[$user]['overload']++;
                                                    $common['overload']++;
                                                    ReportTrait::incrementOrSet($man[$user]['closed_types'][$c]);
                                                    ReportTrait::incrementOrSet($common['closed_types'][$c]);
                                                }
                                            }
                                        } catch (AmoCRMApiException $e)
                                        {
                                            $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                            $man[$user]['closed']++;
                                            $common['closed']++;
                                        }  catch (Exception $e) {
                                            \Illuminate\Support\Facades\Log::error("Error: ". print_r($e->getMessage(), 1));
                                            $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                            $man[$user]['closed']++;
                                            $common['closed']++;
                                        }

                                    } else {

                                        $man[$user]['reasons']["Без причины"] = isset($man[$user]['reasons']["Без причины"]) ? $man[$user]['reasons']["Без причины"] + 1 : 0;
                                        $common['reasons']["Без причины"] = isset($common['reasons']["Без причины"]) ? $common['reasons']["Без причины"] + 1 : 0;
                                        $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                        $man[$user]['closed']++;
                                        $common['closed']++;
                                    }
                                }
                            }


                        }
                    }
                }
            }
            $i++;
            $lf2->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }


        }
        PusherProgress::sendProgress(100, $code);


        if ($report) {
            $report->update([
                'json' => json_encode([
                    'man' => $man,
                    'common' => $common,
                    'period' => $salesT,
                ])
            ]);
        } else {
            ReportConversion::create([
                'date_from' => Carbon::parse($from_first),
                'date_to' => Carbon::parse($to_first),
                'json' => json_encode([
                    'man' => $man,
                    'common' => $common,
                    'period' => $salesT,
                ])
            ]);
        }
        \Illuminate\Support\Facades\Log::info(print_r([
            'man' => $man,
            'common' => $common,
            'period' => $salesT,
        ], 1));

        return [
            'man' => $man,
            'common' => $common,
            'period' => $salesT,
        ];

    }

    public static function report($from_first, $to_first, $hard = false)
    {

        $faculties_mas = [
            "mirk.msk.ru/fakultet-massazha",
            "mirk.msk.ru/kafedra-meditsinskogo-massazha",
            "mirk.msk.ru/kafedra-detskogo-massazha",
            "mirk.msk.ru/kafedra-lfk-i-sportivnoy-meditsiny",
            "mirk.msk.ru/kafedra-spa-protsedur-i-korrektsii-figury",
            "mirk.msk.ru/kafedra-medicinskoy-i-socialnoy-reabilitacii",
            "mirk.msk.ru/kafedra-sporta-i-fizicheskoj-kultury",
            "mirk.msk.ru/kursy-detskogo-massazha-s-sertifikatom",
            "mirk.msk.ru/kursy-lfk-bez-meditsinskogo-obrazovaniya",
            "mirk.msk.ru/kursy-lfk-s-med-obrazovaniyem",
            "mirk.msk.ru/massazh-i-reabilitaciya",
        ];
        $faculties_cos = [
            "mirk.msk.ru/fakultet-kosmetologii",
            "mirk.msk.ru/kafedra-esteticheskoy-kosmetologii",
            "mirk.msk.ru/kafedra-meditsinskoy-kosmetologii",
            "mirk.msk.ru/kafedra-apparatnoy-kosmetologii",
            "mirk.msk.ru/kafedra-inyektsionnoy-kosmetologii",
            "mirk.msk.ru/kafedra-massazha-litsa",
            "mirk.msk.ru/kursy-kosmetologii-dlya-vrachey",
            'mirk.msk.ru/kursy-kosmetologii-s-meditsinskim-obrazovaniyem',

        ];

        $faculties_med = [
            "mirk.msk.ru/fakultet-meditsinskoy-podgotovki",
            "mirk.msk.ru/kursy-povysheniya-kvalifikacii-dlya-vrachej",
            "mirk.msk.ru/kursy-povysheniya-kvalifikacii-dlya-srednego-medpersonala",
            "mirk.msk.ru/kursy-povysheniya-kvalifikacii-dlya-mladshego-medpersonala",
            "mirk.msk.ru/kafedra-meditsinskogo-obucheniya",
        ];
        $fac_common = [
            'mzpo-s.ru' => [
                'Медицинская подготовка' => ['/faculties/medp'],
                'Массаж и реабилитация' => ['/faculties/massag'],
                'Косметология' => ['/faculties/cosmetology'],
            ],
            'mirk.msk.ru' => [
                'Медицинская подготовка' => $faculties_med,
                'Массаж и реабилитация' => $faculties_mas,
                'Косметология' => $faculties_cos,
            ],
            'mzpokurs.com' => [
                'Медицинская подготовка' => ['/course/medp'],
                'Массаж и реабилитация' => ['/course/massazhp'],
                'Косметология' => ['/course/cosmp'],
            ],

        ];
        $report = Report::where('date_from', Carbon::parse($from_first))->where('date_to', Carbon::parse($to_first))->get()->first();
        if ($report and !$hard) {
            $one_day = 2 * 60 * 60; //часы * мин * сек = 86400 c
            if (time() - $report->created_at->timestamp < $one_day or $report->created_at->timestamp > \Carbon\Carbon::parse($to_first . ' 23:59')->timestamp) { // || true
                return $report->json;
            }
        }
        $demo_changed = 0;
//        $db = new DB(SELECT count(*) as co FROM `amo_change_lk_status` WHERE `created_at` BETWEEN '" . date("Y-m-d", strtotime($from_first)) . " 00:00:00' AND '" . date("Y-m-d", strtotime($to_first)) . " 23:29:00'");
        $res = DB::connection('mzpos')->table('amo_change_lk_status')->whereRaw("`created_at` BETWEEN '" . date("Y-m-d", strtotime($from_first)) . " 00:00:00' AND '" . date("Y-m-d", strtotime($to_first)) . " 23:29:00'")->get();

//        if ($res->num_rows > 0) {
//            $demo_changed = $res->fetch_assoc()['co'];
//        }
        $demo_changed = $res->count();
//    $dbdf = \Carbon\Carbon::parse($from_first)->format('Y-m-d');
//    $dbdt = \Carbon\Carbon::parse($to_first)->format('Y-m-d');
//    $db->conn->query("INSERT INTO `amo_report_retail`(`status`, `json`, `date_from`, `date_to`) VALUES (0, '{}', '$dbdf', '$dbdt')");
//    echo(json_encode(['id' => $db->conn->insert_id]));

        $lf = new \AmoCRM\Filters\LeadsFilter();
//        dd([(new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse($from_first . ' 00:00')->timestamp)->setTo(\Carbon\Carbon::parse($to_first . ' 23:59')->timestamp), \Carbon\Carbon::parse($from_first . ' 00:00'), \Carbon\Carbon::parse($to_first . ' 23:59')]);
        $lf->setCreatedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse($from_first . ' 00:00')->timestamp)->setTo(\Carbon\Carbon::parse($to_first . ' 23:59')->timestamp));
        $lf->setLimit(200);
        $i = 1;


        $users = [];
        $pipelines = [];
        $courses_pref = ReportTrait::COURSES;
        $pref_cats = ReportTrait::PREFIX_CATS;

        $apiClient = AmoService::getClient();
        $users = ReportTrait::USERS;
        $pipelines = ReportTrait::PIPES;

        $cats_new = [
            'Акции' => 0
        ];

        $response = [];
        $pipes = [];
        $pipes['retail_success'] = 0;
        $pipes['retail_closed'] = 0;
        $pages = [];
        $managers = [];
        $courses = [];
        $prefs = [];
        $nopage = 0;
        $sources = [
            "instagram" => 0,
            "ucheba.ru" => 0,
            "cruche-academy.ru" => 0,
            "mzpo-s.ru" => 0,
            "vk" => 0,
            "mzpo.education" => 0,
            "Прямой звонок" => 0,
            "mirk.msk.ru" => 0,
            "skillbank.su" => 0,
            "mzpokurs.com" => 0,
            "Telegram" => 0,
            "whatsapp" => 0,
            "lk.mzpo-s.ru" => 0
        ];
        $unbind = [];
        $demo = 0;
        $cats = [
            'mas' => 0,
            'kos' => 0
        ];
        $sites = [
            'lk.mzpo-s.ru' => 0,
            'mzpo-s.ru' => 0,
            'mirk.msk.ru' => 0,
            'mzpo.education' => 0,
            'mzpokurs.com' => 0,
            'skillbank.su' => 0,
            'cruche-academy.ru' => 0
        ];
        $sites_expanded = [
            'lk.mzpo-s.ru' => 0,
            'mzpo-s.ru' => 0,
            'mirk.msk.ru' => 0,
            'mzpo.education' => 0,
            'mzpokurs.com' => 0,
            'skillbank.su' => 0,
            'cruche-academy.ru' => 0
        ];
        $timestage = [];
        $test_counter = 0;
        $timestage_times = [];
        $kval = $utm = $kval_utm = 0;
        while (true) {
            $tmp = $apiClient->leads()->get($lf);
            foreach ($tmp as $lead) {
                $pid = $lead->getPipelineId();
                if ($pid == Pipelines::RETAIL)
                {
                    \Illuminate\Support\Facades\Log::build([
                        'driver' => 'daily',
                        'path' => storage_path('logs/test.log'),
                    ])->info($test_counter++.' '.$lead->getId());

                }
                if (!isset($pipelines[$pid])) {
                    continue;
                }
                $csfv = $lead->getCustomFieldsValues();
                $k = false;
                if (in_array($pid, [Pipelines::RETAIL, Pipelines::DEFFERED])) {
                    ReportTrait::incrementOrSet($timestage[Carbon::parse($lead->created_at)->translatedFormat('d F (D)')]);
                    ReportTrait::incrementOrSet($timestage_times['common'][Carbon::parse($lead->created_at)->translatedFormat('H')]);
                    ReportTrait::incrementOrSet($timestage_times[Carbon::parse($lead->created_at)->translatedFormat('d.m')][Carbon::parse($lead->created_at)->translatedFormat('H')]);

                    if ($csfv) {
                        if ($v = $csfv->getBy("fieldid", 726051)) {
                            if ($v->getValues()->first()->getValue()) {
                                $k = true;
                                $kval++;
                            }
                        }
                        if ($v = $csfv->getBy("fieldid", 640697)) {
                            if ($v->getValues()->first()->getValue() == "yandex-direct") {
                                $utm++;
                                if ($k) $kval_utm++;
                            }
                        }
                    }
                }
                if ($csfv and $v = $csfv->getBy("fieldid", CustomFields::PAGE[0])) {
                    $pre = $v->getValues()->first()->getValue();
                    $pre = str_replace("https://", '', $pre);
                    $pre = str_replace('www.', '', $pre);
                    $pre = explode('?', $pre)[0];
                    $pages[$pre] = isset($pages[$pre]) ? $pages[$pre] + 1 : 1;
                    foreach ($sites as $key => $site) {
                        if (strpos($pre, $key) !== false) {
                            $sites[$key]++;
                            break;
                        }
                    }

//                if (in_array($pre, $categs_urls['massag'])) $cats['mas']++;
//                elseif (in_array($pre, $categs_urls['cosm'])) $cats['kos']++;


                    if ($c = $csfv->getBy("fieldid", CustomFields::COURSE[0])) {

                        if (isset($courses_pref[$pre])) {
                            $courses[] = [
                                'prefix' => $courses_pref[$pre],
                                'course' => $c->getValues()->first()->getValue(),
                                'success' => $lead->getStatusId(),
                                'id' => $lead->getId()
                            ];
                            ReportTrait::incrementOrSet($prefs[$courses_pref[$pre]]);

                        }
                    }
                    try {
                        $categ = $pref_cats[$courses_pref[$pre]];
                    } catch (Exception $exception) {
                    }
                    if (isset($categ) and $categ) {
                        ReportTrait::incrementOrSet($cats_new[$categ]);
                    } else {

                        if ($lead->getTags()) {
                            $f = 0;
                            foreach ($lead->getTags() as $tag) {
                                if ($tag->name == 'Акция') {
                                    $cats_new['Акции']++;
                                    $f = 1;
                                    break;
                                }
                            }
                            if (!$f) {
                                $category_founded_by_url = ReportTrait::find_cat($pre, $fac_common);
                                $cats_new[$category_founded_by_url] = isset($cats_new[$category_founded_by_url]) ? $cats_new[$category_founded_by_url] + 1 : 1;
                                if (!$category_founded_by_url or $category_founded_by_url == "" or strlen($category_founded_by_url) == 0) {
//                                dd($pre, 0);
//                                dd($category_founded_by_url);
                                    file_put_contents(__DIR__ . '/strabge1.txt', $pre . PHP_EOL, FILE_APPEND);
                                }
                            }
                        } else {
                            $category_founded_by_url = ReportTrait::find_cat($pre, $fac_common);
                            ReportTrait::incrementOrSet($cats_new[$category_founded_by_url]);
                            if (!$category_founded_by_url or $category_founded_by_url == "" or strlen($category_founded_by_url) == 0) {
                                file_put_contents(__DIR__ . '/strabge2.txt', $pre . ': ' . $category_founded_by_url . PHP_EOL, FILE_APPEND);
                            }
                        }

                    }
                    if ($v = $csfv->getBy("fieldid", CustomFields::SITE[0])) {
                        $val = $v->getValues()->first()->getValue();
                        foreach (array_keys($sites_expanded) as $site) {
                            if (strpos($val, $site) !== false) {
                                $sources[$site]++;
                                break;
                            }
                        }
                    }
                } else {
                    if ($csfv and $v = $csfv->getBy("fieldid", CustomFields::TYPE[0])) {
                        if ($v->getValues()->first()->getValue() == "Прямой звонок") $sources["Прямой звонок"]++;
                        elseif ($v->getValues()->first()->getValue() == "whatsapp") $sources["whatsapp"]++;
                        elseif ($v->getValues()->first()->getValue() == "vk") $sources["vk"]++;
                        elseif ($v->getValues()->first()->getValue() == "Telegram") $sources["Telegram"]++;
                        elseif (strpos($v->getValues()->first()->getValue(), "ucheba.ru") !== false or strpos($v->getValues()->first()->getValue(), "uchebaru") !== false) $sources["ucheba.ru"]++;
                        elseif (strpos($v->getValues()->first()->getValue(), "Авито") !== false) $sources["ucheba.ru"]++;
                        elseif ($v->getValues()->first()->getValue() == "instagram") $sources["instagram"]++;
                        else {
                            foreach (array_keys($sites_expanded) as $site) {
                                if (strpos($v->getValues()->first()->getValue(), $site) !== false) {
                                    $sources[$site]++;
                                    break;
                                }
                            }
                            $nopage++;
                            $unbind[] = 'https://mzpoeducationsale.amocrm.ru/leads/detail/' . $lead->getId();
                        }
                    } elseif ($csfv and $v = $csfv->getBy("fieldid", CustomFields::SITE[0])) {
                        $val = $v->getValues()->first()->getValue();
                        file_put_contents(__DIR__ . '/sites.txt', $val . PHP_EOL, FILE_APPEND);
                        foreach (array_keys($sites_expanded) as $site) {
                            if (strpos($val, $site) !== false) {
                                $sources[$site]++;
                                break;
                            }
                        }
                    } elseif (in_array($pid, [Pipelines::RETAIL, Pipelines::DEFFERED])) {
                        $nopage++;
                        $unbind[] = 'https://mzpoeducationsale.amocrm.ru/leads/detail/' . $lead->getId();
                    }
                }
                if ($lead->getTags()) {
                    $f = 0;
                    foreach ($lead->getTags() as $tag) {
                        if ($tag->name == 'Демо-доступ') {
                            $demo++;
                            break;
                        }
                    }
                }
                $pipe = $pipelines[$pid];
                $uid = $lead->getResponsibleUserId();
                if (!isset($users[$uid])) {
                    if (in_array($pid, [Pipelines::RETAIL, Pipelines::DEFFERED]))
                    {
                        \Illuminate\Support\Facades\Log::error($lead->getId());
                    }
                    continue;
                    dd($lead, $users, $uid);
                } else {
                    $user = $users[$uid];
                }
                $pipes[$pipe] = isset($pipes[$pipe]) ? $pipes[$pipe] + 1 : 1;
                $closed = $success = $bill = $spam = $work = $korp = $price = 0;
                if ($pid == 2231320) {
                    $korp = 1;
                } elseif ($pid == 3572668) {
                    $spam = 1;
                } elseif ($lead->getStatusId() == 142) {
                    if (\Carbon\Carbon::parse($lead->closedAt)->between(\Carbon\Carbon::parse($from_first . ' 00:00'), \Carbon\Carbon::parse($to_first . ' 23:59'))) {
                        $success = 1;
                        if ($lead->getPipelineId() == Pipelines::RETAIL) {
                            $pipes['retail_success']++;
                            $price = $lead->getPrice();

                        }
                    }
                } elseif ($lead->getStatusId() == 143) {
                    if (\Carbon\Carbon::parse($lead->closedAt)->between(\Carbon\Carbon::parse($from_first . " 00:00"), \Carbon\Carbon::parse($to_first . " 23:59"))) {
                        $closed = 1;
                        if ($lead->getPipelineId() == Pipelines::RETAIL) {
                            $pipes['retail_closed']++;
                        }
                    }
                } elseif (in_array($lead->getStatusId(), [33817816, 46756096])) {
                    $bill = 1;
                } else {
                    $work = 1;
                }
                if (isset($managers[$user])) {
                    $managers[$user]['common'] += 1;
                    $managers[$user]['success'] += $success;
                    $managers[$user]['closed'] += $closed;
                    $managers[$user]['spam'] += $spam;
                    $managers[$user]['bill'] += $bill;
                    $managers[$user]['korp'] += $korp;
                    $managers[$user]['work'] += $work;
                    $managers[$user]['price'] += $price;
                } else {
                    $managers[$user] = [
                        'common' => 1,
                        'success' => $success,
                        'closed' => $closed,
                        'spam' => $spam,
                        'bill' => $bill,
                        'korp' => $korp,
                        'work' => $work,
                        'price' => $price
                    ];
                }
            }
                $i++;
                $lf->setPage($i);
                if ($tmp->count() < 200) {
                    break;
                }

        }
        \Illuminate\Support\Facades\Log::info('nopage' . print_r($nopage, 1));
        asort($pipes, SORT_DESC);
        $tt = [];
        foreach ($timestage_times as $type => $time) {
            foreach ($timestage_times['common'] as $key => $value) {
                if (!isset($time[$key])) {
                    $time[$key] = 0;
                }
            }
            ksort($time);
            $tt[$type] = $time;
        }
        try {
            $stats = [];
            $lf = new \AmoCRM\Filters\LeadsFilter();

            $lf->setStatuses([
                [
                    'status_id' => 36592555,
                    'pipeline_id' => Pipelines::RETAIL
                ]
            ]);
            $i = 1;
            $lf->setLimit(200);
            $lf->setPage($i);


            $stats = [
                'period' => [
                    'sites' => [],
                    'sources' => [],
                ],
                'all' => [
                    'sites' => [],
                    'sources' => [],
                ]
            ];
            $co = 200;

            $sites = [
                'mzpo-s.ru',
                'mirk.msk.ru',
                'ucheba.ru',
                'mzpo.education',
                'mzpokurs.com',
                'cruche-academy.ru',
            ];

            $tags_base = [
                'Барабан',
                'JivoSite',
                'UIS',
                'vkontakte',
                'telegram',
                'tilda'
            ];

            do {
                $lf->setPage($i++);
                $leads = $apiClient->leads()->get($lf);
                $co = $leads->count();

                foreach ($leads as $lead)
                {
                    $tags = [];
                    $found = false;
                    $other = true;
                    foreach ($lead->getTags() as $tag)
                    {
                        $tags[] = $tag->getName();
                        if (in_array($tag->getName(), $tags_base))
                        {
                            if (isset($stats['all']['sources'][$tag->getName()])) {  $stats['all']['sources'][$tag->getName()]++; }
                            else  $stats['all']['sources'][$tag->getName()] = 1;
                            if (\Carbon\Carbon::parse($lead->createdAt)->between(\Carbon\Carbon::parse($from_first . " 00:00"), \Carbon\Carbon::parse($to_first . " 23:59"))) {
                                if (isset($stats['period']['sources'][$tag->getName()])) {  $stats['period']['sources'][$tag->getName()]++; }
                                else  $stats['period']['sources'][$tag->getName()] = 1;
                            }
                            $found = true;
                        }
                    }
                    foreach ($sites as $site)
                    {
                        if (in_array($site, $tags))
                        {
                            if (\Carbon\Carbon::parse($lead->createdAt)->between(\Carbon\Carbon::parse($from_first . " 00:00"), \Carbon\Carbon::parse($to_first . " 23:59"))) {
                                ReportTrait::incrementOrSet($stats['period']['sites'][$site]);
                            }
                            ReportTrait::incrementOrSet($stats['all']['sites'][$site]);
                            if (!$found)
                            {
                                if (\Carbon\Carbon::parse($lead->createdAt)->between(\Carbon\Carbon::parse($from_first . " 00:00"), \Carbon\Carbon::parse($to_first . " 23:59"))) {
                                    ReportTrait::incrementOrSet($stats['period']['sources']["Заявка с сайта"]);

                                }
                                ReportTrait::incrementOrSet($stats['all']['sources']["Заявка с сайта"]);
                            }
                            $other = false;
                            break;
                        }
                    }
                    if ($other)
                    {
                        if (\Carbon\Carbon::parse($lead->createdAt)->between(\Carbon\Carbon::parse($from_first . " 00:00"), \Carbon\Carbon::parse($to_first . " 23:59"))) {
                            ReportTrait::incrementOrSet($stats['period']['sources']["Прочее"]);
                        }
                        ReportTrait::incrementOrSet($stats['all']['sources']["Прочее"]);
                    }
                }
            } while($co == 200);

        } catch (Exception $e) {
//            dd($e);
        }

        $res = [
            'pipes' => $pipes,
            'mans' => $managers,
            'courses' => $courses,
            'pages' => $pages,
            'sites' => $sites,
            'categories_urls' => $cats,
            'prefs' => $prefs,
            'cats_new' => $cats_new,
            'sources' => $sources,
            'demo' => $demo,
            'demo-changed' => $demo_changed,
            'timestage' => $timestage,
            'timestage_times' => $tt,
            'kval' => $kval,
            'utm' => $utm,
            'utm_kval' => $kval_utm,
            'nedozvon' => $stats
        ];
        if ($report) {
            $report->update([
                'json' => json_encode($res)
            ]);
        } else {
            Report::create([
                'date_from' => Carbon::parse($from_first),
                'date_to' => Carbon::parse($to_first),
                'json' => json_encode($res)
            ]);
        }
        return $res;

    }


    public static function postSales()
    {
        $apiClient = AmoService::getClient();
        $lf = new \AmoCRM\Filters\LeadsFilter();
        $lf->setPipelineIds([3338257]);
        $lf->setCreatedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse('01-01-2024 00:00')->timestamp)->setTo(\Carbon\Carbon::parse('01-11-2024 00:00')->timestamp));
        $lf->setLimit(200);
        $i = 1;
        $clients = 0;
        $clientsPeriod = [];
        while (true) {
            $tmp = $apiClient->leads()->get($lf, [LeadModel::CONTACTS]);
            foreach ($tmp as $lead) {
                $c1 = false;
                if ($lead->getTags()) {
                    foreach ($lead->getTags() as $tag) {
                        if ($tag->name == '1с_группы') {
                            $c1= true;
                            break;
                        }
                    }
                }
                if (!$c1)
                {
                    continue;
                }

                /** @var LeadModel $lead */
//        if ($lead->getContacts()->count() > 1) dd($lead);
                if ($lead->getContacts())
                {
                    $clients+=$lead->getContacts()->count();
                    if (isset($clientsPeriod[Carbon::parse($lead->created_at)->format('F')]))
                    {
                        $clientsPeriod[Carbon::parse($lead->created_at)->format('F')]+=$lead->getContacts()->count();
                    } else
                    {
                        $clientsPeriod[Carbon::parse($lead->created_at)->format('F')]=$lead->getContacts()->count();
                    }
                }

            }
            $i++;
            $lf->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }
        }

        $sellPeriod = [];
        $sell = [
            'count' => 0,
            'price' => 0
        ];

        $lf = new \AmoCRM\Filters\LeadsFilter();
        $lf->setPipelineIds([5234134]);
        $lf->setClosedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse('01-01-2024 00:00')->timestamp)->setTo(\Carbon\Carbon::parse('01-11-2024 00:00')->timestamp));
        $lf->setLimit(200);
        $i = 1;
        while (true) {
            $tmp = $apiClient->leads()->get($lf);
            foreach ($tmp as $lead) {
                $c1 = false;
                if ($lead->getStatusId() == 142)
                {
                    $sell['count']+=1;
                    $sell['price']+=$lead->getPrice();
                    if (isset($sellPeriod[Carbon::parse($lead->getClosedAt())->format('F')]))
                    {
                        $sellPeriod[Carbon::parse($lead->getClosedAt())->format('F')]['count']+=1;
                        $sellPeriod[Carbon::parse($lead->getClosedAt())->format('F')]['price']+=$lead->getPrice();
                    }
                    else
                    {
                        $sellPeriod[Carbon::parse($lead->getClosedAt())->format('F')]['count']=1;
                        $sellPeriod[Carbon::parse($lead->getClosedAt())->format('F')]['price']=$lead->getPrice();
                    }

                }


            }
            $i++;
            $lf->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }
        }

        $all = $prices = [];
        $all = [
            '01' => 0,
            '02' => 0,
            '03' => 0,
            '04' => 0,
            '05' => 0,
            '06' => 0,
            '07' => 0,
            '08' => 0,
            '09' => 0,
            '10' => 0,
        ];
        foreach ($clientsPeriod as $month => $data)
        {
            $all[Carbon::parse($month)->format('m')] = $sellPeriod[$month]['count'] / $data;
        }
        $sales = [
            '01' => [],
            '02' => [],
            '03' => [],
            '04' => [],
            '05' => [],
            '06' => [],
            '07' => [],
            '08' => [],
            '09' => [],
            "10" => [],
        ];
        foreach ($sellPeriod as $month => $item)
        {
            if (!isset($clientsPeriod[$month])) continue;
            $sales[Carbon::parse($month)->format('m')] = [
              'count' => $item['count'],
              'price' => $item['price'],
              'Month' => $month,
              'clients' =>   $clientsPeriod[$month]
            ];
        }
        return json_encode([
           'all' => $all,
            'sales' => $sales
        ]);
    }


    public static function YearReport()
    {
        $apiClient = AmoService::getClient();
        $lf = new \AmoCRM\Filters\LeadsFilter();
        $lf->setCreatedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse('01-09-2023 00:00')->timestamp)->setTo(\Carbon\Carbon::parse('01-10-2024 00:00')->timestamp));
        $lf->setLimit(200);
        $i = 1;
        $co = 0;
        $lf->setPipelineIds([Pipelines::RETAIL, Pipelines::DEFFERED, Pipelines::SPAM]);
        $clients = 0;
        $clientsPeriod = [];
        while (true) {
            $tmp = $apiClient->leads()->get($lf, [LeadModel::CONTACTS]);

            $i++;
            $co+=$tmp->count();
            $lf->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }
        }
        dd($co);
    }
}
