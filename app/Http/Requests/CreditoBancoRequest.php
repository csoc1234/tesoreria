<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\Utilidades;

class CreditoBancoRequest extends FormRequest
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
            'credito_id' => ['required', 'exists:creditos,id'],
            'banco_id' => ['required', 'exists:bancos,id'],
            'valor' => ['required', 'digits_between:4,18', 'integer'],
            'fecha_inicio' => ['required', 'date'],
            // 'fecha_fin' => ['required','date'],
            'descripcion' => ['required'],
            'num_dias' => ['required', 'in:30,60,90,120,180'],
            'spread' => ['required'],
            'periodo_gracia' => ['required'],
            'num_annos' => ['required', 'integer', 'min:1'],
            'tasa_ref' => ['required'],
            'tasa_ref_valor' => ['required'],
            'notas' => ['sometimes', 'nullable', 'between:1,500'],
            // 'estado' => ['required', 'in:Activo,Bloqueado'],

        ];

        return $rules;
    }

    public function messages()
    {

        return [
            'credito_id.required' => 'Debe llenar este campo',
            'credito_id.exists' => 'No existe el credito enviado',

            'estado.required' => 'Debe llenar este campo',
            'estado.in' => 'El estado enviado es invalido',

            'banco_id.required' => 'Debe llenar este campo',
            'banco_id.exists' => 'Debe seleccionar un valor',

            'valor.required' => 'Debe llenar este campo',
            'valor.digits_between' => 'No puede exceder 18 números y el valor debe ser positivo',
            'valor.integer' => 'Debe ingresar un valor entero positivo',

            'fecha_inicio.required' => 'Debe llenar este campo',
            'fecha_inicio.date' => 'La fecha ingresada es incorrecta',

            /* 'fecha_fin.required' => 'Debe llenar este campo',
            'fecha_fin.date' => 'La fecha ingresada es incorrecta', */

            'descripcion.required' => 'Debe llenar este campo',

            'num_dias.required' => 'Debe llenar este campo',
            'num_dias.in' => 'Debe seleccionar uno de los siguientes valores: 30,60,90,120 o 180',

            'spread.required' => 'Debe llenar este campo',

            // 'spread.periodo_gracia' => 'Debe llenar este campo',

            'tasa_ref.required' => 'Debe seleccionar un valor',

            'tasa_ref_valor.required' => 'Debe llenar este campo',

            'periodo_gracia.required' => 'Debe llenar este campo',

            'num_annos.required' => 'Debe ingresar un número de años',
            'num_annos.integer' => 'Debe ingresar un valor entero',
            'num_annos.min' => 'El valor minimo permitido es 1',

            'notas.between' => 'El valor no puede exceder 500 caracteres',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = Utilidades::setErrorWrapper($validator->errors());
        $data = [
            'errors' => $errors,
            'message' => 'Error al intentar procesar el formulario, debe corregir los errores indicados'
        ];
        throw new HttpResponseException(response()->json($data, 422));
    }
}
