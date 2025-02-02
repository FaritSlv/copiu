<?php

namespace app\controllers;

use app\models\UploadForm;
use app\models\Users;
use Yii;
use app\models\Settings;
use app\models\search\SettingsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;

/**
 * SettingsController implements the CRUD actions for Settings model.
 */
class SettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors() : array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Users::isAdmin();
                        }
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @param $action
     * @return bool
     * @throws ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action): bool
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
     * Lists all Settings models.
     * @return mixed
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;

        $searchModel = new SettingsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $system_settings = [];
            $cms_settings = [];
            $profile_settings = [];

            $result = [];
            if ($request->isPost) {
                $keys = $request->post('keys');
                foreach ($keys as $key => $value) {
                    $model = Settings::findOne(['key' => $key]);
                    $model->value = $value;
                    if (!$model->save()) {
                        Yii::error($model->errors, '_error');
                        $result = ['success' => false, 'data' => 'При сохранении настроек возникла ошибка'];
                    }
                }
                if (!$result) {
                    $result = ['success' => true, 'data' => 'Настройки сохранены'];
                }
            }
            $settings = Settings::find()->andWhere(['user_id' => Yii::$app->user->identity->id])->all();

            $sys_info_settings = [
                'token',
                'token_date',
                'sync_nomenclature_next_chunk',
                'sync_nomenclature_sync_date',
                'get_nomenclature_date',
                'entities_version',
                'delivery_main_unit',
                'delivery_eid',
                'get_prices_date',
                'sync_price_date',
                'sync_price_next_chunk',
            ];

            $profile_set = [
                'delivery_min_time',
                'delivery_max_time',
                'delivery_period',
                'check_quantity_enabled',
                'comment_required',
                'delivery_article',
                'delivery_nds',
                'delivery_min_sum',
                'delivery_disabled_days',
                'delivery_message',
            ];

            /** @var Settings $setting */
            foreach ($settings as $setting) {
                if (in_array($setting->key, $sys_info_settings)) {
                    continue;
                }
                if ($setting->is_system) {
                    array_push($system_settings, $setting);
                } else {
                    if (!in_array($setting->key, $profile_set)){
                        array_push($profile_settings, $setting);
                    } else {
                        array_push($cms_settings, $setting);
                    }
                }
            }

            return [
                'title' => 'Настройки',
                'content' => $this->renderAjax('_form', [
                    'settings' => [
                        'system' => $system_settings,
                        'cms' => $cms_settings,
                        'profile' => $profile_settings,
                    ],
                    'result' => $result,
                ]),
                'footer' => Html::button('Закрыть',
                        ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
            ];

        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Settings model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Settings #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Закрыть',
                        ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Редактировать', ['update', 'id' => $id],
                        ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Settings model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Settings();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new Settings",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Закрыть',
                            ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else {
                if ($model->load($request->post()) && $model->save()) {
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'title' => "Create new Settings",
                        'content' => '<span class="text-success">Create Settings success</span>',
                        'footer' => Html::button('Закрыть',
                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                    ];
                } else {
                    return [
                        'title' => "Create new Settings",
                        'content' => $this->renderAjax('create', [
                            'model' => $model,
                        ]),
                        'footer' => Html::button('Закрыть',
                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])

                    ];
                }
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }

    }

    /**
     * Updates an existing Settings model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update Settings #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Закрыть',
                            ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else {
                if ($model->load($request->post()) && $model->save()) {
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'title' => "Settings #" . $id,
                        'content' => $this->renderAjax('view', [
                            'model' => $model,
                        ]),
                        'footer' => Html::button('Закрыть',
                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::a('Редактировать', ['update', 'id' => $id],
                                ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                    ];
                } else {
                    return [
                        'title' => "Update Settings #" . $id,
                        'content' => $this->renderAjax('update', [
                            'model' => $model,
                        ]),
                        'footer' => Html::button('Закрыть',
                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                    ];
                }
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing Settings model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    /**
     * Delete multiple existing Settings model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the Settings model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Settings the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Settings::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionSystemInfo()
    {
        $request = Yii::$app->request;

        $sys_info_settings = [
            'token',
            'token_date',
            'sync_nomenclature_next_chunk',
            'sync_nomenclature_sync_date',
            'get_nomenclature_date',
            'entities_version',
            'delivery_main_unit',
            'delivery_eid',
            'get_prices_date',
            'sync_price_date',
            'sync_price_next_chunk',
        ];

        $settings = Settings::find();
        $sys_info = [];
        /** @var Settings $item */
        foreach ($settings->each() as $item) {
            if (in_array($item->key, $sys_info_settings)) {
                $sys_info[] = $item;
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'Системная информация',
                'content' => $this->renderAjax('_system_info', [
                    'settings' => $sys_info,
                ])
            ];
        } else {
            return $this->redirect('index');
        }
    }

    public function actionShowErrors()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Settings::checkSettings();

        if (!$data['success']) {
            return [
                'title' => 'Проверка настроек',
                'content' => $this->renderAjax('_show_errors', [
                    'data' => $data['errors'],
                ]),
                'footer' => Html::button('Закрыть',
                    ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"])
            ];
        } else {
            return [
                'title' => 'Проверка настроек',
                'content' => 'Ошибок не выявлено',
                'footer' => Html::button('Закрыть',
                    ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"])
            ];
        }
    }


    public function actionPrice()
    {
        $model = new UploadForm();

        if (Yii::$app->request->isPost) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload()) {
                Yii::$app->session->setFlash('info', 'Файл успешно загружен');
            }
        }

        return $this->render('upload', ['model' => $model]);
    }

}
