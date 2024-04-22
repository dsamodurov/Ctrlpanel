@extends('layouts.app')

@section('content')
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">{{ config('app.name', 'Laravel') }}</a>

            @auth
                <div class="form-inline my-2 my-lg-0">
                    <a class="btn btn-outline-primary mr-sm-2" href="{{ route('home') }}">{{ Auth::user()->name }}</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-primary my-2 my-sm-0" type="submit">
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="form-inline my-2 my-lg-0">
                    <a class="btn btn-outline-primary mr-sm-2" href="{{ route('login') }}">{{ __('Login') }}</a>
                    <a class="btn btn-primary my-2 my-sm-0" href="{{ route('register') }}">{{ __('Register') }}</a>
                </div>
            @endif
        </div>
    </nav>

    <div class="jumbotron">
        <div class="container">
            <h1 class="display-4">Облачный хостинг игровых серверов</h1>
            <p class="lead">почасовая тарификация за выделенные ресурсы (vcpu, ssd, memory)<br>
                без ограничений по слотам для игроков</p>
            <p>оплачивайте только используемое время без необходимости помесячной оплаты</p>
        </div>
    </div>

    <div class="container">
        <h2 class="text-center">Наши возможности</h2>
        <div class="row">
            <div class="col-sm-4 p-2">Быстрая установка</div>
            <div class="col-sm-4 p-2">Базовая защита от DDOS атак (L3, L4)</div>
            <div class="col-sm-4 p-2">Полный доступ к файлам сервера (FTP)</div>
            <div class="col-sm-4 p-2">Установка любых модов и конфигураций</div>
            <div class="col-sm-4 p-2">Консоль сервера</div>
            <div class="col-sm-4 p-2">Создание резервных копий</div>
            <div class="col-sm-4 p-2">Панель управления pterodactyl</div>
            <div class="col-sm-4 p-2">100% выделенные ресурсы на сервер</div>
        </div>
    </div>

    <div class="container my-4 py-4">
        <div class="row">

            <div class="card col-sm-6 px-0">
                <img src="{{ asset('images/game/terraria.webp') }}" class="card-img-top rounded-top" alt="Terraria">
                <div class="card-body row align-items-start">
                    <p class="card-text pt-2 mb-0">Цена от 0.14 ₽/час</p>
                    <a href="{{ route('servers.index') }}" class="btn btn-outline-success ml-auto">Заказать сервер</a>
                </div>
            </div>

            <div class="card col-sm-6 px-0">
                <img src="{{ asset('images/game/cs2.webp') }}" class="card-img-top rounded-top" alt="Counter-Strike 2">
                <div class="card-body row align-items-start">
                    <p class="card-text pt-2 mb-0">Цена от 0.35 ₽/час</p>
                    <a href="{{ route('servers.index') }}" class="btn btn-outline-success ml-auto">Заказать сервер</a>
                </div>
            </div>

        </div>
    </div>

@endsection
