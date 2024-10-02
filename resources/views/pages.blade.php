
<div class="tab-pane fade" id="pages" role="tabpanel" aria-labelledby="pages-tab">
    <div class="row">
        <div class="col-4">
            @if ($compare)
                <p class="p-2 bg-info-soft text-white rounded advices">При включенном режиме сравнения во
                    внешнем круге указана статистика за первый выбранный период (<?= $_GET['first_period'] ?>{{$second}}
                    )<br>Во внутреннем, соответственно, указано количество лидов по категориям за второй
                    выбранный период ({{$first}}}})</p>
            @endif
            <canvas id="canvasCates"></canvas>
            <script>

                const dataCates = {
                    labels: ["<?=implode('","', array_keys($objects['cats_new']))?>"],
                    datasets: [
                        {
                            label: 'Период',
                            data: [<?=implode(',', array_values($objects['cats_new']))?>],
                            backgroundColor: ['#36a2eb', '#ff6384', '#ff9f40', '#9966ff', '#ffcd56', '#c9cbcf', '#4bc0c0', '#00ff18']
                        } <?php if($compare): ?>
                        ,
                        {
                            label: 'Сравнение',
                            data: [<?=implode(',', array_values($objects2['cats_new']))?>],
                            backgroundColor: ['#36a2eb', '#ff6384', '#ff9f40', '#9966ff', '#ffcd56', '#c9cbcf', '#4bc0c0', '#00ff18']


                        }

                        <?php endif; ?>
                    ]
                };
                const configCates = {
                    type: 'pie',
                    data: dataCates,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Статистика по категориям'
                            },
                            colors: {
                                enabled: true
                            }
                        },
                        indexAxis: 'y'
                    },
                };
                const ctxCates = document.getElementById('canvasCates');
                const chartCates = new Chart(ctxCates, configCates);
            </script>
        </div>
        <div class="col-8">
            <?php if ($compare): ?>
                <p class="p-2 bg-info-soft text-white rounded advices">При включенном режиме сравнения синим
                    цветом указано количество заявок со страниц полпулярных префиксов за первый выбранный период
                    (<?= $_GET['first_period'] ?>
                    )<br>Серым цветом, соответственно, указано количество лидов по префиксам за второй
                    выбранный период (<?= $_GET['second_period'] ?>)</p>
            <?php endif; ?>
            <canvas id="canvasPrefs"></canvas>
            <script>
                const dataPrefs = {
                    labels: ["<?=implode('","', $objects['additional']['labelsPrefs'])?>"],
                    datasets: [
                        {
                            label: 'Период',
                            data: [<?=implode(',', array_values($objects['additional']['prefs']))?>],
                            backgroundColor: "#36a2eb7d"
                        } @if($compare)
                        , {
                            label: 'Сравнение',
                            data: [<?=implode(',', array_values($objects2['additional']['prefs']))?>],
                        }

                       @endif
                    ]
                };
                const configPrefs = {
                    type: 'bar',
                    data: dataPrefs,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Статистика по префиксам'
                            }
                        },
                    },
                };
                const ctxPrefs = document.getElementById('canvasPrefs');
                const chartPrefs = new Chart(ctxPrefs, configPrefs);
            </script>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-6">
            <table class="table table-bordered table-hover table-striped"
            >
                <thead>
                <th>Посадочная страница</th>
                <th>Предложение</th>
                </thead>
                <tbody>
                @foreach ($objects['courses'] as $key => $leads)

                    <tr>
                        <td>{{$leads['prefix'] }}</td>
                        <td>{{ $leads['course']}} <a target="_blank"
                                                       href="https://mzpoeducationsale.amocrm.ru/leads/detail/{{$leads['id'] }}">{!! ($leads['success'] == 142) ? '<span class="badge bg-success-soft">✔</span>' : (($leads['success'] == 143) ? '<span class="badge bg-secondary-soft">☓</span>' : '') !!}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>
        <div class="col-6">
            <canvas id="canvasSites"></canvas>
            <script>

                const dataSites = {
                    labels: ["<?=implode('","', array_keys($objects['sources']))?>"],
                    datasets: [
                        {
                            label: 'Период',
                            data: [<?=implode(',', array_values($objects['sources']))?>],
                            backgroundColor: ['#FF6384', // Red
                                '#36A2EB', // Blue
                                '#FFCE56', // Yellow
                                '#4BC0C0', // Green
                                '#9966FF', // Purple
                                '#FF9F40', // Orange
                                '#E7E9ED', // Light Grey
                                '#8B4513', // Saddle Brown
                                '#00FF00', // Lime
                                '#FFD700', // Gold
                                '#8A2BE2', // Blue Violet
                                '#00CED1', // Dark Turquoise
                                '#FF4500', // Orange Red
                                '#DAA520', // Golden Rod
                                '#ADFF2F'  // Green Yellow
                            ],
                            hoverOffset: 10,
                        } <?php if($compare): ?>
                        ,
                        {
                            label: 'Сравнение',
                            data: [<?=implode(',', array_values($objects2['sources']))?>],
                            backgroundColor: [
                                '#FF6384', // Red
                                '#36A2EB', // Blue
                                '#FFCE56', // Yellow
                                '#4BC0C0', // Green
                                '#9966FF', // Purple
                                '#FF9F40', // Orange
                                '#E7E9ED', // Light Grey
                                '#8B4513', // Saddle Brown
                                '#00FF00', // Lime
                                '#FFD700', // Gold
                                '#8A2BE2', // Blue Violet
                                '#00CED1', // Dark Turquoise
                                '#FF4500', // Orange Red
                                '#DAA520', // Golden Rod
                                '#ADFF2F'  // Green Yellow
                            ]

                        }

                        <?php endif; ?>
                    ]
                };


                function colorize(opaque, hover, ctx) {
                    const v = ctx.parsed;
                    const c = v < -50 ? '#D60000'
                        : v < 0 ? '#F46300'
                            : v < 50 ? '#0358B6'
                                : '#44DE28';

                    const opacity = hover ? 1 - Math.abs(v / 150) - 0.2 : 1 - Math.abs(v / 150);

                    return opaque ? c : Utils.transparentize(c, opacity);
                }

                function hoverColorize(ctx) {
                    return colorize(false, true, ctx);
                }

                const configSites = {
                    type: 'pie',
                    data: dataSites,
                    options: {
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Статистика по сайтам'
                            }
                        },
                        elements: {
                            arc: {
                                backgroundColor: colorize.bind(null, false, false),
                                hoverBackgroundColor: hoverColorize
                            }
                        }
                    },
                };
                const ctxSites = document.getElementById('canvasSites');
                const chartSites = new Chart(ctxSites, configSites);
            </script>

            <canvas id="pagesCanvas" height="600"></canvas>
            <script>
                const dataPages = {
                    labels: ["<?=implode('","', array_values($objects['additional']['labelsPages']))?>"],
                    datasets: [
                        {
                            label: 'Период',
                            data: [<?=implode(',', array_values($objects['additional']['pages']))?>],
                            backgroundColor: "#36a2eb7d"
                        } <?php if($compare): ?>
                        ,
                        {
                            label: 'Сравнение',
                            data: [<?=implode(',', array_values($objects2['additional']['pages']))?>],
                        }

                        <?php endif; ?>
                    ]
                };
                const configPages = {
                    type: 'bar',
                    data: dataPages,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Статистика по посадочным страницам'
                            }
                        },
                        indexAxis: 'y'
                    },
                };
                const ctxPages = document.getElementById('pagesCanvas');
                const chartPages = new Chart(ctxPages, configPages);

            </script>
            <div>
                <canvas id="DemoStatistics"
                        style="width: 100%; height: 400px"></canvas>
                <script>


                    // Chart.register(ChartDataLabels);
                    const ctxDemo = document.getElementById('DemoStatistics');

                    const labelsDemo = ["Заявки", "Переходы"]
                    const dataDemo = {
                        labels: labelsDemo,
                        datasets: [

                            {
                                label: "Период",
                                data: [<?=$objects['demo']?>, <?=$objects['demo-changed']?>],
                                fill: true,
                                backgroundColor: ['#36a2eb', '#ff6384'],
                                //borderColor: <?php //=json_encode($chartColors)?>//,
                                borderWidth: 1,
                                order: 2
                            },

                            <?php if ($compare): ?>

                            {
                                label: "Сравнение",
                                data: [<?=$objects2['demo']?>, <?=$objects2['demo-changed']?>],
                                fill: true,
                                backgroundColor: ['#36a2eb', '#ff6384'],
                                borderWidth: 1,
                                order: 2
                            },
                            <?php endif; ?>


                        ]
                    };

                    const configDemo = {
                        type: 'pie',
                        data: dataDemo,
                        options: {
                            aspectRatio: 1,
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Демо-доступ'
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
                    const chartDemo = new Chart(ctxDemo, configDemo);

                </script>
            </div>
        </div>
    </div>
</div>
