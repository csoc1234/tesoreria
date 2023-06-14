<b-modal id="modalFormCreditoBanco" :title="modales.tituloCreditoBanco" scrollable>
    <div>
        <!-- FILA  -->
        @include('elements.alertMsgJsModal')


        <div class="form-row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="cboBanco"><b>Banco</b></label>
                    <select class="form-control form-control-sm" v-model="creditoBanco.banco_id">
                        <option value=0>Seleccione un item</option>
                        <option v-for="item in listados.bancos" :value="item.id">@{{ item.descripcion }}
                        </option>
                    </select>
                    <span v-if="errors?.banco_id" class="custom_error">@{{ errors . banco_id }}</span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="cboBanco"><b>Descripción/anotaciones</b></label>
                    <textarea class="form-control form-control-sm" maxlength="200" @keypress='validarMaxLength($event, 200)' v-model="creditoBanco.descripcion"></textarea>
                    <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtValor"><b>Valor</b></label>
                    <input type="number" autocomplete="off"  class="form-control form-control-sm" @keypress='validarMaxLengthInt($event, 15)' id="txtValor" v-model="creditoBanco.valor" name="txtValor" maxlength="15" placeholder="" /> @{{ creditoBanco.valor | currency }}
                    <span v-if="errors?.valor" class="custom_error"><br />@{{ errors . valor }}</span>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtSpread"><b>Spread</b></label>
                    <input type="number" @keypress='validarMaxLengthNumeric($event, 6)' class="form-control form-control-sm" id="txtSpread" v-model="creditoBanco.spread" name="txtSpread" maxlength="15" placeholder="" />
                    <span v-if="errors?.spread" class="custom_error"><br />@{{ errors . spread }}</span>
                </div>
            </div>
        </div>


        <div class="form-row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtFechaInicio"><b>Fecha inicio</b></label>
                    <!-- <input type="date" class="form-control form-control-sm" id="txtFechaInicio" v-model="creditoBanco.fecha_inicio" name="txtFechaInicio" placeholder="" /> -->
                    <vuejs-datepicker v-model='creditoBanco.fecha_inicio' format="dd-MM-yyyy" input-class="form-control form-control-sm"></vuejs-datepicker>
                    <span v-if="errors?.fecha_inicio" class="custom_error">@{{ errors . fecha_inicio }}</span>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtNumAnnos"><b>Número de años</b></label>
                    <input type="number" @keypress='validarMaxLengthInt($event, 2)'  class="form-control form-control-sm" id="txtNumAnnos" v-model="creditoBanco.num_annos" name="txtNumAnnos" placeholder="" />
                    <span v-if="errors?.num_annos" class="custom_error">@{{ errors . num_annos }}</span>
                </div>
            </div>
            <!--<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="txtFechaFin"><b>Fecha fin</b></label>
                            <input type="date" class="form-control form-control-sm" id="txtFechaFin" v-model="creditoBanco.fecha_fin" name="txtFechaFin" placeholder="" />
                            <span v-if="errors?.fecha_fin" class="custom_error">@{{ errors . fecha_fin }}</span>
                        </div>
                    </div> -->
        </div>

        <div class="form-row">


            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtPeriodoGracia"><b>Periodo de gracia (meses)</b></label>
                    <input type="number" @keypress='validarMaxLengthInt($event, 2)' class="form-control form-control-sm" id="txtPeriodoGracia" v-model="creditoBanco.periodo_gracia" name="txtPeriodoGracia" placeholder="" />
                    <span v-if="errors?.periodo_gracia" class="custom_error">@{{ errors . periodo_gracia }}</span>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtNumDias"><b>Número de días</b></label>
                    <!-- input type="number" id="txtNumDias" class="form-control form-control-sm" v-model="creditoBanco.num_dias" name="txtNumDias" placeholder="" /> -->
                    <select class="form-control form-control-sm" v-model="creditoBanco.num_dias">
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
                    <select class="form-control form-control-sm" v-model="creditoBanco.tasa_ref">
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
                    <input type="number" @keypress='validarMaxLengthNumeric($event, 6)' id="txtValorTasaRef" class="form-control form-control-sm" v-model="creditoBanco.tasa_ref_valor" name="txtValorTasaRef" placeholder="" />
                    <span v-if="errors?.tasa_ref_valor" class="custom_error">@{{ errors . tasa_ref_valor }}</span>
                </div>
            </div>
        </div>

    </div>

    <template #modal-footer="{ ok, cancel, hide }">

        @if(Gate::check('check-authorization', PERMISO_CREAR_CREDITO_BANCARIO_SIMULADO))
        <button v-if="!modales.editarCreditoBanco" class="btn btn-success btn-sm" @click="guardarCreditoBanco_click">
            <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.guardarCredito"></i>
            Guardar
        </button>
        @endif

        @if(Gate::check('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO))
        <button v-if="modales.editarCreditoBanco" class="btn btn-success btn-sm" @click="editarCreditoBanco_click">
            <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.editarCredito"></i>
            Editar
        </button>
        @endif

        @if(Gate::check('check-authorization', PERMISO_BORRAR_CREDITO_BANCARIO_SIMULADO))
        <button v-if="modales.editarCreditoBanco" class="btn btn-danger btn-sm" @click="borrarCreditoBanco_click">
            <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.borrarCredito"></i>
            Borrar
        </button>
        @endif

        <b-button size="sm" variant="secondary" @click="cancel()">
            Cerrar
        </b-button>

    </template>
</b-modal>
