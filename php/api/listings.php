<?php
// Save to: php/api/listings.php
require_once __DIR__ . '/../objects/Listing.php';
header('Content-Type: application/json');

$filters = [
    'category_id'  => !empty($_GET['category_id'])  ? (int)$_GET['category_id']  : null,
    'condition_id' => !empty($_GET['condition_id'])  ? (int)$_GET['condition_id'] : null,
    'search'       => !empty($_GET['q'])             ? trim($_GET['q'])            : null,
    'sort'         => $_GET['sort'] ?? 'newest',
    'limit'        => !empty($_GET['limit'])  ? min((int)$_GET['limit'],  80) : 24,
    'offset'       => !empty($_GET['offset']) ? (int)$_GET['offset']           : 0,
];

$listing  = new Listing();
$listings = $listing->getListings($filters);
echo json_encode($listings);