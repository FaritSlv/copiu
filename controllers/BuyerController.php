<?php

namespace app\controllers;

use app\models\Users;
use Yii;
use app\models\Buyer;
use app\models\search\BuyerSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * BuyerController implements the CRUD actions for Buyer model.
 */
class BuyerController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function($rule, $action){
                            return Users::isAdmin();
                        }
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
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
     * Lists all Buyer models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BuyerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Buyer model.
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
                'title' => "Buyer #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Закрыть', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Редактировать', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Buyer model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Buyer();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Добавление покупателя",
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
                        'forceClose' => true,
                    ];
                } else {
                    return [
                        'title' => "Добавление покупателя",
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
     * Updates an existing Buyer model.
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
        $user_model = $model->user;
        $addresses = $model->addresses;

        if (!$user_model) {
            $user_model = new Users();
        }
        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => $model->name,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                        'user_model' => $user_model,
                        'addresses' => $addresses,
                    ]),
                    'footer' => Html::button('Закрыть',
                            ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else {
                if ($model->load($request->post())) {
                    //Yii::debug($model->attributes, 'test');
                    if (!$model->user_id) {
                        $user_model->load($request->post());
                        //Проверяем указан ли логин
                        if ($user_model->login) {
                            //Проверяем указан ли пароль
                            if ($user_model->open_pass) {
                                $user_model->fio = $model->name;
                                $user_model->role = $user_model::ROLE_BUYER;
                                $user_model->password = $user_model->open_pass;
                                if (!$user_model->save()) {
                                    Yii::error($user_model->errors, '_error');
                                    return [
                                        'title' => $model->name,
                                        'content' => $this->renderAjax('update', [
                                            'model' => $model,
                                            'user_model' => $user_model,
                                            'addresses' => $addresses,
                                        ]),
                                        'footer' => Html::button('Закрыть',
                                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                            Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                                    ];
                                }
                                $model->user_id = $user_model->id;
                                if (!$model->save()) {
                                    Yii::error($user_model->errors, '_error');
                                    return [
                                        'title' => $model->name,
                                        'content' => $this->renderAjax('update', [
                                            'model' => $model,
                                            'user_model' => $user_model,
                                            'addresses' => $addresses,
                                        ]),
                                        'footer' => Html::button('Закрыть',
                                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                            Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                                    ];
                                }
                            } else {
                                $user_model->addError('open_pass', 'Не указан пароль');
                                return [
                                    'title' => $model->name,
                                    'content' => $this->renderAjax('update', [
                                        'model' => $model,
                                        'user_model' => $user_model,
                                        'addresses' => $addresses,
                                    ]),
                                    'footer' => Html::button('Закрыть',
                                            ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                        Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                                ];
                            }
                        }
                    }
                    if (!$model->save()) {
                        Yii::error($user_model->errors, '_error');
                        return [
                            'title' => $model->name,
                            'content' => $this->renderAjax('update', [
                                'model' => $model,
                                'user_model' => $user_model,
                                'addresses' => $addresses,
                            ]),
                            'footer' => Html::button('Закрыть',
                                    ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                        ];
                    }
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'forceClose' => true,
                    ];
                } else {
                    return [
                        'title' => "Редактирование покупателя",
                        'content' => $this->renderAjax('update', [
                            'model' => $model,
                            'user_model' => $user_model,
                            'addresses' => $addresses,
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
                    'user_model' => $user_model,
                    'addresses' => $addresses,
                ]);
            }
        }
    }

    /**
     * Delete an existing Buyer model.
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
     * Finds the Buyer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Buyer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Buyer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
