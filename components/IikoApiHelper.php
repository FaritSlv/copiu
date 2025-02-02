<?php

namespace app\components;

use app\models\Settings;
use DOMDocument;
use Yii;

/**
 * @property string $base_url Адрес АПИ сервера
 * @property string $request_string СТрока запроса
 * @property string $login Логин
 * @property string $password Пароль
 * @property string $data Данные, полученнные по АПИ
 * @property string $token Токен, полученный от сервера ikko
 * @property string $cookie Куки для отправки
 * @property string $headers заголовки
 * @property string $post_data Данные, отправляемые в POST запросе
 */
class IikoApiHelper
{
    protected $base_url;
    protected $request_string;
    protected $login;
    protected $password;
    protected $data;
    protected $token;
    protected $post_data;
    protected $headers;


    public function __construct()
    {
        $this->base_url = Settings::getValueByKey(['ikko_server_url']);
        if (strpos($this->base_url, '/', strlen($this->base_url) - 2) === false) {
            $this->base_url .= '/';
        }
        $this->login = Settings::getValueByKey(['ikko_server_login']);
        $this->password = Settings::getValueByKey(['ikko_server_password']);
        $this->token = Settings::getValueByKey(['token']);
        $date = Settings::getValueByKey(['token_date']);
        $time = strtotime($date);
        if ((time() - $time) > (60 * 60)) {
            $token_is_expired = true;
        } else {
            $token_is_expired = false;
        }
        //Yii::debug('Token expired: ' . (int)$token_is_expired, 'test');

        if (!$this->token || $token_is_expired) {
            $this->login();
        }
    }

    public function test()
    {
        $this->login();
    }

    /**
     * Получение токена доступа
     * @return bool
     */
    protected function login()
    {
        $params = [
            'login' => $this->login,
            'pass' => sha1($this->password),
        ];
        $this->request_string = $this->base_url . 'resto/api/auth?' . http_build_query($params);
        $this->token = $this->send();
        if ($this->token) {
            Settings::setValueByKey('token', $this->token);
            Settings::setValueByKey('token_date', date('Y-m-d H:i:s'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Освобождение лицензии
     * @return mixed
     */
    public function logout()
    {
        $this->request_string = $this->base_url . 'resto/api/logout?key=' . $this->token;
        Settings::setValueByKey('token', null);
        Settings::setValueByKey('token_date', null);
        return $this->send();
    }

    protected function send($type = 'GET')
    {
        //Yii::debug('Request string: ' . $this->request_string, 'test');
        //Yii::debug('Headers: ' . json_encode($this->headers), 'test');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->request_string);
        if ($type == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);
        }
        if ($this->headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($ch, CURLOPT_COOKIE, "key=" . $this->token);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        //Yii::debug(curl_getinfo($ch, CURLINFO_HEADER_OUT), 'test');

        curl_close($ch);
        if ($response === false) {
            Yii::error(curl_error($ch), '_error');
        }

//        Yii::debug($response, 'test');
        return $response;
    }

    /**
     * Номенклатура
     * @return mixed
     * @throws \Exception
     */
    public function getItems()
    {
        $this->request_string = $this->base_url
            . 'resto/api/v2/entities/products/list?includeDeleted=false&key='
            . $this->token;
        $result = $this->send();

        if (strpos($result, 'Token is expired or invalid') !== false) {
            $this->login();
            $result = $this->send();
        }

        $path_file = 'uploads/list_items.json';
        $put_result = file_put_contents($path_file, $result);
        //Yii::debug($put_result, 'test');

        return $path_file;
//        return json_decode($result, 'true');


    }

    /**
     * Получает позиции номенклатуры по Идентификатору
     * @param array $ids Внешние ID продуктов
     * @return array
     */
    public function getItemsById(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $str_ids = implode('&ids=', $ids);

//        Yii::debug($str_ids, 'test');

        $this->request_string = $this->base_url
            . 'resto/api/v2/entities/products/list?includeDeleted=false&key='
            . $this->token . '&ids=' . $str_ids;
        $result = $this->send();
        Yii::debug($this->request_string, 'test');
        Yii::debug($result, 'test');

        return json_decode($result, true);
    }

    /**
     * Получает номенклатурные группы
     * @return mixed
     */
    public function getNomenclatureGroups()
    {
        $this->request_string = $this->base_url
            . 'resto/api/v2/entities/products/group/list?includeDeleted=false&key='
            . $this->token;
        $result = $this->send();

        if (strpos($result, 'Token is expired or invalid') !== false) {
            $this->login();
            $result = $this->send();
        }

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Данные не получены',
            ];
        }
        return json_decode($result, 'true');
    }

    /**
     * @param string||null $counteragent
     * @return mixed
     */
    public function getBalance($counteragent_outer_id = null)
    {
        $date = date('Y-m-d\TH:i:s', time());
        if (!$counteragent_outer_id) {
            $this->request_string = $this->base_url
                . "resto/api/v2/reports/balance/counteragents?timestamp={$date}&key={$this->token}";
        } else {
            $this->request_string = $this->base_url
                . "resto/api/v2/reports/balance/counteragents?timestamp={$date}&key={$this->token}&counteragent={$counteragent_outer_id}";
        }

        $result = $this->send();

        $info = json_decode($result, 'true');
       Yii::debug($info, 'test');

        $sum = 0;
        $rdb = Settings::getValueByKey('revenue_debit_account');
        foreach ($info as $item) {
            if ($item['account'] == $rdb) {
                $sum -= isset($item['sum']) ? $item['sum'] : 0;
            }
        }

        return round($sum, 2);
    }

    /**
     * Получение накладной по номеру
     * @param array $params Параметры
     * @return mixed
     */
    public function getOrderBlank(array $params)
    {
        if (!isset($params['from']) || !$params['from']) {
            Yii::error('Отсутствует параметр "from"', 'error');
            return false;
        }
        if (!isset($params['to']) || !$params['to']) {
            Yii::error('Отсутствует параметр "to"', 'error');
            return false;
        }
        if (!isset($params['number']) || !$params['number']) {
            Yii::error('Отсутствует параметр "number"', 'error');
            return false;
        }
        $params['key'] = $this->token;

        $params['currentYear'] = 'false';
        $query = http_build_query($params);
        $this->request_string = $this->base_url
            . 'resto/api/documents/export/outgoingInvoice/byNumber?' . $query;
        $result = $this->send();

//       Yii::debug($result, 'test');
        $result = simplexml_load_string($result);
        $json = json_encode($result);
//       Yii::debug(json_decode($json, 'true'), 'test');
        return json_decode($json, 'true');
    }

    /**
     * Создание расходной накладной
     * @param array $params Параметры документа
     * @return string
     */
    public function makeExpenseInvoice(array $params)
    {
        Yii::info('Создание расходной накладной', 'test');
        Yii::info($params, 'test');
        $dom = new domDocument('1.0', 'utf-8');
        $root = $dom->createElement('document');
        $dom->appendChild($root);
        $number = $dom->createElement('documentNumber', $params['documentNumber']);
        $date_incoming = $dom->createElement('dateIncoming', $params['dateIncoming']);
        $useDefaultDocumentTime = $dom->createElement('useDefaultDocumentTime', 'true');
        $revenueAccountCode = $dom->createElement('revenueAccountCode', '4.01');
        $defaultStoreId = $dom->createElement('defaultStoreId', $params['defaultStoreId']);
        $counteragent_id = $dom->createElement('counteragentId', $params['counteragentId']);
        $comment = $dom
            ->createElement('comment', $params['comment']);

        $root->appendChild($number);
        $root->appendChild($date_incoming);
        $root->appendChild($useDefaultDocumentTime);
        $root->appendChild($revenueAccountCode);
        $root->appendChild($defaultStoreId);
        $root->appendChild($counteragent_id);
        $root->appendChild($comment);

        $items = $dom->createElement('items');
        foreach ($params['items'] as $item) {
            $item_element = $dom->createElement('item');
            $product_id = $dom->createElement('productId', $item['outer_id']);
            $num = $dom->createElement('productArticle', $item['num']);
            $price = $dom->createElement('price', $item['price']);
            $amount = $dom->createElement('amount', $item['amount']);
            $sum = $dom->createElement('sum', $item['sum']);
            $container = $dom->createElement('containerId', $item['container_id']);

            $item_element->appendChild($product_id);
            $item_element->appendChild($num);
            $item_element->appendChild($price);
            $item_element->appendChild($amount);
            $item_element->appendChild($sum);
            $item_element->appendChild($container);
            $items->appendChild($item_element);
        }
        $root->appendChild($items);

//        $path = "uploads/invoice.xml";
//        $dom->save($path);

        $this->post_data = $dom->saveXML();
//        if (YII_ENV_DEV) {
        //Сохраняем в файл
        $path = 'uploads/out_invoice/' . $params['documentNumber'] . '.xml';
        try {
            file_put_contents($path, $this->post_data);
            Yii::info('Файл' . $params['documentNumber'] . '.xml сохранен', 'test');
        } catch (\Exception $e) {
            Yii::info('Ошибка: ' . $e->getMessage(), 'test');
            Yii::error($e->getTraceAsString(), 'test');
        }
//        }

        $this->headers = [
            'Content-Type: application/xml'
        ];
       Yii::debug($this->post_data, 'test');

        $this->request_string = $this->base_url . 'resto/api/documents/import/outgoingInvoice?key=' . $this->token;
       Yii::debug('Отправка запроса...', 'test');

        $result = $this->send('POST');
       Yii::debug('Запрос отправлен, ответ получен:', 'test');
       Yii::debug($result, 'test');

        //Сохраняем ответ в файл
        try {
           //Yii::debug('Сохранение файла ответа...', 'test');
            file_put_contents('uploads/out_invoice/' . $params['documentNumber'] . '_response.xml', $result);
           //Yii::debug('Сохранение файла ответа. Успешно. Файл: ' . $params['documentNumber'] . '_response.xml',
                //'test');
        } catch (\Exception $e) {
           //Yii::debug('Сохранение файла ответа. Ошибка: ' . $e->getMessage(), 'test');
            Yii::error($e->getMessage(), 'test');
        }
       //Yii::debug('Завершение создания накладной.', 'test');

        //Запрашивем созданную накладную и записываем в ответ в файл
        //TODO после выявления проблемы с не совпадением продуктов в системе и в Айке - убрать все что ниже, кроме return
        sleep(2);
        $invoice = $this->getExpenseInvoice($params['documentNumber']);
        $invoice_path = 'uploads/out_invoice/' . $params['documentNumber'] . '_created_invoice' . '.xml';
        try {
           //Yii::debug('Сохранение запрошенной накладной...', 'test');
            file_put_contents($invoice_path, $invoice);
           //Yii::debug('Сохранение запрошенной накладной. Успешно. Файл: '
//                . $params['documentNumber']
//                . '_created_invoice' . '.xml',
//                'test');
        } catch (\Exception $e) {
           //Yii::debug('Сохранение запрошенной накладной. Ошибка: ' . $e->getMessage(), 'test');
            Yii::error($e->getMessage(), 'test');
        }
//        Yii::debug($result, 'test');
        return $result;
    }

    /**
     * Получает расходную накладную по ее номеру
     * @param string $num Номер накладной
     * @return string
     */
    public function getExpenseInvoice($num)
    {
        $params = [
            'key' => $this->token,
            'number' => $num,
            'currentYear' => 'true'
        ];

        $query = http_build_query($params);

        $this->request_string = $this->base_url . 'resto/api/documents/export/outgoingInvoice/byNumber?' . $query;

        $result = $this->send('GET');

        return $result;
    }

    /**
     * https://ru.iiko.help/articles/#!api-documentations/schedule-price
     *
     * @param $departmentId
     * @return bool|mixed|string
     */
    public function getPrices($departmentId)
    {
        $params = [
            'dateFrom' => date('Y-m-d'),
            'type' => 'BASE',
            'departmentId' => $departmentId
        ];

        $this->request_string = $this->base_url . 'resto/api/v2/price?' . http_build_query($params);

        $result = $this->send();

        return $result;
    }
}