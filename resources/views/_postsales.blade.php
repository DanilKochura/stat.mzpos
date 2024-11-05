@extends('layouts.app')

@php

    $postsales = collect(json_decode('{"all":{"01":0.22257383966244726,"02":0.22803904170363798,"03":0.23251589464123523,"04":0.2653658536585366,"05":0.3064327485380117,"06":0.2891156462585034,"07":0.4056603773584906,"08":0.22417582417582418,"09":0.2638580931263858,"10":0.1761612620508326},"sales":{"01":{"count":211,"price":2329820,"Month":"January","clients":948},"02":{"count":257,"price":2608510,"Month":"February","clients":1127},"03":{"count":256,"price":2839555,"Month":"March","clients":1101},"04":{"count":272,"price":2863425,"Month":"April","clients":1025},"05":{"count":262,"price":2624185,"Month":"May","clients":855},"06":{"count":255,"price":2986070,"Month":"June","clients":882},"07":{"count":301,"price":3220453,"Month":"July","clients":742},"08":{"count":204,"price":2490115,"Month":"August","clients":910},"09":{"count":238,"price":2972910,"Month":"September","clients":902},"10":{"count":201,"price":2424970,"Month":"October","clients":1141}}}', true));


@endphp
@section('content')
    <div class="container">
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

@endsection
