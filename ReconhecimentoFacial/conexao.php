<?php
// Configuração do banco de dados
$serverName = "DESKTOP-JI9FDB3\SQLEXPRESS"; // Dê atenção à dupla barra (\\)
$connectionOptions = array(
    "Database" => "Reconhecimento",  
    "Uid" => "teste",        
    "PWD" => "teste",        
    "CharacterSet" => "UTF-8"
);

// Conexão
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Verifica conexão
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>