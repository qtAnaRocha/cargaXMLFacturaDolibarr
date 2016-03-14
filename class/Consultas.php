<?php
include 'Conexion.php';
class Consultas {
    /**
    *	Busca el RFC de la empresa propia registrado en la configuracion, en al tabla de constantes
    *	
    *	@return string RFC de la empresa propia
    */
    static function consultaRFC()
    {        
        if(Conexion::checaConexion())
        {    
            $sql = "SELECT `value` FROM `llx_const` WHERE `name`='MAIN_INFO_SIREN'";

            $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/>$sql");
 
            if($reslq)
            {
                if (mysql_num_rows($reslq) == 0)
                {
                    return -1;
                }
                else
                {
                    $arrayRes=mysql_fetch_row($reslq);
                    return $arrayRes[0];
                }
            }   
        }       
        return -1;
    }
    /**
   *	Actualiza DB
   */
   static function actualiza()
   {
        if(Conexion::checaConexion())
        { 
            $sql = 'COMMIT ';
            $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");
            return $reslq;
        }
   }
/*********************************************TERCEROS*********************************************/
    /**
    *	Busca un tercero que concuerde con el RFC
    *
    *	@param	string	$rfc	RFC del Tercero buscado
    *	@return	int	ID del tercero buscado correspondiete al campo de las tablas llx_facture.fk_soc, llx_societe.rowid
    */
    static function buscaTercero($rfc)
    {
        if(Conexion::checaConexion())
        {  
            $sql = 'SELECT rowid, nom, siren FROM llx_societe WHERE siren = "' . $rfc . '"';

            $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");
            if($reslq)
            {
                if (mysql_num_rows($reslq) == 0)
                {
                    return -1;
                }
                else
                {
                    $arrayRes=mysql_fetch_row($reslq);
                    return $arrayRes[0];
                }
            }
        }
        return -1;
    }
    /**
    *	Inserta Nuevo Tercero
    *
    *	@param		string			$nom			Nombre del Tercero
    *	@param		int				$fournisseur	Proveedor	1-Si  0-No
    *	@param		string			$address		Direccion del Tercero
    *	@param		string			$zip			CP del Tercero
    *	@param		string			$town			ciudad del Tercero
    *	@param		string			$siren			RFC del Tercero
    *	@param		int				$fk_user_creat	ID del usuario que crea el Tercero
    *	@param		int				$client			Cliente Potencial/Cliente 0-Ni cliente/Ni Potencial, 1-Cliente, 2-Cliente Potencial, 3-Cliente Potencial/Cliente
    *	@param		int				$fk_pays		ID de codigo de pais en Dolibarr
    *	@return		int				ID del tercero que se creó, < 0 si hubo un error
    */
   static function addTercero($nom, $fournisseur, $address, $zip, $town, $siren, $fk_user_creat, $client, $fk_pays)
   {
       if(Conexion::checaConexion())
        { 
            $nom = str_replace("'","''",$nom);
            $nom = str_replace('"','\"',$nom);
            $sql = 'INSERT INTO llx_societe ( datec, nom, fournisseur, address, zip, town, siren, fk_user_creat, client, fk_pays ) ';
            $val_contact =   '"'.date("Y-m-d H:m:s").'",'.
                             '"'.$nom.'"'.','.
                             '"'.$fournisseur.'"'.','.
                             '"'.$address.'"'.','.
                             '"'.$zip.'"'.','.
                             '"'.$town.'"'.','.
                             '"'.$siren.'"'.','.
                             '"'.$fk_user_creat.'"'.','.
                             '"'.$client.'"'.','.
                             '"'.$fk_pays.'"';
            $sql .= "VALUES ($val_contact);";
            $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");
            Consultas::actualiza();
            return Consultas::buscaTercero($siren);
        }
        return -1;
   }
/*********************************************FACTURAS*********************************************/
    /**
     *	Busca una factura que concuerde con la UUID
     *
     *	@param	string	$uuid				UUID de la Factura
     *	@param	int	$clientOrSuplier	Tipo de Busqueda en la tabla de 0=Facturas a Clientes 1=Facturas a Proveedores 3=busca en las dos tablas
     *	@return	int	ID de la factura buscada correspondiete al campo de las tablas llx_facture.rowid, llx_facturedet.fk_facture
     */
    static function buscaFactura( $uuid, $clientOrSuplier)
    {
        if ($clientOrSuplier == 0 || $clientOrSuplier == 3) {
            $tabla = "llx_facture";
        } else if ($clientOrSuplier == 1) {
            $tabla = "llx_facture_fourn";
        }

        $sql = 'SELECT rowid, ref_ext  FROM '.$tabla.' WHERE ref_ext = "' . $uuid . '"';
        $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");

        if(mysql_num_rows($reslq) == 0 && $clientOrSuplier==3)//no la encontro en llx_facture
        {	
            $tabla = "llx_facture_fourn";	
            $sql = 'SELECT rowid, ref_ext  FROM '.$tabla.' WHERE ref_ext = "' . $uuid . '"';
            $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");
        }
        if($reslq)
        {
            if (mysql_num_rows($reslq) == 0)//no la encontro
            {   
                return -1;
            }
            else
            {
                $arrayRes=mysql_fetch_row($reslq);
                $idFact=$arrayRes[0];
                return $idFact;
            }
        }
    }
    /**
    *	Busca una factura que concuerde con la referencia Suplier o Client
    *
    *	@param		string			$ref				referencia Suplier o Client de la Factura
    *	@return		int									ID de la factura buscada correspondiete al campo de las tablas llx_facture.rowid, llx_facturedet.fk_facture
    */
   static function buscaFacturaRef( $tipoFactura, $ref )
   {     
        if($tipoFactura==0)
        {
            //echo '<p>Busca Factura Cliente</p>';
            $tabla = "llx_facture";
            $atributo = 'ref_client';
        }
        else
        {
            //echo '<p>Busca Factura Proveedor</p>';
            $tabla = "llx_facture_fourn";
            $atributo = 'ref_supplier';
        }
        $sql = 'SELECT rowid, ref_ext  FROM '.$tabla.' WHERE '.$atributo.' = "' . $ref . '"';
        $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");
        if($reslq)
        {
            if (mysql_num_rows($reslq) == 0)//no la encontro
            {                        
                return -1;
            }
            else
            {
                $arrayRes=mysql_fetch_row($reslq);
                $idFact=$arrayRes[0];
                return $idFact;
            }
        }
   }
    /**
    *	Inserta Nueva Factura
    *
    *  @param		int			$tipoFactura			Tipo de Factura 0-Cliente	1-Proveedor					
    *  @param		int			$type				Tipo de la Factura 0-estandar 3-anticipo
    *  @param		date			$datef				Fecha en que se emitio la factura
    *  @param		string			$fk_mode_reglement		Forma de Pago 0-default 1-Ingreso 2-Transferencia 3-Domiciliacion 4-Efectivo 6-Tarjeta 7-Cheque
    *  @param		string			$fk_cond_reglement		Condicion de Pago 1-A la recepcion 2-30 dias 3-30 dias fin de mes 4-60 dias 5- 60 dias a fin de mes 6-Pedido 7-A la entrega 8-50/50
    *  @param		string			$model_pdf			Modelo del documento sugerido por default crabe
    *  @param		string			$note_private			Nota privada unicamente visible en el sistema
    *  @param		string			$note_public			Nota publica visible en la factura
    *  @param		int			$fk_soc				ID del Tercero vinculado a la factura Cliente
    *  @param		float			$amount				Total
    *  @param		float			$remise				Descuento
    *  @param		string			$ref_ext			Referencia externa a dolibarr usada por otras app, UUID folio fiscal
    *  @param		int				$fk_statut		ID del status 0-borrador, 1-no pagada, 2=pagada, 3=abandonada
    *  @param		float			$total				Total Neto
    *  @param		float			$total_ttc			Total Todos los Impuestos Incluidos (Toutes taxes comprises)
    *  @param		float			$tva				Total Impuestos IVA	 
    *  @param		string 			$emisorRFC			RFC del emisor
    *  @param		string 			$libelle			Etiqueta de Factura a Proveedor
    *  @param		string 			$total_tva			Importe total de los impuestos
    *  @return		int			ID de la factura que se inserto, < 0 si hubo un error
    */
   static function addFactura( $tipoFactura, $type, $datef, $fk_mode_reglement,$fk_cond_reglement, $model_pdf, 
                                                           $note_private,$note_public, $fk_soc, $amount,
                                                           $remise, $ref_ext, $fk_statut, $total, $total_ttc, $tva, $emisorRFC,
                                                           $libelle,$total_tva, $selloCFD)
   {	
        $entity=1;//multicompany default 1
        $datec=date("Y-m-d H:m:s");//fecha de creacion
        $tms=date("Y-m-d H:m:s");//fecha de la ultima modificacion
        $paye=0;// esta pagada la facura
        $date_lim_reglement=$datef;


        $ref_supplierClient=str_replace('-', '_', substr($datef, 0, 10)).'_'.$emisorRFC;//$ref_ext;
        //se puede llegar a repetir si llego hasta aqui hay que buscar por refsuplier or client y agregar una numeracion en caso de que ya exista
        $index=0;
        $extra="";
        while(Consultas::buscaFacturaRef($tipoFactura, $ref_supplierClient.$extra)>=0 && ($index<10))
        {
            $index++;
            $extra="_$index";
        }
        $ref_supplierClient=$ref_supplierClient.$extra;

        $datef=str_replace("T"," ",$datef);

        if($tipoFactura==0)
            $tabla='llx_facture';
        else 
        {
            $tabla='llx_facture_fourn';
            $total_ht = $total;		
        }
        $sql = 'INSERT INTO '.$tabla.' ( entity, ref_ext, type, tms, paye, amount, fk_statut, fk_cond_reglement, fk_mode_reglement, 
                        datec, date_lim_reglement, datef, remise, note_private, note_public, total, total_ttc, tva, fk_soc';

        if($tipoFactura==0)
        {
            $sql.=', facnumber, ref_client ';
        }
        else
        {		
            $sql.=', ref, ref_supplier, selloCFD, libelle, total_ht, total_tva ';
        }
        $sql.= ' ) ';

        $val_factura = 
                        '"'.$entity.'"'.','.
                        '"'.$ref_ext.'"'.','.
                        '"'.$type.	'"'.','.
                        '"'.$tms.	'"'.','.
                        '"'.$paye.	'"'.','.
                        '"'.$amount.'"'.','.
                        '"'.$fk_statut.'"'.','.
                        '"'.$fk_cond_reglement.'"'.','.
                        '"'.$fk_mode_reglement.'"'.','.
                        '"'.$datec.	'"'.','.
                        '"'.$date_lim_reglement.	'"'.','.
                        '"'.$datef.	'"'.','.
                        '"'.$remise.'"'.','.
                        '"'.$note_private.'"'.','.
                        '"'.$note_public.'"'.','.				
                        '"'.$total.'"'.','.
                        '"'.$total_ttc.'"'.','.			
                        '"'.$tva.'"'.','.
                        '"'.$fk_soc. '"'.','.	

                        ' "(PROV)" '.','.
                        '"'.$ref_supplierClient.'"'
                        ;

        if($tipoFactura==1)
        {
            $val_factura.=','.
                        '"'.$selloCFD. '"'.','.
                        '"'.$libelle.'"'.','.
                        '"'.$total_ht.'"'.','.
                        '"'.$total_tva.'"'
                        ;
        }	
        $sql .= 'VALUES ('. $val_factura.');';

        $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");

        if($tipoFactura==0)
            $atributo='facnumber';
        else
            $atributo='ref';	
        $sql="UPDATE ".$tabla." SET ".$atributo."=CONCAT('(PROV',rowid,')') WHERE ".$atributo."='(PROV)'";
        $reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");

        if($reslq)
        {
            $fac_busq = Consultas::buscaFactura($ref_ext,$tipoFactura);
            return $fac_busq;
        }	
        return $reslq;	
   }

   /**
    *	Inserta Nueva Linea a la Factura 
    *
    *	@param		int			$tipoFactura			Tipo de factura 0-Cliente	1-Proveedor
    *	@param		int			$fk_facture			ID de la Factura
    *	@param		int			$qty				Cantidad
    *	@param		string			$description			Descripcion del Producto/Servicio
    * 	@param		float			$subprice			Precio Unitario
    *	@param		float			$total_ht			Importe
    *	@param		float			$tva_tx				Impuesto Trasladado
    *   @param		float			$ref				Referencia de Proveedor UUID de la factura
    *   @param		float			$label				Etiqueta de la Linea
    *	@return		int			>0 si la linea se inserto, <=0 si hubo un error
    */
   static function addLinea($tipoFactura, $fk_facture, $qty, $description, $subprice, $total_ht, $tva_tx,
                   $ref='', $label='' )
   {
	$description = str_replace("'","''",$description);
	$description = str_replace('"','\"',$description);
	
	$total_tva = floatval($total_ht) * $tva_tx/100;
	
	$total_ttc = floatval($total_ht) * (1+$tva_tx/100);
	$pu_ttc = floatval($subprice) * (1+$tva_tx/100);
	$localtax1_type=0;
	$localtax2_type=0;
	
	if ($tipoFactura == 0) {
            $tabla = 'llx_facturedet';
        } else {
            $tabla = 'llx_facture_fourn_det';
        }

        $sql = 'INSERT INTO ' . $tabla . ' ( qty, description, total_ht, tva_tx ';

	if ($tipoFactura == 0) {
            $sql .= ', subprice, total_tva, fk_facture ';
        } else {
            $sql .= ', pu_ht, tva, fk_facture_fourn, ref, label, total_ttc, pu_ttc, localtax1_type, localtax2_type';
        }
        $sql .= ') ';

	$val_contact =
                    "'".$qty."'".','.
                    "'".$description."'".','.
                    "'".$total_ht.	"'".','.
                    "'".$tva_tx.	"'".','.

                    "'".$subprice.	"'".','.
                    "'".$total_tva.	"'".','.
                    "'".$fk_facture."'"
                    ;

        if ($tipoFactura == 1) {
            $val_contact .=
                    "," .
                    "'" . $ref . "'" . ',' .
                    "'" . $label . "'" . ',' .
                    "'" . $total_ttc . "'" . ',' .
                    "'" . $pu_ttc . "'" . ',' .
                    "'" . $localtax1_type . "'" . ',' .
                    "'" . $localtax2_type . "'"
            ;
        }

        $sql .= 'VALUES ('. $val_contact.');';
	$reslq = mysql_query($sql) or die("Error SQL :".mysql_error()."<br/><br/>$sql");
	return $resl;
    }
}
