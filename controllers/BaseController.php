<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * Controller dasar: seluruh aksi memerlukan login.
 * Turunan dapat menimpa adminOnlyActions() untuk membatasi aksi ke admin.
 */
abstract class BaseController extends Controller
{
    /**
     * @return string[] daftar id aksi yang hanya boleh diakses admin
     */
    protected function adminOnlyActions(): array
    {
        return [];
    }

    public function behaviors()
    {
        $adminOnly = $this->adminOnlyActions();
        $rules = [];

        if ($adminOnly) {
            $rules[] = [
                'allow' => true,
                'actions' => $adminOnly,
                'roles' => ['@'],
                'matchCallback' => static function () {
                    $identity = Yii::$app->user->identity;
                    return $identity instanceof User && $identity->getIsAdmin();
                },
            ];
            // Pengguna login non-admin: tolak dengan 403 (bukan redirect ke login).
            $rules[] = [
                'allow' => false,
                'actions' => $adminOnly,
                'roles' => ['@'],
                'denyCallback' => static function () {
                    throw new ForbiddenHttpException('Halaman ini hanya untuk administrator.');
                },
            ];
        }

        $rules[] = [
            'allow' => true,
            'roles' => ['@'],
        ];

        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => $rules,
            ],
        ];
    }

    protected function isAjax(): bool
    {
        return Yii::$app->request->isAjax;
    }

    protected function currentUser(): ?User
    {
        $identity = Yii::$app->user->identity;

        return $identity instanceof User ? $identity : null;
    }
}
