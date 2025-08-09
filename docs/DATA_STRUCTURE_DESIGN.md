# SyncFire WordPress Plugin 資料結構設計
本文檔詳細描述了 SyncFire WordPress 插件的資料結構設計，包括 WordPress 資料庫選項、Firebase/Firestore 資料結構以及兩者之間的映射關係。
## 1. WordPress 資料庫選項
---
SyncFire 插件在 WordPress 資料庫的 `wp_options` 表中存儲以下選項：
### 1.1 Firebase 配置選項
| 選項名稱                                    | 資料類型   | 描述                 | 預設值 |
| --------------------------------------- | ------ | ------------------ | --- |
| `syncfire_firebase_api_key`             | string | Firebase API 金鑰    | 空字串 |
| `syncfire_firebase_auth_domain`         | string | Firebase 認證網域      | 空字串 |
| `syncfire_firebase_project_id`          | string | Firebase 專案 ID     | 空字串 |
| `syncfire_firebase_storage_bucket`      | string | Firebase 儲存桶       | 空字串 |
| `syncfire_firebase_messaging_sender_id` | string | Firebase 訊息發送者 ID  | 空字串 |
| `syncfire_firebase_app_id`              | string | Firebase 應用程式 ID   | 空字串 |
| `syncfire_firebase_service_account`     | string | Firebase 服務帳戶 JSON | 空字串 |
### 1.2 分類法同步選項
| 選項名稱                            | 資料類型   | 描述        | 預設值    |
| ------------------------------- | ------ | --------- | ------ |
| `syncfire_taxonomies_to_sync`   | array  | 要同步的分類法列表 | 空陣列    |
| `syncfire_taxonomy_order_field` | string | 分類法排序欄位   | 'name' |
| `syncfire_taxonomy_sort_order`  | string | 分類法排序方向   | 'ASC'  |
### 1.3 文章類型同步選項
| 選項名稱                               | 資料類型  | 描述                      | 預設值 |
| ---------------------------------- | ----- | ----------------------- | --- |
| `syncfire_post_types_to_sync`      | array | 要同步的文章類型列表              | 空陣列 |
| `syncfire_post_type_fields`        | array | 每個文章類型要同步的欄位            | 空陣列 |
| `syncfire_post_type_field_mapping` | array | 文章類型欄位到 Firestore 欄位的映射 | 空陣列 |
### 1.4 其他選項
| 選項名稱               | 資料類型   | 描述   | 預設值              |
| ------------------ | ------ | ---- | ---------------- |
| `syncfire_version` | string | 插件版本 | SYNCFIRE_VERSION |

## 2. Firestore 資料結構
---
SyncFire 插件在 Firestore 中創建以下資料結構：
### 2.1 分類法集合
**路徑：`/taxonomies/{taxonomy_name}`**
每個分類法文檔包含：
```json
{
	"taxonomy": "分類法名稱",
	"terms": [
		{
			"term_id": 123,
			"name": "術語名稱",
			"slug": "術語-slug",
			"description": "術語描述",
			"parent": 0,
			"count": 5,
			"meta": {
				"meta_key1": "meta_value1",
				"meta_key2": "meta_value2"
			}
		}
	]
}
```
### 2.2 文章類型集合
**路徑：`/posts/{post_type}/items/{post_id}`**
每個文章文檔包含根據 `syncfire_post_type_fields` 和 `syncfire_post_type_field_mapping` 選項定義的欄位。例如：
```json
{
	"ID": 123,
	"post_title": "文章標題",
	"post_content": "文章內容",
	"post_excerpt": "文章摘要",
	"post_date": "2023-01-01 12:00:00",
	"post_status": "publish",
	"custom_field": "自定義欄位值",
	"featured_image": {
		"id": 456,
		"url": "https://example.com/image.jpg",
		"width": 800,
		"height": 600
	},
	"categories": [
		{
			"term_id": 789,
			"name": "分類名稱",
			"slug": "category-slug"
		}
	]
}
```

## 3. 資料映射與轉換
---
### 3.1 WordPress 到 Firestore 的資料轉換
#### 3.1.1 分類法資料轉換
分類法資料通過 `SyncFire_Taxonomy_Sync::prepare_term_data()` 方法進行轉換，將 WordPress 的 `WP_Term` 物件轉換為適合 Firestore 的格式。
```php
private function prepare_term_data($term) {
	return array(
		'term_id' => $term->term_id,
		'name' => $term->name,
		'slug' => $term->slug,
		'description' => $term->description,
		'parent' => $term->parent,
		'count' => $term->count,
		'meta' => $this->get_term_meta($term->term_id),
	);
}
```
#### 3.1.2 文章資料轉換
文章資料通過 `SyncFire_Post_Type_Sync::prepare_post_data()` 方法進行轉換，根據配置的欄位和映射將 WordPress 的 `WP_Post` 物件轉換為適合 Firestore 的格式。
```php
private function prepare_post_data($post, $fields_to_sync, $field_mapping) {
	$post_data = array();
	foreach ($fields_to_sync as $field) {
		// 獲取欄位值
		$field_value = $this->get_post_field_value($post, $field);
		// 獲取 Firestore 中的欄位名稱
		$firestore_field = isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
		// 將欄位添加到文章資料中
		$post_data[$firestore_field] = $field_value;
	}
	return $post_data;
}
```
### 3.2 Firestore 資料格式
Firestore 資料通過 `SyncFire_Firestore::prepare_firestore_document()` 和 `SyncFire_Firestore::prepare_firestore_value()` 方法進行格式化，將 PHP 資料類型轉換為 Firestore API 所需的格式。

**支援的資料類型包括：**
- null
- boolean
- integer
- double
- string
- map (關聯陣列)
- array (索引陣列)

## 4. 同步機制
---
### 4.1 分類法同步觸發器
分類法同步通過以下 WordPress 鉤子觸發：
- `created_term`: 當創建新術語時
- `edited_term`: 當編輯術語時
- `delete_term`: 當刪除術語時
### 4.2 文章同步觸發器
文章同步通過以下 WordPress 鉤子觸發：
- `save_post`: 當創建或更新文章時
- `before_delete_post`: 當刪除文章前
- `post_updated`: 當更新文章時
- `transition_post_status`: 當文章狀態變更時
- `added_post_meta`, `updated_post_meta`, `deleted_post_meta`: 當文章元資料變更時
- `updated_post_thumb`, `deleted_post_thumb`: 當文章特色圖片變更時

## 5. 資料驗證與清理
---
### 5.1 設定值清理
所有設定值在保存前都會通過適當的清理函數進行處理：
- 字串值使用 `sanitize_text_field` 或 `sanitize_textarea_field`
- 陣列值使用自定義的 `sanitize_array` 方法遞迴清理
```php
public function sanitize_array($input) {
	// 如果輸入不是陣列，則將其轉換為陣列
	if (!is_array($input)) {
		if (empty($input)) {
			return array();
		}
		return array($input);
	}

// 清理陣列中的每個值
$sanitized_input = array();
	foreach ($input as $key => $value) {
		if (is_array($value)) {
			$sanitized_input[$key] = $this->sanitize_array($value);
		} else {
			$sanitized_input[$key] = sanitize_text_field($value);
		}
	}
	return $sanitized_input;
}
```

## 6. 資料流程圖
---
```
WordPress 資料 → 資料轉換 → Firestore 資料格式 → Firestore API → Google Firestore
↑ |
| |
+---------------------------------------------------------------------+
實時同步
```

## 7. 資料安全性
---
- Firebase 配置資料（API 金鑰、服務帳戶等）存儲在 WordPress 資料庫中
- 存取權限限制為僅管理員可以配置和管理同步設定
- 使用 Firebase 服務帳戶進行 Firestore API 認證
- 存取令牌使用 WordPress 瞬態（transients）機制臨時存儲，有效期比實際令牌短