# TextShare

一个轻量、简洁、开箱即用的在线文本分享工具。

TextShare 适合快速记录、临时分享、跨设备查看纯文本内容：打开网页即可使用，无需注册，无需数据库，部署成本低。

## 特性

- **轻量部署**：单文件 PHP 应用，数据直接存储为本地文件
- **即开即用**：访问站点即可自动创建一个新的笔记地址
- **自动保存**：编辑内容后会自动发送保存请求
- **只读 / 编辑切换**：已存在笔记默认以只读方式打开，可手动切换到编辑模式
- **便于分享**：支持复制内容、复制链接、生成二维码
- **多端可用**：兼容桌面端和移动端浏览器
- **原始文本输出**：支持直接获取笔记纯文本内容，适合命令行或脚本调用

---

## 项目结构

```text
.
├── index.php      # 主程序
├── .htaccess      # Apache 重写规则
├── _tmp/          # 笔记存储目录
└── README.md
```

---

## 运行要求

- PHP 7.4+（推荐 PHP 8.x）
- Apache，并启用 `mod_rewrite`
- Web 服务进程对 `_tmp/` 目录有读写权限

> 当前路由依赖 `.htaccess`，因此默认部署方式是 Apache。

---

## 部署

### 1. 克隆仓库

```bash
git clone https://github.com/lgpay/textshare.git
cd textshare
```

### 2. 准备存储目录

项目默认将笔记保存到：

```php
__DIR__ . '/_tmp'
```

请确认该目录存在，并且 Web 服务用户有读写权限。

例如：

```bash
mkdir -p _tmp
chmod 775 _tmp
```

### 3. 配置 Apache

确保已启用：

- `mod_rewrite`
- `AllowOverride All`

示例虚拟主机配置：

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/textshare

    <Directory /path/to/textshare>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. 访问站点

打开：

```text
http://your-domain.com/
```

系统会自动跳转到一个随机笔记地址。

---

## 使用方式

### 创建笔记

访问站点根路径：

```text
/
```

服务端会自动生成一个随机笔记地址，并跳转到该页面。

### 编辑笔记

新建笔记默认进入编辑模式。输入内容后，前端会在输入停止约 1 秒后自动保存。

对于已存在的笔记，页面默认显示只读内容，可以点击“编辑内容”切换到编辑模式。

### 分享笔记

页面提供：

- **复制内容**
- **复制链接**
- **二维码分享**

适合在手机和桌面端之间快速传递文本。

---

## URL 规则

`.htaccess` 会将以下路径：

```text
/your-note-name
```

重写为：

```text
index.php?note=your-note-name
```

笔记名称只允许以下字符：

- `a-z`
- `A-Z`
- `0-9`
- `_`
- `-`

最大长度为 **64** 个字符。

---

## API / 接口说明

当前项目接口比较简单，主要围绕查询参数和 POST 保存。

### 1. 创建随机地址笔记

创建一个随机名称的笔记，并写入文本内容。

```http
GET /?new&text=hello
```

返回 JSON：

```json
{
  "url": "http://your-domain.com/abcd"
}
```

> 注意：虽然语义上这是“创建”操作，但当前实现使用的是 **GET** 参数，而不是 POST。

---

### 2. 创建或更新指定名称笔记（查询参数方式）

```http
GET /my-note?text=hello
```

返回 JSON：

```json
{
  "status": "success",
  "message": "Note saved successfully."
}
```

适合简单脚本调用，但从 HTTP 语义上看，这不是最标准的写法。

---

### 3. 获取笔记原始内容

```http
GET /my-note?raw
```

返回纯文本内容：

```text
hello
```

如果笔记不存在，返回 `404`。

---

### 4. 通过 POST 保存当前笔记

页面前端自动保存使用的是 POST 请求。

```http
POST /my-note
Content-Type: application/x-www-form-urlencoded

text=hello
```

也支持直接将内容作为请求体发送。

---

## 命令行示例

### 创建随机笔记

```bash
curl "http://your-domain.com/?new&text=hello"
```

### 创建或更新指定笔记

```bash
curl "http://your-domain.com/my-note?text=hello"
```

### 读取原始内容

```bash
curl "http://your-domain.com/my-note?raw"
```

### 使用 POST 更新内容

```bash
curl -X POST \
  -d "text=hello from post" \
  "http://your-domain.com/my-note"
```

---

## 存储方式

每个笔记对应 `_tmp/` 目录中的一个文件，文件名即笔记名。

例如：

```text
_tmp/my-note
```

这意味着：

- 不依赖数据库
- 便于备份和迁移
- 适合轻量、自托管、低并发场景

---

## 已知限制

- 当前默认依赖 Apache URL Rewrite
- 笔记内容大小限制约为 **100 KB**
- 不包含用户系统、权限系统、历史版本或协作编辑
- 随机笔记名当前较短，公开部署时应注意被枚举的风险
- 接口风格偏实用，HTTP 语义未完全统一

---

## 适用场景

TextShare 适合：

- 快速分享一段文本
- 临时记录命令、链接、草稿
- 手机 / 电脑之间传递内容
- 自建一个极简在线记事本

不适合：

- 复杂文档协作
- 富文本排版
- 多用户权限管理
- 高频并发写入场景

---

## 安全建议

如果要公开部署，建议至少考虑：

- 将 `_tmp` 放在更安全的位置，并确保不可被目录遍历访问
- 配置 HTTPS
- 通过 Web 服务器增加基础认证或访问控制
- 定期清理无用笔记
- 根据需要增加更长的随机笔记名

`.htaccess` 中已经预留了 Basic Auth 的示例配置注释。

---

## 致谢

本项目基于 [minimalist-web-notepad](https://github.com/pereorga/minimalist-web-notepad) 改进，感谢原作者的开源贡献。
