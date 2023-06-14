<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600|Open+Sans:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="../css/easion.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js"></script>
    <script src="../js/chart-js-config.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.js" integrity="sha512-otOZr2EcknK9a5aa3BbMR9XOjYKtxxscwyRHN6zmdXuRfJ5uApkHB7cz1laWk2g8RKLzV9qv/fl3RPwfCuoxHQ==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typescript/4.2.2/typescript.js" integrity="sha512-8AWpJQtEOcNPfAlTkQnBOeScloC99n8AcLiohfvhChO1wNUzeOZgyqwipiwfsv0XWlBblez/yIp7KWHBwywHHw==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vuex/3.5.1/vuex.min.js" integrity="sha512-n/iV5SyKXzLRbRczKU75fMgHO0A1DWJSWbK5llLNAqdcoxtUK3NfgfszYpjhvcEqS6nEXwu7gQ5bIkx6z8/lrA==" crossorigin="anonymous"></script>
    <title>PROYECCIONES DEUDA PUBLICA</title>
    <script src="{{ URL::to('js/functions.js') }}"></script>
    <script src="{{ URL::to('js/vue2-filters.min.js') }}"></script>
    <script>
       // Vue.prototype.$baseUrl = 'http://127.0.0.1:8184/';
        //Vue.prototype.$baseUrl = 'https://pruebastic.valledelcauca.gov.co/';
        Vue.config.devtools = true;
    </script>

</head>

<body id="app">

    @yield('content')

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="../js/easion.js"></script>
</body>

</html>
