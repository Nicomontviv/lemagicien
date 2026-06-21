<?php
use PHPUnit\Framework\TestCase;

class TrucoTest extends TestCase {
    
    // URL de tu servidor integrado de PHP
    private $baseUrl = "http://localhost:8000/api/trucos/";
    private static $trucoIdCreado; 

    private function peticionHTTP($metodo, $endpoint, $datos = null) {
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
        $resultado = file_get_contents($this->baseUrl . $endpoint, false, $contexto);
        
        preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
        $status = (int)$match[1];
        
        return ['status' => $status, 'body' => json_decode($resultado, true)];
    }

    public function testEscenario1CreacionExitosa() {
        $datos = [
            "nombre" => "Predicción del Caballero Oscuro",
            "descripcion" => "Adivinación temática con cartas.",
            "categoria" => "Mentalismo",
            "duracion_minutos" => 5
        ];

        $respuesta = $this->peticionHTTP('POST', 'crear.php', $datos);

        $this->assertEquals(201, $respuesta['status']);
        $this->assertArrayHasKey('id_truco', $respuesta['body']);
        
        self::$trucoIdCreado = $respuesta['body']['id_truco'];
    }

    public function testEscenario2NombreFaltante() {
        $datos = [
            "categoria" => "Magia de cerca"
        ];

        $respuesta = $this->peticionHTTP('POST', 'crear.php', $datos);

        $this->assertEquals(400, $respuesta['status']);
        $this->assertStringContainsString("nombre", strtolower($respuesta['body']['mensaje']));
    }

    public function testEscenario2FiltradoPorCategoria() {
        $endpoint = 'buscar.php?categoria=Mentalismo';
        $respuesta = $this->peticionHTTP('GET', $endpoint);

        $this->assertEquals(200, $respuesta['status']);
        $this->assertIsArray($respuesta['body']);
        $this->assertGreaterThanOrEqual(1, count($respuesta['body']));
    }

    public function testEscenario3BusquedaSinResultados() {
        $endpoint = 'buscar.php?categoria=CategoriaInexistente';
        $respuesta = $this->peticionHTTP('GET', $endpoint);

        $this->assertEquals(404, $respuesta['status']);
    }

    public function testEscenario1EdicionExitosa() {
        $datos = [
            "id" => self::$trucoIdCreado,
            "nombre" => "Predicción Actualizada",
            "descripcion" => "La rutina ahora es más corta.",
            "categoria" => "Salón 110",
            "duracion_minutos" => 3
        ];

        $respuesta = $this->peticionHTTP('PUT', 'editar.php', $datos);

        $this->assertEquals(200, $respuesta['status']);
    }

    public function testEscenario2NombreVacioDuranteEdicion() {
        $datos = [
            "id" => self::$trucoIdCreado,
            "nombre" => "", 
            "categoria" => "Salón 110"
        ];

        $respuesta = $this->peticionHTTP('PUT', 'editar.php', $datos);

        $this->assertEquals(400, $respuesta['status']);
    }

    public function testEscenario1EliminacionExitosa() {
        $datos = [
            "id" => self::$trucoIdCreado
        ];

        $respuesta = $this->peticionHTTP('DELETE', 'eliminar.php', $datos);

        $this->assertEquals(200, $respuesta['status']);
        
        $respuestaBusqueda = $this->peticionHTTP('GET', 'buscar.php?nombre=' . urlencode('Predicción Actualizada'));
        $this->assertEquals(404, $respuestaBusqueda['status']);
    }
}