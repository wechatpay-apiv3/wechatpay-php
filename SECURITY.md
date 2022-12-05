# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

Please do not open GitHub issues or pull requests - this makes the problem immediately visible to everyone, including malicious actors.

Security issues in this open source project can be safely reported to [TSRC](https://security.tencent.com).

## 报告漏洞

请不要使用 GitHub issues 或 pull request —— 这会让漏洞立即暴露给所有人，包括恶意人员。

请将本开源项目的安全问题报告给 [腾讯安全应急响应中心](https://security.tencent.com).

---

另外，你可能需要关注影响本SDK运行时行为的主要的PHP扩展缺陷列表：

+ [OpenSSL](https://www.openssl.org/news/vulnerabilities.html)
+ [libxml2](https://gitlab.gnome.org/GNOME/libxml2/-/blob/master/NEWS)
+ [curl](https://curl.se/docs/security.html)

当你准备在报告安全问题时，请先对照如上列表，确认是否存在已知运行时环境安全问题。
当你尝试升级主要扩展至最新版本之后，如若问题依旧存在，请将本开源项目的安全问题报告给 [TSRC腾讯安全应急响应中心](https://security.tencent.com)，致谢。
