<?php

// 设置笔记保存的目录路径，建议使用绝对路径，并确保该路径位于文档根目录之外以提高安全性。
$notes_directory = __DIR__ . '/_tmp';

// 获取保存笔记的真实路径
$absolute_notes_directory = realpath($notes_directory);

// 验证保存路径是否有效且是一个目录
if (!$absolute_notes_directory || !is_dir($absolute_notes_directory)) {
    die('Invalid save path');
}

// 禁用缓存，以确保每次访问都获取最新的笔记内容
header('Cache-Control: no-store');

// 定义随机名称的字节长度（2 字节 => 4 位十六进制字符）
$random_bytes_length = 2; // 2 字节 = 4 位十六进制字符

// API 处理逻辑
// 1. 新建随机地址文本，/?new&text=xxxx，返回新建文本的URL
if (isset($_GET['new']) && isset($_GET['text'])) {
    // 生成随机名称
    $random_note_name = bin2hex(random_bytes($random_bytes_length)); // 使用定义的字节长度
    $note_file_path = $absolute_notes_directory . DIRECTORY_SEPARATOR . $random_note_name;

    // 保存文本内容
    $note_content = $_GET['text'];
    if (strlen($note_content) > 1024 * 100) {
        die('Content too large');
    }
    file_put_contents($note_file_path, $note_content);

    // 返回新建文本的 URL
    header('Content-Type: application/json');
    echo json_encode([
        'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $random_note_name
    ]);
    exit;
}

// 获取笔记的名称（通过URL参数传递）
$note_name = isset($_GET['note']) ? trim($_GET['note']) : null;

// API 处理逻辑
// 2. 新建或修改指定名称的笔记本，/name?text=xxxx，返回保存状态
if ($note_name && isset($_GET['text'])) {
    // 验证笔记名称的合法性
    if (strlen($note_name) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $note_name)) {
        die('Invalid note name');
    }

    // 处理笔记文件路径
    $sanitized_note_name = basename($note_name);
    $note_file_path = $absolute_notes_directory . DIRECTORY_SEPARATOR . $sanitized_note_name;

    // 保存或更新文本内容
    $note_content = $_GET['text'];
    if (strlen($note_content) > 1024 * 100) {
        die('Content too large');
    }

    // 写入文件
    file_put_contents($note_file_path, $note_content);

    // 返回保存状态
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Note saved successfully.'
    ]);
    exit;
}

// 验证笔记名称的合法性（为空、长度超出限制或包含非法字符）
if (!$note_name || strlen($note_name) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $note_name)) {
    // 如果笔记名称不合法，生成一个随机名称（使用定义的字节长度），并重定向到该新名称的页面
    $random_note_name = bin2hex(random_bytes($random_bytes_length)); // 使用定义的字节长度
    header("Location: /" . $random_note_name); // 重定向到没有查询参数的URL
    exit;
}

// 使用basename函数确保笔记名称不包含路径信息，防止目录遍历攻击
$sanitized_note_name = basename($note_name);
$note_file_path = $absolute_notes_directory . DIRECTORY_SEPARATOR . $sanitized_note_name;

// 验证文件路径是否位于笔记保存目录下，防止恶意路径注入
if (strpos($note_file_path, $absolute_notes_directory) !== 0) {
    die('Invalid note path');
}

// API 处理逻辑
// 3. 获取笔记内容，/name?raw，返回文本内容
if (isset($_GET['raw'])) {
    if (is_file($note_file_path)) {
        header('Content-Type: text/plain');
        readfile($note_file_path);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Note not found';
    }
    exit;
}

// 定义笔记的最大允许大小（100KB），防止上传大文件造成服务器压力
$MAX_NOTE_SIZE = 1024 * 100; // 100KB

// 处理POST请求，用户可以通过POST请求创建或更新笔记内容
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_content = isset($_POST['text']) ? $_POST['text'] : file_get_contents("php://input");

    // 检查内容大小是否超出限制
    if (strlen($note_content) > $MAX_NOTE_SIZE) {
        die('Content too large');
    }

    try {
        // 使用文件锁防止并发写入问题
        $file_handle = fopen($note_file_path, 'c');
        if (flock($file_handle, LOCK_EX)) {
            // 如果有内容，则写入文件；如果为空，则删除文件
            if (strlen($note_content)) {
                if (file_put_contents($note_file_path, $note_content) === false) {
                    error_log("Failed to save note to path: $note_file_path");
                    die('Failed to save the note');
                }
            } elseif (is_file($note_file_path) && !unlink($note_file_path)) {
                error_log("Failed to delete the note at path: $note_file_path");
                die('Failed to delete the note');
            }
            flock($file_handle, LOCK_UN);
        }
        fclose($file_handle);
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        die('An error occurred: ' . $e->getMessage());
    }
    exit;
}

// 检查是否请求原始笔记内容（通常通过curl或wget等命令行工具请求）
if (isset($_GET['raw']) || strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === 0 || strpos($_SERVER['HTTP_USER_AGENT'], 'Wget') === 0) {
    if (is_file($note_file_path)) {
        header('Content-type: text/plain');
        readfile($note_file_path);
    } else {
        header('HTTP/1.0 404 Not Found');
    }
    exit;
}

// 判断笔记文件是否存在，以确定是否是新笔记
$is_new_note = !is_file($note_file_path);

// 读取现有笔记内容并转义特殊字符，防止XSS攻击
$note_content_escaped = is_file($note_file_path) ? htmlspecialchars(file_get_contents($note_file_path), ENT_QUOTES, 'UTF-8') : '';
$note_name_escaped = htmlspecialchars($sanitized_note_name, ENT_QUOTES, 'UTF-8');

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $note_name_escaped; ?></title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        body {
            margin: 0;
            background: #ebeef1;
        }
        .container {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 20px;
            right: 20px;
            bottom: 20px;
            left: 20px;
        }
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        #content, #readonly-content {
            margin: 0;
            padding: 20px;
            overflow-y: auto;
            resize: none;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            border: 1px solid #ddd;
            outline: none;
        }
        #readonly-content {
            background: #f9f9f9;
        }
        .printable {
            display: none;
        }
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        .button-container button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .button-container button:hover {
            background-color: #0056b3;
        }
        .copy-success {
            position: fixed;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            display: none;
            z-index: 1000;
        }
        #qr-code-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            border: none;
        }
        @media (min-width: 768px) {
            .container {
                flex-direction: row;
            }
            .content-area {
                flex: 1;
                margin-right: 20px;
            }
            .button-container {
                flex-direction: column;
                align-self: flex-start;
                margin-top: 0;
            }
        }
        @media (prefers-color-scheme: dark) {
            body {
                background: #333b4d;
            }
            #content, #readonly-content {
                background: #24262b;
                color: #fff;
                border-color: #495265;
            }
        }
        @media (max-width: 767px) {
            .container {
                flex-direction: column;
            }
            .content-area {
                margin-right: 0;
            }
            .button-container {
                margin-top: 20px;
            }
            #qr-code-container {
                display: none;
            }
        }
        @media print {
            .container {
                display: none;
            }
            .printable {
                display: block;
                white-space: pre-wrap;
                word-break: break-word;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-area">
            <textarea id="content" style="display: none;"><?php echo $note_content_escaped; ?></textarea>
            <pre id="readonly-content" contenteditable="false" style="display: none;"><?php echo $note_content_escaped; ?></pre>
        </div>
        <div class="button-container">
            <button id="toggle-mode">编辑内容</button>
            <button id="copy-button">复制内容</button>
            <button id="copy-link-button">复制链接</button>
            <div id="qr-code-container"></div>
        </div>
    </div>
    <pre class="printable"></pre>
    <div id="copy-success" class="copy-success">内容已复制到剪贴板</div>
    <div id="copy-link-success" class="copy-success">链接已复制到剪贴板</div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('content');
            const readonlyContent = document.getElementById('readonly-content');
            const toggleButton = document.getElementById('toggle-mode');
            const copyButton = document.getElementById('copy-button');
            const copyLinkButton = document.getElementById('copy-link-button');
            const copySuccess = document.getElementById('copy-success');
            const copyLinkSuccess = document.getElementById('copy-link-success');
            const qrCodeContainer = document.getElementById('qr-code-container');

            // 根据是否为新建笔记来决定初始模式
            let isEditMode = <?php echo $is_new_note ? 'true' : 'false'; ?>;

            // 根据模式设置初始显示状态
            if (isEditMode) {
                textarea.style.display = 'block';
                toggleButton.textContent = '锁定内容';
                textarea.focus();
            } else {
                readonlyContent.style.display = 'block';
            }

            // 切换编辑和只读模式
            toggleButton.addEventListener('click', function() {
                isEditMode = !isEditMode;
                if (isEditMode) {
                    readonlyContent.style.display = 'none';
                    textarea.style.display = 'block';
                    toggleButton.textContent = '锁定内容';
                    textarea.focus();
                } else {
                    readonlyContent.textContent = textarea.value;
                    readonlyContent.style.display = 'block';
                    textarea.style.display = 'none';
                    toggleButton.textContent = '编辑内容';
                }
            });

            // 复制内容到剪贴板
            copyButton.addEventListener('click', function() {
                const textToCopy = isEditMode ? textarea.value : readonlyContent.textContent;
                navigator.clipboard.writeText(textToCopy).then(function() {
                    copySuccess.style.display = 'block';
                    setTimeout(function() {
                        copySuccess.style.display = 'none';
                    }, 2000);
                });
            });

            // 复制链接到剪贴板
            copyLinkButton.addEventListener('click', function() {
                const linkToCopy = window.location.href;
                navigator.clipboard.writeText(linkToCopy).then(function() {
                    copyLinkSuccess.style.display = 'block';
                    setTimeout(function() {
                        copyLinkSuccess.style.display = 'none';
                    }, 2000);
                });
            });

            // 生成二维码
            new QRCode(qrCodeContainer, {
                text: window.location.href,
                width: 128,
                height: 128,
            });

            // 自动保存笔记内容（防抖保存）
            let saveTimeout = null;
            textarea.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    const noteContent = textarea.value;
                    fetch(location.pathname, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'text=' + encodeURIComponent(noteContent),
                    });
                }, 1000);
            });
        });
    </script>
</body>
</html>
