<?php

namespace app\controllers;

use app\models\Bed;
use app\models\InfusionSession;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class InfusionController extends BaseController
{
    /** @var int|null batasi pilihan bed pada form ke satu ruangan (dari filter dashboard) */
    private $roomFilter;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'complete' => ['post'],
                    'stop' => ['post'],
                ],
            ],
        ]);
    }

    /**
     * Form sesi baru. Dibuka sebagai modal (AJAX) dari dashboard, atau halaman penuh.
     */
    public function actionCreate(?int $bed_id = null, ?int $room_id = null)
    {
        $model = new InfusionSession();
        $model->status = InfusionSession::STATUS_ACTIVE;
        if ($bed_id) {
            $model->bed_id = $bed_id;
        }
        $this->roomFilter = $room_id ?: null;
        if (!$model->drop_factor) {
            $model->drop_factor = 20;
        }
        $model->started_at_input = (new \DateTimeImmutable())->format(InfusionSession::INPUT_FORMAT);

        return $this->handleForm($model, 'Sesi infus berhasil dibuat.');
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        if (!$model->getIsActive()) {
            Yii::$app->session->setFlash('warning', 'Sesi yang sudah selesai/dihentikan tidak dapat diubah.');
            return $this->redirect(['bed/history', 'id' => $model->bed_id]);
        }

        return $this->handleForm($model, 'Sesi infus berhasil diperbarui.');
    }

    public function actionView(int $id): string
    {
        $model = $this->findModel($id);

        return $this->render('view', ['model' => $model]);
    }

    public function actionComplete(int $id)
    {
        $model = $this->findModel($id);
        if ($model->getIsActive()) {
            $model->markCompleted();
            $message = 'Sesi infus bed ' . $model->bed->code . ' ditandai selesai.';
        } else {
            $message = 'Sesi sudah tidak aktif.';
        }

        return $this->afterStatusChange($model, $message);
    }

    public function actionStop(int $id)
    {
        $model = $this->findModel($id);
        if ($model->getIsActive()) {
            $model->markStopped();
            $message = 'Sesi infus bed ' . $model->bed->code . ' dihentikan.';
        } else {
            $message = 'Sesi sudah tidak aktif.';
        }

        return $this->afterStatusChange($model, $message);
    }

    // ---- helpers ----

    private function handleForm(InfusionSession $model, string $successMessage)
    {
        $request = Yii::$app->request;

        if ($request->isPost && $model->load($request->post())) {
            if ($model->save()) {
                if ($this->isAjax()) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => $successMessage, 'session' => $model->toDashboardArray()];
                }
                Yii::$app->session->setFlash('success', $successMessage);
                return $this->redirect(['dashboard/index']);
            }
        }

        $beds = Bed::findAvailable($model->isNewRecord ? null : (int) $model->bed_id, $this->roomFilter);
        $params = [
            'model' => $model,
            'beds' => Bed::groupedOptions($beds),
            'roomId' => $this->roomFilter,
        ];

        if ($this->isAjax()) {
            return $this->renderAjax('_form', $params);
        }

        return $this->render($model->isNewRecord ? 'create' : 'update', $params);
    }

    private function afterStatusChange(InfusionSession $model, string $message)
    {
        if ($this->isAjax()) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['success' => true, 'message' => $message, 'id' => (int) $model->id, 'status' => $model->status];
        }

        Yii::$app->session->setFlash('success', $message);
        $return = Yii::$app->request->post('returnUrl');

        return $this->redirect($return ?: ['dashboard/index']);
    }

    private function findModel(int $id): InfusionSession
    {
        $model = InfusionSession::find()->with('bed.room')->where(['id' => $id])->one();
        if ($model === null) {
            throw new NotFoundHttpException('Sesi infus tidak ditemukan.');
        }

        return $model;
    }
}
