@if(Gate::check('check-authorization', PERMISO_VER_CREDITOS_SAP))
<div class="dash-nav-dropdown">
    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle">
        <i class="fas fa-funnel-dollar"></i> Serv. de la deuda&nbsp;</a>
    <div class="dash-nav-dropdown-menu">
        <a href="{{ route('buscar_linea_sap') }}" class="dash-nav-dropdown-item">Buscar línea en SAP</a>
        <!-- <a href="{{ route('buscar_linea_sap') }}" class="dash-nav-dropdown-item">Listado</a> -->
    </div>
</div>
@endif
@if(
Gate::check('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO) ||
Gate::check('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO) ||
Gate::check('check-authorization', PERMISO_LISTAR_CREDITOS_SIMULADOS)
)
<div class="dash-nav-dropdown">
    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle">
        <i class="fas fa-chart-line"></i> Simulador </a>
    <div class="dash-nav-dropdown-menu">
        @if(Gate::check('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO))
        <a href="{{ URL::to('creditos/add') }}" class="dash-nav-dropdown-item">Crear crédito</a>
        @endif

        @if(Gate::check('check-authorization', PERMISO_LISTAR_CREDITOS_SIMULADOS))
        <a href="{{ route('creditos_simulados') }}" class="dash-nav-dropdown-item">Listado créditos</a>
        @endif

    </div>
</div>
@endif

@if(
Gate::check('check-authorization', PERMISO_REGISTRAR_USUARIOS) ||
Gate::check('check-authorization', PERMISO_EDITAR_USUARIOS) ||
Gate::check('check-authorization', PERMISO_ACTIVAR_DESACTIVAR_USUARIOS) ||
Gate::check('check-authorization', PERMISO_LISTAR_USUARIOS)
)
<div class="dash-nav-dropdown">
    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle">
        <i class="fas fa-users"></i> Usuarios </a>
    <div class="dash-nav-dropdown-menu">

        @if(Gate::check('check-authorization', PERMISO_REGISTRAR_USUARIOS))
        <a href="{{ URL::to('usuarios/add') }}" class="dash-nav-dropdown-item">Registrar</a>
        @endif

        @if(
        Gate::check('check-authorization', PERMISO_LISTAR_USUARIOS) ||
        Gate::check('check-authorization', PERMISO_EDITAR_USUARIOS)
        )
        <a href="{{ URL::to('usuarios/index') }}" class="dash-nav-dropdown-item">Listado</a>
        @endif

        @if(Auth::user()->rol_id == ROL_ADMINISTRADOR)
        <a href="{{ route('roles') }}" class="dash-nav-dropdown-item">Roles</a>
        @endif
    </div>
</div>
@endif
<!-- <div class="dash-nav-dropdown">
    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle">
        <i class="fas fa-download"></i> Reportes </a>
    <div class="dash-nav-dropdown-menu">
        <a href="#" class="dash-nav-dropdown-item">Listado reportes</a>

    </div>
</div> -->


@if(
Gate::check('check-authorization', PERMISO_PARAMETRIZAR_DATOS)
)
<div class="dash-nav-dropdown">
    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle">
        <i class="fas fa-cogs"></i> Parametrización </a>
    <div class="dash-nav-dropdown-menu">
        <a href="{{ route('bancos_index') }}" class="dash-nav-dropdown-item">Bancos</a>
        <a href="{{ route('tipo_vinculaciones_index') }}" class="dash-nav-dropdown-item">Tipos de vinculación</a>
    </div>
</div>
@endif
