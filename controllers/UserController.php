<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Manajemen akun pengguna (khusus admin).
 */
class UserController extends BaseController
{
    protected function adminOnlyActions(): array
    {
        return ['index', 'create', 'update', 'toggle'];
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'toggle' => ['post'],
                ],
            ],
        ]);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'users' => User::find()->orderBy(['status' => SORT_DESC, 'username' => SORT_ASC])->all(),
        ]);
    }

    public function actionCreate()
    {
        $model = new User(['scenario' => 'create']);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Pengguna ' . $model->username . ' berhasil ditambahkan.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Pengguna ' . $model->username . ' berhasil diperbarui.');
            return $this->redirect(['index']);
        }
        $model->password = null;

        return $this->render('update', ['model' => $model]);
    }

    public function actionToggle(int $id): Response
    {
        $model = $this->findModel($id);

        if ((int) $model->id === (int) Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Akun yang sedang login tidak dapat dinonaktifkan.');
            return $this->redirect(['index']);
        }

        $model->status = $model->status ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
        $model->save(false);
        Yii::$app->session->setFlash('success', 'Pengguna ' . $model->username . ($model->status ? ' diaktifkan.' : ' dinonaktifkan.'));

        return $this->redirect(['index']);
    }

    private function findModel(int $id): User
    {
        $model = User::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Pengguna tidak ditemukan.');
        }

        return $model;
    }
}
