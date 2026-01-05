<?php
require_once '../config/database.php';
require_once '../models/Projeto.php';
require_once '../models/Professor.php';

$database = new Database();
$db = $database->getConnection();

$projeto = new Projeto($db);

if(isset($_GET['id'])) {
    // Página individual do projeto
    $projeto->buscarPorId($_GET['id']);
    $professor = new Professor($db);
    $professor->buscarPorId($projeto->id_professor);
} else {
    // Lista de projetos
    if(isset($_GET['professor'])) {
        $projetos = $projeto->listarPorProfessor($_GET['professor'])->fetchAll();
    } else {
        $projetos = $projeto->listar()->fetchAll();
    }
}
?>

<!-- Estrutura similar para exibir projetos individualmente ou em lista -->