<?php
require_once '../core/lib/functions.lib.php';

require 'Consultas.php';
require 'LectorXML.php';
require_once 'Enlace.php';

class Cargador {
    protected $lector;
    protected $MIRFC;
    protected $fkTercero;
    protected $siren;
    protected $fkFactura;
    protected $tipoFactura;

    
    function __construct($fileXML,$MIRFC) {
        $this->MIRFC = $MIRFC;
        $this->lector = new LectorXML($fileXML,$this->MIRFC);        
        $this->fkTercero=-1;
        $this->fkFactura=-1;
    }
    function __destruct(){ 
      	unset($this->lector);
    } 
    function getRef()
    {
        return "(PROV".$this->fkFactura.")";
    }
    function getFkFactura() {
        return $this->fkFactura;
    }
    function getTipoFactura() {
        return $this->tipoFactura;
    }        
    
    function cargaTercero(){
        if($this->lector->getEsValida())
        {
            $fournisseur=1;//0-Cliente, 1-Proveedor Default todos son proveedores para poder generar tanto facturas de proveedor como devoluciones
            
            $direccion=$this->lector->getDireccion();

            $address=$this->lector->getDireccionToString();

            $fk_pays = 154; //$direccion['pais']//obtenerIDPais($dirFiscal['pais']);//obtener el codigo del pais default MEX 154
            $zip = $this->lector->getCP();
            $town = $this->lector->getEstado();
            $fk_user_creat = 1;//ID del usuario que va a crear
            $client = 1;//Tipo de 3ro 0-Ni cliente/Ni Potencial, 1-Cliente, 2-Cliente Potencial, 3-Cliente Potencial/Cliente
            $siren=$this->lector->getRfc();

            $fk_soc = Consultas::buscaTercero($siren);
            if( $fk_soc<0 )//no encontro el tercero y hay que agregarlo
            {                        
                //echo '<p>*****Insertando Tercero*****</br>';
                $nom = $this->lector->getNombre();
                //echo 'El tercero "'.$nom.'" aun no se encuentra registrado</br>';
                $fk_soc = Consultas::addTercero($nom, $fournisseur, $address, $zip, $town, $siren, $fk_user_creat, $client, $fk_pays);
                //if($fk_soc>=0)
                //    echo "Tercero '".$nom."' ID: ".$fk_soc." importado satisfactoriamente</br>";
                //echo '</p>';
            }
            $this->fkTercero=$fk_soc;
        }        
        return $this->fkTercero;
    }
    function cargaFactura()
    {
        if($this->lector->getEsValida())
        {
            $ref_ext = $this->lector->getUUID();            
            $fk_facture = Consultas::buscaFactura($ref_ext,3);//busca en todas las facturas el UUID
            $this->tipoFactura=($this->lector->getRFCEmisor() == $this->MIRFC)? 0:1;
            
            if( $fk_facture<0 && $ref_ext!='' )//la factura no existe y necesitamos agregarla
            {
                //echo '<p>*****Insertando Factura "'.$ref_ext.'"*****</p>';
		
                $siren=$this->lector->getRfc();
                $type = 0;//default-0 Tipo de la Factura 0-estandar 3-anticipo
                $datef = $this->lector->getFecha();//str_replace("T"," ",$comprobante['fecha']);//cambia el formato de Fecha al ANSI Standard SQL date format
                $fk_mode_reglement = 0;//default-0  Forma de Pago 0-default 1-Ingreso 2-Transferencia 3-Domiciliacion 4-Efectivo 6-Tarjeta 7-Cheque
                $fk_cond_reglement = 1;		//Condicion de Pago 1-A la recepcion 2-30 dias 3-30 dias fin de mes 4-60 dias 5- 60 dias a fin de mes 6-Pedido 7-A la entrega 8-50/50
                $model_pdf = "crabe";
                $note_private = $ref_ext;
                $amount = 0;
                $remise = $this->lector->getDescuento();
                $fk_statut = 0;//Id del status 0-borrador, 1-no pagada, 2=pagada, 3=abandonada
                $total = $this->lector->getSubTotal();
                $total_ttc = $this->lector->getTotal();
                $tva = $total_tva = $this->lector->getTotalImpuestosTrasladados();
                $libelle = '';
                $selloCFD =$this->lector->getCodigoQR();//generaQR($emisor['rfc'],$receptor['rfc'],$comprobante['total'],$timbreFiscalDigital['UUID']);
                $note_public=$this->lector->getNotePublic();
                               
                if($this->lector->getEsValida())
                {
                    $fk_facture = Consultas::addFactura( $this->tipoFactura, $type, $datef, $fk_mode_reglement,$fk_cond_reglement, $model_pdf, $note_private,
                                                        $note_public, $this->fkTercero, $amount,
                                                        $remise, $ref_ext, $fk_statut, $total, $total_ttc, $tva, $siren,
                                                        $libelle,$total_tva, $selloCFD );
			
                    if($fk_facture>=0)
                    {
                        //echo '<p>Factura Insertada con el ID: '.$fk_facture.'</p>';
                        /*****************INSERTA LINEAS A LA FACTURA*******************/
                        $lineas=$this->lector->getLineas();
                        foreach ($lineas as &$linea)
                        {
                            $description = $linea['descripcion']or $this->lector->setError("Error XML invalido, falta descripcion del concepto");
                            //echo '<p>Insertando Linea "'.$description.'"</p>';
                            $qty = $linea['cantidad']or $this->lector->setError("Error XML invalido, falta cantidad del concepto ".$description);                        
                            $subprice = $linea['valorUnitario']or $this->lector->setError ("Error XML invalido, falta valorUnitario del concepto ".$description);
                            $total_ht = $linea['importe']or $this->lector->setError ("Error XML invalido, falta importe del concepto ".$description);
                            $tva_tx = 16;//$iva;//$impuesto['tasa'];
                            $ref = $linea['noIdentificacion'];//atributo opcional
                            $label = '';
                            Consultas::addLinea( $this->tipoFactura, $fk_facture, $qty, $description, $subprice, $total_ht, $tva_tx, $ref, $label );					
                        }
                        $descuento=$this->lector->getDescuento();
                        if($descuento)//si existe algun descuento se agrega como una nueva linea
                        {
                            Consultas::addLinea($this->tipoFactura, $fk_facture, 1, "Descuento", -$descuento, -$descuento, 0);
                        }
                        echo "LA FACTURA SE IMPORTO SATISFACTORIAMENTE :D!!</br>";
                    }   
                }
            }
            else
                echo "La factura ya existia previamente</br>";
            $this->fkFactura=$fk_facture;
        }        
        return $this->fkFactura;
    }
    function cargaDocumentos($xmlFile, $xmlfilename, $extraFiles)
    {
        $mnsjError="";
        $objectref='(PROV'.$this->fkFactura.')';
        if ($this->tipoFactura == 0) {
            $dir = '../../documents/facture/'.$objectref.'/';
        } else {
            $dir = '../../documents/fournisseur/facture/'.get_exdir($this->fkFactura,2).$objectref.'/';
        }        
        if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
            $mnsjError .= 'Fallo al crear la carpeta ' . $dir . '</br>';
        } else {
            if (!move_uploaded_file($xmlFile, $dir . $xmlfilename)) {
                copy($xmlFile, $dir . $xmlfilename);
            }
            //EXTRAFILES
            foreach ($extraFiles['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $extraFiles['tmp_name'][$key];
                    $name = $extraFiles['name'][$key];
                    if ($name!='' && $name != '.DS_Store') {
                        if (!move_uploaded_file($tmp_name, $dir . $name)) {                            
                            copy($tmp_name, $dir . $name);
                        }                        
                    }
                }
            }            
        }
        return $mnsjError;
    }

    static function cargaFacturaXML($fileXML,$filenameXML,$type,$extraFiles)
    {
        $cargador=null;
        if ($filenameXML!='') {
                if(strstr(strtoupper($type),"XML")){            
                $MIRFC = Consultas::consultaRFC();
                if ($MIRFC) { 
                    $cargador = new Cargador($fileXML, $MIRFC); 
                    if($cargador->lector->getEsValida())
                    {
                        if ($cargador->cargaTercero() >= 0) {
                            if ($cargador->cargaFactura() >= 0) {
                                $fk_facture = $cargador->getFkFactura();
                                $tipoFactura = $cargador->getTipoFactura();
                                $ref = $cargador->getRef();

                                if ($tipoFactura == 0) {
                                    $location = 'compta/facture.php?id=' . $fk_facture;
                                } else {
                                    $location = 'fourn/facture/fiche.php?id=' . $fk_facture;
                                }
                                $mnsjError = $cargador->cargaDocumentos($fileXML, $filenameXML, $extraFiles);
                                if ($mnsjError == "") {
                                    $lRet=new Enlace($location, $ref);                            
                                    return $lRet;
                                }                        
                            }else {
                                $mnsjError = "ERROR al intentar cargar la Factura";
                            }
                        } else {
                            $mnsjError = "ERROR al intentar cargar el Tercero";
                        }
                    } 
                } else {
                    $mnsjError = "ERROR NO existe un RFC propio, requiere agregar uno en Inicio->Configuración->Empresa/Institución->Identificación reglamentaria";
                }
            } else{
                $mnsjError ="ERROR: formato de archivo invalido '".$_FILES['userfilexml']['type']."' Requiere un archivo XML ";
            }
        } else {
            $mnsjError = "ERROR No es Archivo XML ";
        }
        if($cargador!=null) 
            $mnsjError.="</br>".$cargador->lector->getError();
        $lRet=new Enlace($mnsjError, "",true);
        return $lRet;
    }
}
