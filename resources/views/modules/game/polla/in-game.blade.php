@extends('layouts.app_rke_off')
@section('styles')
    @vite('resources/css/games/polla/polla-in-game.css')
@endsection
@section('title', $title)
<div class="banner">
    <div class="custom-shape-divider-top">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path
                d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z"
                opacity=".25" class="shape-fill"></path>
            <path
                d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z"
                opacity=".5" class="shape-fill"></path>
            <path
                d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"
                class="shape-fill"></path>
        </svg>
    </div>
    <div class="container">
        <div class="images-content"><img src="{{ asset('img/games/polla/logo.png') }}" alt="Logo" class="img-fluid">
        </div>
    </div>
</div>
@section('content')
    <div class="container-fluid">
        <div class="col-md-12 text-center">
            <h4 class="title">JUGADAS <span id="title-type-game">{{ $titleTypeGame }}</span></h4>
            <p class="subtitle">Última Actualización: <span id="">10/12/2024 7:18:03 p.m.</span> </p>
        </div>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-ms-12 col-12">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 col-12">
                        <div class="dashboard-stat green">
                            <div class="visual">
                                <svg class="svg-inline--fa fa-trophy" aria-hidden="true" focusable="false" data-prefix="fas"
                                    data-icon="trophy" role="img" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 576 512">
                                    <path class="" fill="currentColor"
                                        d="M400 0H176c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8H24C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9H192c-17.7 0-32 14.3-32 32s14.3 32 32 32H384c17.7 0 32-14.3 32-32s-14.3-32-32-32H357.9C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24H446.4c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112h84.4c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6h84.4c-5.1 66.3-31.1 111.2-63 142.3z">
                                    </path>
                                </svg>
                            </div>
                            <div class="details">
                                <div class="number"><span>Bs.S&nbsp;200.00</span></div>
                                <div class="desc">Primer acumulado</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 col-12">
                        <div class="dashboard-stat purple">
                            <div class="visual">
                                <svg class="svg-inline--fa fa-award" aria-hidden="true" focusable="false" data-prefix="fas"
                                    data-icon="award" role="img" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 384 512">
                                    <path class="" fill="currentColor"
                                        d="M173.8 5.5c11-7.3 25.4-7.3 36.4 0L228 17.2c6 3.9 13 5.8 20.1 5.4l21.3-1.3c13.2-.8 25.6 6.4 31.5 18.2l9.6 19.1c3.2 6.4 8.4 11.5 14.7 14.7L344.5 83c11.8 5.9 19 18.3 18.2 31.5l-1.3 21.3c-.4 7.1 1.5 14.2 5.4 20.1l11.8 17.8c7.3 11 7.3 25.4 0 36.4L366.8 228c-3.9 6-5.8 13-5.4 20.1l1.3 21.3c.8 13.2-6.4 25.6-18.2 31.5l-19.1 9.6c-6.4 3.2-11.5 8.4-14.7 14.7L301 344.5c-5.9 11.8-18.3 19-31.5 18.2l-21.3-1.3c-7.1-.4-14.2 1.5-20.1 5.4l-17.8 11.8c-11 7.3-25.4 7.3-36.4 0L156 366.8c-6-3.9-13-5.8-20.1-5.4l-21.3 1.3c-13.2 .8-25.6-6.4-31.5-18.2l-9.6-19.1c-3.2-6.4-8.4-11.5-14.7-14.7L39.5 301c-11.8-5.9-19-18.3-18.2-31.5l1.3-21.3c.4-7.1-1.5-14.2-5.4-20.1L5.5 210.2c-7.3-11-7.3-25.4 0-36.4L17.2 156c3.9-6 5.8-13 5.4-20.1l-1.3-21.3c-.8-13.2 6.4-25.6 18.2-31.5l19.1-9.6C65 70.2 70.2 65 73.4 58.6L83 39.5c5.9-11.8 18.3-19 31.5-18.2l21.3 1.3c7.1 .4 14.2-1.5 20.1-5.4L173.8 5.5zM272 192a80 80 0 1 0 -160 0 80 80 0 1 0 160 0zM1.3 441.8L44.4 339.3c.2 .1 .3 .2 .4 .4l9.6 19.1c11.7 23.2 36 37.3 62 35.8l21.3-1.3c.2 0 .5 0 .7 .2l17.8 11.8c5.1 3.3 10.5 5.9 16.1 7.7l-37.6 89.3c-2.3 5.5-7.4 9.2-13.3 9.7s-11.6-2.2-14.8-7.2L74.4 455.5l-56.1 8.3c-5.7 .8-11.4-1.5-15-6s-4.3-10.7-2.1-16zm248 60.4L211.7 413c5.6-1.8 11-4.3 16.1-7.7l17.8-11.8c.2-.1 .4-.2 .7-.2l21.3 1.3c26 1.5 50.3-12.6 62-35.8l9.6-19.1c.1-.2 .2-.3 .4-.4l43.2 102.5c2.2 5.3 1.4 11.4-2.1 16s-9.3 6.9-15 6l-56.1-8.3-32.2 49.2c-3.2 5-8.9 7.7-14.8 7.2s-11-4.3-13.3-9.7z">
                                    </path>
                                </svg>
                            </div>
                            <div class="details">
                                <div class="number"><span>Bs.S&nbsp;986,00</span></div>
                                <div class="desc">Segundo acumulado</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 col-12">
                        <div class="dashboard-stat blue">
                            <div class="visual">
                                <svg class="svg-inline--fa fa-inbox" aria-hidden="true" focusable="false" data-prefix="fas"
                                    data-icon="inbox" role="img" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 512 512">
                                    <path class="" fill="currentColor"
                                        d="M121 32C91.6 32 66 52 58.9 80.5L1.9 308.4C.6 313.5 0 318.7 0 323.9V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V323.9c0-5.2-.6-10.4-1.9-15.5l-57-227.9C446 52 420.4 32 391 32H121zm0 64H391l48 192H387.8c-12.1 0-23.2 6.8-28.6 17.7l-14.3 28.6c-5.4 10.8-16.5 17.7-28.6 17.7H195.8c-12.1 0-23.2-6.8-28.6-17.7l-14.3-28.6c-5.4-10.8-16.5-17.7-28.6-17.7H73L121 96z">
                                    </path>
                                </svg>
                            </div>
                            <div class="details">
                                <div class="number"><span>Bs.S&nbsp;1.834,00</span></div>
                                <div class="desc">Acumulado semanal</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 col-12">
                        <div class="dashboard-stat orange">
                            <div class="visual">
                                <svg class="svg-inline--fa fa-money-check-dollar" aria-hidden="true" focusable="false"
                                    data-prefix="fas" data-icon="money-check-dollar" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                    <path class="" fill="currentColor"
                                        d="M64 64C28.7 64 0 92.7 0 128V384c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H64zM272 192H496c8.8 0 16 7.2 16 16s-7.2 16-16 16H272c-8.8 0-16-7.2-16-16s7.2-16 16-16zM256 304c0-8.8 7.2-16 16-16H496c8.8 0 16 7.2 16 16s-7.2 16-16 16H272c-8.8 0-16-7.2-16-16zM164 152v13.9c7.5 1.2 14.6 2.9 21.1 4.7c10.7 2.8 17 13.8 14.2 24.5s-13.8 17-24.5 14.2c-11-2.9-21.6-5-31.2-5.2c-7.9-.1-16 1.8-21.5 5c-4.8 2.8-6.2 5.6-6.2 9.3c0 1.8 .1 3.5 5.3 6.7c6.3 3.8 15.5 6.7 28.3 10.5l.7 .2c11.2 3.4 25.6 7.7 37.1 15c12.9 8.1 24.3 21.3 24.6 41.6c.3 20.9-10.5 36.1-24.8 45c-7.2 4.5-15.2 7.3-23.2 9V360c0 11-9 20-20 20s-20-9-20-20V345.4c-10.3-2.2-20-5.5-28.2-8.4l0 0 0 0c-2.1-.7-4.1-1.4-6.1-2.1c-10.5-3.5-16.1-14.8-12.6-25.3s14.8-16.1 25.3-12.6c2.5 .8 4.9 1.7 7.2 2.4c13.6 4.6 24 8.1 35.1 8.5c8.6 .3 16.5-1.6 21.4-4.7c4.1-2.5 6-5.5 5.9-10.5c0-2.9-.8-5-5.9-8.2c-6.3-4-15.4-6.9-28-10.7l-1.7-.5c-10.9-3.3-24.6-7.4-35.6-14c-12.7-7.7-24.6-20.5-24.7-40.7c-.1-21.1 11.8-35.7 25.8-43.9c6.9-4.1 14.5-6.8 22.2-8.5V152c0-11 9-20 20-20s20 9 20 20z">
                                    </path>
                                </svg>
                            </div>
                            <div class="details">
                                <div class="number"><span>Bs.S&nbsp;40,00</span></div>
                                <div class="desc">Valor de la polla</div>
                            </div>
                        </div>
                    </div>
                </div>
                <section class="pt-3 pb-3 pb-3 ps-3 filters">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Fecha</label>
                            <div class="position-relative">
                                <div
                                    class="mx-datepicker filters-content-datapicker form-control bg-transparent border-0 position-absolute">

                                </div>
                                <div class="input-group mb-3">
                                    <input id="date-input" readonly type="text"
                                        class="form-control border-end-0 input-calendar" placeholder="dd/mm/yyyy"
                                        value="{{ \Carbon\Carbon::now()->format('d/m/Y') }}">
                                    <button class="btn btn-outline-secondary border-start-0 button-calendar"
                                        type="button" id="button-addon2"><svg class="svg-inline--fa fa-calendar"
                                            aria-hidden="true" focusable="false" data-prefix="fas" data-icon="calendar"
                                            role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                            <path class="" fill="currentColor"
                                                d="M96 32V64H48C21.5 64 0 85.5 0 112v48H448V112c0-26.5-21.5-48-48-48H352V32c0-17.7-14.3-32-32-32s-32 14.3-32 32V64H160V32c0-17.7-14.3-32-32-32S96 14.3 96 32zM448 192H0V464c0 26.5 21.5 48 48 48H400c26.5 0 48-21.5 48-48V192z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Moneda</label>
                            <select name="moneda" class="form-select normal" disabled>
                                @foreach ($coins as $item)
                                @endforeach
                                <option value="1">BOLIVAR DIGITAL (VES)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Juego</label>
                            @foreach ($typeGame as $item)
                            @endforeach
                            <select name="juego" class="form-select normal">
                                <option value="31">LA POLLA EXPRESS (3PM-7PM)</option>
                                <option value="30">LA POLLA PLUSS (9AM-7PM)</option>
                                <option value="32">LA POLLA EXPRESS (9AM-1PM)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label id="identification-doc">Cédula</label>
                            <div class="input-group mb-3">
                                <div class="">
                                    <select name="cedula" class="form-select" disabled="">
                                        <option value="V-">V</option>
                                        <option value="E-">E</option>
                                    </select>
                                </div>
                                <input name="input-cedula" type="text" class="form-control identification"
                                    maxlength="10">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 d-none">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="rageDate">
                                <label class="form-check-label" for="rageDate"> Filtrar por rango de fechas </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" id="update">
                                <label class="form-check-label ms-2" for="update">Actualizar cada 2 min</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-grid gap-2 offset-md-7">
                            <button class="btn btn-warning float-end" type="button"><svg
                                    class="svg-inline--fa fa-magnifying-glass" aria-hidden="true" focusable="false"
                                    data-prefix="fas" data-icon="magnifying-glass" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path class="" fill="currentColor"
                                        d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z">
                                    </path>
                                </svg>
                                Buscar
                            </button>
                        </div>
                    </div>
                </section>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-6 text-left pageInfo-winner pb-2">Ganas acertando <span
                                    class="text-danger">6</span> números</div>
                            <div class="col-6 text-right pageInfo pb-2">Mostrando página 1 de 23 - Total de registros 450
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th scope="col">Jugador</th>
                                        <th scope="col">Cédula</th>
                                        <th># Ticket</th>
                                        <th scope="col"> #1</th>
                                        <th scope="col"> #2</th>
                                        <th scope="col"> #3</th>
                                        <th scope="col"> #4</th>
                                        <th scope="col"> #5</th>
                                        <th scope="col"> #6</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Aquí van las filas de la tabla -->
                                    <tr>
                                        <th scope="row">Enrique Parra</th>
                                        <td class="number">***7618</td>
                                        <td class="number">64349653</td>
                                        <td class="number winner"><span>26</span></td>
                                        <td class="number winner"><span>29</span></td>
                                        <td class="number winner"><span>11</span></td>
                                        <td class="number winner"><span>36</span></td>
                                        <td class="number winner"><span>35</span></td>
                                        <td class="number winner"><span>16</span></td>
                                        <td class="number winner-pot">
                                            <span>6
                                                <svg class="svg-inline--fa fa-trophy animate-bounce" aria-hidden="true"
                                                    focusable="false" data-prefix="fas" data-icon="trophy"
                                                    role="img" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 576 512" style="color: orange;">
                                                    <path class="" fill="currentColor"
                                                        d="M400 0H176c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8H24C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9H192c-17.7 0-32 14.3-32 32s14.3 32 32 32H384c17.7 0 32-14.3 32-32s-14.3-32-32-32H357.9C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24H446.4c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112h84.4c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6h84.4c-5.1 66.3-31.1 111.2-63 142.3z">
                                                    </path>
                                                </svg>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-ms-12 col-12">
                <div class="container-results-body colResults">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="text-lg md:text-xl font-bold"> Resultados <span> del 17/12/2024</span></h3>
                        <button class="btn btn-link btn-sm"><svg class="svg-inline--fa fa-arrows-rotate"
                                aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrows-rotate"
                                role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path class="" fill="currentColor"
                                    d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z">
                                </path>
                            </svg> Refrescar</button>
                    </div>
                    <div class="table-responsive resultados">
                        <table class="table table-hover table-striped">
                            @foreach ($lotteryResults as $item)
                            @endforeach
                            <thead class="table-dark">
                                <tr>
                                    <th class="number text-center">Hora</th>
                                    <th class="text-center">LOTTO ACTIVO</th>
                                    <th class="text-center">LA GRANJITA</th>
                                    <th class="text-center">ANIMALITOS LA RICACHONA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th class="number text-center" scope="col">09:00 AM</th>
                                    <td><span>9-AGUILA</span></td>
                                    <td><span>26-VACA</span></td>
                                    <td><span>29-ELEFANTE</span></td>
                                </tr>
                                <tr>
                                    <th class="number text-center" scope="col">10:00 AM</th>
                                    <td><span>5-LEON</span></td>
                                    <td><span>12-CABALLO</span></td>
                                    <td><span>11-GATO</span></td>
                                </tr>
                                <tr>
                                    <th class="number text-center" scope="col">11:00 AM</th>
                                    <td><span>19-CHIVO</span></td>
                                    <td><span>0-DELFIN</span></td>
                                    <td><span>14-PALOMA</span></td>
                                </tr>
                                <tr>
                                    <th class="number text-center" scope="col">12:00 PM</th>
                                    <td><span>35-JIRAFA</span></td>
                                    <td><span>33-PESCADO</span></td>
                                    <td><span>36-CULEBRA</span></td>
                                </tr>
                                <tr>
                                    <th class="number text-center" scope="col">01:00 PM</th>
                                    <td><span>16-OSO</span></td>
                                    <td><span>11-GATO</span></td>
                                    <td><span>3-CIEMPIES</span></td>
                                </tr>
                                <tr>
                                    <th class="number text-center" scope="col">02:00 PM</th>
                                    <td><span>28-ZAMURO</span></td>
                                    <td><span>14-PALOMA</span></td>
                                    <td><span>20-COCHINO</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @vite('resources/js/games/polla/in-game.js')
@endsection
