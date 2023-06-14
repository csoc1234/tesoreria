<b-modal id="modalFormularioDesembolso" title="Desembolso" scrollable>
    <div>
        <!-- FILA  -->
        <div>
            @include('elements.alertMsgJs')
            <div class="form-row">

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtDescripcion"><b>Descripción</b></label>
                        <input type="text" autocomplete="off" v-model="desembolso.descripcion" class="form-control form-control-sm" name="txtDescripcion" placeholder="Ej: Desembolso 1"></input>
                        <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                    </div>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtValor"><b>Valor</b></label>
                        <input type="text" v-model="desembolso.valor" autocomplete="off" class="form-control form-control-sm" id="txtValor" name="txtValor" maxlength="15" placeholder="" /> @{{ desembolso.valor | currency }}
                        <span v-if="errors?.valor" class="custom_error"><br />@{{ errors . valor }}</span>
                    </div>
                </div>


            </div>


            <div class="form-row">

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtFechaInicio"><b>Fecha inicio (día-mes-año)</b></label>
                        <!-- <input type="date" v-model="desembolso.fecha_inicio" class="form-control form-control-sm" id="txtFechaInicio" name="txtFechaInicio" placeholder="" /> -->
                        <vuejs-datepicker @selected="seleccionarFechaInicial" format="dd-MM-yyyy" v-model='desembolso.fecha_inicio' input-class="form-control form-control-sm"></vuejs-datepicker>


                        <span v-if="errors?.fecha_inicio" class="custom_error">@{{ errors . fecha_inicio }}</span>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtFechaFin"><b>Fecha fin (día-mes-año) </b></label>

                        <!-- <input type="date" :disabled="true" v-model="desembolso.fecha_fin" class="form-control form-control-sm" id="txtFechaFin" name="txtFechaFin" placeholder="" /> -->
                        <vuejs-datepicker v-model='desembolso.fecha_fin' :disabled="true" format="dd-MM-yyyy" input-class="form-control form-control-sm"></vuejs-datepicker>
                        <span v-if="errors?.fecha_fin" class="custom_error">@{{ errors . fecha_fin }}</span>
                    </div>
                </div>
            </div>

            <div class="form-row">

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label><b>Desembolso independiente</b></label><br />
                        <input type="checkbox" v-model="desembolso.es_independiente" @click='verificarEsIndependiente($event)' class="" />
                        <span v-if="errors?.es_independiente" class="custom_error">@{{ errors . es_independiente }}</span>
                    </div>
                </div>
                <div class="col-6" v-if="!desembolso.es_primer_desembolso">
                    <div class="form-group">
                        <label><b>Capital e intereses separados</b></label><br />
                        <input type="checkbox" v-model="desembolso.separar_interes_capital" class="" />
                        <span v-if="errors?.separar_interes_capital" class="custom_error">@{{ errors . separar_interes_capital }}</span>
                    </div>
                </div>
            </div>

            <div class="form-row">

                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label><b>Truncar Tasa EA</b></label><br />
                        <input type="checkbox" v-model="desembolso.truncar_tasa_ea" class="" />
                        <span v-if="errors?.truncar_tasa_ea" class="custom_error">@{{ errors . truncar_tasa_ea }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <template #modal-footer="{ ok, cancel, hide }">

        <button v-if="!editarDesembolso" class="btn btn-success btn-sm" @click="guardarDesembolso_click">
            <span v-if="spinners.guardaDesembolso" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Guardar
        </button>
        <button v-if="editarDesembolso" class="btn btn-success btn-sm" @click="editarDesembolso_click">
            <span v-if="spinners.editarDesembolso" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Editar
        </button>
        <b-button size="sm" variant="secondary" @click="cancel()">
            Cerrar
        </b-button>

    </template>
</b-modal>
