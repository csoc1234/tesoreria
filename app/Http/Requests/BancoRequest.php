<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\Utilidades;

class BancoRequest extends FormRequest
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
            'descripcion' => ['string ', 'unique:bancos,descripcion,' . $this->id, 'required', 'between:1,150']
        ];

        return $rules;
    }


    public function messages()
    {

        return [
            'descripcion.required' => 'Debe llenar este campo',
            'descripcion.between' => 'La longitud maxima es de 150 caracteres',
            'descripcion.unique' => 'Ya existe un banco con el nombre ingresado'
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
