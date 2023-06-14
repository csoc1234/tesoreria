@extends('layouts.login')
@section('content')
<div id="vueapp" v-cloak>
    <div class="form-screen">
        <div>
            <img style="width: 250px;" src="{{ URL::to('img/logogobernacionsinfondo.png') }}" />
        </div>
        <div class="card account-dialog" style="margin-top: 2%;">
            <div class="card-header bg-secondary text-white"> <b>Iniciar sesión</b> </div>
            <div class="card-body">

                @include('elements.alertMsg')
                @include('elements.alertMsgJs')

                <form action="#!">
                    <div class="form-group">
                        <input type="email" class="form-control" v-model="login.email" placeholder="Email">
                        <span v-if="errors?.email" class="custom_error">@{{ errors . email }}</span>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" v-model="login.password" placeholder="Contraseña">
                        <span v-if="errors?.password" class="custom_error">@{{ errors . password }}</span>
                    </div>
                    <!--<div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck1">
                            <label class="custom-control-label" for="customCheck1">Remember me</label>
                        </div>
                    </div> -->
                    <div class="account-dialog-actions">
                        <button style="width: 100%;" type="button" @click="btnLogin_click" class="btn btn-secondary">
                            <span v-if="spinners.enviarDatos" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Iniciar sesión
                        </button>

                        <!-- <a class="account-dialog-link" href="#">Recuperar contraseña</a> -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var vueApp = new Vue({
        el: '#vueapp',
        mixins: [myMixin, Vue2Filters.mixin],
        data: {

            spinners: {
                enviarDatos: false,
                // enviarDatos: false
            },

            login: {
                email: '',
                password: ''
            },

            errors: {}
        },
        methods: {

            btnLogin_click() {
                //INICIAR SPINNER
                this.spinners.enviarDatos = true;
                const controlador = 'usuarios/ajaxLogin';
                this.spinners.enviarDatos = true;
                this.enviarDatos(this.login, controlador)
                    .then(response => {
                        const data = response.data;
                        window.location.href = '/creditos/listadoCreditosSimulados';
                        this.spinners.enviarDatos = false;
                        this.errors = {};
                    })
                    .catch(error => {
                        console.log(error);
                        this.spinners.enviarDatos = false;
                        let errorMsg = '';
                        if (error.response !== undefined) {
                            const data = error.response.data;
                            this.errors = data.errors;
                            errorMsg = String(data.message);
                        } else {
                            errorMsg = 'Error al intentar validar ';
                            errorMsg += 'las credenciales del usuario';
                        }

                        this.showAlertMsg(errorMsg, 'error');


                    }).finally(() => {
                        this.spinners.enviarDatos = false;
                    });

                    return false;
            },

        },
        mounted() {
            //  alert('Hola');
        }
    });
</script>
@endsection
