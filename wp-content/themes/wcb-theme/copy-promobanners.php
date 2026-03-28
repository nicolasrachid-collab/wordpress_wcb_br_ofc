<?php
// One-time copy of promo banners — delete after use
$brain = 'C:/Users/Nicolas Rachid/.gemini/antigravity/brain/4140a97f-62d8-46c6-9f0e-a94685212e77/';
$dest = __DIR__ . '/images/';

copy($brain . 'promo_banner_1_1773061825979.png', $dest . 'promo-banner-1.jpg');
copy($brain . 'promo_banner_2_1773061843373.png', $dest . 'promo-banner-2.jpg');

echo 'Banners copied!';
