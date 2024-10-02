<div class="row">

    @if(!$conversions)
        <div class="col-12 d-flex  justify-content-center">
            <div>
                <h3>
                    Статистика не загружена
                </h3>
                <p class="text-center text-success bg-success-soft fw-bold rounded-3">Подождите, идет загрузка</p>
                <div id="progress-container"></div>
            </div>
        </div>
    @else
        @php
            $chartColors = [
                '#FF6384', // Red
                '#36A2EB', // Blue
                '#FFCE56', // Yellow
                '#4BC0C0', // Green
                '#9966FF', // Purple
                '#FF9F40', // Orange
                '#8B4513', // Saddle Brown
                '#00FF00', // Lime
                '#FFD700', // Gold
                '#8A2BE2', // Blue Violet
                '#00CED1', // Dark Turquoise
                '#FF4500', // Orange Red
                '#DAA520', // Golden Rod
                '#ADFF2F'  // Green Yellow
            ];
            $chartColorsGreyTransparent = [
                'rgba(255, 99, 132, 0.5)', // Red (transparent)
                'rgba(54, 162, 235, 0.5)', // Blue (transparent)
                'rgba(255, 206, 86, 0.5)', // Yellow (transparent)
                'rgba(75, 192, 192, 0.5)', // Green (transparent)
                'rgba(153, 102, 255, 0.5)', // Purple (transparent)
                'rgba(255, 159, 64, 0.5)', // Orange (transparent)
                'rgba(231, 233, 237, 0.5)', // Light Grey (transparent)
                'rgba(139, 69, 19, 0.5)', // Saddle Brown (transparent)
                'rgba(0, 255, 0, 0.5)', // Lime (transparent)
                'rgba(255, 215, 0, 0.5)', // Gold (transparent)
                'rgba(138, 43, 226, 0.5)', // Blue Violet (transparent)
                'rgba(0, 206, 209, 0.5)', // Dark Turquoise (transparent)
                'rgba(255, 69, 0, 0.5)', // Orange Red (transparent)
                'rgba(218, 165, 32, 0.5)', // Golden Rod (transparent)
                'rgba(173, 255, 47, 0.5)'  // Green Yellow (transparent)
            ];

            $color_iterator = 0;
            $prices_mans = [];
            function pluck($array, $key)
            {
                $result = array();

                foreach ($array as $item) {
                    if (is_array($item) && isset($item[$key])) {
                        $result[] = $item[$key];
                    } elseif (is_object($item) && isset($item->$key)) {
                        $result[] = $item->$key;
                    }
                }

                return $result;
            }


            foreach ($conversions['period'] as $key => $conv) {
                $prices = pluck($conv, 'price');
                $arr = [$prices[0]];

                for ($i = 1; $i < count($prices); $i++) {

                    $arr[$i] = $prices[$i] + $arr[$i - 1];

                }
                $prices_mans[$key] = $arr;
            }
            $labels = array_keys($conv);

        @endphp
        <div class="d-flex align-items-start">
            <div class="nav flex-column nav-pills me-3" id="v-pills-tab" aria-orientation="vertical">
                <a class="nav-link active" id="tab-common" data-toggle="tab" href="#div-common" role="tab"
                   aria-controls="common" aria-selected="true">Общее</a>
                @foreach ($conversions['man'] as $name => $user)
                    <a class="nav-link" id="tab-{{ $user['id'] }}" data-toggle="tab" href="#div-{{ $user['id'] }}"
                       role="tab" aria-controls="{{ $user['id'] }}" aria-selected="false">{{ $name }}</a>
                @endforeach
            </div>

            <div class="tab-content" id="myTabMansContent">
                <div class="tab-pane active show" id="div-common" role="tabpanel" aria-labelledby="tab-common">
                    <h2>Аналитика по всем менеджерам</h2>
                    <div class="row">
                        <div class="col-4">
                            <div class="div w-100 d-flex flex-column align-items-center" style="min-height: 350px">
                                <p class="text-center">Конверсия</p>
                                <div class="easypie d-inline-block position-relative"
                                     data-bar-color="#6dbb30"
                                     data-track-color="#eaeaea"
                                     data-scale-color="#cccccc"
                                     data-scale-length="5"
                                     data-line-width="20"
                                     data-line-cap="round"

                                     data-percent="{{ round($conversions['common']['success_period'] / ($conversions['common']['leads'] - $conversions['common']['overload']) * 100, 1) }}">

                                    <div class="absolute-full d-middle pt-0 pb-2">
                                        <div class="flex-none text-center">
                                        <span
                                            class="d-block fs-1">{{ round($conversions['common']['success_period'] / ($conversions['common']['leads'] - $conversions['common']['overload']) * 100, 1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-8">
                            <table class="table table-stripped">
                                    <?php $orderc = [
                                    'Новых лидов за период' => 'leads',
                                    'Из них нерелевантно (Дубль, Ошиблись номером)' => 'overload',
                                    'Из них успешно' => 'success_period',
                                    'Из них не реализовано' => 'closed_period',
                                    'Отложено' => 'deffered',
                                    'Продано на сумму' => 'sell',
                                    'Всего успешно' => 'success',
                                    'Всего не реализовано' => 'closed'
                                ]; ?>
                                    <?php foreach ($orderc as $key => $item): ?>

                                <tr>
                                    <th> {{ $key }} </th>
                                    <td> {{ $conversions['common'][$item] }}</td>
                                        <?php if ($compare): ?>
                                    <td> {{ $conversions2['common'][$item] }}</td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th> Средний чек</th>
                                    @if( $conversions['common']['success'] > 0)
                                        <td> {{ number_format(@($conversions['common']['sell'] / $conversions['common']['success']), 0, '', ' ') }}</td>
                                    @else
                                        <td>0</td>
                                    @endif
                                    {{--                                        @if ($compare)--}}
                                    {{--                                            @if($conversions2['common']['success'] > 0)--}}
                                    {{--                                            <td class="text-secondary"> {{ number_format($conversions2['common']['sell'] / $conversions2['common']['success'], 0, '', ' ') }}</td>--}}
                                    {{--                                            @endif--}}
                                    {{--                                    @endif--}}
                                </tr>
                                <tr>
                                    <th> Среднее время закрытия</th>
                                    <td> <?php
                                             if ($conversions['common']['success'])
                                             {
                                                 $times = \App\UseCases\dict\ReportTrait::divide($conversions['common']['time'], $conversions['common']['success']);
                                                 $hours = floor($times / 3600);
                                             } else
                                             {
                                                 $times = $hours = 'undef';

                                             }
                                             echo $hours . ' часов'

                                             ?></td>
                                        <?php if ($compare): ?>
                                    <td class="text-secondary">
                                            <?php
                                            $times2 = \App\UseCases\dict\ReportTrait::divide($conversions2['common']['time'], $conversions2['common']['success']);

                                            $hours2 = floor($times / 3600);

                                            echo $hours2 . ' часов'

                                            ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <th> Среднее время закрытия (без отложенных)</th>
                                    <td> <?php
                                             if ($conversions['common']['deffered_clean'])
                                             {
                                                 $times = $conversions['common']['time_clean'] / $conversions['common']['deffered_clean'];
                                                 $hours = floor($times / 3600);
                                             } else
                                             {
                                                 $times = $hours = 'undef';

                                             }
                                             echo $hours . ' часов'

                                             ?></td>
                                        <?php if ($compare): ?>
                                    <td class="text-secondary">
                                            <?php
                                            $times2 = \App\UseCases\dict\ReportTrait::divide($conversions2['common']['time_clean'], $conversions2['common']['deffered_clean']) ;

                                            $hours2 = floor($times / 3600);

                                            echo $hours2 . ' часов'

                                            ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <th> Конверсия за период (учитываются только свежие закрытые сделки)</th>
                                    <td> {{ round($conversions['common']['success_period'] / ($conversions['common']['leads'] - $conversions['common']['overload']) * 100, 1) }}</td>
                                </tr>
                                <tr>
                                    <th> Среднее время взятия в лида в работу, мин</th>
                                    <td> {{ \App\UseCases\dict\ReportTrait::divide($conversions['common']['time_calls'], ($conversions['common']['time_call_count'] * 60)) }}</td>
                                </tr>
                                <tr>
                                    <th> Среднее время закрытия первой задачи, мин</th>
                                    <td> {{ \App\UseCases\dict\ReportTrait::divide($conversions['common']['time_tasks'], ($conversions['common']['time_tasks_count'] * 60)) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-12">
                            <canvas id="LeadsChartCommon"
                                    style="width: 100%; height: 400px"></canvas>
                            <script>


                                // Chart.register(ChartDataLabels);
                                const ctxACommon = document.getElementById('LeadsChartCommon');

                                const labelsACommon = ["{!!  implode('","', $labels) !!}"]
                                const dataNCommon = {
                                    labels: labelsACommon,
                                    datasets: [

                                                <?php foreach ($prices_mans as $key => $man): $color_iterator++; ?>
                                        {
                                            label: "{{ $key }}",
                                            data: [{{ implode(',', $man) }}],
                                            fill: false,
                                            borderWidth: 1,
                                            borderColor: "{!!  $chartColors[$color_iterator]  !!}",
                                            backgroundColor: "{!!  $chartColors[$color_iterator] !!}",

                                        },
                                        <?php endforeach; ?>



                                    ]
                                };

                                const configACommon = {
                                    type: 'line',
                                    data: dataNCommon,
                                    options: {
                                        aspectRatio: 2.5,
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: 'График продаж'
                                            }
                                        },

                                    },
                                };
                                const chartACommon = new Chart(ctxACommon, configACommon);

                            </script>
                        </div>
                        <div class="col-12">
                            <table id="myTable"
                                   class="table-datatable table table-bordered table-hover table-striped w-100 m-auto">
                                <thead>
                                <tr>
                                    <th>Менеджер</th>
                                    <th>Конверсия</th>
                                    <th>Новых лидов за период</th>
                                    <th>Из них нерелевантно (Дубль, Ошиблись номером)</th>
                                    <th>Из них успешно</th>
                                    <th>Из них не реализовано</th>
                                    <th>Отложено</th>
                                    <th>Всего успешно</th>
                                    <th>Средний чек</th>
                                    <th>Среднее время закрытия</th>
                                    <th>Сумма продаж</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conversions['man'] as $name => $user): ?>
                                <tr>
                                    <td>{{ $name }}</td>
                                    @if($user['leads'] > 0)
                                        <td>{{ round($user['success_period'] / ($user['leads'] - $user['overload']) * 100, 1) }}</td>
                                    @else
                                        <td>0</td>
                                    @endif
                                    <td>{{ $user['leads'] }}</td>
                                    <td>{{ $user['overload'] }}</td>
                                    <td>{{ $user['success_period'] }}</td>
                                    <td>{{ $user['closed_period'] }}</td>
                                    <td>{{ $user['deffered'] }}</td>
                                    <td>{{ $user['success'] }}</td>
                                    <td>{{ $user['success'] ? number_format($user['sell'] / $user['success'], 0, '', ' ') : 0 }}</td>
                                    <td>{{ $user['deffered_clean'] ? floor(($user['time_clean'] / $user['deffered_clean']) / 3600) : 0 }}</td>
                                    <td>{{ number_format($user['sell'], 0, '', ' ') }}</td>
                                </tr>

                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <script>
                                $('#myTable').DataTable({
                                    paging: false,
                                    searching: false
                                });
                            </script>
                        </div>
                        <div class="col-4">
                            <canvas id="FormsChartCommon"
                                    style="width: 100%; height: 400px"></canvas>
                            <script>

                                // Chart.register(ChartDataLabels);
                                const ctxFormsChartCommon = document.getElementById('FormsChartCommon');

                                const labelsFormsChartCommon = ["{!!  implode('","', array_keys($conversions['common']['types']))  !!}"]
                                const dataFormsChartCommon = {
                                    labels: labelsFormsChartCommon,
                                    datasets: [

                                        {
                                            label: "Период",
                                            data: [{{ implode(',', array_values($conversions['common']['types'])) }}],
                                            fill: true,
                                            backgroundColor: {!!  json_encode($chartColorsGreyTransparent)  !!},
                                            borderColor: {!!  json_encode($chartColors)  !!},
                                            borderWidth: 1,
                                            order: 2
                                        },

                                                <?php if ($compare): ?>

                                        {
                                            label: "Сравнение",
                                            data: [{{ implode(',', array_values($conversions2['common']['types'])) }}],
                                            fill: true,
                                            borderWidth: 1,
                                            order: 2
                                        },
                                        <?php endif; ?>


                                    ]
                                };

                                const configFormsChartCommon = {
                                    type: 'pie',
                                    data: dataFormsChartCommon,
                                    options: {
                                        aspectRatio: 1,
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: 'Формы обучения'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                type: 'linear',
                                                display: true,
                                                position: 'left',
                                            },
                                            y1: {
                                                type: 'linear',
                                                display: true,
                                                position: 'right',

                                            },
                                        }
                                    },
                                };
                                const chartFormsChartCommon = new Chart(ctxFormsChartCommon, configFormsChartCommon);

                            </script>
                        </div>
                        @if($conversions['common']['types_price'])
                            <div class="col-4">
                                <canvas id="FormsPriceChartCommon"
                                        style="width: 100%; height: 400px"></canvas>
                                <script>


                                    // Chart.register(ChartDataLabels);
                                    const ctxFormsPriceChartCommon = document.getElementById('FormsPriceChartCommon');

                                    const labelsFormsPriceChartCommon = ["{!!  implode('","', array_keys($conversions['common']['types_price'])) !!}"]
                                    const dataFormsPriceChartCommon = {
                                        labels: labelsFormsPriceChartCommon,
                                        datasets: [

                                            {
                                                label: "Период",
                                                data: [{{ implode(',', array_values($conversions['common']['types_price'])) }}],
                                                fill: true,
                                                backgroundColor: {!!  json_encode($chartColorsGreyTransparent)  !!},
                                                borderColor: {!!  json_encode($chartColors)  !!},
                                                borderWidth: 1,
                                                order: 2
                                            },

                                                    <?php if ($compare): ?>

                                            {
                                                label: "Сравнение",
                                                data: [{{ implode(',', array_values($conversions2['common']['types_price'])) }}],
                                                fill: true,
                                                borderWidth: 1,
                                                order: 2
                                            },
                                            <?php endif; ?>


                                        ]
                                    };

                                    const configFormsPriceChartCommon = {
                                        type: 'pie',
                                        data: dataFormsPriceChartCommon,
                                        options: {
                                            aspectRatio: 1,
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    position: 'top',
                                                },
                                                title: {
                                                    display: true,
                                                    text: 'Формы обучения (суммы)'
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'left',
                                                },
                                                y1: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'right',

                                                },
                                            }
                                        },
                                    };
                                    const chartFormsPriceChartCommon = new Chart(ctxFormsPriceChartCommon, configFormsPriceChartCommon);

                                </script>
                            </div>
                        @endif

                        <div class="col-6">
                            <canvas id="ReasonsChartCommon"
                                    style="width: 100%; height: 400px"></canvas>
                            <script>


                                // Chart.register(ChartDataLabels);
                                const ctxReasonsChartCommon = document.getElementById('ReasonsChartCommon');

                                const labelsReasonsChartCommon = ["{!!  implode('","', array_keys($conversions['common']['reasons']))  !!}"]
                                const dataReasonsChartCommon = {
                                    labels: labelsReasonsChartCommon,
                                    datasets: [

                                        {
                                            label: "Период",
                                            data: [{{ implode(',', array_values($conversions['common']['reasons'])) }}],
                                            fill: true,
                                            backgroundColor: {!!  json_encode($chartColorsGreyTransparent)  !!},
                                            borderWidth: 1,
                                            order: 2
                                        },

                                                <?php if ($compare): ?>

                                        {
                                            label: "Сравнение",
                                            data: [{{ implode(',', array_values($conversions2['common']['reasons'])) }}],
                                            fill: true,
                                            borderWidth: 1,
                                            order: 2
                                        },
                                        <?php endif; ?>


                                    ]
                                };

                                const configReasonsChartCommon = {
                                    type: 'pie',
                                    data: dataReasonsChartCommon,
                                    options: {
                                        aspectRatio: 1,
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: 'Причины отказа'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                type: 'linear',
                                                display: true,
                                                position: 'left',
                                            },
                                            y1: {
                                                type: 'linear',
                                                display: true,
                                                position: 'right',

                                            },
                                        }
                                    },
                                };
                                const chartReasonsChartCommon = new Chart(ctxReasonsChartCommon, configReasonsChartCommon);

                            </script>
                        </div>
                    </div>
                </div>

                    <?php foreach ($conversions['man'] as $name => $user):
//                    $xonv = $user['success'] / ($user['leads'] - $user['overload']) * 100;
                    if ($user['leads'] > 0)
                    {
                        $xonv = $user['success_period'] / ($user['leads'] - $user['overload']) * 100;
                    } else
                    {
                        $xonv = 0;
                    }
                    $avg = $user['success'] ? $user['sell'] / $user['success'] : 0;
                    ?>

                <div class="tab-pane fade" id="div-{{ $user['id'] }}" role="tabpanel"
                     aria-labelledby="tab-{{ $user['id'] }}">
                    <h3>Аналитика по менеджеру: {{ $name }}</h3>
                    <div class="row">
                        <div class="col-4">
                            <div class="div d-flex flex-column align-items-center" style="width: 100%">
                                <p class="text-center">Конверсия</p>
                                <div class="easypie d-inline-block position-relative"
                                     data-bar-color="{{ $xonv < 20 ? '#c18252' : '#6dbb30' }}"
                                     data-track-color="#eaeaea"
                                     data-scale-color="#cccccc"
                                     data-scale-length="5"
                                     data-line-width="20"
                                     data-line-cap="round"
                                     data-percent="{{ round($xonv, 1) }}">

                                    <div class="absolute-full d-middle pt-0 pb-2">
                                        <div class="flex-none text-center">
                                            <span class="d-block fs-1">{{ round($xonv, 1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 ">
                            <table class="table table-stripped">
                                    <?php if ($compare): ?>
                                <tr>
                                    <td></td>
                                    <td>Выбранный период</td>
                                    <td>Сравнение</td>

                                </tr>

                                <?php endif; ?>
                                    <?php foreach ($orderc as $key => $item): ?>
                                <tr>
                                    <th> {{ $key }} </th>
                                    <td> {{ $user[$item] }}</td>
                                        <?php if ($compare): ?>
                                    <td class="text-secondary"> {{ $conversions2['man'][$name][$item] }}</td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th> Средний чек</th>
                                    <td> {{ number_format($avg, 0, '', ' ') }}</td>
{{--                                    @if($compare)--}}
{{--                                        <td class="text-secondary"> {{ number_format($conversions2['man'][$name]['sell'] / $conversions2['man'][$name]['success'], 0, '', ' ') }}</td>--}}
{{--                                    @endif--}}
                                </tr>
                                <tr>
                                    <th> Среднее время закрытия</th>
                                    <td> <?php
                                             $times = $user['success'] ? $user['time'] / $user['success'] : 0;
                                             $hours = floor($times / 3600);
                                             echo $hours . ' часов'

                                             ?></td>
                                        <?php if ($compare): ?>
                                    <td class="text-secondary">
                                            <?php
                                            $times2 = \App\UseCases\dict\ReportTrait::divide( $conversions2['man'][$name]['time'], $conversions2['man'][$name]['success']) ;

                                            $hours2 = floor($times2 / 3600);

                                            echo $hours2 . ' часов'

                                            ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <th> Среднее время закрытия (без отложенных)</th>
                                    <td> <?php
                                             $times = $user['deffered_clean'] ? $user['time_clean'] / $user['deffered_clean'] : 0;
                                             $hours = floor($times / 3600);
                                             echo $hours . ' часов'

                                             ?></td>
                                        <?php if ($compare): ?>
                                    <td class="text-secondary">
                                            <?php
                                            $times2 = \App\UseCases\dict\ReportTrait::divide($conversions2['man'][$name]['time_clean'], $conversions2['man'][$name]['deffered_clean'])  ;

                                            $hours2 = floor($times2 / 3600);

                                            echo $hours2 . ' часов'

                                            ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <th> Конверсия за период (учитываются только свежие закрытые сделки)</th>
                                    @if($conversions['man'][$name]['leads'] > 0)
                                        <td> {{ round(\App\UseCases\dict\ReportTrait::divide($conversions['man'][$name]['success_period'], ($conversions['man'][$name]['leads'] - $conversions['man'][$name]['overload']))) }}</td>
                                    @else
                                        <td>0</td>
                                    @endif
                                </tr>
                                <tr>
                                    <th> Среднее время взятия в лида в работу, мин</th>
                                    <td> {{ \App\UseCases\dict\ReportTrait::divide($conversions['man'][$name]['time_calls'], ($conversions['man'][$name]['time_call_count'] * 60)) }}</td>
                                </tr>
                                <tr>
                                    <th> Среднее время закрытия первой задачи, мин</th>
                                    <td> {{ \App\UseCases\dict\ReportTrait::divide($conversions['man'][$name]['time_tasks'], ($conversions['man'][$name]['time_tasks_count'] * 60)) }}</td>
                                </tr>
                                <!--                                    <tr>-->
                                <!--                                        <th> Конверсия </th>-->
                                <!--                                        <td> --><?php //=round($conversions['man'][$name]['success'] / $conversions['man'][$name]['leads'] * 100, 1)
                                                                                        ?><!--</td>-->
                                <!--                                        --><?php //if ($compare):
                                                                                   ?>
                                    <!--                                            <td class="text-secondary">-->
                                <!--                                                --><?php //=round($conversions2['man'][$name]['success'] / $conversions2['man'][$name]['leads'] * 100, 1)
                                                                                           ?><!--</td>-->
                                <!--                                        --><?php //endif;
                                                                                   ?>
                                    <!--                                    </tr>-->
                            </table>
                        </div>
                        <div class="col-12">
                            <canvas id="LeadsChart{{ $user['id'] }}"
                                    style="width: 100%; height: 400px"></canvas>
                            <script>


                                // Chart.register(ChartDataLabels);
                                const ctxA{{ $user['id'] }} = document.getElementById('LeadsChart{{ $user['id'] }}');
                                const labelsA{{ $user['id'] }} = ["{!! implode('","', array_keys($conversions['period'][$name])) !!} "]
                                const dataN{{ $user['id'] }} = {
                                    labels: labelsA{{ $user['id'] }},
                                    datasets: [

                                        {
                                            yAxisID: 'y',
                                            label: "Входящие лиды",
                                            data: [{{ implode(',', pluck($conversions['period'][$name], 'new')) }}],
                                            fill: true,
                                            backgroundColor: '#fde5c9bf',
                                            borderColor: '#db8e33',
                                            borderWidth: 1,
                                            order: 2
                                        },
                                        {
                                            yAxisID: 'y',
                                            label: "Успешные",
                                            data: [{{ implode(',', pluck($conversions['period'][$name], 'success')) }}],
                                            fill: true,
                                            backgroundColor: [
                                                'rgba(146,210,124,0.29)',
                                            ],
                                            borderColor: [
                                                'rgb(88,126,76)',
                                            ],
                                            borderWidth: 1,
                                            type: 'line',
                                            order: 0,
                                            tension: 0.4
                                        },
                                        {
                                            yAxisID: 'y',
                                            label: "Не реализовано",
                                            data: [{{ implode(',', pluck($conversions['period'][$name], 'closed')) }}],
                                            fill: false,
                                            backgroundColor: [],
                                            borderColor: [],
                                            borderWidth: 1,
                                            type: 'line',
                                            order: 1,
                                            tension: 0.4
                                        },
                                        {
                                            yAxisID: 'y1',
                                            label: "Продажи",
                                            data: [{{ implode(',', $prices_mans[$name]) }}],
                                            fill: false,
                                            backgroundColor: [
                                                '#ff99af'
                                            ],
                                            borderColor: [
                                                '#FF6384'
                                            ],
                                            borderWidth: 1,
                                            type: 'line',
                                            order: 1,
                                            tension: 0.4
                                        },


                                    ]
                                };

                                const configA{{ $user['id'] }} = {
                                    type: 'bar',
                                    data: dataN{{ $user['id'] }},
                                    options: {
                                        aspectRatio: 2.5,
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: 'Распределение лидов во времени'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                type: 'linear',
                                                display: true,
                                                position: 'left',
                                            },
                                            y1: {
                                                type: 'linear',
                                                display: true,
                                                position: 'right',

                                            },
                                        }
                                    },
                                };
                                const chartA{{ $user['id'] }} = new Chart(ctxA{{ $user['id'] }}, configA{{ $user['id'] }});

                            </script>
                        </div>
                        <div class="col-4">
                            <canvas id="FormsChart{{ $user['id'] }}"
                                    style="width: 100%; height: 400px"></canvas>
                            <script>

                            @if($user['types'])
                                // Chart.register(ChartDataLabels);
                                const ctxFormsChart{{ $user['id'] }} = document.getElementById('FormsChart{{ $user['id'] }}');

                                const labelsFormsChart{{ $user['id'] }} = ["{!!  implode('","', array_keys($user['types'])) !!}"]
                                const dataFormsChart{{ $user['id'] }} = {
                                    labels: labelsFormsChart{{ $user['id'] }},
                                    datasets: [

                                        {
                                            label: "Период",
                                            data: [{{ implode(',', array_values($user['types'])) }}],
                                            fill: true,
                                            backgroundColor: {!! json_encode($chartColorsGreyTransparent) !!} ,
                                            borderColor: {!!  json_encode($chartColors) !!},
                                            borderWidth: 1,
                                            order: 2
                                        },

                                                <?php if ($compare): ?>

                                        {
                                            label: "Сравнение",
                                            data: [{{ implode(',', array_values($conversions2['man'][$name]['types'])) }}],
                                            fill: true,
                                            borderWidth: 1,
                                            order: 2
                                        },
                                        <?php endif; ?>


                                    ]
                                };

                                const configFormsChart{{ $user['id'] }} = {
                                    type: 'pie',
                                    data: dataFormsChart{{ $user['id'] }},
                                    options: {
                                        aspectRatio: 1,
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: 'Формы обучения'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                type: 'linear',
                                                display: true,
                                                position: 'left',
                                            },
                                            y1: {
                                                type: 'linear',
                                                display: true,
                                                position: 'right',

                                            },
                                        }
                                    },
                                };
                                const chartFormsChart{{ $user['id'] }} = new Chart(ctxFormsChart{{ $user['id'] }}, configFormsChart{{ $user['id'] }});
                            @endif
                            </script>
                        </div>
                        <div class="col-4">
                            <canvas id="FormsPriceChart{{ $user['id'] }}"
                                    style="width: 100%; height: 400px"></canvas>
                            @if($user['types_price'])
                                <script>


                                    // Chart.register(ChartDataLabels);
                                    const ctxFormsPriceChart{{ $user['id'] }} = document.getElementById('FormsPriceChart{{ $user['id'] }}');

                                    const labelsFormsPriceChart{{ $user['id'] }} = ["{!!  implode('","', array_keys($user['types_price']))  !!}"]
                                    const dataFormsPriceChart{{ $user['id'] }} = {
                                        labels: labelsFormsPriceChart{{ $user['id'] }},
                                        datasets: [

                                            {
                                                label: "Период",
                                                data: [{{ implode(',', array_values($user['types_price'])) }}],
                                                fill: true,
                                                backgroundColor: {!! json_encode($chartColorsGreyTransparent) !!} ,
                                                borderColor: {!!  json_encode($chartColors) !!} ,
                                                borderWidth: 1,
                                                order: 2
                                            },

                                                    <?php if ($compare): ?>

                                            {
                                                label: "Сравнение",
                                                data: [{{ implode(',', array_values($conversions2['man'][$name]['types_price'])) }}],
                                                fill: true,
                                                borderWidth: 1,
                                                order: 2
                                            },
                                            <?php endif; ?>


                                        ]
                                    };

                                    const configFormsPriceChart{{ $user['id'] }} = {
                                        type: 'pie',
                                        data: dataFormsPriceChart{{ $user['id'] }},
                                        options: {
                                            aspectRatio: 1,
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    position: 'top',
                                                },
                                                title: {
                                                    display: true,
                                                    text: 'Формы обучения (суммы)'
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'left',
                                                },
                                                y1: {
                                                    type: 'linear',
                                                    display: true,
                                                    position: 'right',

                                                },
                                            }
                                        },
                                    };
                                    const chartFormsPriceChart{{ $user['id'] }} = new Chart(ctxFormsPriceChart{{ $user['id'] }}, configFormsPriceChart{{ $user['id'] }});

                                </script>
                            @endif
                        </div>
                        <div class="col-6">
                            <canvas id="ReasonsChart{{$user['id']}}"
                                    style="width: 100%; height: 400px"></canvas>
                            <script>


                                // Chart.register(ChartDataLabels);
                                const ctxReasonsChart{{ $user['id'] }} = document.getElementById('ReasonsChart{{ $user['id'] }}');

                                const labelsReasonsChart{{ $user['id'] }} = ["{!!  implode('","', array_keys($conversions['common']['reasons']))  !!}"]
                                const dataReasonsChart{{ $user['id'] }} = {
                                    labels: labelsReasonsChart{{ $user['id'] }},
                                    datasets: [

                                        {
                                            label: "Период",
                                            data: [{{ implode(',', array_values($conversions['common']['reasons'])) }}],
                                            fill: true,
                                            backgroundColor: {!!  json_encode($chartColorsGreyTransparent) !!},
                                            borderWidth: 1,
                                            order: 2
                                        },

                                                <?php if ($compare): ?>

                                        {
                                            label: "Сравнение",
                                            data: [{{ implode(',', array_values($conversions2['common']['reasons'])) }}],
                                            fill: true,
                                            borderWidth: 1,
                                            order: 2
                                        },
                                        <?php endif; ?>


                                    ]
                                };

                                const configReasonsChart{{ $user['id'] }} = {
                                    type: 'pie',
                                    data: dataReasonsChart{{ $user['id'] }},
                                    options: {
                                        aspectRatio: 1,
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: 'Причины отказа'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                type: 'linear',
                                                display: true,
                                                position: 'left',
                                            },
                                            y1: {
                                                type: 'linear',
                                                display: true,
                                                position: 'right',

                                            },
                                        }
                                    },
                                };
                                const chartReasonsChart{{ $user['id'] }} = new Chart(ctxReasonsChart{{ $user['id'] }}, configReasonsChart{{ $user['id'] }});

                            </script>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    @endif
</div>

<script>

</script>
<script>
    @if(!$conversions)
    function getConv() {
        if (i === 0) {
            fetch('/start-loading/{{$first[0]}}|{{$first[1]}}');
        }
    }

    $(document).ready(function () {
        setTimeout(getConv, 2000)

    })
    // Enable pusher logging - don't include this in production
    Pusher.logToConsole = true;
    let i = 0
    var pusher = new Pusher('f3b91429fdc791dda538', {
        cluster: 'eu'
    });

    var channel = pusher.subscribe('progress-channel');
    channel.bind('progress-updated-{{$first[0]}}', function (data) {
        console.log(data)
        $('#bar').text(data + "%")
        i++
        updateProgressBar(data);
        if (data === 100) {
            window.location.reload()
        }
        $('#progress-bar').text(data + "%")
    });
    const bar = new ProgressBar.Circle('#progress-container', {
        strokeWidth: 8,
        color: '#3498db',
        trailColor: '#eee',
        trailWidth: 1,
        easing: 'easeInOut',
        duration: 1000,
        from: {color: '#3498db', width: 1},
        to: {color: '#3498db', width: 6},
        step: function (state, circle) {
            circle.path.setAttribute('stroke', state.color);
            const value = Math.round(circle.value() * 10000) / 100;
            if (value === 0) {
                circle.setText('');
            } else {
                circle.setText(value + '%');
            }
        }
    });

    // Функция для обновления прогресс-бара
    function updateProgressBar(progress) {
        bar.animate(progress / 100); // progress должен быть числом от 0 до 100
    }
    @endif


    $(document).ready(function () {
        $("#check_compare").on('click', function () {
            console.log($(this).is(":checked"))
            if ($(this).is(":checked")) {
                $('#second_period').attr('disabled', false)
            } else {
                $('#second_period').attr('disabled', true)

            }
        })
    })
</script>
