# TextShare

TextShare 是一个简洁、轻量的在线文本共享工具，允许用户方便地创建、编辑和分享笔记。该项目基于 [minimalist-web-notepad](https://github.com/pereorga/minimalist-web-notepad) 改进，旨在提供一个简单易用的文字记录与分享平台。

## 功能

*   **简单易用**：无需注册，访问链接即可创建或编辑笔记。
*   **快速分享**：生成唯一链接和二维码，轻松分享笔记内容。
*   **支持编辑和只读模式**：可以在编辑模式下修改笔记，也可以通过只读模式查看笔记。
*   **自动保存**：编辑过程中，笔记内容会自动保存，防止数据丢失。
*   **暗黑模式**：支持深色主题，适应用户的系统设置。
*   **打印模式**：支持将笔记打印为纸质文档。

## 使用方法

1.  **创建或查看笔记**：

    *   访问 `http://your-domain.com`，系统会自动为你生成一个新的笔记页面。
    *   你可以立即在页面中输入内容，系统会自动保存笔记。
    *   笔记保存后，可通过页面顶部的链接或生成的二维码分享给他人。
2.  **编辑和切换模式**：

    *   默认进入编辑模式，你可以在页面输入笔记。
    *   点击页面中的“切换到只读模式”按钮，锁定笔记，防止进一步编辑。
3.  **分享笔记**：

    *   你可以复制页面的链接或使用自动生成的二维码与他人分享笔记。
    *   其他用户访问该链接时，可以以只读模式查看笔记内容。

## API 功能

TextShare 还提供了一些 API 功能，适合开发者或高级用户通过编程方式创建和管理笔记。

### API 端点

1.  **新建随机地址笔记**：

    *   访问：`POST /?new&text=你的内容`
    *   功能：生成一个随机名称的笔记，并保存传入的文本内容。
    *   返回：包含新笔记 URL 的 JSON 响应。
2.  **创建或修改指定名称的笔记**：

    *   访问：`POST /笔记名称?text=你的内容`
    *   功能：保存或更新指定名称的笔记，名称必须是字母、数字、下划线或连字符的组合，且不超过 64 个字符。
3.  **获取笔记的原始内容**：

    *   访问：`GET /笔记名称?raw`
    *   功能：返回笔记的纯文本内容，适合在命令行工具中查看。

## 部署方法

1.  克隆仓库到你的服务器：

    ```bash
    git clone https://github.com/lgpay/textshare.git

    ```
2.  设置笔记保存的目录：

    *   确保 `index.php` 中定义的笔记保存目录（默认为 `_tmp`）存在且具有读写权限。
3.  将项目部署到你的 Web 服务器上即可使用。

## 示例

访问 `http://your-domain.com/` 创建和分享你的笔记。

## 致谢

本项目基于 [minimalist-web-notepad](https://github.com/pereorga/minimalist-web-notepad) 改进，感谢原作者的开源贡献。
