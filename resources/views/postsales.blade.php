<div class="tab-pane fade" id="postsales" role="tabpanel" aria-labelledby="postsales-tab">
    <div class="row">
        <div class="col-12">
            <canvas id="PostsalesChart"
                    style="width: 100%; height: 400px"></canvas>
            <script>


                // Chart.register(ChartDataLabels);
                const ctxPostsales = document.getElementById('PostsalesChart');
                @php
                $sales = $postsales['sales'];
                    ksort($sales)
 @endphp
                const labelsPostsales = ["{!! implode('","', collect($sales)->pluck('Month')->toArray()) !!} "]
                const dataPostsales = {
                    labels: labelsPostsales,
                    datasets: [

                        {
                            yAxisID: 'y',
                            label: "Конверсия",
                            data: [{{ implode(',', array_values($postsales['all'])) }}],
                            fill: true,
                            backgroundColor: '#fde5c9bf',
                            borderColor: '#db8e33',
                            borderWidth: 1,
                            order: 2
                        },



                    ]
                };

                const configPostsales = {
                    type: 'line',
                    data: dataPostsales,
                    options: {
                        aspectRatio: 2.5,
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Распределение Конверсии отдела допродаж во времени'
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
                const chartPostsales = new Chart(ctxPostsales, configPostsales);

            </script>
        </div>
        <div class="col-5">
            <table class="table table-stripped">
                <thead>
                <tr>
                    <th>Месяц</th>
                    <th>Клиентов поступило</th>
                    <th>Заявок продано</th>
                    <th>Сумма продаж</th>
                    <th>Конверсия</th>
                </tr>

                @foreach($sales as $date => $data)
                    <tr>
                        <td>{{\Carbon\Carbon::parse($data['Month'])->translatedFormat("F")}}</td>
                        <td>{{$data['clients']}}</td>
                        <td>{{$data['count']}}</td>
                        <td>{{$data['price']}}</td>
                        <td>{{round($data['count'] / $data['clients'], 2)}}</td>
                    </tr>
                @endforeach
                </thead>
            </table>
        </div>
        <div class="col-7">
                <canvas id="PostsalesChartSalse"
                        style="width: 100%; height: 400px"></canvas>
                <script>


                    // Chart.register(ChartDataLabels);
                    const ctxPostsalesPrice = document.getElementById('PostsalesChartSalse');
                    @php
                        $sales = collect($sales);
                    @endphp
                    const labelsPostsalesPrice = ["{!! implode('","', $sales->pluck('Month')->toArray()) !!} "]
                    const dataPostsalesPrice = {
                        labels: labelsPostsalesPrice,
                        datasets: [

                            {
                                yAxisID: 'y',
                                label: "Продажи, руб",
                                data: [{{ implode(',', $sales->pluck('price')->toArray()) }}],
                                fill: true,
                                backgroundColor: '#fde5c9bf',
                                borderColor: '#db8e33',
                                borderWidth: 1,
                                order: 2
                            },



                        ]
                    };

                    const configPostsalesPrice = {
                        type: 'bar',
                        data: dataPostsalesPrice,
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
                    const chartPostsalesPrice = new Chart(ctxPostsalesPrice, configPostsalesPrice);

                </script>
        </div>
    </div>

</div>
