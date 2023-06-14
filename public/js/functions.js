const BASE_URL = 'http://127.0.0.1:8184/';
//const BASE_URL = 'https://pruebastic.valledelcauca.gov.co/';
//const BASE_URL = 'https://emprestitos.valledelcauca.gov.co/';

function capturarCierreSesion(error) {
    /* if (
         error.response !== undefined &&
         error.response.status !== undefined &&
         Number(error.response.status) === 408
     ) {
         alert('Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente');
         window.location = BASE_URL + 'usuarios/login';
         return false;
     }*/

    // console.log({ BASE_URL: BASE_URL });

    if (
        error.response !== undefined &&
        error.response.status !== undefined
    ) {
        if (Number(error.response.status) === 408) {
            alert(
                "Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente"
            );
            window.location = BASE_URL;
            return false;
        }

        if (Number(error.response.status) === 403) {
            let msg =
                "El token CSRF se venció, el sistema debe recargar este formulario para generar uno nuevo. ";
            msg += "acto seguido debe intentar ingresar nuevamente";

            alert(msg);
            window.location.reload();
            return false;
        }
    }

    return false;
}



function errorHandling(error, customMsg) {
    let errorMsg = "";
    let objectError = null;

    if (error.response !== undefined) {
        const data = error.response.data;

        if (error.response.status === 422) {
            //422 código de error de validación

            errorMsg = String(data.message);
        } else if (Number(error.response.status) === 419) {
            let msg =
                'El Token CSRF se venció, el sistema recargará este formulario para generar uno nuevo. ';
            msg += 'Debe iniciar sessión nuevamente';
            alert(msg);
            location.reload();
            return false;
        } else {
            errorMsg = String(data.message);
        }

        if (data.errors) {
            objectError = data.errors;
        }

    } else {
        errorMsg = customMsg;
    }

    return { msg: errorMsg, errors: objectError };
}

var myMixin = {
    created: function() {
        //  this.hello()
    },

    data: function() {
        return {
            alertMsg: {
                showError: false,
                showSuccess: false,
                msg: ''
            },
            alertMsgModal: {
                showError: false,
                showSuccess: false,
                msg: ''
            },
            errors: {}
        };
    },
    methods: {
        enviarDatos(formDatos, controlador, tipo = 'POST') {
            //'multipart/form-data'

            return new Promise((resolve, reject) => {
                //  console.log({ tipoPeticion: tipo });

                axios({
                        method: tipo, //Método de envio
                        url: BASE_URL + controlador,
                        data: formDatos,
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then((response) => {
                        //Respuesta del servidor
                        //  console.log(response);

                        resolve(response);
                    })
                    .catch(error => {
                        //Por si ocurre algun error

                        capturarCierreSesion(error);

                        reject(error);
                    })
                    .finally(() => {
                        //  window.location.href = "#vueapp";
                        console.log('finally');
                    });
            });
        },

        enviarDatosArchivos(formDatos, controlador, tipo = 'POST') {
            //'multipart/form-data'

            return new Promise((resolve, reject) => {
                // console.log({ controlador: controlador });

                axios({
                        method: tipo, //Método de envio
                        url: BASE_URL + controlador,
                        data: formDatos,
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                    .then((response) => {
                        //Respuesta del servidor
                        //  console.log(response);

                        resolve(response);
                    })
                    .catch(error => {
                        //Por si ocurre algun error

                        capturarCierreSesion(error);

                        reject(error);
                    })
                    .finally(() => {
                        //  window.location.href = "#vueapp";
                        console.log('finally');
                    });
            });
        },
        cargarDatos(controlador, params = {}, metodo = 'GET') {
            return new Promise((resolve, reject) => {
                axios({
                        method: metodo, //Método de envio
                        url: BASE_URL + controlador,
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        params: params
                    })
                    .then(response => {
                        //Respuesta del servidor
                        resolve(response);
                    })
                    .catch(error => {
                        //Por si ocurre algun error

                        capturarCierreSesion(error);

                        reject(error);
                    })
                    .finally(() => {
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
                //   console.log($event.target.value);
                return false;
            }
        },

        validarMaxLengthInt($event, maxLength) {
            const value = $event.target.value;

            //  console.log({ valor: value });

            //Verificar si el valor ingresado es ENTERO
            if (this.isInt(String(value)) == false && String($event.target.value).length > 0) {
                $event.preventDefault();
                window.document.execCommand('undo', false, null); //Borra el ultimo valor
                // $event.target.value = String($event.target.value).trim().toString().substring(maxLength-1, maxLength);
                // return false;
            }

            /*  if (this.isInt(String(value)) == true && Number($event.target.value).length <= 0) {
                  $event.preventDefault();
                  window.document.execCommand('undo', false, null); //Borra el ultimo valo
              } */

            //Verificar que cumpla con la longitud maxima
            if (this.customMaxLength($event, maxLength) == true) {
                $event.preventDefault();
                //console.log('Esta cambiando la longitud2....');
                $event.target.value = String($event.target.value).trim().toString().substring(0, maxLength);
                //  item['cuota_recibida'] = $event.target.value;
                // console.log($event.target.value);
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
                //console.log($event.target.value);
                return false;
            }
        },

        isInt(value) {
            const er = /^-?[0-9]+$/;
            return er.test(value);
        },

        isEmpty(str) {
            return (!str || str.length === 0);
        },

        isNumeric(str) {
            if (typeof str != 'string') return false; // we only process strings!
            return (!isNaN(str) && !isNaN(parseFloat(str)) // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
            ); // ...and ensure strings of whitespace fail
        },

        /*isNum(value) {
            return /^-?\d+$/.test(value);
        },*/

        redirectUrl: function(url) {
            window.location.href = BASE_URL + url;
        },

        goBack: () => {
            window.history.back();
        },

        /*capturarCierreSesison: (error) => {
            if (
                error.response !== undefined &&
                error.response.status !== undefined &&
                Number(error.response.status) === 401
            ) {
                alert('Su sesión ha finalizado por inactividad. Debe iniciar sesión nuevamente');
                window.location.href = BASE_URL+ 'login';
                return false;
            }
        },*/
        showMsgBoxOne(msg, titulo = 'Notificación', redirect = false, url = '') {
            //   this.boxOne = ''
            //this.alertMgs.boxOne = msg;
            this.$bvModal
                .msgBoxOk(msg, {
                    title: titulo,
                    okVariant: 'secondary',
                    bodyClass: 'bootstrapAlert'
                })
                .then((value) => {
                    //console.log(value);
                    if (redirect) {
                        this.redirectUrl(url);
                    }
                })
                .catch((err) => {
                    // An error occurred
                });
        },

        showAlertMsg(msg, type = 'success', time = 10000) {
            type = type.toUpperCase();

            if (type == 'SUCCESS') {
                this.alertMsg.showSuccess = true;
                this.alertMsg.showError = false;
            } else {
                this.alertMsg.showSuccess = false;
                this.alertMsg.showError = true;
            }

            this.alertMsg.msg = msg;

            if (Number(time) > 0) {
                setTimeout(() => {
                    this.alertMsg.showSuccess = false;
                    this.alertMsg.showError = false;
                }, time);
            }

            window.scrollTo(0, 0);
        },

        showAlertMsgModal(msg, type = 'success', time = 6000) {
            type = type.toUpperCase();

            if (type == 'SUCCESS') {
                this.alertMsgModal.showSuccess = true;
                this.alertMsgModal.showError = false;
            } else {
                this.alertMsgModal.showSuccess = false;
                this.alertMsgModal.showError = true;
            }

            this.alertMsgModal.msg = msg;

            if (Number(time) > 0) {
                setTimeout(() => {
                    this.alertMsgModal.showSuccess = false;
                    this.alertMsgModal.showError = false;
                }, time);
            }
        },
        //CARGAR DATOS
        ajaxCargarRegistros(tabla, arreglo, filtro = false, valor = null, columna = null) {
            // const tabla = 'departamentos';
            const controlador = 'general/ajaxCargarRegistros/' + tabla;
            let params = {};
            if (filtro === true) {
                params = {
                    filtro: filtro,
                    valor: valor,
                    columna: columna
                };
            }

            this.cargarDatos(controlador, params)
                .then(response => { //Respuesta del servidor exitosa
                    const data = response.data;
                    if (data.success === true) {
                        this.listados[arreglo] = data.listado;
                    }
                    /*  console.log(response);
                      console.log({
                          arreglo: this.listados[arreglo]
                      });*/
                }).catch(error => { //Respuesta del servidor erronea
                    capturarCierreSesion(error);
                    console.error(error);

                }).finally(() => {
                    //console.log('finally');
                });
        },

        errorHandling(error, customMsg) {
            let errorMsg = '';
            //  if (error.response !== undefined) {
            if (error.response !== undefined) {
                const data = error.response.data;
                if (error.response.status === 422) {
                    this.errors = data.errors;
                    errorMsg = String(data.message);
                } else {
                    errorMsg = String(data.message);
                }
            } else {
                errorMsg = customMsg;
            }
            /*  } else {
                  errorMsg = customMsg;
              }*/


            return errorMsg;
        },

        limpiarMsg() {
            this.alertMsg.showError = false;
            this.alertMsg.showSuccess = false;
            this.alertMsg.msg = false;
            this.alertMsgModal.showError = false;
            this.alertMsgModal.showSuccess = false;
            this.alertMsgModal.msg = false;
        },
        showMsgBoxOk(msg) {
            //this.boxTwo = ''
            this.$bvModal.msgBoxOk(msg, {
                    title: 'Notificación',
                    size: 'sm',
                    buttonSize: 'sm',
                    okVariant: 'success',
                    headerClass: 'p-2 border-bottom-0',
                    footerClass: 'p-2 border-top-0',
                    centered: false
                })
                .then(value => {
                    // this.boxTwo = value
                })
                .catch(err => {
                    // An error occurred
                })

        }

    }
};