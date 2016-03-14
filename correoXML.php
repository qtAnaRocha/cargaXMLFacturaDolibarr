<?php
require 'class/Cargador.php';
require_once 'class/Enlace.php';
require 'class/conf.php';

$user_id = $_POST['email'];

$password = $_POST['password'];

if($user_id && $password)
{
    echo "<a href='index.html'>Atr&aacutes</a></br>";
    //$array_ini = parse_ini_file("conf/cargaXML.ini");
    
    
    $numero_mensaje=0;
    $hostname = email_host;
    $inbox = imap_open ($hostname, $user_id, $password)or die('No se puede conectar');
    $emails = imap_search($inbox,'UNSEEN');
    echo "</br>Leyendo correo...</br>";
    if(count($emails)>0) {    
            $url='http://'.email_host.':'. email_puerto . email_url;     
            $bandCurl=false;
            if($bandCurl)
            {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt ($ch, CURLOPT_ENCODING, 'gzip');    
            }    
            foreach($emails as $email_number) 
            {	

                $overview = imap_fetch_overview($inbox,$email_number);
                $structure = imap_fetchstructure($inbox,$email_number);
                $partes=$structure->parts;

                if(count($partes)>1)
                {

                    if($structure->subtype=="MIXED")
                    {	
                        //echo "email_number: ".$email_number."</br>";
                        echo "</br><strong>Asunto:".$overview[0]->subject."</br>";
                        echo "Emisor:".$overview[0]->from."</strong></br>";
                        $i=0;
                        $j=0;

                        $userfilexml=array();
                        $filenameXML='';
                        $fileXML='';
                        $location=null;
                        echo "Adjuntos</br>";
                        foreach($partes as $parte){

                            $nombre = $parte->dparameters[0]->value;
                            $tipo = $parte->subtype;


                            $i++;					
                            $fileContent = imap_base64(imap_fetchbody($inbox,$email_number,$i));
                            if($nombre!="")
                            {
                                $arch=fopen("temp/".$nombre, "wb");
                                fwrite($arch,$fileContent);
                                fclose($arch);                        

                                echo "---".$nombre."</br>";
                                if($tipo=='XML')
                                {
                                    if($bandCurl)
                                        $userfilexml['userfilexml'] = '@' . ('temp/'.$nombre);
                                    else    
                                    {
                                        $filenameXML=$nombre;
                                        $fileXML='temp/'.$nombre;
                                        $type=$tipo;
                                    }
                                }
                                else
                                {
                                    if($bandCurl)
                                        $userfilexml['extrafiles['.$j.']'] = '@'. ('temp/'. $nombre);  
                                    else
                                    {
                                        $extraFiles['name'][$j]=$nombre;

                                        $extraFiles['tmp_name'][$j]='temp/'. $nombre;
                                        $extraFiles['error'][$j]=0;                                    
                                    }
                                    $j++;
                                }
                            }
                        }
                        //print_r($extraFiles);
                        //print("</br>");
                        if($bandCurl)
                        {
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $userfilexml);
                            curl_exec($ch);                    
                        }
                        else
                        {
                            $location=Cargador::cargaFacturaXML($fileXML, $filenameXML, $type, $extraFiles);
                            if($location->getBandError())
                                echo "<p>".$location->getLocation()."</p>";
                            else
                                echo "<a href=".'http://'. email_host .':'. email_puerto ."/dolibarr/".$location->getLocation().">".$location->getRef()."</a>";

                        }
                        //Eliminar los archivos temporales				
                        $dir="temp/";
                        $handle=opendir($dir);
                        while($file=readdir($handle))
                                if (is_file($dir.$file)) 
                                        unlink($dir.$file);			
                    }
                }            
            }
            echo 'Termino de leer</br>';        
            if($bandCurl)
                curl_close($ch);        
    }
    else echo 'NO tiene correos nuevos</br>';

    imap_close($inbox);
}
else
    header("Location: index.html");

