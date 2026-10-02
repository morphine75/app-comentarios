<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    switch ($method) {
        case 'GET':
            getComments($pdo);
            break;
        case 'POST':
            createComment($pdo);
            break;
        case 'DELETE':
            deleteComment($pdo);
            break;
        default:
            respond(405, false, 'Método no permitido');
    }
} catch (Throwable $exception) {
    respond(500, false, 'Error al procesar la solicitud');
}

function getComments(PDO $pdo): void
{
    $statement = $pdo->query(
        "SELECT id, author, content, created_at
         FROM comments
         ORDER BY created_at DESC, id DESC
         LIMIT 10"
    );

    $comments = $statement->fetchAll();

    respond(200, true, null, ['comments' => $comments]);
}

function createComment(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        respond(400, false, 'El cuerpo de la solicitud debe ser JSON válido');
        return;
    }

    $author = trim($input['author'] ?? '');
    $content = trim($input['content'] ?? '');

    if ($author === '' || $content === '') {
        respond(400, false, 'Nombre y contenido son requeridos');
        return;
    }

    if (strlen($author) > 100 || strlen($content) > 1000) {
        respond(400, false, 'Datos exceden el límite permitido');
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO comments (author, content, created_at) VALUES (?, ?, NOW())'
    );
    $statement->execute([$author, $content]);

    respond(201, true, null, ['id' => (int) $pdo->lastInsertId()]);
}

function deleteComment(PDO $pdo): void
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false || $id === null) {
        respond(400, false, 'ID inválido');
        return;
    }

    $statement = $pdo->prepare('DELETE FROM comments WHERE id = ?');
    $statement->execute([$id]);

    if ($statement->rowCount() === 0) {
        respond(404, false, 'Comentario no encontrado');
        return;
    }

    respond(200, true);
}

function respond(int $status, bool $success, ?string $error = null, array $data = []): never
{
    http_response_code($status);

    $response = ['success' => $success];
    if ($error !== null) {
        $response['error'] = $error;
    }
    $response = array_merge($response, $data);

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
