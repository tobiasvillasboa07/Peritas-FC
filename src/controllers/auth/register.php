<?php
require_once __DIR__ . '/../../config/bootstrap.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  
  header('Location: /src/views/auth/register.php');
  exit;
}
$data = [
  'email'          => trim($_POST['email'] ?? ''), 
  'name'           => trim($_POST['name'] ?? ''),
  'password'       => $_POST['password'] ?? '',
  'repeatPassword' => $_POST['repeatPassword'] ?? ''
];

if (empty($data['email']) || empty($data['name']) || empty($data['password']) || empty($data['repeatPassword'])) {
  header('Location: /src/views/auth/register.php?error=Todos+los+campos+son+obligatorios');
  exit;
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
  header('Location: /src/views/auth/register.php?error=El+correo+electronico+no+es+valido');
  exit;
}

if ($data['password'] !== $data['repeatPassword']) {
  header('Location: /src/views/auth/register.php?error=Las+contrasenas+no+coinciden');
  exit;
}
try {
  $checkUser = $pdo->prepare('SELECT id FROM users WHERE email = :email');
  $checkUser->execute(['email' => $data['email']]);
  if ($checkUser->fetch()) {
    header('Location: /src/views/auth/register.php?error=El+correo+ya+esta+registrado');
    exit;
  }

  $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

  $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
  $stmt->execute([
    'name'     => $data['name'],
    'email'    => $data['email'],
    'password' => $hashedPassword,
  ]);

  $_SESSION['user'] = [
    'id'    => $pdo->lastInsertId(),
    'name'  => $data['name'],
    'email' => $data['email'],
  ];

  header('Location: /src/views/index.php');
  exit;
} catch (PDOException $e) {
  header('Location: /src/views/auth/register.php?error=Error+en+el+servidor');
  exit;
}