<?php
namespace PhiloQuest\Entidades;

class Administrador extends Usuario {
    
    // O construtor garante que ele nasça com as propriedades básicas preenchidas e o tipo 'ADMIN' cravado
    public function __construct(string $matricula, string $nome, string $senhaHash) {
        parent::__construct($matricula, $nome, $senhaHash, 'ADMIN');
    }

    // Seus métodos de negócio preservados para implementação futura
    public function gerenciarUsuarios(): void {
        // Lógica futura
    }
    
    public function importarListasMatricula(): void {
        // Lógica futura
    }
    
    public function adicionarFilosofo(): void {
        // Lógica futura
    }
    
    public function adicionarConceito(): void {
        // Lógica futura
    }
}