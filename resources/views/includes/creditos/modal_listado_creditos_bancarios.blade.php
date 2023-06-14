<!-- LISTADO DE CREDITOS BANCARIOS -->
<b-modal id="modalCreditosBancarios" title="Listado de créditos bancarios simulados" scrollable size="lg">
    <div>

        <div class="form-row">
            <div class="col-12">
                <span v-if="spinners.cargarCreditosBancos" class="spinner-border spinner-border "
                    style="margin:0 auto; display:table;" role="status" aria-hidden="true">
                </span>

                <div v-if="!spinners.cargarCreditosBancos" class="table-responsive"
                    style="border: 1px solid #E8E8E8; background-color: #F8F8F8; height:350px; overflow: auto;">
                    <table class="table table-striped table-bordered tableFixed" style="word-wrap: break-word;">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th scope="col">Banco</th>
                                <th scope="col">Valor</th>
                                <th scope="col">Valor usado</th>

                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(item, index) in listados.creditoBancos">
                                <tr>
                                    <td>@{{ item . id }}</td>
                                    <td>@{{ item . banco . descripcion }}</td>
                                    <td>@{{ (item . valor) | currency }} </td>
                                    <td>@{{ (item . valor_prestado) | currency }}</td>

                                    <td>
                                        <button @click='modalVerDetalleCreditoBanco_click(item)' data-toggle="collapse"
                                            data-toggle="tooltip" title="Ver detalles del credito"
                                            class="btn btn-outline-secondary btn-sm">
                                            <span class="fa fa-eye"></span>
                                        </button>

                                        @can('check-authorization', PERMISO_BORRAR_DESEMBOLSO_SIMULADO)
                                            <button @click='borrarDesembolsosCreditoBanco_click(item)'
                                                data-toggle="collapse" data-toggle="tooltip" title="Borrar desembolsos"
                                                class="btn btn-outline-secondary btn-sm">
                                                <span class="fa fa-trash"></span>
                                            </button>
                                        @endcan

                                        @if(Gate::check('check-authorization', PERMISO_CREAR_DESEMBOLSO_SIMULADO))
                                        <a @click="verModalFormularioDesembolso_click(item)" data-toggle="tooltip"
                                            title="Agregar desembolso" class="btn btn-outline-secondary btn-sm">
                                            <i class="far fa-plus-square"></i>
                                        </a>
                                        @endif


                                        @can('check-authorization', PERMISO_VER_LISTADO_DESEMBOLSOS_SIMULADOS)
                                            <a @click="btnVerDesembolsos_click(item)" data-toggle="tooltip"
                                                title="Listado desembolsos" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-list-ol"></i>
                                            </a>
                                        @endcan

                                    </td>
                                </tr>

                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <template #modal-footer="{ ok, cancel, hide }">

        <b-button size="sm" variant="secondary" @click="cancel()">
            Cerrar
        </b-button>

    </template>
</b-modal>
