<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>@yield('title')</title>
</head>

<link href="{{asset('css/app.css')}}" rel ="stylesheet">

<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <a class="navbar-brand" href="#"></a>

    <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link"> Generate config </a>
            </li>
        </ul> 
    </div>
</nav>

@yield('content')
    <footer id="footer" class="py-5 bg-dark">
        <div class="container">
            <p class="m-0 text-center text-white"></p>
        </div>
    </footer>
<link href="/css/orders-view.css" rel="stylesheet">
