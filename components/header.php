<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Lost & Found System'); ?></title>
    <link rel="stylesheet" href="style.css">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>
