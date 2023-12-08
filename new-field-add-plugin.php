<?php
/*
Plugin Name: Add Promotional Tag
Plugin URI: https://github.com/Amaterra
Description: Плагин, который интегрируется с WooCommerce и добавляет новое поле "Promotional Tag" в продукты WooCommerce, в разделе редактирования товара и новую вкладку в админ-панели с возможностью массово добавить, удалить или заменить рекламную метку товара.
Version: 1.0
Author: amate-ra
Author URI: https://github.com/Amaterra
License: GPLv2 or later
Text Domain: new-field-add-plugin
*/

if (!function_exists('add_action')) {
    exit;
}

// Активация плагина
function activate_test_fields_plugin() {
    
// Добавление метабокса "Рекламная метка" на страницу редактирования товара
add_action('add_meta_boxes', 'add_custom_field_metabox');
function add_custom_field_metabox() {
        add_meta_box(
            'custom_field_metabox',
            'Рекламная метка',
            'display_custom_field_metabox',
            'product',
            'normal',
            'high'
        );
    }

function display_custom_field_metabox($post) {
        $custom_field = get_post_meta($post->ID, 'custom_field', true);
        ?>
        <label for="custom_field">Рекламная метка:</label>
        <input type="text" id="custom_field" name="custom_field" value="<?php echo esc_attr($custom_field); ?>">
        <?php
    }

    add_action('save_post', 'save_custom_field_metabox');
    function save_custom_field_metabox($post_id) {
        if (isset($_POST['custom_field'])) {
            $custom_field = sanitize_text_field($_POST['custom_field']);
            update_post_meta($post_id, 'custom_field', $custom_field);
        }
    }
}
register_activation_hook(__FILE__, 'activate_test_fields_plugin');

// Деактивация плагина
function deactivate_test_fields_plugin() {

    // Удаление метабокса "Рекламная метка" с страницы редактирования товара
    remove_action('add_meta_boxes', 'add_custom_field_metabox');
    remove_action('save_post', 'save_custom_field_metabox');
}
register_deactivation_hook(__FILE__, 'deactivate_test_fields_plugin');

// Добавление настраиваемого поля в WooCommerce

function add_custom_field_to_products() {
    echo '<div class="options_group">';
    woocommerce_wp_text_input(
        array(
            'id' => 'custom_field',
            'label' => esc_html__('Рекламная метка', 'text-domain'),
            'placeholder' => esc_attr__('Введите рекламную метку', 'text-domain'),
            'desc_tip' => 'true',
            'description' => esc_html__('Добавьте рекламную метку к продукту.', 'text-domain')
        )
    );
    echo '</div>';
}

add_action('woocommerce_product_options_general_product_data', 'add_custom_field_to_products');

// Сохранение значения настраиваемого поля

function save_custom_field_value($product_id) {
    $custom_field = $_POST['custom_field'];
    update_post_meta($product_id, 'custom_field', sanitize_text_field($custom_field));
}

add_action('woocommerce_process_product_meta', 'save_custom_field_value');

// Создание интерфейса администратора

function display_admin_interface() {

    if (isset($_POST['update_ad_label'])) {
        $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : array();
        $ad_label_input = sanitize_text_field($_POST['ad_label_input']);

        foreach ($selected_products as $product_id) {
            update_post_meta($product_id, 'custom_field', $ad_label_input);
        }
    }

    $category_filter = isset($_POST['category_filter']) ? sanitize_text_field($_POST['category_filter']) : '';
    $price_filter = isset($_POST['price_filter']) ? sanitize_text_field($_POST['price_filter']) : '';
    $stock_filter = isset($_POST['stock_filter']) ? sanitize_text_field($_POST['stock_filter']) : '';

    $args = array('post_type' => 'product', 'posts_per_page' => -1);

    // Фильтр по категориям
    if (!empty($category_filter)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $category_filter,
            ),
        );
    }

    // Фильтр по наличию
    if (!empty($stock_filter)) {
        $args['meta_query'][] = array(
            'key' => '_stock_status',
            'value' => ($stock_filter === 'instock') ? 'instock' : 'outofstock',
            'compare' => '=',
        );
    }

    $products = wc_get_products($args);

    // Таблица
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Продукты WooCommerce', 'text-domain') . '</h1>';
    echo '<form method="post" action="">';

    wp_nonce_field('update_ad_label_action', 'update_ad_label_nonce');

    // Кнопка для рекламной метки
    echo '<label for="ad_label_input">' . esc_html__('Введите текст для рекламной метки:', 'text-domain') . '</label>';
echo '<input type="text" name="ad_label_input" id="ad_label_input">';
echo '<input type="submit" name="update_ad_label" value="' . esc_html__('Обновить рекламную метку', 'text-domain') . '">';
echo '<input type="hidden" name="action" value="update_ad_label_action">';

    //Обновление метки
    function handle_bulk_update() {
        if (isset($_POST['update_ad_label'])) {
            // Проверка nonce
            if (!isset($_POST['update_ad_label_nonce']) || !wp_verify_nonce($_POST['update_ad_label_nonce'], 'update_ad_label_action')) {
                die('Недопустимая секретная метка nonce.');
            }

            $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : array();
            $ad_label_input = sanitize_text_field($_POST['ad_label_input']);

            foreach ($selected_products as $product_id) {
                update_post_meta($product_id, 'custom_field', $ad_label_input);
            }
        }
    }

    // Фильтр категории
    echo '<select name="category_filter">';
    echo '<option value="">Все категории</option>';

    $categories = get_terms('product_cat');

    foreach ($categories as $category) {
        $selected = ($category_filter == $category->term_id) ? 'selected' : '';
        echo '<option value="' . esc_html($category->term_id) . '" ' . esc_html($selected) . '>' . esc_html($category->name) . '</option>';
    }
    echo '</select>';

    //Фильтр наличия
    echo '<select name="stock_filter">';
    echo '<option value="">Наличие</option>';
    echo '<option value="instock" ' . esc_html(selected($stock_filter, 'instock', false)) . '>В наличии</option>';
    echo '<option value="outofstock" ' . esc_html(selected($stock_filter, 'outofstock', false)) . '>Нет в наличии</option>';
    echo '</select>';

    echo '<input type="submit" value="Фильтровать">';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Выбрать</th><th>Название продукта</th><th>Категория</th><th>Цена</th><th>Наличие</th><th>Рекламная метка</th></tr></thead>';
    echo '<tbody>';

    foreach ($products as $product) {
        // Пропускать продукты, которые не соответствуют фильтру
        if (!empty($stock_filter) && $product->get_stock_status() !== ($stock_filter === 'instock' ? 'instock' : 'outofstock')) {
            continue;
        }

        $custom_field = get_post_meta($product->get_id(), 'custom_field', true);
        $stock_status = $product->get_stock_status();

        echo '<tr>';
        echo '<td><input type="checkbox" name="selected_products[]" value="' . esc_attr($product->get_id()) . '"></td>';
        echo '<td>' . esc_html($product->get_name()) . '</td>';

        // Вывести категории продукта
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        $category_names = array();

        foreach ($categories as $category) {
            $category_names[] = $category->name;
        }
        echo '<td>' . esc_html(implode(', ', $category_names)) . '</td>';

        echo '<td>' . esc_html($product->get_price()) . '</td>';
        echo '<td>' . esc_html($product->get_stock_status()) . '</td>';
        echo '<td>' . esc_html($custom_field) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    function save_ad_label_input() {
        if (isset($_POST['update_selected']) && isset($_POST['ad_label_input'])) {
            $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : array();
            $ad_label_input = sanitize_text_field($_POST['ad_label_input']);

            foreach ($selected_products as $product_id) {
                update_post_meta($product_id, 'custom_field', $ad_label_input);
            }
        }
    }

    echo '<button type="button" id="select-all-checkboxes">' . esc_html__('Выбрать все', 'text-domain') . '</button>';

// JavaScript для кнопки "Выбрать все"

echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        var selectAllButton = document.getElementById("select-all-checkboxes");
        if (selectAllButton) {
            var toggleState = 0; // 0: Выбрать все, 1: Снять все, 2: Выбрать все снова
            selectAllButton.addEventListener("click", function () {
                var checkboxes = document.querySelectorAll(\'input[name="selected_products[]"]\');
                checkboxes.forEach(function (checkbox) {
                    if (toggleState === 0) {
                        checkbox.checked = true;
                    } else if (toggleState === 1) {
                        checkbox.checked = false;
                    } else {
                        checkbox.checked = true;
                    }
                });

                // Переключение состояния кнопки
                toggleState = (toggleState + 1) % 3;
            });
        }
    });
    </script>';

    add_action('admin_init', 'save_ad_label_input');

    echo '</form>';
    echo '</div>';
    echo '</div>';
}

// Добавление страницы интерфейса в меню администратора
function add_admin_interface_page() {
    add_menu_page('Promotional Tag', 'Promotional Tag', 'manage_options', 'my-plugin', 'display_admin_interface');
}

add_action('admin_menu', 'add_admin_interface_page');
?>