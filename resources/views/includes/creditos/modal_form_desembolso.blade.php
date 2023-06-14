<b-modal id="modalFormDatosDesembolso" size="lg" title="Datos del desembolso" scrollable>

    <div>
        @include('elements.alertMsgJsModal')

        <div class="form-row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="cboBanco"><b>Banco</b></label>
                    <select :disabled=true class="form-control form-control-sm" v-model="datosDesembolso.banco_id">
                        <option value=0>Seleccione un item</option>
                        <option v-for="item in listados.bancos" :value="item.id">@{{ item . descripcion }}
                        </option>
                    </select>
                    <span v-if="errors?.banco_id" class="custom_error">@{{ errors . banco_id }}</span>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label><b>Desembolso independiente</b></label><br />
                    <input type="checkbox" v-model="datosDesembolso.es_independiente"
                        @click='verificarEsIndependiente_click($event)' class="" />
                    <span v-if="errors?.es_independiente" class="custom_error">@{{ errors . es_independiente }}</span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-6">
                <div class="form-group">
                    <label for="cboBanco"><b>Descripción</b></label>
                    <textarea class="form-control form-control-sm" maxlength="500" placeholder="Ej: Desembolso 1"
                        v-model="datosDesembolso.descripcion"></textarea>
                    <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtValor"><b>Valor</b></label>
                    <input type="text" class="form-control form-control-sm" id="txtValor"
                        v-model="datosDesembolso.valor" autocomplete="off" @keypress='validarMaxLengthInt($event, 15)' name="txtValor"
                        maxlength="15" placeholder="" />
                    @{{ (datosDesembolso . valor) | currency }}
                    <span v-if="errors?.valor" class="custom_error"><br />@{{ errors . valor }}</span>
                </div>
            </div>
        </div>


        <div class="form-row" v-if="datosDesembolso.es_independiente">

            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtNumAnnos"><b>Número de años</b></label>
                    <input type="number" min="1" max="99" maxlength="2" @keypress='validarMaxLengthInt($event, 1)'
                        @input="cambiarNumAnnos" class="form-control form-control-sm" id="txtNumAnnos"
                        v-model="datosDesembolso.num_annos" name="txtNumAnnos" placeholder="" />
                    <span v-if="errors?.num_annos" class="custom_error">@{{ errors . num_annos }}</span>
                </div>
            </div>

        </div>

        <div class="form-row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtFechaInicio"><b>Fecha inicio</b></label>

                    <!-- <input type="date" class="form-control form-control-sm" id="txtFechaInicio" v-model="datosDesembolso.fecha_inicio" name="txtFechaInicio" placeholder="" /> -->
                    <vuejs-datepicker @selected="seleccionarFechaInicial" v-model='datosDesembolso.fecha_inicio'
                        format="dd-MM-yyyy" input-class="form-control form-control-sm"></vuejs-datepicker>
                    <span v-if="errors?.fecha_inicio" class="custom_error">@{{ errors . fecha_inicio }}</span>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtFechaInicio"><b>Fecha fin</b></label>
                    <!-- <input type="date" class="form-control form-control-sm" id="txtFechaInicio" v-model="datosDesembolso.fecha_inicio" name="txtFechaInicio" placeholder="" /> -->
                    <vuejs-datepicker :disabled='true' v-model='datosDesembolso.fecha_fin' format="dd-MM-yyyy"
                        input-class="form-control form-control-sm"></vuejs-datepicker>
                    <span v-if="errors?.fecha_fin" class="custom_error">@{{ errors . fecha_fin }}</span>
                </div>
            </div>
        </div>

        <div v-if="datosDesembolso.es_independiente">

            <div class="form-row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtSpread"><b>Spread</b></label>
                        <input type="text" @keypress='validarMaxLengthNumeric($event, 5)'
                            class="form-control form-control-sm" id="txtSpread" v-model="datosDesembolso.spread"
                            name="txtSpread" maxlength="5" placeholder="" />
                        <span v-if="errors?.spread" class="custom_error">@{{ errors . spread }}</span>
                    </div>
                </div>
            </div>


            <div class="form-row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtPeriodoGracia"><b>Periodo de gracia (meses)</b></label>
                        <input type="number" @keypress='validarMaxLengthInt($event, 2)'
                            class="form-control form-control-sm" id="txtPeriodoGracia"
                            v-model="datosDesembolso.periodo_gracia" name="txtPeriodoGracia" placeholder="" />
                        <span v-if="errors?.periodo_gracia"
                            class="custom_error">@{{ errors . periodo_gracia }}</span>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtNumDias"><b>Número de días</b></label>
                        <!-- input type="number" id="txtNumDias" class="form-control form-control-sm" v-model="datosDesembolso.num_dias" name="txtNumDias" placeholder="" /> -->
                        <select class="form-control form-control-sm" v-model="datosDesembolso.num_dias">
                            <option value="0">Seleccione un valor</option>
                            <option value="30">Mensual - 30 días</option>
                            <option value="60">Bimensual - 60 días</option>
                            <option value="90">Trimestral - 90 días</option>
                            <option value="120">Cuatrimestral - 120 días</option>
                            <option value="180">Semestral - 180 días</option>
                        </select>
                        <span v-if="errors?.num_dias" class="custom_error">@{{ errors . num_dias }}</span>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtFecha"><b>Tasa de referencia</b></label>
                        <select class="form-control form-control-sm" v-model="datosDesembolso.tasa_ref">
                            <option value="">Seleccione un valor</option>
                            <option value='IBR'>IBR</option>
                            <option value='DTF'>DTF</option>
                        </select>
                        <span v-if="errors?.tasa_ref" class="custom_error">@{{ errors . tasa_ref }}</span>
                    </div>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtValorTasaRef"><b>Valor tasa de referencia</b></label>
                        <input type="number" @keypress='validarMaxLengthNumeric($event, 5)' id="txtValorTasaRef"
                            class="form-control form-control-sm" v-model="datosDesembolso.tasa_ref_valor"
                            name="txtValorTasaRef" placeholder="" />
                        <span v-if="errors?.tasa_ref_valor"
                            class="custom_error">@{{ errors . tasa_ref_valor }}</span>
                    </div>
                </div>
            </div>
        </div>


        <div class="form-row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label><b>Capital e intereses separados</b></label><br />
                    <input type="checkbox" v-model="datosDesembolso.separar_interes_capital" class="" />
                    <span v-if="errors?.separar_interes_capital"
                        class="custom_error">@{{ errors . separar_interes_capital }}</span>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label><b>Truncar Tasa EA</b></label><br />
                    <input type="checkbox" v-model="datosDesembolso.truncar_tasa_ea" class="" />
                    <span v-if="errors?.truncar_tasa_ea" class="custom_error">@{{ errors . truncar_tasa_ea }}</span>
                </div>
            </div>
        </div>

    </div>

    <template #modal-footer="{ ok, cancel, hide }">
        <div v-if="!mostrarBtnEditarBorrar">
            @if (Gate::check('check-authorization', PERMISO_CREAR_DESEMBOLSO_SIMULADO))
                <button class="btn btn-success btn-sm" @click="guardarDesembolso_click">
                    <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.guardarDesembolso"></i>
                    Guardar
                </button>
            @endif
        </div>
        <div v-if="mostrarBtnEditarBorrar">
            @if (Gate::check('check-authorization', PERMISO_EDITAR_DESEMBOLSO_SIMULADO))
                <button class="btn btn-success btn-sm" @click="editarDesembolso_click">
                    <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.editarDesembolso"></i>
                    Editar
                </button>
            @endif
            @if (Gate::check('check-authorization', PERMISO_BORRAR_DESEMBOLSO_SIMULADO))
                <button class="btn btn-danger btn-sm" @click="borrarDesembolso_click">
                    <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.borrarCredito"></i>
                    Borrar
                </button>
            @endif
        </div>

        <b-button size="sm" variant="secondary" @click="cancel()">
            Cerrar
        </b-button>

    </template>
</b-modal>
