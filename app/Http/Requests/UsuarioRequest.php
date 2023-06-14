<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\Utilidades;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        /*
        $comment = Comment::find($this->route('comment'));

        return $comment && $this->user()->can('update', $comment);
         */
        return true;
    }



    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        //if($this->method() == 'POST')

        // dd(Route::currentRouteAction()); //asi tambien se pueden sacar datos
        // $this->merge(['idenficacion'=>(int)$this->request->get('identificacion')]);



        $rules = [
            'tipo_vinculacion_id' => ['required', 'exists:tipo_vinculacion,id'],
            'genero_id' => ['required', 'exists:generos,id'],
            'identificacion' => ['unique:usuarios,identificacion,' . $this->id, 'required', 'integer', 'digits_between:4,15'],
            'nombres' => 'required|between:2,100',
            'apellidos' => 'required|between:2,100',
            'telefono' => 'sometimes|nullable|digits:10|integer',
            'celular' => 'required|digits:10|integer',
            'email' => 'required|email|unique:usuarios,email,' . $this->id,
            'estado' => ['required', Rule::in([0, 1])], //1 =  Active, 0 = Inactive
            'direccion' => 'nullable|between:5,500',
            'fecha_inicio_vinculacion' => 'required|date',
            'fecha_fin_vinculacion' => 'sometimes|required|date',
            'password' => 'required|between:10,15'
        ];

        if ($this->isUpdating()) :

            if (empty($this->password)) :
                unset($rules['password']);
                $this->request->remove('password');
            endif;

        else:
            if (empty($this->estado)) :
                unset($rules['estado']);
                $this->request->remove('estado');
            endif;
        endif;

        return $rules;
    }

    public function messages()
    {

        return [
            //TIPO VINCULACION
            'tipo_vinculacion_id.required' => 'Debe seleccionar un tipo de vinculación',
            'tipo_vinculacion_id.exists' => 'Debe seleccionar un tipo de vinculación',
            //GENERO
            'genero_id.required' => 'Debe seleccionar un genero',
            'genero_id.exists' => 'Debe seleccionar un genero',

            //IDENTIFICACION
            'identificacion.required' => "Debe llenar este campo",
            'identificacion.integer' => "Debe ingresar un número entero",
            'identificacion.unique' => "Ya existe un usuario registrado con la identificación enviada",
            'identificacion.digits_between' => "La longitud debe estar entre 4 y 15 números",

            //NOMBRES
            'nombres.required' => 'Debe llenar este campo',
            'nombres.between' => "La longitud debe estar entre 2 y 100 caracteres",

            //APELLIDOS
            'apellidos.required' => 'Debe llenar este campo',
            'apellidos.between' => "La longitud debe estar entre 2 y 100 caracteres",


            //TELEFONO
            'telefono.required' => 'Debe llenar este campo',
            'telefono.digits' => 'La longitud debe ser de 10 números',
            'telefono.integer' => 'Debe ingresar un valor entero',
            //'telefono.min' => 'La longitud minima son 6 números',

            //CELULAR
            'celular.required' => 'Debe llenar este campo',
            'celular.integer' => 'Debe ingresar un valor entero',
            'celular.digits' => 'La longitud debe ser de 10 números',
            // 'celular.min' => 'La longitud minima son 10 números',

            //EMAIL
            'email.required' => 'Debe llenar este campo',
            'email.email' => 'Debe ingresar un email valido',
            'email.unique' => 'Ya existe un registro con este email',

            //DIRECCIÓN
            //  'direccion.required' => 'Debe llenar este campo',
            'direccion.between' => 'La longitud debe estar entre 5 y 500 caracteres',

            //FECHA INICIO VINCULACIÓN
            'fecha_inicio_vinculacion.required' => 'Debe llenar este campo',
            'fecha_inicio_vinculacion.date' => 'Debe ingresar una fecha valida',

            //FECHA FIN VINCULACIÓN
            'fecha_fin_vinculacion.required' => 'Debe llenar este campo',
            'fecha_fin_vinculacion.date' => 'Debe ingresar una fecha valida',

            //PASSWORD
            'password.required' => 'Debe llenar este campo',
            //'password.max' => 'La longitud maxima permitida son 15 caracteres',
            'password.between' => "La longitud contraseña debe estar comprendida entre 10 y 15 caracteres",

            'estado.required' => 'Debe llenar este campo',
            'estado.in' => 'El valor seleccionado no es correcto',

        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = Utilidades::setErrorWrapper($validator->errors());
        $errors = [
            'errors' => $errors,
            'message' => 'Error al intentar procesar el formulario. Debe corregir los errores indicados'
        ];
        throw new HttpResponseException(response()->json($errors, 422));
    }

    /*  public function validationData()
    {
        $data = parent::validationData();

        return array_merge($data, [
            'nombres' => 'kjjkjkj',
        ]);
    }*/

    protected function isUpdating()
    {
        /* if($this->isMethod('put')) {
            $this->request->remove('password');
            unset($this->password);
            dd($this->toArray());
        } */
        return $this->isMethod('put') || $this->isMethod('patch');
    }

    protected function prepareForValidation()
    {


        //dd($this->request->get('identificacion'));

        //$this->request->get('identificacion')
        //  if ($this->has('identificaicon')){

        //   }



        /*$data = $this->toArray();

     data_set($data, 'identificacion', '123456');
     data_set($data, 'nombres', 'fff');
     data_set($data, 'apellidos', 'fff');

     $this->merge($data);*/



        //dd($this->request->get('identificacion'));

        //return parent::getValidatorInstance();

    }

    /* public function getValidatorInstance()
    {
        dd(['holl55']);

        parent::getValidatorInstance();
    }*/
    /*

     //this method will be called automatically after basic validation and this is laravel's built in function
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->somethingElseIsInvalid()) {
                $validator->errors()->add('field', 'Something is wrong with this field!');
            }
        });
    }
    */
    //Custom Validation Rule Using Closures https://www.esparkinfo.com/laravel-custom-validation-rules.html
    //Validación personalizada: https://www.esparkinfo.com/laravel-custom-validation-rules.html

    /*
Validar sino esta vacia

  $validator = \Validator::make($input, [
            'emailId' => 'sometimes|nullable|email',
            'mobileNo' => 'sometimes|nullable|numeric|digits:10'
             ], [
            'emailId.sometimes' => 'Please enter valid email',
            'mobileNo.sometimes' => 'Please give valid mobile number with 10 digits',
         ]);
*/
    /*
validación dinamica

public function rules()
{
if($this->method() == 'POST')
$address = 'required|string|min:10|unique:clients,address';
else
$address  = 'required|string|min:10|unique:clients,address,'.$this->id;
//put a hidden input field named id with value on your edit view and catch it here;
return [
'nameEN'   => 'required|string',
'nameHE'   => 'required|string',
'address'  => $address
];
}
 */
}
