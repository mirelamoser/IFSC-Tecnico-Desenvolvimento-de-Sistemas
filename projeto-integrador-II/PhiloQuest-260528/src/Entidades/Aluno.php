<?php
namespace PhiloQuest\Entidades;

class Aluno extends Usuario {
    private int $xp_acumulado;
    private array $cartas_desbloqueadas = [];
    private ?Turma $turma;

    public function __construct(string $matricula, string $nome, string $senha_hash) {
        parent::__construct($matricula, $nome, $senha_hash);
        $this->xp_acumulado = 0;
        $this->turma = null;
    }

    public function adicionarXp(int $quantidade): void {
        if ($quantidade > 0) {
            $this->xp_acumulado += $quantidade;
        }
    }

    public function getXpAcumulado(): int { return $this->xp_acumulado; }

    public function vincularTurma(Turma $turma): void {
        $this->turma = $turma;
    }

    public function desbloquearCarta(CartaConceitual $carta): void {
        $this->cartas_desbloqueadas[] = $carta;
    }
}