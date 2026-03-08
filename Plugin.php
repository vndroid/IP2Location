<?php

namespace TypechoPlugin\IP2Location;

require_once __DIR__ . '/vendor/autoload.php';

use MaxMind\Db\Reader;
use Typecho\Plugin\PluginInterface;
use Typecho\Plugin\Exception as PluginException;
use Typecho\Widget\Helper\Form;
use Widget\Base\Comments;
use Widget\Comments\Admin;
use Widget\Comments\Archive;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 评论定位显示插件 for Typecho
 *
 * @package IP2Location
 * @author Vex
 * @version 0.0.1
 * @link https://github.com/vndroid/IP2Location
 */
class Plugin implements PluginInterface
{
    private const string DB_NAME = 'ipinfo_lite.mmdb';
    private const string DB_FILE = __DIR__ . '/' . self::DB_NAME;

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @return string
     * @throws PluginException
     */
    public static function activate(): string
    {
        // 验证 MMDB 文件存在
        if (!file_exists(self::DB_FILE)) {
            throw new PluginException(_t('激活失败：数据库文件不存在，请将 %s 放置到插件目录下'), self::DB_NAME);
        }

        // 验证 MMDB 文件格式
        try {
            $reader = new Reader(self::DB_FILE);
            $reader->close();
        } catch (\Exception $e) {
            throw new PluginException(_t('激活失败：数据库文件无法读取或损坏 - ') . $e->getMessage());
        }

        if (!extension_loaded('intl')) {
            throw new PluginException(_t('检测到当前 PHP 环境没有 intl 组件, 无法正常使用此插件'));
        }

        Admin::pluginHandle()->callIp = [__CLASS__, 'injectAdmin'];

        \Typecho\Plugin::factory(Archive::class)->___location = [__CLASS__, 'render'];

        return _t('插件已激活，定位钩子已生效');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate(): void
    {
    }

    /**
     * 获取插件配置面板
     */
    public static function config(Form $form): void
    {
    }

    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Form $form): void
    {
    }

    /**
     * ISO 3166-1 alpha-2 国家码转国家名（简体中文）
     *
     * @param string $code 两位国家码，如 "AU"
     * @return string 国家或地区中文名，如 "澳大利亚"
     */
    public static function iso2zh(string $code): string
    {
        if (!preg_match('/^[A-Za-z]{2}$/', $code)) {
            return '未知';
        }

        $zhName = \Locale::getDisplayRegion('-' . strtoupper($code), 'zh_CN');

        // 去掉"特别行政区"后缀（如"中国香港特别行政区"→"中国香港"）, 因为在评论列表中显示过长会导致布局问题
        $zhName = preg_replace('/特别行政区$/', '', $zhName);

        // 超过6个字符时截断
        if (mb_strlen($zhName, 'UTF-8') > 6) {
            $zhName = mb_substr($zhName, 0, 6, 'UTF-8');
        }

        return $zhName;
    }

    /**
     * 管理后台地址解析方法
     *
     * @param Comments $comments 评论
     */
    public static function injectAdmin(Comments $comments): void
    {
        $result = json_decode(self::lookupIp($comments->ip), true);

        if (is_array($result) && ($result['code'] ?? '') === '200') {
            $countryCode = $result['data']['country_code'] ?? '';
            $address = self::iso2zh($countryCode);
        } else {
            $address = '未知';
        }

        if ($comments instanceof Admin) {
            echo $comments->ip . '<br>' . $address;
        } else {
            echo $comments->ip;
        }
    }

    /**
     * 通过本地数据库查询 IP 归属地
     *
     * @param string $ip IP 地址
     * @return string JSON 格式结果，成功时包含 data 字段，失败时包含 error 字段
     */
    public static function lookupIp(string $ip): string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return json_encode(['code' => '401', 'error' => 'Invalid IP address'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $reader = new Reader(self::DB_FILE);
            $record = $reader->get($ip);
            $reader->close();

            if (!is_array($record)) {
                return json_encode(['code' => '404', 'error' => 'IP address not found in database'], JSON_UNESCAPED_UNICODE);
            }

            return json_encode(['code' => '200', 'data' => $record], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return json_encode(['code' => '500', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 渲染评论地理位置（___location 钩子回调）
     *
     * 由 Widget::__get() 通过 Plugin::call('___location', $this) 触发，
     * 框架固定只传入 $archive 一个参数，符合 call 钩子规范。
     *
     * @param Archive $archive 评论归档对象
     * @return string 国家/地区中文名，查询失败时返回 '未知'
     */
    public static function render(Archive $archive): string
    {
        $ip = $archive->ip;

        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return '未知';
        }

        $result = json_decode(self::lookupIp($ip), true);
        if (($result['code'] ?? '') !== '200') {
            return '未知';
        }

        $countryCode = $result['data']['country_code'] ?? '';
        $countryZh = self::iso2zh($countryCode);

        return $countryZh ?: '未知';
    }
}
