<?php
class LectorXML {
    
    protected $emisor;
    protected $receptor;
    protected $comprobante;
    protected $lineas=array();
    protected $impuestoTotal;
    protected $totalImpuestosTrasladados;
    protected $timbreFiscalDigital;    
    
    //al que se le va a facturar
    protected $direccion;
    protected $nombre;
    protected $rfc;
    
    protected $esValida;
    
    function __construct($xmlFile, $MIRFC) {
        $this->esValida="";
        $xml = simplexml_load_file($xmlFile);
        $lineas=array();
	$ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('c', $ns['cfdi']);
	$xml->registerXPathNamespace('t', $ns['tfd']);
	
	$emisor = $xml->xpath('//c:Comprobante//c:Emisor')[0] or $this->setError("ERROR: XML invalido, falta emisor");
        $this->emisor = $emisor;
        $receptor = $xml->xpath('//c:Comprobante//c:Receptor')[0] or $this->setError("ERROR: XML invalido, falta receptor");
        $this->receptor = $receptor; 
        $comprobante = $xml->xpath('//c:Comprobante')[0]or $this->setError("ERROR: XML invalido, falta comprobante");
	$this->comprobante = $comprobante;
        foreach ($xml->xpath('//c:Comprobante//c:Conceptos//c:Concepto') as $linea)
        {
            array_push($lineas, $linea);
        }
        $this->lineas = $lineas;
        
        if($impuestoTotal = $xml->xpath('//c:Impuestos')[0])                       
            $this->totalImpuestosTrasladados = $impuestoTotal['totalImpuestosTrasladados'];
        else
        {
            $impuestoTotal = $xml->xpath('//c:Impuestos//c:Traslados//c:Traslado')[0];
            $this->totalImpuestosTrasladados = $impuestoTotal['importe'];            
        }
        $tax_importe = $this->totalImpuestosTrasladados;
	$this->impuestoTotal = $impuestoTotal;
       
	$timbreFiscalDigital=$xml->xpath('//c:Comprobante//c:Complemento//t:TimbreFiscalDigital')[0]or $this->setError("ERROR: XML invalido, falta timbreFiscalDigital");
        $this->timbreFiscalDigital = $timbreFiscalDigital;
        
        if($receptor['rfc'] == $MIRFC)// La empresa es el receptor, el tercero corresponde al emisor
	{
            $this->direccion = $xml->xpath('//c:Comprobante//c:Emisor//c:DomicilioFiscal')[0]or $this->setError("ERROR: XML invalido, falta DomicilioFiscal");
            $this->nombre = $emisor['nombre'];//atributo opcional
            $this->rfc=$emisor['rfc'];
            if(!$emisor['rfc'])
                $this->setError("ERROR: XML invalido, falta rfc de emisor");
            if (!$this->nombre)
                $this->nombre = $this->rfc;            
        }
	else if($emisor['rfc'] == $MIRFC)// La empresa es el emisor, el tercero corresponde al receptor
	{
            $this->direccion = $xml->xpath('//c:Comprobante//c:Receptor//c:Domicilio')[0]or $this->setError("ERROR: XML invalido, falta Domicilio");
            
            $this->nombre = $receptor['nombre'];//atributo opcional            
            $this->rfc = $receptor['rfc'];
            if(!$receptor['rfc'])
                $this->setError("ERROR: XML invalido, falta rfc de receptor");
            if (!$this->nombre) 
                $this->nombre = $this->rfc;
        }
	else 
	{
            $this->setError('La Empresa con el RFC "'.$MIRFC.'" no se encuentra en la Factura XML ni como emisor, ni como receptor');
	}
    }
    function __destruct(){ 
      	
    } 

    function getEsValida()
    {
        return $this->esValida == "";
    }
    
    function getError()
    {
        return $this->esValida;
    }
    
    function setError($error) {
        $this->esValida .= $error."</br>";
    }

    function getNotePublic()//Funcion que genera la cadena original de certificación digital del SAT
    {
        $selloCFD=$this->timbreFiscalDigital['selloCFD'];
        if($selloCFD=='')
            $this->setError("ERROR: XML invalido, falta selloCFD");
        $selloSAT=$this->timbreFiscalDigital['selloSAT'];
        if($selloSAT=='')
            $this->setError("ERROR: XML invalido, falta selloSAT");
        $version=$this->timbreFiscalDigital['version'];
        if($version=='')
            $this->setError("ERROR: XML invalido, falta version");
        $UUID=$this->getUUID();
        $FechaTimbrado=$this->timbreFiscalDigital['FechaTimbrado'];
        if($FechaTimbrado=='')
            $this->setError("ERROR: XML invalido, falta FechaTimbrado");
        $noCertificadoSAT=$this->timbreFiscalDigital['noCertificadoSAT'];
        if($noCertificadoSAT=='')
            $this->setError("ERROR: XML invalido, falta noCertificadoSAT");
        $noCertificado=$this->comprobante['noCertificado'];
        if($noCertificado=='')
            $this->setError("ERROR: XML invalido, falta noCertificado");
        if($this->getEsValida())
        {
            $cadNotePublic= 'Este documento es una representación impresa de un CFDI.'.
                            '<br><br>Sello Digital del CFDI<br> '.$selloCFD.
                            '<br><br>Sello del SAT <br>'.$selloSAT.
                            '<br><br>Cadena Original de certificacion digital del SAT <br>'.
                            '||'.$version.'|'.$UUID.'|'.$FechaTimbrado.'|'.$selloCFD.'|'.$noCertificadoSAT.'||'.
                            '<br><br>Certificado de Sello Digital '.$noCertificado.
                            '<br><br>Fecha de certificacion '.$FechaTimbrado.'<br>';
        } else{
            $cadNotePublic="";
        }
        return $cadNotePublic;        
    }
    
    function getCodigoQR()//Funcion que genera la cadena para generar el codigo de barras QR
    {        
	$cadTotal = strval($this->getTotal());//Total del comprobante a 17 posiciones (10 para los enteros, 1 para carácter ".", 6 para los decimales)
	$array = explode('.',$cadTotal);	
	$enteros = str_pad($array[0],10,'0',STR_PAD_LEFT);
	$decimales = str_pad($array[1],6,'0',STR_PAD_RIGHT);
	$totalQR = $enteros.'.'.$decimales;
	
	$cadQR='?re='.$this->getRFCEmisor().
                '&rr='.$this->getRFCReceptor().
                '&tt='.$totalQR.
                '&id='.$this->getUUID();
	return $cadQR;
    }
    
    function getCP()//atributo opcional
    {
        $cp= $this->direccion['codigoPostal'];
        return $cp;
    }
    function getEstado()
    {
        $estado=$this->direccion['estado'];
        if($this->direccion['estado']=='')
            $this->setError("ERROR: XML invalido, falta estado");
        return $estado;
    }
    function getDireccionToString()
    {        
        $num=$this->direccion['noExterior']?$this->direccion['noExterior']:$this->direccion['noInterior'];
        $address.=$this->direccion['calle'];
        if($this->direccion['calle']=='')
            $this->setError("ERROR: XML invalido, falta calle");
        $address.= " ".$num;
        $col.=' COL. '.$this->direccion['colonia'];
        if($this->direccion['colonia']!='')
		$address.=$col;
            //$this->setError("ERROR: XML invalido, falta colonia");
        return $address;
    }
    
    function getTotal()
    {
        $total=$this->comprobante['total'];
        if($this->comprobante['total']=='')
            $this->setError("ERROR: XML invalido, falta total");   
        return $total;
    }
    
    function getSubTotal()
    {
        $subTotal=$this->comprobante['subTotal'];
        if($this->comprobante['subTotal']=='')
            $this->setError("ERROR: XML invalido, falta subtotal");   
        $descuento = $this->comprobante['descuento'];        
        return $subTotal-$descuento;
    }
            
    function getDescuento()//atributo opcional
    {
        $descuento=floatval($this->comprobante['descuento']);
        return $descuento;
    }
    function getTotalImpuestosTrasladados()//atributo opcional
    {
        //$totalImpuestosTrasladados=$this->impuestoTotal['totalImpuestosTrasladados'];
        return $this->totalImpuestosTrasladados;
    }
    function getFecha()
    {
        $fecha=$this->comprobante['fecha'];
        if($this->comprobante['fecha']=='')
            $this->setError("ERROR: XML invalido, falta fecha");
        return $fecha;
    }
    
    function getRFCEmisor()
    {
        $rfc= $this->emisor['rfc'];
        if($this->emisor['rfc']=='')
            $this->setError("ERROR: XML invalido, falta RFC del emisor");
        return $rfc;
    }
    
    function getRFCReceptor()
    {
        $rfc= $this->receptor['rfc'];
        if($this->receptor['rfc']=='')
            $this->setError("ERROR: XML invalido, falta RFC del receptor");
        return $rfc;
    }
    
    function getUUID()
    {
        $timbreFiscalDigital = $this->timbreFiscalDigital['UUID'];
        if($this->timbreFiscalDigital['UUID']=='')
            $this->setError("ERROR: XML invalido, falta UUID");
        return strtoupper($timbreFiscalDigital);
    }
    
    function getNombre() {
        return $this->nombre;
    }

    function getRfc() {
        return $this->rfc;
    }
        
    function getDireccion() {
        return $this->direccion;
    }

    function getEmisor() {
        return $this->emisor;
    }

    function getReceptor() {
        return $this->receptor;
    }

    function getComprobante() {
        return $this->comprobante;
    }

    function getLineas() {
        return $this->lineas;
    }

    function getImpuestoTotal() {
        return $this->impuestoTotal;
    }

    function getTimbreFiscalDigital() {
        return $this->timbreFiscalDigital;
    }


}
