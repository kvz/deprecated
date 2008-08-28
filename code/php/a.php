<?php
Class Mother
{
    protected function speak() {
        echo "Hello\n";
    }
    
    public function Mother() {
        $this->speak();
    }
}

Class Dauther extends Mother
{
    private function speak() {
        echo "Hi\n";
    }
}

$Daugter = new Dauther();


?>