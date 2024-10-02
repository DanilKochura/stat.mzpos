@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                    <form action="/managers" id="periodical" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <label for="first_period">Период</label>
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
                            <div class="col-4 align-content-end">
                                <button type="submit" id="make_report" class="btn btn-primary rounded-pill">Заказать
                                    отчет
                                </button>
                            </div>
                        </div>
                    </form>
                    <ul class="nav nav-pills mt-3 justify-content-center" id="myTab1">

                        <li class="nav-item active" role="presentation">
                            <a class="nav-link" id="managers-tab" data-toggle="tab" href="#managers" role="tab"
                               aria-controls="managers"
                               aria-selected="false">По менеджерам</a>
                        </li>
                    </ul>
                    @if($objects)
                        <div class="tab-content" id="myTabContent1">
                            <div class="tab-pane active" id="managers" role="tabpanel" aria-labelledby="managers-tab">
                                @include('conversions')
                            </div>
                        </div>

                    @else
                        <div class="card">
                            <div class="card-body text-danger text-center">
                                Отчет пока не загружен
                            </div>
                        </div>
                    @endif
            </div>
        </div>
    </div>
@endsection
