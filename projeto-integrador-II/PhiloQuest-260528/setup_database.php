<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar banco se não existir
    $pdo->exec('CREATE DATABASE IF NOT EXISTS philoquest');
    $pdo->exec('USE philoquest');

    // Ler e executar o script SQL
    $sql = file_get_contents('database/PhiloQuest_Schema.sql');

    // Dividir em statements individuais
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            echo "Executando: " . substr($statement, 0, 50) . "...<br>";
            $pdo->exec($statement);
        }
    }

    echo "<h2 style='color: green;'>Banco de dados PhiloQuest configurado com sucesso!</h2>";
    echo "<p>Você pode agora testar o sistema:</p>";
    echo "<ul>";
    echo "<li><a href='../login.php'>Login como Professor (PROF001 / prof123)</a></li>";
    echo "<li><a href='../admin/dashboard.php'>Login como Admin (admin / admin123)</a></li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro ao configurar banco de dados:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>