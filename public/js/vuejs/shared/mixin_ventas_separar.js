var mixinVentasSeparar = {

    data: function () {
        return {
            procesoEnEjecucion: false,
            debounceTimer: 0,
            //  searchData:[],
            mostrarDivMsgExito: false,
            mostrarDivMsgError: false,
            msgNotificacion: '',
            msgNotificacionParcelas: '',
            banco_id: '',
            metodo_pago_id: '',
            formCargado: false,
            infoValidada: false,
            infoProyecto: {},
            infoCliente: {},
            infoAsesor: {},
            numeroParcela: '',
            totalSeparadas: 0,
            totalSeparadasConSignoPeso: 0,

            listados: {
                lstBancos: [],
                lstMetodosPago: [],
                lstParcelasProyecto: [],
                lstParcelasSeparadas: [],
                listadoAsesores: [],
                listadoClientes: [],
                listadoProyectos: []
            },

            errores: {},

            filtroBusquedaCliente: '',

            formDatos: {
                asesor_identificacion: '',
                codigo_proyecto: '',
                cliente_identificacion: ''

            }
        }
    },

    methods: {

      
        obtenerListadoAsesores(filtro) {

            console.log({
                filtro: filtro
            });


            console.log({
                filtro: filtro
            });


            if (filtro.toString().length === 0) {
                return false;
            }

            this.procesoEnEjecucion = true;

            clearTimeout(this.debounceTimer);

            this.debounceTimer = setTimeout(() => {

                const controlador = 'usuarios/ajaxListadoAsesoresFiltrados?filtro=' + filtro;

                this.cargarDatos(controlador).then((response) => {
                    console.log(response);

                    if (response.data.peticionExitosa == true) {
                        vueData.listados.listadoAsesores = response.data.asesores;

                        const listado = vueData.listados.listadoAsesores;
                        let nuevoListado = [];

                        Object.keys(listado).forEach(function (index) {
                            let obj = listado[index];
                            const tempObj = {
                                identificacion: obj.identificacion,
                                nombres: obj.nombres + ' ' + obj.apellidos + ' (' + obj.identificacion + ')'
                            }
                            nuevoListado.push(tempObj);
                        });

                        vueData.listados.listadoAsesores = [];
                        vueData.listados.listadoAsesores = nuevoListado;

                    } else {
                        vueData.listados.listadoAsesores = [];
                        this.formDatos.asesor_identificacion = '';
                    }

                }).catch(error => {
                    console.log(error);
                    this.mostrarDivMsgExito = false;
                    this.mostrarDivMsgError = true;
                    this.procesoEnEjecucion = false;

                    if (error.response !== undefined) {
                        this.msgNotificacion = error.response.data.msgRespuesta;
                        this.errores = error.response.data.erroresValidacion;

                    } else {
                        this.msgNotificacion = 'Error al intentar realizar la operación. Debe intentar de nuevo';
                    }

                }).finally(() => {
                    this.procesoEnEjecucion = false;
                });
            }, 500);

            console.log(this.debounceTimer);

            return false;

        },

        obtenerListadoProyectos (filtro) {

            console.log({
                filtro: filtro
            });


            if (filtro.toString().length === 0) {
                return false;
            }

            this.procesoEnEjecucion = true;

            clearTimeout(this.debounceTimer);

            this.debounceTimer = setTimeout(() => {

                const controlador = 'proyectos/ajaxListadoProyectosFiltrados?filtro=' + filtro;

                this.cargarDatos(controlador).then((response) => {

                    console.log(response);

                    if (response.data.peticionExitosa == true) {

                        const listado = response.data.proyectos;
                        let nuevoListado = [];

                        Object.keys(listado).forEach(function (index) {
                            let obj = listado[index];
                            const tempObj = {
                                codigo: obj.codigo,
                                nombre: obj.nombre + ' (' + obj.codigo + ')'
                            };
                            nuevoListado.push(tempObj);
                        });

                        vueData.listados.listadoProyectos = [];
                        vueData.listados.listadoProyectos = nuevoListado;

                    } else {
                        vueData.listados.listadoProyectos = [];
                        this.formDatos.codigo_proyecto = '';
                    }

                }).catch(error => {

                    console.log(error);
                    this.mostrarDivMsgExito = false;
                    this.mostrarDivMsgError = true;

                    this.procesoEnEjecucion = false;

                    if (error.response !== undefined) {
                        
                        this.msgNotificacion = error.response.data.msgRespuesta;
                        this.errores = error.response.data.erroresValidacion;

                    } else {
                        this.msgNotificacion = 'Error al intentar realizar la operación. Debe intentar de nuevo';
                    }

                }).finally(() => {
                    this.procesoEnEjecucion = false;
                });
            }, 500);

            console.log(this.debounceTimer);

            return false;

        },

        obtenerListadoClientes(filtro) {

            console.log({
                filtro: filtro
            });


            if (filtro.toString().length === 0) {
                return false;
            }

            this.procesoEnEjecucion = true;

            clearTimeout(this.debounceTimer);

            this.debounceTimer = setTimeout(() => {

                const controlador = 'clientes/ajaxListadoClientesFiltrados?filtro=' + filtro;

                this.cargarDatos(controlador).then((response) => {


                    if (response.data.peticionExitosa == true) {
                        //  vueData.listados.listadoAsesores = response.data.lstClientes;

                        const listado = response.data.lstClientes;
                        console.log({
                            listadoClientes: listado
                        });

                        let nuevoListado = [];

                        Object.keys(listado).forEach(function (index) {
                            let obj = listado[index];
                            let tempObj;

                            console.log({
                                objeto: obj
                            });

                            if (Number(obj.tipo_cliente_id) === 1) { //Persona

                                tempObj = {
                                    identificacion: obj.identificacion,
                                    nombres: obj.descripcion_cliente
                                }

                            } else if (Number(obj.tipo_cliente_id) === 2) { //Empresa

                                tempObj = {
                                    identificacion: obj.identificacion,
                                    nombres: obj.razon_social + ' (' + obj.identificacion + ')'
                                }

                            }

                            nuevoListado.push(tempObj);
                        });

                        vueData.listados.listadoClientes = [];
                        vueData.listados.listadoClientes = nuevoListado;

                    } else {
                        vueData.listados.listadoClientes = [];
                        this.formDatos.cliente_identificacion = '';
                    }

                }).catch(error => {
                    console.log(error);
                    this.mostrarDivMsgExito = false;
                    this.mostrarDivMsgError = true;

                    this.procesoEnEjecucion = false;
                    //   this.formCargado = true;

                    if (error.response !== undefined) {
                        this.msgNotificacion = error.response.data.msgRespuesta;
                        this.errores = error.response.data.erroresValidacion;

                    } else {
                        this.msgNotificacion = 'Error al intentar realizar la operación. Debe intentar de nuevo';
                    }

                }).finally(() => {
                    this.procesoEnEjecucion = false;
                });
            }, 500);

            console.log(this.debounceTimer);

            return false;

        }

    }

}