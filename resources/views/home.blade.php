@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @if(auth()->user()->status == 0)
                    <div class="card">
                        <div class="card-header">
                            Ваш аккаунт не подтвержден
                        </div>
                        <div class="card-body">
                            Для подтверждения напишите на почту <a
                                href="mailto:d.kochura@mzpo.info">d.kochura@mzpo.info</a>
                            с Вашей рабочей почты
                        </div>
                    </div>
                @else
                    <form action="/home" id="periodical" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <label for="hard_refresh">Период</label>

                                <div class="input-group-over position-realtive z-index-1 bg-white form-control-pill">
                                    <input autocomplete="off" type="text" name="first_period" id="first_period"
                                           class="form-control rangepicker"
                                           data-ranges="true"
                                           data-date-start="{{$first[0] }}"
                                           data-date-end="{{ $first[1] }}"
                                           data-date-format="DD/MM/YYYY"
                                           data-quick-locale='{
		"lang_apply"	: "Сохранить",
		"lang_cancel" : "Отмена",
		"lang_crange" : "Свой период",
		"lang_months"	 : ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
		"lang_weekdays" : ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],

		"lang_today"	: "Сегодня",
		"lang_yday"	 : "Вчера",
		"lang_7days"	: "За последнюю неделю",
		"lang_30days" : "За последние 30 дней",
		"lang_tmonth" : "Этот месяц",
		"lang_lmonth" : "Прошлый месяц"
	}' value="">
                                    <span class="fi fi-calendar fs-2 mx-4"></span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input form-check-input-default" type="checkbox"
                                               value=""
                                               id="check_compare" {{$compare ? 'checked' : ''}}>
                                        <label class="form-check-label" for="checkDefault">
                                            Сравнить с
                                        </label>
                                    </div>
                                </div>
                                <div class="input-group-over position-realtive z-index-1 bg-white form-control-pill">
                                    <input autocomplete="off" type="text" name="second_period" id="second_period"
                                           class="form-control rangepicker"
                                           data-ranges="true"

                                           @if ($compare)
                                               data-date-start="{{$second[0]}}"
                                           data-date-end="{{$second[1]}}"
                                           @else
                                               disabled="disabled"
                                           @endif
                                           data-date-format="DD/MM/YYYY"
                                           data-quick-locale='{
		"lang_apply"	: "Сохранить",
		"lang_cancel" : "Отмена",
		"lang_crange" : "Свой период",
		"lang_months"	 : ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
		"lang_weekdays" : ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],

		"lang_today"	: "Сегодня",
		"lang_yday"	 : "Вчера",
		"lang_7days"	: "За последнюю неделю",
		"lang_30days" : "За последние 30 дней",
		"lang_tmonth" : "Этот месяц",
		"lang_lmonth" : "Прошлый месяц"
	}' value="">
                                    <span class="fi fi-calendar fs-2 mx-4"></span>
                                </div>
                            </div>
                            <div class="col-2 align-content-end">
                                <button type="submit" id="make_report" class="btn btn-primary rounded-pill">Заказать
                                    отчет
                                </button>
                            </div>
                            <div class="col-2">
                                <div class="form-check mb-2">
                                    <input class="form-check-input form-check-input-default" name="hard_refresh" type="checkbox" id="hard_refresh" >
                                    <label class="form-check-label" for="hard_refresh">
                                        Обновить принудительно
                                    </label>
                                </div>

                            </div>
                        </div>
                    </form>
                    <ul class="nav nav-pills mt-3 justify-content-center" id="myTab1">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="leads-tab" data-toggle="tab" href="#leads" role="tab"
                               aria-controls="leads"
                               aria-selected="true">Статистика по лидам</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pages-tab" data-toggle="tab" href="#pages" role="tab"
                               aria-controls="pages"
                               aria-selected="false">Посадочные страницы</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="managers-tab" data-toggle="tab" href="#managers" role="tab"
                               aria-controls="managers"
                               aria-selected="false">По менеджерам</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="postsales-tab" data-toggle="tab" href="#postsales" role="tab"
                               aria-controls="postsales"
                               aria-selected="false">Допродажи</a>
                        </li>
                    </ul>
                    @if($objects)
                        <div class="tab-content" id="myTabContent1">
                            <div class="tab-pane fade show active" id="leads" role="tabpanel"
                                 aria-labelledby="leads-tab">
                                <div class="row mt-3">
                                    <div class="col-5">
                                            <?php if ($compare): ?>
                                        <p class="p-2 bg-info-soft text-white rounded advices">При включенном режиме
                                            сравнения в колонке
                                            "Лиды за выбранный период" (цветная) указана статистика за первый выбранный
                                            период
                                            ({{$first}})<br>Колонка "Период для сравнения", соответственно, отражает
                                            аналитику за второй выбранный период ({{$second}})</p>
                                        <?php endif; ?>
                                        <div class="tab-content" id="myTabContentPipes">
                                            <div class="tab-pane fade show active" id="piptable" role="tabpanel"
                                                 aria-labelledby="home-tab">
                                                <table class="table table-bordered table-hover table-striped">
                                                    <thead>
                                                    <th>Воронка</th>
                                                    <th>Лиды за выбранный период</th>
                                                    @if ($compare)
                                                        <th>Период для сравнения</th>
                                                    @endif
                                                    </thead>
                                                    <tbody>
                                                    @foreach (\App\UseCases\dict\ReportTrait::PIPES_ORDER as $item)
                                                        {{--                                                        @if(!isset($objects['pipes'][$item])) @continue($loop) @endif--}}
                                                        @php $pipe = isset($objects['pipes'][$item]) ? $objects['pipes'][$item] : 0;
                                                        @endphp
                                                        <tr>
                                                            <th>{{$item}}</th>
                                                            <td class="{{ ($compare) ? ($objects['pipes'][$item] >= $objects2['pipes'][$item]) ? 'bg-success-soft' : 'bg-danger-soft' : '' }} ">{{$pipe}} </td>
                                                            @if ($compare)
                                                                <td>{{$objects2['pipes'][$item] ?? 0}}</td>
                                                            @endif
                                                        </tr>

                                                    @endforeach
                                                    <tr class="border-top border"></tr>
                                                    <tr>

                                                        <th>Итого (отл+розн)</th>
                                                            <?php
                                                            $sum = $objects['pipes']['Отложенные'] + $objects['pipes']['Продажи (Розница)'];
                                                            if ($compare) {
                                                                $sum2 = $objects2['pipes']['Отложенные'] + $objects2['pipes']['Продажи (Розница)'];
                                                            }
                                                            ?>
                                                        <th class="<?= ($compare) ? ($sum >= $sum2) ? 'bg-success-soft' : 'bg-danger-soft' : '' ?> "><?= $sum ?></th>
                                                            <?php if ($compare): ?>
                                                        <th><?= $sum2 ?></th>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <tr>
                                                        <th>Успешно в рознице</th>
                                                        <th class="<?= ($compare) ? ($objects['pipes']['retail_success'] >= $objects2['pipes']['retail_success']) ? 'bg-success-soft' : 'bg-danger-soft' : '' ?> "><?= $objects['pipes']['retail_success'] ?></th>
                                                            <?php if ($compare): ?>
                                                        <th><?= $objects2['pipes']['retail_success'] ?? 0 ?></th>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <tr>
                                                        <th>Закрыто и не реализовано</th>
                                                        <th class="<?= ($compare) ? ($objects['pipes']['retail_closed'] >= $objects2['pipes']['retail_closed']) ? 'bg-success-soft' : 'bg-danger-soft' : '' ?> "><?= $objects['pipes']['retail_closed'] ?></th>
                                                            <?php if ($compare): ?>
                                                        <th><?= $objects2['pipes']['retail_closed'] ?? 0 ?></th>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <tr class="border-top border"></tr>
                                                    <tr>
                                                        <th>Квал лиды</th>
                                                        <td>{{$objects['kval']}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Лиды yandex-direct</th>
                                                        <td>{{$objects['utm']}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Квал лиды yandex-direct</th>
                                                        <td>{{$objects['utm_kval']}}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="tab-pane fade" id="pipchart" role="tabpanel"
                                                 aria-labelledby="contact-tab">

                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-7">
                                            <?php if ($compare): ?>
                                        <p class="p-2 bg-info-soft text-white rounded advices">При включенном режиме
                                            сравнения в первой
                                            строке таблице для менеджера (цветная) указана статистика за первый
                                            выбранный
                                            период
                                            (<?= $_GET['first_period'] ?>)<br>Второй ряд, соответственно, отражает
                                            аналитику
                                            за второй
                                            выбранный период (<?= $_GET['second_period'] ?>)</p>
                                        <?php endif; ?>
                                        <ul class="nav nav-tabs" id="myTab">
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home"
                                                   role="tab"
                                                   aria-controls="home" aria-selected="true">Таблица</a>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact"
                                                   role="tab"
                                                   aria-controls="contact" aria-selected="false">График</a>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="myTabContent">
                                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                                 aria-labelledby="home-tab">
                                                <table
                                                    class="table table-bordered table-hover table-striped table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th>Менеджер</th>
                                                        <th>
                                                            Всего лидов
                                                        </th>
                                                        <th data-toggle="tooltip" data-placement="top"
                                                            title="Сделки, созданные и успешно закрытые за период">
                                                            Успешно
                                                        </th>
                                                        <th data-toggle="tooltip" data-placement="top"
                                                            title="Сделки, созданные и не реализованные за период">
                                                            Закрыто
                                                        </th>
                                                        <th>В работе</th>
                                                        <th>Счета</th>
                                                        <th>Корп</th>
                                                        <th>Прайс</th>

                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach (\App\UseCases\dict\ReportTrait::USER_CASTS as $otd => $mans)
                                                        <tr>
                                                            <th colspan="8" class="text-center"><?= $otd ?></th>
                                                        </tr>
                                                        @foreach ($mans as $key)
                                                            @if(!isset($objects['mans'][$key]))
                                                                @continue($loop)
                                                            @endif
                                                            <tr class="">
                                                                <td {{ $compare ? ' rowspan="2"' : '' }}>{{ $key }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['common'] >= $objects2['mans'][$key]['common']) ? 'bg-success-soft-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['common'] }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['success'] >= $objects2['mans'][$key]['success']) ? 'bg-success-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['success'] }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['closed'] >= $objects2['mans'][$key]['closed']) ? 'bg-success-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['closed'] }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['work'] >= $objects2['mans'][$key]['work']) ? 'bg-success-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['work'] }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['bill'] >= $objects2['mans'][$key]['bill']) ? 'bg-success-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['bill'] }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['korp'] >= $objects2['mans'][$key]['korp']) ? 'bg-success-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['korp'] }}</td>
                                                                <td class="{{ ($compare) ? ($objects['mans'][$key]['price'] >= $objects2['mans'][$key]['price']) ? 'bg-success-soft' : 'bg-danger-soft' : '' }}">{{ $objects['mans'][$key]['price'] }}</td>

                                                            </tr>
                                                            @if($compare)
                                                                <tr class="border-bottom border-dark">
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['common'] }}</td>
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['success'] }}</td>
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['closed'] }}</td>
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['work'] }}</td>
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['bill'] }}</td>
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['korp'] }}</td>
                                                                    <td class="text-secondary">{{ $objects2['mans'][$key]['price'] }}</td>

                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    @endforeach

                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="tab-pane fade" id="contact" role="tabpanel"
                                                 aria-labelledby="contact-tab">
                                                <div class="row">
                                                    <canvas id="densityChart"
                                                            style="width: 100%; height: 600px"></canvas>
                                                    <script>

                                                        // Chart.register(ChartDataLabels);
                                                        const ctx = document.getElementById('densityChart');

                                                        const labels = ["<?= implode('","', array_keys($objects['mans'])) ?>"]
                                                        const data = {
                                                            labels: labels,
                                                            datasets: [

                                                                {
                                                                    axis: 'y',
                                                                    label: "Успешные",
                                                                    data: [<?= implode(',', $objects['additional']['success']) ?>],
                                                                    fill: false,
                                                                    backgroundColor: [
                                                                        'rgba(146,210,124,0.29)',
                                                                    ],
                                                                    borderColor: [
                                                                        'rgb(88,126,76)',
                                                                    ],
                                                                    borderWidth: 1
                                                                },
                                                                {
                                                                    axis: 'y',
                                                                    label: "Выставлен счет",
                                                                    data: [<?= implode(',', $objects['additional']['bill']) ?>],
                                                                    fill: false,
                                                                    backgroundColor: [
                                                                        'rgba(215,141,79,0.46)',
                                                                    ],
                                                                    borderColor: [
                                                                        'rgb(110,75,39)',
                                                                    ],
                                                                    borderWidth: 1
                                                                },
                                                                {
                                                                    axis: 'y',
                                                                    label: "Корп",
                                                                    data: [<?= implode(',', $objects['additional']['korp']) ?>],
                                                                    fill: false,
                                                                    backgroundColor: [
                                                                        'rgba(187,107,140,0.49)',
                                                                    ],
                                                                    borderColor: [
                                                                        'rgb(86,39,110)',
                                                                    ],
                                                                    borderWidth: 1
                                                                }, {
                                                                    axis: 'y',
                                                                    label: "В работе",
                                                                    data: [<?= implode(',', $objects['additional']['work']) ?>],
                                                                    fill: false,
                                                                    backgroundColor: [
                                                                        'rgba(99,148,222,0.51)',
                                                                    ],
                                                                    borderColor: [
                                                                        'rgb(27,58,77)',
                                                                    ],
                                                                    borderWidth: 1
                                                                },
                                                                {
                                                                    axis: 'y',
                                                                    label: 'Закрытые',
                                                                    data: [<?= implode(',', $objects['additional']['closed']) ?>],
                                                                    fill: false,
                                                                    backgroundColor: [
                                                                        'rgba(164,164,164,0.6)',
                                                                    ],
                                                                    borderColor: [
                                                                        'rgb(91,91,91)',
                                                                    ],
                                                                    borderWidth: 1
                                                                },


                                                            ]
                                                        };

                                                        const config = {
                                                            type: 'bar',
                                                            data,
                                                            // plugins: [ChartDataLabels],
                                                            options: {
                                                                plugins: {
                                                                    legend: {
                                                                        display: true
                                                                    },
                                                                    tooltips: {
                                                                        enabled: false
                                                                    },
                                                                    // datalabels: {
                                                                    //     font: {
                                                                    //         weight: 'bold',
                                                                    //         size: 14
                                                                    //     },
                                                                    //     labels: {
                                                                    //         value: {},
                                                                    //         title: {
                                                                    //             color: 'blue'
                                                                    //         }
                                                                    //     }
                                                                    // }
                                                                },
                                                                indexAxis: 'y',
                                                                hover: {
                                                                    animationDuration: 0
                                                                },
                                                                scales: {
                                                                    x: {
                                                                        stacked: true
                                                                    },
                                                                    y: {
                                                                        stacked: true
                                                                    }
                                                                }
                                                            }
                                                        };
                                                        const chart1 = new Chart(ctx, config);

                                                    </script>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                        <?php if (false): ?>

                                    <?php endif; ?>
                                    {{--                                    <div class="col-7 mt-3">--}}
                                    {{--                                        <div class="row">--}}
                                    {{--                                            <canvas id="pipeschart"></canvas>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                </div>
                                <div class="row">
                                    @if($objects['timestage'] and  count($objects['timestage']) > 1)
                                        <canvas id="timestageCanvas"></canvas>
                                        <script>
                                            const dataT = {
                                                labels: ["<?= implode('","', array_keys($objects['timestage'])) ?>"],
                                                datasets: [
                                                    {
                                                        label: 'Период',
                                                        data: [<?= implode(',', array_values($objects['timestage'])) ?>],
                                                        borderColor: "#36a2eb7d"
                                                    } @if($compare)
                                                    , {
                                                        label: 'Сравнение',
                                                        data: [<?= implode(',', array_values($objects2['timestage'])) ?>],
                                                    }

                                                    @endif
                                                ]
                                            };
                                            const configT = {
                                                type: 'line',
                                                data: dataT,
                                                options: {
                                                    responsive: true,
                                                    plugins: {
                                                        legend: {
                                                            position: 'top',
                                                        },
                                                        title: {
                                                            display: true,
                                                            text: 'Статистика по дням'
                                                        }
                                                    },
                                                },
                                            };
                                            const ctxT = document.getElementById('timestageCanvas');
                                            const chartT = new Chart(ctxT, configT);
                                        </script>
                                    @endif
                                    @if($objects['timestage_times'])
                                        <canvas id="timestage_timesCanvas"></canvas>
                                        <script>
                                            const tmstptPrefs = {
                                                labels: ["<?= implode(':00","', array_keys($objects['timestage_times']['common'])) ?>"],
                                                datasets: [
                                                        @foreach($objects['timestage_times'] as $key => $times)
                                                    {
                                                        label: "{{$key}}",
                                                        data: [<?= implode(',', array_values($times)) ?>],
                                                        borderColor: "{{$key == 'common' ? "#36eb7f" : "#36a2eb7d"}}"
                                                    },
                                                    @endforeach
                                                    @if($compare)
                                                    , {
                                                        label: 'Сравнение',
                                                        data: [<?= implode(',', array_values($objects2['timestage_times'])) ?>],
                                                    }

                                                    @endif
                                                ]
                                            };
                                            const configtimest = {
                                                type: 'line',
                                                data: tmstptPrefs,
                                                options: {
                                                    responsive: true,
                                                    plugins: {
                                                        legend: {
                                                            position: 'top',
                                                        },
                                                        title: {
                                                            display: true,
                                                            text: 'Статистика по часам'
                                                        }
                                                    },
                                                },
                                            };
                                            const ctxTT = document.getElementById('timestage_timesCanvas');
                                            const chartTT = new Chart(ctxTT, configtimest);
                                        </script>
                                    @endif
                                </div>
                            </div>
                            @include('pages')
                            <div class="tab-pane fade" id="managers" role="tabpanel" aria-labelledby="managers-tab">
                                @include('conversions')
                            </div>
                            @include('postsales')

                        </div>

                    @else
                        <div class="card">
                            <div class="card-body text-danger text-center">
                                Отчет пока не загружен
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
