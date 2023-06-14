<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Utilidades;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\RolPermiso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

//use App\Models\User;

class UsuariosController extends Controller
{
    private $pageLimit = 10;
    /**
     * Registrar usuarios.
     *
     * @param  string  $tipo
     * @return \Illuminate\View\View
     */
    public function add()
    {
        //  dd(Auth::user()->toArray());
        Gate::authorize('check-authorization', PERMISO_REGISTRAR_USUARIOS);
        return view('usuarios.form_usuarios', ['tipo' => 'ADD']);
    }

    public function edit($id)
    {
        //  $tipo = 'EDIT';
        Gate::authorize('check-authorization', PERMISO_EDITAR_USUARIOS);

        try {
            $usuario =  $this->datosUsuario($id);

           // dd($usuario->toArray());

            if (empty($usuario)) :
                throw new \Exception('Error: El usuario no existe');
            endif;
        } catch (\Exception $ex) {
            return redirect()->back()->with('error', '' . $ex->getMessage());
        }

        return view('usuarios.form_usuarios', ['tipo' => 'EDIT', 'usuario' => $usuario]);
    }

    public function perfilUsuario($id)
    {
        try {
            $usuario =  $this->datosUsuario($id);
            if (empty($usuario)) :
                throw new \Exception('Error: El usuario no existe');
            endif;
        } catch (\Exception $ex) {
            return redirect()->back()->with('error', '' . $ex->getMessage());
        }

        return view('usuarios.form_perfil', ['tipo' => 'EDIT', 'usuario' => $usuario]);
    }

    private function datosUsuario($id)
    {
        $andConditions = [
            ['id','=', $id ]
        ];

        $usuario = Usuario::obtenerUsuarioPorCondiciones($andConditions);

       // dd($usuario->get()->toArray());

        if(!empty($usuario)):
            $fechaInicioVinculacion = date('Y-m-d H:i:s', strtotime($usuario->fecha_inicio_vinculacion));
            $fechaFinVinculacion = date('Y-m-d H:i:s', strtotime($usuario->fecha_fin_vinculacion));
            $usuario['fecha_inicio_vinculacion'] = $fechaInicioVinculacion;
            $usuario['fecha_fin_vinculacion'] = $fechaFinVinculacion;
        endif;

        return $usuario;
    }
    public function index(Request $request)
    {
        Gate::authorize('check-authorization', PERMISO_LISTAR_USUARIOS);

        $usuarios = null;
        $fields = [
            'id',
            'identificacion',
            'nombres',
            'apellidos',
            'telefono',
            'celular',
            'email',
            'fecha_inicio_vinculacion',
            'fecha_fin_vinculacion',
            'created_at',
            'updated_at',
            'estado'
        ];

        $filtro = null;

        if ($request->isMethod('post')) {
            $filtro = $request->input('txtFiltro');
        }

        $usuarios = Usuario::obtenerListadoUsuarios($fields, $filtro, $this->pageLimit);

        $usuarios =  $usuarios->paginate($this->pageLimit);

        $filasDetalle = $this->obtenerFilasDetalle($usuarios->toArray()['data']);

        //   dd($usuarios->toArray());

        return view(
            'usuarios.listado_usuarios',
            [
                'usuarios' => $usuarios,
                'filasDetalle' => $filasDetalle
            ]
        );
    }


    private function obtenerFilasDetalle($listado)
    {
        $datos = [];
        foreach ($listado as $index => $fila) :
            // dd($fila);
            $datos[$index]['Celular'] = $fila['celular'];
            $datos[$index]['Email'] = $fila['email'];
            $datos[$index]['Fecha registro'] = date('d-m-Y', strtotime($fila['created_at']));
            $datos[$index]['Fecha inicio actividad'] = date('d-m-Y', strtotime($fila['fecha_inicio_vinculacion']));
            $datos[$index]['Fecha fin actividad'] = date('d-m-Y', strtotime($fila['fecha_fin_vinculacion']));
            //  $datos[$index]['Fecha actualización'] = date('d-m-Y', strtotime($fila['updated_at']));
            if ($fila['estado'] == 1) :
                $datos[$index]['Estado'] = 'Activo';
            else :
                $datos[$index]['Estado'] = 'Bloqueado';
            endif;
        endforeach;

        return $datos;
    }

    public function login()
    {
        return view('usuarios.form_login', []);
    }
    public function ajaxLogin(Request $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Inicio de sesión exitoso';

        $requestData = $request->all();

        //REGLAS DE VALIDACIÓN
        $rules = [
            'email'    => 'required|email',
            'password' => 'required|between:0,15'
        ];

        //MENSAJES DE LAS REGLAS DE VALIDACIÓN
        $messages = [
            'email.required' => 'Debe llenar este campo', //The :attribute field is required.
            'email.email' => 'Debe ingresar un email valido',
            'password.required' => 'Debe llenar este campo',
            'password.between' => 'La longitud maxima permitida son 15 caracteres'
        ];

        //APLICAR VALIDACIÓN
        $validator = Validator::make(
            $requestData,
            $rules,
            $messages
        );

        try {
            //VERIFICAR SI EXISTEN ERRORES EN LA VALIDACIÓN
            if ($validator->fails()) :
                // dd($validator->errors());
                $errors = Utilidades::setErrorWrapper($validator->errors());
                $msg = 'Error al intentar validar las credenciales';
                //  $msg .= 'Debe corregir los errores indicados';
                $data['errors'] = $errors;
                throw new \Exception($msg);
            endif;

            $condiciones = [
                ['email', '=', $requestData['email']],
                ['estado', '=', 1] //1=ACTIVO

                // ['password', '=', Hash::make($requestData['password'])]
            ];

            $user = Usuario::where($condiciones)->with('rol')
                ->first();

            //  dd($user->toArray());

            if (!empty($user)) :

                if (!Hash::check($requestData['password'], $user->password)) :
                    throw new \Exception('Usuario o contraseña incorrectos');
                endif;

                if (!Utilidades::checkRolAdmin(null, $user->rol_id)) :
                    $this->checkIfUserIsActivate($user->fecha_fin_vinculacion);
                endif;

                /*  GUARDAR PERMISOS DEL USUARIO */
                // $fileName = 'permissions/FILE_' . $user->rol_id . '.js';
                //   Utilidades::saveFileRolPermissions($fileName, $user->permisos);

                Auth::login($user);

                /* AUDITORIA */
                Utilidades::saveAdutoria('USUARIOS', 'INICIAR_SESION', 'usuarios', $user->id);

            else :
                throw new \Exception('Usuario o contraseña incorrectos');
            endif;

            // $data['success'] = false;
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }
        // Auth::loginUsingId($user->id, TRUE);

        //Auth::login($user);

        //GATES RESPONSE JSON: https://www.youtube.com/watch?v=E11JQYiF-7g
        //https://www.webtrickshome.com/forum/how-to-add-custom-authentication-middleware-in-laravel
        //FACIL: https://www.youtube.com/watch?v=Qgq6Uq6JO14
        //ROLES: https://www.youtube.com/watch?v=X-FmK4few8A
        //GATES FACIL: https://www.youtube.com/watch?v=BNY7lGv8JgQ
        //EXPLICACIÓN FACIL DE POLICIES https://www.youtube.com/watch?v=6nCbiljC07U
        return response()->json($data, $statusCode);
    }

    private function checkIfUserIsActivate($unactivation_date)
    {
        $fechaFinVinculacion = date('Y-m-d', strtotime($unactivation_date));

        $fechaActualStrToTime = strtotime(date('Y-m-d'));
        $fechaFinVinculacionStrToTime = strtotime($fechaFinVinculacion);

        if ($fechaActualStrToTime > $fechaFinVinculacionStrToTime) :
            $msg = 'Su fecha de usuario activo ya se cumplio: ' . $fechaFinVinculacion;
            $msg .= '. Debe ponerse en contacto con el administrador del sistema';
            throw new \Exception($msg);
        endif;
    }

    public function listadoRoles()
    {
        return view('usuarios.listado_roles', compact([]));
    }

    //************************************************ PETICIONES AJAX ************************************************

    //GUARDAR USUARIOS
    public function ajaxGuardarUsuario(UsuarioRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_REGISTRAR_USUARIOS);

            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            $requestData['password'] = Hash::make($requestData['password']);

            $this->formatearFechas(
                $requestData['fecha_inicio_vinculacion'],
                $requestData['fecha_fin_vinculacion']
            );

            $requestData['created_at'] = date('Y-m-d H:i:s');
            $requestData['updated_at'] = date('Y-m-d H:i:s');
            $requestData['estado'] = 1;

            $usuario = Usuario::create($requestData);

            if (!$usuario) :
                throw new \Exception('Error al intentar crear el registro');
            endif;

            //AUDITORIA
            Utilidades::saveAdutoria('USUARIOS', 'REGISTRAR_USUARIO', 'usuarios', $usuario->d);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 401;
            $data['success'] = false;

            $message = Utilidades::getAjaxUnauthorizedMessage($ex->getMessage());

            if (empty($message)) :
                $data['message'] = $ex->getMessage();
            else :
                $data['message'] = $message;
            endif;
        }

        return response()->json($data, $statusCode);
    }

    private function formatearFechas(&$fechaInicio, &$fechaFin)
    {
        $strTimeFechaInicio = strtotime($fechaInicio);
        $strTimeFechaFin = strtotime($fechaFin);

        if ($strTimeFechaFin <= $strTimeFechaInicio) :
            throw new \Exception('La fecha final debe ser mayor que la fecha inicial');
        endif;

        $fechaInicio = date('Y-m-d H:i:s', $strTimeFechaInicio);
        $fechaFin = date('Y-m-d H:i:s', $strTimeFechaFin);
    }

    //CARGAR ROLES
    public function ajaxCargarRoles()
    {
        $data = [];
        $data['success'] = false;
        $statusCode = 200;

        try {
            $listado = [];

            $listado = DB::table('roles')
                ->where('visible', 1)
                ->get();


            $data['listado'] = $listado;
            $data['success'] = true;
            $data['message'] = 'Registro(s) cargado(s) correctamente';
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['message'] = 'Error: ' . $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    //EDITAR USUARIOS
    public function ajaxEditarUsuario(UsuarioRequest $request, $id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro actualizado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_EDITAR_USUARIOS);

            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            if (empty($requestData['password'])) :
                unset($requestData['password']);
            else :
                $requestData['password'] = Hash::make($requestData['password']);
            endif;

            $this->formatearFechas(
                $requestData['fecha_inicio_vinculacion'],
                $requestData['fecha_fin_vinculacion']
            );

            $requestData['updated_at'] = date('Y-m-d H:i:s');
            Usuario::whereId($id)->update($requestData);

            //AUDITORIA
            Utilidades::saveAdutoria('USUARIOS', 'EDITAR_USUARIO', 'usuarios', $id);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 401;
            $data['success'] = false;

            $message = Utilidades::getAjaxUnauthorizedMessage($ex->getMessage());

            if (empty($message)) :
                $data['message'] = $ex->getMessage();
            else :
                $data['message'] = $message;
            endif;
        }

        return response()->json($data, $statusCode);
    }

    //BORRRAR USUARIOS
    public function ajaxBorrarUsuario(Request $request, $id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro borrado correctamente';

        DB::beginTransaction();

        try {

            Gate::authorize('check-authorization', PERMISO_BORRAR_USUARIO);

            $usuario = Usuario::obtenerUsuarioPorCondiciones([
                ['id','=', $id]
            ]);

            $userId = Utilidades::getUserId();

            if($usuario->id == $userId):
                $msg = 'Error: usted no puede auto-borrarse. ';
                throw new \Exception($msg);
            endif;

            $jsonData = json_encode($usuario->toArray());

            if(!$usuario->delete()):
                $msg = 'Error: no fue posible borrar el usuario. ';
                $msg = 'Debe consultar al administrador del sistema';
                throw new \Exception($msg);
            endif;

            //AUDITORIA
            Utilidades::saveAdutoria(
                'USUARIOS',
                'BORRAR_USUARIO',
                'usuarios',
                $id,
                $jsonData
            );

            DB::commit();

        } catch (\PDOException $ex) {

            DB::rollback();

            $errorInfo = '';

            if (is_object($ex)) :

                $errorInfo = $ex->errorInfo;
                $errorCode = $errorInfo[1];
                $errorMsg = $errorInfo[2];

                if ($errorCode === 1451) :
                    $msg = 'Error: no puede borrar el usuario, ';
                    $msg .= 'está relacionado con otros registros';
                    $data['message'] = $msg;

                else :
                    $data['message'] = $errorMsg;
                endif;

            else :
                $data['message'] = $ex->getMessage();
            endif;

            $data['success'] = false;
            //  http_response_code(400);
            $statusCode = 401;
        } catch (\Exception $ex) {

            DB::rollback();

            $statusCode = 401;
            $data['success'] = false;

            $message = Utilidades::getAjaxUnauthorizedMessage($ex->getMessage());

            if (empty($message)) :
                $data['message'] = $ex->getMessage();
            else :
                $data['message'] = $message;
            endif;
        }

        return response()->json($data, $statusCode);
    }

    //EDITAR PERFIL
    public function ajaxEditarPerfil(UsuarioRequest $request, $id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro actualizado correctamente';
        DB::beginTransaction();
        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            if (empty($requestData['password'])) :
                unset($requestData['password']);
            else :
                $requestData['password'] = Hash::make($requestData['password']);
            endif;

            /* $this->formatearFechas(
                $requestData['fecha_inicio_vinculacion'],
                $requestData['fecha_fin_vinculacion']
            ); */

            //VALORES QUE NO PUEDE MODIFICAR
            unset($requestData['tipo_vinculacion_id']);
            unset($requestData['fecha_inicio_vinculacion']);
            unset($requestData['fecha_fin_vinculacion']);
            unset($requestData['identificacion']);
            unset($requestData['rol_id']);
            // unset($requestData['tipo_vinculacion_id']);


            $requestData['updated_at'] = date('Y-m-d H:i:s');

            Usuario::where('id', $id)->update($requestData);

            //AUDITORIA
            Utilidades::saveAdutoria('USUARIOS', 'ACTUALIZAR_PERFIL', 'usuarios', $id);

            /* dd($result);

            if (!$result) :
                throw new \Exception('Error al intentar actualizar el registro');
            endif; */

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/');
    }

    public function ajaxObtenerRolPermisos($idRol = 0)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registros cargados correctamente';

        try {
            if (!is_numeric($idRol)) :
                throw new \Exception('Error: debe enviar un ID valdio');
            endif;

            $rol = Rol::find($idRol);
            if (empty($rol)) :
                throw new \Exception('Error: no existe el rol seleccionado');
            endif;

            $permisos = Permiso::get()->toArray();
            $rolPermisos = Rol::find($rol['id'])->permisos()->get();

            foreach ($permisos as $index => $value) :
                if ($this->verificarPermisoAsignado($rolPermisos, $value)) :
                    $permisos[$index]['activado'] = true;
                else :
                    $permisos[$index]['activado'] = false;
                endif;
            endforeach;

            $data['auth'] = Auth::check();
            $data['listado'] = $permisos;
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function verificarPermisoAsignado($rolPermisos, $permiso)
    {
        // const rolPermisos = this.listados.permisos;
        $index = 0;
        $to = count($rolPermisos);
        for ($index; $index < $to; $index++) {
            $item = $rolPermisos[$index];

            // dd($item->toArray());
            if ($item['id'] === $permiso['id']) {
                return true;
            }
        }
        /* Object.keys(rolPermisos).forEach(index => {
             const item = lstPermisos[index];

         });*/
    }


    public function ajaxActualizarRolPermisos(Request $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Permisos actualizados correctamente';

        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            if (empty($requestData['permisos'])) :
                throw new \Exception('Error: debe enviar los permisos que desea actualizar');
            endif;

            if (empty($requestData['rol_id']) || !is_numeric($requestData['rol_id'])) :
                throw new \Exception('Error: debe enviar el ID del rol que desea actualizar');
            endif;

            $permisos = $requestData['permisos'];
            $rolId = $requestData['rol_id'];


            $arrayRolPermisos = [];
             /* ARRAY DE PERMISOS ACTUALES*/
            $idsPermisos = [];
            foreach ($permisos as $index => $permiso) :
                if ($permiso['activado'] === true) :
                    $arrayRolPermisos[] = [
                        'id' => Utilidades::getRandomInt(),
                        'permiso_id' => $permiso['id'],
                        'rol_id' =>  $rolId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                endif;
                $idsPermisos[] =  $permiso['id'];
            endforeach;

            //BORRAR LOS PERMISOS ACTUALES
            RolPermiso::whereIn('permiso_id', $idsPermisos)
                ->where('rol_id', $rolId)
                ->delete();

            if (!RolPermiso::insert($arrayRolPermisos)) :
                throw new \Exception('Error: no fue posible actualizar los permisos');
            endif;


            /* ACTUALIZAR EL ARCHIVO JSON DE LOS PERMISOS */
            $fileName = 'permissions/FILE_' . $rolId . '.js';
            Utilidades::saveFileRolPermissions($fileName, $this->getPermisosByRolId($rolId));

            /* ACTUALIZAR EL CAMPO permisos_json de la tabla permisos */
            $permisosJson['permisos_json'] = json_encode($permisos);
            Rol::where('id', $rolId)->first()->update($permisosJson);



            //DB::rollBack();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function getPermisosByRolId($rolId)
    {
        $permissions = Rol::whereId(intval($rolId))
            //->select('permisos.slug')
            ->with('permisos')
            ->first()->toArray();

        return  $permissions['permisos'];
    }

    public function ajaxGuardarRol(Request $request)
    {
        $statusCode = 201;
        $data['success'] = true;
        $data['message'] = 'Rol creado correctamente';


        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            if (empty($requestData['descripcion'])) :
                throw new \Exception('Error: debe enviar el nombre del rol');
            endif;

            if (strlen($requestData['descripcion']) > 50) :
                $msg = 'Error: el nombre del rol excede la longitud ';
                $msg .= 'permitida (50 caracteres)';
                throw new \Exception($msg);
            endif;

            $descripcion = trim($requestData['descripcion']);

            $rol = Rol::where('descripcion', $requestData['descripcion'])
                ->first();

            if (!empty($rol) && $rol->descripcion == $descripcion) :
                $msg = 'Ya existe un rol llamado: ' . $descripcion;
                throw new \Exception($msg);
            endif;

            $entity = [
                'descripcion' => $descripcion,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = Rol::insertGetId($entity);

            /* CREAR ARCHIVO DEL ROL */
            $fileName = 'permissions/FILE_' . $result. '.js';
            Utilidades::saveFileRolPermissions($fileName, []);

            $entity['id'] = $result;

            if (empty($result)) :
                $msg = 'Ocurrió un error al intentar guardar el Rol, ';
                $msg .= 'debe consultar con el administrador del sistema';
                throw new \Exception($msg);
            endif;

            $data['entity'] = $entity;

            /* AUDITORIA */
            Utilidades::saveAdutoria('ROLES', 'REGISTRAR_ROL', 'roles', $entity['id']);

            //DB::rollBack();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxEditarNombreRol(Request $request, $rolId = 0)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Rol actualizado correctamente';


        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            //VALIDACIÓN DE LOS DATOS ENVIADOS
            if (empty($requestData['descripcion'])) :
                throw new \Exception('Error: debe enviar el nombre del rol');
            endif;

            if (empty($requestData['rol_id'])) :
                throw new \Exception('Error: debe enviar el ID del rol');
            endif;

            if (strlen($requestData['descripcion']) > 50) :
                $msg = 'Error: el nombre del rol excede la longitud ';
                $msg .= 'permitida (50 caracteres)';
                throw new \Exception($msg);
            endif;


            $rolId = intval($requestData['rol_id']);
            $descripcion = trim($requestData['descripcion']);

            /* ENTIDAD CON LOS DATOS A EDITAR */
            $entity = [
                'descripcion' => $requestData['descripcion'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            /* VERIFICACIÓN DEL ROL QUE SE INTENTA EDITAR EXISTA */
            $rol = Rol::whereId($rolId)->first();



            if (empty($rol)) :
                $msg = 'Error: el rol que intenta editar no existe ';
                throw new \Exception($msg);
            endif;

            /* VERIFICACIÓN DE QUE EL NUEVO NOMBRE NO LO TENGA OTRO ROL */
            $rolDiferente = Rol::where([
                ['descripcion', '=', $descripcion],
                ['id', '!=', $rolId]
            ])
                ->first();


            if (!empty($rolDiferente)) :
                $msg = 'Error: ya existe un rol con el nombre ingresado';
                throw new \Exception($msg);
            endif;

            // dd($rol);

            if ($descripcion !=  $rol->descripcion) :
                $result = Rol::whereId($rolId)
                    ->update($entity);

                if (empty($result)) :
                    $msg = 'Ocurrió un error al intentar editar el Rol, ';
                    $msg .= 'debe consultar con el administrador del sistema';
                    throw new \Exception($msg);
                endif;
            endif;

            $entity['id'] = $rolId;
            $data['entity'] = $entity;

            //DB::rollBack();
            //AUDITORIA
            Utilidades::saveAdutoria('ROLES', 'CAMBIAR_NOMBRE_ROL', 'roles', $rolId);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxBorrarRol(Request $request, $rolId = 0)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Rol borrado correctamente';


        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            //  dd($rolId);

            //VALIDACIÓN DE LOS DATOS ENVIADOS
            if (empty($rolId)) :
                throw new \Exception('Error: debe enviar el ID del rol');
            endif;

            $rolId = intval($rolId);

            /* VERIFICACIÓN DEL ROL QUE SE INTENTA EDITAR EXISTA */
            $result = Rol::whereId($rolId)->delete();

            if (empty($result)) :
                $msg = 'Error: no fue posible borrar el rol. ';
                $msg .= 'Debe consultar al administrador del sistema';
                throw new \Exception($msg);
            endif;

            $data['rol_id'] = $rolId;

            //AUDITORIA
            Utilidades::saveAdutoria('ROLES', 'BORRAR_ROL', 'roles', $rolId);

            DB::commit();
        } catch (\PDOException $ex) {
            $errorInfo = '';

            if (is_object($ex)) :

                $errorInfo = $ex->errorInfo;
                $errorCode = $errorInfo[1];
                $errorMsg = $errorInfo[2];

                if ($errorCode === 1451) :
                    $msg = 'Error: no puede borrar el rol, ';
                    $msg .= 'tiene usuarios asociados a el';
                    $data['message'] = $msg;

                else :
                    $data['message'] = $errorMsg;
                endif;

            else :
                $data['message'] = $ex->getMessage();
            endif;

            $data['success'] = false;
            //  http_response_code(400);
            $statusCode = 401;
        } catch (\Exception $ex) {
            DB::rollBack();
            $statusCode = 401;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }





    /* public function update(Request $request, $id)
{
$request->validate([
'first_name'=>'required',
'last_name'=>'required',
'email'=>'required'
]);

$contact = Contact::find($id);
$contact->first_name =  $request->get('first_name');
$contact->last_name = $request->get('last_name');
$contact->email = $request->get('email');
$contact->job_title = $request->get('job_title');
$contact->city = $request->get('city');
$contact->country = $request->get('country');
$contact->save();

return redirect('/contacts')->with('success', 'Contact updated!');
}*/
}
