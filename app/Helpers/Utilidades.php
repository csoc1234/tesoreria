<?php

namespace App\Helpers;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Utilidades
{
    public static function prueba()
    {
        echo "Helpers";
    }

    public static function setErrorWrapper($errors)
    {
        $errors = $errors->toArray();

        $newErrorData = [];

        foreach ($errors as $key => $value) :
            foreach ($value as $k => $v) :
                //  $this->customDebug($v);
                $newErrorData[$key] = $v;
            endforeach;
        endforeach;

        //  exit;

        return $newErrorData;
    }

    public static function saveFile($path, $content)
    {
        Storage::disk('local')->put($path, $content);
    }

    public static function getFile($path)
    {
       // Storage::disk('local')->put($path, "hola.txt");
     //   dd(Storage::disk('local')->get($path));
       return Storage::disk('local')->get($path);
    }


    public static function cleanString(&$requestData, $deep = false)
    {
        foreach ($requestData as $index => $value) :
            if (is_string($value)) {
                $string = $value;
                $string = str_ireplace('"', "", $string);
                $string = str_ireplace("'", "", $string);
                $string = str_ireplace("\\", "", $string);
                $string = str_ireplace("//", "", $string);
                $string = str_ireplace("`", "", $string);
                $string = str_ireplace("\n", "", $string);
                $string = str_ireplace("\'", "", $string);
                $string = trim($string);
                $requestData[$index] = $string;
            }
        endforeach;
    }

    public static function adminUnauthorizedLogout()
    {
        Session::flush();
        Auth::logout();

        $errorType = 'error';
        $errorMsg = 'No está autorizado para realizar esta acción. ';
        $errorMsg .= 'Debe consultar al administrador del sistema ';

        return redirect()->back()
            ->with($errorType, $errorMsg);
    }

    public static function getAjaxUnauthorizedMessage($msg)
    {
        $message = '';
        if ($msg == 'This action is unauthorized.') :
            $message = 'No está autorizado para realizar esta acción. ';
            $message .= 'Debe consultar al administrador del sistema. ';
        endif;

        return $message;
    }

    public static function checkRolAdmin($usuario, $rolUsuario)
    {
        // dd($arrayRoles);
        if (in_array($rolUsuario, [ROL_ADMINISTRADOR])) :
            return true;
        endif;

        return false;
    }

    public static function getUserId()
    {
        return Auth::user()->id;
    }

    public static function saveAdutoria(
        $proceso,
        $accion,
        $tabla,
        $registroId,
        $jsonData = ''
    ) {

        $array = [
            'usuario_id' => self::getUserId(),
            'accion' => $accion,
            'proceso' => $proceso,
            'fecha' => date('Y-m-d'),
            'hora' =>  date('H:i:s'),
            'tabla' => $tabla,
            'json_data' => $jsonData,
            'registro_id' => $registroId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if(empty($jsonData)):
            unset($array['json_data']);
        endif;


        DB::table('auditorias')->insert($array);
    }

    public static function havePermision($usuario, $slug)
    {
        if ($usuario->rol_id == ROL_ADMINISTRADOR) :
            return true;
        endif;


        $slug = strtoupper($slug);
       // $permisos = $usuario->permisos;

        $fileName = 'permissions/FILE_' . $usuario->rol_id . '.js';
        $permisos = json_decode(Self::getFileRolPermissions($fileName));

       // dd($permisos);

        foreach ($permisos as $index => $value) :
            if ($slug === trim($value->slug)) :
                return true;
            endif;
        endforeach;

        return false;
    }


    public static function saveFileRolPermissions($fileName, $rolPermissions)
    {
        Storage::disk('local')->put($fileName, json_encode($rolPermissions));
    }

    public static function getFileRolPermissions($fileName)
    {
        try {
            return Storage::disk('local')->get($fileName);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $ex) {
            $msg = 'Error: no fue posible leer los permisos de usuario. ';
            $msg .= 'Debe consultar al administrador del sistema';
            throw new \Exception($msg);
        }
    }


    //uso: Utilidades::msgBox($request, 'Hola', 'success');
    public static function msgBox($msg, $type = "success")
    {
        //$request = new Request();
        // $request->session();
        //  Session::put('key', 'value');
        Session::flash($type, $msg);
    }

    public static function getRandomId($len = 8)
    {
        $hex = md5("yourSaltHere" . uniqid("", true));

        $pack = pack('H*', $hex);
        $tmp =  base64_encode($pack);

        $uid = preg_replace("#(*UTF8)[^A-Za-z0-9]#", "", $tmp);

        $len = max(4, min(128, $len));

        while (strlen($uid) < $len) {
            $uid .= self::getRandomId(22);
        }

        return substr($uid, 0, $len);
    }

    public static function getRandomInt($length = 8)
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }

    public static function generateExcel(
        $tituloEncabezado,
        $tituloHoja,
        $cabeceras,
        $filaCabeceras,
        $lstValores,
        $valoresResumen = [],
        $columnasFormatMoney = [],
        $filasColores = []
    ) {
        //  ini_set('memory_limit', '512M');
        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->setActiveSheetIndex(0);
        //Titulo de la hoja
        $tituloHoja = strtoupper($tituloHoja);
        $objPHPExcel->getActiveSheet()->setTitle($tituloHoja);

        //Titulo en el archivo EXCEL
        $styleArray = array(
            'font' => array(
                'bold' => true,
                //'color' => array('rgb' => 'FF0000'),
                'size' => 12,
                //  'name' => 'Verdana'
            ),
            /* 'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ) */
        );

        /* $styleArray = array(
            'font' => array(
                'bold' => true,
            )
        );*/


        $objPHPExcel->getActiveSheet()->mergeCells('A2:J2'); //UNIR CELDA  A2:B2
        $objPHPExcel->getActiveSheet()->setCellValue("A2", "" . $tituloEncabezado);
        $objPHPExcel->getActiveSheet()->getStyle("A2")->applyFromArray($styleArray);

        //    $objPHPExcel->getActiveSheet()->getRowDimension('A2')->setRowHeight(-1);
        // $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setWrapText(true);
        // $objPHPExcel->getActiveSheet()->getStyle('A2')->applyFromArray($styleArray);
        /* $objPHPExcel->getActiveSheet()
          ->getc
          ->setAutoSize(true); */

        //$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        //$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

        // $sheet->getColumnDimension('A')->setAutoSize(true);
        //Cabeceras




        foreach ($cabeceras as $key => $value) :

            $cell = $key . $filaCabeceras;

            //$conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle($cell)->getConditionalStyles();
            // $conditionalStyles[] = $conditional1;
            // $conditionalStyles[] = $conditional2;

            // $objPHPExcel->getActiveSheet()->setHori
            $objPHPExcel->getActiveSheet()->setCellValue($cell, "" . $value);
            $objPHPExcel->getActiveSheet()->getStyle($cell, "" . $value)->applyFromArray($styleArray);
            //  $objPHPExcel->getActiveSheet()->getStyle($cell)->setConditionalStyles($conditionalStyles);

            // ->setValueExplicit($value, $objPHPExcel->PHPExcel_Cell_DataType::TYPE_STRING); //Formatear todo a String
        endforeach;

        //Valores



        // $countCell = $filaCabeceras++;
        //  $lstMunicipios = $this->Municipios->find("all", ["conditions" => $this->condiciones])->toArray();

        //VALORES
        foreach ($lstValores as $key => $value) :
            $objPHPExcel->getActiveSheet()->setCellValue($key, "" . $value);
            //  $objPHPExcel->getActiveSheet()->getStyle($key)->applyFromArray($styleArray);
            //$objPHPExcel->getActiveSheet()->getCell($key);
            $cell = $key; //setHorizontal('top')
            $objPHPExcel->getActiveSheet()
                ->getStyle($cell)
                ->getAlignment()
                ->setVertical('top')
                ->setHorizontal('left')
                ->setWrapText(true);


            //  ->setValueExplicit($value, $objPHPExcel->TYPE_STRING); //Formatear todo a String
        endforeach;



        //Ancho
        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            $objPHPExcel->getActiveSheet()
                ->getColumnDimension($col)
                ->setWidth('25')
                ->setAutoSize(false);
        }


        //FILA RESUMEN
        if (!empty($valoresResumen)) :
            foreach ($valoresResumen as $key => $value) :
                $cell = $key;
                $objPHPExcel->getActiveSheet()->setCellValue($cell, "" . $value);
                // $objPHPExcel->getActiveSheet()->getStyle($key)->getAlignment()->setHorizontal('left');
                // $objPHPExcel->setCellValue('B1', 'B1 Cell Data Here');
                $objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal('left');
                $objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleArray);
            endforeach;
        endif;


        foreach ($columnasFormatMoney as $index => $col) :
            $sheet = $objPHPExcel->getActiveSheet();
            $sheet->getStyle($col)
                ->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);
        endforeach;

        // Colores
        if (!empty($filasColores['valores'])) :
            foreach ($filasColores['valores'] as $fila => $value) :
                $objPHPExcel->getActiveSheet()->getStyle('B' . $fila . ':' . $filasColores['colparada'] . $fila)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($value);
            endforeach;
        endif;


        // $objPHPExcel->getActiveSheet()->getStyle('A:G')->getAlignment()->setHorizontal('left');

        /* foreach (range('A', 'Z') as $columnID) {
            $objPHPExcel->getColumnDimension($columnID)->setAutoSize(true);
        } */

        $writer = new Xlsx($objPHPExcel);


        // Save .xlsx file to the current directory
        $nombreArchivo = str_replace(' ', '_', $tituloEncabezado);
        $nombreArchivo = str_replace(':', '_', $tituloEncabezado);
        $nombreArchivo = str_replace('Á', 'A', $nombreArchivo);
        $nombreArchivo = str_replace('É', 'E', $nombreArchivo);
        $nombreArchivo = str_replace('Í', 'I', $nombreArchivo);
        $nombreArchivo = str_replace('Ó', 'O', $nombreArchivo);
        $nombreArchivo = str_replace('Ú', 'U', $nombreArchivo);
        $nombreArchivo = str_replace('Ñ', 'N', $nombreArchivo);

        // dd($tituloEncabezado);
        $writer->save(public_path() . '/reportes/' . $nombreArchivo . ".xlsx");
    }

    public static function setCellValue(&$objPHPExcel, $arrayValues)
    {
        foreach ($arrayValues as $cell => $value) :
            $objPHPExcel->getActiveSheet()->setCellValue($cell, "" . $value['text']);
            $objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal('left');
            $objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal('left');

            //BOLD
            $styleBold = [
                'font' => [
                    'bold' => true,
                    'size' => 12
                ],
            ];

            if (isset($value['bold']) && $value['bold'] == true) :
                $objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleBold);
            endif;

            //FORMATO MONEY
            if (isset($value['format_money']) && $value['format_money'] == true) :
                $objPHPExcel->getActiveSheet()->getStyle($cell)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);
            endif;


        endforeach;
    }

    public static function setWidth(&$objPHPExcel, $width, $autoSize = false)
    {
        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) :
            $objPHPExcel->getActiveSheet()
                ->getColumnDimension($col)
                ->setWidth($width)
                ->setAutoSize($autoSize);
        endforeach;
    }
}
