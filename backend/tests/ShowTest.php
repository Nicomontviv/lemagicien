<?php
use PHPUnit\Framework\TestCase;

class ShowTest extends TestCase {
    
    // Ahora las URLs son estáticas
    private static $baseUrlShows = "http://localhost:8000/api/shows/";
    private static $baseUrlTrucos = "http://localhost:8000/api/trucos/";
    
    private static $usuarioId = 1; // Tu usuario de prueba en la BD
    private static $trucoId1;
    private static $trucoId2;
    private static $showIdCreado;

    // La función ahora es estática para poder usarse antes de que se instancie la clase
    private static function peticionHTTP($urlBase, $metodo, $endpoint, $datos = null) {
        $opciones = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => $metodo,
                'ignore_errors' => true 
            ]
        ];
        if ($datos) {
            $opciones['http']['content'] = json_encode($datos);
        }
        $contexto  = stream_context_create($opciones);
        $resultado = file_get_contents($urlBase . $endpoint, false, $contexto);
        
        preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
        $status = (int)$match[1];
        
        return ['status' => $status, 'body' => json_decode($resultado, true)];
    }

    // Configuración previa: Creamos dos trucos reales en el repertorio para asociar
    public static function setUpBeforeClass(): void {
        $truco1 = ["nombre" => "Aparición de Paloma Test", "categoria" => "Magia Infantil"];
        $res1 = self::peticionHTTP(self::$baseUrlTrucos, 'POST', 'crear.php', $truco1);
        self::$trucoId1 = $res1['body']['id_truco'];

        $truco2 = ["nombre" => "Cuerda Cortada Test", "categoria" => "Magia Infantil"];
        $res2 = self::peticionHTTP(self::$baseUrlTrucos, 'POST', 'crear.php', $truco2);
        self::$trucoId2 = $res2['body']['id_truco'];
    }

    public function testEscenario1CreacionExitosa() {
        $datos = [
            "usuario_id" => self::$usuarioId,
            "nombre" => "Show Infantil Profesional",
            "descripcion" => "Estructura pensada para salones de fiesta.",
            "trucos" => [self::$trucoId1]
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'POST', 'crear.php', $datos);

        $this->assertEquals(201, $respuesta['status']);
        $this->assertArrayHasKey('id_show', $respuesta['body']);
        self::$showIdCreado = $respuesta['body']['id_show'];
    }

    public function testEscenario2NombreFaltante() {
        $datos = [
            "usuario_id" => self::$usuarioId,
            "nombre" => "",
            "trucos" => [self::$trucoId1]
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'POST', 'crear.php', $datos);
        $this->assertEquals(400, $respuesta['status']);
    }

    public function testEscenario3SinTrucosAsociados() {
        $datos = [
            "usuario_id" => self::$usuarioId,
            "nombre" => "Show Fantasma",
            "trucos" => []
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'POST', 'crear.php', $datos);
        $this->assertEquals(400, $respuesta['status']);
    }

    public function testEscenario1AgregarTruco() {
        $datos = [
            "show_id" => self::$showIdCreado,
            "truco_id" => self::$trucoId2,
            "accion" => "agregar"
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'POST', 'modificar_trucos.php', $datos);
        $this->assertEquals(200, $respuesta['status']);
    }

    public function testEscenario1VisualizacionExitosa() {
        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'GET', 'detalle.php?id=' . self::$showIdCreado);
        
        $this->assertEquals(200, $respuesta['status']);
        $this->assertEquals("Show Infantil Profesional", $respuesta['body']['nombre']);
        $this->assertCount(2, $respuesta['body']['trucos']); // Debe tener el original + el agregado
    }

    public function testEscenario2QuitarTruco() {
        $datos = [
            "show_id" => self::$showIdCreado,
            "truco_id" => self::$trucoId2,
            "accion" => "quitar"
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'POST', 'modificar_trucos.php', $datos);
        $this->assertEquals(200, $respuesta['status']);
    }

    public function testEscenario3IntentarDejarSinTrucos() {
        // Intentamos quitar el truco 1 (que ahora es el único que queda en el show)
        $datos = [
            "show_id" => self::$showIdCreado,
            "truco_id" => self::$trucoId1,
            "accion" => "quitar"
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlShows, 'POST', 'modificar_trucos.php', $datos);
        $this->assertEquals(400, $respuesta['status']);
    }
}