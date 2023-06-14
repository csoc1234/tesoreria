<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\Utilidades;

class CreditoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'num_ordenanza' => ['required'],
            // 'linea' => ['required'],
            'descripcion' => ['sometimes', 'nullable', 'between:1,500'],
            'valor' => ['required', 'digits_between:4,18', 'integer'],
            'fecha' => ['required'],
            'estado' => ['required', 'in:Activo,Bloqueado'],

        ];

        return $rules;
    }


    public function messages()
    {

        return [
            'num_ordenanza.required' => 'Debe llenar este campo',

            'estado.required' => 'Debe llenar este campo',
            'estado.in' => 'El estado enviado es invalido',
            //  'linea.required' => 'Debe llenar este campo',

            // 'descripcion.valor' => 'Debe llenar este campo',
            'descripcion.between' => 'El valor ingresado excede la longitud permitida de 500 caracteres',

            'valor.required' => 'Debe llenar este campo',
            'valor.digits_between' => 'Debe ingresar un valor entero',
            'valor.integer' => 'Debe ingresar un valor entero',


            'fecha.required' => 'Debe llenar este campo',


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
}
