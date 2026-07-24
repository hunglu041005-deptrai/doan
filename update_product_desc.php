<?php
require __DIR__ . '/db.php';

/**
 * Mô tả chi tiết cho từng sản phẩm — MỖI CÁI KHÁC NHAU
 * key = chuỗi tìm kiếm trong tên sản phẩm (case-insensitive)
 */
$catalog = [

    /* ══════════════ VỢT CẦU LÔNG ══════════════ */

    'Arcsaber 11' => [
        'short' => 'Kiểm soát hoàn hảo – vợt huyền thoại của Yonex với công nghệ X-Fullerene cho cảm giác đánh giòn và chính xác.',
        'desc'  => 'Vợt Yonex Arcsaber 11 ứng dụng công nghệ X-Fullerene kết hợp nano carbon siêu nhẹ, giúp trục vợt uốn cong và phục hồi nhanh chóng sau mỗi cú đánh. Khung Sonic Metal tạo âm thanh giòn và cảm giác tiếp xúc cầu rõ ràng. Điểm cân bằng trung tính phù hợp cho lối chơi toàn diện, đặc biệt hiệu quả ở các pha kiểm soát lưới và lừa bài. Trọng lượng: 88g (3U G4). Căng dây đề xuất: 20–28 lbs. Bảo hành chính hãng 12 tháng.',
    ],

    'Victor Thruster K9900' => [
        'short' => 'Sức mạnh tấn công vượt trội – lựa chọn của các tay vợt chuyên nghiệp tại các giải đấu quốc tế.',
        'desc'  => 'Vợt Victor Thruster K9900 kết hợp vật liệu nano carbon thế hệ mới với sợi thủy tinh ceramic, cho độ cứng khung tối ưu mà không tăng trọng lượng. Thiết kế khung khí động học SWORD giảm sức cản không khí, tốc độ vung vợt tăng đáng kể. Lực đập cầu mạnh và dứt khoát, phù hợp lối chơi tấn công chủ động. Trọng lượng: 83g (4U G5). Căng dây đề xuất: 24–30 lbs. Đi kèm túi bảo vệ đầu vợt.',
    ],

    'Lining Windstorm 78' => [
        'short' => 'Nhẹ như gió với chỉ 78g – vợt siêu nhẹ cho lối chơi phản xạ tốc độ cao tại vùng lưới.',
        'desc'  => 'Vợt Lining Windstorm 78 đạt trọng lượng chỉ 78g (5U) nhờ công nghệ W-Shape Beam tăng độ cứng khung mà không tăng vật liệu. Điểm cân bằng head-light giúp cổ tay linh hoạt trong các pha phản xạ nhanh và lừa bài đối thủ. Khung oval rộng mở rộng sweet spot, giảm thiểu lỗi kỹ thuật khi đánh lệch tâm. Thiết kế đẹp mắt, phù hợp cho người chơi ưu tiên tốc độ và kỹ thuật tinh tế. Căng dây đề xuất: 18–25 lbs.',
    ],

    'Mizuno Fortius' => [
        'short' => 'Bền bỉ và tin cậy – vợt Mizuno dành cho người tập luyện cường độ cao với mức giá phải chăng.',
        'desc'  => 'Vợt Mizuno Fortius Tour F làm từ hợp kim nhôm cao cấp pha trộn carbon composite, đảm bảo độ bền tốt cho tập luyện hàng ngày. Grip vợt PU cao su tổng hợp êm ái, thấm mồ hôi tốt, giảm mỏi tay khi chơi dài giờ. Khung vợt cứng chắc phù hợp người mới bắt đầu muốn có cảm giác kiểm soát cầu tốt và người chơi trung cấp muốn nâng cao kỹ năng. Kèm theo bao đựng đầu vợt tiện lợi. Trọng lượng: 92g (2U).',
    ],

    /* ══════════════ GIÀY THỂ THAO ══════════════ */

    'Power Cushion 65Z3' => [
        'short' => 'Đệm êm ái hàng đầu – Power Cushion thế hệ mới hấp thụ xung lực, bảo vệ khớp gối tối đa.',
        'desc'  => 'Giày Yonex Power Cushion 65Z3 trang bị công nghệ đệm Power Cushion hấp thụ lực tác động đến 47% so với đế thường, bảo vệ hiệu quả khớp gối và cổ chân trong các bước di chuyển đột ngột. Đế cao su Hexagrip non-marking bám sàn gỗ và nhựa tổng hợp hoàn hảo. Upper dệt 3D Hexamesh thoáng khí, hút ẩm nhanh giữ bàn chân khô ráo. Hệ thống dây buộc Assist System cố định cổ chân, hạn chế trẹo chân. Phù hợp mọi kiểu chân và cấp độ chơi.',
    ],

    'Victor A922' => [
        'short' => 'Ổn định, bền bỉ suốt trận – giày Victor A922 cho người chơi cầu lông hàng ngày đến bán chuyên.',
        'desc'  => 'Giày Victor A922 trang bị đế ENERGYMAX 6 phân tán lực đều toàn bộ bàn chân, giảm thiểu chấn thương khi dừng và đổi hướng đột ngột. Phần upper mesh kết hợp lớp gia cố TPU tại mũi và gót tăng độ bền mà không nặng thêm. Lót trong kháng khuẩn AC+ khử mùi và duy trì vệ sinh sau nhiều buổi tập. Đế phẳng thiết kế để bám sàn cầu lông, phù hợp với mọi loại sàn trong nhà. Thích hợp người chơi phong trào đến bán chuyên.',
    ],

    /* ══════════════ QUẦN ÁO ══════════════ */

    'Áo Yonex 10274EX' => [
        'short' => 'Thoáng mát suốt buổi tập – công nghệ COOLING vải thoát nhiệt nhanh, thấm hút mồ hôi hiệu quả.',
        'desc'  => 'Áo Yonex 10274EX sử dụng vải polyester cao cấp với công nghệ COOLING nhanh khô, thoát nhiệt hiệu quả giúp duy trì thân nhiệt ổn định ngay cả khi thi đấu cường độ cao. In sublimation giữ màu sắc không phai sau hơn 50 lần giặt. Co giãn 4 chiều không hạn chế chuyển động vung tay và xoay người. Cổ áo chữ V thoải mái, phần gấu vừa vặn không tuột ra ngoài khi vận động mạnh. Nhẹ và thoáng, lý tưởng cho mọi thời tiết.',
    ],

    /* ══════════════ PHỤ KIỆN ══════════════ */

    'BAG92026EX' => [
        'short' => 'Túi đựng 6 cây vợt – ngăn cách nhiệt riêng, vải Oxford chống thấm, bảo vệ thiết bị tối đa.',
        'desc'  => 'Túi vợt Yonex BAG92026EX chứa tối đa 6 cây vợt với ngăn đựng nhiệt tách biệt bảo vệ cước căng khỏi nhiệt độ cao và tia UV. Vải chính Oxford 600D chống thấm nước, kháng trầy xước và bền màu qua nhiều năm sử dụng. Ngăn phụ rộng rãi đựng giày, quần áo và phụ kiện cá nhân. Hai quai đeo đa năng: đeo vai hoặc đeo ba lô tiện lợi. Phần đáy túi gia cố thêm lớp cao su chống mài mòn khi đặt xuống sàn. Màu sắc đa dạng, thiết kế thời trang hiện đại.',
    ],
];

// ── Lấy tất cả sản phẩm ──
$all = $mysqli->query("SELECT id, name FROM products WHERE status = 1 ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$updated = 0;
$skipped = [];

foreach ($all as $p) {
    $matched = false;

    foreach ($catalog as $keyword => $texts) {
        if (stripos($p['name'], $keyword) !== false) {
            $stmt = $mysqli->prepare('UPDATE products SET short_description = ?, description = ? WHERE id = ?');
            $stmt->bind_param('ssi', $texts['short'], $texts['desc'], $p['id']);
            $stmt->execute();
            $stmt->close();
            $updated++;
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $skipped[] = "#{$p['id']} – {$p['name']}";
    }
}

echo '<h2 style="color:#10b981">✅ Cập nhật hoàn tất</h2>';
echo "<p><strong>Đã cập nhật:</strong> $updated sản phẩm</p>";

if (!empty($skipped)) {
    echo '<p><strong style="color:#ef4444">Chưa có mô tả (' . count($skipped) . '):</strong></p><ul>';
    foreach ($skipped as $s) echo "<li>$s</li>";
    echo '</ul>';
    echo '<p style="color:#6b7280;font-size:.85rem;">→ Hãy nhắn tên sản phẩm này để tôi bổ sung mô tả.</p>';
} else {
    echo '<p style="color:#10b981">🎉 Tất cả sản phẩm đã có mô tả!</p>';
}

echo '<br><a href="equipment.php" style="background:#dc3545;color:#fff;padding:8px 16px;border-radius:8px;text-decoration:none;margin-right:8px;">Xem shop</a>';
echo '<a href="admin/shop.php" style="background:#6366f1;color:#fff;padding:8px 16px;border-radius:8px;text-decoration:none;">Admin shop</a>';
