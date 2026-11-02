<?php

require_once __DIR__ . '/src_database.php';
require_once __DIR__ . '/src_task.php';

function render_focus_slider(): void
{
    $uid = (int) ($_COOKIE['takify_last_user'] ?? 0);
    $tasks = [];
    if ($uid > 0) {
        try {
            $tasks = TaskRepository::focus($uid, 10);
        } catch (Throwable $e) {
            $tasks = [];
        }
    }
    if (!$tasks) {
        return;
    }
    ?>
    <div class="focus-slider" aria-live="polite">
        <?php foreach ($tasks as $i => $t):
            $p = max(0, min(100, (int) $t['progress']));
            $info = (int) $t['sub_total'] > 0
                ? (int) $t['sub_done'] . ' dari ' . (int) $t['sub_total'] . ' sub-tugas selesai'
                : status_label($t['status']); ?>
            <article class="focus-slide <?= $i === 0 ? 'is-active' : '' ?>"><span class="chip"><?= e($t['kategori_nama'] ?: 'Tanpa kategori') ?></span>
                <h3><?= e($t['title']) ?></h3>
                <p><?= e($info) ?></p>
                <div class="progress"><span style="width:<?= $p ?>%"></span></div>
                <small><?= $p ?>% selesai · <?= e(date('d M, H.i', strtotime($t['due_date']))) ?> WIB</small>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (count($tasks) > 1): ?>
        <div class="focus-slider-dots" aria-label="Navigasi tugas">
            <?php foreach ($tasks as $i => $t): ?>
                <button class="<?= $i === 0 ? 'is-active' : '' ?>" type="button" aria-label="Tugas <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <script>
            (function () {
                const slides = [...document.querySelectorAll('.focus-slide')];
                const dots = [...document.querySelectorAll('.focus-slider-dots button')];
                let activeSlide = 0;
                let sliderTimer;
                const showSlide = (index) => {
                    activeSlide = (index + slides.length) % slides.length;
                    slides.forEach((slide, i) => slide.classList.toggle('is-active', i === activeSlide));
                    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === activeSlide));
                };
                const startSlider = () => {
                    clearInterval(sliderTimer);
                    sliderTimer = setInterval(() => showSlide(activeSlide + 1), 3500);
                };
                dots.forEach((dot, index) => dot.addEventListener('click', () => { showSlide(index); startSlider(); }));
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) startSlider();
            })();
        </script>
    <?php endif;
}
