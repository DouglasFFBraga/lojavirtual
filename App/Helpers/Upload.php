<?php

namespace Helpers;

use Model\Model;
use Psr\Http\Message\UploadedFileInterface;

class Upload extends Model{

    protected $file;

    //Para Configuração/Validação:
    protected $max_upload_size;
    protected $extension_supported;
    protected $upload_dir;

   public function __construct($file)
   {
       $this->max_upload_size     = getenv("MAX_UPLOAD_SIZE") * 1048576;//Convertendo de MegaBytes para Bytes.
       $this->extension_supported = explode(",",getenv("EXTENSION_ALLOWED"));
       $this->upload_dir          = getenv("UPLOAD_DIR");

       for($i = 0; $i < count($file);$i++){
           $this->file[$i]["tmp"]         = $file[$i]->file;
           $this->file[$i]["name"]        = pathinfo($file[$i]->getClientFilename(),PATHINFO_FILENAME);
           $this->file[$i]["type"]        = pathinfo($file[$i]->getClientFilename(),PATHINFO_EXTENSION);
           $this->file[$i]["size"]        = $file[$i]->getSize();
           $this->file[$i]["error"]       = $file[$i]->getError();
           $this->file[$i]["status"]      = null;
           $this->file[$i]["message"]     = null;
       }


   }

    public function statusFile(){

       for($i = 0; $i < count($this->file); $i++) {

           switch ($this->file[$i]["error"]) {
               case 0:
                   $this->file[$i]["message"] = "Arquivo pronto para ser enviado.";
                   $this->file[$i]["status"]  = true;
                   break;
               case 1:
                   $this->file[$i]["message"] = "O arquivo enviado excede o limite definido na diretiva:upload_max_filesize";
                   $this->file[$i]["status"]  = false;
                   break;
               case 2:
                   $this->file[$i]["message"] = "O arquivo excede o limite definido em MAX_FILE_SIZE no formulário HTML.";
                   $this->file[$i]["status"]  = false;
                   break;
               case 3:
                   $this->file[$i]["message"] = "O upload do arquivo foi feito parcialmente.";
                   $this->file[$i]["status"]  = false;
                   break;
               case 4:
                   $this->file[$i]["message"] = "Nenhum arquivo foi enviado para o Formulário";
                   $this->file[$i]["status"]  = false;
                   break;
               case 5:
                   $this->file[$i]["message"] = "Pasta temporária ausênte.";
                   $this->file[$i]["status"]  = false;
                   break;
               case 6:
                   $this->file[$i]["message"] = "Falha em escrever o arquivo em disco.";
                   $this->file[$i]["status"]  = false;
                   break;
               case 7:
                   $this->file[$i]["message"] = "Uma extensão do PHP interrompeu o upload do arquivo.";
                   $this->file[$i]["status"]  = false;
                   break;
               default:
                   $this->file[$i]["message"] = "Erro não catálogado ao fazer o Upload";
                   $this->file[$i]["status"]  = false;
                   break;
           }

       }

    }
    public function checkSizeFile(){

        for($i = 0; $i < count($this->file); $i++){

            if($this->file[$i]["size"] > $this->max_upload_size){
                $this->file[$i]["message"] = "Favor informe um arquivo menor que {$_ENV["MAX_UPLOAD_SIZE"]} MegaByte";
                $this->file[$i]["status"]  = false;
            }else{
                $this->file[$i]["status"]  = true;
            }
        }

    }
    public function checkExtension(){

        for($i = 0; $i < count($this->file); $i++){

            if(!in_array($this->file[$i]["type"],$this->extension_supported)){
                $this->file[$i]["message"] = "Extensão .{$this->file[$i]["type"]} não suportada.";
                $this->file[$i]["status"]  = false;
            }else{
                $this->file[$i]["status"]  = true;
            }

        }

    }
    public function upload($nameFile,$folder_opt = null)
    {
        $ano      = date("Y");
        $mes      = date("m");
        $dia      = date("d");
        $hora     = date("H");
        $minuto   = date("i");
        $segundos = date("s");

        if (!is_null($folder_opt)) {
            $pathCustom = $this->upload_dir . DIRECTORY_SEPARATOR . $folder_opt . DIRECTORY_SEPARATOR .$ano.DIRECTORY_SEPARATOR . $mes . DIRECTORY_SEPARATOR .$dia .DIRECTORY_SEPARATOR;
            if (!file_exists($pathCustom)) {
                mkdir($pathCustom, 0777,true);
                chmod($pathCustom,0777);
                $dir = $pathCustom;
            }else{
                $dir = $pathCustom;
            }
        }else{
            $pathDefault = $this->upload_dir . DIRECTORY_SEPARATOR .$ano.DIRECTORY_SEPARATOR . $mes . DIRECTORY_SEPARATOR .$dia .DIRECTORY_SEPARATOR;
            mkdir($pathDefault,0777,true);
            chmod($pathDefault,0777);
            $dir = $pathDefault;
        }

        $this->statusFile();
        $this->checkSizeFile();
        $this->checkExtension();

        for ($i = 0; $i < count($this->file); $i++) {

            if ($this->file[$i]["status"]) {
                while(file_exists($dir.$nameFile[$i].".".$this->file[$i]["type"])){
                    $nameFile[$i] = $this->checkFileExist($nameFile[$i]);
                }

                $fileTemp = $this->file[$i]["tmp"];
                $moveTo = $dir.$nameFile[$i] . "." . $this->file[$i]["type"];

                move_uploaded_file($fileTemp, $moveTo);
                chmod($moveTo,0777);
               if(file_exists($moveTo)){
                   $this->file[$i]["message"] = "Arquivo Enviado com Sucesso!";
               }
            }
        }
    }

    public function checkFileExist($FileName){

           if(strpos($FileName,"_copia-")){
               $numberCopi = substr($FileName,strpos($FileName,"_copia-")+7);
               $nameFile = substr($FileName,0,strpos($FileName,"_copia-")) . "_copia-" . ($numberCopi + 1);
           }else{
               $nameFile = $FileName ."_copia-". 1;
           }

        return $nameFile;
    }

    public function resizeImage(){
        $filename = $this->fileTmp;
        //Tamanho desejado:
        $width = 100;
        $height = 100;

        // Obtendo o tamanho original
        list($width_orig, $height_orig) = getimagesize($filename);

        // Calculando a proporção
        $ratio_orig = $width_orig/$height_orig;

        if ($width/$height > $ratio_orig) {
            $width = $height*$ratio_orig;
        } else {
            $height = $width/$ratio_orig;
        }

        // O resize propriamente dito. Na verdade, estamos gerando uma nova imagem.
        $image_p = imagecreatetruecolor($width, $height);
        $image = imagecreatefromjpeg($filename);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        // Gerando a imagem de saída para ver no browser, qualidade 75%:
        //header('Content-Type: image/png');
        //imagejpeg($image_p, null, 75);

        // Ou, se preferir, Salvando a imagem em arquivo:
        imagejpeg($image_p, 'nova.jpg', 75);
    }
    public function formatSize($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}