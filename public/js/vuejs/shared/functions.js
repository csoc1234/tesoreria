var myMixin = {
    created: function() {
        //  this.hello()
    },

    data: function() {
        return {
            registroGuardado: false,
            //  desCboCiu: true,
            //  desCboDep: true,
            mostrarDivDatosPersona: true,
            mostrarDivDatosEmpresa: false,
            //     resolvePromise: null,
            //    rejectPromise: null,
            mostrarInputCiudad: false,
            msgNotificacion: '',
            formCargado: false,

            mostrarDivMsgExito: false,
            mostrarDivMsgError: false,

            listados: {
                lstPaises: [],
                lstDepartamentos: [],
                lstCiudades: [],
            },
            dataAlertMsg: {
                message: '',
                show: false,
                time: undefined,
                cssClass: 'alert alert-success'
            }

        }
    },
    methods: {


        /* CARGAR DEPARTAMENTOS */
        /* cargarDepartamentos(codPais) {
             console.log(codPais);
             axios({
                 method: 'GET', //Método de envio
                 url: this.baseUrl + 'departamentos/ajaxCargarDepartamentos?codpais=' + codPais,
             }).then(function (response) { //Respuesta del servidor
                 if (response.status === 200) {
                     //    this.listados.lstDepartamentos = [];
                     //    this.listados.lstDepartamentos = response.data.departamentos;
                     //    console.log(vueData.listados.lstDepartamentos);
                 }
             }).catch(function (error) { //Por si ocurre algun error
                 console.log(error)
             });
         }, */

        /* CARGAR DEPARTAMENTOS */
        cargarDepartamentos(idPais) {
            console.log(idPais);
            axios({
                method: 'GET', //Método de envio
                url: BASE_URL+ 'departamentos/ajaxCargarDepartamentos?idpais=' + idPais,
            }).then((response) => { //Respuesta del servidor
                if (response.status === 200) {
                    this.listados.lstDepartamentos = [];
                    this.listados.lstDepartamentos = response.data.departamentos;
                    console.log(this.listados.lstDepartamentos);
                    this.desCboDep = false;
                }
            }).catch(function(error) { //Por si ocurre algun error
                console.log(error)
            });
        },
        /* CARGAR CIUDADES */
        /*    cargarCiudades1: function (idDep) {

                console.log('Cargar ciudades....');
                /*
                Esta tambien funciona
                Or you an => function like this

                created () {
                    axios.get('/api/posts')
                    .then( (response) => {
                    this.posts = response.data;
                    })
                    .catch(function (error) {
                    console.log(error);
                    });
                },
                }

                const vueData = this;
                axios({
                    method: 'GET', //Método de envio
                    url: BASE_URL+ 'ciudads/ajaxCargarCiudades?iddep=' + idDep,
                }).then((response) => { //Respuesta del servidor
                    if (response.status === 200) {
                        vueData.desCboCiu = false;
                        vueData.listados.lstCiudades = response.data.ciudades;
                        console.log(vueData.listados.lstCiudades);
                    }
                }).catch(function (error) { //Por si ocurre algun error
                    console.log(error)
                });
            }, */

        toggleBodyClass(addRemoveClass, className) {
            const el = document.body;

            if (addRemoveClass === 'addClass') {
                el.classList.add(className);
            } else {
                el.classList.remove(className);
            }
        },

        getServerInfo(serverInfo) {
            let baseURL = config.API.ServerTypeInfo
            let url = baseURL.replace('[$$$]', serverInfo.ServerTypeID)

            return new Promise((resolve, reject) => {
                Vue.http.get(url)
                    .then(response => resolve(response))
                    .catch(() => reject)
            })
        },

        consoleLog: function(object) {
            console.log(JSON.parse(JSON.stringify(object)))
        },

        enviarDatos(formDatos, controlador, tipo = 'POST') { //'multipart/form-data'

            return new Promise((resolve, reject) => {

                console.log({ 'tipoPeticion': tipo });

                axios({
                        method: tipo, //Método de envio
                        url: BASE_URL+ controlador,
                        data: formDatos,
                        headers: {
                            "Content-Type": "application/json"
                        }
                    }).then(response => { //Respuesta del servidor
                        //  console.log(response);

                        resolve(response);


                    })
                    .catch((error) => { //Por si ocurre algun error

                        if (
                            error.response !== undefined &&
                            error.response.status !== undefined &&
                            Number(error.response.status) === 408
                        ) {
                            alert('Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente');
                            window.location.href = BASE_URL+ 'usuarios/login';
                            return false;
                        }

                        reject(error);

                    }).finally(() => {
                        //  window.location.href = "#vueapp";
                        console.log('finally');
                    });

            });

        },

        enviarDatosArchivos(formDatos, controlador, tipo = 'POST') { //'multipart/form-data'

            return new Promise((resolve, reject) => {

                console.log({ 'tipoPeticion': tipo });

                axios({
                        method: tipo, //Método de envio
                        url: BASE_URL+ controlador,
                        data: formDatos,
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => { //Respuesta del servidor
                        //  console.log(response);

                        resolve(response);


                    })
                    .catch((error) => { //Por si ocurre algun error

                        if (
                            error.response !== undefined &&
                            error.response.status !== undefined &&
                            Number(error.response.status) === 408
                        ) {
                            alert('Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente');
                            window.location.href = BASE_URL+ 'usuarios/login';
                            return false;
                        }

                        reject(error);

                    }).finally(() => {
                        //  window.location.href = "#vueapp";
                        console.log('finally');
                    });

            });

        },
        cargarDatos(controlador, params = {}) {
            return new Promise((resolve, reject) => {
                axios({
                    method: 'GET', //Método de envio
                    url: BASE_URL+ controlador,
                    headers: {
                        "Content-Type": "application/json"
                    },
                    params: params
                }).then(response => { //Respuesta del servidor
                    resolve(response);
                }).catch((error) => { //Por si ocurre algun error


                    if (
                        error.response !== undefined &&
                        error.response.status !== undefined &&
                        Number(error.response.status) === 408
                    ) {
                        alert('Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente');
                        window.location.href = BASE_URL+ 'usuarios/login';
                        return false;
                    }

                    reject(error);

                }).finally(() => {
                    console.log('finally');
                });
            });
        },

        borrarRegistro(controlador, params = {}) {
            return new Promise((resolve, reject) => {
                axios({
                    method: 'DELETE', //Método de envio
                    url: BASE_URL+ controlador,
                    params: params,
                    headers: {
                        "Content-Type": "application/json"
                    }
                }).then(response => { //Respuesta del servidor
                    resolve(response);
                }).catch((error) => { //Por si ocurre algun error
                    reject(error);
                }).finally(() => {
                    console.log('finally');
                });
            });
        },

        customMaxLength(event, maxLength = 14) {

            const length1 = String(event.target.value).trim().length;
            // const value = Number(event.target.value);


            if (length1 > Number(maxLength)) {
                event.preventDefault();
                return true;
                /// alert("not allow more than 11 character");
            }

            return false;
        },

        ocultarMensajeNotificacion(tiempo = 5000) {
            setTimeout(() => {
                this.mostrarDivMsgExito = false;
                this.mostrarDivMsgError = false;
            }, tiempo);

        },

        mostrarMensajeExito(msg, tiempo = 5000) {
            this.mostrarDivMsgExito = true;
            this.mostrarDivMsgError = false;
            this.msgNotificacion = msg;
            this.ocultarMensajeNotificacion(tiempo);
        },

        mostrarMensajeError(msg, tiempo = 5000) {
            this.mostrarDivMsgExito = false;
            this.mostrarDivMsgError = true;
            this.msgNotificacion = msg;
            this.ocultarMensajeNotificacion(tiempo);
        },


        validarMaxLengthNumeric($event, maxLength) {

            const value = $event.target.value;


            //Verificar si el valor ingresado es NUMERIC
            if (this.isNumeric(String(value)) == false && String($event.target.value).length > 0) {
                $event.preventDefault();
                // $event.target.value = String($event.target.value).trim().toString().substring(maxLength-1, maxLength);
                window.document.execCommand('undo', false, null); //Borra el ultimo valor
                // return false;
            }

            //Verificar que cumpla con la longitud maxima
            if (this.customMaxLength($event, maxLength) == true) {
                $event.preventDefault();
                $event.target.value = String($event.target.value).trim().toString().substring(0, maxLength);
                //  item['cuota_recibida'] = $event.target.value;
                console.log($event.target.value);
                return false;
            }

        },

        validarMaxLengthInt($event, maxLength) {

            const value = $event.target.value;

            console.log({ valor: value });

            //Verificar si el valor ingresado es ENTERO
            if (this.isInt(String(value)) == false && String($event.target.value).length > 0) {
                $event.preventDefault();
                console.log('Esta cambiando la longitud....');
                window.document.execCommand('undo', false, null); //Borra el ultimo valor
                // $event.target.value = String($event.target.value).trim().toString().substring(maxLength-1, maxLength);
                // return false;
            }

            //Verificar que cumpla con la longitud maxima
            if (this.customMaxLength($event, maxLength) == true) {
                $event.preventDefault();
                console.log('Esta cambiando la longitud2....');
                $event.target.value = String($event.target.value).trim().toString().substring(0, maxLength);
                //  item['cuota_recibida'] = $event.target.value;
                console.log($event.target.value);
                return false;
            }

        },

        validarMaxLength($event, maxLength) {

            const value = $event.target.value;

            //Verificar que cumpla con la longitud maxima
            if (this.customMaxLength($event, maxLength) == true && String($event.target.value).length > 0) {
                $event.preventDefault();
                $event.target.value = String($event.target.value).trim().toString().substring(0, maxLength);
                //  item['cuota_recibida'] = $event.target.value;
                console.log($event.target.value);
                return false;
            }

        },

        isInt(value) {
            const er = /^-?[0-9]+$/;
            return er.test(value);
        },

        isNumeric(str) {
            if (typeof str != "string") return false // we only process strings!
            return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
                !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
        },

        /*isNum(value) {
            return /^-?\d+$/.test(value);
        },*/


        redirectUrl: function(url) {
            window.location.href = BASE_URL+ url;
        },

        goBack: () => {
            window.history.back();
        },

        capturarCierreSesison: (error) => {
            if (
                error.response !== undefined &&
                error.response.status !== undefined &&
                Number(error.response.status) === 408
            ) {
                alert('Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente');
                window.location.href = BASE_URL+ 'usuarios/login';
                return false;
            }

        },
        showMsgBoxOne(msg, redirect = false, url = '') {
            //   this.boxOne = ''
            //this.alertMgs.boxOne = msg;
            this.$bvModal.msgBoxOk(msg, {
                    title: 'Notificación',
                    okVariant: 'secondary',
                    bodyClass: 'bootstrapAlert'
                })
                .then(value => {
                    console.log(value);
                    if (redirect) {
                        this.redirectUrl(url);
                    }
                })
                .catch(err => {
                    // An error occurred
                })
        },

        showAlertMsg(msg, sucess = true, time = 10000) {

            this.dataAlertMsg.message = msg;

            if (sucess == true) {
                this.dataAlertMsg.cssClass = 'alert alert-success';
            } else {
                this.dataAlertMsg.cssClass = 'alert alert-danger';
            }
            this.dataAlertMsg.show = true;

            if (Number(time) > 0) {
                setTimeout(() => {
                    this.dataAlertMsg.show = false;
                }, time);
            }
        }
    }
}
