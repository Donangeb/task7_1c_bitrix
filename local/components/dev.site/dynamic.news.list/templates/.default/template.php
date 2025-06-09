<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!empty($arResult['ITEMS_BY_IBLOCK'])): ?>
    <?php foreach ($arResult['ITEMS_BY_IBLOCK'] as $iblockId => $items): ?>
        <div class="iblock-section" data-iblock-id="<?= $iblockId ?>">
            <h2>Инфоблок #<?= $iblockId ?></h2>
            <div class="news-list">
                <?php foreach ($items as $item): ?>
                    <div class="news-item">
                        <a href="<?= $item['DETAIL_PAGE_URL'] ?>">
                        <?= $item['NAME'] ?></a>
                        <div class="preview-text"><?= $item['PREVIEW_TEXT'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Элементы не найдены</p>
<?php endif; ?>