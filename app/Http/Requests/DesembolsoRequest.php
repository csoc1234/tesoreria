<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\Utilidades;

class DesembolsoRequest extends FormRequest
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
        // $today = Carbon::now()->format('Y-m-d');

        $rules = [
            'descripcion' => ['required'],
            'credito_banco_id' => ['required', 'exists:creditos_bancos,id'],
            'fecha_inicio' => ['required', 'date'], //,'date_format:Y-m-d'
            'fecha_fin' => ['required', 'date'], //,'date_format:Y-m-d'
            'valor' => ['required', 'digits_between:4,18', 'integer'],
        ];

       /* return [
            'descripcion' => ['required'],
            'credito_banco_id' => ['required', 'exists:creditos_bancos,id'],
            'fecha_inicio' => ['required', 'date'], //,'date_format:Y-m-d'
            'fecha_fin' => ['required', 'date'], //,'date_format:Y-m-d'
            'valor' => ['required', 'digits_between:4,18', 'integer'],
        ]; */
       //   dd($this->request->get('es_independiente')); //FUNCIONA
      //  dd($this->es_independiente); //FUNCIONA
      //  dd($this->input('es_independiente')); //FUNCIONA
        //SI ES INDEPENDIENTE SE PUEDE OMITIR LA FECHA DE FIN
        if ($this->es_independiente) :
            $rules['num_dias'] = ['required', 'in:30,60,90,120,180'];
            $rules['spread'] = ['required'];
            $rules['periodo_gracia'] = ['required'];
            $rules['num_annos'] = ['required', 'integer', 'min:1'];
            $rules['tasa_ref'] = ['required'];
            $rules['tasa_ref_valor'] = ['required'];
        endif;

        return $rules;
    }

    public function messages()
    {

        return [
            'descripcion.required' => 'Debe llenar este campo',
            'credito_banco_id.required' => 'Debe llenar este campo',
            'credito_banco_id.exists' => 'El credito enviado no existe',

            'fecha_inicio.required' => 'Debe ingresar una fecha correcta',
            'fecha_inicio.date' => 'Debe ingresar una fecha correcta',

            'fecha_fin.required' => 'Debe ingresar una fecha correcta',
            'fecha_fin.date' => 'Debe ingresar una fecha correcta',

            'descripcion.between' => 'El valor no puede exceder 500 caracteres',

            'valor.required' => 'Debe llenar este campo',
            'valor.digits_between' => 'Debe ingresar un valor entero entre 4 y 18 números',
            'valor.integer' => 'Debe ingresar un valor entero',

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
        //  throw new HttpResponseException(response()->json(['errors' => $errors], 422));
    }
}
