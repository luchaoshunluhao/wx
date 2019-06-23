<?php 

if (isset($_FILES['file'])) {
    include 'wechat.php';
    $pdo = include 'db.php';
    $type = $_POST['type'];
    $is_forever = $_POST['is_forever'];
    $filename = __DIR__ . '/' . $_FILES['file']['name'];
    $ret = (new wechat)->upFile($type, $filename, $is_forever);
    // 此处应价格对 ret 返回结果的判断
    $ret['created_at'] = $ret['created_at'] ?? time();
    $ret['url'] = $ret['url'] ?? '';
    $sql = "INSERT INTO `material` (`is_forever`, `type`, `media_id`, `url`, `filepath`, `created_at`) VALUE (?, ?, ?, ?, ?, ?)";
    move_uploaded_file($_FILES['file']['tmp_name'], $filename);
    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute([$is_forever, $type, $ret['media_id'], $ret['url'], $filename, $ret['created_at']]);
    var_dump($res);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>上传素材</title>
</head>
<body>
    <form action="" enctype="multipart/form-data" method="post">
        <p>
        <select name="is_forever">
                <option value="0">临时</option>
                <option value="1">永久</option>
            </select>
            <select name="type">
                <option value="image">图片</option>
                <option value="voice">语音</option>
                <option value="video">视频</option>
            </select>
            <input type="file" name="file" id="">
            <input type="submit">
        </p>
    </form>
</body>
</html>