<?php

namespace app\controllers;

use app\models\Room;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Master ruangan. Daftar dapat dilihat semua pengguna; perubahan hanya admin.
 */
class RoomController extends BaseController
{
    protected function adminOnlyActions(): array
    {
        return ['create', 'update', 'toggle'];
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
            'rooms' => Room::find()->with('beds')->orderBy(['is_active' => SORT_DESC, 'name' => SORT_ASC])->all(),
        ]);
    }

    public function actionCreate()
    {
        $model = new Room(['is_active' => 1]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Ruangan ' . $model->name . ' berhasil ditambahkan.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Ruangan ' . $model->name . ' berhasil diperbarui.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionToggle(int $id): Response
    {
        $model = $this->findModel($id);

        if ($model->is_active && $model->getActiveSessionCount() > 0) {
            Yii::$app->session->setFlash('error', 'Ruangan ' . $model->name . ' masih memiliki sesi infus aktif dan tidak dapat dinonaktifkan.');
            return $this->redirect(['index']);
        }

        $model->is_active = $model->is_active ? 0 : 1;
        $model->save(false);
        Yii::$app->session->setFlash('success', 'Ruangan ' . $model->name . ($model->is_active ? ' diaktifkan.' : ' dinonaktifkan.'));

        return $this->redirect(['index']);
    }

    private function findModel(int $id): Room
    {
        $model = Room::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Ruangan tidak ditemukan.');
        }

        return $model;
    }
}
