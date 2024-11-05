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
use Exception;
use Illuminate\Console\Command;
use Laravel\Reverb\Loggers\Log;

class YearReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:year {start=null} {end=null}';

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

        $start = '01-09-2023 03:00:00';
        $end = '01-10-2024 03:00:00';

        error_reporting(E_ERROR);
        $answer = [];
        $apiClient = AmoService::getClient();

        $users = [];
        $users_clean = $apiClient->users()->get();
//        dd($users_clean->count());
        foreach ($users_clean as $item) {
            $users[$item->getId()] = $item->getName();
        }
        //region New
        $lf = new \AmoCRM\Filters\LeadsFilter();
        $lf->setCreatedAt(
            (new \AmoCRM\Filters\BaseRangeFilter())
                ->setFrom(\Carbon\Carbon::parse($start)->timestamp)
                ->setTo(\Carbon\Carbon::parse($end)->timestamp)
        );
        $lf->setLimit(200);
        $i = 1;
        $co = 0;
        $pips = [];
        $lf->setPipelineIds([Pipelines::RETAIL, Pipelines::DEFFERED, Pipelines::SPAM]);
        while (true) {
            $tmp = $apiClient->leads()->get($lf, [LeadModel::CONTACTS]);
            foreach ($tmp as $lead) {
                /** @var LeadModel $lead */
                $my = Carbon::parse($lead->getCreatedAt())->translatedFormat('F y');
                $user = $users[$lead->getResponsibleUserId()];
                if (!$user)
                {
                    dd($lead);
                }
                $answer['common']['leads'][$my]++;
                $answer[$user]['leads'][$my]++;
                if ($lead->getPipelineId() == Pipelines::SPAM) {
                    $answer['common']['spam'][$my]++;
                    $answer[$user]['spam'][$my]++;
                } else {
                    $answer['common']['leads_clean'][$my]++;
                    $answer[$user]['leads_clean'][$my]++;
                }
            }
            $i++;
            $co += $tmp->count();
            $this->info($i . ' ' . $co);
            $lf->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }
        }
        //endregion

        //region Closed
        $lf2 = new \AmoCRM\Filters\LeadsFilter();
        $lf2->setClosedAt(
            (new \AmoCRM\Filters\BaseRangeFilter())
                ->setFrom(\Carbon\Carbon::parse($start)->timestamp)
                ->setTo(\Carbon\Carbon::parse($end)->timestamp)
        );
        $lf2->setLimit(200);
        $i = 1;
        $test = [];
        $co = 0;
        $lf2->setPipelineIds([Pipelines::RETAIL]);
        while (true) {
            $tmp = $apiClient->leads()->get($lf2, [LeadModel::CONTACTS]);
            foreach ($tmp as $lead) {
                /** @var LeadModel $lead */

                $my = Carbon::parse($lead->getClosedAt())->translatedFormat('F y');
                $my2 = Carbon::parse($lead->getCreatedAt())->translatedFormat('F y');
                $user = $users[$lead->getResponsibleUserId()];


                if ($lead->getStatusId() == 142) {
                    $answer['common']['success'][$my]++;
                    $answer[$user]['success'][$my]++;
                    $answer['common']['price'][$my] += $lead->getPrice();
                    $answer[$user]['price'][$my] += $lead->getPrice();
                    $test[$my2]++;
                    if ($my2 == $my) {
                        $answer['common']['success_period'][$my]++;
                        $answer[$user]['success_period'][$my]++;
                    }
                    $pips[$lead->getPipelineId()]++;

                } else {
                    if ($csfv = $lead->getCustomFieldsValues()) {
                        if ($c = $csfv->getBy("fieldid", 644039)) {
                            try {
                                $cn = $csfv->getBy("fieldid", 644641);
                                $po_vopr_obuch = false;
                                if ($cn) {
                                    try {
                                        $cn = $cn->getValues()->first()->getValue();
                                        if ($cn == 'Слушатель по вопросу обучения') {
                                            $po_vopr_obuch = true;
                                        }
                                    } catch (Exception $e) {
                                    }
                                }
                                if ($c = $c->getValues()->first()->getValue()) {
                                    if (in_array($c, ['Дубль', 'Ошиблись номером', 'Уже обучается']) or $po_vopr_obuch) {
                                        $answer['common']['overload'][$my]++;
                                        $answer[$user]['overload'][$my]++;
                                    }
                                }
                            } catch (Exception $e) {

                            }
                        }
                    }
                }
            }
            $i++;
            $co += $tmp->count();
            $this->warn($i . ' ' . $co);
            $lf2->setPage($i);
            if ($tmp->count() < 200) {
                break;
            }
        }
        //endregion
        $this->info($co);
        \Illuminate\Support\Facades\Log::info(json_encode($answer, JSON_UNESCAPED_UNICODE));
//        dd($test);
        dd($answer, $pips);
        $this->info($answer);
    }
}
