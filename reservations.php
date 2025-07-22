<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite cereri din frontend
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$file = 'reservations.json';

// Inițializează fișierul JSON dacă nu există
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Citește rezervările
    $reservations = json_decode(file_get_contents($file), true);
    echo json_encode($reservations);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adaugă o rezervare nouă
    $data = json_decode(file_get_contents('php://input'), true);
    $reservations = json_decode(file_get_contents($file), true);

    // Verifică limita de 4 rezervări pe zi
    $date = $data['date'];
    $dayReservations = array_filter($reservations, function ($res) use ($date) {
        return $res['date'] === $date;
    });

    if (count($dayReservations) >= 4) {
        http_response_code(400);
        echo json_encode(['error' => 'Ziua este complet rezervată']);
        exit;
    }

    // Adaugă rezervarea
    $reservations[] = [
        'title' => $data['title'],
        'date' => $date,
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'eventType' => $data['eventType'],
        'details' => $data['details'],
        'createdAt' => date('c')
    ];

    // Salvează în fișier cu blocare pentru a preveni suprascrierea
    $fp = fopen($file, 'c+');
    if (flock($fp, LOCK_EX)) {
        file_put_contents($file, json_encode($reservations, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
    }
    fclose($fp);

    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodă nepermisă']);
}
?>