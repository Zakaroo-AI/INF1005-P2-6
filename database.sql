-- ============================================================
-- PokéMart Global — Database Setup
-- Run this file in your MySQL / Google Cloud SQL console
-- ============================================================

CREATE DATABASE IF NOT EXISTS pokemart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pokemart;

-- -----------------------------------------------
-- USERS
-- -----------------------------------------------
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role        ENUM('trainer','admin') DEFAULT 'trainer',
    avatar_url  VARCHAR(255) DEFAULT NULL,
    is_banned   TINYINT(1)   DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- CARDS CATALOGUE
-- -----------------------------------------------
CREATE TABLE cards (
    card_id     INT AUTO_INCREMENT PRIMARY KEY,
    card_name   VARCHAR(100) NOT NULL,
    set_name    VARCHAR(100) NOT NULL,
    card_number VARCHAR(20)  NOT NULL,
    typing      VARCHAR(30)  NOT NULL,
    rarity      ENUM('Common','Uncommon','Rare','Holo Rare','Double Rare','Ultra Rare','Illustration Rare','Special Illustration Rare','Hyper Rare','Secret Rare','Ace Spec Rare','Shiny Rare','Shiny Ultra Rare','Promo') NOT NULL DEFAULT 'Common',
    image_url   VARCHAR(255) NOT NULL DEFAULT 'assets/images/default.png',
    description TEXT DEFAULT NULL
);

-- -----------------------------------------------
-- LISTINGS
-- -----------------------------------------------
CREATE TABLE listings (
    listing_id  INT AUTO_INCREMENT PRIMARY KEY,
    seller_id   INT NOT NULL,
    card_id     INT NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    price       DECIMAL(10,2) NOT NULL,
    stock       INT NOT NULL DEFAULT 1,
    condition_grade ENUM('PSA 1','PSA 2','PSA 3','PSA 4','PSA 5','PSA 6','PSA 7','PSA 8','PSA 9','PSA 10') NOT NULL DEFAULT 'PSA 7',
    language    VARCHAR(30)  NOT NULL DEFAULT 'English',
    status      ENUM('active','sold','removed') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id)   ON DELETE CASCADE,
    FOREIGN KEY (card_id)   REFERENCES cards(card_id)   ON DELETE CASCADE
);

-- -----------------------------------------------
-- CART
-- -----------------------------------------------
CREATE TABLE cart (
    cart_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    listing_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, listing_id)
);

-- -----------------------------------------------
-- ORDERS
-- -----------------------------------------------
CREATE TABLE orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id    INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','processing','shipped','delivered') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- ORDER ITEMS
-- -----------------------------------------------
CREATE TABLE order_items (
    item_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    listing_id  INT NOT NULL,
    quantity    INT NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)     ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- WISHLIST
-- -----------------------------------------------
CREATE TABLE wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    listing_id  INT NOT NULL,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, listing_id)
);

-- -----------------------------------------------
-- DISPUTES
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS disputes (
    dispute_id  INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    buyer_id    INT NOT NULL,
    reason      TEXT NOT NULL,
    status      ENUM('open','resolved','closed') DEFAULT 'open',
    admin_note  TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)   ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id)     ON DELETE CASCADE,
    UNIQUE KEY unique_dispute (order_id, buyer_id)
);

-- -----------------------------------------------
-- REVIEWS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    buyer_id    INT NOT NULL,
    seller_id   INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (order_id, buyer_id),
    FOREIGN KEY (order_id)  REFERENCES orders(order_id)  ON DELETE CASCADE,
    FOREIGN KEY (buyer_id)  REFERENCES users(user_id)    ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(user_id)    ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin account (password: password)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@pokemart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Trainer accounts (password: password)
INSERT INTO users (username, email, password_hash, role) VALUES
('AshKetchum',  'ash@pokemart.com',  '$2y$10$TKh8H1.PfbuNauTTalJMFO2bGa9OaBIFerZ2RFJFY/HHkOCCQ0Bla', 'trainer'),
('MistyWater',  'misty@pokemart.com','$2y$10$TKh8H1.PfbuNauTTalJMFO2bGa9OaBIFerZ2RFJFY/HHkOCCQ0Bla', 'trainer'),
('BrockRock',   'brock@pokemart.com','$2y$10$TKh8H1.PfbuNauTTalJMFO2bGa9OaBIFerZ2RFJFY/HHkOCCQ0Bla', 'trainer');

-- Cards catalogue
INSERT INTO cards (card_name, set_name, card_number, typing, rarity, image_url, description) VALUES
('Charizard',  'Base Set',          '4/102',  'Fire',      'Holo Rare',   'https://images.pokemontcg.io/base1/4_hires.png',   'One of the most iconic cards ever printed. A must-have for any serious collector.'),
('Blastoise',  'Base Set',          '2/102',  'Water',     'Holo Rare',   'https://images.pokemontcg.io/base1/2_hires.png',   'The Water-type powerhouse from the original Base Set.'),
('Venusaur',   'Base Set',          '15/102', 'Grass',     'Holo Rare',   'https://images.pokemontcg.io/base1/15_hires.png',  'Rare Grass-type card from the original Base Set.'),
('Pikachu',    'Base Set',          '58/102', 'Lightning', 'Common',      'https://images.pokemontcg.io/base1/58_hires.png',  'The classic Pikachu card everyone started with.'),
('Mewtwo',     'Base Set',          '10/102', 'Psychic',   'Holo Rare',   'https://images.pokemontcg.io/base1/10_hires.png',  'Legendary Psychic-type with incredible power.'),
('Gengar',     'Base Set',          '5/102',  'Psychic',   'Holo Rare',   'https://images.pokemontcg.io/base1/5_hires.png',   'Spooky Ghost-type card from the original Base Set.'),
('Charizard',  'Scarlet & Violet',  '006/198','Fire',      'Ultra Rare',  'https://images.pokemontcg.io/sv1/6_hires.png',    'The modern Scarlet & Violet reprint of the beloved Charizard.'),
('Pikachu',    'Celebrations',      '005/025','Lightning', 'Uncommon',    'https://images.pokemontcg.io/cel25/5_hires.png',  'Special 25th Anniversary Celebrations Pikachu.'),
('Umbreon',    'Aquapolis',         'H29/H32','Darkness',  'Holo Rare',   'https://images.pokemontcg.io/ecard2/H29_hires.png','A highly sought-after Darkness-type from the e-Card era.'),
('Lucario',    'Diamond & Pearl',   '6/130',  'Fighting',  'Holo Rare',   'https://images.pokemontcg.io/dp1/6_hires.png',    'The Aura Pokémon in holographic glory.'),
('Dragonite',  'Fossil',            '4/62',   'Colorless', 'Holo Rare',   'https://images.pokemontcg.io/fossil/4_hires.png', 'A rare Dragonite card from the Fossil expansion.'),
('Eevee',      'Jungle',            '51/64',  'Colorless', 'Common',      'https://images.pokemontcg.io/jungle/51_hires.png','The classic Eevee card from the Jungle set.');

-- Sample listings
INSERT INTO listings (seller_id, card_id, title, description, price, stock, condition_grade, language) VALUES
(2, 1,  'Base Set Charizard — PSA 9',            'Near mint condition, no scratches on holo. One of the best copies around.',   2500.00, 1, 'PSA 9',  'English'),
(2, 2,  'Base Set Blastoise — Holo',             'Classic Blastoise in great condition. A staple for any Base Set collection.', 750.00,  1, 'PSA 8',  'English'),
(3, 3,  'Base Set Venusaur — Holo',             'Lightly played Venusaur, minor edge wear only.',                               400.00,  1, 'PSA 7',  'English'),
(3, 5,  'Mewtwo Base Set — Holo',               'Excellent condition Mewtwo with strong holo pattern.',                         600.00,  2, 'PSA 8',  'English'),
(4, 4,  'Classic Pikachu Base Set',             'The original yellow border Pikachu. Nostalgic and collectible.',                45.00,  3, 'PSA 6',  'English'),
(4, 7,  'Charizard Scarlet & Violet',           'Modern Charizard from the new SV set. Gem mint pulled straight from pack.',    180.00,  2, 'PSA 10', 'English'),
(2, 8,  'Celebrations Pikachu 25th Anniversary','Special anniversary card in perfect condition.',                                90.00,  2, 'PSA 10', 'English'),
(3, 9,  'Umbreon Aquapolis Holo — Japanese',    'Stunning Japanese Umbreon from the e-Card era. Very rare find.',               850.00,  1, 'PSA 9',  'Japanese'),
(4, 10, 'Lucario Diamond & Pearl Holo',         'Beautiful Lucario holo in near mint condition.',                               200.00,  1, 'PSA 8',  'English'),
(2, 11, 'Dragonite Fossil Holo',                'Vintage Dragonite from the Fossil expansion. Played but complete.',            300.00,  1, 'PSA 5',  'English'),
(3, 12, 'Jungle Eevee — Classic Common',        'The original Jungle Eevee. Great for completing the set.',                     25.00,  5, 'PSA 6',  'English'),
(4, 6,  'Gengar Base Set Holo',                 'Spooky Gengar in great condition. Strong holo with minor whitening.',          550.00,  1, 'PSA 7',  'English');

-- ============================================================
-- MIGRATION: Run this on existing databases to update rarity ENUM
-- ============================================================
-- ALTER TABLE cards
-- MODIFY COLUMN rarity ENUM('Common','Uncommon','Rare','Holo Rare','Double Rare','Ultra Rare','Illustration Rare','Special Illustration Rare','Hyper Rare','Secret Rare','Ace Spec Rare','Shiny Rare','Shiny Ultra Rare','Promo') NOT NULL DEFAULT 'Common';
