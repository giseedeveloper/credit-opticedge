<?php

/**
 * Generates database/sql/oe_bulk_reference_data.sql
 * Run: php database/sql/generate_oe_bulk_reference_data.php
 */

declare(strict_types=1);

$now = date('Y-m-d H:i:s');
$pwOpt = '$2y$12$BzY58AAGGMvS/NO0xWFosOPl8TA.ERLLtiODBPkXQ7.LwRxyYlHya'; // Opticedge@2026
$pwOwner = '$2y$12$ZWvO4ZaLA/8V4ipvc/ezC.Qgi0PAIAtdhPgQacTyyP0DznUNDLlCa'; // DealerOwner@2026

function sqlq(string $s): string
{
    return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $s)."'";
}

function spec(array $ramGb, int $storageGb, string $color, ?string $display = null): string
{
    $p = [
        'ram' => $ramGb[0].'GB'.(isset($ramGb[1]) ? ' + '.$ramGb[1].'GB virtual' : ''),
        'storage' => $storageGb.'GB',
        'color' => $color,
    ];
    if ($display !== null) {
        $p['display'] = $display;
    }

    return json_encode($p, JSON_UNESCAPED_UNICODE);
}

/** @return list<array{0:string,1:string,2:float,3:float,4:string}> name, slug, retail, cost, specifications json */
function modelsForBrand(string $brandSlug): array
{
    return match ($brandSlug) {
        'samsung' => [
            ['Galaxy A06 (6/128)', 'samsung-galaxy-a06-6-128', 429000, 352000, spec([6], 128, 'Light Blue', '6.7" HD+')],
            ['Galaxy A15 (6/128)', 'samsung-galaxy-a15-6-128', 549000, 450000, spec([6], 128, 'Blue Black', '6.5" FHD+ Super AMOLED')],
            ['Galaxy A16 (6/128)', 'samsung-galaxy-a16-6-128', 489000, 401000, spec([6], 128, 'Gray', '6.7" FHD+')],
            ['Galaxy A25 5G (8/256)', 'samsung-galaxy-a25-5g-8-256', 799000, 655000, spec([8], 256, 'Blue', '6.5" FHD+ 120Hz')],
            ['Galaxy A35 5G (8/256)', 'samsung-galaxy-a35-5g-8-256', 989000, 811000, spec([8], 256, 'Awesome Lilac', '6.6" FHD+ 120Hz')],
            ['Galaxy A55 5G (8/256)', 'samsung-galaxy-a55-5g-8-256', 1249000, 1024000, spec([8], 256, 'Awesome Navy', '6.6" FHD+ 120Hz')],
            ['Galaxy A05 (4/64)', 'samsung-galaxy-a05-4-64', 299000, 245000, spec([4], 64, 'Silver', '6.7" PLS LCD')],
            ['Galaxy A05s (6/128)', 'samsung-galaxy-a05s-6-128', 379000, 311000, spec([6], 128, 'Green', '6.7" FHD+')],
            ['Galaxy M14 5G (6/128)', 'samsung-galaxy-m14-5g-6-128', 559000, 458000, spec([6], 128, 'Dark Blue', '6.6" FHD+')],
            ['Galaxy M34 5G (8/256)', 'samsung-galaxy-m34-5g-8-256', 729000, 598000, spec([8], 256, 'Midnight Blue', '6.5" FHD+ 120Hz')],
            ['Galaxy A14 (4/128)', 'samsung-galaxy-a14-4-128', 419000, 343000, spec([4], 128, 'Black', '6.6" FHD+')],
            ['Galaxy A24 (6/128)', 'samsung-galaxy-a24-6-128', 519000, 426000, spec([6], 128, 'Awesome Graphite', '6.5" FHD+ Super AMOLED')],
            ['Galaxy A34 5G (8/256)', 'samsung-galaxy-a34-5g-8-256', 879000, 721000, spec([8], 256, 'Awesome Violet', '6.6" FHD+ 120Hz')],
            ['Galaxy A54 5G (8/256)', 'samsung-galaxy-a54-5g-8-256', 1099000, 901000, spec([8], 256, 'Awesome Lime', '6.4" FHD+ 120Hz')],
            ['Galaxy S23 FE (8/256)', 'samsung-galaxy-s23-fe-8-256', 1899000, 1557000, spec([8], 256, 'Mint', '6.4" Dynamic AMOLED 2X')],
        ],
        'tecno' => [
            ['Spark 20 (8/256)', 'tecno-spark-20-8-256', 459000, 376000, spec([8], 256, 'Cyber White', '6.6" HD+ 90Hz')],
            ['Spark 20C (8/128)', 'tecno-spark-20c-8-128', 389000, 319000, spec([8], 128, 'Alpenglow Gold', '6.6" HD+ 90Hz')],
            ['Spark 20 Pro (8/256)', 'tecno-spark-20-pro-8-256', 599000, 491000, spec([8], 256, 'Moonlit Black', '6.78" FHD+ 120Hz')],
            ['Camon 30 (8/256)', 'tecno-camon-30-8-256', 849000, 696000, spec([8], 256, 'Iceland Basaltic Grey', '6.78" AMOLED 120Hz')],
            ['Camon 30 Pro (12/512)', 'tecno-camon-30-pro-12-512', 1249000, 1024000, spec([12], 512, 'Alpine Snow', '6.78" AMOLED 144Hz')],
            ['Pova 6 (12/256)', 'tecno-pova-6-12-256', 679000, 557000, spec([12], 256, 'Comet Green', '6.78" FHD+ 120Hz')],
            ['Pova 6 Neo (8/256)', 'tecno-pova-6-neo-8-256', 549000, 450000, spec([8], 256, 'Rising Tide', '6.78" HD+')],
            ['Spark Go 2024 (4/128)', 'tecno-spark-go-2024-4-128', 329000, 270000, spec([4], 128, 'Mystery White', '6.56" HD+')],
            ['Pop 8 (4/128)', 'tecno-pop-8-4-128', 279000, 229000, spec([4], 128, 'Mystery Black', '6.6" HD+')],
            ['Spark 10 (8/256)', 'tecno-spark-10-8-256', 429000, 352000, spec([8], 256, 'Meta Black', '6.6" HD+ 90Hz')],
            ['Spark 10 Pro (8/256)', 'tecno-spark-10-pro-8-256', 519000, 426000, spec([8], 256, 'Starry Black', '6.8" FHD+')],
            ['Phantom X2 (8/256)', 'tecno-phantom-x2-8-256', 1599000, 1311000, spec([8], 256, 'Stardust Gray', '6.8" Curved AMOLED')],
            ['Spark 8C (4/64)', 'tecno-spark-8c-4-64', 259000, 212000, spec([4], 64, 'Turquoise', '6.52" HD+')],
            ['Camon 20 Pro (8/256)', 'tecno-camon-20-pro-8-256', 729000, 598000, spec([8], 256, 'Serenity Blue', '6.67" FHD+ AMOLED')],
            ['Pova 5 Pro (8/256)', 'tecno-pova-5-pro-8-256', 629000, 516000, spec([8], 256, 'Dark Illusion', '6.78" FHD+ 120Hz')],
        ],
        'infinix' => [
            ['Hot 40i (8/256)', 'infinix-hot-40i-8-256', 449000, 368000, spec([8], 256, 'Palm Blue', '6.56" HD+ 90Hz')],
            ['Hot 40 (8/256)', 'infinix-hot-40-8-256', 529000, 434000, spec([8], 256, 'Starlit Black', '6.78" FHD+ 120Hz')],
            ['Hot 40 Pro (8/256)', 'infinix-hot-40-pro-8-256', 649000, 532000, spec([8], 256, 'Titan Gold', '6.78" FHD+ 120Hz')],
            ['Note 40 (8/256)', 'infinix-note-40-8-256', 799000, 655000, spec([8], 256, 'Vintage Green', '6.78" FHD+ AMOLED 120Hz')],
            ['Note 40 Pro (12/512)', 'infinix-note-40-pro-12-512', 1149000, 942000, spec([12], 512, 'Obsidian Black', '6.78" AMOLED 120Hz')],
            ['Smart 8 (4/128)', 'infinix-smart-8-4-128', 319000, 262000, spec([4], 128, 'Timber Black', '6.6" HD+')],
            ['Smart 8 Plus (4/128)', 'infinix-smart-8-plus-4-128', 349000, 286000, spec([4], 128, 'Galaxy White', '6.6" HD+')],
            ['Hot 30 (8/256)', 'infinix-hot-30-8-256', 479000, 393000, spec([8], 256, 'Boring Black', '6.78" FHD+ 90Hz')],
            ['Hot 30 Play (8/128)', 'infinix-hot-30-play-8-128', 399000, 327000, spec([8], 128, 'Lime', '6.82" HD+')],
            ['Note 30 (8/256)', 'infinix-note-30-8-256', 689000, 565000, spec([8], 256, 'Magic Black', '6.78" FHD+')],
            ['Note 30 VIP (12/256)', 'infinix-note-30-vip-12-256', 949000, 778000, spec([12], 256, 'Sunset Gold', '6.67" AMOLED')],
            ['GT 10 Pro (8/256)', 'infinix-gt-10-pro-8-256', 899000, 737000, spec([8], 256, 'Cyber Black', '6.67" FHD+ 120Hz')],
            ['Hot 20 (6/128)', 'infinix-hot-20-6-128', 369000, 303000, spec([6], 128, 'Racing Black', '6.82" HD+')],
            ['Hot 20s (8/128)', 'infinix-hot-20s-8-128', 419000, 344000, spec([8], 128, 'Tempo Blue', '6.82" HD+')],
            ['Zero 30 (8/256)', 'infinix-zero-30-8-256', 999000, 819000, spec([8], 256, 'Rome Green', '6.78" AMOLED 144Hz')],
        ],
        'itel' => [
            ['A50 (4/128)', 'itel-a50-4-128', 289000, 237000, spec([4], 128, 'Meadow Green', '6.6" HD+')],
            ['A70 (8/256)', 'itel-a70-8-256', 379000, 311000, spec([8], 256, 'Field Green', '6.6" HD+')],
            ['Vision 3 (4/128)', 'itel-vision-3-4-128', 319000, 262000, spec([4], 128, 'Jewelry Blue', '6.6" HD+')],
            ['Vision 5 (8/128)', 'itel-vision-5-8-128', 359000, 294000, spec([8], 128, 'Crystal Blue', '6.6" HD+')],
            ['P55 (8/256)', 'itel-p55-8-256', 429000, 352000, spec([8], 256, 'Brilliant Gold', '6.6" HD+')],
            ['P55+ (8/128)', 'itel-p55-plus-8-128', 399000, 327000, spec([8], 128, 'Royal Green', '6.6" HD+')],
            ['A90 (8/256)', 'itel-a90-8-256', 459000, 376000, spec([8], 256, 'Starry Black', '6.6" HD+')],
            ['S23 (4/128)', 'itel-s23-4-128', 349000, 286000, spec([4], 128, 'Stone White', '6.6" HD+')],
            ['A60 (4/128)', 'itel-a60-4-128', 269000, 221000, spec([4], 128, 'Shadow Black', '6.6" HD+')],
            ['A40 (4/64)', 'itel-a40-4-64', 229000, 188000, spec([4], 64, 'Blue', '6.3" FWVGA')],
            ['P65 (8/256)', 'itel-p65-8-256', 489000, 401000, spec([8], 256, 'Titanium Silver', '6.7" HD+')],
            ['Vision 2S (4/64)', 'itel-vision-2s-4-64', 249000, 204000, spec([4], 64, 'Gradation Blue', '6.52" HD+')],
            ['A05s (4/64)', 'itel-a05s-4-64', 219000, 180000, spec([4], 64, 'Crystal Blue', '6.6" HD+')],
            ['P55 Max (8/256)', 'itel-p55-max-8-256', 459000, 376000, spec([8], 256, 'Aurora Green', '6.6" HD+')],
            ['Super 25 (4/128)', 'itel-super-25-4-128', 349000, 286000, spec([4], 128, 'Alpine Blue', '6.6" HD+')],
        ],
        'oppo' => [
            ['Reno 11 5G (12/256)', 'oppo-reno-11-5g-12-256', 1349000, 1106000, spec([12], 256, 'Wave Green', '6.7" AMOLED 120Hz')],
            ['Reno 10 5G (8/256)', 'oppo-reno-10-5g-8-256', 1099000, 901000, spec([8], 256, 'Silvery Grey', '6.7" AMOLED 120Hz')],
            ['A18 (4/128)', 'oppo-a18-4-128', 399000, 327000, spec([4], 128, 'Glowing Black', '6.56" HD+')],
            ['A38 (6/128)', 'oppo-a38-6-128', 479000, 393000, spec([6], 128, 'Glowing Gold', '6.56" HD+')],
            ['A58 (8/256)', 'oppo-a58-8-256', 629000, 516000, spec([8], 256, 'Dazzling Green', '6.72" FHD+')],
            ['A78 (8/256)', 'oppo-a78-8-256', 729000, 598000, spec([8], 256, 'Mist Black', '6.43" AMOLED')],
            ['A59 5G (6/128)', 'oppo-a59-5g-6-128', 549000, 450000, spec([6], 128, 'Silk Gold', '6.56" HD+')],
            ['F25 Pro 5G (8/128)', 'oppo-f25-pro-5g-8-128', 699000, 573000, spec([8], 128, 'Ocean Blue', '6.7" AMOLED')],
            ['Reno 8 5G (8/256)', 'oppo-reno-8-5g-8-256', 899000, 737000, spec([8], 256, 'Shimmer Gold', '6.4" AMOLED 90Hz')],
            ['A57 (6/128)', 'oppo-a57-6-128', 429000, 352000, spec([6], 128, 'Glowing Green', '6.56" HD+')],
            ['F21 Pro (8/128)', 'oppo-f21-pro-8-128', 649000, 532000, spec([8], 128, 'Sunset Orange', '6.43" AMOLED')],
            ['Reno 7 (8/256)', 'oppo-reno-7-8-256', 849000, 696000, spec([8], 256, 'Startrails Blue', '6.4" AMOLED 90Hz')],
            ['A17 (4/64)', 'oppo-a17-4-64', 319000, 262000, spec([4], 64, 'Lake Blue', '6.56" HD+')],
            ['A77s (8/128)', 'oppo-a77s-8-128', 559000, 458000, spec([8], 128, 'Sunset Orange', '6.56" HD+')],
            ['Find X7 (16/512)', 'oppo-find-x7-16-512', 2899000, 2377000, spec([16], 512, 'Tide Blue', '6.78" LTPO AMOLED')],
        ],
        'vivo' => [
            ['V30e (8/256)', 'vivo-v30e-8-256', 899000, 737000, spec([8], 256, 'Crystal Blue', '6.78" AMOLED 120Hz')],
            ['Y100 (8/256)', 'vivo-y100-8-256', 649000, 532000, spec([8], 256, 'Metal Black', '6.67" AMOLED')],
            ['Y27 (6/128)', 'vivo-y27-6-128', 459000, 376000, spec([6], 128, 'Sea Blue', '6.64" FHD+')],
            ['Y56 5G (8/128)', 'vivo-y56-5g-8-128', 529000, 434000, spec([8], 128, 'Orange Shimmer', '6.58" FHD+')],
            ['X100 (16/512)', 'vivo-x100-16-512', 2499000, 2049000, spec([16], 512, 'Asteroid Black', '6.78" LTPO AMOLED')],
            ['V29 (12/256)', 'vivo-v29-12-256', 1199000, 983000, spec([12], 256, 'Peak Blue', '6.78" AMOLED 120Hz')],
            ['T3 5G (8/128)', 'vivo-t3-5g-8-128', 579000, 475000, spec([8], 128, 'Crystal Flake', '6.67" FHD+ 120Hz')],
            ['Y36 (8/256)', 'vivo-y36-8-256', 599000, 491000, spec([8], 256, 'Meteor Black', '6.64" FHD+')],
            ['V27 (8/256)', 'vivo-v27-8-256', 999000, 819000, spec([8], 256, 'Noble Black', '6.78" AMOLED 120Hz')],
            ['Y200 (8/128)', 'vivo-y200-8-128', 629000, 516000, spec([8], 128, 'Desert Gold', '6.67" AMOLED')],
            ['Y18 (4/128)', 'vivo-y18-4-128', 379000, 311000, spec([4], 128, 'Space Crystal', '6.56" HD+')],
            ['T2x (8/128)', 'vivo-t2x-8-128', 549000, 450000, spec([8], 128, 'Marine Blue', '6.58" FHD+')],
            ['Y16 (4/64)', 'vivo-y16-4-64', 329000, 270000, spec([4], 64, 'Stellar Black', '6.51" HD+')],
            ['Y02t (4/64)', 'vivo-y02t-4-64', 299000, 245000, spec([4], 64, 'Orchid Blue', '6.51" HD+')],
            ['V25 (8/256)', 'vivo-v25-8-256', 849000, 696000, spec([8], 256, 'Aquatic Blue', '6.44" AMOLED 90Hz')],
        ],
        'xiaomi' => [
            ['Redmi Note 13 (8/256)', 'xiaomi-redmi-note-13-8-256', 699000, 573000, spec([8], 256, 'Ice Blue', '6.67" AMOLED 120Hz')],
            ['Redmi 13 (8/256)', 'xiaomi-redmi-13-8-256', 549000, 450000, spec([8], 256, 'Pearl Pink', '6.79" FHD+')],
            ['Redmi 13C (8/256)', 'xiaomi-redmi-13c-8-256', 479000, 393000, spec([8], 256, 'Navy Blue', '6.74" HD+')],
            ['Poco X6 (12/256)', 'xiaomi-poco-x6-12-256', 899000, 737000, spec([12], 256, 'Snowstorm White', '6.67" AMOLED 120Hz')],
            ['Poco M6 (8/256)', 'xiaomi-poco-m6-8-256', 629000, 516000, spec([8], 256, 'Black', '6.79" FHD+')],
            ['Redmi Note 13 Pro (8/256)', 'xiaomi-redmi-note-13-pro-8-256', 949000, 778000, spec([8], 256, 'Forest Green', '6.67" AMOLED 120Hz')],
            ['Poco X6 Pro (12/512)', 'xiaomi-poco-x6-pro-12-512', 1249000, 1024000, spec([12], 512, 'Yellow', '6.67" AMOLED 120Hz')],
            ['Redmi A3 (4/128)', 'xiaomi-redmi-a3-4-128', 299000, 245000, spec([4], 128, 'Midnight Black', '6.71" HD+')],
            ['Redmi 12 (8/256)', 'xiaomi-redmi-12-8-256', 519000, 426000, spec([8], 256, 'Polar Silver', '6.79" FHD+')],
            ['Redmi Note 12 (6/128)', 'xiaomi-redmi-note-12-6-128', 579000, 475000, spec([6], 128, 'Onyx Gray', '6.67" AMOLED')],
            ['Poco C65 (8/256)', 'xiaomi-poco-c65-8-256', 449000, 368000, spec([8], 256, 'Black', '6.74" HD+')],
            ['Redmi 10 2022 (4/128)', 'xiaomi-redmi-10-2022-4-128', 389000, 319000, spec([4], 128, 'Sea Blue', '6.5" FHD+')],
            ['Redmi 12C (4/128)', 'xiaomi-redmi-12c-4-128', 359000, 294000, spec([4], 128, 'Graphite Gray', '6.71" HD+')],
            ['Poco F5 (12/256)', 'xiaomi-poco-f5-12-256', 1399000, 1147000, spec([12], 256, 'Snowstorm White', '6.67" AMOLED 120Hz')],
            ['Mi 13T (8/256)', 'xiaomi-mi-13t-8-256', 1599000, 1311000, spec([8], 256, 'Meadow Green', '6.67" AMOLED 144Hz')],
        ],
        'realme' => [
            ['12 5G (8/256)', 'realme-12-5g-8-256', 799000, 655000, spec([8], 256, 'Navigator Beige', '6.67" FHD+ 120Hz')],
            ['11 (8/256)', 'realme-11-8-256', 649000, 532000, spec([8], 256, 'Glory Gold', '6.72" FHD+')],
            ['C67 (8/256)', 'realme-c67-8-256', 429000, 352000, spec([8], 256, 'Sunny Oasis', '6.72" HD+')],
            ['C55 (8/256)', 'realme-c55-8-256', 399000, 327000, spec([8], 256, 'Rainy Night', '6.72" FHD+')],
            ['C53 (6/128)', 'realme-c53-6-128', 349000, 286000, spec([6], 128, 'Champion Gold', '6.74" HD+')],
            ['Narzo 70 (8/256)', 'realme-narzo-70-8-256', 589000, 483000, spec([8], 256, 'Ice Blue', '6.72" FHD+')],
            ['10 Pro+ (8/256)', 'realme-10-pro-plus-8-256', 729000, 598000, spec([8], 256, 'Hyperspace', '6.7" Curved AMOLED')],
            ['GT Neo 3 (12/256)', 'realme-gt-neo-3-12-256', 999000, 819000, spec([12], 256, 'Sprint White', '6.7" AMOLED 120Hz')],
            ['9i (6/128)', 'realme-9i-6-128', 459000, 376000, spec([6], 128, 'Laser Black', '6.6" FHD+')],
            ['C51 (4/128)', 'realme-c51-4-128', 329000, 270000, spec([4], 128, 'Mint Green', '6.74" HD+')],
            ['Narzo 60x (6/128)', 'realme-narzo-60x-6-128', 419000, 344000, spec([6], 128, 'Stellar Green', '6.72" FHD+')],
            ['8i (6/128)', 'realme-8i-6-128', 389000, 319000, spec([6], 128, 'Space Black', '6.6" FHD+ 120Hz')],
            ['C33 (4/128)', 'realme-c33-4-128', 319000, 262000, spec([4], 128, 'Aqua Blue', '6.5" HD+')],
            ['7i (4/128)', 'realme-7i-4-128', 359000, 294000, spec([4], 128, 'Fusion Green', '6.5" HD+')],
            ['GT 2 (12/256)', 'realme-gt-2-12-256', 1199000, 983000, spec([12], 256, 'Paper White', '6.62" AMOLED 120Hz')],
        ],
        'nokia' => [
            ['G42 5G (6/128)', 'nokia-g42-5g-6-128', 549000, 450000, spec([6], 128, 'So Purple', '6.56" HD+ 90Hz')],
            ['G60 5G (6/128)', 'nokia-g60-5g-6-128', 629000, 516000, spec([6], 128, 'Black', '6.58" FHD+ 120Hz')],
            ['G50 (4/128)', 'nokia-g50-4-128', 479000, 393000, spec([4], 128, 'Midnight Sun', '6.82" HD+')],
            ['X30 (8/256)', 'nokia-x30-8-256', 899000, 737000, spec([8], 256, 'Cloudy Blue', '6.43" AMOLED 90Hz')],
            ['C32 (4/64)', 'nokia-c32-4-64', 279000, 229000, spec([4], 64, 'Beach Pink', '6.5" HD+')],
            ['C12 (2/64)', 'nokia-c12-2-64', 229000, 188000, spec([2], 64, 'Charcoal', '6.3" HD+')],
            ['G21 (4/64)', 'nokia-g21-4-64', 349000, 286000, spec([4], 64, 'Nordic Blue', '6.5" HD+')],
            ['G20 (4/64)', 'nokia-g20-4-64', 329000, 270000, spec([4], 64, 'Night', '6.52" HD+')],
            ['C22 (3/64)', 'nokia-c22-3-64', 259000, 212000, spec([3], 64, 'Midnight Black', '6.5" HD+')],
            ['X20 (8/128)', 'nokia-x20-8-128', 749000, 614000, spec([8], 128, 'Midnight Sun', '6.67" FHD+')],
            ['G11 Plus (4/64)', 'nokia-g11-plus-4-64', 319000, 262000, spec([4], 64, 'Lake Blue', '6.52" HD+')],
            ['C31 (4/64)', 'nokia-c31-4-64', 299000, 245000, spec([4], 64, 'Cyan', '6.75" HD+')],
            ['G10 (4/64)', 'nokia-g10-4-64', 309000, 253000, spec([4], 64, 'Night', '6.52" HD+')],
            ['2660 Flip 4G', 'nokia-2660-flip-4g', 189000, 155000, '{"ram":"48MB","storage":"128MB","color":"Black","display":"2.8\\u0022 dual display"}'],
            ['8210 4G', 'nokia-8210-4g', 169000, 139000, '{"ram":"48MB","storage":"128MB","color":"Sand","display":"2.8\\u0022 QVGA"}'],
        ],
        'huawei' => [
            ['nova 12i (8/256)', 'huawei-nova-12i-8-256', 899000, 737000, spec([8], 256, 'Green', '6.7" FHD+ LCD')],
            ['nova 12s (8/256)', 'huawei-nova-12s-8-256', 1149000, 942000, spec([8], 256, 'White', '6.7" OLED 120Hz')],
            ['nova 11 Pro (8/256)', 'huawei-nova-11-pro-8-256', 1049000, 860000, spec([8], 256, 'Green', '6.78" OLED 120Hz')],
            ['nova 9 SE (8/128)', 'huawei-nova-9-se-8-128', 649000, 532000, spec([8], 128, 'Crystal Blue', '6.78" FHD+ LCD')],
            ['nova 8i (8/128)', 'huawei-nova-8i-8-128', 579000, 475000, spec([8], 128, 'Moonlight Silver', '6.67" FHD+')],
            ['Enjoy 70z (8/256)', 'huawei-enjoy-70z-8-256', 729000, 598000, spec([8], 256, 'Green', '6.75" HD+')],
            ['Enjoy 60X (8/128)', 'huawei-enjoy-60x-8-128', 559000, 458000, spec([8], 128, 'Green', '6.95" FHD+')],
            ['nova Y91 (8/256)', 'huawei-nova-y91-8-256', 799000, 655000, spec([8], 256, 'Black', '6.95" FHD+')],
            ['nova Y72 (8/256)', 'huawei-nova-y72-8-256', 689000, 565000, spec([8], 256, 'Green', '6.75" HD+')],
            ['P Smart 2021 (4/128)', 'huawei-p-smart-2021-4-128', 429000, 352000, spec([4], 128, 'Crush Green', '6.67" FHD+')],
            ['Y9s (6/128)', 'huawei-y9s-6-128', 499000, 409000, spec([6], 128, 'Breathing Crystal', '6.59" FHD+')],
            ['Y7p (4/64)', 'huawei-y7p-4-64', 359000, 294000, spec([4], 64, 'Midnight Black', '6.39" HD+')],
            ['Y6p (4/64)', 'huawei-y6p-4-64', 339000, 278000, spec([4], 64, 'Phantom Purple', '6.3" HD+')],
            ['P40 lite (6/128)', 'huawei-p40-lite-6-128', 749000, 614000, spec([6], 128, 'Sakura Pink', '6.4" FHD+')],
            ['Mate 50 (8/256)', 'huawei-mate-50-8-256', 2199000, 1803000, spec([8], 256, 'Silver', '6.7" OLED 90Hz')],
        ],
        default => [],
    };
}

$brands = [
    ['Samsung', 'samsung'],
    ['Tecno', 'tecno'],
    ['Infinix', 'infinix'],
    ['Itel', 'itel'],
    ['Oppo', 'oppo'],
    ['Vivo', 'vivo'],
    ['Xiaomi', 'xiaomi'],
    ['Realme', 'realme'],
    ['Nokia', 'nokia'],
    ['Huawei', 'huawei'],
];

$dealers = [
    ['Simu Kitaa Express', 'DMO-TZ-001', '+255 762 100 001', 'simu.kitaa.kinondoni@merchant.opticedgecredity.com', 'Kinondoni, Dar es Salaam — Msikiti Mwaungani'],
    ['Mawasiliano Plaza', 'DMO-TZ-002', '+255 762 100 002', 'mawasiliano.plaza@merchant.opticedgecredity.com', 'Ubungo Riverside, Dar es Salaam'],
    ['Simu Smart Temeke', 'DMO-TZ-003', '+255 762 100 003', 'simu.smart.temeke@merchant.opticedgecredity.com', 'Temeke Mwisho, karibu na stendi'],
    ['Agakuki Mobile World', 'DMO-TZ-004', '+255 762 100 004', 'agakuki.mobile@merchant.opticedgecredity.com', 'Mwenge, Morocco'],
    ['Karibu Simu Shop', 'DMO-TZ-005', '+255 762 100 005', 'karibu.simu.posta@merchant.opticedgecredity.com', 'Posta Mpya, Kariakoo'],
    ['Pembeni Tech Corner', 'DMO-TZ-006', '+255 762 100 006', 'pembeni.tech@merchant.opticedgecredity.com', 'Masaki, Slipway'],
    ['Dar Fast Mobile', 'DMO-TZ-007', '+255 762 100 007', 'dar.fast.mobile@merchant.opticedgecredity.com', 'Kariakoo Aggrey na Likoma'],
    ['Nuru Communications', 'DMO-TZ-008', '+255 762 100 008', 'nuru.comms@merchant.opticedgecredity.com', 'Magomeni Mapipa'],
    ['Twiga Phones Mikocheni', 'DMO-TZ-009', '+255 762 100 009', 'twiga.phones@merchant.opticedgecredity.com', 'Mikocheni A, Ali Hassan Mwinyi Road'],
    ['Bahari Electronics', 'DMO-TZ-010', '+255 762 100 010', 'bahari.electronics@merchant.opticedgecredity.com', 'Msasani, Slipway'],
    ['Jua Kali Mobile Chanika', 'DMO-TZ-011', '+255 762 100 011', 'jua.kali.chanika@merchant.opticedgecredity.com', 'Chanika, Ilala'],
    ['Mlimani Phone Hub', 'DMO-TZ-012', '+255 762 100 012', 'mlimani.hub@merchant.opticedgecredity.com', 'Mlimani City, Ubungo'],
    ['Simu Bora Kijitonyama', 'DMO-TZ-013', '+255 762 100 013', 'simu.bora.kijitonyama@merchant.opticedgecredity.com', 'Kijitonyama, Ali Hassan Mwinyi'],
    ['Makumbusho Mobile Centre', 'DMO-TZ-014', '+255 762 100 014', 'makumbusho.mobile@merchant.opticedgecredity.com', 'Kijichi, Makumbusho'],
    ['Vikokotoni Digital Store', 'DMO-TZ-015', '+255 762 100 015', 'vikokotoni.digital@merchant.opticedgecredity.com', 'Stone Town, Zanzibar'],
];

// Stable UUIDs (version 4 layout: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx, y in 8-b)
$dealerUuids = [];
foreach (range(1, 15) as $i) {
    $dealerUuids[$i] = sprintf('71d9d8a0-%04x-4000-8000-%012x', $i, $i);
}
$brandUuids = [];
foreach (range(1, 10) as $i) {
    $brandUuids[$i] = sprintf('72d9d8a0-%04x-4000-8000-%012x', $i, $i + 100);
}
$modelSeq = 1;
$modelUuid = static function () use (&$modelSeq): string {
    $u = sprintf('73d9d8a0-%04x-4000-8000-%012x', $modelSeq, $modelSeq + 1000);
    $modelSeq++;

    return $u;
};

$adminUuids = [
    '74d9d8a0-0001-4000-8000-000000000001',
    '74d9d8a0-0002-4000-8000-000000000002',
    '74d9d8a0-0003-4000-8000-000000000003',
];
$ownerUuids = [];
foreach (range(1, 15) as $i) {
    $ownerUuids[$i] = sprintf('75d9d8a0-%04x-4000-8000-%012x', $i, $i + 2000);
}

$lines = [];
$lines[] = '-- ========================================================================';
$lines[] = '-- Opticedge Credit — bulk reference data (MySQL 8+)';
$lines[] = '-- Majina halisi ya brands/models (specs za soko) + maduka ya demo Tanzania.';
$lines[] = '-- ========================================================================';
$lines[] = '-- Kabla: php artisan migrate && php artisan db:seed --class=RolesAndPermissionsSeeder';
$lines[] = '-- Import: mysql -u USER -p DATABASE < database/sql/oe_bulk_reference_data.sql';
$lines[] = '--';
$lines[] = '-- Ikiwa brands/slugs tayari zipo (mf. Samsung), futa rekodi za majaribito au';
$lines[] = '-- badilisha slugs kwenye generate_oe_bulk_reference_data.php kisha zalisha upya.';
$lines[] = '--';
$lines[] = '-- Users: INSERT ... ON DUPLICATE KEY UPDATE (barua pepe / employee_code) —';
$lines[] = '--   unaweza ku-import tena bila #1062 duplicate email.';
$lines[] = '--';
$lines[] = '-- Nenosiri: Opticedge@2026 (admins 3), DealerOwner@2026 (owners 15).';
$lines[] = '-- Regenerate: php database/sql/generate_oe_bulk_reference_data.php';
$lines[] = '-- ========================================================================';
$lines[] = '';
$lines[] = 'SET NAMES utf8mb4;';
$lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
$lines[] = '';

$lines[] = '-- ---------------------------------------------------------------- dealers';
$lines[] = 'INSERT INTO `dealers` (`id`, `owner_user_id`, `name`, `code`, `phone`, `email`, `address`, `tin_number`, `commission_rate`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES';
$dr = [];
foreach ($dealers as $idx => [$name, $code, $phone, $email, $addr]) {
    $n = $idx + 1;
    $tin = sprintf('1777%011d', $n);
    $dr[] = "('{$dealerUuids[$n]}',NULL,".sqlq($name).','.sqlq($code).','.sqlq($phone).','.sqlq($email).','.sqlq($addr).','.sqlq($tin).",'2.50','active','{$now}','{$now}',NULL)";
}
$lines[] = implode(",\n", $dr).';';
$lines[] = '';

$lines[] = '-- ---------------------------------------------------------------- brands';
$lines[] = 'INSERT INTO `brands` (`id`, `name`, `slug`, `logo_url`, `is_active`, `created_at`, `updated_at`) VALUES';
$br = [];
foreach ($brands as $i => [$name, $slug]) {
    $id = $brandUuids[$i + 1];
    $br[] = "('{$id}',".sqlq($name).','.sqlq($slug).",NULL,1,'{$now}','{$now}')";
}
$lines[] = implode(",\n", $br).';';
$lines[] = '';

$lines[] = '-- ---------------------------------------------------------------- phone_models';
$lines[] = 'INSERT INTO `phone_models` (`id`, `brand_id`, `name`, `slug`, `retail_price`, `cost_price`, `specifications`, `is_active`, `created_at`, `updated_at`) VALUES';
$mr = [];
foreach ($brands as $i => [$name, $slug]) {
    $bid = $brandUuids[$i + 1];
    foreach (modelsForBrand($slug) as [$mname, $mslug, $retail, $cost, $specJson]) {
        $id = $modelUuid();
        $mr[] = "('{$id}','{$bid}',".sqlq($mname).','.sqlq($mslug).",'".number_format($retail, 2, '.', '')."','".number_format($cost, 2, '.', '')."',".sqlq($specJson).",1,'{$now}','{$now}')";
    }
}
$lines[] = implode(",\n", $mr).';';
$lines[] = '';

$lines[] = '-- ---------------------------------------------------------------- users';
$lines[] = 'INSERT INTO `users` (`id`, `dealer_id`, `joined_at`, `role`, `name`, `email`, `phone`, `employee_code`, `is_active`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `created_at`, `updated_at`, `avatar_url`) VALUES';
$ur = [];
$admins = [
    [$adminUuids[0], 'admin', 'Msimamizi Mkuu (Demo)', 'sql.superadmin@opticedgecredity.com', '+255 700 900 001', 'EMP-DMO-ADM-01'],
    [$adminUuids[1], 'admin', 'Msimamizi Operesheni (Demo)', 'sql.opsadmin@opticedgecredity.com', '+255 700 900 002', 'EMP-DMO-ADM-02'],
    [$adminUuids[2], 'admin', 'Msimamizi Fedha (Demo)', 'sql.finance@opticedgecredity.com', '+255 700 900 003', 'EMP-DMO-ADM-03'],
];
foreach ($admins as [$uid, $role, $name, $em, $ph, $ec]) {
    $ur[] = "('{$uid}',NULL,'2026-01-01',".sqlq($role).','.sqlq($name).','.sqlq($em).','.sqlq($ph).','.sqlq($ec).",1,'{$now}',".sqlq($pwOpt).",NULL,NULL,NULL,NULL,'{$now}','{$now}',NULL)";
}
foreach (range(1, 15) as $d) {
    $uid = $ownerUuids[$d];
    $did = $dealerUuids[$d];
    $shop = $dealers[$d - 1][0];
    $name = 'Mwenye '.$shop;
    $em = sprintf('owner.dmo-tz-%02d@opticedgecredity.com', $d);
    $ph = '+255 763 '.str_pad((string) (200000 + $d), 6, '0', STR_PAD_LEFT);
    $ec = 'EMP-DMO-OWN-'.str_pad((string) $d, 3, '0', STR_PAD_LEFT);
    $ur[] = "('{$uid}','{$did}','2026-01-01','owner',".sqlq($name).','.sqlq($em).','.sqlq($ph).','.sqlq($ec).",1,'{$now}',".sqlq($pwOwner).",NULL,NULL,NULL,NULL,'{$now}','{$now}',NULL)";
}
$userUpsertSuffix = <<<'SQL'
ON DUPLICATE KEY UPDATE
  `dealer_id` = VALUES(`dealer_id`),
  `joined_at` = VALUES(`joined_at`),
  `role` = VALUES(`role`),
  `name` = VALUES(`name`),
  `phone` = VALUES(`phone`),
  `employee_code` = VALUES(`employee_code`),
  `is_active` = VALUES(`is_active`),
  `email_verified_at` = VALUES(`email_verified_at`),
  `password` = VALUES(`password`),
  `two_factor_secret` = VALUES(`two_factor_secret`),
  `two_factor_recovery_codes` = VALUES(`two_factor_recovery_codes`),
  `two_factor_confirmed_at` = VALUES(`two_factor_confirmed_at`),
  `remember_token` = VALUES(`remember_token`),
  `updated_at` = VALUES(`updated_at`),
  `avatar_url` = VALUES(`avatar_url`)
SQL;
$lines[] = implode(",\n", $ur)."\n".$userUpsertSuffix.';';
$lines[] = '';

foreach (range(1, 15) as $d) {
    $code = sprintf('DMO-TZ-%03d', $d);
    $ownerEmail = sprintf('owner.dmo-tz-%02d@opticedgecredity.com', $d);
    $lines[] = 'UPDATE `dealers` SET `owner_user_id` = (SELECT `id` FROM `users` WHERE `email` = '.sqlq($ownerEmail).' LIMIT 1) WHERE `code` = '.sqlq($code).';';
}
$lines[] = '';

$lines[] = 'INSERT IGNORE INTO `model_has_roles` (`role_id`, `model_type`, `model_uuid`)';
$lines[] = 'SELECT r.`id`, \'App\\\\Models\\\\User\', u.`id` FROM `roles` r CROSS JOIN `users` u';
$lines[] = 'WHERE r.`name` = \'admin\' AND r.`guard_name` = \'web\'';
$lines[] = 'AND u.`email` IN (\'sql.superadmin@opticedgecredity.com\',\'sql.opsadmin@opticedgecredity.com\',\'sql.finance@opticedgecredity.com\');';
$lines[] = '';
$lines[] = 'INSERT IGNORE INTO `model_has_roles` (`role_id`, `model_type`, `model_uuid`)';
$lines[] = 'SELECT r.`id`, \'App\\\\Models\\\\User\', u.`id` FROM `roles` r CROSS JOIN `users` u';
$lines[] = 'WHERE r.`name` = \'owner\' AND r.`guard_name` = \'web\'';
$lines[] = 'AND u.`email` LIKE \'owner.dmo-tz-%@opticedgecredity.com\';';
$lines[] = '';

$lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
$lines[] = '';
$lines[] = '-- Admins: sql.superadmin@ / sql.opsadmin@ / sql.finance@ — Opticedge@2026';
$lines[] = '-- Owners: owner.dmo-tz-01@ … owner.dmo-tz-15@ — DealerOwner@2026';

$out = dirname(__DIR__).'/sql/oe_bulk_reference_data.sql';
file_put_contents($out, implode("\n", $lines));
echo "Wrote {$out} (".strlen(implode("\n", $lines))." bytes)\n";
