<?php

namespace app\controllers;

use app\models\AppSetting;
use app\models\Bed;
use app\models\InfusionSession;
use app\models\Room;
use DateTimeImmutable;
use Yii;
use yii\web\Response;

class DashboardController extends BaseController
{
    /**
     * @param int|null $room filter ruangan (null/0 = semua ruangan)
     */
    public function actionIndex(?int $room = null): string
    {
        $now = new DateTimeImmutable();
        $roomId = $this->resolveRoom($room);

        return $this->render('index', [
            'payload' => $this->buildPayload($now, $roomId),
            'rooms' => Room::options(),
            'roomId' => $roomId,
            'now' => $now,
        ]);
    }

    /**
     * GET /api/infusions/active?room=ID — data sesi aktif + waktu server.
     */
    public function actionData(?int $room = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->buildPayload(new DateTimeImmutable(), $this->resolveRoom($room));
    }

    private function resolveRoom(?int $room): ?int
    {
        if (!$room) {
            return null;
        }

        return Room::find()->where(['id' => $room])->exists() ? $room : null;
    }

    private function buildPayload(DateTimeImmutable $now, ?int $roomId): array
    {
        $query = InfusionSession::find()
            ->alias('s')
            ->with('bed.room')
            ->where(['s.status' => InfusionSession::STATUS_ACTIVE])
            ->orderBy(['s.estimated_end_at' => SORT_ASC]);

        if ($roomId !== null) {
            $query->innerJoin(['b' => Bed::tableName()], 'b.id = s.bed_id')->andWhere(['b.room_id' => $roomId]);
        }

        /** @var InfusionSession[] $sessions */
        $sessions = $query->all();

        $items = [];
        foreach ($sessions as $session) {
            $items[] = $session->toDashboardArray($now);
        }

        $idleBeds = array_map(static function (Bed $bed) {
            return [
                'id' => (int) $bed->id,
                'code' => $bed->code,
                'room_id' => (int) $bed->room_id,
                'room_name' => $bed->getRoomName(),
            ];
        }, Bed::findAvailable(null, $roomId));

        $roomName = null;
        if ($roomId !== null) {
            $r = Room::findOne($roomId);
            $roomName = $r ? $r->name : null;
        }

        return [
            'server_time' => $now->format(DATE_ATOM),
            'server_timestamp_ms' => (int) round(microtime(true) * 1000),
            'room_id' => $roomId,
            'room_name' => $roomName,
            'warning_minutes' => AppSetting::warningMinutes(),
            'alarm_minutes' => AppSetting::alarmMinutes(),
            'sync_seconds' => (int) (Yii::$app->params['dashboardSyncSeconds'] ?? 30),
            'alarm_repeat_seconds' => (int) (Yii::$app->params['alarmRepeatSeconds'] ?? 3),
            'sessions' => $items,
            'idle_beds' => $idleBeds,
        ];
    }
}
