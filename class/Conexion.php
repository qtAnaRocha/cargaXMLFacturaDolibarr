<?php
class Conexion {
    
    public static $conexion=null;
    function __construct() {
        
    }
    public static function checaConexion()
    {
        $bandRet=0;
        
        if($conexion == null)
        {        
            Conexion::conecta();
            $bandRet=1;
            //echo 'conecta</br>';
        }
        else
        {
            $bandRet=1;
            //echo 'ya estaba conectado</br>';
        }
        return $bandRet;
    }
    static function conecta()
    {   
        /*if(function_exists("parse_ini_file"))
        {
            $array_ini = parse_ini_file("conf/cargaXML.ini");
            print_r($array_ini);
            $conexion=mysql_connect($array_ini['sql_host'], $array_ini['sql_login'], $array_ini['sql_pass']) or die("Error Conexion SQL (mysql_connect):".mysql_error()."<br/>");
            mysql_select_db($array_ini['sql_base'],$conexion) or die("Error Conexion SQL (mysql_select_db):".mysql_error()."<br/>");
        }
        else
        {*/
            require 'class/conf.php';  
            
            $conexion=mysql_connect(sql_host, sql_login, sql_pass) or die("Error Conexion SQL (mysql_connect):".mysql_error()."<br/>");
            mysql_select_db(sql_base,$conexion) or die("Error Conexion SQL (mysql_select_db):".mysql_error()."<br/>");
       // }
    }
    function cierraConexion($dbid)
    {
        mysql_close($dbid);
    }
    
}

