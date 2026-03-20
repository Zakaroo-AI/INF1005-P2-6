<?php
// ============================================================
// api/card-search.php — Proxy to Pokémon TCG API
// ============================================================
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$ini    = parse_ini_file('/var/www/private/db_config.ini', true);
$apiKey = $ini['pokemontcg']['api_key'] ?? '';

$url = 'https://api.pokemontcg.io/v2/cards?' . http_build_query([
    'q'        => "name:{$q}*",
    'pageSize' => 12,
    'select'   => 'id,name,set,number,rarity,types,images',
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 6,
    CURLOPT_HTTPHEADER     => $apiKey ? ["X-Api-Key: {$apiKey}"] : [],
]);
$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if (!$response) { echo json_encode([]); exit; }

$data = json_decode($response, true);
$cards = [];

foreach ($data['data'] ?? [] as $card) {
    $cards[] = [
        'api_id'      => $card['id'],
        'name'        => $card['name'],
        'set_name'    => $card['set']['name']     ?? '',
        'card_number' => $card['number']          ?? '',
        'rarity'      => mapRarity($card['rarity'] ?? ''),
        'typing'      => $card['types'][0]        ?? 'Colorless',
        'image'       => $card['images']['small'] ?? '',
        'image_large' => $card['images']['large'] ?? $card['images']['small'] ?? '',
    ];
}

echo json_encode($cards);

function mapRarity(string $r): string {
    if (strpos($r, 'Hyper') !== false)                                                                       return 'Hyper Rare';
    if (strpos($r, 'Special Illustration') !== false)                                                        return 'Special Illustration Rare';
    if (strpos($r, 'Illustration Rare') !== false)                                                           return 'Illustration Rare';
    if (strpos($r, 'Shiny Ultra') !== false)                                                                 return 'Shiny Ultra Rare';
    if (strpos($r, 'Shiny') !== false)                                                                       return 'Shiny Rare';
    if (strpos($r, 'Ace Spec') !== false)                                                                    return 'Ace Spec Rare';
    if (strpos($r, 'Secret') !== false || strpos($r, 'Rainbow') !== false || strpos($r, 'Gold') !== false)  return 'Secret Rare';
    if (strpos($r, 'Ultra') !== false  || strpos($r, 'VMAX') !== false    || strpos($r, 'VSTAR') !== false) return 'Ultra Rare';
    if (strpos($r, 'Double Rare') !== false || strpos($r, 'Two Rare') !== false)                            return 'Double Rare';
    if (strpos($r, 'Promo') !== false)                                                                       return 'Promo';
    if (strpos($r, 'Holo') !== false || strpos($r, 'EX') !== false || strpos($r, 'GX') !== false || preg_match('/ V( |$)/', $r) || strpos($r, 'BREAK') !== false) return 'Holo Rare';
    if ($r === 'Rare')     return 'Rare';
    if ($r === 'Uncommon') return 'Uncommon';
    return 'Common';
}
