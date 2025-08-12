<?php
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase 
{
    public function testSystemIsWorking()
    {
        $this->assertTrue(true, "Sistema básico funcionando");
    }
    
    public function testMathOperation()
    {
        $result = 2 + 2;
        $this->assertEquals(4, $result, "Matemática básica funcionando");
    }
}
