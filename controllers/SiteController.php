<?php

namespace app\controllers;

use app\components\IkkoApiHelper;
use app\components\PostmanApiHelper;
use app\models\Buyer;
use app\models\NGroup;
use app\models\Nomenclature;
use app\models\PriceCategory;
use app\models\Settings;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['sync-nomenclature','get-nomenclature'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * @param $action
     * @return bool
     * @throws ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if (!Yii::$app->user->can($action->id)) {
                throw new ForbiddenHttpException('Доступ запрещен!');
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }
        return $this->render('index');

    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        $this->layout = '//main-login';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            //Проверяем доступ
            $access = $model->checkAccess();
            if (!$access['success']) {
                Yii::$app->user->logout();
                $model->addError('password', $access['error']);
                return $this->render('login', [
                    'model' => $model,
                ]);
            }

            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Отображет форму с кнопками синхронизации
     * @return array
     */
    public function actionSyncing()
    {
        $request = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;

        $syncing_methods = [
            '1. Синхронизация покупателей и ценовых категорий' => '/site/sync-all',
//            'Синхронизация покупателей' => '/site/sync-buyer',
//            'Синхронизация ценовых категорий' => '/site/sync-price-category',
            '2. Синхронизация групп номенклатуры' => '/site/sync-nomenclature-group',
            '3. Синхронизация номенклатуры' => '/site/get-nomenclature',
            '4. Синхронизация цен для ценовых категорий' => '/site/sync-price-for-p-c',
        ];

        if ($request->isGet) {
            return [
                'title' => 'Синхронизация данных',
                'content' => $this->renderAjax('sync', [
                    'syncing_methods' => $syncing_methods
                ]),
            ];
        }
        return [
            'title' => 'Синхронизация данных',
            'content' => 'Ок',
        ];
    }

    /**
     * Синхронизация покупателей
     */
    public function actionSyncBuyer()
    {
        set_time_limit(600);
        ini_set("memory_limit", "2000M");
        Yii::$app->response->format = Response::FORMAT_JSON;

        $helper = new PostmanApiHelper();
        $buyer_model = new Buyer();
        return $buyer_model->sync($helper->getBuyers());

//        return [
//            'success' => false,
//            'data' => 'Синхронизация покупателей прошла успешно',
//            'error' => 'Ошбика Синхронизации покупателей категорий',
//        ];
    }

    /**
     * Синхронизация ценовых категорий
     */
    public function actionSyncPriceCategory()
    {
        set_time_limit(600);
        ini_set("memory_limit", "2000M");

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'success' => false,
            'data' => 'Синхронизация ценовых категорий прошла успешно',
            'error' => 'Ошибка Синхронизации ценовых категорий',
        ];
    }

    /**
     * Синхронизация ценовых категорий ипокупателей
     */
    public function actionSyncAll()
    {
        set_time_limit(600);
//        ini_set("memory_limit", "128M");

        Yii::$app->response->format = Response::FORMAT_JSON;

        $helper = new PostmanApiHelper();
        $buyer_model = new Buyer();
        $pc_model = new PriceCategory();

        $data = $helper->getAll();

        if (isset($data['success']) && $data['success'] === false) {
            return $data;
        }

        $sync_pc_result = $pc_model->sync($data['price_category']);

        if (!$sync_pc_result['success']) {
            return [
                'success' => false,
                'error' => 'Ошбика синхронизации ценовых категорий',
            ];
        }
        $sync_buyer_result = $buyer_model->sync($data['buyer']);
        if (!$sync_buyer_result['success']) {
            return [
                'success' => false,
                'error' => 'Ошбика синхронизации покупателей',
            ];
        }
//        Yii::warning('Всего памяти ' . memory_get_usage(true), 'test');

        return [
            'success' => true,
            'data' => 'Синхронизация покупателей и ценовых категорий прошла успешно',
        ];
    }

    /**
     * Получение и сохранение в файл номенклатуры
     * @return array|mixed
     * @throws \Exception
     */
    public function actionGetNomenclature()
    {
        //Проверяем период получения номенклатуры
        $last_time = strtotime(Settings::getValueByKey('sync_nomenclature_sync_date'));
        $diff_time = time() - $last_time;
        if ($diff_time < (60 * 60 * 12)){
            return 'Ожидание синхронизации';
        }

        set_time_limit(600);
//        ini_set("memory_limit", "128M");

        Yii::$app->response->format = Response::FORMAT_JSON;
        $ikko = new IkkoApiHelper();

       $ikko->getItems();

       Settings::setValueByKey('get_nomenclature_date', date('Y-m-d H:i:s', time()));

        return [
            'success' => true,
            'data' => 'Файл номенклатуры создан успешно. Номенклатура будет синхронизирована в течении получаса',
        ];
    }

    /**
     * Синхронизация номенклатуры.
     * Производится частями по 500 позиций
     */
    public function actionSyncNomenclature()
    {
        //Проверяем период синхронизации номенклатуры
        $last_time = strtotime(Settings::getValueByKey('sync_nomenclature_sync_date'));
        $diff_time = time() - $last_time;
        if ($diff_time < 110){
            return 'Ожидание синхронизации';
        }
        set_time_limit(600);

        $path_json = 'uploads/list_items.json';
        Yii::info('Файл найден: ' . (int)is_file($path_json), 'test');
        if (!is_file($path_json)) {
            return 'Файл не найден';
        } else {
            $json = file_get_contents($path_json);

            $data = json_decode($json, true);
            Yii::info('Всего записей: ' . count($data), 'test');

            $next_chunk = (int)Settings::getValueByKey('sync_nomenclature_next_chunk');
            $chunk_data = array_chunk($data, 500);
            $count_chank = count($chunk_data);
            Yii::info('Всего чанков: ' . $count_chank, 'test');

            if ($next_chunk === null) {
                $next_chunk = 0;
            }

            if (!isset($chunk_data[$next_chunk]) || !$chunk_data[$next_chunk]){
                //Если нет чанка или он пустой - значит данные все импортированы, завршаем импорт
                Settings::setValueByKey('sync_nomenclature_next_chunk', null);
                try {
                    unlink($path_json);
                } catch (\Exception $e) {
                    Yii::error($e->getMessage(), '_error');
                }
            }

            $result = Nomenclature::import($chunk_data[$next_chunk]);
            $chunk_num = $next_chunk + 1;
            $result['chunk_done'] = "{$chunk_num} из {$count_chank}";
            Settings::setValueByKey('sync_nomenclature_sync_date', date('Y-m-d H:i:s', time()));

            if ($result['success']){
                $next_chunk++;
                Settings::setValueByKey('sync_nomenclature_next_chunk', (string)$next_chunk);
            }
            Yii::warning('Всего памяти ' . memory_get_usage(true), 'test');
            VarDumper::dump($result, 10, true);
        }

    }

    public function actionSyncNomenclatureGroup()
    {
        set_time_limit(300);
//        ini_set("memory_limit", "128M");

        Yii::$app->response->format = Response::FORMAT_JSON;
        $ikko = new IkkoApiHelper();

        $items = $ikko->getNomenclatureGroups();
        if (isset($items['success']) && !$items['success']) {
            return $items;
        }
//        Yii::info(isset($items[0]) ? $items[0] : 'Данные не получены', 'test');

        //Импортируем Группы номенклатуры
        $n_group = new NGroup();
        return $n_group->import($items);
    }

    /**
     * Синхронизация цен для ценовых категорий
     */
    public function actionSyncPriceForPC()
    {
        set_time_limit(600);
//        ini_set("memory_limit", "128M");

        Yii::$app->response->format = Response::FORMAT_JSON;
        $postman = new PostmanApiHelper();

        $result = $postman->getPriceListItems();
        Yii::warning('Всего памяти ' . memory_get_usage(true), 'test');

        return $result;
    }

    /**
     * Для тестов
     */
    public function actionTest()
    {
        $result = json_decode(file_get_contents('uploads/list_items.json'), true);
//        $helper = new PostmanApiHelper();
//        $result = $helper->getItems();

//        $ikko = new IkkoApiHelper();
//        $invoice_params = [
//            'documentNumber' => 'A345f',
//            'dateIncoming' => date('Y-m-d H:i:s', time()),
//            'counteragentId' => '2e8fe03e-a13c-4f0b-8100-0d24350d0e1c',
//            'from' => '13:00',
//            'to' => '15:00',
//            'items' => [
//                [
//                    'productId' => 'f8e8cb4c-6337-46db-b41d-003e50b30d2f',
//                    'num' => '12347',
//                    'amount' => 3,
//                    'price' => 10,
//                    'sum' => 30,
//                ]
//            ],
//        ];
//        $result = $ikko->makeExpenseInvoice($invoice_params);
//        $sum = $ikko->getBalance();
//        $result = $ikko->getItems();
//        $result = $ikko->logout();

        Yii::warning('Всего памяти ' . memory_get_usage(true), 'test');
        VarDumper::dump($result, 10, true);
//        VarDumper::dump($result[0], 10, true);
//        return $result;
    }
}
