<?php

namespace app\controllers;

use Yii;
use app\models\Users;
use app\models\search\UsersSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends Controller
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
     * Lists all Users models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (Users::isBuyer()){
            return $this->render('view', [
                'model' => Users::getUser(),
            ]);
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Users model.
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
                'title' => "Пользователь #" . $id,
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
     * Creates a new Users model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Users();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Добавление пользователя",
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
                        'title' => "Добавление пользователя",
                        'content' => '<span class="text-success">Create Users success</span>',
                        'footer' => Html::button('Закрыть',
                                ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                    ];
                } else {
                    return [
                        'title' => "Добавление пользователя",
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
     * Updates an existing Users model.
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
                    'title' => "Редактирование пользователя #" . $id,
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
                        'forceClose' => true,
                    ];
                } else {
                    return [
                        'title' => "Редактирование пользователя #" . $id,
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
     * Delete an existing Users model.
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
        if (!Users::isAdmin()){
            throw new ForbiddenHttpException('Доступ запрещен!');
        } else {
            $this->findModel($id)->delete();
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
     * Delete multiple existing Users model.
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
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Users the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Users::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionProfile()
    {
        $request = Yii::$app->request;

        $id = Yii::$app->user->id;

        $model = Users::findOne($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => 'Профиль ' . $model->fio,
                    'content' => $this->renderAjax('profile', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Закрыть',
                            ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Сохранить', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else {
                $model->load($request->post());
                $model->save();

                return [
                    'forceClose' => true,
                ];
            }
        }

        return $this->render('profile', [
            'model' => $model,
        ]);
    }
}
