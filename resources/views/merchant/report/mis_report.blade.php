@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="mr--10 ml--10">
            <div class="row" style="margin-right: 0rem;margin-left: 0rem">
                @if(Auth::user('merchant')->can('dashboard'))
                    <!-- Site Statistics -->
                    @if(Auth::user('merchant')->can('view_rider') || Auth::user('merchant')->can('view_drivers') || Auth::user('merchant')->can('view_countries') || Auth::user('merchant')->can('view_area') || Auth::user('merchant')->can('expired_driver_documents'))
                        <div class="col-12 col-md-12 col-sm-12">
                            <!-- Example Panel With Heading -->
                            <div class="panel panel-bordered">
                                <div class="panel-heading">
                                    <div class="panel-actions">
                                    </div>
                                    <h3 class="panel-title">@lang("$string_file.mis") @lang("$string_file.report")</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <canvas id="barChart"></canvas>
                                        </div>
                                        <div class="col-md-6">
                                            <canvas id="lineChart"></canvas>
                                        </div>
                                    </div>
                                    <br><br>   <br><br>    <br><br>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <canvas id="donut"></canvas>
                                        </div>
                                        <div class="col-md-6">
                                            <canvas id="radar"></canvas>
                                        </div>
                                    </div>

                                    <div class="row">

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('barChart');
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: @json($taxi_stats['labels']),
              datasets: [{
                    label: '{{$taxi_stats['heading']}}',
                    data: @json($taxi_stats['values']),
                    backgroundColor: [
                      'rgba(255, 99, 132, 0.2)',
                      'rgba(255, 159, 64, 0.2)',
                      'rgba(255, 205, 86, 0.2)',
                      'rgba(75, 192, 192, 0.2)',
                      'rgba(54, 162, 235, 0.2)',
                      'rgba(153, 102, 255, 0.2)',
                      'rgba(201, 203, 207, 0.2)'
                    ],
                    borderColor: [
                      'rgb(255, 99, 132)',
                      'rgb(255, 159, 64)',
                      'rgb(255, 205, 86)',
                      'rgb(75, 192, 192)',
                      'rgb(54, 162, 235)',
                      'rgb(153, 102, 255)',
                      'rgb(201, 203, 207)'
                    ],
                    borderWidth: 1
                  }]
            },
            options: {
              scales: {
                y: {
                  beginAtZero: true
                }
              }
            }
          });



          const ltx = document.getElementById('lineChart');
          new Chart(ltx, {
            type: 'line',
            data: {
              labels: @json($acceptance_ratio['labels']),
              datasets: [{
                   label: '{{$acceptance_ratio['heading']}}',
                    data: @json($acceptance_ratio['values']),
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
              }]
            },
          });

        const dtx = document.getElementById('donut');
        const donut_data = {
              labels: @json($earnings['labels']),
              datasets: [{
                label: @json($earnings['heading']),
                data: @json($earnings['values']),
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(54, 162, 235)',
                ],
                hoverOffset: 4
              }]
            };

          new Chart(dtx, {
            type: 'doughnut',
            data: donut_data,
          });



        const rtx = document.getElementById('radar');
        const radar_data = {
          labels: @json($country_area_bookings['labels']),
          datasets: @json($country_area_bookings['datasets'])
        };
        new Chart(rtx, {
            type: 'radar',
              data: radar_data,
              options: {
                elements: {
                  line: {
                    borderWidth: 3
                  }
                }
              },
          });
    </script>


@endsection