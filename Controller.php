<?php

// Подключаем PHP-SDK CRest для работы с REST API
require_once __DIR__ . '/crest/crest.php';

/**
 * Контроллер для обработки запросов виджета
 * Обрабатывает два экшена: index (отображает виджет) и install (устанавливает локальное приложение)
 */
class Controller
{
    /**
     * Экшен index: отрисовывает приложение
     */
    public function index()
    {
        // Получаем ID списка из параметров в запросе
        // $listId = $_GET['list_id'] ?? '';

        // Пример запроса на получение данных списка
        // $zhdPerevozki = CRest::call('lists.element.get', [
        //     'IBLOCK_TYPE_ID' => 'lists',
        //     'IBLOCK_ID'      => 30,
        //     'select'         => ['ID', 'NAME'],
        // ]);

        // Подключаем файл с формой, наши переменные автоматом пробросятся в наш виджет
        $widgetFile = __DIR__ . '/widget.php';
        if (file_exists($widgetFile)) {
            include $widgetFile;
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'Ошибка: файл widget.php не найден.';
        }
    }

    /**
     * Экшен install: регистрирует локальное приложение в Битрикс24,
     * используя CRest::installApp
     */
    public function install()
    {
        // Собираем все входящие данные, включая параметры авторизации Bitrix24
        $params = $_REQUEST;
        $domain = $params['DOMAIN'] ?? '';
        $newAccessToken  = $params['AUTH_ID'] ?? '';
        $newRefreshToken = $params['REFRESH_ID'] ?? '';
        if (!$newAccessToken) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Отсутствуют параметры авторизации.';
            return;
        }

        // Загружаем конфигурацию приложения
        $configFile = __DIR__ . '/app_config.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            // Сохраняем новые токены в конфиг
            $config['access_token']  = $newAccessToken;
            $config['refresh_token'] = $newRefreshToken;
            $export = var_export($config, true);
            $phpCode = "<?php\nreturn {$export};\n";
            if (false === file_put_contents($configFile, $phpCode, LOCK_EX)) {
                header('HTTP/1.0 500 Internal Server Error');
                return;
            }
        }

        // запишет в /crest/settings.json
        $result = CRest::installApp();  
        if (!empty($result['error'])) {
            echo "ошибка регистрации";
            return;
        }

        // Передаём $result в файл install.php
        $viewFile = __DIR__ . '/crest/install.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            echo "Не найден файл представления install.php";
        }

        // //Регистрируем обработчик встраивания
        // $result = CRest::call('placement.bind', [
        //     'auth' => $newAccessToken,
        //     'PLACEMENT'   => 'LEFT_MENU',
        //     'HANDLER'     => $config['handler'],
        //     'TITLE'       => $config['title'],
        //     'DESCRIPTION' => $config['description'],
        //     //'LANG_ALL'  => ['ru' => $config['title']],
        //     // 'ADDITIONAL'=> []   // дополнительные параметры
        // ]);
    }
}

// Точка входа
//
// XXXXX.RU/Controller.php?action=index
// XXXXX.RU/Controller.php?action=install
//
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$controller = new Controller();
if (method_exists($controller, $action)) {
    $controller->{$action}();
} else {
    header('HTTP/1.0 404 Not Found');
    echo 'Экшен ' . htmlspecialchars($action) . ' не найден.';
}
