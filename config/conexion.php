<?php
// =========================================================
// CONFIGURACIÓN DE LA CONEXIÓN A MYSQL (conexion.php)
// ---------------------------------------------------------
// Crea el objeto PDO global $pdo que usan todas las clases
// Model. Delega en el gestor Db (includes/Db.php) encargado
// de la conexión compartida, la persistencia opcional y la
// reconexión automática si el servidor MySQL la corta.
// =========================================================

require_once __DIR__ . '/../includes/Db.php';

$pdo = Db::conexion();

