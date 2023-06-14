<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600|Open+Sans:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ URL::asset('css/easion.css') }}" el="stylesheet" type="text/css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/bootstrap-vue@2.21.2/dist/bootstrap-vue.css" />
    <script src="{{ URL::asset('js/easion.js') }} "></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.js" integrity="sha512-otOZr2EcknK9a5aa3BbMR9XOjYKtxxscwyRHN6zmdXuRfJ5uApkHB7cz1laWk2g8RKLzV9qv/fl3RPwfCuoxHQ==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typescript/4.2.2/typescript.js" integrity="sha512-8AWpJQtEOcNPfAlTkQnBOeScloC99n8AcLiohfvhChO1wNUzeOZgyqwipiwfsv0XWlBblez/yIp7KWHBwywHHw==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vuex/3.5.1/vuex.min.js" integrity="sha512-n/iV5SyKXzLRbRczKU75fMgHO0A1DWJSWbK5llLNAqdcoxtUK3NfgfszYpjhvcEqS6nEXwu7gQ5bIkx6z8/lrA==" crossorigin="anonymous"></script>
    <title>PROYECCIONES DEUDA</title>
    <script src="{{ URL::to('js/bootstrap-vue.min.js') }}"></script>
    <script src="{{ URL::to('js/functions.js') }}"></script>
    <script src="{{ URL::to('js/vue2-filters.min.js') }}"></script>
    <script src="{{ URL::to('js/httpVueLoader.js') }}"></script>
    <script src="{{ URL::to('js/vuejs-datepicker.min.js') }}"></script>
    <script src="{{ URL::to('js/moment.min.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js" integrity="sha512-WFN04846sdKMIP5LKNphMaWzU7YpMyCU245etK3g/2ARYbPK9Ub18eG+ljU96qKRCWh+quCY7yefSmlkQw1ANQ==" crossorigin="anonymous"></script>

    <script>
       // Vue.prototype.$baseUrl = 'http://127.0.0.1:8184/';
        //Vue.prototype.$baseUrl = 'https://pruebastic.valledelcauca.gov.co/';
        Vue.config.devtools = true;
    </script>

</head>

<body id="app">
    <div class="dash">
        <div class="dash-nav dash-nav-dark">
            <header>

                <img style="width: 160px;" src="{{ URL::to('img/logogobernacionsinfondo.png') }}" />
            </header>
            <nav class="dash-nav-list">
                @include('includes.menu')
            </nav>
        </div>
        <div class="dash-app">
            <header class="dash-toolbar">
                <a href="#!" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </a>
                <a href="#!" class="searchbox-toggle">
                    <i class="fas fa-search"></i>
                </a>
                <form class="searchbox" action="#!">
                    <a href="#!" class="searchbox-toggle"> <i class="fas fa-arrow-left"></i> </a>
                    <!-- <button type="submit" class="searchbox-submit"> <i class="fas fa-search"></i> </button>
                    <input type="text" class="searchbox-input" placeholder="type to search"> -->
                </form>
                <div class="tools">
                    @auth
                    <div class="form-row">
                        <div class="col-12 text-right">
                            {{ Auth::user()->nombres.' '.Auth::user()->apellidos  }}<br />
                            Rol: {{ Auth::user()->rol['descripcion'] }}
                        </div>
                    </div>

                    <div class="dropdown tools-item">
                        <a href="#" class="" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">
                            <a class="dropdown-item" href="{{ route('perfil_usuario',Auth::user()->id) }}">Mi perfil</a>
                            <a class="dropdown-item" href="{{ route('logout') }}">Cerrar sesión</a>
                        </div>
                    </div>
                    @endauth
                </div>
            </header>
            <main class="dash-content">
                <div class="container-fluid">
                    @yield('content')
                </div>

            </main>
            <div class="clearfix"></div>
        </div>
    </div>

</body>

</html>
