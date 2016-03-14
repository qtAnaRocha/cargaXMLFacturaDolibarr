<?php
require 'class/Cargador.php';
require 'class/conf.php';

if ($_FILES['userfilexml']) {//Conexion::checaConexion())
    require_once '../core/lib/functions.lib.php';

    $fileXML = $_FILES['userfilexml']['tmp_name'];
    $filenameXML = $_FILES['userfilexml']['name'];
    $type = $_FILES['userfilexml']['type'];
    $extraFiles =$_FILES['extrafiles'];        
    $location=Cargador::cargaFacturaXML($fileXML, $filenameXML, $type, $extraFiles);
    if ($location->getBandError()) {
        echo "<a href='index.html'>Atr&aacutes</a>";
        echo $location->getLocation(); 
    } else {
        header("Location:../" . $location->getLocation());
    }    
} else {
    header("Location: index.html");
}


