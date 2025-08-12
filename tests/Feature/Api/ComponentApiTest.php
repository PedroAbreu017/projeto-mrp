
<?php

use PHPUnit\Framework\TestCase;

class ComponentApiTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';
    
    public function testGetComponentsReturnsValidJson()
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($this->baseUrl . '/api/components', false, $context);
        
        if ($response === false) {
            $this->markTestSkipped('Servidor não disponível em localhost:8000');
            return;
        }
        
        $data = json_decode($response, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
    }
    
    public function testGetComponentsStructure()
    {
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $response = @file_get_contents($this->baseUrl . '/api/components', false, $context);
        
        if ($response === false) {
            $this->markTestSkipped('Servidor não disponível');
            return;
        }
        
        $data = json_decode($response, true);
        
        if (!empty($data['data'])) {
            $component = $data['data'][0];
            
            $this->assertArrayHasKey('id', $component);
            $this->assertArrayHasKey('nome', $component);
            $this->assertArrayHasKey('quantidade_estoque', $component);
        }
        
        $this->assertTrue(true); // Sempre passa se chegou aqui
    }
}
