@extends('layout.admin')

@section('conteudo')
<link rel="stylesheet" href="{{ asset('/public/template/vendor/libs/apex-charts/apex-charts.css') }}" />

<div class="d-flex justify-content-between">
    <h4 class="card-title">Dashboard</h4>
    @if($administrador->id == '6' || $administrador->id == '3' || $administrador->id == '1')
        <div class="col-md-3">
            <form action="{{ route('adm.dashboard') }}" method="get">
                <div class="form-floating form-floating-outline">
                    <select onchange="submit()" name="controle" id="controle" class="form-control">
                        <option @if($controle == "Dia") selected @endif value="Dia">Dia</option>
                        <option @if($controle == "Semana") selected @endif value="Semana">Semana</option>
                        <option @if($controle == "Mês") selected @endif value="Mês">Mês</option>
                    </select>
                    <label for="">Filtro de Tempo:</label>
                </div>
            </form>
        </div>
    @endif
</div>
<hr>
@if($administrador->id == '6' || $administrador->id == '3' || $administrador->id == '1')
    <div class="row">
        <div class="col-sm-6 col-md-4 col-lg-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="mdi mdi-cash mdi-20px"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 display-6">R$ {{ valorDbForm($vl_faturamento) }}</h4>
                    </div>
                    <p class="mb-0 text-heading">Faturamento {{ $controle }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mt-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Faturamento Clinica</h5>
                    <div id="donutChart"></div>
                </div>
            </div>
            <script>
                window.addEventListener('load',()=>{
                    legendColor = '#696969';
                    headingColor = '#696969';
                    fontFamily = 'arial'
                    const donutChartEl = document.querySelector('#donutChart'),
                        donutChartConfig = {
                            chart: {
                                height: 390,
                                fontFamily: 'Inter',
                                type: 'donut'
                            },
                            labels: [{!! $label_clinicas !!}],
                            series: [{!! $valores_clinicas !!}],
                            colors: [{!! $cores_clinicas !!}],
                            stroke: {
                                show: false,
                                curve: 'straight'
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function (val, opt) {
                                    return parseInt(val, 10) + '%';
                                }
                            },
                            legend: {
                                show: true,
                                position: 'bottom',
                                markers: {
                                    size: 6
                                },
                                itemMargin: {
                                    vertical: 3,
                                    horizontal: 10
                                },
                                labels: {
                                    colors: legendColor,
                                    useSeriesColors: false
                                }
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '2rem',
                                                fontFamily: fontFamily
                                            },
                                            value: {
                                                fontSize: '1.5rem',
                                                color: legendColor,
                                                fontFamily: fontFamily,
                                                formatter: function (val) {
                                                    return parseInt(val, 10) + '%';
                                                }
                                            },
                                            total: {
                                                show: true,
                                                fontSize: '1.5rem',
                                                color: headingColor,
                                                label: '',
                                                formatter: function (w) {
                                                    return '';
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            responsive: [
                                {
                                    breakpoint: 992,
                                    options: {
                                        chart: {
                                            height: 380
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 576,
                                    options: {
                                        chart: {
                                            height: 320
                                        },
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    labels: {
                                                        show: true,
                                                        name: {
                                                            fontSize: '1.5rem'
                                                        },
                                                        value: {
                                                            fontSize: '1rem'
                                                        },
                                                        total: {
                                                            fontSize: '1.5rem'
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 420,
                                    options: {
                                        chart: {
                                            height: 280
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                },
                                {
                                    breakpoint: 360,
                                    options: {
                                        chart: {
                                            height: 250
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                }
                            ]
                        };
                        if (typeof donutChartEl !== undefined && donutChartEl !== null) {
                            const donutChart = new ApexCharts(donutChartEl, donutChartConfig);
                            donutChart.render();
                        }
                    });
            </script>
        </div>
        <div class="col-md-6 mt-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Faturamento Médico</h5>
                    <div id="donutChart_medico"></div>
                </div>
            </div>
            <script>
                window.addEventListener('load',()=>{
                    legendColor = '#696969';
                    headingColor = '#696969';
                    fontFamily = 'arial'
                    const donutChartEl = document.querySelector('#donutChart_medico'),
                        donutChartConfig = {
                            chart: {
                                height: 390,
                                fontFamily: 'Inter',
                                type: 'donut'
                            },
                            labels: [{!! $label_medicos !!}],
                            series: [{!! $valores_medicos !!}],
                            colors: [{!! $cores_medicos !!}],
                            stroke: {
                                show: false,
                                curve: 'straight'
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function (val, opt) {
                                    return parseInt(val, 10) + '%';
                                }
                            },
                            legend: {
                                show: true,
                                position: 'bottom',
                                markers: {
                                    size: 6
                                },
                                itemMargin: {
                                    vertical: 3,
                                    horizontal: 10
                                },
                                labels: {
                                    colors: legendColor,
                                    useSeriesColors: false
                                }
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '2rem',
                                                fontFamily: fontFamily
                                            },
                                            value: {
                                                fontSize: '1.5rem',
                                                color: legendColor,
                                                fontFamily: fontFamily,
                                                formatter: function (val) {
                                                    return parseInt(val, 10) + '%';
                                                }
                                            },
                                            total: {
                                                show: true,
                                                fontSize: '1.5rem',
                                                color: headingColor,
                                                label: '',
                                                formatter: function (w) {
                                                    return '';
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            responsive: [
                                {
                                    breakpoint: 992,
                                    options: {
                                        chart: {
                                            height: 380
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 576,
                                    options: {
                                        chart: {
                                            height: 320
                                        },
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    labels: {
                                                        show: true,
                                                        name: {
                                                            fontSize: '1.5rem'
                                                        },
                                                        value: {
                                                            fontSize: '1rem'
                                                        },
                                                        total: {
                                                            fontSize: '1.5rem'
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 420,
                                    options: {
                                        chart: {
                                            height: 280
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                },
                                {
                                    breakpoint: 360,
                                    options: {
                                        chart: {
                                            height: 250
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                }
                            ]
                        };
                        if (typeof donutChartEl !== undefined && donutChartEl !== null) {
                            const donutChart = new ApexCharts(donutChartEl, donutChartConfig);
                            donutChart.render();
                        }
                    });
            </script>
        </div>
        <div class="col-md-6 mt-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Faturamento Medicamento</h5>
                    <div id="donutChart_medicamento"></div>
                </div>
            </div>
            <script>
                window.addEventListener('load',()=>{
                    legendColor = '#696969';
                    headingColor = '#696969';
                    fontFamily = 'arial'
                    const donutChartEl = document.querySelector('#donutChart_medicamento'),
                        donutChartConfig = {
                            chart: {
                                height: 390,
                                fontFamily: 'Inter',
                                type: 'donut'
                            },
                            labels: [{!! $label_medicamentos !!}],
                            series: [{!! $valores_medicamentos !!}],
                            colors: [{!! $cores_medicamentos !!}],
                            stroke: {
                                show: false,
                                curve: 'straight'
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function (val, opt) {
                                    return parseInt(val, 10) + '%';
                                }
                            },
                            legend: {
                                show: true,
                                position: 'bottom',
                                markers: {
                                    size: 6
                                },
                                itemMargin: {
                                    vertical: 3,
                                    horizontal: 10
                                },
                                labels: {
                                    colors: legendColor,
                                    useSeriesColors: false
                                }
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '2rem',
                                                fontFamily: fontFamily
                                            },
                                            value: {
                                                fontSize: '1.5rem',
                                                color: legendColor,
                                                fontFamily: fontFamily,
                                                formatter: function (val) {
                                                    return parseInt(val, 10) + '%';
                                                }
                                            },
                                            total: {
                                                show: true,
                                                fontSize: '1.5rem',
                                                color: headingColor,
                                                label: '',
                                                formatter: function (w) {
                                                    return '';
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            responsive: [
                                {
                                    breakpoint: 992,
                                    options: {
                                        chart: {
                                            height: 380
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 576,
                                    options: {
                                        chart: {
                                            height: 320
                                        },
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    labels: {
                                                        show: true,
                                                        name: {
                                                            fontSize: '1.5rem'
                                                        },
                                                        value: {
                                                            fontSize: '1rem'
                                                        },
                                                        total: {
                                                            fontSize: '1.5rem'
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 420,
                                    options: {
                                        chart: {
                                            height: 280
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                },
                                {
                                    breakpoint: 360,
                                    options: {
                                        chart: {
                                            height: 250
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                }
                            ]
                        };
                        if (typeof donutChartEl !== undefined && donutChartEl !== null) {
                            const donutChart = new ApexCharts(donutChartEl, donutChartConfig);
                            donutChart.render();
                        }
                    });
            </script>
        </div>
    </div>
@endif
    <div class="row">
        <div class="col-md-12 mt-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Consumo de Medicamentos (Quantidade)</span>
                        <form action="{{ route('adm.dashboard') }}" method="get" class="d-flex align-items-center gap-2">
                            @if(isset($controle))
                                <input type="hidden" name="controle" value="{{ $controle }}">
                            @endif
                            <label class="mb-0 text-nowrap">Período:</label>
                            <input type="date" name="dt_inc_consumo" class="form-control form-control-sm" value="{{ $dt_inc_consumo }}">
                            <span class="text-nowrap">a</span>
                            <input type="date" name="dt_fn_consumo" class="form-control form-control-sm" value="{{ $dt_fn_consumo }}">
                            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                            <a href="{{ route('adm.dashboard') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                        </form>
                    </h5>
                    <div id="donutChart_consumo"></div>
                </div>
            </div>
            <script>
                window.addEventListener('load',()=>{
                    legendColor = '#696969';
                    headingColor = '#696969';
                    fontFamily = 'arial'
                    const donutChartEl = document.querySelector('#donutChart_consumo'),
                        donutChartConfig = {
                            chart: {
                                height: 600,
                                fontFamily: 'Inter',
                                type: 'donut'
                            },
                            labels: [{!! $label_consumo !!}],
                            series: [{!! $valores_consumo !!}],
                            colors: [{!! $cores_consumo !!}],
                            stroke: {
                                show: false,
                                curve: 'straight'
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function (val, opt) {
                                    return opt.w.globals.seriesTotals[opt.seriesIndex];
                                }
                            },
                            tooltip: {
                                y: {
                                    formatter: function(value) {
                                        return value + " unidades";
                                    }
                                }
                            },
                            legend: {
                                show: true,
                                position: 'right',
                                markers: {
                                    size: 6
                                },
                                itemMargin: {
                                    vertical: 3,
                                    horizontal: 10
                                },
                                labels: {
                                    colors: legendColor,
                                    useSeriesColors: false
                                }
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '2rem',
                                                fontFamily: fontFamily
                                            },
                                            value: {
                                                fontSize: '1.5rem',
                                                color: legendColor,
                                                fontFamily: fontFamily,
                                                formatter: function (val) {
                                                    return val + ' unid';
                                                }
                                            },
                                            total: {
                                                show: true,
                                                fontSize: '1.5rem',
                                                color: headingColor,
                                                label: 'Total',
                                                formatter: function (w) {
                                                    return w.globals.seriesTotals.reduce((a, b) => {
                                                        return a + b
                                                    }, 0) + ' unid';
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            responsive: [
                                {
                                    breakpoint: 992,
                                    options: {
                                        chart: {
                                            height: 380
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 576,
                                    options: {
                                        chart: {
                                            height: 320
                                        },
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    labels: {
                                                        show: true,
                                                        name: {
                                                            fontSize: '1.5rem'
                                                        },
                                                        value: {
                                                            fontSize: '1rem'
                                                        },
                                                        total: {
                                                            fontSize: '1.5rem'
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                colors: legendColor,
                                                useSeriesColors: false
                                            }
                                        }
                                    }
                                },
                                {
                                    breakpoint: 420,
                                    options: {
                                        chart: {
                                            height: 280
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                },
                                {
                                    breakpoint: 360,
                                    options: {
                                        chart: {
                                            height: 250
                                        },
                                        legend: {
                                            show: false
                                        }
                                    }
                                }
                            ]
                        };
                        if (typeof donutChartEl !== undefined && donutChartEl !== null) {
                            const donutChart = new ApexCharts(donutChartEl, donutChartConfig);
                            donutChart.render();
                        }
                    });
            </script>
        </div>
    </div>
<div class="card card-border-shadow-primary mt-3">
    <div class="card-body">
        <h4 class="card-title">Medicamentos com vencimento nos próximos 60 dias</h4>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Clinica</th>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>Codigo Barras</th>
                        <th>Vencimento</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($array_view) == 0)
                        <tr>
                            <td colspan="6">Não há medicamentos vencendo nos próximos 60 dias</td>
                        </tr>
                    @endif
                    @foreach($array_view as $linha)
                        <tr>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medicamento'] }}</td>
                            <td>{{ $linha['lote'] }}</td>
                            <td>{{ $linha['codigo_barras'] }}</td>
                            <td>{{ $linha['vencimento'] }}</td>
                            <td>{{ $linha['quantidade'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


    </div>
</div>
@endsection
