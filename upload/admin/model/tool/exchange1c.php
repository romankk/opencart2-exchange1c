<?php

class ModelToolExchange1c extends Model {

	private $VERSION_XML 	= '';
	private $STORE_ID		= 0;
	private $LANG_ID		= 0;


	/**
	 * ****************************** ОБЩИЕ ФУНКЦИИ ******************************
	 */


	/**
	 * Номер текущей версии
	 *
	 */
	public function version() {
		return "1.6.2.b9";
	} // version()


	/**
	 * Пишет информацию в файл журнала
	 *
	 * @param	int				Уровень сообщения
	 * @param	string,object	Сообщение или объект
	 */
	private function log($message, $level=1) {
		if ($this->config->get('exchange1c_log_level') >= $level) {

			if ($level == 1) {
				$this->log->write(print_r($message,true));

			} elseif ($level == 2) {
				$memory_usage = sprintf("%.3f", memory_get_usage() / 1024 / 1024);
				list ($di) = debug_backtrace();
				$line = sprintf("%04s",$di["line"]);

				if (is_array($message) || is_object($message)) {
					$this->log->write($memory_usage . " Mb | " . $line);
					$this->log->write(print_r($message, true));
				} else {
					$this->log->write($memory_usage . " Mb | " . $line . " | " . $message);
				}
			}
		}
	} // log()


	/**
	 * Конвертирует XML в массив
	 *
	 * @param	array				data
	 * @param	SimpleXMLElement	XML
	 * @return	XML
	 */
	function array_to_xml($data, &$xml) {
		foreach($data as $key => $value) {
			if (is_array($value)) {
				if (!is_numeric($key)) {
					$subnode = $xml->addChild(preg_replace('/\d/', '', $key));
					$this->array_to_xml($value, $subnode);
				}
			}
			else {
				$xml->addChild($key, $value);
			}
		}
		return $xml;
	} // array_to_xml()


	/**
	 * Очистка лога
	 */
	private function clearLog() {
		$file = DIR_LOGS . $this->config->get('config_error_filename');
		$handle = fopen($file, 'w+');
		fclose($handle);
	}


	/**
	 * Возвращает строку даты
	 *
	 * @param	string	var
	 * @return	string
	 */
	function format($var){
		return preg_replace_callback(
		    '/\\\u([0-9a-fA-F]{4})/',
		    create_function('$match', 'return mb_convert_encoding("&#" . intval($match[1], 16) . ";", "UTF-8", "HTML-ENTITIES");'),
		    json_encode($var)
		);
	} // format()


	/**
	 * Проверим файл на стандарт Commerce ML
	 */
	private function checkCML($xml) {
		$this->log("==> checkCML()",2);
		if ($xml['ВерсияСхемы']) {
			$this->VERSION_XML = (string)$xml['ВерсияСхемы'];
			$this->log("[i] Версия XML: " . $this->VERSION_XML,2);
		} else {
			$this->log("[ERROR] Файл не является стандартом Commerce ML!");
			return 0;
		}
		return 1;
	} // checkCML()


	/**
	 * Очищает базу
	 */
	public function cleanDB() {
		$this->log("==> cleanDB()",2);
		// Удаляем товары
		$result = "";

		$this->log("[i] Очистка таблиц товаров...",2);
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_attribute`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_discount`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_image`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option_value`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_related`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_reward`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_special`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_quantity`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
		$result .=  "Очищены таблицы товаров\n";

		//SEO
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'url_alias`');

		// Характеристики (группы опций)
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_price`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature_value`');

		// Дополнительные единицы измерений товара
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_unit`');

		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_category`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_download`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_layout`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_store`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'order_option`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option`');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%product_id=%"');
		$result .=  "Очищены таблицы товаров, опций\n";

		// Очищает таблицы категорий
		$this->log("Очистка таблиц категорий...",2);
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
		$result .=  "Очищены таблицы категорий\n";

		// Очищает таблицы от всех производителей
		$this->log("Очистка таблиц производителей...",2);
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_1c');
		$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` WHERE `Tables_in_" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "manufacturer_description'");
		//$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "manufacturer_description'");
		if ($query->num_rows) {
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
		}
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%manufacturer_id=%"');
		$result .=  "Очищены таблицы производителей\n";

		// Очищает атрибуты
		$this->log("Очистка таблиц атрибутов...",2);
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_description`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_to_1c`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group_description`");
		$result .=  "Очищены таблицы атрибутов\n";

		// Выставляем кол-во товаров в 0
		$this->log("Очистка остатков...",2);
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `quantity` = 0");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "warehouse`");
		$result .=  "Обнулены все остатки\n";

		// Удаляем все цены
		$this->log("Очистка остатков...",2);
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_price`");
		$result .=  "Удалены все цены\n";

		// Удаляем все характеристики
		$this->log("Очистка характеристик...",2);
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature_value`');
		$result .=  "Удалены все характеристики\n";

		// Удаляем связи с магазинами
		$this->log("Очистка связей с магазинами...",2);
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'store_to_1c`');
		$result .=  "Удалены все связи с магазинами\n";

		// Удаляем связи с единицами измерений
		$this->log("Очистка связей с единицами измерений...",2);
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'unit_to_1c`');
		$result .=  "Очищены таблицы связей с единицами измерений\n";

		return $result;
	} // cleanDB()


	/**
	 * Очищает базу
	 */
	public function cleanLinks() {
		$this->log("==> cleanLinks()",2);
		// Удаляем связи
		$result = "";

		$this->log("[i] Очистка таблиц товаров...",2);
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
		$result .=  "Таблица связей товаров '" . DB_PREFIX . "product_to_1c'\n";
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'category_to_1c`');
		$result .=  "Таблица связей категорий '" . DB_PREFIX . "category_to_1c'\n";
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'manufacturer_to_1c`');
		$result .=  "Таблица связей производителей '" . DB_PREFIX . "manufacturer_to_1c'\n";
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_to_1c`");
		$result .=  "Таблица связей атрибутов '" . DB_PREFIX . "attribute_to_1c'\n";
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'store_to_1c`');
		$result .=  "Таблица связей с магазинами\n";

		return $result;
	} // cleanLinks()


	/**
	 * Возвращает информацию о синхронизированных объектов с 1С товарок, категорий, атрибутов
	 */
	public function linksInfo() {
		$this->log("==> linksInfo()",2);
		$data = array();
		$query = $this->db->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'product_to_1c`');
		$data['product_to_1c'] = $query->row['num'];
		$query = $this->db->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'category_to_1c`');
		$data['category_to_1c'] = $query->row['num'];
		$query = $this->db->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'manufacturer_to_1c`');
		$data['manufacturer_to_1c'] = $query->row['num'];
		$query = $this->db->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'attribute_to_1c`');
		$data['attribute_to_1c'] = $query->row['num'];

		return $data;

	} // linksInfo()


	/**
	 * Удаляет связи cml_id -> id
	 */
	public function deleteLinkProduct($product_id) {
		$this->log("==> deleteLinkProduct(), product_id = " . $product_id, 2);
		// Удаляем линк
		if ($product_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);
			$this->log("Удалена связь товара cml_id с id: " . $product_id, 2);
		}
		$this->load->model('catalog/product');
		$product = $this->model_catalog_product->getProduct($product_id);
		if ($product['image']) {
			// Удаляем только в папке import_files
			if (substr($product['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $product['image']);
				$this->log("Удален файл: " . $product['image'],2);
			}
		}
		$productImages = $this->model_catalog_product->getProductImages($product_id);
		foreach ($productImages as $image) {
			// Удаляем только в папке import_files
			if (substr($image['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $image['image']);
				$this->log("Удален файл: " . $image['image'],2);
			}
		}
	} // deleteLinkProduct()


	/**
	 * Удаляет связи cml_id -> id
	 */
	public function deleteLinkCategory($category_id) {
		$this->log("==> deleteLinkCategory()",2);
		// Удаляем линк
		if ($category_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "category_to_1c` WHERE `category_id` = " . (int)$category_id);
			$this->log("Удалена связь категории cml_id с id: " . $category_id,2);
		}
	} //  deleteLinkCategory()


	/**
	 * Удаляет связи cml_id -> id
	 */
	public function deleteLinkManufacturer($manufacturer_id) {
		$this->log("==> deleteLinkManufacturer()",2);
		// Удаляем линк
		if ($manufacturer_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "manufacturer_to_1c` WHERE `manufacturer_id` = " . $manufacturer_id);
			$this->log("Удалена связь производителя cml_id с id: " . $manufacturer_id,2);
		}
	} //  deleteLinkManufacturer()


	/**
	 * Удаляет связи товара c характеристиками 1С
	 */
	public function deleteLinkFeature($product_id) {
		$this->log("==> deleteLinkFeature()",2);
		// Удаляем линк
		if ($product_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_feature` WHERE `product_id` = " . $product_id);
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_feature_value` WHERE `product_id` = " . $product_id);
			$this->log("Удалена связь характеристик с товаром, id: " . $product_id,2);
		}
	} //  deleteLinkFeature()


	/**
	 * Создает события
	 */
	public function setEvents() {
		$this->log("==> setEvents()",2);
		// Установка событий
		$this->load->model('extension/event');

		// Удалим все события
		$this->model_extension_event->deleteEvent('exchange1c');

		// Добавим удаление связей при удалении товара
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.product.delete', 'module/exchange1c/eventDeleteProduct');
		// Добавим удаление связей при удалении категории
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.category.delete', 'module/exchange1c/eventDeleteCategory');
		// Добавим удаление связей при удалении Производителя
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.manufacturer.delete', 'module/exchange1c/eventDeleteManufacturer');
		// Добавим удаление связей при удалении Характеристики
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.option.delete', 'module/exchange1c/eventDeleteOption');
	} // setEvents()


	/**
	 * Получает language_id из code (ru, en, etc)
	 * Как ни странно, подходящей функции в API не нашлось
	 *
	 * @param	string
	 * @return	int
	 */
	public function getLanguageId($lang) {
		$this->log("==> getLanguageId()",2);
		$query = $this->db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $this->db->escape($lang) . "'");
		$this->LANG_ID = $query->row['language_id'];
		return $query->row['language_id'];
	} // getLanguageId()


	/**
	 * Проверяет таблицы модуля
	 */
	public function checkDB() {
		$this->log("==> checkDB()",2);
		if (version_compare($this->config->get('exchange1c_version'), '1.6.2.b7', '<')) {
			$tables_module = array("product_to_1c","product_quantity","category_to_1c","warehouse","store_to_1c","attribute_to_1c","manufacturer_to_1c");
		} else {
			$tables_module = array("product_to_1c","product_quantity","product_price","category_to_1c","warehouse","product_feature","product_feature_value","store_to_1c","attribute_to_1c","manufacturer_to_1c","unit");
		}
		foreach ($tables_module as $table) {
			$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "%" . $table . "'");
			if (!$query->rows) {
				$error = "[ERROR] Таблица " . $table . " в базе отсутствует, переустановите модуль! Все связи будут потеряны!";
				$this->log(1,$error);
				return $error;
			}
		}
		// проверка полей таблиц

		return "";
	} // checkDB()


	/**
	 * Устанавливает SEO URL (ЧПУ) для заданного товара
	 * @param 	inf
	 * @param 	string
	 */
	private function setSeoURL($url_type, $element_id, $element_name) {
		$this->log("==> setSeoURL()",2);
		$sql = "DELETE FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $url_type . "=" . $element_id . "'";
		$this->log($sql,2);
		$this->db->query($sql);

		$sql = "INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword` = '" . $this->db->escape($element_name) . "'";
		$this->log($sql,2);
		$this->db->query($sql);

	} // setSeoURL()


	/**
	 * Транслиетрирует RUS->ENG
	 * @param string $aString
	 * @return string type
	 */
	private function transString($aString) {
		$rus = array(" ", "/", "*", "-", "+", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "+", "[", "]", "{", "}", "~", ";", ":", "'", "\"", "<", ">", ",", ".", "?", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ъ", "Ы", "Ь", "Э", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ъ", "ы", "ь", "э", "ё",  "ж",  "ц",  "ч",  "ш",  "щ",   "ю",  "я",  "Ё",  "Ж",  "Ц",  "Ч",  "Ш",  "Щ",   "Ю",  "Я");
		$lat = array("-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-",  "-", "-", "-", "-", "-", "-", "a", "b", "v", "g", "d", "e", "z", "i", "y", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "",  "i", "",  "e", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "",  "i", "",  "e", "yo", "zh", "ts", "ch", "sh", "sch", "yu", "ya", "yo", "zh", "ts", "ch", "sh", "sch", "yu", "ya");
		$string = str_replace($rus, $lat, $aString);
		while (mb_strpos($string, '--')) {
			$string = str_replace('--', '-', $string);
		}
		$string = strtolower(trim($string, '-'));
		return $string;
	} // transString()


	/**
	 * Формирует строку запроса при наличии переменной
	 */
	private function setStrQuery($field_name, $type) {
		if ($type == 'string') {
			return isset($data[$field_name]) ? ", " . $field_name . " = '" . $this->db->escape($data[$field_name]) . "'" : "";
		}
		elseif ($type == 'int') {
			return isset($data[$field_name]) ? ", " . $field_name . " = " . (int)$data[$field_name] : "";
		}
		elseif ($type == 'float') {
			return isset($data[$field_name]) ? ", " . $field_name . " = " . (float)$data[$field_name] : "";
		}
		return "";
	} //setStrQuery()


	/**
	 * Поиск cml_id товара по ID
	 */
	private function getcml_idByProductId($product_id) {
		$query = $this->db->query("SELECT 1c_id FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . $product_id);
		return isset($query->row['1c_id']) ? $query->row['1c_id'] : '';
	} // getcml_idByProductId()


	/**
	 * Проверка на существование поля в таблице
	 */
	public function existField($table, $field, $value="") {
		if (!$this->existTable($table)) return 0;
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` WHERE `field` = '" . $field . "'");
		if ($query->num_rows) {
			if (!empty($value)) {
				return ", " . $field . " = '" . $value . "'";
			} else {
				return 1;
			}

		}
		if (!empty($value)) {
			return "";
		} else {
			return 0;
		}
	} // existField()


	/**
	 * Проверка на существование таблицы
	 */
	private function existTable($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");
		if ($query->num_rows) {
			return 1;
		} else {
			return 0;
		}
	} // existTable()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ SEO ******************************
	 */

	/**
	 * Получает все категории продукта в строку для SEO
	 */
    private function getProductCategoriesString($product_id) {
		$this->log("==> getProductCategories()",2);
 		$categories = array();
		$query = $this->db->query("SELECT c.category_id, cd.name FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` pc ON (pc.category_id = c.category_id) WHERE cd.language_id = " . $this->LANG_ID . " AND pc.product_id = " . $product_id . " ORDER BY c.sort_order, cd.name ASC");
		foreach ($query->rows as $category) {
			$categories[] = $category['name'];
		}
		return implode(',', $categories);
      } // getProductCategoriesString()


	/**
	 * Получает все категории продукта в массив
	 * первым в массиме будет главная категория
	 */
    private function getProductCategories($product_id) {
		$this->log("==> getProductCategories()",2);

		$main_category = $this->existField('product_to_category', 'main_category');
		$sql = "SELECT `category_id`" . ($main_category ? ",`main_category`" : "") . " FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $product_id;
		$this->log($sql, 2);
		$query = $this->db->query($sql);
		$categories = array();
		foreach ($query->rows as $category) {
			if ($main_category && $category['main_category']) {
				// главную категорию добавляем в начало массива
				array_unshift($categories, $category['category_id']);
			} else {
				$categories[] = $category['category_id'];
			}

		}
		return $categories;

    } // getProductCategories()


	/**
	 * Генерит SEO строк
	 */
	private function seoGenerateString($template, $product_tags, $trans = false) {

		$this->log("==> seoGenerateString()",2);

		// Выберем все теги которые используются в шаблоне
		preg_match_all('/\{(\w+)\}/', $template, $matches);
		$values = array();

		foreach ($matches[0] as $match) {
			$value = isset($product_tags[$match]) ? $product_tags[$match] : '';
			if ($trans) {
				$values[] = $this->transString($value);
			} else {
				$values[] = $value;
			}
		}

		return str_replace($matches[0], $values, $template);

	} // seoGenerateStr()


	/**
	 * Генерит SEO переменные шаблона для товара
	 */
	private function seoGenerateProduct(&$data) {
		$this->log("==> seoGenerateProduct()",2);

		// Товары, Категории
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
			'tag'				=> array()
		);

		// Сопоставляем значения
		$tags = array(
			'{name}'		=> isset($data['name']) 		? $data['name'] 								: '',
			'{sku}'			=> isset($data['sku'])			? $data['sku'] 									: '',
			'{brand}'		=> isset($data['manufacturer'])	? $data['manufacturer'] 						: '',
			'{desc}'		=> isset($data['description'])	? $data['description'] 							: '',
			'{cats}'		=> isset($data['categories'])	? $data['categories'] 							: '',
			'{price}'		=> isset($data['price'])		? $this->currency->format($data['price']) 		: '',
			'{prod_id}'		=> isset($data['product_id'])	? $data['product_id'] 							: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 							: ''
		);

		if ($this->existField('product_description', 'meta_h1')) {
			$seo_fields['meta_h1'] = array();
		}

		// Получим поля для сравнения
		$fields_list = array();
		foreach ($seo_fields as $field=>$param) {
			if ($field == 'seo_url') continue;
			$fields_list[] = $field;
		}
		$fields	= implode($fields_list,', ');
		if (!isset($data['name']))
			$fields .= ", name";
		$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			foreach ($fields_list as $field) {
				$data[$field] = $query->row[$field];
				$this->log('field: '.$field,2);
			}
		}
		if (!isset($data['name']) && isset($query->row['name'])) {
			$data['name'] = $query->row['name'];
		$tags['{name}']	= $data['name'];
		}

//		$this->log($data, 2);

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_product_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_product_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_product_'.$field) == 'import') {
				// из свойства которое считалось при обмене
			}
			if ($this->config->get('exchange1c_seo_product_overwrite') == 'overwrite') {
				// Перезаписывать
				$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
			} else {
				// Только если поле пустое
				if (empty($data[$field])) {
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
				} else {
					$this->log("Поле '" . $field . "' не пустое", 2);
				}
			}
			$this->log("Поле '" . $field . "' = '" . $data[$field] . "'", 2);
		}

		if (isset($data['seo_url'])) {
			if ($this->config->get('exchange1c_seo_product_overwrite') == 'overwrite') {
				$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
			} else {
				$sql = "SELECT keyword FROM `" . DB_PREFIX . "url_alias` WHERE `query` = 'product_id=" . $data['product_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$data['seo_url'] = $query->row['keyword'];
					if (empty($data['seo_url']))
						$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
				} else {
					$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
				}
			}
		}
		$this->log("<== seoGenerateProduct()",2);
	} // seoGenerateProduct()


	/**
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateCategory(&$data) {
		$this->log("==> seoGenerateCategory()",2);

		// Товары, Категории
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);

		if ($this->existField('product_description', 'meta_h1')) {
			$seo_fields['meta_h1'] = array();
		}

		// Получим поля для сравнения
		$fields_list = array();
		foreach ($seo_fields as $field=>$param) {
			if ($field == 'seo_url') continue;
			$fields_list[] = $field;
		}
		$fields	= implode($fields_list,', ');
		$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "category_description` WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $this->LANG_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			foreach ($fields_list as $field) {
				$data[$field] = $query->row[$field];
			}
		}

		// Сопоставляем значения к тегам
		$tags = array(
			'{cat}'			=> isset($data['name']) 		? $data['name'] 		: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 	: ''
		);

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_category_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_category_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_category_'.$field) == 'import') {
				// из свойства которое считалось при обмене
			}

			if ($this->config->get('exchange1c_seo_category_overwrite') == 'overwrite') {
				// Перезаписывать
				$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
			} else {
				// Только если поле пустое
				if (empty($data[$field])) {
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
				} else {
					$this->log("Поле '" . $field . "' не пустое", 2);
				}
			}

		}

		if (isset($data['seo_url'])) {
			if ($this->config->get('exchange1c_seo_category_overwrite') == 'overwrite') {
				$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
			} else {
				$sql = "SELECT keyword FROM `" . DB_PREFIX . "url_alias` WHERE `query` = 'category_id=" . $data['category_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$data['seo_url'] = $query->row['keyword'];
					if (empty($data['seo_url']))
						$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
				} else {
					$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
				}
			}
		}
		$this->log("<== seoGenerateCategory()",2);
	} // seoGenerateCategory()


	/**
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateManufacturer(&$data) {
		$this->log("==> seoGenerateManufacturer()",2);

		// Производители
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);


		if ($this->existTable('product_description')) {
			if ($this->existField('product_description', 'meta_h1')) {
				$seo_fields['meta_h1'] = array();
			}
			// Получим поля для сравнения
			$fields_list = array();
			foreach ($seo_fields as $field=>$param) {
				if ($field == 'seo_url') continue;
				$fields_list[] = $field;
			}
			$fields	= implode($fields_list,', ');
			$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . $data['manufacturer_id'] . " AND `language_id` = " . $this->LANG_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				foreach ($fields_list as $field) {
					$data[$field] = $query->row[$field];
				}
			}
		}

		// Сопоставляем значения к тегам
		$tags = array(
			'{brand}'		=> isset($data['name']) 			? $data['name'] 			: '',
			'{brand_id}'	=> isset($data['manufacturer_id'])	? $data['manufacturer_id'] 	: ''
		);

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_manufacturer_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_manufacturer_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_manufacturer_'.$field) == 'import') {
				// из свойства которое считалось при обмене
			}

			if ($this->config->get('exchange1c_seo_manufacturer_overwrite') == 'overwrite') {
				// Перезаписывать
				$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
			} else {
				// Только если поле пустое
				if (empty($data[$field])) {
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
				} else {
					$this->log("Поле '" . $field . "' не пустое", 2);
				}
			}

		}

		if (isset($data['seo_url'])) {
			if ($this->config->get('exchange1c_seo_manufacturer_overwrite') == 'overwrite') {
				$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
			} else {
				$sql = "SELECT keyword FROM `" . DB_PREFIX . "url_alias` WHERE `query` = 'manufacturer_id=" . $data['manufacturer_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$data['seo_url'] = $query->row['keyword'];
					if (empty($data['seo_url']))
						$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
				} else {
					$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
				}
			}
		}
		$this->log("<== seoGenerateManufacturer()",2);
	} // seoGenerateManufacturer()

	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ КАТАЛОГА ******************************
	 */

	/**
	 * Формирует строку запроса для категории
	 */
	private function prepareStrQueryCategory($data, $mode = 'set') {
		$this->log("==> prepareStrQueryCategory()",2);
		$sql = array();

		if (isset($data['top']))
			$sql[] = $mode == 'set' ? "`top` = " .			(int)$data['top']			: "top";
		if (isset($data['column']))
			$sql[] = $mode == 'set' ? "`column` = " .		(int)$data['column']		: "column";
		if (isset($data['sort_order']))
			$sql[] = $mode == 'set' ? "`sort_order` = " . 	(int)$data['sort_order']	: "sort_order";
		if (isset($data['status']))
			$sql[] = $mode == 'set' ? "`status` = " . 		(int)$data['status']		: "status";
		if (isset($data['noindex']))
			$sql[] = $mode == 'set' ? "`noindex` = " . 		(int)$data['noindex']		: "noindex";
		if (isset($data['parent_id']))
			$sql[] = $mode == 'set' ? "`parent_id` = " . 	(int)$data['parent_id']		: "parent_id";
		$this->log("<== prepareStrQueryCategory()", 2);
		return implode(($mode = 'set' ? ', ' : ' AND '), $sql);
	} //prepareStrQueryCategory()


	/**
	 * Формирует строку запроса для описания категорий и товаров
	 */
	private function prepareStrQueryDescription($data, $mode = 'set') {
		$this->log("==> prepareStrQueryDescription()",2);
		$sql = array();
		if (isset($data['name']))
			$sql[] = $mode == 'set' 	? "`name` = '" .				$this->db->escape($data['name']) . "'"				: "`name`";
		if (isset($data['description']))
			$sql[] = $mode == 'set' 	? "`description` = '" .			$this->db->escape($data['description']) . "'"		: "`description`";
		if (isset($data['meta_title']))
			$sql[] = $mode == 'set' 	? "`meta_title` = '" .			$this->db->escape($data['meta_title']) . "'"		: "`meta_title`";
		if (isset($data['meta_h1']))
			$sql[] = $mode == 'set' 	? "`meta_h1` = '" .				$this->db->escape($data['meta_h1']) . "'"			: "`meta_h1`";
		if (isset($data['meta_description']))
			$sql[] = $mode == 'set' 	? "`meta_description` = '" .	$this->db->escape($data['meta_description']) . "'"	: "`meta_description`";
		if (isset($data['meta_keyword']))
			$sql[] = $mode == 'set' 	? "`meta_keyword` = '" .		$this->db->escape($data['meta_keyword']) . "'"		: "`meta_keyword`";
		if (isset($data['tag']))
			$sql[] = $mode == 'set' 	? "`tag` = '" .					$this->db->escape($data['tag']) . "'"				: "`tag`";

		$this->log("<== prepareStrQueryDescription()", 2);
		return implode(($mode = 'set' ? ', ' : ' AND '), $sql);
	} //prepareStrQueryDescription()


	/**
	 * Подготавливает запрос для товара
	 */
	private function prepareQueryProduct($data, $mode = 'set') {
		$this->log('==> prepareQueryProduct()',2);
		$sql = array();
		if (isset($data['model']))
	 		$sql[] = $mode == 'set'		? "`model` = '" .				$this->db->escape($data['model']) . "'"				: "`model`";
		if (isset($data['sku']))
	 		$sql[] = $mode == 'set'		? "`sku` = '" .					$this->db->escape($data['sku']) . "'"				: "`sku`";
		if (isset($data['upc']))
	 		$sql[] = $mode == 'set'		? "`upc` = '" .					$this->db->escape($data['upc']) . "'"				: "`upc`";
		if (isset($data['ean']))
	 		$sql[] = $mode == 'set'		? "`ean` = '" .					$this->db->escape($data['ean']) . "'"				: "`ean`";
		if (isset($data['jan']))
	 		$sql[] = $mode == 'set'		? "`jan` = '" .					$this->db->escape($data['jan']) . "'"				: "`jan`";
		if (isset($data['isbn']))
	 		$sql[] = $mode == 'set'		? "`isbn` = '" .				$this->db->escape($data['isbn']) . "'"				: "`isbn`";
		if (isset($data['mpn']))
	 		$sql[] = $mode == 'set'		? "`mpn` = '" .					$this->db->escape($data['mpn']) . "'"				: "`mpn`";
		if (isset($data['location']))
	 		$sql[] = $mode == 'set'		? "`location` = '" .			$this->db->escape($data['location']) . "'"			: "`location`";
		if (isset($data['quantity']))
	 		$sql[] = $mode == 'set'		? "`quantity` = '" .			(float)$data['quantity'] . "'"						: "`quantity`";
		if (isset($data['minimum']))
	 		$sql[] = $mode == 'set'		? "`minimum` = '" .				(float)$data['minimum'] . "'"						: "`minimum`";
		if (isset($data['subtract']))
	 		$sql[] = $mode == 'set'		? "`subtract` = '" .			(int)$data['subtract'] . "'"						: "`subtract`";
		if (isset($data['stock_status_id']))
	 		$sql[] = $mode == 'set'		? "`stock_status_id` = '" .		(int)$data['stock_status_id'] . "'"					: "`stock_status_id`";
		if (isset($data['date_available']))
	 		$sql[] = $mode == 'set'		? "`date_available` = '" .		$this->db->escape($data['date_available']) . "'"	: "`date_available`";
		if (isset($data['manufacturer_id']))
	 		$sql[] = $mode == 'set'		? "`manufacturer_id` = '" .		(int)$data['manufacturer_id'] . "'"					: "`manufacturer_id`";
		if (isset($data['shipping']))
	 		$sql[] = $mode == 'set'		? "`shipping` = '" .			(int)$data['shipping'] . "'"						: "`shipping`";
		if (isset($data['price']))
	 		$sql[] = $mode == 'set'		? "`price` = '" .				(float)$data['price'] . "'"							: "`price`";
		if (isset($data['points']))
	 		$sql[] = $mode == 'set'		? "`points` = '" .				(int)$data['points'] . "'"							: "`points`";
		if (isset($data['length']))
	 		$sql[] = $mode == 'set'		? "`length` = '" .				(float)$data['length'] . "'"						: "`length`";
		if (isset($data['width']))
	 		$sql[] = $mode == 'set'		? "`width` = '" .				(float)$data['width'] . "'"							: "`width`";
		if (isset($data['weight']))
	 		$sql[] = $mode == 'set'		? "`weight` = '" .				(float)$data['weight'] . "'"						: "`weight`";
		if (isset($data['height']))
	 		$sql[] = $mode == 'set'		? "`height` = '" .				(float)$data['height'] . "'"						: "`height`";
		if (isset($data['status']))
	 		$sql[] = $mode == 'set'		? "`status` = '" .				(int)$data['status'] . "'"							: "`status`";
		if (isset($data['noindex']))
	 		$sql[] = $mode == 'set'		? "`noindex` = '" .				(int)$data['noindex'] . "'"							: "`noindex`";
		if (isset($data['tax_class_id']))
	 		$sql[] = $mode == 'set'		? "`tax_class_id` = '" .		(int)$data['tax_class_id'] . "'"					: "`tax_class_id`";
		if (isset($data['sort_order']))
	 		$sql[] = $mode == 'set'		? "`sort_order` = '" .			(int)$data['sort_order'] . "'"						: "`sort_order`";
		if (isset($data['length_class_id']))
	 		$sql[] = $mode == 'set'		? "`length_class_id` = '" .		(int)$data['length_class_id'] . "'"					: "`length_class_id`";
		if (isset($data['weight_class_id']))
	 		$sql[] = $mode == 'set'		? "`weight_class_id` = '" .		(int)$data['weight_class_id'] . "'"					: "`weight_class_id`";

		return implode(($mode = 'set' ? ', ' : ' AND '),$sql);

	} // prepareQueryProduct()



	/**
	 * Формирует строку запроса для описания производителя
	 */
	private function prepareStrQueryManufacturerDescription($data) {

		$this->log('==> prepareStrQueryManufacturerDescription()',2);

		$sql  = isset($data['description']) 		? ", `description` = '" . $this->db->escape($data['description']) . "'"					: "";
		if ($this->existField("manufacturer_description", "name")) {
			// Пока не знаю зачем это поле было добавлено в ocStore 2.1.0.2.1, в ocShop 2.1.0.1.4 его нет
			$sql .= isset($data['name']) 				? ", `name` = '" . $this->db->escape($data['name']) . "'" 							: "";
		}
		$sql .= isset($data['meta_description']) 	? ", `meta_description` = '" . $this->db->escape($data['meta_description']) . "'" 		: "";
		$sql .= isset($data['meta_keyword']) 		? ", `meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'"				: "";
		$sql .= isset($data['meta_title']) 			? ", `meta_title` = '" . $this->db->escape($data['meta_title']) . "'"					: "";
		$sql .= isset($data['meta_h1']) 			? ", `meta_h1` = '" . $this->db->escape($data['meta_h1']) . "'" 						: "";
		return $sql;
	} //prepareStrQueryManufacturerDescription()


	/**
	 * Сравнивает запрос с массивом данных и формирует список измененных полей
	 */
	private function compareArrays($query, $data) {
		$this->log("==> compareArrays()", 2);
		// Сравниваем значения полей, если есть изменения, формируем поля для запроса
		$upd_fields = array();
		if ($query->num_rows) {
			foreach($query->row as $key => $row) {
				if (!isset($data[$key])) continue;
				if ($row <> $data[$key]) {
					$upd_fields[] = $key . " = '" . $this->db->escape($data[$key]) . "'";
					$this->log("[i] Отличается поле '" . $key . "'", 2);
				} else {
					$this->log("[i] Поле '" . $key . "' не имеет отличий", 2);
				}
			}
		}
		$this->log("<== compareArrays()", 2);
		return implode(', ', $upd_fields);
	} // compareArrays()


	/**
	 * Заполняет родительские категории у продукта
	 */
	public function fillParentsCategories($data) {

		$this->log('==> fillParentsCategories(data)',2);
		if (!$data['product_id']) {
			$this->log(1,"[ERROR] Заполнение родительскими категориями отменено, т.к. не указан product_id!");
			return false;
		}

		// Подгружаем только один раз
		if (empty($data['product_categories'])) {
			$this->log('<== fillParentsCategories(data) data[product_categories] уже заполнено',2);
			return false;
		}

		// Определяем наличие поля main_category
		$main_category = $this->existField('product_to_category', 'main_category');

		// Читаем все категории товара
		$product_categories = array();
		$fields = "`category_id`";
		if ($main_category) {
			$fields .= ", `main_category`";
		}
		$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $data['product_id'];
		$this->log($sql,2);
		$query = $this->db->query($sql);
		//$this->log($query,2);
		foreach ($query->rows as $row) {
			if ($main_category)
				$product_categories[$row['category_id']] = $row['main_category'];
			else
				$product_categories[$row['category_id']] = 0;
		}

		// Перезаписываем все родительские категории
//		$sql = "DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $data['product_id'];
//		$this->log($sql,2);
//		$this->db->query($sql);

		$this->load->model('catalog/product');

		//$this->log($product_categories,2);
		$product_cats_id = $data['product_categories'];
		foreach ($data['product_categories'] as $category_id) {
			$parents_id = $this->findParentsCategories($category_id);
			foreach ($parents_id as $parent_id) {
				$key = array_search($parent_id, $product_cats_id);
				if ($key === false)
					$product_cats_id[] = $parent_id;
			}
			//$this->log($product_cats_id,2);
		}
		foreach ($product_cats_id as $parent_id) {
			//$this->log('$parent_id = ' . $parent_id, 2);
			if ($parent_id != 0) {
				if (isset($product_categories[$parent_id])) {
					//$this->log('$product_categories[$parent_id] = ' . $product_categories[$parent_id], 2);
					//$this->log("unset, parent_id = ".$parent_id,2);
						unset($product_categories[$parent_id]);
				} else {
					$this->log("insert, parent_id = ".$parent_id,2);
					if ($main_category)
						$field_main_category = ", `main_category` = " . ($category_id == $parent_id ? 1 : 0);
					else
						$field_main_category = '';
					$sql = "INSERT INTO `" .DB_PREFIX . "product_to_category` SET `product_id` = " . $data['product_id'] . ", `category_id` = " . $parent_id . $field_main_category;
					$this->log($sql,2);
					$this->db->query($sql);
				}
				$this->log("> Родительская категория, category_id: " . $parent_id, 2);
			}
		}
		$this->log(1,"[i] Заполнены родительские категории");
		return true;
	} // fillParentsCategories()


	/**
	 * Ищет все родительские категории
	 *
	 * @param	int
	 * @return	array
	 */
	private function findParentsCategories($category_id) {
		$this->log('==> findParentsCategories()',2);
		$result = array();
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX ."category` WHERE `category_id` = " . $category_id);
		if (isset($query->row['parent_id'])) {
			if ($query->row['parent_id'] <> 0) {
				$result[] = $query->row['parent_id'];
				$result = array_merge($result, $this->findParentsCategories($query->row['parent_id']));
			}
		}
		return $result;
	} // findParentsCategories()


	/**
	 * Устанавливает в какой магазин загружать данные
	 */
	private function setStore($classifier_name) {
		$this->log('==> setStore()',2);
		$config_stores = $this->config->get('exchange1c_stores');
		if (!$config_stores) {
			$this->STORE_ID = 0;
			return;
		}
		// Если ничего не заполнено - по умолчанию
		foreach ($config_stores as $key => $config_store) {
			if ($classifier_name == "Классификатор (" . $config_store['name'] . ")") {
				$this->STORE_ID = $config_store['store_id'];
			}
		}
	} // setStore()


	/**
	 * Возвращает id по cml_id
	 */
	private function getCategoryIdBycml_id($cml_id) {
		$this->log('==> getCategoryIdBycml_id()',2);
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_to_1c` WHERE `1c_id` = '" . $this->db->escape($cml_id) . "'");
		$category_id = isset($query->row['category_id']) ? $query->row['category_id'] : 0;

		// Проверим существование такого товара
		if ($category_id) {
			$query = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `category_id` = " . (int)$category_id);
			if (!$query->num_rows) {

				// Удалим неправильную связь
				$this->db->query("DELETE FROM `" . DB_PREFIX . "category_to_1c` WHERE `category_id` = " . (int)$category_id);

				$category_id = 0;
			}
		}

		return $category_id;

	} // getCategoryIdBycml_id()


	/**
	 * Возвращает id по названию и уровню категории
	 */
	private function getCategoryIdByName($name, $parent_id = 0) {
		$this->log("==> getCategoryIdByName(), name  = '" . $name . "', parent_id = '" . $parent_id . "'", 2);
		$sql = "SELECT `c`.`category_id` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX. "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`name` = LOWER('" . $this->db->escape(strtolower($name)) . "') AND `cd`.`language_id` = " . $this->LANG_ID . " AND `c`.`parent_id` = " . $parent_id;
		$this->log($sql, 2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$this->log("<== getCategoryIdByName(), category_id = " . $query->row['category_id'], 2);
			return $query->row['category_id'];
		}
		$this->log("<== getCategoryIdByName(), category_id = 0", 2);
		return 0;
	} // getCategoryIdByName()


	/**
	 * Возвращает массив id,name категории по cml_id
	 */
	private function getCategoryBycml_id($cml_id) {
		$this->log('==> getCategoryBycml_id()',2);
		$query = $this->db->query("SELECT `c`.`category_id`, `cd`.`name` FROM `" . DB_PREFIX . "category_to_1c` `c` LEFT JOIN `" . DB_PREFIX. "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `c`.`1c_id` = '" . $this->db->escape($cml_id) . "' AND `cd`.`language_id` = " . $this->LANG_ID);
		return $query->rows;
	} // getCategoryBycml_id()


	/**
	 * Обновляет описание категории
	 */
	private function updateCategoryDescription($data) {
		$this->log("==> updateCategoryDescription()", 2);

		// Надо ли обновлять
		$fields = $this->prepareStrQueryDescription($data, 'get');
		if ($fields) {
			$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "category_description` `cd` LEFT JOIN `" . DB_PREFIX . "category_to_store` `cs` ON (`cd`.`category_id` = `cs`.`category_id`) WHERE `cd`.`category_id` = " . $data['category_id'] . " AND `cd`.`language_id` = " . $this->LANG_ID . " AND `cs`.`store_id` = " . $this->STORE_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);
		} else {
			// Нечего даже обновлять
			$this->log("<== updateCategoryDescription() - нет данных", 2);
			return false;
		}

//		$this->log($query,2);
//		$this->log($data,2);

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$fields = $this->compareArrays($query, $data);

//		$this->log($fields,2);

		// Если есть расхождения, производим обновление
		if ($fields) {
			$sql = "UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $this->LANG_ID;
			$this->log($sql,2);
			$this->db->query($sql);

			$sql = "UPDATE `" . DB_PREFIX . "category` SET date_modified = NOW() WHERE `category_id` = " . $data['category_id'];
			$this->log($sql,2);
			$this->db->query($sql);

			$this->log("<== updateCategoryDescription(), обновленые поля: '" . $fields . "'", 2);
			return true;
		}

		$this->log("[i] Описание категории не нуждается в обновлении",2);
		$this->log("<== updateCategoryDescription()", 2);
		return false;

	} // updateCategoryDescription()


	/**
	 * Добавляет иерархию категории
	 */
	private function addHierarchical($category_id, $data) {
		$this->log("==> addHierarchical()", 2);

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['parent_id'] . " ORDER BY `level` ASC");

		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $category_id . ", `path_id` = " . (int)$result['path_id'] . ", `level` = " . $level);
			$level++;
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $category_id . ", `path_id` = " . $category_id . ", `level` = " . $level);

		$this->log("==> addHierarchical()", 2);
	} // addHierarchical()


	/**
	 * Обновляет иерархию категории
	 */
	private function updateHierarchical($data) {
		$this->log("==> updateHierarchical()", 2);

		// MySQL Hierarchical Data Closure Table Pattern
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `path_id` = " . $data['category_id'] . " ORDER BY `level` ASC");

		if ($query->rows) {
			foreach ($query->rows as $category_path) {
				// Delete the path below the current one
				$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_path['category_id'] . " AND `level` < " . (int)$category_path['level']);

				$path = array();

				// Get the nodes new parents
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['parent_id'] . " ORDER BY `level` ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Get whats left of the nodes current path
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $category_path['category_id'] . " ORDER BY `level` ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Combine the paths with a new level
				$level = 0;

				foreach ($path as $path_id) {
					$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $category_path['category_id'] . ", `path_id` = " . $path_id . ", `level` = " . $level);

					$level++;
				}
			}
		} else {
			// Delete the path below the current one
			$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['category_id']);

			// Fix for records with no paths
			$level = 0;

			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['parent_id'] . " ORDER BY `level` ASC");

			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $data['category_id'] . ", `path_id` = " . (int)$result['path_id'] . ", `level` = " . $level);

				$level++;
			}

			$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $data['category_id'] . ", `path_id` = " . $data['category_id'] . ", `level` = " . $level);
		}

		$this->log("<== updateHierarchical()", 2);

	} // updateHierarchical()


	/**
	 * Обновляет категорию
	 */
	private function updateCategory($data) {

		$this->log("==> updateCategory() ------------------------------",2);
		$this->log("> Категория : '" . $data['name'] . "'", 2);

		// Читаем старые данные
		$sql = $this->prepareStrQueryCategory($data, 'get');
		$this->log($sql, 2);
		if ($sql) {
			$sql = "SELECT " . $sql . " FROM `" . DB_PREFIX . "category` WHERE `category_id` = " . $data['category_id'];
			$this->log($sql,2);
			$query = $this->db->query($sql);
//			$this->log($query, 2);

			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$fields = $this->compareArrays($query, $data);

			if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "category` SET " . $fields . ", `date_modified` = NOW() WHERE `category_id` = " . $data['category_id'];
				$this->log($sql,2);
				$this->db->query($sql);

				// Запись иерархии категорий если были изменения
				$this->updateHierarchical($data);

				// SEO
		 		$this->seoGenerateCategory($data);
			}
		}

		// Если было обновление описания
		$this->updateCategoryDescription($data);

		$this->cache->delete('category');

		$this->log("<== updateCategory()", 2);

	} // updateCategory()


	/**
	 * Добавляет связь между группами в 1С и CMS
	 */
	private function insertCategoryLinkToCML($category_id, $cml_id) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_1c` SET `category_id` = " . (int)$category_id . ", `1c_id` = '" . $this->db->escape($cml_id) . "'");
	}


	/**
	 * Добавляет категорию
	 */
	private function addCategory($data) {

		$this->log("==> addCategory() ------------------------------", 2);
		$this->log("> Категория: '" . $data['name'] . "'");

		if ($data == false) return 0;

		if ($this->config->get('exchange1c_status_new_category') == 0){
			$data['status'] = 0;
		}

		$sql = $this->prepareStrQueryCategory($data);
		$sql = "INSERT INTO `" . DB_PREFIX . "category` SET " . $sql . ", `date_modified` = NOW(), `date_added` = NOW()";
		$this->log($sql,2);
		$this->db->query($sql);

		$data['category_id'] = $this->db->getLastId();

		// SEO
 		$this->seoGenerateCategory($data);

		//$this->log($data,2);
		// Описание
		$fields = $this->prepareStrQueryDescription($data, 'set');
		//$this->log($fields, 2);

		if ($fields) {
			$sql = "SELECT category_id FROM `" . DB_PREFIX . "category_description` WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $this->LANG_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$this->log("[i] Добавление описания к категории отменено, так оно уже существует у category_id = " . $data['category_id']);
				return $data['category_id'];
			}

			$sql = "INSERT INTO `" . DB_PREFIX . "category_description` SET `category_id` = " . $data['category_id'] . ", `language_id` = " . $this->LANG_ID . ", " . $fields;
			$this->log($sql,2);
			$this->db->query($sql);
		}

		// Запись иерархии категорий для админки
		$this->addHierarchical($data['category_id'], $data);

		// Магазин
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET `category_id` = " . $data['category_id'] . ", `store_id` = " . $this->STORE_ID);

		// Добавим линк
		$this->insertCategoryLinkToCML($data['category_id'], $data['cml_id']);

		// Чистим кэш
		$this->cache->delete('category');

		$this->log("> Категория добавлена: '" . $data['name'] . "', id: " . $data['category_id'] . ", Ид: " . $data['cml_id'],2);

		$this->log("<== addCategory(), return category_id: " . $data['category_id'], 2);
		return $data['category_id'];

	} // addCategory()


	/**
	 * Обрабатывает категории
	 */
	private function parseCategories($xml, $parent_id=0) {
		$this->log("==> parseCategories()", 2);

		foreach ($xml->Группа as $category){
			if (isset($category->Ид) && isset($category->Наименование) ){

				$data = array();
				$data['cml_id']			= (string)$category->Ид;
				$data['category_id']	= $this->getCategoryIdBycml_id($data['cml_id']);
				$data['parent_id']		= $parent_id;
				$data['status']			= 1;
				$data['sort_order']		= isset($category->Сортировка) ? (int)$category->Сортировка : 0;

				if ($parent_id == 0)
					$data['top']		= 1;

				// Определяем наименование и порядок, сортировка - число до точки, наименование все что после точки
				$split = $this->splitNameStr((string)$category->Наименование, false);
				$data['sort_order']	= $split['order'];
				$data['name']	= $split['name'];

				$this->log("[i] Определили порядок группы: '" . $data['sort_order'] . "'", 2);
				$this->log("[i] Определили названия группы: '" . $data['name'] . "'", 2);

				// Если не нашли категорию по Ид, пытаемся найти по имени учитывая id родительской категории
				if (!$data['category_id']) {
					$data['category_id'] = $this->getCategoryIdByName($data['name'], $parent_id);
					// Если нашли, добавляем связь
					if ($data['category_id'])
						$this->insertCategoryLinkToCML($data['category_id'], $data['cml_id']);
				}

				if (!$data['category_id']) {
					$data['category_id'] = $this->addCategory($data);
				} else {
					$this->updateCategory($data);
				}
			}
			if ($category->Группы) {
				$this->parseCategories($category->Группы, $data['category_id']);
			}
		}
		$this->log("<== parseCategories()", 2);
	} // parseCategories()


	/**
	 * ******************************************* ОПЦИИ *********************************************
	 */


	/**
	 * Поиск значения опции по названию
	 */
	private function getOptionValueByName($option_id, $name) {

		$this->log("==> getOptionValueByName()", 2);
		$sql = "SELECT `option_value_id` FROM `" . DB_PREFIX . "option_value_description` WHERE `language_id` = " . $this->LANG_ID . " AND `option_id` = " . $option_id . " AND `name` = '" . $this->db->escape($name) . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

        if ($query->num_rows) {
 			$this->log("<== getOptionValueByName(), наден option_value_id = " . $query->row['option_value_id'], 2);
        	return $query->row['option_value_id'];
       	}

 		$this->log("<== getOptionValueByName(), не надено.", 2);
		return 0;

	} // getOptionValueByName()


	/**
	 * Добавляет или получает значение опции по названию
	 */
	private function setOptionValue($option_id, $value, $image='', $sort_order=0) {

		$this->log("==> setOptionValue()", 2);
		// Проверим есть ли такое значение
		$option_value_id = $this->getOptionValueByName($option_id, $value);

		if ($option_value_id)
			return $option_value_id;

		$sql = "INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = " . $option_id . ", `image` = '" . $this->db->escape($image) . "', `sort_order` = " . $sort_order;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		$option_value_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "option_value_description` SET `option_id` = " . $option_id . ", `option_value_id` = " . $option_value_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($value) . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		return $option_value_id;

	} // setOptionValue()


	/**
	 * Добавляет или получает значение опциию по названию
	 */
	private function setOptionValues($option_id, $values) {
		$this->log("==> setOptionValues(), option_id=".$option_id.", values:", 2);
		$this->log($values, 2);

		// Удалим все значения опции
		//$sql = "DELETE FROM `" . DB_PREFIX . "option_value` WHERE option_id = '" . $option_id . "'";
 		//$this->log($sql,2);
		//$this->db->query($sql);

		foreach ($values as $key => $value) {

			$option_value_id = 0;

			$sql = "SELECT `ov`.`option_value_id` FROM `" . DB_PREFIX . "option_value` `ov` LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ov`.`option_value_id` = `ovd`.`option_value_id`) WHERE `ov`.`option_id` = " . $option_id . " AND `ovd`.`language_id` = '" . $this->LANG_ID . "' AND `ovd`.`name` = '" . $this->db->escape($value['name']) . "'";
	 		$this->log($sql,2);
			$query = $this->db->query($sql);

			if ($query->num_rows) {
				$option_value_id = $query->row['option_value_id'];
			}

			if (!$option_value_id) {

				$sql = "INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = " . $option_id . ", `image` = '" . $this->db->escape($value['image']) . "', `sort_order` = " . $value['sort_order'];
		 		$this->log($sql,2);
				$query = $this->db->query($sql);

				$option_value_id = $this->db->getLastId();

				$sql = "INSERT INTO `" . DB_PREFIX . "option_value_description` SET `option_id` = " . $option_id . ", `option_value_id` = '" . $option_value_id . "', `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($value['name']) . "'";
		 		$this->log($sql,2);
				$query = $this->db->query($sql);

			}

			$values[$key]['option_value_id'] = $option_value_id;
		}

		$this->log("<== setOptionValues(), return:", 2);
		$this->log($values, 2);
		return $values;

	} // setOptionValues()


	/**
	 * Установка опции
	 */
	private function setOption($name, $type = 'select', $sort_order = 0) {
		$this->log("==> setOption()", 2);

		$sql = "SELECT `o`.`option_id`, `o`.`type`, `o`.`sort_order` FROM `" . DB_PREFIX . "option` `o` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`o`.`option_id` = `od`.`option_id`) WHERE `od`.`name` = '" . $this->db->escape($name) . "' AND `od`.`language_id` = " . $this->LANG_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		//$this->log($query, 2);
        if ($query->num_rows) {

			$option_id = $query->row['option_id'];

			$fields = array();
        	if ($query->row['type'] <> $type) {
        		$fields[] = "`type` = '" . $type . "'";
        	}
        	if ($query->row['sort_order'] <> $sort_order) {
        		$fields[] = "`sort_order` = " . (int)$sort_order;
        	}
        	$fields = implode(', ', $fields);
        	//$this->log("fields: " . $fields, 2);
        	if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "option` SET " . $fields . " WHERE `option_id` = " . $option_id;
				$this->log($sql, 2);
				$this->db->query($sql);
        	}
        }

		// Если опции нет, добавляем
		if (empty($option_id)) {
			$option_id = $this->addOption($name, $type);
		}

		$this->log("<== setOption(), return option_id: " . $option_id, 2);
		return $option_id;
	} // setOption()


	/**
	 * **************************************** ОПЦИИ ТОВАРА ******************************************
	 */


	/**
	 * Поиск опции в товаре по id товара и id опции
	 */
	private function getProductOption($product_id, $option_id, $value='') {
		$this->log("==> getProductOption()", 2);

		// Ищем опцию в товаре
		$sql = "SELECT `product_option_id` FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = " . $product_id . " AND `option_id` = " . $option_id . " AND `value` = '" . $this->db->escape($value) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
        if ($query->num_rows) {
        	$this->log("<== getProductOption(), return product_option_id: " . $query->row['product_option_id'], 2);
        	return $query->row['product_option_id'];
        }

        $this->log("<== getProductOption(), return product_option_id: 0", 2);
		return 0;
	} // getProductOption()


	/**
	 * добавляет опцию в товар
	 */
	private function addProductOption($product_id, $option_id, $option_name='', $required=1) {
		$this->log("==> addProductOption()", 2);

		// Добавляем опцию в товар
		$sql = "INSERT INTO `" . DB_PREFIX . "product_option` SET `product_id` = " . $product_id . ", `option_id` = " . $option_id . ", `value` = '" . $this->db->escape($option_name) . "', `required` = " . $required;
 		$this->log($sql,2);
		$this->db->query($sql);

		$product_option_id = $this->db->getLastId();

		$this->log("<== addProductOption(), return: " . $product_option_id, 2);
       	return $product_option_id;
	} // addProductOption()


	/**
	 * Добавляет или находит опцию в товаре и возвращает ID
     * $data['product_id'], $option_id, $option_name
	 */
	private function setProductOption($product_id, $option_id, $option_name) {
		$this->log("==> setProductOption(), name = " . $option_name . ", option_id = " . $option_id, 2);
		$options = array();

		// Ищем опцию
		$product_option_id = $this->getProductOption($product_id, $option_id, $option_name);

		if (!$product_option_id) {
			$product_option_id = $this->addProductOption($product_id, $option_id, $option_name);
		}

		$this->log("<== setProductOption(), return: " . $product_option_id, 2);
		return $product_option_id;
	} // setProductOption()


	/**
	 * Удаление опции товара
	 */
	private function deleteProductOption($product_option_id) {

		$this->log("==> deleteProductOption()", 2);

		// Удалим старые опции
		$sql = "DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_option_id` = " . $product_option_id;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		$this->log("<== deleteProductOption()", 2);
	} // deleteProductOptions()


	/**
	 * Поиск значения опции товара по всем id
	 */
	private function getProductOptionValue($feature, $option, $data) {
		$this->log("==> getProductOptionValue()", 2);

		//$sql = "SELECT product_option_value_id FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_id` = " . $option['product_option_id'] . " AND `product_id` = " . $data['product_id'] . " AND `option_id` = " . $option['option_id'] . " AND option_value_id = " . $option['option_value_id'] . " AND `product_feature_id` = " . $feature['product_feature_id'];
		$sql = "SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_id` = " . $option['product_option_id'] . " AND `product_id` = " . $data['product_id'] . " AND `option_id` = " . $option['option_id'] . " AND option_value_id = " . $option['option_value_id'];
 		$this->log($sql,2);
		$query = $this->db->query($sql);

        if ($query->num_rows) {
			$this->log("<== getProductOptionValue(), есть данные: " . $query->num_rows, 2);
			return $query->row;
       	}

		$this->log("<== getProductOptionValue(), нет данных", 2);
		return array();

	} // getProductOptionValues()


	/**
	 * Добавляет опцию в товар
	 */
	private function addProductOptionValue($feature, $option, $data) {
		$this->log("==> addProductOption()", 2);

		// Добавляем опцию в товар
		//$sql = "INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = " . $option['product_option_id'] . ", `product_id` = " . $data['product_id'] . ", `option_id` = " . $option['option_id'] . ", `product_feature_id` = " . $feature['product_feature_id'] . ", quantity = '" . $feature['quantities']['quantity'] . "', `subtract` = " . $option['subtract'];
		//$sql = "INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = " . $option['product_option_id'] . ", `product_id` = " . $data['product_id'] . ", `option_id` = " . $option['option_id'] . ", quantity = '" . $feature['quantities']['quantity'] . "', `subtract` = " . $option['subtract'];

        if (isset($feature['product_quantity'])) {
        	if (count($feature['product_quantity']))
        		$quantity = $feature['quantities']['quantity'];
        } elseif (isset($feature['quantity'])) {
        	$quantity = $feature['quantity'];
		} else {
        	$this->log(" > feature['quantities'] - не определено", 2);
        	$quantity = 0;
        }

		$sql = "INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = " . $option['product_option_id'] . ", `product_id` = " . $data['product_id'] . ", `option_value_id` = " . $option['option_value_id'] . ", `option_id` = " . $option['option_id'] . ", quantity = '" . $quantity . "', `subtract` = " . $option['subtract'];
 		$this->log($sql,2);
		$this->db->query($sql);

		$product_option_value_id = $this->db->getLastId();

		$this->log("<== addProductOptionValue(), return product_option_value_id = " . $product_option_value_id, 2);
       	return $product_option_value_id;
	} // addProductOptionValue()


	/**
	 * Обновляет опцию в товар
	 */
	private function updateProductOptionValue($product_option_value_id, $quantity, $price_prefix = "", $price = 0) {
		$this->log("==> updateProductOptionValue()", 2);

		$sql = "SELECT `quantity`,`price_prefix`,`price` `" . DB_PREFIX . "product_option_value` WHERE `product_option_value_id` = " . $product_option_value_id;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		$sql = "";
		if ($query->row['quantity'] <> $quantity) {
			$sql .= " `quantity` = " . $quantity;
		}
		if ($query->row['price_prefix'] <> $price_prefix && $query->row['price'] <> $price) {
			$sql .= ($sql ? "," : "") . " `price_prefix` = " . $price_prefix . ", `price` = " . $price;
		}

		if ($sql) {
			$sql = "UPDATE `" . DB_PREFIX . "product_option_value` SET " . $sql . " WHERE `product_option_value_id` = ". $product_option_value_id;
	 		$this->log($sql,2);
			$this->db->query($sql);
			$this->log("<== updateProductOptionValue(), return: true", 2);
			return true;
		}

		$this->log("<== updateProductOptionValue(), return: false", 2);
       	return false;
	} // updateProductOptionValue()


	/**
	 * Устанавливаем опцию в товар
	 */
	private function setProductOptionValue($feature, $option, $data) {
		$this->log("==> setProductOptionValue(), value = " . $option['value'], 2);

		$product_option_value = $this->getProductOptionValue($feature, $option, $data);

		if (empty($product_option_value)){
			$product_option_value_id = $this->addProductOptionValue($feature, $option, $data);
		} else {
			$product_option_value_id = $product_option_value['product_option_value_id'];
			$this->log('product_option_value:', 2);
			$this->log($product_option_value, 2);

			// В режиме загрузки характеристик - характеристика, записываем остаток и разницу цен в опции
			// Хотя остатки и цены хранятся в отдельных таблицах
			if ($this->config->get('exchange1c_product_options_mode') == 'feature') {
				// Определим разницу в цене
				//$this->updateProductOptionValue($product_option_value_id, $feature['quantity'], $price_prefix, $price);
			}
		}

		$this->log("<== addProductOptionValue(), return: " . $product_option_value_id, 2);
       	return $product_option_value_id;
	} // addProductOptionValue()


	/**
	 * Удаляет значение опции из товара
	 */
	private function deleteProductOptionValue($product_option_value_id) {
		$this->log("==> deleteProductOptionValue()", 2);

		// Добавляем опцию в товар
		$sql = "DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_value_id` = " . $product_option_value_id;
 		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== deleteProductOptionValue()", 2);
	} // deleteProductOptionValue()


	/**
	 * ************************************ ФУНКЦИИ ДЛЯ РАБОТЫ С ХАРАКТЕРИСТИКАМИ *************************************
	 */

	/**
	 * Ищет, проверяет, добавляет значение характеристики товара
	 */
	private function setProductFeatureValue($product_feature_id, $product_id, $product_option_id, $product_option_value_id) {

		$this->log("==> setProductFeatureValue()", 2);
		// Поищем такое значение
		$sql = "SELECT `product_feature_value_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_feature_id` = " . $product_feature_id . " AND `product_option_value_id` = " . $product_option_value_id;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->num_rows) {
			$this->log("<== setProductFeatureValue(), return: " . $query->row['product_feature_value_id'], 2);
			return $query->row['product_feature_value_id'];
		}

       	// Добавим значение
		$sql = "INSERT INTO `" . DB_PREFIX . "product_feature_value` SET `product_feature_id` = " . $product_feature_id . ", `product_id` = " . $product_id . ", `product_option_id` = " . $product_option_id . ", `product_option_value_id` = " . $product_option_value_id;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		$product_feature_value_id = $this->db->getLastId();

		$this->log("<== setProductFeatureValue(), return: " . $product_feature_value_id, 2);
		return $product_feature_value_id;

	} // setProductFeatureValue()


	/**
	 * Получить минимальную цену из всех характеристик
	 * Пока не работает, просто сохранил алгоритм в эту функцию
	 */
	private function getMinPrice(&$data) {
		// Найдем минимальную цену и установим корректировку цен для опций каждой группы покупателей
		$min_prices = array();
		foreach ($data['features'] as $feature_cml_id => $feature) {
			// Цены
			foreach($feature['prices'] as $feature_price) {
				if (isset($min_prices[$feature_price['customer_group_id']])) {
					$min_prices[$feature_price['customer_group_id']] = array(
						'price'		=> min($min_prices[$feature_price['customer_group_id']]['price'], $feature_price['price']),
						'quantity'	=> $feature_price['quantity'],
						'priority'	=> $feature_price['priority'],
						'unit_id'	=> $feature_price['unit_id']
					);
				} else {
					$min_prices[$feature_price['customer_group_id']] = array(
						'price'		=> $feature_price['price'],
						'quantity'	=> $feature_price['quantity'],
						'priority'	=> $feature_price['priority'],
						'unit_id'	=> $feature_price['unit_id']
					);
				}
			}

		}
		$data['min_prices'] = $min_prices;
	}


	/**
	 * Создает или возвращает характеристику по Ид
	 */
	private function setProductFeatures(&$data) {
		$this->log("==> setProductFeatures()", 2);

		if (!isset($data['features'])) {
			$this->log("[i] Нет характеристик");
			return false;
		}

		foreach ($data['features'] as $feature_cml_id => $feature) {
			// СВЯЗЬ ХАРАКТЕРИСТИКИ С 1С:ПРЕДПРИЯТИЕ
			// Ищем характеристику по Ид
			$sql = "SELECT * FROM `" . DB_PREFIX . "product_feature` WHERE `1c_id` = '" . $this->db->escape($feature_cml_id) . "'";
	 		$this->log($sql,2);
			$query = $this->db->query($sql);
//			$this->log($query,2);

			if ($query->num_rows) {
				$product_feature_id = $query->row['product_feature_id'];
				$data['features'][$feature_cml_id]['product_feature_id'] = $product_feature_id;

				// Сравнивает запрос с массивом данных и формирует список измененных полей
				$fields = $this->compareArrays($query, $feature);

				if ($fields) {
					$sql = "UPDATE `" . DB_PREFIX . "product_feature` SET " . $fields . " WHERE `product_feature_id` = " . $product_feature_id;
			 		$this->log($sql,2);
					$this->db->query($sql);
				}

	       	} else {
	       		// Добавляем
	       		$sql = isset($feature['name'])	? ", `name` = '"	. $this->db->escape($feature['name']) 	. "'" : "";
	       		$sql .= isset($feature['sku'])	? ", `sku` = '"		. $this->db->escape($feature['sku']) 	. "'" : "";
	       		$sql .= isset($feature['ean'])	? ", `ean` = '"		. $feature['ean'] . "'" : "";
				$sql = "INSERT INTO `" . DB_PREFIX . "product_feature` SET `1c_id` = '" . $this->db->escape($feature_cml_id) . "'" . $sql;
		 		$this->log($sql,2);
				$this->db->query($sql);

				$product_feature_id = $this->db->getLastId();
				$data['features'][$feature_cml_id]['product_feature_id'] = $product_feature_id;
	       	}

	       	// ЦЕНЫ
	       	if (isset($feature['prices'])) {
	       		$this->log("[i] Запись цены характеристики");
	       		foreach ($feature['prices'] as $price) {
	       			$this->setProductPrice($price, $data['product_id'], $product_feature_id);
	       		}
	       	}

	       	// ОСТАТКИ
	       	if (isset($feature['quantities'])) {
	       		$this->log("[i] Запись остатков характеристики");

	       		// старые остатки характеристики
				$product_old_quantities = $this->getProductQuantities($data['product_id'], $product_feature_id);

	       		foreach ($feature['quantities'] as $warehouse_id => $quantity) {
	       			$product_filter = array(
	       				'product_id'			=> $data['product_id'],
	       				'warehouse_id'			=> $warehouse_id,
	       				'product_feature_id'	=> $product_feature_id
					);
					// так как не указана какая единица измерения, подразумевается - базовая
	       			$product_quantity_id = $this->setProductQuantityNew($product_filter, $quantity);

					// которые есть остатки удаляем из массива
					if (isset($product_old_quantities[$product_quantity_id])) {
						unset($product_old_quantities[$product_quantity_id]);
					}
	       		}

				// которые остались удаляем
				foreach ($product_old_quantities as $product_quantity_id => $pq) {
					$this->log("[!] Удален старый неиспользуемый остаток товара product_quantity_id = " . $product_quantity_id, 2);
					$this->deleteProductQuantity($product_quantity_id);
				}
	       	}

		}

//		$this->log("[i] End of setProductFeatures-data:", 2);
//		$this->log($data, 2);
        $this->log("<== setProductFeatures()", 2);
		return true;

	} // setProductFeatures()


	/**
	 * Находит характеристику товара
	 */
	private function getProductFeatureId($feature_cml_id) {

		$this->log("==> getProductFeatureId()", 2);
		// Ищем характеристику по Ид
		$sql = "SELECT `product_feature_id` FROM `" . DB_PREFIX . "product_feature` WHERE `1c_id` = '" . $this->db->escape($feature_cml_id) . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->num_rows) {
			$this->log("<== getProductFeatureId(), return product_feature_id: " . $query->row['product_feature_id'], 2);
			return $query->row['product_feature_id'];
		}

		$this->log("<== getProductFeatureId(), return product_feature_id: 0", 2);
		return 0;
	} // getProductFeatureId()


	/**
	 * Обрабатывает опции характеристики
	 * и записывает их в товар
	 */
	private function setProductFeaturesOptions(&$data) {
		$this->log("==> setProductFeaturesOptions()", 2);
		//$this->log($data, 2);

		// Читаем старые опции товара, сравниваем, лишние удаляем
		$old_options = array();
		$sql = "SELECT `product_option_id` FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = " . $data['product_id'];
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		foreach ($query->rows as $option) {
			$old_options[] = $option['product_option_id'];
		}
		$this->log("old_options: ", 2);
		//$this->log($old_options, 2);

		// Читаем старые значения опциий товара
		$old_values = array();
		$sql = "SELECT `product_option_value_id` FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = " . $data['product_id'];
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		foreach ($query->rows as $value) {
			$old_values[] = $value['product_option_value_id'];
		}
		$this->log("old_values: ", 2);
		$this->log($old_values, 2);

		// Читаем старые значения характеристики текущего товара
		$old_features_values = array();
		$sql = "SELECT `product_feature_value_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_id` = " . $data['product_id'];
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		foreach ($query->rows as $value) {
			$old_features_values[] = $value['product_feature_value_id'];
		}
		$this->log("old_features_values: ", 2);
		//$this->log($old_features_values, 2);


		foreach ($data['features'] as $feature) {
			if (!isset($feature['options'])) {
				$this->log("[i] У характеристики нет опций", 2);
				return true;
			}

			// Массив с опциями, если нет опций, то массив будет пустой
			foreach ($feature['options'] as $option) {

				// Запишем опции в товар
				$option['product_option_id'] = $this->setProductOption($data['product_id'], $option['option_id'], $option['name']);
				$key = array_search($option['product_option_id'], $old_options);
				if ($key !== false) {
					$this->log("Найден ключ = " . $key, 2);
					unset($old_options[$key]);
				}

				// Запишем значения опции в товар
				$product_option_value_id = $this->setProductOptionValue($feature, $option, $data);
				$key = array_search($product_option_value_id, $old_values);
				if ($key !== false) {
					$this->log("Найден ключ = " . $key, 2);
					unset($old_values[$key]);
				}

				// Установим значение в характеристике
				$product_feature_value_id = $this->setProductFeatureValue($feature['product_feature_id'], $data['product_id'], $option['product_option_id'], $product_option_value_id);
				$key = array_search($product_feature_value_id, $old_features_values);
				if ($key !== false) {
					$this->log("Найден ключ = " . $key, 2);
					unset($old_features_values[$key]);
				}

				// Установим остатки по складам и характеристикам,
				// а также общий остаток по всем складам в товар

			}
		}

		// Удалим старые неиспользуемые опции из товара
		foreach ($old_options as $option) {
			$this->deleteProductOption($option);
		}

		// Удалим старые неиспользуемые значения опции из товара
		foreach ($old_values as $value) {
			$this->deleteProductOptionValue($value);
		}

		// Удалим старые неиспользуемые значения опции из товара
		foreach ($old_features_values as $value) {
			$this->deleteProductFeatureValue($value);
		}

		$this->log("<== setProductFeaturesOptions()", 2);
	}


	/**
	 * Удаляет значение характеристики товара
	 */
	private function deleteProductFeatureValue($product_feature_value_id) {
		$this->log("==> deleteProductFeatureValue()", 2);

		// Добавляем опцию в товар
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_feature_value_id` = " . $product_feature_value_id;
 		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== deleteProductFeatureValue()", 2);
	} // deleteProductFeatureValue()


	/**
	 * Удаление характеристик и опций у товара
	 */
	private function deleteProductFeatures($product_id) {

		$this->log("==> deleteFeatures()",2);

		// Удалим старые характеристики
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature` WHERE `product_id` = " . $product_id;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		// Удалим старые значения характеристики
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_id` = " . $product_id;
 		$this->log($sql,2);
		$query = $this->db->query($sql);

	} // deleteProductFeatures()


	/**
	 * **************************************** ФУНКЦИИ ДЛЯ РАБОТЫ С ТОВАРОМ ******************************************
	 */


	/**
	 * Добавляет товар в базу
	 */
	private function addProduct($data) {

		$this->log("==> addProduct()",2);

		if ($this->config->get('exchange1c_status_new_product') == 0){
			$data['status'] = 0;
		}

		// Подготовим список полей по которым есть данные
		$fields = $this->prepareQueryProduct($data);
		if ($fields) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product` SET " . $fields . ", `date_added` = NOW(), `date_modified` = NOW()";
			$this->log(2,$sql);
			$this->db->query($sql);

			$data['product_id'] = $this->db->getLastId();

		} else {
			// Если нет данны - выходим
			return false;
		}

		if ($this->config->get('exchange1c_import_product_name') == 'fullname' && !empty($data['full_name'])) {
			if ($data['full_name'])
				$data['name'] = $data['full_name'];
		}

		// описание (пока только для одного языка)
		$fields = $this->prepareStrQueryDescription($data);
		if ($fields) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product_description` SET `product_id` = " . $data['product_id'] . ", `language_id` = " . $this->LANG_ID . ", " . $fields;
			$this->log($sql,2);
			$this->db->query($sql);
		}

		// категории продукта
		// Если есть поле main_category
		$main_category = $this->existField("product_to_category", "main_category", 1);

		// Главная категория будет первая в списке
		if (isset($data['product_categories'])) {
			foreach ($data['product_categories'] as $key => $category_id) {
				if ($key == 0) {
					$sql = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . $data['product_id'] . ", `category_id` = " . $category_id . $main_category;
				} else {
					$sql = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . $data['product_id'] . ", `category_id` = " . $category_id;
				}
				$this->log($sql,2);
				$this->db->query($sql);
				$this->log("[i] В товар добавлена категория, category_id: " . $category_id,2);
			}
		}

		// Устанавливаем магазин
		$sql = "SELECT `store_id` FROM `" . DB_PREFIX . "product_to_store` WHERE `product_id` = " . $data['product_id'] . " AND `store_id` = " . $this->STORE_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = " . $data['product_id'] . ", `store_id` = " . (int)$this->STORE_ID);
		}

		// Связь с 1С
		$sql = "INSERT INTO `" . DB_PREFIX . "product_to_1c` SET `product_id` = " . $data['product_id'] . ", `1c_id` = '" . $this->db->escape($data['product_cml_id']) . "'";
		$this->log($sql,2);
		$this->db->query($sql);

		// Чистим кэш
		$this->cache->delete('product');

		$this->log("[i] Товар добавлен. product_id: " . $data['product_id'],2);

		$this->log("<== addProduct()",2);
		return $data['product_id'];

	} // addProduct()


	/**
	 * Обновляет описание товара в базе для одного языка
	 */
	private function updateProductDescription($data) {

		$this->log("==> updateProductDescription()",2);

		$fields = $this->prepareStrQueryDescription($data, 'get');
		if ($fields) {
			$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);
		} else {
			// Нечего обновлять даже
			$this->log("[i] Нет заданы поля для обновления",2);
			return false;
		}

		// Сравнивает запрос с массивом данных и формирует список измененных полей
//		$this->log($query,2);
//		$this->log($data,2);
		$fields = $this->compareArrays($query, $data);
//		$this->log($fields,2);

		// Если есть расхождения, производим обновление
		if ($fields) {

			$sql = "UPDATE `" . DB_PREFIX . "product_description` SET `language_id` = " . $this->LANG_ID . ", " . $fields . " WHERE `product_id` = " . $data['product_id'];
			$this->log($sql,2);
			$this->db->query($sql);

			$this->log("[i] Описание категории обновлено, обновлены поля: '" . $fields . "'",2);

			return true;
		}

		$this->log("<== updateProductDescription()",2);
		return false;

	} // updateProductDescription()


	/**
	 * Обновляет товар в базе
	 */
	private function updateProduct(&$data) {

		$this->log("==> updateProduct()", 2);
		$this->log($data,2);
		$update = false;

		// Обнуляем остаток только у тех товаров что загружаются
		if ($this->config->get('exchange1c_flush_quantity') == 1) {
			$data['quantity'] = 0;
		}

		// ФИЛЬТР ОБНОВЛЕНИЯ
		if ($this->config->get('exchange1c_import_product_name') == 'disable') {
			unset($data['name']);
			$this->log("[i] Обновление названия отключено",2);
		}
		if ($this->config->get('exchange1c_import_categories') <> 1) {
			unset($data['product_categories']);
			$this->log("[i] Обновление категорий отключено",2);
		}
		if ($this->config->get('exchange1c_import_product_description') <> 1) {
			unset($data['description']);
			$this->log("[i] Обновление описаний товаров отключено",2);
		}

		if ($this->config->get('exchange1c_import_product_manufacturer') <> 1) {
			unset($data['manufacturer_id']);
			$this->log("[i] Обновление производителя в товаре отключено",2);
		}

		// КОНЕЦ ФИЛЬТРА

		if (isset($data['features'])) {

			// наименование характеристики, связи
			$this->setProductFeatures($data);

			// Формируем список опций из всех характеристик
			$this->setProductFeaturesOptions($data);
		}

		// общий остаток товара
		if (isset($data['quantity'])) {
			$this->setQuantity($data['product_id'], $data['quantity']);
		}

		// цены без характеристик
		if (isset($data['prices'])) {
			$this->setPrice($data['product_id'], $data['prices']);
		}

		//if ($this->config->get('exchange1c_product_status_disable_if_quantity_zero') == 1) {
		//	if ($data['quantity'] <= 0) {
		//		$data['status'] = 0;
		//		$this->log("> Товар отключен, так как остаток нулевой",1);
		//	}
		//}

		// Полное наименование из 1С в товар
		if ($this->config->get('exchange1c_import_product_name') == 'fullname' && isset($data['full_name'])) {
			if ($data['full_name'])
				$data['name'] = $data['full_name'];
		}

		// Читаем только те данные, которые получены из файла
		$fields = $this->prepareQueryProduct($data, 'get');
		if ($fields) {
			$sql = "SELECT " . $fields . "  FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $data['product_id'];
			$this->log($sql,2);
			$query = $this->db->query($sql);
		}

		// SEO формируем только из offers
		//$this->seoGenerateProduct($data);

		//$this->log($data, 2);

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$fields = $this->compareArrays($query, $data);

		// Если есть что обновлять
		if ($fields) {
			$sql = "UPDATE `" . DB_PREFIX . "product` SET " . $fields . ", `date_modified` = NOW() WHERE `product_id` = " . $data['product_id'];
			$this->log($sql,2);
			$this->db->query($sql);
			$update = true;
		}

		// Обновляем описание товара
		if ($this->updateProductDescription($data))
			$update = true;

		// категории
		$main_category = $this->existField("product_to_category", "main_category", 1);

		// Читаем все категории товара
		if (isset($data['product_categories'])) {
			$categories = array();
			if ($main_category) {
				$sql = "SELECT `category_id`,`main_category`  FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $data['product_id'];
			} else {
				$sql = "SELECT `category_id`  FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $data['product_id'];
			}
			$this->log($sql,2);
			$query = $this->db->query($sql);
			foreach ($query->rows as $category) {
				$categories[$category['category_id']] = $main_category;
			}
//			$this->log($categories, 2);

			foreach ($data['product_categories'] as $key => $category_id) {
				if (isset($categories[$category_id])) {
					// Если есть ничего не делаем, отмечаем что такая группа есть
					unset($categories[$category_id]);
				} else {
					// Значит надо добавить, возможно группу удалили или изменили
					if ($key == 0) {
						$sql = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . $data['product_id'] . ", `category_id` = " . $category_id . $main_category;
					} else {
						$sql = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . $data['product_id'] . ", `category_id` = " . $category_id;
					}
					$this->log($sql,2);
					$this->db->query($sql);
				}
			}

//			$this->log($categories, 2);

			// а те которые не указаны в файле, удаляем
			foreach ($categories as $category_id => $main_category) {
				$sql = "DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $data['product_id'] . " AND `category_id` = " . $category_id;
				$this->log($sql,2);
				$this->db->query($sql);
			}

		} // isset($data['product_categories'])

		// Устанавливаем магазин
		$sql = "SELECT `store_id`  FROM `" . DB_PREFIX . "product_to_store` WHERE `product_id` = " . $data['product_id'];
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = " . $data['product_id'] . ", `store_id` = " . $this->STORE_ID;
			$this->log($sql,2);
			$this->db->query($sql);
		} else {
			if ($query->row['store_id'] <> $this->STORE_ID) {
				// Обновим, будем иметь ввиду что этот товар может быть только в одном магазине
				$sql = "UPDATE `" . DB_PREFIX . "product_to_store` SET `store_id` = " . $this->STORE_ID . " WHERE `product_id` = " . $data['product_id'];
				$this->log($sql,2);
				$this->db->query($sql);
//			} else {
//				$this->log("[i] Регистрация в магазине товара не требуется", 2);
			}
		}

		// Очистим кэш товаров
		$this->cache->delete('product');
		//$this->log("data:", 2);
		//$this->log($data, 2);
		$this->log("<== updateProduct()", 2);
		return true;

	} // updateProduct()


	/**
	 * Получает product_id по артикулу
	 */
	private function getProductBySKU($sku) {

		$this->log("==> getProductBySKU()",2);
		$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $this->db->escape($sku) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->num_rows)
			return $query->row['product_id'];
		else
			return 0;
	} // getProductBySKU()


	/**
	 * Получает product_id по наименованию товара
	 */
	private function getProductByName($name) {

		$this->log("==> getProductByName()",2);
		$sql = "SELECT `pd`.`product_id` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `name` = LOWER('" . $this->db->escape(strtolower($name)) . "')";
		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->num_rows)
			return $query->row['product_id'];
		else
			return 0;
	} // getProductByName()


	/**
	 * Получает product_id по наименованию товара
	 */
	private function getProductByEAN($ean) {

		$this->log("==> getProductByEAN()",2);
		$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `ean` = '" . $ean . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->num_rows)
			return $query->row['product_id'];
		else
			return 0;
	} // getProductByEAN()


	/**
	 * Добавляет связь товара ID с Ид в XML
	 */
	private function insertProductLinkToCML($product_id, $product_cml_id) {

		$this->log("==> insertProductLinkToCML()", 2);
		$sql = "INSERT INTO `" . DB_PREFIX . "product_to_1c` SET `product_id` = '" . (int)$product_id . "', `1c_id` = '" . $this->db->escape($product_cml_id) . "'";
		$this->log($sql,2);
		$this->db->query($sql);
		$this->log("<== insertProductLinkToCML()", 2);

	} // insertProductLinkToCML()

	/**
	 * Обновление или добавление товара
	 * вызывается при обработке каталога
	 */
 	private function setProduct(&$data) {
		$this->log("==> setProduct()", 2);

		if (empty($data)) {
			$this->log("[ERROR] Нет входящих данных");
			$this->log("<== setProduct() return: false", 2);
			return false;
		}

		if (!$data['product_cml_id']) {
			$this->log("[ERROR] Не задан Ид товара");
			$this->log("<== setProduct() return: false", 2);
			return false;
		}

 		// Ищем товар...
 		$data['product_id'] = $this->getProductIdByCML($data['product_cml_id']);
 		if (!$data['product_id']) {
 			if ($this->config->get('exchange1c_synchronize_new_product_by') == 'sku') {
 				$this->log("[i] Товар новый, ищем по артикулу: '" . $data['sku'] . "'", 2);
 				if (empty($data['sku'])) {
					$this->log("[ERROR] При синхронизации по артикулу, артикул не должен быть пустым! Проверьте товар " . $data['name']);
					$this->log("<== setProduct() return: false", 2);
 					return false;
 				} else {
 					$data['product_id'] = $this->getProductBySKU($data['sku']);
 				}
 			} elseif ($this->config->get('exchange1c_synchronize_new_product_by') == 'name' && !empty($data['name'])) {
 				$this->log("[i] Товар новый, ищем по наименованию" . $data['name'], 2);
 				$data['product_id'] = $this->getProductByName($data['name']);
 			} elseif ($this->config->get('exchange1c_synchronize_new_product_by') == 'ean') {
 				$this->log("[i] Товар новый, ищем по штрихкоду" . $data['ean'], 2);
 				if (empty($data['ean'])) {
					$this->log("[ERROR] При синхронизации по штрихкоду, штрихкод не должен быть пустым! Проверьте товар " . $data['name']);
					$this->log("<== setProduct() return: false", 2);
 					return false;
 				} else {
 					$data['product_id'] = $this->getProductByEan($data['name']);
 				}
 			}

			// Если нашли, запишем связь
 			if ($data['product_id'])
				$this->insertProductLinkToCML($data['product_id'], $data['product_cml_id']);

 		}
 		// Можно добавить поиск по наименованию или другим полям...

 		// Если не найден товар...
 		if (!$data['product_id']) {
 			$data['product_id'] = $this->addProduct($data);
 		} else {
 			$this->updateProduct($data);
 		}
 		//$this->log($data,2);
 		$this->log("<== setProduct()", 2);

 		return true;
 	} // setProduct()


	/**
	 * Загружает реквизиты товара в массив
	 */
	private function parseRequisite($xml, $data) {
		$this->log("==> parseRequisite()",2);

		$this->log("> Всего реквизитов: " . sizeof($xml->ЗначениеРеквизита));

		foreach ($xml->ЗначениеРеквизита as $requisite){
			$name 	= (string)$requisite->Наименование;
			$value 	= $requisite->Значение;

			switch ($name){
				case 'Вес':
					$data['weight'] = $value ? (float)str_replace(',','.',$value) : 0;
					$this->log("> Реквизит: " . $name. " => weight",2);
				break;
				case 'ТипНоменклатуры':
					$data['item_type'] = $value ? (string)$value : '';
					$this->log("> Реквизит: " . $name. " => item_type",2);
				break;
				case 'ВидНоменклатуры':
					$data['item_view'] = $value ? (string)$value : '';
					$this->log("> Реквизит: " . $name. " => item_view",2);
				break;
				case 'ОписаниеВФорматеHTML':
					if ($value) {
						$data['description'] =  (string)$value;
						$this->log("> Реквизит: " . $name. " => description (HTML format)",2);
					}
				break;
				case 'Полное наименование':
					$data['full_name'] = $value ? htmlspecialchars((string)$value) : '';
					$this->log("> Реквизит: " . $name. " => full_name",2);
				break;
				default:
					$this->log("[!] Неиспользуемый реквизит: " . $name. " = " . (string)$value,2);
			}
		}
		return $data;
	} // parseRequisite()


	/**
	 * Получает путь к картинке и накладывает водяные знаки
	 */
	private function applyWatermark($filename, $wm_filename) {
		$this->log("==> applyWatermark()",2);

		$wm_fullname = DIR_IMAGE . $wm_filename;
		$fullname = DIR_IMAGE . $filename;

		if (is_file($wm_fullname) && is_file($fullname)) {

			// Получим расширение файла
			$info = pathinfo($filename);
			$extension = $info['extension'];

			// Создаем объект картинка из водяного знака и получаем информацию о картинке
			$image = new Image($fullname);
			if (version_compare($this->config->get('exchange1c_CMS_version'), '2.0.3.1', '>')) {
				$image->watermark(new Image($wm_fullname));
			} else  {
				$image->watermark($wm_fullname);
			}

			// Формируем название для файла с наложенным водяным знаком
			$new_image = utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '_wm.' . $extension;

			// Сохраняем картинку с водяным знаком
			$image->save(DIR_IMAGE . $new_image);

			$this->log("> Файл с водяным знаком " . $new_image);
			$this->log("[i] Удален старый файл: " . $filename,2);
			return $new_image;
		}
		else {
			return $filename;
		}
	} // applyWatermark()


	/**
	 * Определяет что за файл и принимает дальнейшее действие
	 */
	private function setFile($filename, $product_id) {
		$this->log("==> setFile()",2);
		$info = pathinfo($filename);
		if (isset($info['extension'])) {

			// Если расширение txt - грузим в описание
			if ($info['extension'] == "txt") {
				$description = file_get_contents($filename);
				// если не в кодировке UTF-8, переводим
				if (!mb_check_encoding($description, 'UTF-8')) {
					$description = nl2br(htmlspecialchars(iconv('windows-1251', 'utf-8', $description)));
				}
				// обновляем только описание
				$this->updateProductDescription(array('description'	=> $description), $product_id);
				$this->log("> Добавлено описание товара из файла",1);
				$this->log("> Файл описания  " . $filename, 2);
				return 1;
			}
		}
		return 0;
	} // setFile())


	/**
	 * Добавляет картинки в товар
	 */
	private function parseImages($xml, $product_id) {
		$this->log("==> parseImages(), product_id = " . $product_id, 2);

		if (!$product_id) {
			$this->log("[ERROR] Обновление картинок невозможно, так как product_id = 0");
			return false;
		}

		$watermark = $this->config->get('exchange1c_watermark');
		$index = 0;

		// Нужно ли обновлять картинки товара
		if (!$this->config->get('exchange1c_import_images') == 1) {
			$this->log("[i] Обновление картинок отключено!");
			return true;
		}

		// Прочитаем все старые картинки
		$old_images = array();
		$sql = "SELECT `product_image_id`,`image` FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = " . $product_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		foreach ($query->rows as $image) {
			$old_images[$image['product_image_id']] = $image['image'];
		}
		$sql = "SELECT `image` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $product_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows)
			$old_images[0] = $query->row['image'];

		$this->log("old images: ", 2);
		//$this->log($old_images, 2);

		foreach ($xml as $image) {

			$image = (string)$image;
			$this->log("Картинка: " . $image, 2);

			if (empty($image)) {
				continue;
			}

			$full_image = DIR_IMAGE . $image;

			if (file_exists($full_image)) {
				// Является ли файл картинкой, прочитаем свойства картинки
				$image_info = getimagesize($full_image);
				if ($image_info == NULL) {
					if (!$this->setFile($full_image, $product_id)) {
						$this->log("Файл '" . $image . "' не является картинкой");
					}
					continue;
				}
				// если надо наложить водяные знаки, проверим накладывали уже их ранее, т.е. имеется ли такой файл
				if (!empty($watermark)) {
					// Файл с водяными знаками имеет название /path/image_wm.ext
					$path_parts = pathinfo($image);
					$new_image = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_wm." . $path_parts['extension'];
					if (!file_exists(DIR_IMAGE . $new_image)) {
						// Если нет файла, накладываем водяные знаки
						$new_image = $this->applyWatermark($image, $watermark);
						$this->log("[i] Создана картинка с водяными знаками: " . $new_image, 2);
					}
//					// Удаляем оригинал
//					$this->log("[i] Удаляем оригинальный файл: " . $image, 2);
//					unlink($full_image);

				} else {
					// Не надо накладывать водяные знаки
					$new_image = $image;
				}


			} else {
				// если картинки нет подставляем эту
				$new_image = 'no_image.png';
			}

			// основная картинка
			if ($index == 0) {
				if ($old_images[0] <> $new_image) {
					// Надо обновить
					$sql = "UPDATE `" . DB_PREFIX . "product` SET `image` = '" . $this->db->escape($new_image) . "' WHERE `product_id` = " . $product_id;
					$this->log($sql, 2);
					$this->db->query($sql);
					$this->log("> Картинка основная: '" . $new_image . "'", 2);
				}
				// Удалять картинку не нужно
				$product_image_id = array_search($new_image, $old_images);
				if ($product_image_id !== false) {
					$this->log("Найден product_image_id = " . $product_image_id, 2);
					unset($old_images[$product_image_id]);
				}
			} else {
				// Установим картинку в товар, т.е. если нет - добавим, если есть возвратим product_image_id
				$product_image_id = array_search($new_image, $old_images);
				if ($product_image_id !== false) {
					$this->log("Найден product_image_id = " . $product_image_id, 2);
					unset($old_images[$product_image_id]);
				} else {
					// Нет картинки такой
					$sql = "INSERT INTO `" . DB_PREFIX . "product_image` SET `product_id` = " . $product_id . ", `image` = '" . $this->db->escape($new_image) . "', `sort_order` = " . $index;
					$this->log($sql, 2);
					$this->db->query($sql);
					$this->log("> Картинка дополнительная: '" . $new_image . "'", 2);
				}
			}

			$index ++;
		}

		// Удалим старые неиспользованные картинки
		foreach ($old_images as $product_image_id => $image) {
			$sql = "DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_image_id` = " . $product_image_id;
			$this->log($sql,2);
			$this->db->query($sql);

			if (is_file(DIR_IMAGE . $image)) {
				// Также удалим файл с диска
				unlink(DIR_IMAGE . $image);
				$this->log("[i] Удален файл: " . DIR_IMAGE . $image, 2);
			}
		}

		$this->log("> Картинок: " . $index);
		$this->log("<== parseImages(), return: true", 2);
		return true;
	} // parseImages()


	/**
	 * Возвращает id группы для свойств
	 */
	private function setAttributeGroup($name) {
		$this->log("==> setAttributeGroup()",2);
		$sql = "SELECT `attribute_group_id` FROM `" . DB_PREFIX . "attribute_group_description` WHERE `name` = '" . $this->db->escape($name) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->rows) {
			return $query->row['attribute_group_id'];
		}

		// Добавляем группу
		$sql = "INSERT INTO `" . DB_PREFIX . "attribute_group` SET `sort_order` = 1";
		$this->log($sql,2);
		$this->db->query($sql);

		$attribute_group_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "attribute_group_description` SET `attribute_group_id` = " . $attribute_group_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'";
		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== setAttributeGroup()",2);
		return $attribute_group_id;
	} // setAttributeGroup()


	/**
	 * Возвращает id атрибута из базы
	 */
	private function setAttribute($cml_id, $attribute_group_id, $name, $sort_order) {
		$this->log("==> setAttribute()",2);

		// Ищем свойства по 1С Ид
		$attribute_id = 0;
		$sql = "SELECT `attribute_id` FROM `" . DB_PREFIX . "attribute_to_1c` WHERE `1c_id` = '" . $this->db->escape($cml_id) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$attribute_id = $query->row['attribute_id'];
		}

		if (!$attribute_id) {
			// Попытаемся найти по наименованию
			$sql = "SELECT `a`.`attribute_id` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID . " AND `ad`.`name` LIKE '" . $this->db->escape($name) . "' AND `a`.`attribute_group_id` = " . $attribute_group_id;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$attribute_id = $query->row['attribute_id'];
			}
		}

		// Обновление
		if ($attribute_id) {
			$sql = "SELECT `a`.`attribute_group_id`,`ad`.`name` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID . " AND `a`.`attribute_id` = " . $attribute_id;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				// Изменилась группа свойства
				if ($query->row['attribute_group_id'] <> $attribute_group_id) {
					$sql = "UPDATE `" . DB_PREFIX . "attribute` SET `attribute_group_id` = " . (int)$attribute_group_id . " WHERE `attribute_id` = " . $attribute_id;
					$this->log($sql,2);
					$this->db->query($sql);
				}
				// Изменилось имя
				if ($query->row['name'] <> $name) {
					$sql = "UPDATE `" . DB_PREFIX . "attribute_description` SET `name` = '" . $this->db->escape($name) . "' WHERE `attribute_id` = " . $attribute_id . " AND `language_id` = " . $this->LANG_ID;
					$this->log($sql,2);
					$this->db->query($sql);
				}
			}

			$this->log("<== setAttribute(), return attribute_id: " . $attribute_id, 2);
			return $attribute_id;
		}

		// Добавим в базу характеристику
		$sql = "INSERT INTO `" . DB_PREFIX . "attribute` SET `attribute_group_id` = " . $attribute_group_id . ", `sort_order` = " . $sort_order;
		$this->log($sql,2);
		$this->db->query($sql);

		$attribute_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "attribute_description` SET `attribute_id` = " . $attribute_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'";
		$this->log($sql,2);
		$this->db->query($sql);

		// Добавляем ссылку для 1С Ид
		$sql = "INSERT INTO `" .  DB_PREFIX . "attribute_to_1c` SET `attribute_id` = " . $attribute_id . ", `1c_id` = '" . $this->db->escape($cml_id) . "'";
		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== setAttribute(), return attribute_id: " . $attribute_id, 2);
		return $attribute_id;
	} // setAttribute()


	/**
	 * Загружает значения атрибута (Свойства из 1С)
	 */
	private function parseAttributesValues($xml) {
		$this->log("==> parseAttributesValues()", 2);
		$data = array();
		if (!$xml) {
			$this->log("<== parseAttributesValues()", 2);
			return $data;
		}

		if (isset($xml->ВариантыЗначений)) {
			if (isset($xml->ВариантыЗначений->Справочник)) {
				foreach ($xml->ВариантыЗначений->Справочник as $item) {
					$value = trim(htmlspecialchars((string)$item->Значение, 2));
					$data[(string)$item->ИдЗначения] = $value;
					$this->log("> Значение: " . $value);
				}
			}
		}

		$this->log("<== parseAttributesValues()", 2);
		return $data;
	} // parseAttributesValues()


	/**
	 * Загружает атрибуты (Свойства из 1С) в классификаторе
	 */
	private function parseAttributes($xml) {
		$this->log("==> parseAttributes()", 2);
		//$this->log($xml, 2);
		$data = array();
		$sort_order = 0;
		if ($xml->Свойство) {
			$properties = $xml->Свойство;
		} else {
			$properties = $xml->СвойствоНоменклатуры;
		}
		foreach ($properties as $property) {
			$cml_id		= (string)$property->Ид;
			$name 		= trim((string)$property->Наименование);

			// Название группы свойств по умолчанию (в дальнейшем сделать определение в настройках)
			$group_name = "Свойства";

			// Определим название группы в название свойства в круглых скобках в конце названия
			$this->log("[i] Определение названия группы свойства: " . $name, 2);
			preg_match('/(.*) \((.*)\)/', $name, $match);
			if (isset($match[2])) {
//				$this->log($match, 2);
				$name = $match[1];
				$group_name = $match[2];
			}
			// Установим группу для свойств
			$attribute_group_id = $this->setAttributeGroup($group_name);

			if ($property->ДляПредложений) {
				// Свойства для характеристик скорее всего
				if ((string)$property->ДляПредложений == 'true') {
					$this->log("> Свойство '" . $name . "' для предложений, в атрибуты не будет добавлено", 2);
					continue;
				}
			}

			switch ($name) {
				case 'Производитель':
					$values = $this->parseAttributesValues($property);
					foreach ($values as $manufacturer_cml_id => $value) {
						$this->setManufacturer($value, $manufacturer_cml_id);
					}
				//break;
				case 'Изготовитель':
					$values = $this->parseAttributesValues($property);
					foreach ($values as $manufacturer_cml_id => $value) {
						$this->setManufacturer($value, $manufacturer_cml_id);
					}
				//break;
				default:
					$data[$cml_id] = array(
						'name'			=> $name,
						'attribute_id'	=> $this->setAttribute($cml_id, $attribute_group_id, $name, $sort_order)
					);
					$values = $this->parseAttributesValues($property);
					if ($values) {
						$data[$cml_id]['values'] = $values;
					}
					$sort_order ++;
					$this->log("> Свойство: '" . $name . "'", 2);
			}

		}
		$this->log("> Свойств загружено: " . sizeof($properties));

		$this->log("<== parseAttributes()", 2);
		return $data;
	} // parseAttributes()


	/**
	 * Читает свойства товара  записывает их в массив
	 */
	private function parseProductAttributes(&$data, $xml, $attributes) {
		$this->log("==> parseProductAttributes()",2);

		$product_attributes = array();

		foreach ($xml->ЗначенияСвойства as $property) {

			// Ид объекта в 1С
			$cml_id = (string)$property->Ид;

			// Загружаем только те что в классификаторе
			if (!isset($attributes[$cml_id])) {
				$this->log("[i] Свойство не было загружено в классификаторе, Ид: " . $cml_id,2);
				continue;
			}

			$name 	= trim($attributes[$cml_id]['name']);
			$value 	= trim((string)$property->Значение);

			if ($value) {
				if ($attributes[$cml_id]) {
					// агрегатный тип
					if (isset($attributes[$cml_id]['values'])) {
						$value = trim($attributes[$cml_id]['values'][$value]);
					}
				}
			}

			// Пропускаем с пустыми значениями
			if (empty($value))
				continue;

			switch ($name) {
				case 'Производитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if (!isset($data['manufacturer_id'])) {
						$data['manufacturer_id'] = $this->setManufacturer($value);
						$this->log("> Производитель (из свойства): '" . $value . "', id: " . $data['manufacturer_id']);
					}
				break;
				case 'Изготовитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if (!isset($data['manufacturer_id'])) {
						$data['manufacturer_id'] = $this->setManufacturer($value);
						$this->log("> Производитель (из свойства): '" . $value . "', id: " . $data['manufacturer_id']);
					}
				break;
				case 'Вес':
					$data['weight'] = round((float)str_replace(',','.',$value), 3);
					$this->log("> Свойство Вес => weight = ".$data['weight']);
				break;
				case 'Ширина':
					$data['width'] = round((float)str_replace(',','.',$value), 2);
					$this->log("> Свойство Ширина => width");
				break;
				case 'Высота':
					$data['height'] = round((float)str_replace(',','.',$value), 2);
					$this->log("> Свойство Высота => height");
				break;
				case 'Длина':
					$data['length'] = round((float)str_replace(',','.',$value), 2);
					$this->log("> Свойство Длина => length",1);
				break;
				case 'Модель':
					$data['model'] = (string)$value;
					$this->log("> Свойство Модель => model",1);
				break;
				case 'Артикул':
					$data['sku'] = (string)$value;
					$this->log("> Свойство Артикул => sku",1);
				break;
				default:
					$product_attributes[$attributes[$cml_id]['attribute_id']] = array(
						'name'			=> $name,
						'value'			=> $value,
						'cml_id'		=> $cml_id,
						'attribute_id'	=> $attributes[$cml_id]['attribute_id']
					);
					$this->log("> Свойство: '" . $name . "' = '" . $value . "'",2);
			}
		}
		$data['product_attributes'] = $product_attributes;

		$this->log("<== parseProductAttributes()",2);

	} // parseProductAttributes()


	/**
	 * Устанавливает свойства в товар из массива
	 */
	private function updateProductAttributeValue($product_id, $attribute_id, $value) {
		$this->log("==> updateProductAttributeValue()", 2);
		$sql = "UPDATE `" . DB_PREFIX . "product_attribute` SET `text` = '" . $this->db->escape($value) . "' WHERE `product_id` = " . $product_id . " AND `attribute_id` = " . $attribute_id . " AND `language_id` = " . $this->LANG_ID;
		$this->log($sql,2);
		$this->db->query($sql);
		$this->log("<== updateProductAttributeValue()", 2);
	} // updateProductAttributeValue()

	/**
	 * Устанавливает свойства в товар из массива
	 */
	private function setProductAttributes($data) {
		$this->log("==> setProductAttributes()", 2);
		//$this->log($data,2);

		// Проверяем
		$product_attributes = array();
		$sql = "SELECT `attribute_id`,`text` FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		//$this->log('query:',2);
		//$this->log($query,2);
		foreach ($query->rows as $attribute) {
			$product_attributes[$attribute['attribute_id']] = $attribute['text'];
		}
		//$this->log('product_attributes:', 2);
		//$this->log($product_attributes, 2);

		//$this->log('data[product_attributes]:',2);
		//$this->log($data['product_attributes'], 2);

		foreach ($data['product_attributes'] as $property) {
			// Проверим есть ли такой атрибут
			$this->log("[i] Поиск значения: '" . $property['value'] . "'",2);

			if (isset($product_attributes[$property['attribute_id']])) {

				// Проверим значение
				if ($product_attributes[$property['attribute_id']] != $property['value'])
					$this->updateProductAttributeValue($data['product_id'], $property['attribute_id'], $property['value']);

				unset($product_attributes[$property['attribute_id']]);
			} else {
				// Добавим в товар
				$sql = "INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = " . $data['product_id'] . ", `attribute_id` = " . $property['attribute_id'] . ", `language_id` = " . $this->LANG_ID . ", `text` = '" .  $this->db->escape($property['value']) . "'";
				$this->db->query($sql);
				$this->log($sql,2);
				$this->log("> Свойство '" . $this->db->escape($property['name']) . "' = '" . $this->db->escape($property['value']) . "' записано в товар id: " . $data['product_id'],2);
			}
		}

		// Удалим неиспользованные
		foreach ($product_attributes as $attribute_id => $attribute) {
			$sql = "DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID . " AND `attribute_id` = " . $attribute_id;
			$this->log($sql,2);
			$this->db->query($sql);
		}
		$this->log("<== setProductAttributes()", 2);
	} // setProductAttributes()


	/**
	 * Обновляем производителя в базе данных
	 */
	private function updateManufacturer($data) {
		$this->log("==> updateManufacturer()",2);
//		$this->log($data,2);

		$sql = "SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $data['manufacturer_id'];
		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->row['name'] <> $data['name']) {
			// Обновляем
			$sql  = " `name` = '" . $this->db->escape($data['name']) . "'";
			$sql .= isset($data['noindex']) ? ", `noindex` = " . $data['noindex'] : "";
			$sql = "UPDATE `" . DB_PREFIX . "manufacturer` SET " . $sql . " WHERE `manufacturer_id` = " . $data['manufacturer_id'];
			$this->log($sql,2);
			$this->db->query($sql);
		}

		if ($this->existTable('manufacturer_description')) {

	        $this->seoGenerateManufacturer($data);

			$sql = "SELECT `name`,`description`,`meta_title`,`meta_description`,`meta_keyword` FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . $data['manufacturer_id'] . " AND `language_id` = " . $this->LANG_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);

			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$fields = $this->compareArrays($query, $data);

			if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "manufacturer_description` SET " . $fields . " WHERE `manufacturer_id` = " . $data['manufacturer_id'] . " AND `language_id` = " . $this->LANG_ID;
				$this->log($sql,2);
				$this->db->query($sql);
				$this->log("> Обновлено описание производителя '" . $data['name'] . "'",2);
			}

		}

		$this->log("<== updateManufacturer()",2);
		return true;
	} // updateManufacturer()


	/**
	 * Добавляем производителя
	 */
	private function addManufacturer(&$manufacturer_data) {
		$this->log("==> addManufacturer()",2);

		$sql 	 = " `name` = '" . $this->db->escape($manufacturer_data['name']) . "'";
		$sql 	.= isset($manufacturer_data['sort_order']) 			? ", `sort_order` = " . $manufacturer_data['sort_order']					: "";
		$sql 	.= isset($manufacturer_data['image']) 				? ", `image` = '" . $this->db->escape($manufacturer_data['image']) . "'" 	: ", `image` = ''";
		$sql 	.= isset($manufacturer_data['noindex']) 			? ", `noindex` = " . $manufacturer_data['noindex'] 							: "";
		$sql = "INSERT INTO `" . DB_PREFIX . "manufacturer` SET" . $sql;
		$this->log($sql,2);
		$query = $this->db->query($sql);

		$manufacturer_data['manufacturer_id'] = $this->db->getLastId();

        $this->seoGenerateManufacturer($manufacturer_data);

		if ($this->existTable('manufacturer_description')) {
			$sql = $this->prepareStrQueryManufacturerDescription($manufacturer_data);
			if ($sql) {
				$sql = "INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET `manufacturer_id` = " . $manufacturer_data['manufacturer_id'] . ", `language_id` = " . $this->LANG_ID . $sql;
				$this->log($sql,2);
				$this->db->query($sql);
			}
		}

		if (isset($manufacturer_data['cml_id'])) {
			// добавляем связь
			$sql 	= "INSERT INTO `" . DB_PREFIX . "manufacturer_to_1c` SET `1c_id` = '" . $this->db->escape($manufacturer_data['cml_id']) . "', `manufacturer_id` = " . $manufacturer_data['manufacturer_id'];
			$this->log($sql,2);
			$this->db->query($sql);
		}

		$sql 	= "INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET `manufacturer_id` = " . $manufacturer_data['manufacturer_id'] . ", `store_id` = " . $this->STORE_ID;
		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("> Производитель '" . $manufacturer_data['name'] . "' добавлен, id: " . $manufacturer_data['manufacturer_id']);

		$this->log("<== addManufacturer()",2);
	} // addManufacturer()


	/**
	 * Устанавливаем производителя
	 */
	private function setManufacturer($name, $cml_id='') {
		$this->log("==> setManufacturer(name = ".$name.", cml_id = ".$cml_id.")",2);

		$manufacturer_data = array();
		$manufacturer_data['name']			= (string)$name;
		$manufacturer_data['description'] 	= 'Производитель ' . $manufacturer_data['name'];
		$manufacturer_data['sort_order']	= 1;
		$manufacturer_data['cml_id']		= (string)$cml_id;

		if ($this->existField("manufacturer", "noindex")) {
			$manufacturer_data['noindex'] = 1;	// значение по умолчанию
		}

		if ($cml_id) {
			// Поиск (производителя) изготовителя по 1C Ид
			$sql 	= "SELECT mc.manufacturer_id FROM `" . DB_PREFIX . "manufacturer_to_1c` mc LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (mc.manufacturer_id = ms.manufacturer_id) WHERE mc.1c_id = '" . $this->db->escape($manufacturer_data['cml_id']) . "' AND ms.store_id = " . $this->STORE_ID;
		} else {
			// Поиск по имени
			$sql 	= "SELECT m.manufacturer_id FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (m.manufacturer_id = ms.manufacturer_id) WHERE m.name LIKE '" . $this->db->escape($manufacturer_data['name']) . "' AND ms.store_id = " . $this->STORE_ID;
		}

		// Если есть таблица manufacturer_description тогда нужно условие
		// AND language_id = '" . $this->LANG_ID . "'

		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$manufacturer_data['manufacturer_id'] = $query->row['manufacturer_id'];
			$this->log("Найден manufacturer_id: " . $manufacturer_data['manufacturer_id'], 2);
		}

		if (!isset($manufacturer_data['manufacturer_id'])) {
			// Создаем
			$this->addManufacturer($manufacturer_data);
		} else {
			// Обновляем
			$this->updateManufacturer($manufacturer_data);
		}

		$this->log("> Производитель: '" . $manufacturer_data['name'] . "'",1);

		$this->log("<== setManufacturer()",2);
		return $manufacturer_data['manufacturer_id'];

	} // setManufacturer()


	/**
	 * Обрабатывает единицу измерения товара
	 */
	private function parseProductUnit($xml) {
		$this->log("==> parseProductUnit(xml)",2);
		$data = array();

		if (!$xml) {
			$this->log("<== parseUnit(xml пустой)",2);
			return $data;
		}
//		$this->log($xml, 2);

		if (isset($xml->Пересчет)) {
			foreach ($xml->Пересчет as $recalculation) {
				$data['Recalculation'] = array(
					'code'		=> (string)$recalculation->Единица,
					'ratio'		=> (float)$recalculation->Коэффициент
				);
			}
		} else {
			$data['name'] = (string)$xml;
		}

		// Если единица не назначена, устанавливается по умолчанию штука
		$data['code'] = isset($xml['Код']) ? (string)$xml['Код'] : "796";
		$data['unit_id'] = $this->getUnitId($data['code']);

		if (isset($xml['НаименованиеПолное'])) {
			$data['full_name'] = htmlspecialchars((string)$xml['НаименованиеПолное']);
		}
		if (isset($xml['МеждународноеСокращение'])) {
			$data['code_eng'] = (string)$xml['МеждународноеСокращение'];
		}

		if (!isset($data['name'])) {
			// Если имя не задаоно в xml получим из таблицы
			$sql 	= "SELECT `rus_name1` FROM `" . DB_PREFIX . "unit` WHERE `number_code` = '" . $data['code'] . "'";
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$data['name'] = $query->row['rus_name1'];
			}
		}

		$this->log("<== parseUnit(), return array:", 2);
		//$this->log($data, 2);
		return $data;
	} // parseProductUnit()


	/**
	 * Обрабатывает единицы измерения в классификаторе XML 2.09
	 */
	private function parseUnits($xml) {
		$this->log("==> parseUnits(xml) XML 2.09",2);
		$old_inits = array();

		// Прочитаем старые соответствия единиц измерения
		$sql = "SELECT * FROM `" . DB_PREFIX . "unit_to_1c`";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$old_inits[$query->row['unit_id']] = $query->row['cml_id'];
		}

		foreach ($xml->ЕдиницаИзмерения as $unit) {
			// Сопоставляет Ид с id единицей измерения CMS
			$cml_id		= (string)$unit->Ид;
			$delete		= isset($unit->ПометкаУдаления) ? (string)$unit->ПометкаУдаления : "false";
			$name		= (string)$unit->НаименованиеКраткое;
			$code		= (string)$unit->Код;
			$fullname	= (string)$unit->НаименованиеПолное;
			$code_eng	= (string)$unit->МеждународноеСокращение;

			$key = array_search($cml_id, $old_inits);
			if (false !== $key) {
				unset($old_inits[$key]);
			} else {
				$unit_id = $this->getUnitId($code);

				$sql = "INSERT INTO `" . DB_PREFIX . "unit_to_1c` SET `cml_id` = '" . $this->db->escape($cml_id) . "', `unit_id` = " . $unit_id;
				$this->log($sql,2);
				$this->db->query($sql);
			}
		}

		// удаляем неиспользуемые
		foreach ($old_inits as $key => $old_init) {
			$sql = "DELETE FROM `" . DB_PREFIX . "unit_to_1c` WHERE `product_unit_id` = " . (int)$key;
			$this->log($sql,2);
			$this->db->query($sql);
		}
		$this->log("<== parseUnits()",2);
	}


	/**
	 * Обрабатывает товары из import.xml или import?_?.xml
	 */
	private function parseProducts($xml, $classifier) {
		$this->log("==> parseProducts()",2);

		if (!$xml->Товар) {
			$this->log("<== parseProducts() Нет товаров", 2);
			return true;
		}

		// В некоторых CMS имеется поле для синхронизаци например с Yandex
		if ($this->existField("product", "noindex")) {
			$noindex = 1;
		}

		// По умолчанию статус при отсутствии на складах
		$default_stock_status = $this->config->get('exchange1c_default_stock_status');

		foreach ($xml->Товар as $product){
			if ($product->Ид && $product->Наименование) {
				$data = array();

				$cml_id = explode("#", (string)$product->Ид);
				$data['product_cml_id'] = $cml_id[0];
				$data['feature_cml_id'] = isset($cml_id[1]) ? $cml_id[1] : '';

				$data['mpn']				= $data['product_cml_id'];
				$data['name']				= htmlspecialchars((string)$product->Наименование);
				if ($product->Код) {
					$data['code']			= htmlspecialchars((string)$product->Код);
				}

				if ($product->Артикул) {
					$data['sku']			= htmlspecialchars((string)$product->Артикул);
					$data['model']			= $data['sku'];
				} else {
					$data['model']			= $data['product_cml_id'];
				}

				if ($product->Штрихкод) {
					$data['ean'] 			= (string)$product->Штрихкод;
				}

				// Значения по-умолчанию
				$data['length_class_id']	= $this->config->get('config_length_class_id');
				$data['weight_class_id']	= $this->config->get('config_weight_class_id');

				$data['status']				= 1;

				if ($this->existField('product','noindex')) {
					$data['noindex']		= 1; // В некоторых версиях
				}

				$this->log("------------------------------",2);
				$this->log("Товар '" . $data['name'] . "'",1);

				if ($product->БазоваяЕдиница) {
					$this->log("==> Базовая единица",2);
					$data['unit'] = $this->parseProductUnit($product->БазоваяЕдиница);
				} else {
					$this->log("==> Базовая единица не определена, назначена как штука",2);
					$data['unit'] = $this->parseProductUnit("шт");
				}

				if ($product->ПолноеНаименование) {
					$this->log("==> Полное наименование",2);
					$data['full_name']		= htmlspecialchars((string)$product->ПолноеНаименование);
				}

				// описание в текстовом формате, нужна опция если описание в формате HTML
				if ($product->Описание)	{
					$this->log("==> Описание товара",2);
					$description = (string)$product->Описание;
					$data['description']	= $this->config->get('exchange1c_description_html') == 1 ? $description : nl2br(htmlspecialchars($description));
				}

				// Реквизиты (разные версии CML)
				if ($product->ЗначениеРеквизита) {
					$data = $this->parseRequisite($product, $data);
				}

				// Реквизиты (разные версии CML)
				if ($product->ЗначенияРеквизитов) {
					$data = $this->parseRequisite($product->ЗначенияРеквизитов, $data);
				}

				// Тип номенклатуры читается из реквизитов
				// Если фильтр по типу номенклатуры заполнен, то загружаем указанные там типы
				$exchange1c_parse_only_types_item = $this->config->get('exchange1c_parse_only_types_item');
				if (isset($data['item_type']) && (!empty($exchange1c_parse_only_types_item))) {
					if (mb_stripos($exchange1c_parse_only_types_item, $data['item_type']) === false) {
					 	continue;
					}
				}

				// Категории
				$data['product_categories']	= array();
				if ($product->Группы) {
					$this->log("==> Категории товара",2);
					foreach ($product->Группы->Ид as $category_cml_id) {
						$category_id = $this->getCategoryIdBycml_id((string)$category_cml_id);
						if ($category_id)
							$data['product_categories'][] = $category_id;
					}
				}

				// Читаем изготовителя, добавляем/обновляем его в базу
				if ($product->Изготовитель) {
					$this->log("[i] Загрузка производителя из тега Изготовитель",2);
					$data['manufacturer_id'] = $this->setManufacturer($product->Изготовитель->Наименование, $product->Изготовитель->Ид);
				}

				// Статус по-умолчанию при отсутствии товара на складе
				// Можно реализовать загрузку из свойств
				if ($default_stock_status) {
					$data['stock_status_id'] = $default_stock_status;
				}

				// Свойства
				if ($product->ЗначенияСвойств && isset($classifier['attributes'])) {
					$this->parseProductAttributes($data, $product->ЗначенияСвойств, $classifier['attributes']);
				}

				// Добавляем или обновляем товар в базе
				if (!$this->setProduct($data)) {
					$this->log("<== parseProducts(), setProduct() выполнен с ошибкой", 2);
					return false;
				}

				// Удаляем старые опции и характеристики
				//$this->deleteProductFeatures($product_id);

				// Записываем атрибуты в товар
				if (isset($data['product_attributes'])) {
					$this->setProductAttributes($data);
	                unset($data['product_attributes']);
				}

				// Заполнение родительских категорий в товаре
				if ($this->config->get('exchange1c_fill_parent_cats') == 1)
					$this->fillParentsCategories($data);

				// картинки
				if ($product->Картинка) {
					if (!$this->parseImages($product->Картинка, $data['product_id'])) return false;
				}
				$this->log('ParseProductsEnd, data[]:', 2);
				//$this->log($data, 2);

			} // if (isset($product->Ид) && isset($product->Наименование) )

		}
		$this->log("<== parseProducts()", 2);
		return true;
	} // parseProducts()


	/**
	 * Разбор каталога
	 */
	private function parseDirectory($xml, $classifier) {

		$this->log("==> parseDirectory() --------------------- КАТАЛОГ ТОВАРОВ ---------------------", 2);

		$directory					= array();
		$directory['cml_id']		= (string)$xml->Ид;
		$directory['name']			= (string)$xml->Наименование;
		$directory['classifier_id']	= (string)$xml->ИдКлассификатора;
		if (isset($classifier['id'])) {
			if ($directory['classifier_id'] <> $classifier['id']) {
				$this->log->write("[ERROR] Каталог не соответствует классификатору");
				return 0;
			}
		}

		// Если полная выгрузка - требуется очистка для текущего магазина: товаров, остатков и пр.
		if ((string)$xml['СодержитТолькоИзменения'] == 'false')  {
			$this->log("[i] Полная выгрузка с 1С",1);
		}

		// Загрузка товаров
		if (!$this->parseProducts($xml->Товары, $classifier)) {
			unset($xml->Товары);
			return 0;
		}

		$this->log("<== parseDirectory(), return 1", 2);
		return 1;
	} // parseDirectory()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ ПРЕДЛОЖЕНИЙ ******************************
	 */

	/**
	 * Добавляет склад в базу данных
	 */
	private function addWarehouse($cml_id, $name) {

		$this->log("==> addWarehouse()", 2);
		$this->db->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET `name` = '" . $this->db->escape($name) . "', `1c_id` = '" . $this->db->escape($cml_id) . "'");
		$warehouse_id = $this->db->getLastId();

		$this->log("<== addWarehouse(), warehouse_id = " . $warehouse_id, 2);
		return $warehouse_id;

	} // addWarehouse()


	/**
	 * Ищет склад по cml_id
	 */
	private function getWarehouseBycml_id($cml_id) {

		$this->log("==> getWarehouseBycml_id(cml_id=".$cml_id.")", 2);
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'warehouse` WHERE `1c_id` = "' . $this->db->escape($cml_id) . '"');

		if ($query->num_rows) {
			$this->log("<== getWarehouseBycml_id(), warehouse_id = " . $query->row['warehouse_id'], 2);
			return $query->row['warehouse_id'];
		}

		$this->log("<== getWarehouseBycml_id(), warehouse_id = 0", 2);
		return 0;

	} // getWarehouseBycml_id()


	/**
	 * Возвращает id склада
	 */
	private function setWarehouse($cml_id, $name) {

		$this->log("==> setWarehouse(cml_id=".$cml_id.",name=".$name.")",2);
		// Поищем склад по 1С Ид
		$warehouse_id = $this->getWarehouseBycml_id($cml_id);

		if (!$warehouse_id) {
			$warehouse_id = $this->addWarehouse($cml_id, $name);
		}

		$this->log("<== setWarehouse(), warehouse_id = " . $warehouse_id,2);
		return $warehouse_id;

	} // setWarehouse()


	/**
	 * Получает общий остаток товара
	 */
	private function getQuantity($product_id) {

		$this->log("==> getQuantity(product_id=".$product_id.")", 2);
		$sql = "SELECT `quantity` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $product_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);

		if ($query->num_rows) {
			$this->log("<== getQuantity(), quantity = " . $query->row['quantity'], 2);
			return $query->row['quantity'];
		}

		$this->log("<== getQuantity(), quantity = 0", 2);
		return 0;

	} // getQuantity()


	/**
	 * Устанавливает общий остаток товара
	 */
	private function setQuantity($product_id, $quantity) {

		$this->log("==> setQuantity(product_id=".$product_id.", quantity=".$quantity.")", 2);
		$quantity_old = $this->getQuantity($product_id);

		if ($quantity <> $quantity_old) {
			$sql = "UPDATE `" . DB_PREFIX . "product` SET `quantity` = " . (float)$quantity . " WHERE `product_id` = " . $product_id;
			$this->log($sql,2);
			$this->db->query($sql);
		}

		$this->log("<== setQuantity()", 2);

	} // setQuantity()


	/**
	 * Получает все остатки товара по складам и характеристикам
	 */
	private function getProductQuantities($product_id, $product_feature_id = 0) {

		$this->log("==> getProductQuantity(product_id=".$product_id.")", 2);
		$data_quantity = array();
		$where = $product_feature_id ? " AND `product_feature_id` = " . $product_feature_id : "";
		$sql = "SELECT `product_quantity_id`,`product_feature_id`,`warehouse_id`,`quantity` FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . $product_id . $where;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		//$this->log($query, 2);
		foreach ($query->rows as $row) {
			$data_quantity[$row['product_quantity_id']] = array(
				'product_feature_id'	=> $row['product_feature_id'],
				'warehouse_id'			=> $row['warehouse_id'],
				'quantity'				=> $row['quantity']
			);
		}
		$this->log("<== getProductQuantity(), size: " . count($data_quantity), 2);
		//$this->log($data_quantity, 2);
		return $data_quantity;

	} // getProductQuantity()


	/**
	 * Получает остаток товара по фильтру
	 */
	private function getProductQuantityNew($product_quantity_filter) {
		$this->log("==> getProductQuantityNew()", 2);

		$where = "";
		foreach ($product_quantity_filter as $field => $value) {
			$where .= ($where ? " AND" : "") . " `" . $field . "` = " . $value;
		}

		$sql = "SELECT `product_quantity_id`,`quantity` FROM `" . DB_PREFIX . "product_quantity` WHERE " . $where;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		//$this->log($query, 2);
		if ($query->num_rows) {
			$data_quantity = array(
				'product_quantity_id'	=> $query->row['product_quantity_id'],
				'quantity'				=> $query->row['quantity']
			);
			//$this->log($data_quantity, 2);
			$this->log("<== getProductQuantityNew(), return:", 2);
			//$this->log($data_quantity, 2);
			return $data_quantity;
		} else {
			$this->log("<== getProductQuantityNew(), return: false", 2);
			return false;
		}

	} // getProductQuantity()


	/**
	 * Обновляет остаток товара
	 */
	private function updateProductQuantity($product_quantity_id, $quantity) {

		$this->log("==> updateProductQuantity(product_quantity_id=".$product_quantity_id.", quantity=".$quantity.")", 2);

		$sql = "UPDATE `" . DB_PREFIX . "product_quantity` SET `quantity` = '" . (float)$quantity . "' WHERE `product_quantity_id` = " . $product_quantity_id;
		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== updateProductQuantity()", 2);

	} // updateProductQuantity()


	/**
	 * Добавляет остаток товара
	 */
	private function addProductQuantity($product_id, $quantity, $unit_id = 0, $warehouse_id = 0, $product_feature_id = 0) {

		$this->log("==> addProductQuantity(product_id=".$product_id.", quantity=".$quantity.", unit_id=".$unit_id.", warehouse_id=".$warehouse_id.", product_feature_id=".$product_feature_id.")", 2);

		$sql = "INSERT INTO `" . DB_PREFIX . "product_quantity` SET `quantity` = '" . (float)$quantity . "', `product_id` = " . $product_id . ", `unit_id` = " . $unit_id . ", `warehouse_id` = " . $warehouse_id . ", `product_feature_id` = " . $product_feature_id;
		$this->log($sql,2);
		$this->db->query($sql);

		$product_quantity_id = $this->db->getLastId();
		$this->log("<== addProductQuantity(), return: " . $product_quantity_id, 2);
		return $product_quantity_id;

	} // addProductQuantity()


	/**
	 * Добавляет остаток товара по фильтру
	 */
	private function addProductQuantityNew($product_quantity_filter, $quantity) {
		$this->log("==> addProductQuantityNew()", 2);

		$set = "";
		foreach ($product_quantity_filter as $field => $value) {
			$set .= ", `" . $field . "` = " . $value;
		}

		$sql = "INSERT INTO `" . DB_PREFIX . "product_quantity` SET `quantity` = '" . (float)$quantity . "'" . $set;
		$this->log($sql,2);
		$this->db->query($sql);

		$product_quantity_id = $this->db->getLastId();
		$this->log("<== addProductQuantity(), product_quantity_id = " . $product_quantity_id, 2);
		return $product_quantity_id;

	} // addProductQuantityNew()


	/**
	 * Удаляет остаток товара
	 */
	private function deleteProductQuantity($product_quantity_id) {

		$this->log("==> deleteProductQuantity()", 2);

		$sql = "DELETE FROM `" . DB_PREFIX . "product_quantity` WHERE `product_quantity_id` = " . $product_quantity_id;
		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== deleteProductQuantity()", 2);

	} // deleteProductQuantity()


	/**
	 * Сравнивает остаток
	 */
	private function compareProductQuantity($quantities, $quantity, $unit_id = 0, $warehouse_id = 0, $product_feature_id = 0) {
		$this->log("==> compareProductQuantity()", 2);
		$result = array(
			'product_quantity_id' 	=> 0,
			'update'				=> 0
		);
		foreach ($quantities as $product_quantity_id => $quantity_data) {
			if ($quantity_data['unit_id'] == $unit_id && $quantity_data['warehouse_id'] == $warehouse_id && $quantity_data['product_feature_id'] == $product_feature_id) {
				// Если остаток отличается, изменяем
				$result['product_quantity_id'] = $product_quantity_id;
				if ($quantity_data['quantity'] <> $quantity) {
					$result['update'] = 1;
				}
			}
		}
		$this->log("<== compareProductQuantity(), update: " . $result['update'] . ", product_quantity_id: " . $result['product_quantity_id'], 2);
		return $result;
	} // compareProductQuantity()


	/**
	 * Сравнивает массивы и формирует список измененных полей для запроса
	 */
	private function compareArraysNew($data1, $data2) {
		$this->log("==> compareArraysNew()", 2);

		$upd_fields = array();
		if ($query->num_rows) {
			foreach($data1 as $key => $row) {
				if (!isset($data2[$key])) continue;
				if ($row <> $data2[$key]) {
					$upd_fields[] = $key . " = '" . $this->db->escape($data2[$key]) . "'";
					$this->log("[i] Отличается поле '" . $key . "'", 2);
				} else {
					$this->log("[i] Поле '" . $key . "' не имеет отличий", 2);
				}
			}
		}
		$this->log("<== compareArraysNew()", 2);
		return implode(', ', $upd_fields);
	} // compareArraysNew()


	/**
	 * Ищет совпадение данных в массиве данных, при совпадении значений, возвращает ключ второго массива
	 */
	private function findMatch($data1, $data_array) {

		$bestMatch = 0;
		foreach ($data_array as $key2 => $data2) {
			$matches = 0;
			$fields = 0;
			foreach ($data1 as $key1 => $value) {
				if (isset($data2[$key1])) {
					$fields++;
					if ($data2[$key1] == $value) {
						$matches++;
					}

				}
			}
			// у всех найденых полей совпали значения
			if ($matches == $fields){
				return $key2;
			}
		}
		return false;
	} // findMatch()


	/**
	 * Устанавливает остаток товара (новая)
	 */
	private function setProductQuantityNew($product_quantity_filter, $quantity) {
		$this->log("==> setProductQuantityNew()",2);

		$product_quantity = $this->getProductQuantityNew($product_quantity_filter);

		if ($product_quantity == false) {
			$product_quantity_id = $this->addProductQuantityNew($product_quantity_filter, $quantity);
			$this->log("<== setProductQuantityNew(), product_quantity_id = " . $product_quantity_id, 2);
			return $product_quantity_id;
		} else {
			if ($product_quantity['quantity'] != $quantity) {
				$this->updateProductQuantity($product_quantity['product_quantity_id'], $quantity);
				$this->log("<== setProductQuantityNew(), product_quantity_id = " . $product_quantity['product_quantity_id'], 2);
			}
			return $product_quantity['product_quantity_id'];
		}
	}


	/**
	 * Устанавливает остаток товара
	 */
	private function setProductQuantity($data) {

		$this->log("==> setProductQuantity()",2);

		if (!isset($data['quantity'])) {
			$this->log("<== setProductQuantity(), no data[quantity]", 2);
			return;
		}

		// Читаем все остатки товара
		$quantities = $this->getProductQuantities($data['product_id']);

		// Единица измерения для товара по умолчанию, если единица не была указана в характеристике
		if (empty($data['unit'])) {
			$data['unit'] = $this->parseProductUnit("шт");
		}
		$unit_id = $data['unit']['unit_id'];

		// Если есть характеристики, записываем остатки по ним
		if (isset($data['features'])) {

			foreach ($data['features'] as $feature) {
				$this->log("[i] Характеристика: " . $feature['name'], 2);
				// Единица измерения
				if (isset($feature['unit']['unit_id']))
					$unit_id = $feature['unit']['unit_id'];

				// Остатки по складам
				if (isset($feature['product_quantity'])) {
					$this->log("[i] Есть остатки по складам", 2);
					foreach ($feature['product_quantity'] as $warehouse_id => $quantity) {
						// обрабатываем
						$product_quantity = $this->compareProductQuantity($quantities, $quantity, $unit_id, $warehouse_id, $feature['product_feature_id']);
						if ($product_quantity['product_quantity_id']) {
							// обновляем запись
							unset($quantities[$product_quantity['product_quantity_id']]);
							if ($product_quantity['update']) {
								$this->updateProductQuantity($product_quantity['product_quantity_id'], $quantity);
							}
						} else {
							// добавляем запись
							$product_quantity['product_quantity_id'] = $this->addProductQuantity($data['product_id'], $quantity, $unit_id, $warehouse_id, $feature['product_feature_id']);
						}
					}
				} else {
					$this->log("[i] Нет остатков по складам", 2);
					// нет складов
					$product_quantity = $this->compareProductQuantity($quantities, $feature['quantity'], $unit_id, 0, $feature['product_feature_id']);
					if ($product_quantity['product_quantity_id']) {
						// обновляем запись
						unset($quantities[$product_quantity['product_quantity_id']]);
						if ($product_quantity['update']) {
							$this->updateProductQuantity($product_quantity['product_quantity_id'], $feature['quantity']);
						}
					} else {
						// добавляем запись
						$product_quantity['product_quantity_id'] = $this->addProductQuantity($data['product_id'], $feature['quantity'], $unit_id, 0, $feature['product_feature_id']);
					}
				}

			}

		} else {
			// Товар без характеристик

			// Остатки по складам
			if (isset($data['product_quantity'])) {
				$this->log("[i] Остатки по складам, без характеристик", 2);
				foreach ($data['product_quantity'] as $warehouse_id => $quantity) {
					// обрабатываем
					$product_quantity = $this->compareProductQuantity($quantities, $quantity, $unit_id, $warehouse_id);
					if ($product_quantity['product_quantity_id']) {
						// обновляем запись
						unset($quantities[$product_quantity['product_quantity_id']]);
						if ($product_quantity['update']) {
							$this->updateProductQuantity($product_quantity['product_quantity_id'], $quantity);
						}
					} else {
						// добавляем запись
						$product_quantity['product_quantity_id'] = $this->addProductQuantity($data['product_id'], $quantity, $unit_id, $warehouse_id);
					}
				}
			} else {
				$this->log("[i] Остатки без складов, без характеристик", 2);
				$product_quantity = $this->compareProductQuantity($quantities, $data['quantity'], $unit_id);
				if ($product_quantity['product_quantity_id']) {
					// обновляем запись
					unset($quantities[$product_quantity['product_quantity_id']]);
					if ($product_quantity['update']) {
						$this->updateProductQuantity($product_quantity['product_quantity_id'], $data['quantity']);
					}
				} else {
					// добавляем запись
					$product_quantity['product_quantity_id'] = $this->addProductQuantity($data['product_id'], $data['quantity'], $unit_id);
				}
			}

		}

		// Общий остаток который заносится в товар
		if (isset($data['quantity'])) {
			// обрабатываем
			$this->setQuantity($data['product_id'], $data['quantity']);
		}

		//$this->log("[i] Остатки которые нужно удалить", 2);
		//$this->log($quantities, 2);
        // Удаляем лишние
		foreach ($quantities as $product_quantity_id => $quantity_data) {
        	$this->deleteProductQuantity($product_quantity_id);
       	}

		$this->log("<== setProductQuantity()", 2);

	} // setProductQuantity()


	/**
	 * Удаляет склад и все остатки поо нему
	 */
	private function deleteStockWarehouse($warehouse_id) {
		$this->log("==> deleteStockWarehouse()",2);
		$sql = "DELETE FROM `" . DB_PREFIX . "product_quantity ` WHERE `warehouse_id` = " . (int)$warehouse_id;
		$this->log($sql,2);
		$this->db->query($sql);
		$this->log("<== deleteStockWarehouse()",2);
	}


	/**
	 * Удаляет склад и все остатки поо нему
	 */
	private function deleteWarehouse($id_cml) {
		$this->log("==> deleteWarehouse()",2);
		$warehouse_id = $this->getWarehouseBycml_id($id_cml);
		if ($warehouse_id) {
			// Удаляем все остатки по этму складу
			$this->deleteStockWarehouse($warehouse_id);

			// Удаляем склад
			$sql = "DELETE FROM `" . DB_PREFIX . "warehouse ` WHERE `1c_id` = '" . $this->db->escape($id_cml) . "'";
			$this->log($sql,2);
			$this->db->query($sql);

		}
		$this->log("<== deleteWarehouse()",2);
	}


	/**
	 * Загружает список складов
	 */
	private function parseWarehouses($xml) {
		$this->log("==> parseWarehouses()",2);
		$data = array();
		foreach ($xml->Склад as $warehouse){
			if (isset($warehouse->Ид) && isset($warehouse->Наименование) ){
				$id_cml = (string)$warehouse->Ид;
				$name = trim((string)$warehouse->Наименование);
				$delete = isset($warehouse->ПометкаУдаления) ? $warehouse->ПометкаУдаления : "false";
				if ($delete == "false") {

					$data[$id_cml] = array(
						'name' => $name
					);
					$data[$id_cml]['warehouse_id'] = $this->setWarehouse($id_cml, $name);
				} else {
					// Удалить склад
					$this->log("[i] Склад помечен на удаление - удаление склада и остатков в CMS");
					$this->deleteWarehouse($id_cml);
				}
			}
		}
		$this->log("<== parseWarehouses()",2);
		return $data;
	} // parseWarehouses()


	/**
	 * Загружает остатки по складам
	 * Возвращает остатки по складам
	 * где индекс - это warehouse_id, а значение - это quantity (остаток)
	 */
	private function parseQuantity($xml, $offers_pack, $data) {
		$this->log("==> parseQuantity()",2);

		$data_quantity = array();

		if (!$xml) {
			$this->log("[i] Нет данных в XML", 2);
			return $data_quantity;
		}

		// есть секция с остатками, обрабатываем
		foreach ($xml->Остаток as $quantity) {
			//$this->log("<ПакетПредложений><Предложения><Предложение><Остатки><Остаток>",2);

			if ($quantity->Склад->Ид) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><Остатки><Остаток><Склад><Ид>",2);
				$warehouse_cml_id = (string)$quantity->Склад->Ид;
				$warehouse_id = $this->getWarehouseBycml_id($warehouse_cml_id);
				if (!$warehouse_id) {
					$this->log("<== parseRests() false, warehouse_id = 0 when warehouse_cml = " . $warehouse_cml_id, 2);
					return false;
				}
			} else {
				$warehouse_id = 0;
			}

			if ($quantity->Склад->Количество) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><Остатки><Остаток><Склад><Количество>",2);
				$quantity = (float)$quantity->Склад->Количество;
			}
			$data_quantity[$warehouse_id] = $quantity;
		}

		$this->log("<== parseQuantity()", 2);
		return $data_quantity;

	} // parseQuantity()


	/**
	 * Возвращает массив данных валюты по id
	 */
	private function getCurrency($currency_id) {
		$this->log("==> getCurrency()",2);
		$sql = "SELECT * FROM `" . DB_PREFIX . "currency` WHERE `currency_id` = " . $currency_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			return $query->row;
		}
		return array();
	} // getCurrency()


	/**
	 * Возвращает id валюты по коду
	 */
	private function getCurrencyId($code) {
		$this->log("==> getCurrencyId()", 2);
		$sql = "SELECT `currency_id` FROM `" . DB_PREFIX . "currency` WHERE `code` = '" . $this->db->escape($code) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$this->log("<== getCurrencyId() currency_id = " . $query->row['currency_id'], 2);
			return $query->row['currency_id'];
		}

		// Попробуем поискать по символу справа
		$sql = "SELECT `currency_id` FROM `" . DB_PREFIX . "currency` WHERE `symbol_right` = '" . $this->db->escape($code) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$this->log("<== getCurrencyId() currency_id = " . $query->row['currency_id'], 2);
			return $query->row['currency_id'];
		}

		$this->log("<== getCurrencyId() currency_id = 0", 2);
		return 0;
	} // getCurrencyId()


	/**
	 * Сохраняет настройки сразу в базу данных
	 */
	private function configSet($key, $value, $store_id=0) {
		if (!$this->config->has('exchange1c_'.$key)) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `value` = '" . $value . "', `store_id` = " . $store_id . ", `code` = 'exchange1c', `key` = '" . $key . "'");
		}
	} // configSet()


	/**
	 * Получает список групп покупателей
	 */
	private function getCustomerGroups() {
		$sql = "SELECT `customer_group_id` FROM `" . DB_PREFIX. "customer_group` ORDER BY `sort_order`";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		$data = array();
		foreach ($query->rows as $row) {
			$data[] = $row['customer_group_id'];
		}
		return $data;
	} // getCustomerGroups()


	/**
	 * Загружает типы цен автоматически в таблицу которых там нет
	 */
	private function autoLoadPriceType($xml) {
		$this->log("==> autoLoadPriceType()", 2);
		$config_price_type = $this->config->get('exchange1c_price_type');

		if (empty($config_price_type)) {
			$config_price_type = array();
		}

		$update = false;

		// список групп покупателей
		$customer_groups = $this->getCustomerGroups();

		$index = 0;
		foreach ($xml->ТипЦены as $price_type)  {
			$name = trim((string)$price_type->Наименование);
			$delete = isset($price_type->ПометкаУдаления) ? $price_type->ПометкаУдаления : "false";
			$id_cml = (string)$price_type->Ид;
			$priority = 0;
			$found = -1;
			foreach ($config_price_type as $key => $cpt) {
				//$this->log("в настройках: ", 2);
				//$this->log($cpt, 2);
				//$this->log("в файле: ", 2);
				//$this->log($name, 2);
				if (!empty($cpt['id_cml']) && $cpt['id_cml'] == $id_cml) {
					$this->log("[i] Найдена цена по Ид", 2);
					$found = $key;
					break;
				}
				if (strtolower(trim($cpt['keyword'])) == strtolower($name)) {
					$this->log("[i] Найдена цена по наименованию", 2);
					$found = $key;
					break;
				}
				$priority = max($priority, $cpt['priority']);
			}

			$this->log("Ключ: ".$found, 2);
			// Не найден в настройках, добавляем в настройки
			if ($found >= 0) {

				// Если тип цены помечен на удаление, удалим ее из настроек
				if ($delete == "true") {
					$this->log("[!] Тип цены помечен на удаление", 2);
					unset($config_price_type[$found]);
					$update = true;
				} else {
					// Обновим Ид
					if ($config_price_type[$found]['id_cml'] != $id_cml) {
						$config_price_type[$found]['id_cml'] = $id_cml;
						$update = true;
					}
				}

			} else {
				// Добавим цену в настройку если он ане помечена на удаление
				$this->log("[!!!] Не найдена цена", 2);
				$this->log($index, 2);
				$customer_group_id = isset($customer_groups[$index]) ? $customer_groups[$index] : $this->config->get('config_customer_group_id');
				if ($delete == "false") {
					$config_price_type[] = array(
						'keyword' 				=> $name,
						'id_cml' 				=> $id_cml,
						'customer_group_id' 	=> $customer_group_id,
						'quantity' 				=> 1,
						'priority' 				=> $priority,
					);
					$update = true;
				}
			} // if
			$index++;
		} // foreach

        if ($update) {
			if ($this->config->get('exchange1c_price_type')) {
				$sql = "UPDATE `". DB_PREFIX . "setting` SET `value` = '" . $this->db->escape(json_encode($config_price_type)) . "', `serialized` = 1 WHERE `key` = 'exchange1c_price_type'";
	        } else {
				$sql = "INSERT `". DB_PREFIX . "setting` SET `value` = '" . $this->db->escape(json_encode($config_price_type)) . "', `serialized` = 1, `code` = 'exchange1c', `key` = 'exchange1c_price_type'";
	        }
	        $this->log($sql, 2);
	        $this->db->query($sql);
        }
		$this->log("<== autoLoadPriceType()", 2);
		return $config_price_type;
	} // autoLoadPriceType()


	/**
	 * Загружает типы цен и сразу определяет к каким группам сопоставлены они
	 * Если не сопоставлен ни один тип цен, то цены не будут загружаться
	 */
	private function parsePriceType($xml) {
		$this->log("==> parsePriceType()", 2);

		// Автозагрузка цен
		if ($this->config->get('exchange1c_price_types_auto_load')) {
			$config_price_type = $this->autoLoadPriceType($xml);
		} else {
			$config_price_type = $this->config->get('exchange1c_price_type');
		}

		$data = array();

		// Перебираем все цены из CML
		foreach ($xml->ТипЦены as $price_type)  {
			$currency		= isset($price_type->Валюта) ? (string)$price_type->Валюта : "RUB";
			$id_cml			= (string)$price_type->Ид;
		 	$name			= trim((string)$price_type->Наименование);
		 	$code			= $price_type->Код ? $price_type->Код : ($price_type->Валюта ? $price_type->Валюта : '');

			// Найденный индекс цены в настройках
			$found = -1;

			// Перебираем все цены из настроек модуля
			foreach ($config_price_type as $index => $config_type) {

				if ($found >= 0)
					break;

				if (!empty($config_type['id_cml']) && $config_type['id_cml'] == $id_cml) {
					$found = $index;
					break;
				} elseif (strtolower($name) == strtolower($config_type['keyword'])) {
					$found = $index;
					break;
				}

			} // foreach ($config_price_type as $config_type)

			if ($found >= 0) {
				$this->log("[i] Цена '" . $name . "' найдена в настройках модуля, Ид = '" . $id_cml . "'", 2);
				if ($code) {
					$currency_id					= $this->getCurrencyId($code);
				} else {
					$currency_id					= $this->getCurrencyId($currency);
				}
				$data[$id_cml] 					= $config_type;
				$data[$id_cml]['currency'] 		= $currency;
				$data[$id_cml]['currency_id'] 	= $currency_id;
				if ($currency_id) {
					$currency_data = $this->getCurrency($currency_id);
					$rate = $currency_data['value'];
					$decimal_place = $currency_data['decimal_place'];
				} else {
					$rate = 1;
					$decimal_place = 2;
				}
				$data[$id_cml]['rate'] 			= $rate;
				$data[$id_cml]['decimal_place'] = $decimal_place;
				$this->log('Вид цены: ' . $name,1);
			}


		} // foreach ($xml->ТипЦены as $price_type)

		unset($xml);
		//$this->log($data, 2);
		$this->log("<== parsePriceType()", 2);
		return $data;
	} // parsePriceType()


	/**
	 * Устанавливает цены на один товар
	 */
	private function setProductDiscount($price_data, $product_id) {
		$this->log("==> setProductDiscount()", 2);
		//$this->log("price_data: ", 2);
		//$this->log($price_data, 2);

		// Характеристика, у нее могут быть несколько цен
		$sql = "SELECT `product_discount_id`, `quantity`, `priority`, `price`, `customer_group_id` FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $price_data['customer_group_id'];
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {

			$product_discount_id = $query->row['product_discount_id'];

			// Определим что обновлять
			$fields = $this->compareArrays($query, $price_data);

			if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "product_discount` SET " . $fields . " WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $price_data['customer_group_id'];
		 		$this->log($sql,2);
				$this->db->query($sql);
			}

		} else {
			// Добавляем
			$sql = "INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = " . $product_id . ", `customer_group_id` = " . $price_data['customer_group_id'] . ", `quantity` = '" . (float)$price_data['quantity'] . "', `price` = '" . (float)$price_data['price'] . "', `priority` = " . $price_data['priority'];
			$this->log($sql,2);
			$query = $this->db->query($sql);

			$product_discount_id = $this->db->getLastId();
		}

		$this->log("<== setProductDiscount(), return product_discount_id: " . $product_discount_id, 2);
		return $product_discount_id;
	} // setProductDiscount()


	/**
	 * Удаляет цену товара
	 */
	private function deleteProductPrice($product_price_id) {
		$this->log("==> deleteProductPrice(), product_price_id = " . $product_price_id, 2);

		$sql = "DELETE FROM `" . DB_PREFIX . "product_price` WHERE `product_price_id` = " . $product_price_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);

		$this->log("<== deleteProductPrice()", 2);
	} // deleteProductPrice()


	/**
	 * Удаляет дополнительные цены (скидки) )товара
	 */
	private function deleteProductDiscount($product_discount_id) {
		$this->log("==> deleteProductDiscount(), product_discount_id = " . $product_discount_id, 2);

		$sql = "DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_discount_id` = " . $product_discount_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);

		$this->log("<== deleteProductDiscount()", 2);
	} // deleteProductDiscount()


	/**
	 * Устанавливает ценs товара без характеристик
	 */
	private function setPrice($product_id, $data_prices) {
		$this->log("==> setPrice()", 2);
		foreach ($data_prices as $price) {
			if ($price['default']) {
				$sql = "UPDATE `" . DB_PREFIX . "product` SET `price` = " . (float)$price['price'] . " WHERE `product_id` = " . $product_id;
				$this->log($sql,2);
				$this->db->query($sql);
			} else{
				$this->setDiscountPrice($price, $product_id);
			}
		}
		$this->log("<== setPrice()", 2);
	}


	/**
	 * Устанавливает цену товара для разных групп покупателей
	 */
	private function setDiscountPrice($price_data, $product_id) {
		$this->log("==> setDiscountPrice()", 2);

		$sql = "SELECT `product_discount_id`,`price` FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $price_data['customer_group_id'];
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$product_discount_id = $query->row['product_discount_id'];
		}

		if (empty($product_discount_id)) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = " . $product_id . ", `quantity` = " . $price_data['quantity'] . ", `priority` = " . $price_data['priority'] . ", `customer_group_id` = " . $price_data['customer_group_id'] . ", `price` = '" . (float)$price_data['price'] . "'";
			$this->log($sql,2);
			$query = $this->db->query($sql);

			$product_discount_id = $this->db->getLastId();

		} else {
			$fields = $this->compareArrays($query, $price_data);
//			$this->log($fields,2);

			// Если есть расхождения, производим обновление
			if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "product_discount` SET " . $fields . " WHERE `product_discount_id` = " . $product_discount_id;
				$this->log($sql,2);
				$this->db->query($sql);
			}
		}
		$this->log("<== setDiscountPrice(), return product_discount_id = " . $product_discount_id, 2);
		return $product_discount_id;
	} // setDiscountPrice()


	/**
	 * Устанавливает цену товара базовой единицы товара
	 */
	private function setProductPrice($price_data, $product_id, $product_feature_id = 0) {
		$this->log("==> setProductPrice()", 2);

		$sql = "SELECT `product_price_id`,`price` FROM `" . DB_PREFIX . "product_price` WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $price_data['customer_group_id'] . " AND `product_feature_id` = " . $product_feature_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$product_price_id = $query->row['product_price_id'];
		}

		if (empty($product_price_id)) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product_price` SET `product_id` = " . $product_id . ", `product_feature_id` = " . $product_feature_id . ", `customer_group_id` = " . $price_data['customer_group_id'] . ", `price` = '" . (float)$price_data['price'] . "'";
			$this->log($sql,2);
			$query = $this->db->query($sql);

			$product_price_id = $this->db->getLastId();

		} else {
			$fields = $this->compareArrays($query, $price_data);
//			$this->log($fields,2);

			// Если есть расхождения, производим обновление
			if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "product_price` SET " . $fields . " WHERE `product_id` = " . $product_id . " AND `product_feature_id` = " . $product_feature_id . " AND `customer_group_id` = " . $price_data['customer_group_id'];
				$this->log($sql,2);
				$this->db->query($sql);
			}
		}
		$this->log("<== setProductPrice(), return product_price_id = " . $product_price_id, 2);
		return $product_price_id;
	} // setProductPrice()


	/**
	 * Получает по коду его id
	 */
	private function getUnitId($number_code) {
		$this->log("==> getUnitId()", 2);
		$sql = "SELECT `unit_id` FROM `" . DB_PREFIX . "unit` WHERE `number_code` = '" . $this->db->escape($number_code) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$this->log("<== getUnitId(), return unit_id =  " . $query->row['unit_id'], 2);
			return $query->row['unit_id'];
		}
		$sql = "SELECT `unit_id` FROM `" . DB_PREFIX . "unit` WHERE `rus_name1` = '" . $this->db->escape($number_code) . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$this->log("<== getUnitId(), return unit_id = " . $query->row['unit_id'], 2);
			return $query->row['unit_id'];
		}

		$this->log("<== getUnitId(), return unit_id = 0", 2);
		return 0;
	} // getUnitId()


	/**
	 * Загружает все цены только в одной валюте
	 */
	private function parsePrice($xml, $offers_pack, $data) {

		$this->log("==> parsePrice()", 2);
		//$this->log("data[]:", 2);
		//$this->log($data, 2);
		//$this->log($offers_pack,2);
		//$this->log($xml,2);
		$result = array();

		if (!$xml) {
			$this->log("[i] Нет цен в предложении");
			return $result;
		}

		foreach ($xml->Цена as $price) {
			$price_cml_id	= (string)$price->ИдТипаЦены;
			$data_price = array();

			foreach ($offers_pack['price_types'] as $config_price_type) {
				if ($config_price_type['id_cml'] == $price_cml_id) {
					// найдена цена
					$data_price = $config_price_type;
					if ($price->ЦенаЗаЕдиницу) {
						//$this->log("<ПакетПредложений><Предложения><Предложение><Цены><Цена><ЦенаЗаЕдиницу>",2);
						$data_price['price']		= (float)$price->ЦенаЗаЕдиницу;
					}

					// автоматическая конвертация в основную валюту CMS
					if ($this->config->get('exchange1c_currency_convert') == 1) {
						if (isset($data_price['rate'])) {
							if ($data_price['rate'] <> 1 && $data_price['rate'] > 0) {
								$data_price['price'] = round((float)$price->ЦенаЗаЕдиницу / (float)$data_price['rate'], $data_price['decimal_place']);
							}
						}
					}

					// Если включено пропускать нулевые цены и новая цена будет нулевой, то старая цена не будет изменена
					if ($this->config->get('exchange1c_ignore_price_zero') && $data_price['price'] == 0) {
						continue;
					}

					// если это не базовая единица
					if ($price->Коэффициент) {
						//$this->log("<ПакетПредложений><Предложения><Предложение><Цены><Цена><Коэффициент>",2);
						$data_price['quantity']		= (float)$price->Коэффициент;
					}

					if ($price->Единица) {
						//$this->log("<ПакетПредложений><Предложения><Предложение><Цены><Цена><Единица>",2);
			 			$data_price['unit_name']	= (string)$price->Единица;
						$data_price['unit_id']		= $this->getUnitId($data_price['unit_name']);
						if (!empty($data_price['unit_id'])) {
							// Значит в наименовании единицы измерения был прописан не наименование а международный код
							if (array_search($data_price['unit_name'], $data['unit'])) {
								$data_price['unit_id'] = $data['unit']['unit_id'];
							}
						}
			 		}
			 		if ($price->Представление) {
			 			//$this->log("<ПакетПредложений><Предложения><Предложение><Цены><Цена><Представление>",2);
					 	$data_price['name']			= (string)$price->Представление;
			 		}
			 		//$data_price['currency']		= (string)$price->Валюта;

		 			// истина если цена для группы по-умолчанию
					$data_price['default'] 	= $data_price['customer_group_id'] == $this->config->get('config_customer_group_id') ? true : false;

					$this->log("> Цена '" . $data_price['name'] . "'");

			 		$result[$price_cml_id] = $data_price;
					break;
				}
			}

			if (!$data_price) {
				$this->log('[i] Не найдена цена, Ид: ' . $price_cml_id,1);
			}
 		}

		//$this->log("[i] data_price: ",2);
		//$this->log($result,2);
		$this->log("<== parsePrice()", 2);
		return $result;

 	} // parsePrices()


	/**
	 * ХАРАКТЕРИСТИКИ
	 */


	/**
	 * Добавляет опциию по названию
	 */
	private function addOption($name, $type='select') {
		$this->log("==> addOption()", 2);
		$sql = "INSERT INTO `" . DB_PREFIX . "option` SET `type` = '" . $this->db->escape($type) . "'";
 		$this->log($sql,2);
		$this->db->query($sql);

		$option_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "option_description` SET `option_id` = '" . $option_id . "', `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'";
 		$this->log($sql,2);
		$this->db->query($sql);

		$this->log("<== addOption(), return option_id = " . $option_id, 2);
		return $option_id;
	} // addOption()


	/**
	 * Находит или добавляет значение опции в товар
	 */
	private function getProductPrice($product_id) {
		$this->log("==> getProductPrice()", 2);
		$sql = "SELECT price FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $product_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

        if ($query->num_rows) {
        	$this->log("<== getProductPrice(), return price = " . $query->row['price'], 2);
        	return $query->row['price'];
       	}
       	$this->log("<== getProductPrice(), return price = 0", 2);
       	return 0;
	} // getProductPrice()


	/**
	 * Получение наименования производителя по manufacturer_id
	 */
	private function getManufacturerName($manufacturer_id) {

		$this->log("==> getManufacturerName()", 2);
		if (!$manufacturer_id) {
			$this->log("[ERROR] Не указан manufacturer_id");
			return "";
		}

		$query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $manufacturer_id);
		$name = isset($query->row['name']) ? $query->row['name'] : "";

		$this->log("<== getManufacturerName(), return name = " . $name, 2);
		return $name;
	} // getManufacturerName()


	/**
	 * Получение product_id по Ид
	 */
	private function getProductIdByCML($product_cml_id) {

		$this->log("==> getProductIdByCML(), product_cml_id = " . $product_cml_id, 2);
		// Определим product_id
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product_to_1c` WHERE `1c_id` = '" . $this->db->escape($product_cml_id) . "'");
		$product_id = isset($query->row['product_id']) ? $query->row['product_id'] : 0;

		// Проверим существование такого товара
		if ($product_id) {
			$query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$product_id);
			if (!$query->num_rows) {

				// Удалим неправильную связь
				$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);

				$product_id = 0;
			}
		}

		$this->log("<== getProductIdByCML(), product_id = " . $product_id, 2);
		return $product_id;

	} // getProductIdByCML()


	/**
	 * Получение полей товара name,sku,brand,desc,cats,cat_id
	 */
	private function getProduct($product_id, &$data) {

		$this->log("==> getProduct()",2);
		if (!$product_id) {
			$this->log("[ERROR] Не указан product_id");
			return false;
		}

		$data['product_id'] = $product_id;

		$sql = "SELECT `sku`,`ean`,`manufacturer_id`, `image` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $product_id;
		$this->log($sql, 2);
		$query = $this->db->query($sql);

		if ($query->num_rows) {
			// Получим sku если он не задан
			$data['sku'] = $query->row['sku'];
			$data['ean'] = $query->row['ean'];

			// Получим наименование производителя, если manufacturer_id указан
			if ($query->row['manufacturer_id']) {
				$data['manufacturer_id']	= $query->row['manufacturer_id'];
				$data['manufacturer']		= $this->getManufacturerName($data['manufacturer_id']);
			}
		}

		// нужно было для SEO
		//$data['categories'] = $this->getProductCategories($product_id);

		// Описание товара
		// тоже нужно только для SEO
		//$sql = "SELECT `name`,`description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . $product_id . " AND `language_id` = " . $this->LANG_ID;
		//$this->log($sql, 2);
		//$query_desc = $this->db->query($sql);
		//if ($query_desc->num_rows) {
		//	$data['description'] 	= $query_desc->row['description'];
		//	$data['name'] 			= $query_desc->row['name'];
		//}

		// id категории товара
		$data['categories'] = $this->getProductCategories($product_id);
		$this->log("<== getProduct()", 2);
		return true;

	} // getProduct()


	/**
	 * Разбивает название по шаблону "[order].[name] [option]"
	 */
	private function splitNameStr($str, $opt_yes = true) {
		$this->log("==> splitName() string = " . $str, 2);

		$str = trim(str_replace(array("\r","\n"),'',$str));
		$length = mb_strlen($str);
		//$this->log('length: '.$length,2);
		$data = array(
			'order' 	=> 0,
			'name' 		=> "",
			'option' 	=> ""
		);

        $pos_name_start = 0;
		$pos_opt_end = 0;
		$pos_opt_start = $length;

		if ($opt_yes) {
			// Поищем опцию
			$level = 0;
			for ($i = $length; $i > 0; $i--) {
				$char = mb_substr($str,$i,1);
				if ($char == ")") {
					$level++;
					if (!$pos_opt_end)
						$pos_opt_end = $i;
				}
				if ($char == "(") {
					$level--;
					if ($level == 0) {
						$pos_opt_start = $i+1;
						$data['option'] = mb_substr($str, $pos_opt_start, $pos_opt_end-$pos_opt_start);
						$pos_opt_start -= 2;
						//$this->log('pos_opt_start = ' . $pos_opt_start . ', pos_opt_end = ' . $pos_opt_end, 2);
						break;
					}
				}
			}
		}

		// Поищем порядок сортировки, order (обязательно после цифры должна идти точка а после нее пробел!)
		$pos_order_end = 0;
		for ($i = 0; $i < $length; $i++) {
			if (is_numeric(mb_substr($str,$i,1))) {
				//$this->log('order: число '.mb_substr($str,$i,1), 2);
				$pos_order_end++;
				if ($i+1 <= $length && mb_substr($str, $i+1, 1) == ".") {
					$data['order'] = (int)mb_substr($str, 0, $pos_order_end);
					$pos_name_start = $i+2;
				}
			} else {
				// Если первая не цифра, дальше не ищем
				break;
			}
		}

		// Наименование
		$data['name'] = trim(mb_substr($str, $pos_name_start, $pos_opt_start-$pos_name_start));
		//$this->log($data, 2);
		$this->log("<== splitName()", 2);
		return $data;
	} // splitNameStr()


	/**
	 * Разбор предложения
	 * $offer, $product_id, $feature_cml_id, $data, $offers_pack
	 */
	private function parseFeature($xml, $product_id, $feature_cml_id, &$data, $offers_pack) {
		$this->log("==> parseFeature()", 2);
		if (!$xml) {
			$this->log("[ERROR] Пустые данные в XML");
			$this->log($xml,2);
			return false;
		}
		if (!$feature_cml_id) {
			$this->log("[ERROR] Нет характеристики");
			return false;
		}

//		$this->log("data:", 2);
//		$this->log($data, 2);

		// Разбиваем название
		$matches = $this->splitNameStr(htmlspecialchars(trim((string)$xml->Наименование)));
		//$this->log($matches, 2);
		$feature_order = $matches['order'];
		$product_name = $matches['name'];
		$feature_name = $matches['option'];

		if (empty($product_name) || empty($feature_name)) {
			$this->log("[ERROR] Имя товара или характеристики не может быть пустым");
			return false;
		}

//		$quantities = $this->parseQuantity($xml, $offers_pack, $data);
		$feature = array(
			'order'					=> $feature_order,
			'name'					=> $feature_name
		);


		// Опции характеристики
		$feature_options = array();
		if ($xml->ХарактеристикиТовара) {
			if ($this->config->get('exchange1c_product_options_mode') == 'feature') {
				$option_name = array();
				foreach ($xml->ХарактеристикиТовара->ХарактеристикаТовара as $feature_option) {
					// разбиваем название опции
					$matches = $this->splitNameStr((string)$feature_option->Наименование);
					$option_name[] = $matches['name'];
				}
				$option_value = $feature['name'];

				$this->log($option_value, 2);

				$option_name		= implode(",",array_reverse($option_name));
				$option_id 			= $this->setOption($option_name);
				$option_value_id 	= $this->setOptionValue($option_id, $option_value);
				$feature_options[$option_value_id]	= array(
					'name'				=> $option_name,
					'value'				=> $option_value,
					'option_id'			=> $option_id,
					'option_value_id'	=> $option_value_id,
					'subtract'			=> $this->config->get('exchange1c_product_options_subtract') == 1 ? 1 : 0
				);



			} elseif ($this->config->get('exchange1c_product_options_mode') == 'certine') {
				// Отдельные товары
				$this->log("data:", 2);
				$this->log($data, 2);
				$this->log("feature:", 2);
				$this->log($feature, 2);

			} elseif ($this->config->get('exchange1c_product_options_mode') == 'related') {
				foreach ($xml->ХарактеристикиТовара->ХарактеристикаТовара as $feature_option) {

					// ЗНАЧЕНИЕ ОПЦИИ
					$matches_value = $this->splitNameStr((string)$feature_option->Значение);
					$value_sort_order 	= $matches_value['order'];
					$value_name			= $matches_value['name'];
					$value_option		= $matches_value['option'];

					$image	= '';

					// ОПЦИЯ
					$matches_option = $this->splitNameStr((string)$feature_option->Наименование);

					// Тип по-умолчанию, если не будет переопределен
					$option_type = "select";
					switch($matches_option['option']) {
						case 'select':
							$option_type 	= 'select';
							break;
						case 'radio':
							$option_type 	= 'radio';
							break;
						case 'checkbox':
							$option_type 	= 'checkbox';
							break;
						case 'image':
							$option_type 	= 'image';
							$image			= $matches_value['option'] ? "options/" . $matches_value['option'] : "";
							break;
						default:
							$option_type 	= "select";
					}

					//$this->log("[i] Название товара: '" . $data['name'] . "'", 2);

					$option_id			= $this->setOption($matches_option['name'], $option_type, $matches_option['order']);
					$option_value_id    = $this->setOptionValue($option_id, $value_name, $image, $value_sort_order);

					$feature_options[$option_value_id] = array(
						'option_cml_id'		=> $feature_option->Ид ? (string)$feature_option->Ид : '',
						'subtract'			=> $this->config->get('exchange1c_product_options_subtract') == 1 ? 1 : 0,
						'name'				=> $matches_option['name'],
						'value'				=> $value_name,
						'option_id'			=> $option_id,
						'option_value_id'   => $option_value_id,
						'type'				=> $option_type
					);


				}
			}

		}
		$feature['options'] = $feature_options;
		$this->log($feature_options, 2);
		$data['features'][$feature_cml_id] = $feature;

		$this->log("> Характеристика: '" . $feature['name'] . "'");
		$this->log("product_name: = " . $product_name, 2);

		//$this->parseProductFeature($xml->ХарактеристикиТовара, $data);
		$this->log("<== parseFeature()", 2);
		return true;

	} // parseFeature()


	/**
	 * Читает типы цен из настроек
	 */
	private function getConfigPriceType() {
		$this->log("==> getConfigPriceType()",2);
		$price_types = $this->config->get('exchange1c_price_type');
		if (!$price_types)
			$price_types = array();
		$this->log("<== getConfigPriceType()",2);
		return $price_types;
	} // getConfigPriceType()

	/**
	 * Разбор предложений
	 */
	private function parseOffers($xml, $offers_pack) {

		$this->log("==> parseOffers()",2);
		if (!$xml->Предложение) return true;

		// Массив для хранения данных об одном товаре, все характеристики загружаются в него
		$data = array();

		foreach ($xml->Предложение as $offer) {

			$this->log("=-=-=-= НАЧАЛО ПРЕДЛОЖЕНИЯ =-=-=-=", 2);

			// Получаем Ид товара и характеристики
			$cml_id 			= explode("#", (string)$offer->Ид);

			$product_cml_id		= $cml_id[0];
			$feature_cml_id 	= isset($cml_id[1]) ? $cml_id[1] : '';
			unset($cml_id);

			// Проверка на пустое предложение
			if (empty($product_cml_id)) {
				$this->log("[!] Ид товара пустое, предложение игнорируется!", 2);
				continue;
			}

			$this->log("[i] Ид товара: " . $product_cml_id . ", Ид характеристики: " . $feature_cml_id, 2);

			// Читаем product_id, если нет товара выходим с ошибкой, значит что-то не так
			$product_id = $this->getProductIdByCML($product_cml_id);
			if (!$product_id) {
				$this->log("[ERROR] Не найден товар в базе по Ид");
				return false;
			}

			// ОПРЕДЕЛЯЕМ К КАКОМУ ТОВАРУ ОТНОСИТСЯ ПРЕДЛОЖЕНИЕ
			if (isset($data['product_id'])) {
				$this->log("[i] Есть предыдущее предложение");
				if ($data['product_id'] == $product_id) {
					$this->log("[i] Предложение относится к предыдущему товару, добавляем предложения", 2);

				} else {
					$this->log("[i] Предложение нового товара, нужно обработать предыдущие предложения и очистить данные", 2);
					if (!$this->updateProduct($data)) return false;

					$data = array();
					// Записывает в data: product_id,name,sku,brand,desc,cats,cat_id
					// Только когда первый раз читается новый товар
					if (!$this->getProduct($product_id, $data))	return false;
				}
			} else {
				$this->log("[i] Пустые данные, первый товар", 2);
				// Записывает в data: product_id,name,sku,brand,desc,cats,cat_id
				// Только когда первый раз читается новый товар
				if (!$this->getProduct($product_id, $data))
					return false;
			}

			//$this->log("Товар: '" . $data['name'] . "'");

			// Базовая единица измерения
			if ($offer->БазоваяЕдиница) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><БазоваяЕдиница>",2);
				$data['unit'] = $this->parseProductUnit($offer->БазоваяЕдиница);
			}

			if ($feature_cml_id) {
				// Предложение с характеристикой
				// Создает характеристику, связь, и опции
				if ($offer->ХарактеристикиТовара) {
					//$this->log("<ПакетПредложений><Предложения><Предложение><ХарактеристикиТовара>",2);
					if (!$this->parseFeature($offer, $product_id, $feature_cml_id, $data, $offers_pack))
						return false;
				}

			}

			if ($offer->Штрихкод) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><Штрихкод>",2);
				if ($feature_cml_id) {
					// штрихкод характеристики
					$data['features'][$feature_cml_id]['ean'] 	=  (string)$offer->Штрихкод;
				} else {
					// без характеристики
					$data['ean'] 	=  (string)$offer->Штрихкод;
				}
			}

			if ($offer->Количество) {
				if (!isset($data['quantity']))
					$data['quantity'] = 0;
				//$this->log("<ПакетПредложений><Предложения><Предложение><Количество>",2);
				if ($feature_cml_id) {
					// штрихкод характеристики
					$data['features'][$feature_cml_id]['quantity'] 	=  (float)$offer->Количество;
					$data['quantity'] 	+=  (float)$offer->Количество;
				} else {
					// без характеристики
					$data['quantity'] 	=  (float)$offer->Количество;
				}
			}

			if ($offer->Цены) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><Цены>",2);
				if ($feature_cml_id) {
					if (!isset($offers_pack['price_types'])) {
						$offers_pack['price_types'] = $this->getConfigPriceType();
					}
					$data['features'][$feature_cml_id]['prices'] = $this->parsePrice($offer->Цены, $offers_pack, $data);
				} else {
					$data['prices'] = $this->parsePrice($offer->Цены, $offers_pack, $data);
				}
			}

			// остатки CML <= 2.08
			if ($offer->Склад) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><Склад>",2);
				if ($feature_cml_id) {
					$data['features'][$feature_cml_id]['quantities'] = $this->parseQuantity($offer, $offers_pack, $data);
				} else {
					$data['quantities'] = $this->parseQuantity($offer, $offers_pack, $data);
				}
			}

			// остатки CML = 2.09
			if ($offer->Остатки) {
				//$this->log("<ПакетПредложений><Предложения><Предложение><Остатки>",2);
				if ($feature_cml_id) {
					$quantities = $this->parseQuantity($offer->Остатки, $offers_pack, $data);
					$data['features'][$feature_cml_id]['quantities'] = $quantities;
				} else {
					$data['quantities'] = $this->parseQuantity($offer->Остатки, $offers_pack, $data);
				}
			}

			// общий остаток
			if (isset($data['features'][$feature_cml_id]['quantities'])) {
				//$this->log($data['features'][$feature_cml_id]['quantities'],2);
				$quantity_total = 0;
				foreach ($data['features'][$feature_cml_id]['quantities'] as $quantity) {
					$quantity_total += $quantity;
					//$this->log("Общий остаток характеристик ".$quantity_total,2);
				}
				if (isset($data['quantity'])) {
					//$this->log("Общий остаток ".$data['quantity'],2);
					$data['quantity'] += $quantity_total;
				} else {
					$data['quantity'] = $quantity_total;
					//$this->log("Общий остаток ".$data['quantity'],2);
				}
			}

			$data['product_cml_id'] = $product_cml_id;
			$data['product_id'] 	= $product_id;

		} // foreach()

		// Обновляем последний товар
		if (isset($data['product_id'])) {
			if (!$this->updateProduct($data)) return false;
		}

//			$this->log("data-end-offers:",2);
//			$this->log($data, 2);
		$this->log("<== parseOffers()",2);
		return true;
	} // parseOffers()


	/**
	 * Загружает пакет предложений
	 */
	private function parseOffersPack($xml) {
		$this->log("==> parseOffersPack() ================================= ПАКЕТ ПРЕДЛОЖЕНИЙ =================================",2);

		$offers_pack = array();
		$offers_pack['offers_pack_id']	= (string)$xml->Ид;
		$offers_pack['name']			= (string)$xml->Наименование;
		$offers_pack['directory_id']	= (string)$xml->ИдКаталога;
		$offers_pack['classifier_id']	= (string)$xml->ИдКлассификатора;

		// Сопоставленные типы цен
		if ($xml->ТипыЦен) {
			$this->log("<Классификатор><ПакетПредложений><ТипыЦен>",2);
			$offers_pack['price_types'] = $this->parsePriceType($xml->ТипыЦен);
            //$this->log($offers_pack['price_types'], 'Загруженные типы цен');
			unset($xml->ТипыЦен);
		}

		// Загрузка складов
		if ($xml->Склады) {
			$this->log("<Классификатор><ПакетПредложений><Склады>",2);
			$offers_pack['warehouses'] = $this->parseWarehouses($xml->Склады);
			unset($xml->Склады);
		}
		// Загружаем предложения
		if (!$this->parseOffers($xml->Предложения, $offers_pack)) {
			return 0;
		}
		unset($xml->Предложения);

		return 1;
	 } // parseOffersPack()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ ЗАКАЗОВ ******************************
	 */

	/**
	 * Меняет статусы заказов
	 *
	 * @param	int		exchange_status
	 * @return	bool
	 */
	private function sendMail($subject, $order_info) {

		$this->log("==> sendMail()",2);
		$message = 'Изменился статус Вашего заказа!';

		$mail = new Mail();
		$mail->protocol = $this->config->get('config_mail_protocol');
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($order_info['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		$mail->setText($message);
		$mail->send();
		$this->log($mail,2);

	} // sendMail()

	/**
	 * Меняет статусы заказов
	 *
	 * @param	int		exchange_status
	 * @return	bool
	 */
	public function queryOrdersStatus($params) {
		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status']);
			$this->log("> Поиск заказов со статусом id: " . $params['exchange_status'],2);
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
			$this->log("> Поиск заказов с даты: " . $params['from_date'],2);
		}
		if ($query->num_rows) {
			foreach ($query->rows as $order_data) {

				//$this->log('order_data:',2);
				//$this->log($order_data,2);

				if ($order_data['order_status_id'] == $params['new_status']) {
					$this->log("> Cтатус заказа #" . $order_data['order_id'] . " не менялся.",2);
					//continue;
				}

				// Меняем статус
				$sql = "UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = '" . $params['new_status'] . "' WHERE `order_id` = '" . $order_data['order_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				$this->log("> Изменен статус заказа #" . $order_data['order_id'],1);
				// Добавляем историю в заказ
				$sql = "INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . $order_data['order_id'] . "', `comment` = 'Ваш заказ обрабатывается', `order_status_id` = " . $params['new_status'] . ", `notify` = 0, `date_added` = NOW()";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				$this->log("> Добавлена история в заказ #" . $order_data['order_id'],2);

				// Уведомление
				if ($params['notify']) {
					$this->log("> Отправка уведомления на почту: " . $order_data['email'],2);
					$this->sendMail('Статус Вашего заказа изменен', $order_data);
				}
			}
		}
		return 1;
	}


	/**
	 * Получает CML Ид характеристики по выбранным опциям
	 *
	 */
	private function getFeatureCML($order_id, $product_id) {

		$order_options = $this->model_sale_order->getOrderOptions($order_id, $product_id);
		//$this->log($order_options,2);
		$options = array();
		foreach ($order_options as $order_option) {
			$options[$order_option['product_option_id']] = $order_option['product_option_value_id'];
		}

		$product_feature_id = 0;
		foreach ($order_options as $order_option) {
			$sql = "SELECT `product_feature_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_option_value_id` = " . (int)$order_option['product_option_value_id'];
			$this->log($sql,2);
			$query = $this->db->query($sql);

			if ($query->num_rows) {
				if ($product_feature_id) {
					if ($product_feature_id != $query->row['product_feature_id']) {
						$this->log("[ERROR] По опциям товара найдено несколько характеристик!");
						return false;
					}
				} else {
					$product_feature_id = $query->row['product_feature_id'];
				}

			}

		}
		//$this->log($product_feature_id,2);

		$feature_cml_id = "";
		if ($product_feature_id) {
			// Получаем Ид
			$sql = "SELECT 1c_id FROM `" . DB_PREFIX . "product_feature` WHERE `product_feature_id` = " . (int)$product_feature_id;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$feature_cml_id = $query->row['1c_id'];
			}
			$features[$product_feature_id] = $feature_cml_id;
		}

		//$this->log($feature_cml_id,2);
		return $feature_cml_id;

	} // getFeatureCML


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ВЫГРУЗКИ ЗАКАЗОВ ******************************
	 */
	public function queryOrders($params) {
		$this->log("==== Выгрузка заказов ====",2);

		$version = $this->config->get('exchange1c_CMS_version');
		if (version_compare($version, '2.0.3.1', '>')) {
			$this->log("customer/customer_group",2);
			$this->load->model('customer/customer_group');
		} else {
			$this->log("sale/customer_group",2);
			$this->load->model('sale/customer_group');
		}

		$this->load->model('sale/order');

		if ($params['exchange_status'] != 0) {
			// Если указано с каким статусом выгружать заказы
			$sql = "SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status'];
			$this->log($sql,2);
			$query = $this->db->query($sql);
		} else {
			// Иначе выгружаем заказы с последей выгрузки, если не определа то все
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
		}

		$document = array();
		$document_counter = 0;

		if ($query->num_rows) {
			foreach ($query->rows as $orders_data) {
				$order = $this->model_sale_order->getOrder($orders_data['order_id']);
				$this->log("> Выгружается заказ #" . $order['order_id'],1);
				$date = date('Y-m-d', strtotime($order['date_added']));
				$time = date('H:i:s', strtotime($order['date_added']));
				if (version_compare($version, '2.0.3.1', '>')) {
					$customer_group = $this->model_customer_customer_group->getCustomerGroup($order['customer_group_id']);
				} else {
					$customer_group = $this->model_sale_customer_group->getCustomerGroup($order['customer_group_id']);
				}
				$document['Документ' . $document_counter] = array(
					 'Ид'          => $order['order_id']
					,'Номер'       => $order['order_id']
					,'Дата'        => $date
					,'Время'       => $time
					,'Валюта'      => $params['currency']
					,'Курс'        => 1
					,'ХозОперация' => 'Заказ товара'
					,'Роль'        => 'Продавец'
					,'Сумма'       => $order['total']
					,'Комментарий' => $order['comment']
					,'Соглашение'  => $customer_group['name']
				);

				// Разбирает ФИО
				$user = explode(",", $order['payment_firstname']);
				array_unshift($user, $order['payment_lastname']);
				$username = implode(" ", $user);

				// Контрагент
				$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = array(
					 'Ид'                 => $order['customer_id'] . '#' . $order['email']
					,'Наименование'		    => $username
					//+++ Для организаций
					//,'ИНН'		        => $order['payment_company_id']
					//,'КПП'				=>'753601001'
					//,'ОКПО'				=>'1234567890'
					//,'ОфициальноеНаименование' => $order['payment_company']		// Если заполнено, значит ЮрЛицо
					//,'ПолноеНаименование'	=> $order['payment_company']			// Полное наименование организации
					//,'РасчетныеСчета'		=> array(
					//	'НомерСчета'			=> '12345678901234567890'
					//	,'Банк'					=> ''
					//	,'БанкКорреспондент'	=> ''
					//	,'Комментарий'			=> ''
					//)
					//---
					,'Роль'               => 'Покупатель'
					,'ПолноеНаименование' => $username
					,'Фамилия'            => isset($user[0]) ? $user[0] : ''
					,'Имя'			      => isset($user[1]) ? $user[1] : ''
					,'Отчество'		      => isset($user[2]) ? $user[2] : ''
					,'АдресРегистрации' => array(
						'Представление'	=> $order['shipping_address_1'].', '.$order['shipping_city'].', '.$order['shipping_postcode'].', '.$order['shipping_country']
					)
					,'Контакты' => array(
						'Контакт1' => array(
							'Тип' => 'ТелефонРабочий'
							,'Значение'	=> $order['telephone']
						)
						,'Контакт2'	=> array(
							 'Тип' => 'Почта'
							,'Значение'	=> $order['email']
						)
					)
				);

				// Реквизиты документа передаваемые в 1С
				$document['Документ' . $document_counter]['ЗначенияРеквизитов'] = array(
					'ЗначениеРеквизита0' => array(
						'Наименование' => 'Дата отгрузки',
						'Значение' => $date
					)
//					,'ЗначениеРеквизита1' => array(
//						'Наименование' => 'Организация',
//						'Значение' => 'Тесла-Чита'
//					)
//					,'ЗначениеРеквизита2' => array(
//						'Наименование' => 'Склад',
//						'Значение' => 'Фрунзе 3'
//					)
//					,'ЗначениеРеквизита3' => array(
//						'Наименование' => 'ВидЦен',
//						'Значение' => 'Розница'
//					)
//					,'ЗначениеРеквизита4' => array(
//						'Наименование' => 'Подразделение',
//						'Значение' => 'Интернет-магазин'
//					)
//					,'ЗначениеРеквизита5' => array(
//						'Наименование' => 'Сумма включает НДС',
//						'Значение' => true
//					)
//					,'ЗначениеРеквизита6' => array(
//						'Наименование' => 'Статус заказа',
//						'Значение' => $order_status_description[$language_id]['name']
//					)
//					,'ЗначениеРеквизита7' => array(
//						'Наименование' => 'Дата отгрузки',
//						'Значение' => '2016-09-26Т10:10:10'
//					)
//
				);

				// Товары
				$products = $this->model_sale_order->getOrderProducts($orders_data['order_id']);

				$product_counter = 0;
				foreach ($products as $product) {
					$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter] = array(
						 'Ид'             => $this->getcml_idByProductId($product['product_id'])
						,'Наименование'   => $product['name']
						,'ЦенаЗаЕдиницу'  => $product['price']
						,'Количество'     => $product['quantity']
						,'Сумма'          => $product['total']
						,'Резерв' 		  	=> $product['quantity']
						,'БазоваяЕдиница' => array('Код' => '796','НаименованиеПолное' => 'Штука')
						,'Скидки'         => array('Скидка' => array(
							'УчтеноВСумме' => 'false'
							,'Сумма' => 0
							)
						)

						,'ЗначенияРеквизитов' => array(
							'ЗначениеРеквизита' => array(
								'Наименование' => 'ТипНоменклатуры'
								,'Значение' => 'Товар'
							)
						)
					);

					// Характеристики
					$this->log($order,2);
					$feature_cml_id = $this->getFeatureCML($orders_data['order_id'], $product['order_product_id']);
					if ($feature_cml_id) {
						$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter]['Ид'] .= "#" . $feature_cml_id;
					}

					$product_counter++;
				}

				$document_counter++;

			} // foreach ($query->rows as $orders_data)
		}

		// Формируем заголовок
		$root = '<?xml version="1.0" encoding="utf-8"?><КоммерческаяИнформация ВерсияСхемы="2.07" ДатаФормирования="' . date('Y-m-d', time()) . '" />';
		$xml = $this->array_to_xml($document, new SimpleXMLElement($root));

		// Проверка на запись файлов в кэш
		$cache = DIR_CACHE . 'exchange1c/';
		if (is_writable($cache)) {
			// запись заказа в файл
			$f_order = fopen(DIR_CACHE . 'exchange1c/orders.xml', 'w');
			fwrite($f_order, $xml->asXML());
			fclose($f_order);
		} else {
			$this->log("Папка " . $cache . " не доступна для записи, файл заказов не может быть сохранен!",1);
		}

		return $xml->asXML();
	}


	/**
	 * Адрес
	 */
	private function parseAddress($xml) {
		if (!$xml) return "";
		return (string)$xml->Представление;
	} // parseAddress()


	/**
	 * Банк
	 */
	private function parseBank($xml) {
		if (!$xml) return "";
		return array(
			'correspondent_account'	=> (string)$xml->СчетКорреспондентский,
			'name'					=> (string)$xml->Наименование,
			'bic'					=> (string)$xml->БИК,
			'address'				=> $this->parseAddress($xml->Адрес)
		);
	} // parseBank()


	/**
	 * Расчетные счета
	 */
	private function parseAccount($xml) {
		if (!$xml) return "";
		$data = array();
		foreach ($xml->РасчетныйСчет as $object) {
			$data[]	= array(
				'number'	=> $object->Номерсчета,
				'bank'		=> $this->parseBank($object->Банк)
			);
		}
		return $data;
	} // parseAccount()


	/**
	 * Владелец
	 */
	private function parseOwner($xml) {
		if (!$xml) return "";
		return array(
			'id'		=> (string)$xml->Ид,
			'name'		=> (string)$xml->Наименование,
			'fullname'	=> (string)$xml->ПолноеНаименование,
			'inn'		=> (string)$xml->ИНН,
			'account'	=> $this->parseAccount($xml->РасчетныеСчета)
		);
	} // parseOwner()


	/**
	 * Разбор классификатора
	 */
	private function parseClassifier($xml) {
		$this->log("==> parseClassifier()",2);

		$data = array();
		$data['id']				= (string)$xml->Ид;
		$data['name']			= (string)$xml->Наименование;
		$this->setStore($data['name']);

		// Организация
		if ($xml->Владелец) {
			$this->log("--->>> Загрузка владельца",2);
			$data['owner']			= $this->parseOwner($xml->Владелец);
			unset($xml->Владелец);
			$this->log("<<<--- Владелец загружен",2);
		}

		if ($xml->Группы) {
			$this->log("--->>> Загрузка категорий",2);
			$this->parseCategories($xml->Группы);
			unset($xml->Группы);
			$this->log("<<<--- Категории загружены",2);
		}

		if ($xml->ТипыЦен) {
			$this->log("--->>> Загрузка типов цен (CML v2.09)",2);
			$this->parsePriceType($xml->ТипыЦен);
			unset($xml->ТипыЦен);
			$this->log("<<<--- Типы цен загружены",2);
		}

		if ($xml->Склады) {
			$this->log("--->>> Загрузка складов (CML v2.09)",2);
			$this->parseWarehouses($xml->Склады);
			unset($xml->Склады);
			$this->log("<<<--- Склады загружены",2);
		}

		if ($xml->ЕдиницыИзмерения) {
			$this->log("--->>> Загрузка единиц измерений (CML v2.09)",2);
			$this->parseUnits($xml->ЕдиницыИзмерения);
			unset($xml->ЕдиницыИзмерения);
			$this->log("<<<--- Единицы измерения загружены",2);
		}

		if ($xml->Свойства) {
			$this->log("--->>> Загрузка свойств",2);
			$data['attributes']		= $this->parseAttributes($xml->Свойства);
			//unset($xml->Свойства);
			$this->log("<<<--- Свойства загружены",2);
		}

		return $data;
	} // parseClassifier()


	/**
	 * Разбор документа
	 */
	private function parseDocument($xml) {
		$id			= (string)$xml->Ид;
		$header		= $this->parseDocumentHeader($xml);
		$customer	= $this->parseDocumentCustomer($xml->Контрагенты);
		$products	= $this->parseDocumentProducts($xml->Товары);
		$details	= $this->parseDocumentDetails($xml->ЗначенияРеквизитов);
		unset($xml);

		return true;
	} // parseDocument()


	/**
	 * Импорт файла
	 */
	public function importFile($importFile, $type) {

		// Функция будет сама определять что за файл загружается
		$this->log("==== Начата загрузка данных ====");
		$this->log("[i] Всего доступно памяти: " . sprintf("%.3f", memory_get_peak_usage() / 1024 / 1024) . " Mb",2);

		$this->log(">>> Начинается чтение XML",2);
		// Конвертируем XML в массив
		$xml = simplexml_load_file($importFile);
		$this->log("<<< XML прочитан",2);

		// Файл стандарта Commerce ML
		if (!$this->checkCML($xml)) {
			return 0;
		}

		// IMPORT.XML, OFFERS.XML
		if ($xml->Классификатор) {
			$this->log(">>> Загружается классификатор",2);
			$classifier = $this->parseClassifier($xml->Классификатор);
			unset($xml->Классификатор);
			$this->log("<<< Классификатор загружен",2);
		} else {
			// CML 2.08 + Битрикс
			$classifier = array();
		}

		if ($xml->Каталог) {

			//$this->clearLog();

			// Запишем в лог дату и время начала обмена

			$this->log(">>> Загрузка каталога",1);
			if (!isset($classifier)) {
				$this->log("[i] Классификатор не загружен! Все товары из файлов будут загружены в магазин по умолчанию!");
			}

			if (!$this->parseDirectory($xml->Каталог, $classifier)) {
				$this->log("<<< [!] Ошибка загрузки каталога",1);
				return 0;
			}
			unset($xml->Каталог);
			$this->log("<<< Каталог загружен",1);
		}

		// OFFERS.XML
		if ($xml->ПакетПредложений) {
			$this->log(">>> Загрузка пакета предложений");

			if (!$this->parseOffersPack($xml->ПакетПредложений)) {
				// Пакет предложений
				return 0;
			}

			unset($xml->ПакетПредложений);
			$this->log("<<< Пакет предложений загружен");

			// После загрузки пакета предложений формируем SEO
			//$this->seoGenerate();

		}

		// ORDERS.XML
		if ($xml->Документ) {
			$this->log(">>> Загрузка документов");
			if (!isset($classifier)) {
				$this->log("[ERROR] Не загружен классификатор!");
				return 0;
			}

			// Документ (заказ)
			foreach ($xml->Документ as $doc) {
				if (!$this->parseDocument($doc)) {
					return 0;
				}
			}
			$this->log("<<< Документы загружены");
		}
		else {
			//$this->log("[i] Не обработанные данные XML",2);
			//$this->log($xml,2);
		}
		$this->log("==== Окончена загрузка данных ====");
		return 1;
	}


	/**
	 * Устанавливает обновления
	 */
	public function update($settings) {

		// Нужно ли обновлять
		if (version_compare($settings['exchange1c_version'], $this->version(), '>=')) {
			return "";
		}

		$version = $settings['exchange1c_version'];
		$update = false;

		//$version = $this->version();

		$message = "Модуль в обновлении не нуждается";

		if ($version == '1.6.2.b8') {
			$update = $this->update162b9();
			$version = '1.6.2.b9';
		}

		if ($update) {
			$this->setEvents();
			$settings['exchange1c_version'] = $version;
			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$message = "Модуль успешно обновлен до версии " . $version;
		}
		return $message;

	} // update()


	/**
	 * Устанавливает обновления
	 */
	private function update162b9() {

		// Увеличивем точность поля веса до тысячных
		if ($this->existField('product','weight'))
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `weight` `weight` DECIMAL(15,3) NOT NULL DEFAULT 0.000 COMMENT 'Вес'");

		// Удаляем колонку
		if ($this->existField('product_feature','product_id'))
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_feature` DROP COLUMN `product_id`");

		// Добавляем колонку
		if (!$this->existField('product_price','product_price_id')) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_price` ADD `product_price_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
		}

		// Добавляем колонку
		if (!$this->existField('product_quantity','product_quantity_id')) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_quantity` ADD `product_quantity_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
		}

		if (!$this->existField('cart','product_feature_id')) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` ADD  `product_feature_id` INT( 11 ) NOT NULL DEFAULT 0 AFTER  `option`");
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` ADD  `unit_id` INT( 11 ) NOT NULL DEFAULT 0 AFTER  `option`");
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` DROP INDEX  `cart_id` ,	ADD INDEX  `cart_id` (  `customer_id` ,  `session_id` ,  `product_id` ,  `recurring_id` ,  `product_feature_id` , `unit_id`)");
		}

		// Привязка единиц измерения к торговой системе
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "unit_to_1c` (
				`unit_id` 					SMALLINT(6) 	NOT NULL 				COMMENT 'ID единицы измерения по каталогу',
				`cml_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид единицы измерения в ТС',
				KEY (`cml_id`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `". DB_PREFIX ."unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);


		return true;

	}


}

