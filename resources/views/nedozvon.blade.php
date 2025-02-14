@if($objects['nedozvon']['all'])
    <div class="tab-pane fade" id="nedozvon" role="tabpanel" aria-labelledby="nedozvon-tab">
        <h2>За период</h2>
        <div class="row">
            <div class="col-6">
                <canvas id="NedozvonPeriodSourcesChart"
                        style="width: 100%; height: 400px"></canvas>
                <script>


                    // Chart.register(ChartDataLabels);
                    const ctxNedozvonPeriodS = document.getElementById('NedozvonPeriodSourcesChart');
                    @php
                        $sales = $objects['nedozvon']['period']['sources'];
                    @endphp
                    const labelsNedozvonPeriodS = ["{!! implode('","', array_keys($sales)) !!} "]
                    const dataNedozvonPeriodS = {
                        labels: labelsNedozvonPeriodS,
                        datasets: [

                            {
                                label: "Конверсия",
                                data: [{{ implode(',', array_values($sales)) }}],
                                fill: true,
                                backgroundColor: [
                                    'rgba(54,162,235,0.74)',
                                    'rgba(255,99,132,0.71)',
                                    'rgba(255,159,64,0.81)',
                                    'rgba(153,102,255,0.63)',
                                    'rgba(255,205,86,0.69)',
                                    'rgba(201,203,207,0.75)',
                                    'rgba(75,192,192,0.62)',
                                    'rgba(0,255,24,0.75)']
                            },



                        ]
                    };

                    const configNedozvonPeriodS = {
                        type: 'pie',
                        data: dataNedozvonPeriodS,
                        options: {
                            aspectRatio: 2.5,
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Недозвоны по источникам'
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
                    const chartNedozvonPeriodS = new Chart(ctxNedozvonPeriodS, configNedozvonPeriodS);

                </script>
            </div>
            <div class="col-6">
                <canvas id="NedozvonPeriodSitesChart"
                        style="width: 100%; height: 400px"></canvas>
                <script>


                    // Chart.register(ChartDataLabels);
                    const ctxNedozvonPeriod = document.getElementById('NedozvonPeriodSitesChart');
                    @php
                        $sales = $objects['nedozvon']['period']['sites'];
                    @endphp
                    const labelsNedozvonPeriod = ["{!! implode('","', array_keys($sales)) !!} "]
                    const dataNedozvonPeriod = {
                        labels: labelsNedozvonPeriod,
                        datasets: [

                            {
                                label: "Конверсия",
                                data: [{{ implode(',', array_values($sales)) }}],
                                fill: true,
                                backgroundColor: [
                                    'rgba(54,162,235,0.45)',
                                    'rgba(255,99,132,0.62)',
                                    'rgba(255,159,64,0.65)',
                                    'rgba(153,102,255,0.6)',
                                    'rgba(255,205,86,0.59)', 'rgba(201,203,207,0.7)', 'rgba(75,192,192,0.73)', 'rgba(0,255,24,0.74)']
                            },



                        ]
                    };

                    const configNedozvonPeriod = {
                        type: 'pie',
                        data: dataNedozvonPeriod,
                        options: {
                            aspectRatio: 2.5,
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Недозвоны по сайтам'
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
                    const chartNedozvonPeriod = new Chart(ctxNedozvonPeriod, configNedozvonPeriod);

                </script>
            </div>
        </div>
        <hr>
        <h2>За все время</h2>
        <div class="row">
            <div class="col-6">
                <canvas id="NedozvonSourcesChart"
                        style="width: 100%; height: 400px"></canvas>
                <script>


                    // Chart.register(ChartDataLabels);
                    const ctxNedozvonS = document.getElementById('NedozvonSourcesChart');
                    @php
                        $sales = $objects['nedozvon']['all']['sources'];
                    @endphp
                    const labelsNedozvonS = ["{!! implode('","', array_keys($sales)) !!} "]
                    const dataNedozvonS = {
                        labels: labelsNedozvonS,
                        datasets: [

                            {
                                label: "Конверсия",
                                data: [{{ implode(',', array_values($sales)) }}],
                                fill: true,
                                backgroundColor: [
                                    'rgba(54,162,235,0.74)',
                                    'rgba(255,99,132,0.71)',
                                    'rgba(255,159,64,0.81)',
                                    'rgba(153,102,255,0.63)',
                                    'rgba(255,205,86,0.69)',
                                    'rgba(201,203,207,0.75)',
                                    'rgba(75,192,192,0.62)',
                                    'rgba(0,255,24,0.75)']
                            },



                        ]
                    };

                    const configNedozvonS = {
                        type: 'pie',
                        data: dataNedozvonS,
                        options: {
                            aspectRatio: 2.5,
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Недозвоны по источникам'
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
                    const chartNedozvonS = new Chart(ctxNedozvonS, configNedozvonS);

                </script>
            </div>
            <div class="col-6">
                <canvas id="NedozvonSitesChart"
                        style="width: 100%; height: 400px"></canvas>
                <script>


                    // Chart.register(ChartDataLabels);
                    const ctxNedozvon = document.getElementById('NedozvonSitesChart');
                    @php
                        $sales = $objects['nedozvon']['all']['sites'];
                    @endphp
                    const labelsNedozvon = ["{!! implode('","', array_keys($sales)) !!} "]
                    const dataNedozvon = {
                        labels: labelsNedozvon,
                        datasets: [

                            {
                                label: "Конверсия",
                                data: [{{ implode(',', array_values($sales)) }}],
                                fill: true,
                                backgroundColor: [
                                    'rgba(54,162,235,0.45)',
                                    'rgba(255,99,132,0.62)',
                                    'rgba(255,159,64,0.65)',
                                    'rgba(153,102,255,0.6)',
                                    'rgba(255,205,86,0.59)', 'rgba(201,203,207,0.7)', 'rgba(75,192,192,0.73)', 'rgba(0,255,24,0.74)']
                            },



                        ]
                    };

                    const configNedozvon = {
                        type: 'pie',
                        data: dataNedozvon,
                        options: {
                            aspectRatio: 2.5,
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Недозвоны по сайтам'
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
                    const chartNedozvon = new Chart(ctxNedozvon, configNedozvon);

                </script>
            </div>
        </div>

    </div>

@endif
