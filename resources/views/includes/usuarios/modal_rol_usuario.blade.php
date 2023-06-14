<!-- LISTADO DE CREDITOS BANCARIOS -->
<b-modal id="modalRolUsuario" title="Roles" scrollable>
    <div>

        @include('elements.alertMsgJsModal')

        <div class="form-row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <input placeholder="Descripción del rol" maxlength="50" @keyup='customMaxLength($event, 50)' type="text" class="form-control form-control-sm" v-model="datosFormulario.descripcion" placeholder="">
                    <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                </div>
            </div>
        </div>
    </div>

    <template #modal-footer="{ ok, cancel, hide }">
        <button @click="guardarRol_click" v-if="tipoPeticion == 'NUEVO_ROL'" class="btn btn-success btn-sm">
            <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.guardarRol"></i>
            Guardar
        </button>
        <button @click="editarDescripcionRol_click" v-else-if="tipoPeticion == 'CAMBIAR_NOMBRE_ROL'" class="btn btn-success btn-sm">
            <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.guardarCambios"></i>
            Guardar cambio
        </button>
        <button @click="guardarRol_click" v-else-if="tipoPeticion == 'CAMBIAR_NOMBRE_ROL'" class="btn btn-danger btn-sm">
            <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.borrarRol"></i>
            Borrar
        </button>
        <b-button size="sm" variant="secondary" @click="cancel()">
            Cerrar
        </b-button>

    </template>
</b-modal>
