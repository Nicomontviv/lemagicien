<?php
use PHPUnit\Framework\TestCase;

class EventoTest extends TestCase {
    
    private static $baseUrlEventos = "http://localhost:8000/api/eventos/";
    private static $baseUrlShows = "http://localhost:8000/api/shows/";
    private static $baseUrlTrucos = "http://localhost:8000/api/trucos/";
    
    private static $usuarioId = 1; 
    private static $showIdDummy;
    private static $eventoIdCreado;

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

    public static function setUpBeforeClass(): void {
        // 1. Crear truco base
        $truco = ["nombre" => "Mentalismo Base", "categoria" => "Mentalismo"];
        $resTruco = self::peticionHTTP(self::$baseUrlTrucos, 'POST', 'crear.php', $truco);
        $trucoId = $resTruco['body']['id_truco'];

        // 2. Crear show base
        $show = [
            "usuario_id" => self::$usuarioId,
            "nombre" => "Show Principal Noche",
            "trucos" => [$trucoId]
        ];
        $resShow = self::peticionHTTP(self::$baseUrlShows, 'POST', 'crear.php', $show);
        self::$showIdDummy = $resShow['body']['id_show'];
    }

    public function testEscenario1RegistroExitoso() {
        $datos = [
            "usuario_id" => self::$usuarioId,
            "show_id" => self::$showIdDummy,
            "cliente" => "Productora de Comedia",
            "fecha" => "2026-03-19",
            "hora" => "21:00",
            "direccion" => "Miraflores, Lima",
            "monto" => 150000,
            "observaciones" => "Lima Magic & Comedy Nights - Fecha 1"
        ];

        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'POST', 'crear.php', $datos);

        $this->assertEquals(201, $respuesta['status']);
        $this->assertArrayHasKey('id_evento', $respuesta['body']);
        self::$eventoIdCreado = $respuesta['body']['id_evento'];
    }

    public function testEscenario2DatosObligatoriosFaltantes() {
        $datos = [
            "cliente" => "Cliente Fantasma"
            // Faltan fecha, hora, monto
        ];
        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'POST', 'crear.php', $datos);
        $this->assertEquals(400, $respuesta['status']);
    }

    public function testEscenario3ShowNoSeleccionado() {
        $datos = [
            "cliente" => "Cliente Real",
            "fecha" => "2026-04-10",
            "hora" => "20:00",
            "monto" => 50000
        ];
        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'POST', 'crear.php', $datos);
        $this->assertEquals(400, $respuesta['status']);
    }

    public function testEscenario3ConfirmarEvento() {
        $datos = [
            "id" => self::$eventoIdCreado,
            "estado" => "Confirmado"
        ];
        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'PUT', 'cambiar_estado.php', $datos);
        $this->assertEquals(200, $respuesta['status']);
    }

    public function testEscenario1ConsultarAgendaExitoso() {
        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'GET', 'agenda.php');
        $this->assertEquals(200, $respuesta['status']);
        $this->assertIsArray($respuesta['body']);
    }

    public function testEscenario1EventoRealizadoCalculaIngresos() {
        // Pasamos el evento a "Realizado"
        $datos = ["id" => self::$eventoIdCreado, "estado" => "Realizado"];
        self::peticionHTTP(self::$baseUrlEventos, 'PUT', 'cambiar_estado.php', $datos);

        // Chequeamos ingresos de ese mes (Marzo 2026)
        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'GET', 'ingresos.php?mes=03&anio=2026');
        
        $this->assertEquals(200, $respuesta['status']);
        $this->assertEquals(150000, $respuesta['body']['total']);
    }

    public function testEscenario2EventoCanceladoNoCalculaIngresos() {
        // Pasamos el evento a "Cancelado"
        $datos = ["id" => self::$eventoIdCreado, "estado" => "Cancelado"];
        self::peticionHTTP(self::$baseUrlEventos, 'PUT', 'cambiar_estado.php', $datos);

        // Chequeamos ingresos nuevamente
        $respuesta = self::peticionHTTP(self::$baseUrlEventos, 'GET', 'ingresos.php?mes=03&anio=2026');
        
        $this->assertEquals(200, $respuesta['status']);
        $this->assertEquals(0, $respuesta['body']['total']); // No debe contabilizarlo
    }
}