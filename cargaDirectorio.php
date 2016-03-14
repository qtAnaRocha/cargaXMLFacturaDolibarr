<?php
require 'class/Cargador.php';
require 'class/conf.php';


/*if (count($_FILES['directorio'])>5) {//Conexion::checaConexion())
    echo "Cargando ".count($_FILES['directorio'])." archivos</br>";
    cargaDirFiles($_FILES['directorio']);
} else */
if($_FILES['directorio']) {
    echo "<a href='index.html'>Atr&aacutes</a>";
    echo "</br>Cargando directorio ".count($_FILES['directorio']['name'])." archivos...</br>";
    cargaDirFiles($_FILES['directorio']);
    echo "<a href='index.html'>Atr&aacutes</a>";
} else {
    header("Location: index.html");
}

function cargaDirFiles($extraFiles)
{    
    foreach ($extraFiles['error'] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            
            $tmp_name = $extraFiles['tmp_name'][$key];
            $name = $extraFiles['name'][$key];
            $tipo = $extraFiles['type'][$key];
            

            if ($name!='' && $name != '.DS_Store' && strpos(strtoupper($tipo),"XML")>0) {
                echo "</br><strong> ".$name."(".$tipo.")</strong></br>";
                $location=Cargador::cargaFacturaXML($tmp_name, $name, "XML");
                $contTotal++;
                
                 if($location->getBandError())
                     echo "<p>".$location->getLocation()."</p>";
                 else
                 {
                     $cont++;
                     echo "<a href=".'http://'.email_host.':'. email_puerto ."/dolibarr/".$location->getLocation().">".$location->getRef()."</a>";
                 }
                 echo "</br>";
                //if (!move_uploaded_file($tmp_name, $dir . $name)) {                            
                //    copy($tmp_name, $dir . $name);
                //}                        
            } 
        }
    } 
    echo "</br>Termino de leer el directorio</br>";
    echo "<p>Se subieron ".$cont." de ".$contTotal." archivos con formato XML</p>";
    /*
    foreach($files as $file)
    {
        $filename=$archivo=$file['name'];
        echo "<br />".$archivo;
        if(strpos(strtoupper($archivo),".XML"))
        {            
            // Mostramos el archivo
            echo "<br />";
            print_r($extraFiles);
            echo "<br />";

            //leerSubirXML($filename);

             $location=Cargador::cargaFacturaXML($filename, $archivo, "XML", $extraFiles);
             if($location->getBandError())
                 echo "<p>".$location->getLocation()."</p>";
             else
                 echo "<a href=".'http://'.sql_host.':'.email_puerto."/dolibarr/".$location->getLocation().">".$location->getRef()."</a>";
             $extraFiles=array();
        }
        else
        {
             $extraFiles['name'][$j] = $archivo;
             $extraFiles['tmp_name'][$j] = $filename;
             $extraFiles['error'][$j]=0;  
             $j++;
        }
    }
    */
     
}
/**
* Funcion recursiva que va recorriendo los archivos y carpetas buscando archivos XML y los va evaluando
* @param	string		$path		Directorio donde buscar los archivos (tiene que terminar con diagonal/)
*/
function cargaDirectorio($path)
{	
    echo $path."</br>";
    if(function_exists("dir"))
    {
        // asignamos a $directorio el objeto dir creado con la ruta
        $directorio = dir($path);
        print_r($directorio);
        echo "</br>";

        // recorremos todos los archivos y carpetas
        while ($archivo = $directorio->read())
        {
             if($archivo!="." && $archivo!="..")
             {
                      if(is_dir($path.$archivo))
                      {
                         // Mostramos el nombre de la carpeta y los archivo contenidos en la misma
                         echo "<p><strong>CARPETA: " . $archivo . "</strong></p>";
                         $extraFiles=array();
                         // llamamos nuevamente a la funciÃ³n con la nueva carpeta
                         cargaDirectorio($path.$archivo."/");
                      }
                      else
                      {	
                         echo $archivo;
                         echo "</br>";
                         $filename=$path.$archivo;
                         if(strpos(strtoupper($archivo),".XML"))
                         {                                
                             $location=Cargador::cargaFacturaXML($filename, $archivo, "XML", $extraFiles);
                             echo "</br>";
                             if($location->getBandError())
                                 echo "<p>".$location->getLocation()."</p>";
                             else
                                 echo "<a href=".'http://'.email_host.':'. email_puerto ."/dolibarr/".$location->getLocation().">".$location->getRef()."</a>";
                         }
                         else
                         {
                              $extraFiles['name'][$j] = $archivo;
                              $extraFiles['tmp_name'][$j] = $filename;
                              $extraFiles['error'][$j]=0;  
                              $j++;
                         }
                      }
             }
        }
        $directorio -> close();    
    } else{
        echo "No existe la funcion dir</br>";
    }
}

