<b-modal id="modalFormBanco" title="Bancos" scrollable>
    <div>
        <!-- FILA  -->
        @include('elements.alertMsgJs')
        <div class="form-row">

            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label for="txtDescripcion"><b>Descripción</b></label>
                    <input type="text" maxlength="150" autocomplete="off" v-model="modalFormBanco.descripcion" class="form-control form-control-sm" name="txtDescripcion" placeholder=""></input>
                    <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                </div>
            </div>

        </div>


    </div>

    <template #modal-footer="{ ok, cancel, hide }">

        <button v-if="!editarBanco" class="btn btn-success btn-sm" @click="guardarBanco_click">
            <span v-if="spinners.guardarBanco" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Guardar
        </button>
        <button v-if="editarBanco" class="btn btn-success btn-sm" @click="editarBanco_click">
            <span v-if="spinners.editarBanco" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Editar
        </button>
        <b-button size="sm" variant="secondary" @click="cancel()">
            Cerrar
        </b-button>

    </template>
</b-modal>
