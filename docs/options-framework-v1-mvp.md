# Lerm Options Framework v1 MVP

`Lerm Options Framework` 是从当前主题原生设置页中抽出来的第一版可复用内核。它的目标不是一次性复制 CSF Pro 的全部能力，而是先把最重要的三层打稳：

- `Schema`：页面、分组、字段、默认值、依赖、choices
- `Store`：基于 WordPress option 的统一读取、归一化、保存、重置
- `Container`：通用后台设置页，支持无刷新保存、当前标签重置、整页重置

## 目录

- `app/OptionsFramework/Framework.php`
  - 框架入口，负责共享字段注册表、store 实例和 page 实例
- `app/OptionsFramework/Support/PageSchema.php`
  - schema 工具类，负责解析 sections、fields、defaults、choices
- `app/OptionsFramework/Registry/FieldTypeRegistry.php`
  - 字段类型注册表，v1 先支持 MVP 内置字段并开放自定义注册
- `app/OptionsFramework/Stores/OptionStore.php`
  - 基于 `get_option / update_option` 的存储层
- `app/OptionsFramework/Admin/OptionsPage.php`
  - 通用后台页面容器和 UI
- `app/OptionsFramework/assets/options-framework.js`
  - 无刷新保存、重置、依赖切换、媒体库、排序、CodeMirror
- `app/OptionsFramework/assets/options-framework.css`
  - 页面布局和交互样式

## 当前接入方式

当前主题已经作为第一个真实使用方接入，但主题侧只保留“接入定义”，不再在框架里额外挂一层 repository 和 page adapter：

- `app/OptionsFramework/Integrations/LermTheme/OptionsPageDefinition.php`
  - 提供当前主题的完整 `definition()` 定义
- `functions.php`
  - `lerm_options()` 直接通过框架的 `OptionStore` 读取 `lerm_theme_options`
- `app/bootstrap.php`
  - 直接调用框架挂载页面，不再额外包一层 `ThemeSettingsPage`

## Page Definition 结构

```php
use Lerm\OptionsFramework\Framework;

$definition = array(
	'id'          => 'demo-settings',
	'option_name' => 'demo_options',
	'menu'        => array(
		'parent_slug' => 'themes.php',
		'page_title'  => 'Demo Settings',
		'menu_title'  => 'Demo Settings',
		'capability'  => 'manage_options',
	),
	'view'        => array(
		'eyebrow'     => 'Native admin',
		'title'       => 'Demo Settings',
		'description' => 'Settings powered by the framework MVP.',
	),
	'sections'    => array(
		'general' => array(
			'title'       => 'General',
			'description' => 'Basic options.',
			'fields'      => array(
				array(
					'id'      => 'headline',
					'type'    => 'text',
					'label'   => 'Headline',
					'default' => '',
				),
			),
		),
	),
);

Framework::instance()->mount_options_page( $definition );
```

## v1 内置字段

- `text`
- `textarea`
- `url`
- `number`
- `switcher`
- `select`
- `radio`
- `button_set`
- `checkbox_list`
- `color`
- `media`
- `gallery`
- `sorter`
- `wp_editor`
- `code_editor`

## 扩展字段

v1 的扩展点先保持足够简单，直接通过字段注册表注册：

```php
use Lerm\OptionsFramework\Framework;
use Lerm\OptionsFramework\Stores\OptionStore;
use Lerm\OptionsFramework\Admin\OptionsPage;

Framework::instance()->field_types()->register(
	'json',
	array(
		'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ) {
			return is_scalar( $value ) ? trim( (string) $value ) : '';
		},
		'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ) {
			printf(
				'<textarea id="%1$s" name="%2$s" class="large-text" rows="10">%3$s</textarea>',
				esc_attr( (string) $field['id'] ),
				esc_attr( $field_name ),
				esc_textarea( (string) $value )
			);
		},
	)
);
```

## MVP 边界

v1 目前只覆盖：

- `Options Page`
- `option` 存储适配器
- 后台原生 UI
- 无刷新保存和重置
- 字段类型注册

v1 还没有覆盖：

- `metabox / term meta / user meta / comment meta / network option`
- 导入导出
- 快照与回滚
- 远程模板库
- 授权、更新和商业版分层
- 高级字段，比如 `group / repeater / typography / spacing / border / background`

## 下一阶段建议

1. 把 `Store` 抽象成接口，新增 `PostMetaStore / TermMetaStore / UserMetaStore`
2. 把 built-in 字段的渲染和 sanitize 从大 `switch` 迁到独立 field classes
3. 增加 `Container` 层，支持 `MetaboxContainer`
4. 加导入导出、快照和 schema 调试面板
5. 把资产路径和命名从主题常量中彻底抽离，为插件单独发布做准备
