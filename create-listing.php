<?php
// ============================================================
// create-listing.php — Create New Listing
// ============================================================
ob_start();
require_once 'includes/header.php';
$pageTitle = 'Create Listing';
requireLogin();

$pdo    = getPDO();
$errors = [];

$conditions = ['PSA 1','PSA 2','PSA 3','PSA 4','PSA 5','PSA 6','PSA 7','PSA 8','PSA 9','PSA 10'];
$languages  = ['English','Japanese','Korean','Chinese','German','French','Spanish','Italian'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName       = trim($_POST['card_name_h']    ?? '');
    $setName        = trim($_POST['set_name_h']     ?? '');
    $cardNumber     = trim($_POST['card_number_h']  ?? '');
    $typing         = trim($_POST['typing_h']       ?? '');
    $rarity         = trim($_POST['rarity_h']       ?? '');
    $imageUrl       = trim($_POST['image_url_h']    ?? '');
    $title          = trim($_POST['title']          ?? '');
    $description    = trim($_POST['description']    ?? '');
    $price          = (float)($_POST['price']       ?? 0);
    $stock          = (int)($_POST['stock']         ?? 1);
    $conditionGrade = trim($_POST['condition_grade']?? '');
    $language       = trim($_POST['language']       ?? '');

    if (empty($cardName))            $errors[] = 'Please search and select a card.';
    if (strlen($title) < 5)          $errors[] = 'Title must be at least 5 characters.';
    if ($price <= 0)                 $errors[] = 'Price must be greater than 0.';
    if ($stock < 1 || $stock > 99)   $errors[] = 'Stock must be between 1 and 99.';
    if (!in_array($conditionGrade, $conditions)) $errors[] = 'Please select a condition.';
    if (!in_array($language, $languages))        $errors[] = 'Please select a language.';

    if (empty($errors)) {
        // Check if card already exists, if not insert it
        $stmt = $pdo->prepare("SELECT card_id FROM cards WHERE card_name = ? AND set_name = ? AND card_number = ?");
        $stmt->execute([$cardName, $setName, $cardNumber]);
        $cardId = $stmt->fetchColumn();

        if (!$cardId) {
            $pdo->prepare("INSERT INTO cards (card_name, set_name, card_number, typing, rarity, image_url) VALUES (?,?,?,?,?,?)")
                ->execute([$cardName, $setName, $cardNumber, $typing, $rarity, $imageUrl]);
            $cardId = $pdo->lastInsertId();
        }

        $pdo->prepare("
            INSERT INTO listings (seller_id, card_id, title, description, price, stock, condition_grade, language)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$_SESSION['user_id'], $cardId, $title, $description, $price, $stock, $conditionGrade, $language]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing created successfully!'];
        header('Location: /my-listings.php'); exit;
    }
}
?>

<div class="container py-5" style="max-width:680px;">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-plus-circle me-2"></i>Create a Listing
    </h1>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <form method="POST" id="listingForm" novalidate>

            <!-- Hidden card data fields -->
            <input type="hidden" name="card_name_h"   id="card_name_h"   value="<?= isset($_POST['card_name_h'])   ? e($_POST['card_name_h'])   : '' ?>">
            <input type="hidden" name="set_name_h"    id="set_name_h"    value="<?= isset($_POST['set_name_h'])    ? e($_POST['set_name_h'])    : '' ?>">
            <input type="hidden" name="card_number_h" id="card_number_h" value="<?= isset($_POST['card_number_h']) ? e($_POST['card_number_h']) : '' ?>">
            <input type="hidden" name="typing_h"      id="typing_h"      value="<?= isset($_POST['typing_h'])      ? e($_POST['typing_h'])      : '' ?>">
            <input type="hidden" name="rarity_h"      id="rarity_h"      value="<?= isset($_POST['rarity_h'])      ? e($_POST['rarity_h'])      : '' ?>">
            <input type="hidden" name="image_url_h"   id="image_url_h"   value="<?= isset($_POST['image_url_h'])   ? e($_POST['image_url_h'])   : '' ?>">

            <!-- Card Search -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Search Card <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="cardSearch"
                           placeholder="Type a card name, e.g. Charizard..." autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="clearCard" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div id="searchStatus" class="form-text"></div>
                <div id="searchResults" class="mt-2 d-flex flex-wrap gap-2" style="max-height:300px;overflow-y:scroll;"></div>
            </div>

            <!-- Selected Card Preview -->
            <div id="cardPreview" class="mb-3 p-3 rounded-3 border <?= isset($_POST['card_name_h']) && $_POST['card_name_h'] ? '' : 'd-none' ?>"
                 style="background:#f0f4ff;">
                <div class="d-flex gap-3 align-items-center">
                    <img id="previewImg" src="<?= isset($_POST['image_url_h']) ? e($_POST['image_url_h']) : '' ?>"
                         alt="" style="width:60px;height:84px;object-fit:contain;">
                    <div>
                        <div class="fw-bold fs-6" id="previewName"><?= isset($_POST['card_name_h']) ? e($_POST['card_name_h']) : '' ?></div>
                        <div class="text-muted small" id="previewSet">
                            <?= isset($_POST['set_name_h']) ? e($_POST['set_name_h']) . ' · #' . e($_POST['card_number_h'] ?? '') : '' ?>
                        </div>
                        <div class="mt-1" id="previewBadges"></div>
                    </div>
                </div>
            </div>

            <!-- Condition & Language -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="condition_grade" class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                    <select class="form-select" id="condition_grade" name="condition_grade" required>
                        <option value="">— Select —</option>
                        <?php foreach ($conditions as $cond): ?>
                        <option value="<?= $cond ?>" <?= (isset($_POST['condition_grade']) && $_POST['condition_grade'] === $cond) ? 'selected' : '' ?>><?= $cond ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label for="language" class="form-label fw-semibold">Language <span class="text-danger">*</span></label>
                    <select class="form-select" id="language" name="language" required>
                        <option value="">— Select —</option>
                        <?php foreach ($languages as $lang): ?>
                        <option value="<?= $lang ?>" <?= (isset($_POST['language']) && $_POST['language'] === $lang) ? 'selected' : '' ?>><?= $lang ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Title -->
            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Listing Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title"
                       value="<?= isset($_POST['title']) ? e($_POST['title']) : '' ?>"
                       required minlength="5" maxlength="150"
                       placeholder="e.g. Charizard Base Set — PSA 9">
                <div class="invalid-feedback">Title must be at least 5 characters.</div>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"
                          placeholder="Describe the card — centering, surface scratches, etc."><?= isset($_POST['description']) ? e($_POST['description']) : '' ?></textarea>
            </div>

            <!-- Price & Stock -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label for="price" class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="price" name="price"
                               value="<?= isset($_POST['price']) ? e($_POST['price']) : '' ?>"
                               required min="0.01" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="col-6">
                    <label for="stock" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="stock" name="stock"
                           value="<?= isset($_POST['stock']) ? e($_POST['stock']) : '1' ?>"
                           required min="1" max="99">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pm-primary px-5">
                    <i class="bi bi-check-circle me-2"></i>Create Listing
                </button>
                <a href="/my-listings.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function mapRarity(r) {
    if (/Hyper/.test(r))                          return 'Hyper Rare';
    if (/Special Illustration/.test(r))           return 'Special Illustration Rare';
    if (/Illustration Rare/.test(r))              return 'Illustration Rare';
    if (/Shiny Ultra/.test(r))                    return 'Shiny Ultra Rare';
    if (/Shiny/.test(r))                          return 'Shiny Rare';
    if (/Ace Spec/.test(r))                       return 'Ace Spec Rare';
    if (/Secret|Rainbow|Gold/.test(r))            return 'Secret Rare';
    if (/Ultra|VMAX|VSTAR/.test(r))               return 'Ultra Rare';
    if (/Double Rare|Two Rare/.test(r))           return 'Double Rare';
    if (/Promo/.test(r))                          return 'Promo';
    if (/Holo|EX|GX| V$| V |BREAK/.test(r))      return 'Holo Rare';
    if (r === 'Rare')                              return 'Rare';
    if (r === 'Uncommon')                         return 'Uncommon';
    return 'Common';
}

let searchTimer;
let currentAbortController = null;
const searchInput = document.getElementById('cardSearch');
const resultsDiv  = document.getElementById('searchResults');
const statusDiv   = document.getElementById('searchStatus');
const previewDiv  = document.getElementById('cardPreview');

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) {
        if (currentAbortController) { currentAbortController.abort(); currentAbortController = null; }
        resultsDiv.innerHTML = ''; statusDiv.textContent = ''; return;
    }
    statusDiv.textContent = 'Searching...';
    searchTimer = setTimeout(() => fetchCards(q), 400);
});

document.getElementById('clearCard').addEventListener('click', () => {
    clearTimeout(searchTimer);
    if (currentAbortController) { currentAbortController.abort(); currentAbortController = null; }
    searchInput.value = '';
    resultsDiv.innerHTML = '';
    statusDiv.textContent = '';
    previewDiv.classList.add('d-none');
    ['card_name_h','set_name_h','card_number_h','typing_h','rarity_h','image_url_h']
        .forEach(id => document.getElementById(id).value = '');
});

async function fetchCards(q) {
    if (currentAbortController) currentAbortController.abort();
    currentAbortController = new AbortController();
    const signal = currentAbortController.signal;

    try {
        const res  = await fetch(
            `https://api.pokemontcg.io/v2/cards?q=name:${encodeURIComponent(q)}*&pageSize=50&select=id,name,set,number,rarity,types,images`,
            { signal }
        );
        if (!res.ok) throw new Error('API error');
        const data  = await res.json();
        const cards = (data.data || []).map(c => ({
            api_id:      c.id,
            name:        c.name,
            set_name:    c.set?.name      ?? '',
            card_number: c.number         ?? '',
            rarity:      mapRarity(c.rarity ?? ''),
            typing:      c.types?.[0]     ?? 'Colorless',
            image:       c.images?.small  ?? '',
            image_large: c.images?.large  ?? c.images?.small ?? '',
        }));
        resultsDiv.innerHTML = '';
        statusDiv.textContent = cards.length ? `${cards.length} results — click a card to select` : 'No cards found.';
        cards.forEach(card => {
            const el = document.createElement('div');
            el.className = 'border rounded-3 p-1 text-center';
            el.style.cssText = 'width:78px;cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;';
            el.innerHTML = `
                <img src="${card.image}" style="width:62px;height:87px;object-fit:contain;" alt="${card.name}">
                <div style="font-size:0.58rem;font-weight:600;line-height:1.2;margin-top:2px;">${card.name}</div>
                <div style="font-size:0.55rem;color:#888;">${card.set_name}</div>`;
            el.addEventListener('click',      () => selectCard(card, el));
            el.addEventListener('mouseenter', () => { el.style.transform = 'scale(1.08)'; el.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)'; });
            el.addEventListener('mouseleave', () => { el.style.transform = ''; el.style.boxShadow = ''; });
            resultsDiv.appendChild(el);
        });
    } catch (e) {
        if (e.name === 'AbortError') return; // Cancelled — ignore
        statusDiv.textContent = 'Could not reach the card API. Please try again.';
    }
}

function selectCard(card, el) {
    document.querySelectorAll('#searchResults > div').forEach(e => e.style.outline = '');
    el.style.outline = '3px solid #3B4CCA';

    document.getElementById('card_name_h').value   = card.name;
    document.getElementById('set_name_h').value    = card.set_name;
    document.getElementById('card_number_h').value = card.card_number;
    document.getElementById('typing_h').value      = card.typing;
    document.getElementById('rarity_h').value      = card.rarity;
    document.getElementById('image_url_h').value   = card.image_large;

    document.getElementById('previewImg').src             = card.image_large || card.image;
    document.getElementById('previewName').textContent    = card.name;
    document.getElementById('previewSet').textContent     = card.set_name + ' · #' + card.card_number;
    document.getElementById('previewBadges').innerHTML    =
        `<span class="badge me-1" style="background:#6c757d;">${card.typing}</span>` +
        `<span class="badge rarity-${card.rarity.toLowerCase().replace(/ /g,'-')}">${card.rarity}</span>`;
    previewDiv.classList.remove('d-none');

    // Auto-fill title if empty
    const titleInput = document.getElementById('title');
    if (!titleInput.value) titleInput.value = `${card.name} — ${card.set_name}`;
}
</script>

<?php require_once 'includes/footer.php'; ?>
