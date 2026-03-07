# IP2Location

评论地址解析插件，基于 [ICU4C](https://android.googlesource.com/platform/external/icu4c/%2B/donut-release/data/locales/zh.txt) 开发，支持 Typecho 1.2 及以上版本。

## 插件亮点

- 遵循 Typecho 1.2 开发规范，兼容性更好；
- 使用 IPinfo Lite [免费 MMDB 数据库](https://ipinfo.io/developers/ipinfo-lite-database)，支持 IPv4 和 IPv6；
- 使用 [MaxMind](https://support.maxmind.com/knowledge-base/articles/maxmind-database-formats) [DB Reader](https://github.com/maxmind/MaxMind-DB-Reader-php) PHP 解析器；
- 使用 [PHP: intl](https://www.php.net/manual/en/book.intl.php) 扩展；

## MMDB 返回字段

```json
{
    "country": "Australia",
    "country_code": "AU",
    "continent": "Oceania",
    "continent_code": "OC",
    "asn": "AS13335",
    "as_name": "Cloudflare, Inc.",
    "as_domain": "cloudflare.com"
}
```

![ipinfo-lite-example.png](ipinfo-lite-example.png)

IP address data powered by [IPinfo](https://ipinfo.io)
