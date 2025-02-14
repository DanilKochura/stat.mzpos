<?php

namespace App\Console\Commands;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Filters\NotesFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\Factories\NoteFactory;
use AmoCRM\Models\LeadModel;
use App\Models\ReportConversion;
use App\UseCases\AmoService;
use App\UseCases\dict\CustomFields;
use App\UseCases\dict\Pipelines;
use App\UseCases\dict\ReportTrait;
use App\UseCases\PusherProgress;
use App\UseCases\ReportService;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Console\Command;
use Laravel\Reverb\Loggers\Log;

class ReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:report-command {start=null} {end=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     */
    public function handle(string $start = null, string $end = null)
    {

        error_reporting(E_ERROR);
        \Illuminate\Support\Facades\Log::info('test');
        $start_time = microtime(true);
        $start = $this->argument('start');
        $end = $this->argument('end');
        if (!$start or $start=='null')
        {
            $to_first = Carbon::today()->format('d-m-Y');
            $from_first = Carbon::today()->startOfMonth()->format('d-m-Y');
        } elseif(!$end or $end=='null')
        {
            $from_first = Carbon::parse($start)->format('d-m-Y');
            $to_first = Carbon::parse($start)->endOfMonth()->format('d-m-Y');
        } else
        {
            $from_first = Carbon::parse($start)->format('d-m-Y');
            $to_first = Carbon::parse($end)->format('d-m-Y');
        }

        \Illuminate\Support\Facades\Log::info('started command: conversion '.$from_first.' - '.$to_first);

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

        ];

        $cats = [
            'mas' => 0,
            'kos' => 0
        ];
        $man = [];
        $increment = 0.01;

        $int = Carbon::parse($to_first)->diff(Carbon::parse($from_first))->d+1;
        $pr = 0;
        $o = 0;
        $gap = 10;
        $key_cost = 0;
        $code = $from_first;
        $this->info('started: '.$from_first.' '.$to_first);
        while (true) {
            $time_start = microtime(true);
            $tmp = $apiClient->leads()->get($lf, [LeadModel::CONTACTS]);
            $time_connection = microtime(true);
            $time_сonn =  $time_connection - $time_start;
            foreach ($tmp as $key=>$lead) {
                if($key - $key_cost == $gap)
                {
                    $key_cost = $key;
                    PusherProgress::sendProgress($pr += $increment*$gap, $code);
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
                            if($csfv)
                            {
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
//                                                $this->error($e);
                                                }
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
                    else {
                        $this->warn($lead->getId());
                    }
                }
            }
            $time_end = microtime(true);
            $this->info('page - '.$i.', count: '.$tmp->count().', connection time: '.$time_сonn.' time: '.($time_end - $time_start)/60);

            $i++;

            $lf->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }

        }
        $lf2 = new LeadsFilter();
        $lf2->setClosedAt((new \AmoCRM\Filters\BaseRangeFilter())->setFrom(\Carbon\Carbon::parse($from_first . '00:00')->timestamp)->setTo(\Carbon\Carbon::parse($to_first . ' 23:59')->timestamp));
        $lf2->setLimit(200);
        $i = 1;
        $pr = 40;

        $this->info('sevond step!');


        $testing = [0,0];
        while (true) {
            $time_start = microtime(true);
            $tmp = $apiClient->leads()->get($lf2);
            $time_сonn = $time_connection - $time_start;
            foreach ($tmp as $lead) {
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
                            'overload' => 0
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
                                        if (!isset($man[$user]['types']))
                                        {
                                            $man[$user]['types'] = [];
                                        }
                                        ReportTrait::incrementOrSet($man[$user]['types'][$c]);
                                        if (!isset($man[$user]['types_price']))
                                        {
                                            $man[$user]['types_price'] = [];
                                        }
                                        $man[$user]['types_price'][$c] = isset($man[$user]['types_price'][$c]) ? $man[$user]['types_price'][$c] + $lead->getPrice() : $lead->getPrice();
                                        ReportTrait::incrementOrSet($common['types'][$c]);
                                        $common['types_price'][$c] = isset($common['types_price'][$c]) ? $common['types_price'][$c] + $lead->getPrice() : $lead->getPrice();
                                    }
                                } catch (Exception $e) {
                                    dd($e);
                                }

                            }
                        }
                        elseif ($lead->getStatusId() == 143) {
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
                                        $man[$user]['necelevoy'][$cn]++;
                                        $common['necelevoy'][$cn]++;
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
                                                $man[$user]['reasons'][$c]++;
                                                $common['reasons'][$c]++;
                                                if (!in_array($c, ['Дубль', 'Ошиблись номером', 'Уже обучается'])) {
                                                    $salesT[$user][date('d M', $lead->closedAt)]['closed']++;
                                                    $man[$user]['closed']++;
                                                    $common['closed']++;
                                                } else {

                                                    $man[$user]['overload']++;
                                                    $common['overload']++;
                                                    $man[$user]['closed_types'][$c]++;
                                                    $common['closed_types'][$c]++;
                                                }
                                            }
                                        } catch (Exception $e) {
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
            $time_end = microtime(true);
            $this->info('page - '.$i.', count: '.$tmp->count().', connection: '.$time_сonn.' time: '.($time_end - $time_start));
            $i++;
            $lf2->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }


        }


//        if (Carbon::parse($end)->between('24-10-2024', '01-11-2024') )
//        {
//            $man['Петрова Ольга']['leads']+=100;
//            $man['Петрова Ольга']['closed']+=200;
//
//            $man['Сиренко Оксана']['leads']+=30;
//            $man['Сиренко Оксана']['closed']+=100;
//
//            $man['Кубрина Людмила']['leads']+=40;
//            $man['Кубрина Людмила']['closed']+=150;
//
//            $man['Матюк Анастасия']['leads']+=60;
//            $man['Матюк Анастасия']['closed']+=140;
//
//            $man['Воронова Екатерина']['leads']+=30;
//            $man['Воронова Екатерина']['closed']+=40;
//
//            $man['Прокопенко Наталия']['leads']+=70;
//            $man['Прокопенко Наталия']['closed']+=80;
//
//            $man['Белоусова Екатерина']['leads']+=20;
//            $man['Белоусова Екатерина']['closed']+=20;
//
//            $man['Гребенникова Кристина']['leads']+=70;
//            $man['Гребенникова Кристина']['closed']+=170;
//
//
//            $common['leads']+=400;
//            $common['closed']+=1000;
//        }

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
        \Illuminate\Support\Facades\Log::info('ENDED command: conversion '.$from_first.' - '.$to_first.'. Time:'.(microtime(true)-$time_start)/60);
        $this->info("ENDED");
        return [
            'man' => $man,
            'common' => $common,
            'period' => $salesT,
        ];
    }
}
