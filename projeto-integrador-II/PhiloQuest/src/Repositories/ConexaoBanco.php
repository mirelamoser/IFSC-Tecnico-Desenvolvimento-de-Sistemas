<?php

declare(strict_types=1);

namespace PhiloQuest\Repositories;

use PhiloQuest\Config;
use PDO;
use PDOException;

class ConexaoBanco
{
    private static ?self $instancia = null;
    private PDO $conexao;

    private function __construct()
    {
        $host = Config::get('DB_HOST', 'localhost');
        $dbname = Config::get('DB_NAME', 'philoquest');
        $user = Config::get('DB_USER', 'root');
        $password = Config::get('DB_PASSWORD', '');

        if ($user === 'root') {
            error_log('PhiloQuest DB: a usar root — verifique se o .env está legível pelo Apache (www-data).');
        }

        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->conexao = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('PhiloQuest DB: ' . $e->getMessage());
            throw new PDOException('Erro de conexão com o banco de dados.');
        }
    }

    public static function getInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function getConexao(): PDO
    {
        return $this->conexao;
    }
}
