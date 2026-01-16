<?php
// ====== CONFIG ======
$dir = __DIR__ . "/pages";
$webDir = "pages";

// รองรับ jpg/png/webp
$files = array_merge(
    glob($dir . "/*.jpg"),
    glob($dir . "/*.jpeg"),
    glob($dir . "/*.png"),
    glob($dir . "/*.webp")
);

// เรียงไฟล์ตามชื่อ (แนะนำตั้งชื่อ 01.jpg, 02.jpg, ...)
natsort($files);
$files = array_values($files);

$pages = array_map(function ($p) use ($webDir) {
    return $webDir . "/" . rawurlencode(basename($p));
}, $files);

$total = count($pages);
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1" />
    <title>Flipbook (Single Page)</title>

    <!-- CSS แยกไฟล์ -->
    <link rel="stylesheet" href="style.css">

    <!-- Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/4/turn.min.js"></script>
</head>

<body>

    <!-- TOP BAR -->
    <div class="ui">
        <div class="left">
            <div class="pill">
                หน้า <span id="cur">1</span> / <span id="tot"><?= max(1, (int) $total) ?></span>
            </div>
        </div>

        <div class="right">
            <button id="zoom">ซูม</button>
        </div>
    </div>

    <!-- STAGE -->
    <div class="stage">
        <div id="zoomWrap" class="zoomWrap">
            <div id="flipbook">
                          <?php if ($total === 0): ?>
                    <div class="page" style="width:320px;height:480px;display:grid;place-items:center;">
                        ใส่รูปหน้าแคตตาล็อกในโฟลเดอร์ <b>/pages</b>
                    </div>
                          <?php else: ?>
                                <?php foreach ($pages as $src): ?>
                        <div class="page"><img src="<?= htmlspecialchars($src) ?>" alt=""></div>
                                <?php endforeach; ?>
                          <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BOTTOM BAR -->
    <div class="bottom">
        <button class="navbtn" id="prev">◀</button>

        <div class="pill" style="flex:1; justify-content:center;">
            <input id="slider" type="range" min="1" max="<?= max(1, (int) $total) ?>" value="1" />
        </div>

        <button class="navbtn" id="next">▶</button>
    </div>

    <script>
        (function () {
            const TOTAL = <?= (int) max(1, $total) ?>;
            const $fb = $("#flipbook");
            const $cur = $("#cur");
            const $slider = $("#slider");
            const $zoomWrap = $("#zoomWrap");
            let zoomed = false;

            // A4 แนวตั้ง (ใกล้เคียง): width/height ≈ 0.707
            const PAGE_RATIO = 0.707;

            function calcSize() {
                // ให้สอดคล้องกับ padding บน/ล่างใน CSS (ตัวแปร --topPad/--bottomPad)
                const topPad = 58;
                const bottomPad = 58;

                const availH = Math.max(320, window.innerHeight - topPad - bottomPad - 24);
                const h = Math.min(780, availH);
                const w = Math.min(window.innerWidth - 24, Math.round(h * PAGE_RATIO));
                return { w, h };
            }

            function updateUI(p) {
                $cur.text(p);
                $slider.val(p);
            }

            function init() {
                if (!$fb.length) return;

                if ($fb.data("turn")) $fb.turn("destroy");

                const { w, h } = calcSize();
                $fb.css({ width: w, height: h });

                $fb.turn({
                    width: w,
                    height: h,
                    autoCenter: true,

                    // สำคัญ: เปิดทีละหน้า
                    display: "single",

                    gradients: true,
                    acceleration: true,
                    elevation: 60
                });

                // เริ่มหน้า 1
                $fb.turn("page", 1);
                updateUI(1);
            }

            // ปุ่มก่อนหน้า/ถัดไป
            $("#prev").on("click", () => $fb.turn("previous"));
            $("#next").on("click", () => $fb.turn("next"));

            // Slider เลื่อนไปหน้าที่ต้องการ
            $slider.on("input", function () {
                const p = parseInt(this.value, 10) || 1;
                $fb.turn("page", p);
            });

            // ซูมแบบง่าย (scale)
            $("#zoom").on("click", () => {
                zoomed = !zoomed;
                $zoomWrap.css("transform", zoomed ? "scale(1.18)" : "scale(1)");
                $("#zoom").text(zoomed ? "ย่อ" : "ซูม");
            });

            // อัปเดต UI เมื่อพลิกหน้า
            $fb.on("turning", function (e, page) {
                updateUI(page);
            });

            // คีย์บอร์ด (PC)
            window.addEventListener("keydown", (e) => {
                if (e.key === "ArrowLeft") $fb.turn("previous");
                if (e.key === "ArrowRight") $fb.turn("next");
            });

            // Responsive
            window.addEventListener("resize", () => {
                clearTimeout(window.__t);
                window.__t = setTimeout(() => init(), 120);
            });

            init();
        })();
    </script>

</body>

</html>