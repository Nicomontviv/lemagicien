<?php
// backend/config/conexion.php

class Conexion {
    public $conn;

    public function getConexion() {
        $this->conn = null;

        // Buscamos el archivo .env un nivel arriba de la carpeta config
        $ruta_env = __DIR__ . '/../.env';
        
        // Verificamos que el archivo exista para evitar errores fatales de ejecución
        if (!file_exists($ruta_env)) {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error de configuración: Faltan las variables de entorno."]);
            exit();
        }

        // Parseamos el archivo .env como un arreglo asociativo
        $env = parse_ini_file($ruta_env);

        try {
            // Configuraciones avanzadas de PDO para garantizar seguridad y rendimiento
            $opciones = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lanza excepciones ante errores de SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve los datos como arreglos asociativos
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4" // Asegura soporte correcto de tildes y caracteres especiales
            );
            
            // Establecemos la conexión utilizando los datos extraídos del .env
            $this->conn = new PDO(
                "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'],
                $env['DB_USER'],
                $env['DB_PASS'],
                $opciones
            );
            
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode(["mensaje" => "Error de conexión a la base de datos: " . $exception->getMessage()]);
            exit();
        }

        return $this->conn;
    }
}
?>