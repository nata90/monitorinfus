<?php

namespace app\controllers;

use app\models\Bed;
use app\models\InfusionSession;
use app\models\Room;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class BedController extends BaseController
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

    public function actionIndex(?int $room = null): string
    {
        $q = trim((string) Yii::$app->request->get('q', ''));

        $query = Bed::find()
            ->alias('b')
            ->with(['activeSession', 'room'])
            ->innerJoin(['r' => Room::tableName()], 'r.id = b.room_id')
            ->orderBy(['b.is_active' => SORT_DESC, 'r.name' => SORT_ASC, 'b.code' => SORT_ASC]);
        if ($q !== '') {
            $query->andWhere(['or', ['like', 'b.code', $q], ['like', 'r.name', $q]]);
        }
        if ($room) {
            $query->andWhere(['b.room_id' => $room]);
        }

        return $this->render('index', [
            'beds' => $query->all(),
            'q' => $q,
            'rooms' => Room::options(false),
            'roomId' => $room ?: null,
        ]);
    }

    public function actionCreate(?int $room = null)
    {
        $model = new Bed(['is_active' => 1, 'room_id' => $room ?: null]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Bed ' . $model->code . ' berhasil ditambahkan.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model, 'rooms' => Room::options()]);
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Bed ' . $model->code . ' berhasil diperbarui.');
            return $this->redirect(['index']);
        }

        $rooms = Room::options();
        if ($model->room && !isset($rooms[$model->room_id])) {
            $rooms[$model->room_id] = $model->room->name . ' (nonaktif)';
        }

        return $this->render('update', ['model' => $model, 'rooms' => $rooms]);
    }

    public function actionToggle(int $id): Response
    {
        $model = $this->findModel($id);

        if ($model->is_active && $model->activeSession) {
            Yii::$app->session->setFlash('error', 'Bed ' . $model->code . ' masih memiliki sesi infus aktif dan tidak dapat dinonaktifkan.');
            return $this->redirect(['index']);
        }

        $model->is_active = $model->is_active ? 0 : 1;
        $model->save(false);
        Yii::$app->session->setFlash('success', 'Bed ' . $model->code . ($model->is_active ? ' diaktifkan.' : ' dinonaktifkan.'));

        return $this->redirect(['index']);
    }

    /**
     * Riwayat sesi infus satu bed dengan filter status & rentang tanggal.
     */
    public function actionHistory(int $id): string
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $status = (string) $request->get('status', '');
        $from = (string) $request->get('from', '');
        $to = (string) $request->get('to', '');

        $query = InfusionSession::find()
            ->with(['creator', 'updater'])
            ->where(['bed_id' => $model->id])
            ->orderBy(['started_at' => SORT_DESC]);

        if ($status !== '' && array_key_exists($status, InfusionSession::statusLabels())) {
            $query->andWhere(['status' => $status]);
        }
        if ($from !== '' && strtotime($from) !== false) {
            $query->andWhere(['>=', 'started_at', date('Y-m-d 00:00:00', strtotime($from))]);
        }
        if ($to !== '' && strtotime($to) !== false) {
            $query->andWhere(['<=', 'started_at', date('Y-m-d 23:59:59', strtotime($to))]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => false,
        ]);

        return $this->render('history', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'filters' => ['status' => $status, 'from' => $from, 'to' => $to],
        ]);
    }

    private function findModel(int $id): Bed
    {
        $model = Bed::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Bed tidak ditemukan.');
        }

        return $model;
    }
}
